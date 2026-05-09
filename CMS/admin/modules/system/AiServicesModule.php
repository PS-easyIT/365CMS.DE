<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Database;
use CMS\Logger;
use CMS\Services\AI\AiSettingsService;

final class AiServicesModule
{
    private const int MAX_TEXT_LENGTH = 120;
    private const int MAX_URL_LENGTH = 255;
    private const int MAX_PROVIDER_ENTRIES = 20;
    private const int USAGE_LOOKBACK_DAYS = 30;
    private const int USAGE_HISTORY_LIMIT = 15;
    private const int USAGE_DATASET_LIMIT = 500;
    /** @var array<string, string> */
    private const SUPPORTED_EDITORJS_BLOCK_TYPES = [
        'paragraph' => 'paragraph',
        'header' => 'header',
        'list' => 'list',
        'checklist' => 'checklist',
        'quote' => 'quote',
        'callout' => 'callout',
        'warning' => 'warning',
        'mediatext' => 'mediaText',
    ];

    private ?AiSettingsService $settings = null;
    private ?Database $db = null;
    private string $dbPrefix = '';
    private string $initializationError = '';

    public function __construct()
    {
        try {
            $this->settings = AiSettingsService::getInstance();
            $this->db = Database::instance();
            $this->dbPrefix = $this->db->getPrefix();
        } catch (\Throwable $e) {
            $this->initializationError = 'AI-Services konnten nicht initialisiert werden. Bitte Datenbank-/Runtime-Logs prüfen.';
            Logger::instance()->withChannel('admin.ai-services')->error('AI-Services Initialisierung fehlgeschlagen.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function getData(string $section = 'overview'): array
    {
        try {
            if ($this->settings === null) {
                $fallback = $this->getDefaultData();
                $fallback['error'] = $this->initializationError !== '' ? $this->initializationError : 'AI-Services-Konfiguration konnte gerade nicht geladen werden.';

                return $fallback;
            }

            $configuration = $this->settings->getConfiguration();

            if ($section === 'overview') {
                $configuration = $this->attachOverviewInsights($configuration);
            }

            return $configuration;
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-services')->error('AI-Settings konnten nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);

            $fallback = $this->getDefaultData();
            if ($section === 'overview') {
                $fallback = $this->attachOverviewInsights($fallback);
            }

            $fallback['error'] = 'AI-Services-Konfiguration konnte gerade nicht geladen werden. Bitte Logs prüfen.';

            return $fallback;
        }
    }

    /** @return array<string, mixed> */
    private function getDefaultData(): array
    {
        return [
            'providers' => ['active_provider_id' => 'mock', 'fallback_provider_id' => '', 'entries' => [], 'catalog' => []],
            'features' => [],
            'translation' => [],
            'logging' => [],
            'quotas' => [],
            'prompts' => [],
            'summary' => [],
            'monitoring' => [],
            'generation_history' => [],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function attachOverviewInsights(array $configuration): array
    {
        try {
            $providerLabels = $this->buildProviderLabelMap($configuration);
            $entries = $this->loadUsageEntries($providerLabels);
            $configuration['monitoring'] = $this->buildUsageMonitoring($entries, $configuration, $providerLabels);
            $configuration['generation_history'] = array_slice($entries, 0, self::USAGE_HISTORY_LIMIT);

            return $configuration;
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-services')->warning('AI-Nutzungsmonitoring konnte nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);

            $configuration['monitoring'] = [
                'load_error' => 'Nutzungsmonitoring konnte gerade nicht geladen werden.',
                'metrics_logging_enabled' => !empty($configuration['logging']['store_request_metrics']),
                'successes_30d' => 0,
                'failures_30d' => 0,
                'runs_24h' => 0,
                'failures_24h' => 0,
                'success_rate_30d' => 0,
                'avg_duration_ms_30d' => 0,
                'current_user' => [
                    'id' => $this->getCurrentUserId(),
                    'requests_24h' => 0,
                    'request_limit' => (int) ($configuration['quotas']['daily_requests_per_user'] ?? 0),
                    'request_usage_percent' => 0,
                    'chars_24h' => 0,
                    'char_limit' => (int) ($configuration['quotas']['daily_chars_per_user'] ?? 0),
                    'char_usage_percent' => 0,
                    'char_metrics_available' => false,
                ],
                'active_provider' => [
                    'provider_id' => $this->sanitizeProviderId((string) ($configuration['providers']['active_provider_id'] ?? '')),
                    'provider_label' => '—',
                    'requests_30d' => 0,
                    'blocks_30d' => 0,
                    'chars_30d' => 0,
                    'char_metrics_available' => false,
                    'request_limit' => (int) ($configuration['quotas']['monthly_requests_per_provider'] ?? 0),
                    'usage_percent' => 0,
                ],
                'provider_breakdown' => [],
            ];
            $configuration['generation_history'] = [];

            return $configuration;
        }
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, string>
     */
    private function buildProviderLabelMap(array $configuration): array
    {
        $labels = [];

        foreach ((array) ($configuration['providers']['entries'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $providerId = $this->sanitizeProviderId((string) ($entry['id'] ?? ''));
            if ($providerId === '') {
                continue;
            }

            $label = $this->sanitizeText((string) ($entry['label'] ?? ''), self::MAX_TEXT_LENGTH);
            $labels[$providerId] = $label !== '' ? $label : $providerId;
        }

        return $labels;
    }

    /**
     * @param array<string, string> $providerLabels
     * @return list<array<string, mixed>>
     */
    private function loadUsageEntries(array $providerLabels): array
    {
        if ($this->db === null || $this->dbPrefix === '') {
            throw new \RuntimeException('AI-Nutzungsdatenbank ist nicht initialisiert.');
        }

        $since = (new \DateTimeImmutable('-' . self::USAGE_LOOKBACK_DAYS . ' days'))->format('Y-m-d H:i:s');
        $rows = $this->db->get_results(
            "SELECT user_id, action, severity, description, metadata, created_at
             FROM {$this->dbPrefix}audit_log
             WHERE action IN (?, ?) AND created_at >= ?
             ORDER BY created_at DESC
             LIMIT ?",
            [
                'ai.editorjs.translate.processed',
                'ai.editorjs.translate.failed',
                $since,
                self::USAGE_DATASET_LIMIT,
            ]
        );

        $currentUserId = $this->getCurrentUserId();
        $entries = [];
        foreach ($rows as $row) {
            if (!is_object($row)) {
                continue;
            }

            $entries[] = $this->normalizeUsageEntry($row, $providerLabels, $currentUserId);
        }

        return $entries;
    }

    /**
     * @param array<string, string> $providerLabels
     * @return array<string, mixed>
     */
    private function normalizeUsageEntry(object $row, array $providerLabels, int $currentUserId): array
    {
        $metadata = [];
        $metadataRaw = $row->metadata ?? null;
        if (is_string($metadataRaw) && trim($metadataRaw) !== '') {
            $decoded = json_decode($metadataRaw, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $providerId = $this->sanitizeProviderId((string) ($metadata['provider'] ?? ''));
        $providerLabel = $providerLabels[$providerId] ?? ($providerId !== '' ? $providerId : '—');
        $userId = isset($row->user_id) ? (int) $row->user_id : 0;
        $status = (string) ($row->action ?? '') === 'ai.editorjs.translate.processed' ? 'success' : 'warning';
        $targetLocale = strtolower(trim((string) ($metadata['target_locale'] ?? '')));
        $targetLocale = $targetLocale !== '' ? strtoupper($targetLocale) : '—';

        return [
            'created_at' => trim((string) ($row->created_at ?? '')),
            'status' => $status,
            'status_label' => $status === 'success' ? 'erfolgreich' : 'fehlgeschlagen',
            'user_id' => $userId,
            'user_label' => $userId <= 0 ? 'System' : ($userId === $currentUserId ? 'Du' : 'User #' . $userId),
            'provider_id' => $providerId,
            'provider_label' => $providerLabel,
            'target_locale' => $targetLocale,
            'duration_ms' => $this->normalizePositiveNullable($metadata['duration_ms'] ?? null),
            'translated_blocks' => $this->normalizePositiveNullable($metadata['translated_blocks'] ?? null),
            'translated_segments' => $this->normalizePositiveNullable($metadata['translated_segments'] ?? null),
            'char_count' => $this->normalizePositiveNullable($metadata['char_count'] ?? null),
            'block_count' => $this->normalizePositiveNullable($metadata['block_count'] ?? null),
            'resolved_via' => $this->sanitizeText((string) ($metadata['resolved_via'] ?? 'direct'), self::MAX_TEXT_LENGTH) ?: 'direct',
            'description' => $this->sanitizeText((string) ($row->description ?? ''), self::MAX_TEXT_LENGTH),
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $configuration
     * @param array<string, string> $providerLabels
     * @return array<string, mixed>
     */
    private function buildUsageMonitoring(array $entries, array $configuration, array $providerLabels): array
    {
        $last24Hours = time() - 86400;
        $currentUserId = $this->getCurrentUserId();
        $quotas = is_array($configuration['quotas'] ?? null) ? $configuration['quotas'] : [];

        $successes30d = 0;
        $failures30d = 0;
        $successes24h = 0;
        $failures24h = 0;
        $durationTotal = 0;
        $durationCount = 0;
        $currentUserRequests24h = 0;
        $currentUserChars24h = 0;
        $currentUserCharMetricsAvailable = false;
        $providerBreakdown = [];

        foreach ($entries as $entry) {
            $entryTime = strtotime((string) ($entry['created_at'] ?? '')) ?: 0;
            $isSuccess = (string) ($entry['status'] ?? '') === 'success';
            $isRecent24h = $entryTime >= $last24Hours;

            if ($isSuccess) {
                ++$successes30d;
                if ($isRecent24h) {
                    ++$successes24h;
                }

                if (($entry['duration_ms'] ?? null) !== null) {
                    $durationTotal += (int) $entry['duration_ms'];
                    ++$durationCount;
                }

                $providerId = (string) ($entry['provider_id'] ?? '');
                $providerKey = $providerId !== '' ? $providerId : '__unknown__';
                if (!isset($providerBreakdown[$providerKey])) {
                    $providerBreakdown[$providerKey] = [
                        'provider_id' => $providerId,
                        'provider_label' => $providerLabels[$providerId] ?? ($providerId !== '' ? $providerId : 'Unbekannt'),
                        'requests_30d' => 0,
                        'blocks_30d' => 0,
                        'chars_30d' => 0,
                        'char_metrics_available' => false,
                        'request_limit' => (int) ($quotas['monthly_requests_per_provider'] ?? 0),
                        'usage_percent' => 0,
                    ];
                }

                ++$providerBreakdown[$providerKey]['requests_30d'];
                $providerBreakdown[$providerKey]['blocks_30d'] += max(0, (int) ($entry['translated_blocks'] ?? 0));

                if (($entry['char_count'] ?? null) !== null) {
                    $providerBreakdown[$providerKey]['chars_30d'] += (int) $entry['char_count'];
                    $providerBreakdown[$providerKey]['char_metrics_available'] = true;
                }

                if ($isRecent24h && (int) ($entry['user_id'] ?? 0) === $currentUserId) {
                    ++$currentUserRequests24h;
                    if (($entry['char_count'] ?? null) !== null) {
                        $currentUserChars24h += (int) $entry['char_count'];
                        $currentUserCharMetricsAvailable = true;
                    }
                }

                continue;
            }

            ++$failures30d;
            if ($isRecent24h) {
                ++$failures24h;
            }
        }

        foreach ($providerBreakdown as &$providerStats) {
            $providerStats['usage_percent'] = $this->calculateUsagePercent((int) $providerStats['requests_30d'], (int) $providerStats['request_limit']);
        }
        unset($providerStats);

        uasort(
            $providerBreakdown,
            static fn (array $left, array $right): int => ($right['requests_30d'] <=> $left['requests_30d'])
                ?: strcmp((string) $left['provider_label'], (string) $right['provider_label'])
        );

        $providerBreakdownList = array_values($providerBreakdown);
        $activeProviderId = $this->sanitizeProviderId((string) ($configuration['providers']['active_provider_id'] ?? ''));
        $activeProviderKey = $activeProviderId !== '' ? $activeProviderId : '__unknown__';
        $activeProvider = $providerBreakdown[$activeProviderKey] ?? [
            'provider_id' => $activeProviderId,
            'provider_label' => $providerLabels[$activeProviderId] ?? ($activeProviderId !== '' ? $activeProviderId : '—'),
            'requests_30d' => 0,
            'blocks_30d' => 0,
            'chars_30d' => 0,
            'char_metrics_available' => false,
            'request_limit' => (int) ($quotas['monthly_requests_per_provider'] ?? 0),
            'usage_percent' => 0,
        ];

        $totalRuns30d = $successes30d + $failures30d;

        return [
            'metrics_logging_enabled' => !empty($configuration['logging']['store_request_metrics']),
            'successes_30d' => $successes30d,
            'failures_30d' => $failures30d,
            'runs_24h' => $successes24h + $failures24h,
            'failures_24h' => $failures24h,
            'success_rate_30d' => $totalRuns30d > 0 ? (int) round(($successes30d / $totalRuns30d) * 100) : 0,
            'avg_duration_ms_30d' => $durationCount > 0 ? (int) round($durationTotal / $durationCount) : 0,
            'current_user' => [
                'id' => $currentUserId,
                'requests_24h' => $currentUserRequests24h,
                'request_limit' => (int) ($quotas['daily_requests_per_user'] ?? 0),
                'request_usage_percent' => $this->calculateUsagePercent($currentUserRequests24h, (int) ($quotas['daily_requests_per_user'] ?? 0)),
                'chars_24h' => $currentUserChars24h,
                'char_limit' => (int) ($quotas['daily_chars_per_user'] ?? 0),
                'char_usage_percent' => $currentUserCharMetricsAvailable ? $this->calculateUsagePercent($currentUserChars24h, (int) ($quotas['daily_chars_per_user'] ?? 0)) : 0,
                'char_metrics_available' => $currentUserCharMetricsAvailable,
            ],
            'active_provider' => $activeProvider,
            'provider_breakdown' => array_slice($providerBreakdownList, 0, 5),
        ];
    }

    private function normalizePositiveNullable(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return max(0, (int) $value);
    }

    private function calculateUsagePercent(int $used, int $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        return max(0, min(100, (int) round(($used / $limit) * 100)));
    }

    private function getCurrentUserId(): int
    {
        return isset($_SESSION['user_id']) ? max(0, (int) $_SESSION['user_id']) : 0;
    }

    /** @return array<string, mixed> */
    public function saveProviders(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Provider-Einstellungen konnten nicht gespeichert werden.');
            }

            $current = $this->settings->getConfiguration();
            $currentProviderEntries = array_values(array_filter(
                (array) ($current['providers']['entries'] ?? []),
                static fn (mixed $entry): bool => is_array($entry)
            ));
            $providerEntries = $this->sanitizeProviderEntries($post, $current);
            $providerIds = array_values(array_map(
                static fn (array $entry): string => (string) ($entry['id'] ?? ''),
                $providerEntries
            ));
            $removedProviderIds = [];
            foreach ($currentProviderEntries as $entry) {
                $entryId = $this->sanitizeProviderId((string) ($entry['id'] ?? ''));
                if ($entryId !== '' && !in_array($entryId, $providerIds, true)) {
                    $removedProviderIds[] = $entryId;
                }
            }

            $meta = [
                'active_provider_id' => $this->resolvePrimaryProviderSelection((string) ($post['active_provider_id'] ?? ''), $providerIds),
                'fallback_provider_id' => $this->sanitizeProviderSelection((string) ($post['fallback_provider_id'] ?? ''), $providerIds),
            ];

            if ($meta['fallback_provider_id'] !== '' && $meta['fallback_provider_id'] === $meta['active_provider_id']) {
                $meta['fallback_provider_id'] = '';
            }

            $secretValues = [];
            foreach ((array) ($post['provider_secret'] ?? []) as $providerId => $secretValue) {
                $providerId = $this->sanitizeProviderId((string) $providerId);
                $secretValue = trim((string) $secretValue);
                if ($providerId !== '' && $secretValue !== '') {
                    $secretValues[$providerId] = $secretValue;
                }
            }

            $clearSecrets = [];
            foreach (array_keys((array) ($post['clear_provider_secret'] ?? [])) as $providerId) {
                $providerId = $this->sanitizeProviderId((string) $providerId);
                if ($providerId !== '') {
                    $clearSecrets[] = $providerId;
                }
            }
            $clearSecrets = array_values(array_unique(array_merge($clearSecrets, $removedProviderIds)));

            if (!$this->settings->saveProviders($meta, $providerEntries, $secretValues, $clearSecrets)) {
                return ['success' => false, 'error' => 'Provider-Einstellungen konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.providers.save',
                'AI-Provider-Einstellungen gespeichert.',
                'setting',
                null,
                [
                    'active_provider_id' => $meta['active_provider_id'],
                    'fallback_provider_id' => $meta['fallback_provider_id'],
                    'providers_enabled' => count(array_filter($providerEntries, static fn (array $provider): bool => !empty($provider['enabled']))),
                    'provider_count' => count($providerEntries),
                    'secrets_updated' => count($secretValues),
                    'secrets_cleared' => $clearSecrets,
                ],
                'info'
            );

            return ['success' => true, 'message' => 'Provider-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.providers.save_failed', 'Provider-Einstellungen konnten nicht gespeichert werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function addProvider(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Provider-Eintrag konnte nicht angelegt werden.');
            }

            $providerType = $this->sanitizeProviderType((string) ($post['provider_type'] ?? ''), true);
            if ($providerType === '') {
                return ['success' => false, 'error' => 'Bitte einen unterstützten Providertyp auswählen.'];
            }

            $current = $this->settings->getConfiguration();
            $providersData = is_array($current['providers'] ?? null) ? $current['providers'] : [];
            $entries = array_values(array_filter(
                (array) ($providersData['entries'] ?? []),
                static fn (mixed $entry): bool => is_array($entry)
            ));

            if (count($entries) >= self::MAX_PROVIDER_ENTRIES) {
                return ['success' => false, 'error' => 'Es können maximal ' . self::MAX_PROVIDER_ENTRIES . ' Provider-Einträge verwaltet werden.'];
            }

            $newEntry = $this->settings->buildProviderEntry($providerType);
            $entries[] = $newEntry;

            $meta = [
                'active_provider_id' => (string) ($providersData['active_provider_id'] ?? ''),
                'fallback_provider_id' => (string) ($providersData['fallback_provider_id'] ?? ''),
            ];

            if (($meta['active_provider_id'] ?? '') === '') {
                $meta['active_provider_id'] = (string) ($newEntry['id'] ?? '');
            }

            if (!$this->settings->saveProviders($meta, $entries)) {
                return ['success' => false, 'error' => 'Provider-Eintrag konnte nicht angelegt werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.providers.add',
                'AI-Provider-Eintrag angelegt.',
                'setting',
                null,
                [
                    'provider_id' => (string) ($newEntry['id'] ?? ''),
                    'provider_type' => $providerType,
                ],
                'info'
            );

            return [
                'success' => true,
                'message' => 'Provider-Eintrag wurde angelegt.',
                'redirect_section' => 'settings',
            ];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.providers.add_failed', 'Provider-Eintrag konnte nicht angelegt werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function deleteProvider(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Provider-Eintrag konnte nicht gelöscht werden.');
            }

            $providerId = $this->sanitizeProviderId((string) ($post['provider_id'] ?? ''));
            if ($providerId === '') {
                return ['success' => false, 'error' => 'Der zu löschende Provider-Eintrag ist ungültig.'];
            }

            $current = $this->settings->getConfiguration();
            $providersData = is_array($current['providers'] ?? null) ? $current['providers'] : [];
            $entries = array_values(array_filter(
                (array) ($providersData['entries'] ?? []),
                static fn (mixed $entry): bool => is_array($entry)
            ));

            $remainingEntries = array_values(array_filter(
                $entries,
                static fn (array $entry): bool => (string) ($entry['id'] ?? '') !== $providerId
            ));

            if (count($remainingEntries) === count($entries)) {
                return ['success' => false, 'error' => 'Der gewählte Provider-Eintrag wurde nicht gefunden.'];
            }

            $remainingIds = array_values(array_map(
                static fn (array $entry): string => (string) ($entry['id'] ?? ''),
                $remainingEntries
            ));

            $meta = [
                'active_provider_id' => $this->resolvePrimaryProviderSelection((string) ($providersData['active_provider_id'] ?? ''), $remainingIds),
                'fallback_provider_id' => $this->sanitizeProviderSelection((string) ($providersData['fallback_provider_id'] ?? ''), $remainingIds),
            ];
            if ($meta['fallback_provider_id'] !== '' && $meta['fallback_provider_id'] === $meta['active_provider_id']) {
                $meta['fallback_provider_id'] = '';
            }

            if (!$this->settings->saveProviders($meta, $remainingEntries, [], [$providerId])) {
                return ['success' => false, 'error' => 'Provider-Eintrag konnte nicht gelöscht werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.providers.delete',
                'AI-Provider-Eintrag gelöscht.',
                'setting',
                null,
                [
                    'provider_id' => $providerId,
                ],
                'info'
            );

            return [
                'success' => true,
                'message' => 'Provider-Eintrag wurde gelöscht.',
                'redirect_section' => 'settings',
            ];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.providers.delete_failed', 'Provider-Eintrag konnte nicht gelöscht werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function saveFeatures(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Feature-Gates konnten nicht gespeichert werden.');
            }

            $values = [
                'ai_services_enabled' => !empty($post['ai_services_enabled']),
                'ai_translation_enabled' => !empty($post['ai_translation_enabled']),
                'ai_rewrite_enabled' => !empty($post['ai_rewrite_enabled']),
                'ai_summary_enabled' => !empty($post['ai_summary_enabled']),
                'ai_seo_meta_enabled' => !empty($post['ai_seo_meta_enabled']),
                'ai_editorjs_enabled' => !empty($post['ai_editorjs_enabled']),
            ];

            if (!$this->settings->saveFeatures($values)) {
                return ['success' => false, 'error' => 'Feature-Gates konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.features.save',
                'AI-Feature-Gates gespeichert.',
                'setting',
                null,
                $values,
                'info'
            );

            return ['success' => true, 'message' => 'Feature-Gates gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.features.save_failed', 'Feature-Gates konnten nicht gespeichert werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function saveTranslation(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Translation-Einstellungen konnten nicht gespeichert werden.');
            }

            $values = [
                'default_source_locale' => $this->sanitizeLocale((string) ($post['default_source_locale'] ?? 'de'), 'de'),
                'default_target_locale' => $this->sanitizeLocale((string) ($post['default_target_locale'] ?? 'en'), 'en'),
                'allowed_target_locales' => $this->sanitizeCsvList((string) ($post['allowed_target_locales'] ?? 'en'), ['en']),
                'supported_block_types' => $this->sanitizeSupportedBlockTypes((string) ($post['supported_block_types'] ?? 'paragraph,header,list,checklist,quote,callout,warning,mediaText'), ['paragraph', 'header', 'list', 'checklist', 'quote', 'callout', 'warning', 'mediaText']),
                'preview_required' => true,
                'preserve_unsupported_blocks' => !empty($post['preserve_unsupported_blocks']),
                'skip_html_blocks' => !empty($post['skip_html_blocks']),
                'result_mode' => $this->sanitizeResultMode((string) ($post['result_mode'] ?? 'localized-field')),
            ];

            if (!$this->settings->saveTranslation($values)) {
                return ['success' => false, 'error' => 'Translation-Einstellungen konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.translation.save',
                'AI-Translation-Einstellungen gespeichert.',
                'setting',
                null,
                [
                    'source_locale' => $values['default_source_locale'],
                    'target_locale' => $values['default_target_locale'],
                    'result_mode' => $values['result_mode'],
                    'target_locale_count' => count($values['allowed_target_locales']),
                    'block_type_count' => count($values['supported_block_types']),
                ],
                'info'
            );

            return ['success' => true, 'message' => 'Translation-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.translation.save_failed', 'Translation-Einstellungen konnten nicht gespeichert werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function saveTranslationPrompts(array $post): array
    {
        return $this->savePromptTemplatesForArea('translation', $post, 'Translation-Prompt-Vorlage gespeichert.');
    }

    /** @return array<string, mixed> */
    public function saveContentPrompts(array $post): array
    {
        return $this->savePromptTemplatesForArea('content_creator', $post, 'Content-Creator-Prompt-Vorlage gespeichert.');
    }

    /** @return array<string, mixed> */
    public function saveSeoPrompts(array $post): array
    {
        return $this->savePromptTemplatesForArea('seo_creator', $post, 'SEO-Creator-Prompt-Vorlage gespeichert.');
    }

    /** @return array<string, mixed> */
    private function savePromptTemplatesForArea(string $area, array $post, string $successMessage): array
    {
        try {
            $area = $this->sanitizePromptArea($area);
            if ($area === '') {
                return ['success' => false, 'error' => 'Unbekannter Prompt-Vorlagenbereich.'];
            }

            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Prompt-Vorlage konnte nicht gespeichert werden.');
            }

            $label = $this->sanitizeText((string) ($post['prompt_label'] ?? ''), self::MAX_TEXT_LENGTH);
            $systemPrompt = $this->sanitizePromptBody((string) ($post['system_prompt'] ?? ''), 4000);
            $userTemplate = $this->sanitizePromptBody((string) ($post['user_template'] ?? ''), 4000);
            $notes = $this->sanitizePromptBody((string) ($post['prompt_notes'] ?? ''), 1000);

            if ($systemPrompt === '' || $userTemplate === '') {
                return ['success' => false, 'error' => 'System-Prompt und User-Template dürfen nicht leer sein.'];
            }

            $values = [
                $area => [
                    'enabled' => !empty($post['prompt_enabled']),
                    'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $area)),
                    'system_prompt' => $systemPrompt,
                    'user_template' => $userTemplate,
                    'notes' => $notes,
                ],
            ];

            if (!$this->settings->savePromptTemplates($values)) {
                return ['success' => false, 'error' => 'Prompt-Vorlage konnte nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.prompts.save',
                'AI-Prompt-Vorlage gespeichert.',
                'setting',
                null,
                [
                    'area' => $area,
                    'enabled' => !empty($values[$area]['enabled']),
                    'system_prompt_length' => $this->measureTextLength($systemPrompt),
                    'user_template_length' => $this->measureTextLength($userTemplate),
                    'notes_length' => $this->measureTextLength($notes),
                ],
                'info'
            );

            return ['success' => true, 'message' => $successMessage];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.prompts.save_failed', 'Prompt-Vorlage konnte nicht gespeichert werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function saveLogging(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Logging-Einstellungen konnten nicht gespeichert werden.');
            }

            $values = [
                'logging_mode' => $this->sanitizeLoggingMode((string) ($post['logging_mode'] ?? 'technical')),
                'retention_days' => $this->sanitizeInt((int) ($post['retention_days'] ?? 30), 1, 3650),
                'store_content_hashes' => !empty($post['store_content_hashes']),
                'store_request_metrics' => !empty($post['store_request_metrics']),
                'store_error_context' => !empty($post['store_error_context']),
                'store_prompt_preview' => !empty($post['store_prompt_preview']),
            ];

            if (!$this->settings->saveLogging($values)) {
                return ['success' => false, 'error' => 'Logging-Einstellungen konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.logging.save',
                'AI-Logging-Einstellungen gespeichert.',
                'setting',
                null,
                [
                    'logging_mode' => $values['logging_mode'],
                    'retention_days' => $values['retention_days'],
                    'store_prompt_preview' => $values['store_prompt_preview'],
                ],
                'info'
            );

            return ['success' => true, 'message' => 'Logging-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.logging.save_failed', 'Logging-Einstellungen konnten nicht gespeichert werden.', $e);
        }
    }

    /** @return array<string, mixed> */
    public function saveQuotas(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Quota- und Limit-Einstellungen konnten nicht gespeichert werden.');
            }

            $values = [
                'max_chars_per_request' => $this->sanitizeInt((int) ($post['max_chars_per_request'] ?? 12000), 250, 250000),
                'max_blocks_per_request' => $this->sanitizeInt((int) ($post['max_blocks_per_request'] ?? 40), 1, 500),
                'timeout_seconds' => $this->sanitizeInt((int) ($post['timeout_seconds'] ?? 25), 5, 300),
                'retry_count' => $this->sanitizeInt((int) ($post['retry_count'] ?? 1), 0, 10),
                'daily_requests_per_user' => $this->sanitizeInt((int) ($post['daily_requests_per_user'] ?? 40), 1, 5000),
                'daily_chars_per_user' => $this->sanitizeInt((int) ($post['daily_chars_per_user'] ?? 120000), 500, 2000000),
                'monthly_requests_per_provider' => $this->sanitizeInt((int) ($post['monthly_requests_per_provider'] ?? 5000), 10, 1000000),
            ];

            if (!$this->settings->saveQuotas($values)) {
                return ['success' => false, 'error' => 'Quota- und Limit-Einstellungen konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.quotas.save',
                'AI-Quota- und Limit-Einstellungen gespeichert.',
                'setting',
                null,
                $values,
                'info'
            );

            return ['success' => true, 'message' => 'Quota- und Limit-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.quotas.save_failed', 'Quota- und Limit-Einstellungen konnten nicht gespeichert werden.', $e);
        }
    }

    private function sanitizeProviderType(string $value, bool $addableOnly = false): string
    {
        $value = strtolower(trim($value));

        if ($value === '' || !AiSettingsService::isKnownProviderType($value)) {
            return '';
        }

        if ($addableOnly && !AiSettingsService::isAddableProviderType($value)) {
            return '';
        }

        return $value;
    }

    private function sanitizeProviderId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    /** @param list<string> $validProviderIds */
    private function sanitizeProviderSelection(string $value, array $validProviderIds): string
    {
        $value = $this->sanitizeProviderId($value);

        return in_array($value, $validProviderIds, true) ? $value : '';
    }

    /** @param list<string> $validProviderIds */
    private function resolvePrimaryProviderSelection(string $value, array $validProviderIds): string
    {
        $selection = $this->sanitizeProviderSelection($value, $validProviderIds);
        if ($selection !== '' || $validProviderIds === []) {
            return $selection;
        }

        return (string) ($validProviderIds[0] ?? '');
    }

    private function sanitizeProfile(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['disabled', 'beta', 'editor-translation', 'content-assist', 'seo-assist'];

        return in_array($value, $allowed, true) ? $value : 'disabled';
    }

    private function sanitizeResultMode(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['preview', 'localized-field', 'overwrite-current-draft'];

        return in_array($value, $allowed, true) ? $value : 'localized-field';
    }

    private function sanitizeLoggingMode(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['minimal', 'technical', 'debug-no-content'];

        return in_array($value, $allowed, true) ? $value : 'technical';
    }

    private function sanitizePromptArea(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = ['translation', 'content_creator', 'seo_creator'];

        return in_array($value, $allowed, true) ? $value : '';
    }

    /** @return list<string> */
    private function sanitizeCsvList(string $value, array $fallback): array
    {
        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $part = strtolower(trim($part));
            $part = preg_replace('/[^a-z0-9._-]+/', '', $part) ?? '';
            if ($part === '') {
                continue;
            }

            $normalized[$part] = $part;
        }

        return $normalized !== [] ? array_values($normalized) : array_values(array_unique($fallback));
    }

    /** @return list<string> */
    private function sanitizeSupportedBlockTypes(string $value, array $fallback): array
    {
        $parts = preg_split('/\s*,\s*/', trim($value)) ?: [];
        $normalized = [];

        foreach ($parts as $part) {
            $part = strtolower(trim($part));
            $part = preg_replace('/[^a-z0-9._-]+/', '', $part) ?? '';
            if ($part === '' || !isset(self::SUPPORTED_EDITORJS_BLOCK_TYPES[$part])) {
                continue;
            }

            $canonical = self::SUPPORTED_EDITORJS_BLOCK_TYPES[$part];
            $normalized[$canonical] = $canonical;
        }

        if ($normalized !== []) {
            return array_values($normalized);
        }

        $resolvedFallback = [];
        foreach ($fallback as $entry) {
            $key = strtolower(trim((string) $entry));
            if ($key === '' || !isset(self::SUPPORTED_EDITORJS_BLOCK_TYPES[$key])) {
                continue;
            }

            $canonical = self::SUPPORTED_EDITORJS_BLOCK_TYPES[$key];
            $resolvedFallback[$canonical] = $canonical;
        }

        return $resolvedFallback !== [] ? array_values($resolvedFallback) : array_values(self::SUPPORTED_EDITORJS_BLOCK_TYPES);
    }

    private function sanitizeLocale(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return $value !== '' ? $value : $fallback;
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function sanitizePromptBody(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function measureTextLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function sanitizeUrl(string $value, bool $allowEmpty = false): string
    {
        $value = trim($value);
        if ($value === '') {
            return $allowEmpty ? '' : '';
        }

        $value = filter_var($value, FILTER_SANITIZE_URL) ?: '';
        $value = function_exists('mb_substr') ? mb_substr($value, 0, self::MAX_URL_LENGTH) : substr($value, 0, self::MAX_URL_LENGTH);
        if ($value === '' || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($value);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $value;
    }

    private function sanitizeProviderEndpoint(string $providerType, string $value, string $fallback = ''): string
    {
        if ($providerType === 'mock') {
            return 'internal://mock';
        }

        $sanitized = $this->sanitizeUrl($value, true);
        if ($sanitized !== '') {
            return $sanitized;
        }

        return $fallback !== '' ? $this->sanitizeUrl($fallback, true) : '';
    }

    private function sanitizeApiVersion(string $value, string $fallback = ''): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '', $value) ?? '';

        if ($value !== '') {
            return $value;
        }

        $fallback = strtolower(trim($fallback));
        $fallback = preg_replace('/[^a-z0-9._-]+/', '', $fallback) ?? '';

        return $fallback;
    }

    private function sanitizeInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $current
     * @return list<array<string, mixed>>
     */
    private function sanitizeProviderEntries(array $post, array $current): array
    {
        $rawEntries = array_values(array_filter(
            (array) ($post['provider_entries'] ?? []),
            static fn (mixed $entry): bool => is_array($entry)
        ));
        $currentEntries = [];
        foreach ((array) ($current['providers']['entries'] ?? []) as $entry) {
            if (is_array($entry) && !empty($entry['id'])) {
                $currentEntries[(string) $entry['id']] = $entry;
            }
        }

        $entries = [];
        $knownIds = [];

        foreach ($rawEntries as $rawEntry) {
            $providerId = $this->sanitizeProviderId((string) ($rawEntry['id'] ?? ''));
            $providerType = $this->sanitizeProviderType((string) ($rawEntry['type'] ?? ''), false);
            if ($providerId === '' || $providerType === '' || !empty($rawEntry['remove']) || isset($knownIds[$providerId])) {
                continue;
            }

            $currentEntry = is_array($currentEntries[$providerId] ?? null) ? $currentEntries[$providerId] : [];
            $defaultEntry = $this->settings->buildProviderEntry($providerType, $providerId);

            $entries[] = [
                'id' => $providerId,
                'type' => $providerType,
                'label' => $this->sanitizeText((string) ($rawEntry['label'] ?? ($currentEntry['label'] ?? $defaultEntry['label'] ?? '')), self::MAX_TEXT_LENGTH) ?: (string) ($defaultEntry['label'] ?? $providerType),
                'enabled' => !empty($rawEntry['enabled']),
                'profile' => $this->sanitizeProfile((string) ($rawEntry['profile'] ?? ($currentEntry['profile'] ?? $defaultEntry['profile'] ?? 'disabled'))),
                'default_model' => $this->sanitizeText((string) ($rawEntry['default_model'] ?? ($currentEntry['default_model'] ?? $defaultEntry['default_model'] ?? '')), self::MAX_TEXT_LENGTH),
                'endpoint' => $this->sanitizeProviderEndpoint($providerType, (string) ($rawEntry['endpoint'] ?? ($currentEntry['endpoint'] ?? $defaultEntry['endpoint'] ?? '')), (string) ($defaultEntry['endpoint'] ?? '')),
                'deployment' => $this->sanitizeText((string) ($rawEntry['deployment'] ?? ($currentEntry['deployment'] ?? $defaultEntry['deployment'] ?? '')), self::MAX_TEXT_LENGTH),
                'api_version' => $this->sanitizeApiVersion((string) ($rawEntry['api_version'] ?? ($currentEntry['api_version'] ?? $defaultEntry['api_version'] ?? '')), (string) ($defaultEntry['api_version'] ?? '')),
                'translation_enabled' => !empty($rawEntry['translation_enabled']),
                'rewrite_enabled' => !empty($rawEntry['rewrite_enabled']),
                'summary_enabled' => !empty($rawEntry['summary_enabled']),
                'seo_meta_enabled' => !empty($rawEntry['seo_meta_enabled']),
                'editorjs_enabled' => !empty($rawEntry['editorjs_enabled']),
                'allowed_locales' => $this->sanitizeCsvList((string) ($rawEntry['allowed_locales'] ?? implode(',', (array) ($currentEntry['allowed_locales'] ?? $defaultEntry['allowed_locales'] ?? ['en']))), ['en']),
                'beta_only' => !empty($rawEntry['beta_only']),
            ];
            $knownIds[$providerId] = true;

            if (count($entries) >= self::MAX_PROVIDER_ENTRIES) {
                break;
            }
        }

        return $entries;
    }

    /** @return array<string, mixed> */
    private function failResult(string $action, string $message, \Throwable $e): array
    {
        Logger::instance()->withChannel('admin.ai-services')->error($message, [
            'action' => $action,
            'exception' => $e::class,
            'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
        ]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            $action,
            $message,
            'setting',
            null,
            ['exception' => $e::class],
            'error'
        );

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    /** @return array<string, mixed> */
    private function runtimeUnavailableResult(string $message): array
    {
        return [
            'success' => false,
            'error' => $message . ' ' . ($this->initializationError !== '' ? $this->initializationError : 'AI-Services sind aktuell nicht initialisiert.'),
        ];
    }
}