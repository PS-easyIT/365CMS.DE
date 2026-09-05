<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

final class AiProviderPolicyService
{
    /** @var list<string> */
    private const EXTERNAL_PROVIDER_TYPES = ['openai', 'mistral', 'azure_openai', 'openrouter'];

    /**
     * @param array<string, mixed> $features
     * @param array<string, mixed> $providerConfig
     */
    public function assertFeatureAllowed(array $features, array $providerConfig, string $feature): void
    {
        if (empty($features['ai_services_enabled'])) {
            throw new \RuntimeException('AI Services sind aktuell global deaktiviert.');
        }

        if (empty($providerConfig['enabled'])) {
            throw new \RuntimeException('Der gewählte AI-Provider ist deaktiviert.');
        }

        $profile = strtolower(trim((string) ($providerConfig['profile'] ?? 'disabled')));
        if ($profile === 'disabled') {
            throw new \RuntimeException('Das Betriebsprofil des AI-Providers ist deaktiviert.');
        }

        if (($profile === 'beta' || !empty($providerConfig['beta_only'])) && empty($features['ai_beta_providers_enabled'])) {
            throw new \RuntimeException('Der gewählte AI-Provider ist nur für den explizit freigegebenen Beta-Betrieb verfügbar.');
        }

        $providerType = strtolower(trim((string) ($providerConfig['type'] ?? '')));
        if (in_array($providerType, self::EXTERNAL_PROVIDER_TYPES, true) && empty($features['ai_external_provider_data_sharing_enabled'])) {
            throw new \RuntimeException('Die Datenweitergabe an externe AI-Provider ist noch nicht explizit freigegeben.');
        }

        switch ($feature) {
            case 'translation':
                $this->assertEnabled($features, 'ai_translation_enabled', 'AI-Übersetzungen sind aktuell global deaktiviert.');
                $this->assertEnabled($features, 'ai_editorjs_enabled', 'Die Editor.js-Integration für AI ist aktuell deaktiviert.');
                $this->assertProviderCapability($providerConfig, 'translation_enabled', 'Der AI-Provider ist nicht für Übersetzungen freigegeben.');
                $this->assertProviderCapability($providerConfig, 'editorjs_enabled', 'Der AI-Provider ist nicht für Editor.js-Inhalte freigegeben.');
                return;

            case 'seo_metadata':
                $this->assertEnabled($features, 'ai_seo_meta_enabled', 'Die AI-SEO-Metadaten-Generierung ist aktuell global deaktiviert.');
                $this->assertEnabled($features, 'ai_editorjs_enabled', 'Die Editor.js-Integration für AI ist aktuell deaktiviert.');
                $this->assertProviderCapability($providerConfig, 'seo_meta_enabled', 'Der AI-Provider ist nicht für SEO-Metadaten freigegeben.');
                $this->assertProviderCapability($providerConfig, 'editorjs_enabled', 'Der AI-Provider ist nicht für Editor.js-Inhalte freigegeben.');
                return;

            case 'content_summary':
                $this->assertEnabled($features, 'ai_summary_enabled', 'AI-Zusammenfassungen sind aktuell global deaktiviert.');
                $this->assertProviderCapability($providerConfig, 'summary_enabled', 'Der AI-Provider ist nicht für Zusammenfassungen freigegeben.');
                return;

            case 'content_rewrite':
                $this->assertEnabled($features, 'ai_rewrite_enabled', 'AI-Content-Entwürfe sind aktuell global deaktiviert.');
                $this->assertProviderCapability($providerConfig, 'rewrite_enabled', 'Der AI-Provider ist nicht für Content-Entwürfe freigegeben.');
                return;

            case 'health':
                return;

            default:
                throw new \InvalidArgumentException('Der angeforderte AI-Feature-Scope ist unbekannt.');
        }
    }

    /** @param array<string, mixed> $features */
    private function assertEnabled(array $features, string $key, string $message): void
    {
        if (empty($features[$key])) {
            throw new \RuntimeException($message);
        }
    }

    /** @param array<string, mixed> $providerConfig */
    private function assertProviderCapability(array $providerConfig, string $key, string $message): void
    {
        if (empty($providerConfig[$key])) {
            throw new \RuntimeException($message);
        }
    }
}
