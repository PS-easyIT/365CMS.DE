<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\AI\AiSettingsService;

final class AiServicesModule
{
    private const int MAX_TEXT_LENGTH = 120;
    private const int MAX_URL_LENGTH = 255;
    private const int MAX_PROVIDER_ENTRIES = 20;
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

    private AiSettingsService $settings;

    public function __construct()
    {
        $this->settings = AiSettingsService::getInstance();
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        try {
            return $this->settings->getConfiguration();
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-services')->error('AI-Settings konnten nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), self::MAX_TEXT_LENGTH),
            ]);

            return [
                'providers' => ['active_provider_id' => 'mock', 'fallback_provider_id' => '', 'entries' => [], 'catalog' => []],
                'features' => [],
                'translation' => [],
                'logging' => [],
                'quotas' => [],
                'summary' => [],
                'error' => 'AI-Services-Konfiguration konnte gerade nicht geladen werden. Bitte Logs prüfen.',
            ];
        }
    }

    /** @return array<string, mixed> */
    public function saveProviders(array $post): array
    {
        try {
            $current = $this->settings->getConfiguration();
            $providerEntries = $this->sanitizeProviderEntries($post, $current);
            $providerIds = array_values(array_map(
                static fn (array $entry): string => (string) ($entry['id'] ?? ''),
                $providerEntries
            ));

            $meta = [
                'active_provider_id' => $this->sanitizeProviderSelection((string) ($post['active_provider_id'] ?? ''), $providerIds),
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
                'active_provider_id' => $this->sanitizeProviderSelection((string) ($providersData['active_provider_id'] ?? ''), $remainingIds),
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
            $values = [
                'default_source_locale' => $this->sanitizeLocale((string) ($post['default_source_locale'] ?? 'de'), 'de'),
                'default_target_locale' => $this->sanitizeLocale((string) ($post['default_target_locale'] ?? 'en'), 'en'),
                'allowed_target_locales' => $this->sanitizeCsvList((string) ($post['allowed_target_locales'] ?? 'en'), ['en']),
                'supported_block_types' => $this->sanitizeSupportedBlockTypes((string) ($post['supported_block_types'] ?? 'paragraph,header,list,checklist,quote,callout,warning,mediaText'), ['paragraph', 'header', 'list', 'checklist', 'quote', 'callout', 'warning', 'mediaText']),
                'preview_required' => !empty($post['preview_required']),
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
    public function saveLogging(array $post): array
    {
        try {
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
            if ($providerId === '' || $providerType === '' || isset($knownIds[$providerId])) {
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
}