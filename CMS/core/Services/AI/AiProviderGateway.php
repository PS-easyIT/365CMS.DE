<?php
declare(strict_types=1);

namespace CMS\Services\AI;

use CMS\Http\Client;
use CMS\Json;
use CMS\Logger;
use CMS\Services\AI\Providers\AzureOpenAiProvider;
use CMS\Services\AI\Providers\MockAiProvider;
use CMS\Services\AI\Providers\OllamaAiProvider;
use CMS\Services\EditorJs\EditorJsSanitizer;

if (!defined('ABSPATH')) {
    exit;
}

final class AiProviderGateway
{
    private static ?self $instance = null;

    private AiSettingsService $settings;
    private EditorJsTranslationPipeline $pipeline;
    private EditorJsSanitizer $editorJsSanitizer;
    private Client $httpClient;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = AiSettingsService::getInstance();
        $this->pipeline = EditorJsTranslationPipeline::getInstance();
        $this->editorJsSanitizer = new EditorJsSanitizer();
        $this->httpClient = Client::getInstance();
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function translateEditorJsDraft(array $request): array
    {
        $startedAt = microtime(true);
        $configuration = $this->settings->getConfiguration();
        $features = is_array($configuration['features'] ?? null) ? $configuration['features'] : [];
        $translationConfig = is_array($configuration['translation'] ?? null) ? $configuration['translation'] : [];
        $loggingConfig = is_array($configuration['logging'] ?? null) ? $configuration['logging'] : [];
        $quotaConfig = is_array($configuration['quotas'] ?? null) ? $configuration['quotas'] : [];
        $providersConfig = is_array($configuration['providers'] ?? null) ? $configuration['providers'] : [];

        $this->assertEditorJsTranslationEnabled($features);

        $sourceLocale = $this->normalizeLocale((string) ($request['source_locale'] ?? ($translationConfig['default_source_locale'] ?? 'de')), (string) ($translationConfig['default_source_locale'] ?? 'de'));
        $targetLocale = $this->normalizeLocale((string) ($request['target_locale'] ?? ($translationConfig['default_target_locale'] ?? 'en')), (string) ($translationConfig['default_target_locale'] ?? 'en'));
        $this->assertTargetLocaleAllowed($targetLocale, (array) ($translationConfig['allowed_target_locales'] ?? ['en']));

        $title = trim((string) ($request['title'] ?? ''));
        $excerpt = trim((string) ($request['excerpt'] ?? ''));
        $slug = trim((string) ($request['slug'] ?? ''));
        $contentType = $this->normalizeContentType((string) ($request['content_type'] ?? 'editorjs'));

        $sourceJson = (string) ($request['editor_data'] ?? '');
        $sanitizedJson = $this->editorJsSanitizer->sanitize($sourceJson);
        $editorData = Json::decodeArray($sanitizedJson, ['blocks' => []]);
        $blocks = is_array($editorData['blocks'] ?? null) ? $editorData['blocks'] : [];

        $this->enforceQuotas($title, $excerpt, $sanitizedJson, count($blocks), $quotaConfig);

        $providerResolution = $this->resolveProvider($providersConfig, $targetLocale, $quotaConfig);
        /** @var AiProviderInterface $provider */
        $provider = $providerResolution['provider'];
        $providerConfig = is_array($providerResolution['config'] ?? null) ? $providerResolution['config'] : [];

        $pipelineResult = $this->pipeline->translate(
            [
                'title' => $title,
                'excerpt' => $excerpt,
                'slug' => $slug,
                'content_type' => $contentType,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'editor_data' => $editorData,
            ],
            $provider,
            $translationConfig
        );

        $telemetry = [
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'requested_provider' => (string) ($providerResolution['requested_provider'] ?? 'mock'),
            'resolved_provider' => $provider->getSlug(),
            'resolved_provider_type' => (string) ($providerConfig['type'] ?? 'mock'),
            'resolved_via' => (string) ($providerResolution['resolved_via'] ?? 'direct'),
            'content_type' => $contentType,
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
        ];

        if (!empty($loggingConfig['store_content_hashes'])) {
            $telemetry['source_hash'] = hash('sha256', $sanitizedJson);
            $telemetry['translated_hash'] = hash('sha256', (string) ($pipelineResult['editor_json'] ?? '{"blocks":[]}'));
        }

        if (!empty($loggingConfig['store_request_metrics'])) {
            $telemetry['char_count'] = $this->measureCharCount($title, $excerpt, $sanitizedJson);
            $telemetry['block_count'] = count($blocks);
            $telemetry['translated_blocks'] = (int) (($pipelineResult['stats']['translated_blocks'] ?? 0));
        }

        Logger::instance()->withChannel('ai.gateway')->info('AI Editor.js-Übersetzung wurde verarbeitet.', [
            'provider' => $provider->getSlug(),
            'provider_type' => (string) ($providerConfig['type'] ?? 'mock'),
            'resolved_via' => (string) ($providerResolution['resolved_via'] ?? 'direct'),
            'content_type' => $contentType,
            'target_locale' => $targetLocale,
            'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
            'translated_blocks' => (int) (($pipelineResult['stats']['translated_blocks'] ?? 0)),
        ]);

        return [
            'provider' => [
                'slug' => $provider->getSlug(),
                'type' => (string) ($providerConfig['type'] ?? 'mock'),
                'id' => (string) ($providerConfig['id'] ?? $provider->getSlug()),
                'label' => $provider->getLabel(),
                'model' => (string) ($providerConfig['default_model'] ?? $provider->getDefaultModel()),
                'mock' => $provider->isMock(),
                'resolved_via' => (string) ($providerResolution['resolved_via'] ?? 'direct'),
            ],
            'preview_required' => !empty($translationConfig['preview_required']),
            'result_mode' => (string) ($translationConfig['result_mode'] ?? 'localized-field'),
            'warnings' => array_values(array_filter(array_merge(
                (array) ($providerResolution['warnings'] ?? []),
                (array) ($pipelineResult['warnings'] ?? [])
            ))),
            'translation' => [
                'title' => (string) ($pipelineResult['title'] ?? ''),
                'excerpt' => (string) ($pipelineResult['excerpt'] ?? ''),
                'slug' => (string) ($pipelineResult['slug'] ?? ''),
                'content_data' => is_array($pipelineResult['editor_data'] ?? null) ? $pipelineResult['editor_data'] : ['blocks' => []],
                'content_json' => (string) ($pipelineResult['editor_json'] ?? '{"blocks":[]}'),
            ],
            'stats' => is_array($pipelineResult['stats'] ?? null) ? $pipelineResult['stats'] : [],
            'telemetry' => $telemetry,
        ];
    }

    /** @param array<string, mixed> $features */
    private function assertEditorJsTranslationEnabled(array $features): void
    {
        if (empty($features['ai_services_enabled'])) {
            throw new \RuntimeException('AI Services sind aktuell global deaktiviert.');
        }

        if (empty($features['ai_translation_enabled'])) {
            throw new \RuntimeException('AI-Übersetzungen sind aktuell global deaktiviert.');
        }

        if (empty($features['ai_editorjs_enabled'])) {
            throw new \RuntimeException('Die Editor.js-Integration für AI ist aktuell deaktiviert.');
        }
    }

    /** @param list<string> $allowedTargetLocales */
    private function assertTargetLocaleAllowed(string $targetLocale, array $allowedTargetLocales): void
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            fn (string $locale): string => $this->normalizeLocale($locale, ''),
            $allowedTargetLocales
        ))));

        if ($normalized === []) {
            $normalized = ['en'];
        }

        if (!in_array($targetLocale, $normalized, true)) {
            throw new \InvalidArgumentException('Die gewählte Zielsprache ist für die aktuelle AI-Konfiguration nicht freigegeben.');
        }
    }

    /** @param array<string, mixed> $quotaConfig */
    private function enforceQuotas(string $title, string $excerpt, string $editorJson, int $blockCount, array $quotaConfig): void
    {
        $charCount = $this->measureCharCount($title, $excerpt, $editorJson);
        $maxChars = max(250, (int) ($quotaConfig['max_chars_per_request'] ?? 12000));
        $maxBlocks = max(1, (int) ($quotaConfig['max_blocks_per_request'] ?? 40));

        if ($charCount > $maxChars) {
            throw new \InvalidArgumentException('Die Übersetzungsanfrage überschreitet das aktuell erlaubte Zeichenlimit.');
        }

        if ($blockCount > $maxBlocks) {
            throw new \InvalidArgumentException('Die Übersetzungsanfrage überschreitet die aktuell erlaubte Blockanzahl.');
        }
    }

    private function measureCharCount(string $title, string $excerpt, string $editorJson): int
    {
        $payload = $title . "\n" . $excerpt . "\n" . $editorJson;

        return function_exists('mb_strlen')
            ? mb_strlen($payload, 'UTF-8')
            : strlen($payload);
    }

    /**
     * @param array<string, mixed> $providersConfig
     * @param array<string, mixed> $quotaConfig
     * @return array{provider:AiProviderInterface,config:array<string,mixed>,requested_provider:string,resolved_via:string,warnings:list<string>}
     */
    private function resolveProvider(array $providersConfig, string $targetLocale, array $quotaConfig): array
    {
        $providerEntries = array_values(array_filter(
            (array) ($providersConfig['entries'] ?? []),
            static fn (mixed $entry): bool => is_array($entry)
        ));
        $requestedProvider = trim((string) ($providersConfig['active_provider_id'] ?? ''));
        $fallbackProvider = trim((string) ($providersConfig['fallback_provider_id'] ?? ''));
        $warnings = [];

        $entriesById = [];
        foreach ($providerEntries as $entry) {
            $entryId = trim((string) ($entry['id'] ?? ''));
            if ($entryId === '') {
                continue;
            }

            $entriesById[$entryId] = $entry;
        }

        $candidateIds = array_values(array_unique(array_filter(array_merge(
            [$requestedProvider, $fallbackProvider],
            array_keys($entriesById)
        ))));

        foreach ($candidateIds as $index => $providerId) {
            $providerConfig = is_array($entriesById[$providerId] ?? null) ? $entriesById[$providerId] : [];
            if ($providerConfig === []) {
                continue;
            }

            $readinessIssues = $this->collectProviderReadinessIssues($providerConfig, $targetLocale);
            if ($readinessIssues !== []) {
                $warnings[] = 'Provider „' . (string) ($providerConfig['label'] ?? $providerId) . '“ wurde übersprungen: ' . implode(' ', $readinessIssues);
                continue;
            }

            try {
                $provider = $this->createProvider($providerConfig, $quotaConfig);
            } catch (\Throwable $e) {
                $warnings[] = 'Provider „' . (string) ($providerConfig['label'] ?? $providerId) . '“ konnte nicht initialisiert werden: ' . $e->getMessage();
                continue;
            }

            if ($provider === null) {
                $warnings[] = 'Für den Provider „' . (string) ($providerConfig['label'] ?? $providerId) . '“ existiert aktuell noch kein Live-Adapter.';
                continue;
            }

            return [
                'provider' => $provider,
                'config' => $providerConfig,
                'requested_provider' => $requestedProvider !== '' ? $requestedProvider : $providerId,
                'resolved_via' => $index === 0 ? 'direct' : ($providerId === $fallbackProvider ? 'fallback' : 'auto-fallback'),
                'warnings' => $warnings,
            ];
        }

        throw new \RuntimeException('Kein für Editor.js-Übersetzungen freigegebener Provider ist aktuell einsatzbereit.');
    }

    /** @param array<string, mixed> $providerConfig
     *  @return list<string>
     */
    private function collectProviderReadinessIssues(array $providerConfig, string $targetLocale): array
    {
        $issues = [];
        $providerType = strtolower(trim((string) ($providerConfig['type'] ?? '')));
        $providerId = trim((string) ($providerConfig['id'] ?? ''));

        if ($providerId === '') {
            $issues[] = 'Es fehlt eine interne Provider-ID.';
        }

        if (!AiSettingsService::isKnownProviderType($providerType)) {
            $issues[] = 'Der Providertyp ist unbekannt.';
            return $issues;
        }

        if (empty($providerConfig['enabled']) || empty($providerConfig['translation_enabled']) || empty($providerConfig['editorjs_enabled'])) {
            $issues[] = 'Provider oder Translation-/Editor.js-Scope ist deaktiviert.';
        }

        $allowedLocales = array_values(array_unique(array_filter(array_map(
            fn (string $locale): string => $this->normalizeLocale($locale, ''),
            (array) ($providerConfig['allowed_locales'] ?? ['en'])
        ))));

        if ($allowedLocales !== [] && !in_array($targetLocale, $allowedLocales, true)) {
            $issues[] = 'Zielsprache ' . strtoupper($targetLocale) . ' ist für diesen Provider nicht freigegeben.';
        }

        $definition = AiSettingsService::getProviderTypeDefinition($providerType);
        if (!empty($definition['requires_secret']) && !$this->settings->hasProviderSecret($providerId, $providerType)) {
            $issues[] = 'Es ist kein Secret/API-Key hinterlegt.';
        }

        if ($providerType === 'ollama') {
            if (trim((string) ($providerConfig['endpoint'] ?? '')) === '') {
                $issues[] = 'Der Ollama-Endpoint fehlt.';
            }

            if (trim((string) ($providerConfig['default_model'] ?? '')) === '') {
                $issues[] = 'Das Ollama-Modell fehlt.';
            }
        }

        if ($providerType === 'azure_openai') {
            if (trim((string) ($providerConfig['endpoint'] ?? '')) === '') {
                $issues[] = 'Der Azure-Endpoint fehlt.';
            }

            if (trim((string) ($providerConfig['deployment'] ?? '')) === '') {
                $issues[] = 'Der Azure-Deployment-Name fehlt.';
            }

            if (trim((string) ($providerConfig['api_version'] ?? '')) === '') {
                $issues[] = 'Die Azure-API-Version fehlt.';
            }
        }

        return $issues;
    }

    /** @param array<string, mixed> $providerConfig
     *  @param array<string, mixed> $quotaConfig
     */
    private function createProvider(array $providerConfig, array $quotaConfig): ?AiProviderInterface
    {
        $providerType = strtolower(trim((string) ($providerConfig['type'] ?? 'mock')));
        $providerId = (string) ($providerConfig['id'] ?? $providerType);
        $label = (string) ($providerConfig['label'] ?? ($providerConfig['type_label'] ?? ucfirst(str_replace('_', ' ', $providerType))));
        $defaultModel = trim((string) ($providerConfig['default_model'] ?? ''));
        $timeoutSeconds = max(5, (int) ($quotaConfig['timeout_seconds'] ?? 25));

        return match ($providerType) {
            'mock' => new MockAiProvider($providerId, $label, $defaultModel !== '' ? $defaultModel : 'mock-local-v1'),
            'ollama' => new OllamaAiProvider(
                $providerId,
                $label,
                $defaultModel !== '' ? $defaultModel : 'llama3.1:8b',
                (string) ($providerConfig['endpoint'] ?? 'http://127.0.0.1:11434'),
                $this->httpClient,
                $timeoutSeconds
            ),
            'azure_openai' => new AzureOpenAiProvider(
                $providerId,
                $label,
                $defaultModel !== '' ? $defaultModel : 'gpt-4.1-mini',
                (string) ($providerConfig['endpoint'] ?? ''),
                (string) ($providerConfig['deployment'] ?? ''),
                (string) ($providerConfig['api_version'] ?? '2024-10-21'),
                $this->settings->getProviderSecret($providerId, $providerType),
                $this->httpClient,
                $timeoutSeconds
            ),
            default => null,
        };
    }

    private function normalizeLocale(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return $value !== '' ? $value : $fallback;
    }

    private function normalizeContentType(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['post', 'page'], true) ? $value : 'editorjs';
    }
}