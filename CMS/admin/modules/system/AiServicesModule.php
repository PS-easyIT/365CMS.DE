<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Database;
use CMS\Logger;
use CMS\Services\AI\AiService;
use CMS\Services\AI\AiSettingsService;
use CMS\Services\AI\AiQuotaService;

final class AiServicesModule
{
    private const int MAX_TEXT_LENGTH = 120;
    private const int MAX_URL_LENGTH = 255;
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
        'image' => 'image',
        'code' => 'code',
        'table' => 'table',
        'delimiter' => 'delimiter',
        'spacer' => 'spacer',
        'space' => 'spacer',
        'embed' => 'embed',
        'attaches' => 'attaches',
        'linktool' => 'linkTool',
        'linkTool' => 'linkTool',
        'raw' => 'raw',
        'callout' => 'callout',
        'warning' => 'warning',
        'alert' => 'alert',
        'accordion' => 'accordion',
        'imagegallery' => 'imageGallery',
        'imageGallery' => 'imageGallery',
        'mediatext' => 'mediaText',
        'mediaText' => 'mediaText',
    ];

    private ?AiSettingsService $settings = null;
    private ?AiQuotaService $quotaService = null;
    private ?Database $db = null;
    private string $dbPrefix = '';
    private string $initializationError = '';

    public function __construct()
    {
        try {
            $this->settings = AiSettingsService::getInstance();
            $this->db = Database::instance();
            $this->dbPrefix = $this->db->getPrefix();
            $this->quotaService = new AiQuotaService($this->db);
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
            $this->attachQuotaMonitoring($configuration);
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

    /** @param array<string, mixed> $configuration */
    private function attachQuotaMonitoring(array &$configuration): void
    {
        if ($this->quotaService === null) {
            return;
        }

        $quotas = is_array($configuration['quotas'] ?? null) ? $configuration['quotas'] : [];
        $activeProviderId = $this->sanitizeProviderId((string) ($configuration['providers']['active_provider_id'] ?? ''));
        $usage = $this->quotaService->getCurrentUsage($this->getCurrentUserId(), $activeProviderId, $quotas);
        $monitoring = is_array($configuration['monitoring'] ?? null) ? $configuration['monitoring'] : [];
        $monitoring['current_user'] = array_merge(is_array($monitoring['current_user'] ?? null) ? $monitoring['current_user'] : [], [
            'requests_24h' => $usage['requests_24h'],
            'request_limit' => $usage['request_limit'],
            'request_usage_percent' => $this->calculateUsagePercent($usage['requests_24h'], $usage['request_limit']),
            'chars_24h' => $usage['chars_24h'],
            'char_limit' => $usage['char_limit'],
            'char_usage_percent' => $this->calculateUsagePercent($usage['chars_24h'], $usage['char_limit']),
            'char_metrics_available' => true,
        ]);
        $monitoring['active_provider'] = array_merge(is_array($monitoring['active_provider'] ?? null) ? $monitoring['active_provider'] : [], [
            'requests_30d' => $usage['requests_30d'],
            'request_limit' => $usage['provider_request_limit'],
            'usage_percent' => $this->calculateUsagePercent($usage['requests_30d'], $usage['provider_request_limit']),
            'quota_period_label' => 'aktueller UTC-Monat',
        ]);
        $configuration['monitoring'] = $monitoring;
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
                 WHERE action IN (?, ?, ?, ?, ?, ?) AND created_at >= ?
             ORDER BY created_at DESC
             LIMIT ?",
            [
                'ai.editorjs.translate.processed',
                'ai.editorjs.translate.failed',
                     'ai.editorjs.seo_metadata.processed',
                     'ai.editorjs.seo_metadata.failed',
                'ai.content.creator.processed',
                'ai.content.creator.failed',
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
        $action = (string) ($row->action ?? '');
        $status = str_ends_with($action, '.processed') ? 'success' : 'warning';
        $targetLocale = strtolower(trim((string) ($metadata['target_locale'] ?? $metadata['locale'] ?? '')));
        $targetLocale = $targetLocale !== '' ? strtoupper($targetLocale) : '—';

        return [
            'created_at' => trim((string) ($row->created_at ?? '')),
            'status' => $status,
            'status_label' => $status === 'success' ? 'erfolgreich' : 'fehlgeschlagen',
            'user_id' => $userId,
            'user_label' => $userId <= 0 ? 'System' : ($userId === $currentUserId ? 'Du' : 'User #' . $userId),
            'provider_id' => $providerId,
            'provider_label' => $providerLabel,
            'operation' => str_contains($action, '.seo_metadata.')
                ? 'SEO-Metadaten'
                : (str_contains($action, '.content.creator.') ? 'Content Creator' : 'Editor.js-Übersetzung'),
            'target_locale' => $targetLocale,
            'duration_ms' => $this->normalizePositiveNullable($metadata['duration_ms'] ?? null),
            'translated_blocks' => $this->normalizePositiveNullable($metadata['translated_blocks'] ?? null),
            'translated_segments' => $this->normalizePositiveNullable($metadata['translated_segments'] ?? null),
            'char_count' => $this->normalizePositiveNullable($metadata['char_count'] ?? $metadata['source_char_count'] ?? $metadata['input_char_count'] ?? null),
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
            $providerEntry = $this->sanitizeGlobalProviderEntry($post, $current);
            $providerId = (string) ($providerEntry['id'] ?? '');
            $providerEntries = [];
            $replaced = false;
            foreach ((array) ($current['providers']['entries'] ?? []) as $currentEntry) {
                if (!is_array($currentEntry)) {
                    continue;
                }

                if ((string) ($currentEntry['id'] ?? '') === $providerId) {
                    $providerEntries[] = $providerEntry;
                    $replaced = true;
                    continue;
                }

                $providerEntries[] = $currentEntry;
            }

            if (!$replaced) {
                $providerEntries[] = $providerEntry;
            }

            $meta = [
                'active_provider_id' => $providerId,
                'fallback_provider_id' => $this->sanitizeProviderId((string) ($post['fallback_provider_id'] ?? '')),
            ];

            $secretValues = [];
            $secretValue = trim((string) ($post['provider_secret'] ?? ''));
            if ($providerId !== '' && $secretValue !== '') {
                $secretValues[$providerId] = $secretValue;
            }

            $clearSecrets = [];
            if (!empty($post['clear_provider_secret']) && $providerId !== '') {
                $clearSecrets[] = $providerId;
            }

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
                    'provider_type' => (string) ($providerEntry['type'] ?? ''),
                    'provider_enabled' => !empty($providerEntry['enabled']),
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
    public function deleteProvider(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('AI-Provider konnte nicht gelöscht werden.');
            }

            $providerId = $this->sanitizeProviderId((string) ($post['provider_id'] ?? ''));
            if ($providerId === '' || $providerId === 'mock') {
                return ['success' => false, 'error' => 'Der integrierte Mock-Provider kann nicht gelöscht werden.'];
            }

            $current = $this->settings->getConfiguration();
            $remainingEntries = [];
            $found = false;
            $deletedProviderType = '';
            foreach ((array) ($current['providers']['entries'] ?? []) as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                if ($this->sanitizeProviderId((string) ($entry['id'] ?? '')) === $providerId) {
                    $found = true;
                    $deletedProviderType = (string) ($entry['type'] ?? '');
                    continue;
                }

                $remainingEntries[] = $entry;
            }

            if (!$found) {
                return ['success' => false, 'error' => 'Der AI-Provider wurde nicht gefunden.'];
            }

            $activeProviderId = $this->sanitizeProviderId((string) ($current['providers']['active_provider_id'] ?? ''));
            $fallbackProviderId = $this->sanitizeProviderId((string) ($current['providers']['fallback_provider_id'] ?? ''));
            $meta = [
                'active_provider_id' => $activeProviderId === $providerId ? '' : $activeProviderId,
                'fallback_provider_id' => $fallbackProviderId === $providerId ? '' : $fallbackProviderId,
            ];
            if (!$this->settings->saveProviders($meta, $remainingEntries, [], [$providerId])) {
                return ['success' => false, 'error' => 'AI-Provider konnte nicht gelöscht werden.'];
            }
            if (!$this->settings->deleteProviderSecrets($providerId, $deletedProviderType)) {
                return ['success' => false, 'error' => 'AI-Provider wurde entfernt, aber sein Secret konnte nicht vollständig gelöscht werden. Bitte die AI-Einstellungen prüfen.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.provider.delete',
                'AI-Provider und zugehöriges Secret wurden gelöscht.',
                'ai_provider',
                null,
                ['provider_id' => $providerId],
                'warning'
            );

            return ['success' => true, 'message' => 'AI-Provider und zugehöriges Secret wurden gelöscht.'];
        } catch (\Throwable $e) {
            return $this->failResult('setting.ai.provider.delete_failed', 'AI-Provider konnte nicht gelöscht werden.', $e);
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
                'ai_beta_providers_enabled' => !empty($post['ai_beta_providers_enabled']),
                'ai_external_provider_data_sharing_enabled' => !empty($post['ai_external_provider_data_sharing_enabled']),
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
                'supported_block_types' => $this->sanitizeSupportedBlockTypes((string) ($post['supported_block_types'] ?? 'paragraph,header,list,checklist,quote,image,table,attaches,linkTool,callout,warning,alert,accordion,imageGallery,mediaText'), ['paragraph', 'header', 'list', 'checklist', 'quote', 'image', 'table', 'attaches', 'linkTool', 'callout', 'warning', 'alert', 'accordion', 'imageGallery', 'mediaText']),
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
    public function generateContentDraft(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Content-Entwurf konnte nicht erzeugt werden.');
            }

            $userId = (int) (Auth::instance()->getCurrentUser()->id ?? 0);
            $result = AiService::getInstance()->generateContentDraft([
                'user_id' => $userId,
                'task' => (string) ($post['content_task'] ?? ''),
                'brief' => (string) ($post['content_brief'] ?? ''),
                'context' => (string) ($post['content_context'] ?? ''),
                'tone' => (string) ($post['content_tone'] ?? ''),
                'locale' => (string) ($post['content_locale'] ?? 'de'),
            ]);
            $draft = is_array($result['draft'] ?? null) ? $result['draft'] : [];
            $telemetry = is_array($result['telemetry'] ?? null) ? $result['telemetry'] : [];

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.content.creator.processed',
                'Content-Entwurf wurde im geschützten AI-Adminbereich erzeugt.',
                'content_draft',
                null,
                array_filter([
                    'user_id' => $userId,
                    'provider' => (string) ($result['provider']['slug'] ?? ''),
                    'task' => (string) ($draft['task'] ?? ''),
                    'locale' => (string) ($telemetry['locale'] ?? ''),
                    'input_char_count' => isset($telemetry['input_char_count']) ? (int) $telemetry['input_char_count'] : null,
                    'source_truncated' => !empty($telemetry['source_truncated']),
                    'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
                    'source_hash' => (string) ($telemetry['source_hash'] ?? ''),
                    'resolved_via' => (string) ($result['provider']['resolved_via'] ?? 'direct'),
                ], static fn (mixed $value): bool => $value !== '' && $value !== null),
                'info'
            );

            return [
                'success' => true,
                'render_inline' => true,
                'message' => 'Content-Entwurf wurde erzeugt. Er bleibt ein ungespeicherter Vorschlag und wird nicht veröffentlicht.',
                'report_payload' => [
                    'content_draft' => [
                        'task' => (string) ($draft['task'] ?? ''),
                        'content' => (string) ($draft['content'] ?? ''),
                        'provider' => is_array($result['provider'] ?? null) ? $result['provider'] : [],
                        'stats' => is_array($result['stats'] ?? null) ? $result['stats'] : [],
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-content-creator')->error('Content-Entwurf konnte nicht erzeugt werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.content.creator.failed',
                'Content-Entwurf konnte nicht erzeugt werden.',
                'content_draft',
                null,
                ['exception' => $e::class],
                'warning'
            );

            return ['success' => false, 'error' => 'Content-Entwurf konnte nicht erzeugt werden. Bitte Einstellungen und Logs prüfen.'];
        }
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
                'store_prompt_preview' => false,
            ];

            if (!$this->settings->saveLogging($values)) {
                return ['success' => false, 'error' => 'Logging-Einstellungen konnten nicht gespeichert werden.'];
            }

            $this->pruneAiOperationalData($values['retention_days']);

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
                'retry_count' => $this->sanitizeInt((int) ($post['retry_count'] ?? 1), 0, 2),
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

    private function pruneAiOperationalData(int $retentionDays): void
    {
        if ($this->db === null || $this->dbPrefix === '') {
            return;
        }

        $retentionDays = max(31, min(3650, $retentionDays));
        $cutoff = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('-' . $retentionDays . ' days')
            ->format('Y-m-d H:i:s');
        $actions = [
            'ai.editorjs.translate.processed',
            'ai.editorjs.translate.failed',
            'ai.editorjs.seo_metadata.processed',
            'ai.editorjs.seo_metadata.failed',
            'ai.content.creator.processed',
            'ai.content.creator.failed',
            'ai.provider.healthcheck.processed',
            'ai.provider.healthcheck.failed',
        ];
        $placeholders = implode(', ', array_fill(0, count($actions), '?'));

        try {
            $this->db->execute(
                "DELETE FROM {$this->dbPrefix}audit_log WHERE action IN ({$placeholders}) AND created_at < ?",
                [...$actions, $cutoff]
            );
            (new \CMS\Services\AI\AiQuotaService($this->db))->prune($retentionDays);
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-services')->warning('AI-Aufbewahrung konnte nicht vollständig bereinigt werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);
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

        return $resolvedFallback !== []
            ? array_values($resolvedFallback)
            : array_values(array_unique(self::SUPPORTED_EDITORJS_BLOCK_TYPES));
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
        if (in_array($providerType, ['openai', 'mistral', 'openrouter', 'azure_openai'], true)
            && $sanitized !== ''
            && strtolower((string) parse_url($sanitized, PHP_URL_SCHEME)) !== 'https'
        ) {
            return '';
        }
        if ($sanitized !== '') {
            return $sanitized;
        }

        $fallback = $fallback !== '' ? $this->sanitizeUrl($fallback, true) : '';
        if (in_array($providerType, ['openai', 'mistral', 'openrouter', 'azure_openai'], true)
            && $fallback !== ''
            && strtolower((string) parse_url($fallback, PHP_URL_SCHEME)) !== 'https'
        ) {
            return '';
        }

        return $fallback;
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
     * @return array<string, mixed>
     */
    private function sanitizeGlobalProviderEntry(array $post, array $current): array
    {
        $providerType = $this->sanitizeProviderType((string) ($post['active_provider_type'] ?? ''), false);
        if ($providerType === '') {
            $providerType = 'mock';
        }

        $providerId = $providerType;
        $currentEntry = [];
        foreach ((array) ($current['providers']['entries'] ?? []) as $entry) {
            if (is_array($entry) && (string) ($entry['type'] ?? '') === $providerType) {
                $currentEntry = $entry;
                break;
            }
        }

        $defaultEntry = $this->settings->buildProviderEntry($providerType, $providerId);
        $enabled = !empty($post['provider_enabled']);
        if ($providerType === 'mock') {
            $enabled = true;
        }

        return [
            'id' => $providerId,
            'type' => $providerType,
            'label' => $this->sanitizeText((string) ($post['provider_label'] ?? ($currentEntry['label'] ?? $defaultEntry['label'] ?? '')), self::MAX_TEXT_LENGTH) ?: (string) ($defaultEntry['label'] ?? $providerType),
            'enabled' => $enabled,
            'profile' => $this->sanitizeProviderProfile((string) ($post['provider_profile'] ?? ($currentEntry['profile'] ?? $defaultEntry['profile'] ?? 'editor-translation'))),
            'default_model' => $this->sanitizeText((string) ($post['provider_model'] ?? ($currentEntry['default_model'] ?? $defaultEntry['default_model'] ?? '')), self::MAX_TEXT_LENGTH),
            'endpoint' => $this->sanitizeProviderEndpoint($providerType, (string) ($post['provider_endpoint'] ?? ($currentEntry['endpoint'] ?? $defaultEntry['endpoint'] ?? '')), (string) ($defaultEntry['endpoint'] ?? '')),
            'deployment' => $this->sanitizeText((string) ($post['provider_deployment'] ?? ($currentEntry['deployment'] ?? $defaultEntry['deployment'] ?? '')), self::MAX_TEXT_LENGTH),
            'api_version' => $this->sanitizeApiVersion((string) ($post['provider_api_version'] ?? ($currentEntry['api_version'] ?? $defaultEntry['api_version'] ?? '')), (string) ($defaultEntry['api_version'] ?? '')),
            'translation_enabled' => !empty($post['provider_translation_enabled']),
            'rewrite_enabled' => !empty($post['provider_rewrite_enabled']),
            'summary_enabled' => !empty($post['provider_summary_enabled']),
            'seo_meta_enabled' => !empty($post['provider_seo_meta_enabled']),
            'editorjs_enabled' => !empty($post['provider_editorjs_enabled']),
            'allowed_locales' => $this->sanitizeCsvList((string) ($post['provider_allowed_locales'] ?? implode(',', (array) ($currentEntry['allowed_locales'] ?? $defaultEntry['allowed_locales'] ?? ['en']))), ['en']),
            'allowed_internal_hosts' => $this->sanitizeCsvList((string) ($post['provider_allowed_internal_hosts'] ?? implode(',', (array) ($currentEntry['allowed_internal_hosts'] ?? $defaultEntry['allowed_internal_hosts'] ?? []))), (array) ($defaultEntry['allowed_internal_hosts'] ?? [])),
            'beta_only' => !empty($post['provider_beta_only']),
        ];
    }

    private function sanitizeProviderProfile(string $value): string
    {
        $value = strtolower(trim($value));
        $allowed = array_keys(AiSettingsService::getProviderProfiles());

        return in_array($value, $allowed, true) ? $value : 'editor-translation';
    }

    /** @return array<string, mixed> */
    public function checkProviderHealth(array $post): array
    {
        try {
            if ($this->settings === null) {
                return $this->runtimeUnavailableResult('Provider-Healthcheck konnte nicht ausgeführt werden.');
            }

            $providerId = $this->sanitizeProviderId((string) ($post['provider_id'] ?? ''));
            $result = AiService::getInstance()->checkProviderHealth($providerId);
            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'ai.provider.healthcheck.processed',
                'AI-Provider-Healthcheck im Admin ausgeführt.',
                'ai_provider',
                null,
                [
                    'provider' => (string) ($result['provider']['slug'] ?? ''),
                    'status' => (string) ($result['status'] ?? 'unknown'),
                    'attempts' => (int) ($result['attempts'] ?? 0),
                    'duration_ms' => (int) ($result['duration_ms'] ?? 0),
                ],
                'info'
            );

            return [
                'success' => true,
                'render_inline' => true,
                'message' => 'Provider-Healthcheck erfolgreich.',
                'report_payload' => ['provider_health' => $result],
            ];
        } catch (\Throwable $e) {
            return $this->failResult('ai.provider.healthcheck.failed', 'Provider-Healthcheck ist fehlgeschlagen.', $e);
        }
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