<?php
declare(strict_types=1);

namespace CMS\Services\AI;

use CMS\Http\Client;
use CMS\Services\AI\Providers\AzureOpenAiProvider;
use CMS\Services\AI\Providers\MockAiProvider;
use CMS\Services\AI\Providers\OllamaAiProvider;
use CMS\Services\AI\Providers\OpenAiCompatibleProvider;

if (!defined('ABSPATH')) {
    exit;
}

final class AiProviderFactory
{
    private static ?self $instance = null;

    private AiSettingsService $settings;
    private Client $httpClient;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = AiSettingsService::getInstance();
        $this->httpClient = Client::getInstance();
    }

    /**
     * @param array<string, mixed> $providerConfig
     * @param array<string, mixed> $quotaConfig
     */
    public function create(array $providerConfig, array $quotaConfig = []): AiProviderInterface
    {
        $providerType = strtolower(trim((string) ($providerConfig['type'] ?? 'mock')));
        $providerId = $this->sanitizeProviderId((string) ($providerConfig['id'] ?? $providerType));
        $label = trim((string) ($providerConfig['label'] ?? ($providerConfig['type_label'] ?? ucfirst(str_replace('_', ' ', $providerType)))));
        $defaultModel = trim((string) ($providerConfig['default_model'] ?? ''));
        $timeoutSeconds = max(5, (int) ($quotaConfig['timeout_seconds'] ?? 25));

        return match ($providerType) {
            'mock' => new MockAiProvider($providerId !== '' ? $providerId : 'mock', $label !== '' ? $label : 'Mock Provider', $defaultModel !== '' ? $defaultModel : 'mock-local-v1'),
            'ollama' => new OllamaAiProvider(
                $providerId !== '' ? $providerId : 'ollama',
                $label !== '' ? $label : 'Ollama',
                $defaultModel !== '' ? $defaultModel : 'llama3.1:8b',
                (string) ($providerConfig['endpoint'] ?? 'http://127.0.0.1:11434'),
                $this->httpClient,
                $timeoutSeconds,
                (array) ($providerConfig['allowed_internal_hosts'] ?? [])
            ),
            'azure_openai' => new AzureOpenAiProvider(
                $providerId !== '' ? $providerId : 'azure_openai',
                $label !== '' ? $label : 'Azure AI',
                $defaultModel !== '' ? $defaultModel : 'gpt-4.1-mini',
                (string) ($providerConfig['endpoint'] ?? ''),
                (string) ($providerConfig['deployment'] ?? ''),
                (string) ($providerConfig['api_version'] ?? '2024-10-21'),
                $this->settings->getProviderSecret($providerId, $providerType),
                $this->httpClient,
                $timeoutSeconds
            ),
            'openai', 'mistral', 'openrouter' => new OpenAiCompatibleProvider(
                $providerId !== '' ? $providerId : $providerType,
                $label !== '' ? $label : ucfirst(str_replace('_', ' ', $providerType)),
                $defaultModel !== '' ? $defaultModel : 'gpt-4.1-mini',
                (string) ($providerConfig['endpoint'] ?? ''),
                $this->settings->getProviderSecret($providerId, $providerType),
                $this->httpClient,
                $timeoutSeconds
            ),
            default => throw new \InvalidArgumentException('Für den gewählten AI-Provider ist noch kein Adapter registriert.'),
        };
    }

    /** @param array<string, mixed> $providerConfig */
    public function assertReady(array $providerConfig, string $targetLocale = ''): void
    {
        $providerType = strtolower(trim((string) ($providerConfig['type'] ?? '')));
        $providerId = $this->sanitizeProviderId((string) ($providerConfig['id'] ?? ''));

        if ($providerId === '') {
            throw new \RuntimeException('Es fehlt eine interne Provider-ID.');
        }

        if (!AiSettingsService::isKnownProviderType($providerType)) {
            throw new \RuntimeException('Der Providertyp ist unbekannt.');
        }

        if (empty($providerConfig['enabled'])) {
            throw new \RuntimeException('Der globale AI-Provider ist deaktiviert.');
        }

        if ($targetLocale !== '') {
            $allowedLocales = array_values(array_unique(array_filter(array_map(
                fn (string $locale): string => $this->normalizeLocale($locale),
                (array) ($providerConfig['allowed_locales'] ?? [])
            ))));
            if ($allowedLocales !== [] && !in_array($this->normalizeLocale($targetLocale), $allowedLocales, true)) {
                throw new \RuntimeException('Die Zielsprache ist für den globalen AI-Provider nicht freigegeben.');
            }
        }

        $definition = AiSettingsService::getProviderTypeDefinition($providerType);
        if (!empty($definition['requires_secret']) && !$this->settings->hasProviderSecret($providerId, $providerType)) {
            throw new \RuntimeException('Es ist kein Secret/API-Key für den globalen AI-Provider hinterlegt.');
        }

        if (in_array($providerType, ['openai', 'mistral', 'openrouter'], true)
            && trim((string) ($providerConfig['endpoint'] ?? '')) === ''
        ) {
            throw new \RuntimeException('Der Provider-Endpoint fehlt.');
        }

        if (in_array($providerType, ['openai', 'mistral', 'openrouter', 'azure_openai'], true)) {
            $this->assertHttpsEndpoint((string) ($providerConfig['endpoint'] ?? ''));
        }

        if ($providerType === 'ollama') {
            if (trim((string) ($providerConfig['endpoint'] ?? '')) === '') {
                throw new \RuntimeException('Der Ollama-Endpoint fehlt.');
            }
            if (trim((string) ($providerConfig['default_model'] ?? '')) === '') {
                throw new \RuntimeException('Das Ollama-Modell fehlt.');
            }
            $this->assertAllowedOllamaEndpoint($providerConfig);
        }

        if ($providerType === 'azure_openai') {
            if (trim((string) ($providerConfig['endpoint'] ?? '')) === '') {
                throw new \RuntimeException('Der Azure-Endpoint fehlt.');
            }
            if (trim((string) ($providerConfig['deployment'] ?? '')) === '') {
                throw new \RuntimeException('Der Azure-Deployment-Name fehlt.');
            }
            if (trim((string) ($providerConfig['api_version'] ?? '')) === '') {
                throw new \RuntimeException('Die Azure-API-Version fehlt.');
            }
        }
    }

    private function sanitizeProviderId(string $providerId): string
    {
        $providerId = strtolower(trim($providerId));
        $providerId = preg_replace('/[^a-z0-9._-]+/', '-', $providerId) ?? '';
        $providerId = preg_replace('/-+/', '-', $providerId) ?? '';

        return trim($providerId, '-');
    }

    private function normalizeLocale(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return $value;
    }

    private function assertHttpsEndpoint(string $endpoint): void
    {
        $scheme = strtolower((string) parse_url(trim($endpoint), PHP_URL_SCHEME));
        if ($scheme !== 'https') {
            throw new \RuntimeException('Cloud-AI-Provider dürfen nur über einen HTTPS-Endpoint angesprochen werden.');
        }
    }

    /** @param array<string, mixed> $providerConfig */
    private function assertAllowedOllamaEndpoint(array $providerConfig): void
    {
        $endpoint = trim((string) ($providerConfig['endpoint'] ?? ''));
        $host = strtolower((string) parse_url($endpoint, PHP_URL_HOST));
        $allowedHosts = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => strtolower(trim((string) $value)),
            (array) ($providerConfig['allowed_internal_hosts'] ?? [])
        ), static fn (string $value): bool => $value !== '')));

        if ($host === '' || $allowedHosts === [] || !in_array($host, $allowedHosts, true)) {
            throw new \RuntimeException('Der Ollama-Endpoint ist nicht in der expliziten internen Host-Allowlist freigegeben.');
        }
    }
}
