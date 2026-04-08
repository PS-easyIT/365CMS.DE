<?php
declare(strict_types=1);

namespace CMS\Services\AI;

use CMS\Json;
use CMS\Logger;
use CMS\Services\AI\Providers\MockAiProvider;
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

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = AiSettingsService::getInstance();
        $this->pipeline = EditorJsTranslationPipeline::getInstance();
        $this->editorJsSanitizer = new EditorJsSanitizer();
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

        $providerResolution = $this->resolveProvider($providersConfig, $targetLocale);
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
            'resolved_via' => (string) ($providerResolution['resolved_via'] ?? 'direct'),
            'content_type' => $contentType,
            'target_locale' => $targetLocale,
            'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
            'translated_blocks' => (int) (($pipelineResult['stats']['translated_blocks'] ?? 0)),
        ]);

        return [
            'provider' => [
                'slug' => $provider->getSlug(),
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
     * @return array{provider:AiProviderInterface,config:array<string,mixed>,requested_provider:string,resolved_via:string,warnings:list<string>}
     */
    private function resolveProvider(array $providersConfig, string $targetLocale): array
    {
        $providerProfiles = is_array($providersConfig['providers'] ?? null) ? $providersConfig['providers'] : [];
        $requestedProvider = (string) ($providersConfig['active_provider'] ?? 'mock');
        $fallbackProvider = (string) ($providersConfig['fallback_provider'] ?? 'openai');
        $warnings = [];

        $candidates = array_values(array_unique(array_filter([$requestedProvider, $fallbackProvider, 'mock'])));

        foreach ($candidates as $index => $providerSlug) {
            $providerConfig = is_array($providerProfiles[$providerSlug] ?? null) ? $providerProfiles[$providerSlug] : [];
            if (!$this->isProviderReady($providerSlug, $providerConfig, $targetLocale)) {
                continue;
            }

            $provider = $this->createProvider($providerSlug);
            if ($provider === null) {
                $warnings[] = 'Für den Provider „' . $providerSlug . '“ existiert noch kein Live-Adapter. Es wird der nächste verfügbare Provider geprüft.';
                continue;
            }

            return [
                'provider' => $provider,
                'config' => $providerConfig,
                'requested_provider' => $requestedProvider,
                'resolved_via' => $index === 0 ? 'direct' : ($providerSlug === 'mock' ? 'mock-fallback' : 'fallback'),
                'warnings' => $warnings,
            ];
        }

        throw new \RuntimeException('Kein für Editor.js-Übersetzungen freigegebener Provider ist aktuell einsatzbereit.');
    }

    /** @param array<string, mixed> $providerConfig */
    private function isProviderReady(string $providerSlug, array $providerConfig, string $targetLocale): bool
    {
        if (!in_array($providerSlug, AiSettingsService::PROVIDER_SLUGS, true)) {
            return false;
        }

        if (empty($providerConfig['enabled']) || empty($providerConfig['translation_enabled']) || empty($providerConfig['editorjs_enabled'])) {
            return false;
        }

        $allowedLocales = array_values(array_unique(array_filter(array_map(
            fn (string $locale): string => $this->normalizeLocale($locale, ''),
            (array) ($providerConfig['allowed_locales'] ?? ['en'])
        ))));

        return $allowedLocales === [] || in_array($targetLocale, $allowedLocales, true);
    }

    private function createProvider(string $providerSlug): ?AiProviderInterface
    {
        return match ($providerSlug) {
            'mock' => new MockAiProvider(),
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