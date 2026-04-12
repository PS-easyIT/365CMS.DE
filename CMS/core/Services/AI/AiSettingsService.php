<?php
declare(strict_types=1);

namespace CMS\Services\AI;

use CMS\Services\SettingsService;

if (!defined('ABSPATH')) {
    exit;
}

final class AiSettingsService
{
    public const GROUP_PROVIDERS = 'ai.providers';
    public const GROUP_FEATURES = 'ai.features';
    public const GROUP_TRANSLATION = 'ai.translation';
    public const GROUP_LOGGING = 'ai.logging';
    public const GROUP_QUOTAS = 'ai.quotas';

    /** @var list<string> */
    public const PROVIDER_SLUGS = ['mock', 'openai', 'azure_openai', 'ollama', 'openrouter'];

    private const PROVIDER_SECRET_PREFIX = 'provider_secret_';

    /** @var array<string, array<string, mixed>> */
    private const PROVIDER_TYPE_DEFINITIONS = [
        'mock' => [
            'label' => 'Mock Provider',
            'description' => 'Lokaler Testprovider ohne externen Live-Call.',
            'requires_secret' => false,
            'secret_label' => '',
            'live_supported' => true,
            'addable' => true,
        ],
        'ollama' => [
            'label' => 'Ollama',
            'description' => 'Lokaler oder interner Ollama-Endpunkt für echte Live-Übersetzungen.',
            'requires_secret' => false,
            'secret_label' => '',
            'live_supported' => true,
            'addable' => true,
        ],
        'azure_openai' => [
            'label' => 'Azure AI',
            'description' => 'Azure OpenAI / Azure AI Inference über Resource-Endpoint, Deployment und API-Key.',
            'requires_secret' => true,
            'secret_label' => 'API-Key',
            'live_supported' => true,
            'addable' => true,
        ],
        'openai' => [
            'label' => 'OpenAI',
            'description' => 'Vorbereitet für spätere Bridge-Anbindung; aktuell noch kein Live-Adapter im Gateway.',
            'requires_secret' => true,
            'secret_label' => 'API-Key',
            'live_supported' => false,
            'addable' => false,
        ],
        'openrouter' => [
            'label' => 'OpenRouter',
            'description' => 'Vorbereitet für spätere Bridge-Anbindung; aktuell noch kein Live-Adapter im Gateway.',
            'requires_secret' => true,
            'secret_label' => 'API-Key',
            'live_supported' => false,
            'addable' => false,
        ],
    ];

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

    private const PROVIDER_SECRET_KEYS = [
        'openai_api_key',
        'azure_openai_api_key',
        'openrouter_api_key',
    ];

    private static ?self $instance = null;

    private SettingsService $settings;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = SettingsService::getInstance();
    }

    /** @return array<string, array<string, mixed>> */
    public static function getProviderTypeDefinitions(bool $addableOnly = false): array
    {
        if (!$addableOnly) {
            return self::PROVIDER_TYPE_DEFINITIONS;
        }

        return array_filter(
            self::PROVIDER_TYPE_DEFINITIONS,
            static fn (array $definition): bool => !empty($definition['addable'])
        );
    }

    /** @return array<string, mixed> */
    public static function getProviderTypeDefinition(string $providerType): array
    {
        $providerType = strtolower(trim($providerType));

        return self::PROVIDER_TYPE_DEFINITIONS[$providerType] ?? self::PROVIDER_TYPE_DEFINITIONS['mock'];
    }

    public static function isKnownProviderType(string $providerType): bool
    {
        return array_key_exists(strtolower(trim($providerType)), self::PROVIDER_TYPE_DEFINITIONS);
    }

    public static function isAddableProviderType(string $providerType): bool
    {
        $definition = self::getProviderTypeDefinition($providerType);

        return !empty($definition['addable']);
    }

    /** @return array<string, mixed> */
    public function buildProviderEntry(string $providerType, ?string $providerId = null): array
    {
        $providerType = self::isKnownProviderType($providerType) ? strtolower(trim($providerType)) : 'mock';
        $providerId = $this->sanitizeProviderId($providerId ?? $this->generateProviderId($providerType));

        return $this->normalizeProviderEntry([
            'id' => $providerId,
            'type' => $providerType,
        ], $providerType, $providerId);
    }

    public function getProviderSecret(string $providerId, string $providerType = ''): string
    {
        $providerId = $this->sanitizeProviderId($providerId);
        if ($providerId === '') {
            return '';
        }

        $secret = $this->settings->getString(self::GROUP_PROVIDERS, $this->buildProviderSecretKey($providerId));
        if ($secret !== '') {
            return $secret;
        }

        $providerType = strtolower(trim($providerType));
        if ($providerType === '' && in_array($providerId, self::PROVIDER_SLUGS, true)) {
            $providerType = $providerId;
        }

        $legacySecretKey = $this->resolveLegacySecretKey($providerType);
        if ($legacySecretKey === '') {
            return '';
        }

        return $this->settings->getString(self::GROUP_PROVIDERS, $legacySecretKey);
    }

    public function hasProviderSecret(string $providerId, string $providerType = ''): bool
    {
        return $this->getProviderSecret($providerId, $providerType) !== '';
    }

    /** @return array<string, mixed> */
    public function getConfiguration(): array
    {
        $providers = $this->normalizeProviders($this->settings->getGroup(self::GROUP_PROVIDERS));
        $features = $this->normalizeFeatures($this->settings->getGroup(self::GROUP_FEATURES));
        $translation = $this->normalizeTranslation($this->settings->getGroup(self::GROUP_TRANSLATION));
        $logging = $this->normalizeLogging($this->settings->getGroup(self::GROUP_LOGGING));
        $quotas = $this->normalizeQuotas($this->settings->getGroup(self::GROUP_QUOTAS));

        return [
            'providers' => $providers,
            'features' => $features,
            'translation' => $translation,
            'logging' => $logging,
            'quotas' => $quotas,
            'summary' => $this->buildSummary($providers, $features, $translation, $logging, $quotas),
        ];
    }

    /**
     * @param array<string, mixed> $meta
     * @param list<array<string, mixed>> $entries
     * @param array<string, string> $secretValues
     * @param list<string> $clearSecrets
     */
    public function saveProviders(array $meta, array $entries, array $secretValues = [], array $clearSecrets = []): bool
    {
        $sanitizedEntries = [];
        $knownEntryIds = [];

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $providerType = strtolower(trim((string) ($entry['type'] ?? 'mock')));
            if (!self::isKnownProviderType($providerType)) {
                continue;
            }

            $providerId = $this->sanitizeProviderId((string) ($entry['id'] ?? $this->generateProviderId($providerType)));
            if ($providerId === '' || isset($knownEntryIds[$providerId])) {
                continue;
            }

            $sanitizedEntries[] = $this->prepareProviderEntryForStorage($entry, $providerType, $providerId);
            $knownEntryIds[$providerId] = true;
        }

        $entryIds = array_values(array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $sanitizedEntries
        ));

        $payload = [
            'active_provider_id' => $this->normalizeSelectedProviderId((string) ($meta['active_provider_id'] ?? ''), $entryIds, $sanitizedEntries),
            'fallback_provider_id' => $this->normalizeSelectedProviderId((string) ($meta['fallback_provider_id'] ?? ''), $entryIds, $sanitizedEntries, (string) ($meta['active_provider_id'] ?? '')),
            'entries' => $sanitizedEntries,
        ];

        if (!$this->settings->setMany(self::GROUP_PROVIDERS, $payload, [], 0)) {
            return false;
        }

        foreach ($secretValues as $providerId => $secretValue) {
            $providerId = $this->sanitizeProviderId((string) $providerId);
            $secretValue = trim($secretValue);
            if ($providerId === '' || $secretValue === '' || !isset($knownEntryIds[$providerId])) {
                continue;
            }

            if (!$this->settings->set(self::GROUP_PROVIDERS, $this->buildProviderSecretKey($providerId), $secretValue, true, 0)) {
                return false;
            }
        }

        foreach ($clearSecrets as $providerId) {
            $providerId = $this->sanitizeProviderId((string) $providerId);
            if ($providerId === '') {
                continue;
            }

            if (!$this->settings->forget(self::GROUP_PROVIDERS, $this->buildProviderSecretKey($providerId))) {
                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $values */
    public function saveFeatures(array $values): bool
    {
        return $this->settings->setMany(self::GROUP_FEATURES, $values, [], 0);
    }

    /** @param array<string, mixed> $values */
    public function saveTranslation(array $values): bool
    {
        return $this->settings->setMany(self::GROUP_TRANSLATION, $values, [], 0);
    }

    /** @param array<string, mixed> $values */
    public function saveLogging(array $values): bool
    {
        return $this->settings->setMany(self::GROUP_LOGGING, $values, [], 0);
    }

    /** @param array<string, mixed> $values */
    public function saveQuotas(array $values): bool
    {
        return $this->settings->setMany(self::GROUP_QUOTAS, $values, [], 0);
    }

    /** @return array<string, mixed> */
    private function normalizeProviders(array $stored): array
    {
        $defaults = $this->defaultProviders();
        $rawEntries = $this->extractStoredProviderEntries($stored);
        $entries = [];
        $knownEntryIds = [];

        foreach ($rawEntries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $providerType = strtolower(trim((string) ($entry['type'] ?? 'mock')));
            if (!self::isKnownProviderType($providerType)) {
                continue;
            }

            $providerId = $this->sanitizeProviderId((string) ($entry['id'] ?? $providerType));
            if ($providerId === '' || isset($knownEntryIds[$providerId])) {
                continue;
            }

            $entries[] = $this->normalizeProviderEntry($entry, $providerType, $providerId);
            $knownEntryIds[$providerId] = true;
        }

        if ($entries === []) {
            $entries[] = $this->buildProviderEntry('mock', 'mock');
        }

        $entryIds = array_values(array_map(
            static fn (array $entry): string => (string) ($entry['id'] ?? ''),
            $entries
        ));

        $activeProviderId = $this->normalizeSelectedProviderId(
            (string) ($stored['active_provider_id'] ?? $stored['active_provider'] ?? $defaults['active_provider_id']),
            $entryIds,
            $entries
        );
        $fallbackProviderId = $this->normalizeSelectedProviderId(
            (string) ($stored['fallback_provider_id'] ?? $stored['fallback_provider'] ?? $defaults['fallback_provider_id']),
            $entryIds,
            $entries,
            $activeProviderId
        );

        return [
            'active_provider_id' => $activeProviderId,
            'fallback_provider_id' => $fallbackProviderId,
            'entries' => $entries,
            'catalog' => $this->buildProviderCatalog(),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeProviderEntry(array $stored, string $providerType, string $providerId): array
    {
        $definition = self::getProviderTypeDefinition($providerType);
        $defaults = $this->defaultProviderConfig($providerType);
        $profile = (string) ($stored['profile'] ?? $defaults['profile']);
        if (!in_array($profile, ['disabled', 'beta', 'editor-translation', 'content-assist', 'seo-assist'], true)) {
            $profile = (string) $defaults['profile'];
        }

        $label = trim((string) ($stored['label'] ?? $defaults['label']));
        if ($label === '') {
            $label = (string) $defaults['label'];
        }

        return [
            'id' => $providerId,
            'type' => $providerType,
            'type_label' => (string) ($definition['label'] ?? ucfirst(str_replace('_', ' ', $providerType))),
            'description' => (string) ($definition['description'] ?? ''),
            'live_supported' => !empty($definition['live_supported']),
            'addable' => !empty($definition['addable']),
            'requires_secret' => !empty($definition['requires_secret']),
            'secret_label' => (string) ($definition['secret_label'] ?? ''),
            'label' => $label,
            'enabled' => (bool) ($stored['enabled'] ?? $defaults['enabled']),
            'profile' => $profile,
            'default_model' => trim((string) ($stored['default_model'] ?? $defaults['default_model'])),
            'endpoint' => trim((string) ($stored['endpoint'] ?? $defaults['endpoint'])),
            'deployment' => trim((string) ($stored['deployment'] ?? $defaults['deployment'])),
            'api_version' => trim((string) ($stored['api_version'] ?? $defaults['api_version'])),
            'translation_enabled' => (bool) ($stored['translation_enabled'] ?? $defaults['translation_enabled']),
            'rewrite_enabled' => (bool) ($stored['rewrite_enabled'] ?? $defaults['rewrite_enabled']),
            'summary_enabled' => (bool) ($stored['summary_enabled'] ?? $defaults['summary_enabled']),
            'seo_meta_enabled' => (bool) ($stored['seo_meta_enabled'] ?? $defaults['seo_meta_enabled']),
            'editorjs_enabled' => (bool) ($stored['editorjs_enabled'] ?? $defaults['editorjs_enabled']),
            'allowed_locales' => $this->normalizeStringList($stored['allowed_locales'] ?? $defaults['allowed_locales'], ['en']),
            'beta_only' => (bool) ($stored['beta_only'] ?? $defaults['beta_only']),
            'secret_configured' => $this->hasProviderSecret($providerId, $providerType),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeFeatures(array $stored): array
    {
        $defaults = $this->defaultFeatures();

        return [
            'ai_services_enabled' => (bool) ($stored['ai_services_enabled'] ?? $defaults['ai_services_enabled']),
            'ai_translation_enabled' => (bool) ($stored['ai_translation_enabled'] ?? $defaults['ai_translation_enabled']),
            'ai_rewrite_enabled' => (bool) ($stored['ai_rewrite_enabled'] ?? $defaults['ai_rewrite_enabled']),
            'ai_summary_enabled' => (bool) ($stored['ai_summary_enabled'] ?? $defaults['ai_summary_enabled']),
            'ai_seo_meta_enabled' => (bool) ($stored['ai_seo_meta_enabled'] ?? $defaults['ai_seo_meta_enabled']),
            'ai_editorjs_enabled' => (bool) ($stored['ai_editorjs_enabled'] ?? $defaults['ai_editorjs_enabled']),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeTranslation(array $stored): array
    {
        $defaults = $this->defaultTranslation();
        $resultMode = (string) ($stored['result_mode'] ?? $defaults['result_mode']);
        if (!in_array($resultMode, ['preview', 'localized-field', 'overwrite-current-draft'], true)) {
            $resultMode = (string) $defaults['result_mode'];
        }

        return [
            'default_source_locale' => $this->normalizeLocale((string) ($stored['default_source_locale'] ?? $defaults['default_source_locale']), (string) $defaults['default_source_locale']),
            'default_target_locale' => $this->normalizeLocale((string) ($stored['default_target_locale'] ?? $defaults['default_target_locale']), (string) $defaults['default_target_locale']),
            'allowed_target_locales' => $this->normalizeStringList($stored['allowed_target_locales'] ?? $defaults['allowed_target_locales'], ['en']),
            'supported_block_types' => $this->normalizeSupportedBlockTypes($stored['supported_block_types'] ?? $defaults['supported_block_types'], (array) $defaults['supported_block_types']),
            'preview_required' => (bool) ($stored['preview_required'] ?? $defaults['preview_required']),
            'preserve_unsupported_blocks' => (bool) ($stored['preserve_unsupported_blocks'] ?? $defaults['preserve_unsupported_blocks']),
            'skip_html_blocks' => (bool) ($stored['skip_html_blocks'] ?? $defaults['skip_html_blocks']),
            'result_mode' => $resultMode,
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeLogging(array $stored): array
    {
        $defaults = $this->defaultLogging();
        $mode = (string) ($stored['logging_mode'] ?? $defaults['logging_mode']);
        if (!in_array($mode, ['minimal', 'technical', 'debug-no-content'], true)) {
            $mode = (string) $defaults['logging_mode'];
        }

        return [
            'logging_mode' => $mode,
            'retention_days' => max(1, (int) ($stored['retention_days'] ?? $defaults['retention_days'])),
            'store_content_hashes' => (bool) ($stored['store_content_hashes'] ?? $defaults['store_content_hashes']),
            'store_request_metrics' => (bool) ($stored['store_request_metrics'] ?? $defaults['store_request_metrics']),
            'store_error_context' => (bool) ($stored['store_error_context'] ?? $defaults['store_error_context']),
            'store_prompt_preview' => (bool) ($stored['store_prompt_preview'] ?? $defaults['store_prompt_preview']),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeQuotas(array $stored): array
    {
        $defaults = $this->defaultQuotas();

        return [
            'max_chars_per_request' => max(250, (int) ($stored['max_chars_per_request'] ?? $defaults['max_chars_per_request'])),
            'max_blocks_per_request' => max(1, (int) ($stored['max_blocks_per_request'] ?? $defaults['max_blocks_per_request'])),
            'timeout_seconds' => max(5, (int) ($stored['timeout_seconds'] ?? $defaults['timeout_seconds'])),
            'retry_count' => max(0, (int) ($stored['retry_count'] ?? $defaults['retry_count'])),
            'daily_requests_per_user' => max(1, (int) ($stored['daily_requests_per_user'] ?? $defaults['daily_requests_per_user'])),
            'daily_chars_per_user' => max(500, (int) ($stored['daily_chars_per_user'] ?? $defaults['daily_chars_per_user'])),
            'monthly_requests_per_provider' => max(10, (int) ($stored['monthly_requests_per_provider'] ?? $defaults['monthly_requests_per_provider'])),
        ];
    }

    /** @return array<string, mixed> */
    private function buildSummary(array $providers, array $features, array $translation, array $logging, array $quotas): array
    {
        $providerConfigs = is_array($providers['entries'] ?? null) ? $providers['entries'] : [];
        $enabledProviders = 0;
        $translationReadyProviders = 0;

        foreach ($providerConfigs as $provider) {
            if (!is_array($provider)) {
                continue;
            }

            if (!empty($provider['enabled'])) {
                $enabledProviders++;
            }

            if (!empty($provider['enabled']) && !empty($provider['translation_enabled']) && !empty($provider['editorjs_enabled'])) {
                $translationReadyProviders++;
            }
        }

        $enabledFeatures = 0;
        foreach ($features as $value) {
            if ($value === true) {
                $enabledFeatures++;
            }
        }

        return [
            'provider_total' => count($providerConfigs),
            'provider_enabled' => $enabledProviders,
            'feature_enabled' => $enabledFeatures,
            'translation_ready' => !empty($features['ai_services_enabled'])
                && !empty($features['ai_translation_enabled'])
                && !empty($features['ai_editorjs_enabled'])
                && $translationReadyProviders > 0,
            'translation_ready_provider_count' => $translationReadyProviders,
            'target_locale_count' => count((array) ($translation['allowed_target_locales'] ?? [])),
            'logging_mode' => (string) ($logging['logging_mode'] ?? 'minimal'),
            'quota_chars' => (int) ($quotas['max_chars_per_request'] ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function defaultProviders(): array
    {
        return [
            'active_provider_id' => 'mock',
            'fallback_provider_id' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function defaultProviderConfig(string $providerSlug): array
    {
        return match ($providerSlug) {
            'mock' => [
                'label' => 'Mock Provider',
                'enabled' => true,
                'profile' => 'editor-translation',
                'default_model' => 'mock-local-v1',
                'endpoint' => 'internal://mock',
                'deployment' => '',
                'api_version' => '',
                'translation_enabled' => true,
                'rewrite_enabled' => false,
                'summary_enabled' => false,
                'seo_meta_enabled' => false,
                'editorjs_enabled' => true,
                'allowed_locales' => ['en'],
                'beta_only' => false,
            ],
            'openai' => [
                'label' => 'OpenAI',
                'enabled' => false,
                'profile' => 'editor-translation',
                'default_model' => 'gpt-5-mini',
                'endpoint' => 'https://api.openai.com/v1',
                'deployment' => '',
                'api_version' => '',
                'translation_enabled' => true,
                'rewrite_enabled' => true,
                'summary_enabled' => true,
                'seo_meta_enabled' => true,
                'editorjs_enabled' => true,
                'allowed_locales' => ['en'],
                'beta_only' => true,
            ],
            'azure_openai' => [
                'label' => 'Azure AI',
                'enabled' => false,
                'profile' => 'editor-translation',
                'default_model' => 'gpt-4.1-mini',
                'endpoint' => '',
                'deployment' => '',
                'api_version' => '2024-10-21',
                'translation_enabled' => true,
                'rewrite_enabled' => true,
                'summary_enabled' => true,
                'seo_meta_enabled' => true,
                'editorjs_enabled' => true,
                'allowed_locales' => ['en'],
                'beta_only' => true,
            ],
            'ollama' => [
                'label' => 'Ollama',
                'enabled' => false,
                'profile' => 'beta',
                'default_model' => 'llama3.1:8b',
                'endpoint' => 'http://127.0.0.1:11434',
                'deployment' => '',
                'api_version' => '',
                'translation_enabled' => true,
                'rewrite_enabled' => true,
                'summary_enabled' => true,
                'seo_meta_enabled' => false,
                'editorjs_enabled' => true,
                'allowed_locales' => ['en'],
                'beta_only' => true,
            ],
            'openrouter' => [
                'label' => 'OpenRouter',
                'enabled' => false,
                'profile' => 'beta',
                'default_model' => 'openai/gpt-4.1-mini',
                'endpoint' => 'https://openrouter.ai/api/v1',
                'deployment' => '',
                'api_version' => '',
                'translation_enabled' => true,
                'rewrite_enabled' => true,
                'summary_enabled' => true,
                'seo_meta_enabled' => true,
                'editorjs_enabled' => true,
                'allowed_locales' => ['en'],
                'beta_only' => true,
            ],
            default => [
                'label' => ucfirst(str_replace('_', ' ', $providerSlug)),
                'enabled' => false,
                'profile' => 'disabled',
                'default_model' => '',
                'endpoint' => '',
                'deployment' => '',
                'api_version' => '',
                'translation_enabled' => false,
                'rewrite_enabled' => false,
                'summary_enabled' => false,
                'seo_meta_enabled' => false,
                'editorjs_enabled' => false,
                'allowed_locales' => ['en'],
                'beta_only' => true,
            ],
        };
    }

    /** @return array<string, bool> */
    private function defaultFeatures(): array
    {
        return [
            'ai_services_enabled' => true,
            'ai_translation_enabled' => true,
            'ai_rewrite_enabled' => false,
            'ai_summary_enabled' => false,
            'ai_seo_meta_enabled' => false,
            'ai_editorjs_enabled' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function defaultTranslation(): array
    {
        return [
            'default_source_locale' => 'de',
            'default_target_locale' => 'en',
            'allowed_target_locales' => ['en'],
            'supported_block_types' => ['paragraph', 'header', 'list', 'checklist', 'quote', 'callout', 'warning', 'mediaText'],
            'preview_required' => true,
            'preserve_unsupported_blocks' => true,
            'skip_html_blocks' => true,
            'result_mode' => 'localized-field',
        ];
    }

    /** @return array<string, mixed> */
    private function defaultLogging(): array
    {
        return [
            'logging_mode' => 'technical',
            'retention_days' => 30,
            'store_content_hashes' => true,
            'store_request_metrics' => true,
            'store_error_context' => true,
            'store_prompt_preview' => false,
        ];
    }

    /** @return array<string, int> */
    private function defaultQuotas(): array
    {
        return [
            'max_chars_per_request' => 12000,
            'max_blocks_per_request' => 40,
            'timeout_seconds' => 25,
            'retry_count' => 1,
            'daily_requests_per_user' => 40,
            'daily_chars_per_user' => 120000,
            'monthly_requests_per_provider' => 5000,
        ];
    }

    /** @param mixed $value
     *  @param list<string> $fallback
     *  @return list<string>
     */
    private function normalizeStringList(mixed $value, array $fallback): array
    {
        $source = [];
        if (is_array($value)) {
            $source = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $source = preg_split('/\s*,\s*/', trim($value)) ?: [];
        }

        $normalized = [];
        foreach ($source as $item) {
            $item = strtolower(trim((string) $item));
            $item = preg_replace('/[^a-z0-9._-]+/', '', $item) ?? '';
            if ($item === '') {
                continue;
            }

            $normalized[$item] = $item;
        }

        return $normalized !== [] ? array_values($normalized) : $fallback;
    }

    /** @param mixed $value
     *  @param list<string> $fallback
     *  @return list<string>
     */
    private function normalizeSupportedBlockTypes(mixed $value, array $fallback): array
    {
        $source = [];
        if (is_array($value)) {
            $source = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $source = preg_split('/\s*,\s*/', trim($value)) ?: [];
        }

        $normalized = [];
        foreach ($source as $item) {
            $item = strtolower(trim((string) $item));
            $item = preg_replace('/[^a-z0-9._-]+/', '', $item) ?? '';
            if ($item === '' || !isset(self::SUPPORTED_EDITORJS_BLOCK_TYPES[$item])) {
                continue;
            }

            $canonical = self::SUPPORTED_EDITORJS_BLOCK_TYPES[$item];
            $normalized[$canonical] = $canonical;
        }

        if ($normalized !== []) {
            return array_values($normalized);
        }

        $resolvedFallback = [];
        foreach ($fallback as $item) {
            $key = strtolower(trim((string) $item));
            if ($key === '' || !isset(self::SUPPORTED_EDITORJS_BLOCK_TYPES[$key])) {
                continue;
            }

            $canonical = self::SUPPORTED_EDITORJS_BLOCK_TYPES[$key];
            $resolvedFallback[$canonical] = $canonical;
        }

        return $resolvedFallback !== [] ? array_values($resolvedFallback) : array_values(self::SUPPORTED_EDITORJS_BLOCK_TYPES);
    }

    private function normalizeLocale(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return $value !== '' ? $value : $fallback;
    }

    /** @return list<array<string, mixed>> */
    private function extractStoredProviderEntries(array $stored): array
    {
        if (is_array($stored['entries'] ?? null)) {
            return array_values(array_filter(
                $stored['entries'],
                static fn (mixed $entry): bool => is_array($entry)
            ));
        }

        return $this->migrateLegacyProviders($stored);
    }

    /** @return list<array<string, mixed>> */
    private function migrateLegacyProviders(array $stored): array
    {
        $entries = [];

        foreach (self::PROVIDER_SLUGS as $providerSlug) {
            $legacyConfig = is_array($stored[$providerSlug] ?? null) ? $stored[$providerSlug] : [];
            $defaults = $this->defaultProviderConfig($providerSlug);
            $shouldInclude = $providerSlug === 'mock'
                || !empty($legacyConfig['enabled'])
                || $this->legacyProviderConfigLooksCustomized($legacyConfig, $defaults)
                || $this->hasProviderSecret($providerSlug, $providerSlug);

            if (!$shouldInclude) {
                continue;
            }

            $entries[] = array_merge($defaults, $legacyConfig, [
                'id' => $providerSlug,
                'type' => $providerSlug,
            ]);
        }

        return $entries;
    }

    private function legacyProviderConfigLooksCustomized(array $legacyConfig, array $defaults): bool
    {
        $keysToCompare = [
            'label',
            'profile',
            'default_model',
            'endpoint',
            'deployment',
            'api_version',
            'translation_enabled',
            'rewrite_enabled',
            'summary_enabled',
            'seo_meta_enabled',
            'editorjs_enabled',
            'allowed_locales',
            'beta_only',
        ];

        foreach ($keysToCompare as $key) {
            if (($legacyConfig[$key] ?? $defaults[$key] ?? null) !== ($defaults[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $entryIds
     *  @param list<array<string, mixed>> $entries
     */
    private function normalizeSelectedProviderId(string $candidate, array $entryIds, array $entries, string $exclude = ''): string
    {
        $candidate = $this->sanitizeProviderId($candidate);
        $exclude = $this->sanitizeProviderId($exclude);

        if ($candidate !== '' && in_array($candidate, $entryIds, true) && $candidate !== $exclude) {
            return $candidate;
        }

        foreach ($entries as $entry) {
            $entryId = $this->sanitizeProviderId((string) ($entry['id'] ?? ''));
            if ($entryId === '' || $entryId === $exclude) {
                continue;
            }

            if (!empty($entry['enabled'])) {
                return $entryId;
            }
        }

        foreach ($entryIds as $entryId) {
            $entryId = $this->sanitizeProviderId((string) $entryId);
            if ($entryId !== '' && $entryId !== $exclude) {
                return $entryId;
            }
        }

        return '';
    }

    /** @return array<string, mixed> */
    private function prepareProviderEntryForStorage(array $entry, string $providerType, string $providerId): array
    {
        $defaults = $this->defaultProviderConfig($providerType);

        return [
            'id' => $providerId,
            'type' => $providerType,
            'label' => trim((string) ($entry['label'] ?? $defaults['label'])) !== '' ? trim((string) ($entry['label'] ?? $defaults['label'])) : (string) $defaults['label'],
            'enabled' => (bool) ($entry['enabled'] ?? $defaults['enabled']),
            'profile' => (string) ($entry['profile'] ?? $defaults['profile']),
            'default_model' => trim((string) ($entry['default_model'] ?? $defaults['default_model'])),
            'endpoint' => trim((string) ($entry['endpoint'] ?? $defaults['endpoint'])),
            'deployment' => trim((string) ($entry['deployment'] ?? $defaults['deployment'])),
            'api_version' => trim((string) ($entry['api_version'] ?? $defaults['api_version'])),
            'translation_enabled' => (bool) ($entry['translation_enabled'] ?? $defaults['translation_enabled']),
            'rewrite_enabled' => (bool) ($entry['rewrite_enabled'] ?? $defaults['rewrite_enabled']),
            'summary_enabled' => (bool) ($entry['summary_enabled'] ?? $defaults['summary_enabled']),
            'seo_meta_enabled' => (bool) ($entry['seo_meta_enabled'] ?? $defaults['seo_meta_enabled']),
            'editorjs_enabled' => (bool) ($entry['editorjs_enabled'] ?? $defaults['editorjs_enabled']),
            'allowed_locales' => $this->normalizeStringList($entry['allowed_locales'] ?? $defaults['allowed_locales'], ['en']),
            'beta_only' => (bool) ($entry['beta_only'] ?? $defaults['beta_only']),
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function buildProviderCatalog(): array
    {
        $catalog = [];

        foreach (self::PROVIDER_TYPE_DEFINITIONS as $providerType => $definition) {
            $defaults = $this->defaultProviderConfig($providerType);
            $catalog[$providerType] = [
                'type' => $providerType,
                'label' => (string) ($definition['label'] ?? ucfirst(str_replace('_', ' ', $providerType))),
                'description' => (string) ($definition['description'] ?? ''),
                'requires_secret' => !empty($definition['requires_secret']),
                'secret_label' => (string) ($definition['secret_label'] ?? ''),
                'live_supported' => !empty($definition['live_supported']),
                'addable' => !empty($definition['addable']),
                'default_model' => (string) ($defaults['default_model'] ?? ''),
                'default_endpoint' => (string) ($defaults['endpoint'] ?? ''),
            ];
        }

        return $catalog;
    }

    private function resolveLegacySecretKey(string $providerType): string
    {
        return match ($providerType) {
            'openai' => 'openai_api_key',
            'azure_openai' => 'azure_openai_api_key',
            'openrouter' => 'openrouter_api_key',
            default => '',
        };
    }

    private function buildProviderSecretKey(string $providerId): string
    {
        return self::PROVIDER_SECRET_PREFIX . $this->sanitizeProviderId($providerId);
    }

    private function sanitizeProviderId(string $providerId): string
    {
        $providerId = strtolower(trim($providerId));
        $providerId = preg_replace('/[^a-z0-9._-]+/', '-', $providerId) ?? '';
        $providerId = preg_replace('/-+/', '-', $providerId) ?? '';

        return trim($providerId, '-');
    }

    private function generateProviderId(string $providerType): string
    {
        try {
            return $this->sanitizeProviderId($providerType . '-' . bin2hex(random_bytes(4)));
        } catch (\Throwable) {
            return $this->sanitizeProviderId($providerType . '-' . uniqid('', false));
        }
    }
}