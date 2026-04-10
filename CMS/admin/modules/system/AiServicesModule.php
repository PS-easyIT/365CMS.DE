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
                'providers' => ['active_provider' => 'openai', 'fallback_provider' => 'azure_openai', 'providers' => []],
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
            $providerState = is_array($current['providers']['providers'] ?? null) ? $current['providers']['providers'] : [];

            $meta = [
                'active_provider' => $this->sanitizeProviderSlug((string) ($post['active_provider'] ?? 'openai'), 'openai'),
                'fallback_provider' => $this->sanitizeProviderSlug((string) ($post['fallback_provider'] ?? 'azure_openai'), 'azure_openai'),
            ];

            $providers = [];
            foreach (AiSettingsService::PROVIDER_SLUGS as $providerSlug) {
                $currentProvider = is_array($providerState[$providerSlug] ?? null) ? $providerState[$providerSlug] : [];
                $providers[$providerSlug] = [
                    'enabled' => !empty($post[$providerSlug . '_enabled']),
                    'profile' => $this->sanitizeProfile((string) ($post[$providerSlug . '_profile'] ?? ($currentProvider['profile'] ?? 'disabled'))),
                    'default_model' => $this->sanitizeText((string) ($post[$providerSlug . '_default_model'] ?? ($currentProvider['default_model'] ?? '')), self::MAX_TEXT_LENGTH),
                    'endpoint' => $this->sanitizeUrl((string) ($post[$providerSlug . '_endpoint'] ?? ($currentProvider['endpoint'] ?? '')), true),
                    'translation_enabled' => !empty($post[$providerSlug . '_translation_enabled']),
                    'rewrite_enabled' => !empty($post[$providerSlug . '_rewrite_enabled']),
                    'summary_enabled' => !empty($post[$providerSlug . '_summary_enabled']),
                    'seo_meta_enabled' => !empty($post[$providerSlug . '_seo_meta_enabled']),
                    'editorjs_enabled' => !empty($post[$providerSlug . '_editorjs_enabled']),
                    'allowed_locales' => $this->sanitizeCsvList((string) ($post[$providerSlug . '_allowed_locales'] ?? 'en'), ['en']),
                    'beta_only' => !empty($post[$providerSlug . '_beta_only']),
                ];
            }

            $secretValues = [];
            if (trim((string) ($post['openai_api_key'] ?? '')) !== '') {
                $secretValues['openai_api_key'] = trim((string) $post['openai_api_key']);
            }
            if (trim((string) ($post['azure_openai_api_key'] ?? '')) !== '') {
                $secretValues['azure_openai_api_key'] = trim((string) $post['azure_openai_api_key']);
            }
            if (trim((string) ($post['openrouter_api_key'] ?? '')) !== '') {
                $secretValues['openrouter_api_key'] = trim((string) $post['openrouter_api_key']);
            }

            $clearSecrets = [];
            foreach (['openai_api_key', 'azure_openai_api_key', 'openrouter_api_key'] as $secretKey) {
                if (!empty($post['clear_' . $secretKey])) {
                    $clearSecrets[] = $secretKey;
                }
            }

            if (!$this->settings->saveProviders($meta, $providers, $secretValues, $clearSecrets)) {
                return ['success' => false, 'error' => 'Provider-Einstellungen konnten nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'setting.ai.providers.save',
                'AI-Provider-Einstellungen gespeichert.',
                'setting',
                null,
                [
                    'active_provider' => $meta['active_provider'],
                    'fallback_provider' => $meta['fallback_provider'],
                    'providers_enabled' => count(array_filter($providers, static fn (array $provider): bool => !empty($provider['enabled']))),
                    'secrets_updated' => array_keys($secretValues),
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

    private function sanitizeProviderSlug(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));

        return in_array($value, AiSettingsService::PROVIDER_SLUGS, true) ? $value : $fallback;
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

    private function sanitizeInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
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