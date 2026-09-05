<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

final class AiExecutionService
{
    public function __construct(
        private readonly AiProviderFactory $providerFactory,
        private readonly AiQuotaService $quotaService,
        private readonly AiProviderPolicyService $policyService
    ) {
    }

    /**
     * Runs a logical AI feature request with policy checks, atomic quota reservation,
     * bounded retry and an optional, equally policy-checked fallback provider.
     *
     * @param array<string, mixed> $configuration
     * @param callable(AiProviderInterface): mixed $operation
     * @return array{result:mixed,provider:AiProviderInterface,provider_config:array<string,mixed>,resolved_via:string,attempts:int,used_fallback:bool}
     */
    public function execute(array $configuration, string $feature, int $userId, int $characterCount, callable $operation, string $targetLocale = ''): array
    {
        $providers = is_array($configuration['providers'] ?? null) ? $configuration['providers'] : [];
        $features = is_array($configuration['features'] ?? null) ? $configuration['features'] : [];
        $quotas = is_array($configuration['quotas'] ?? null) ? $configuration['quotas'] : [];
        $primaryConfig = $this->resolveProviderConfig($providers, (string) ($providers['active_provider_id'] ?? ''));

        try {
            return $this->executeWithProvider(
                $primaryConfig,
                $features,
                $quotas,
                $feature,
                $userId,
                $characterCount,
                $operation,
                'global',
                $targetLocale,
                true
            );
        } catch (\Throwable $primaryError) {
            if (!$this->isTransientFailure($primaryError)) {
                throw $primaryError;
            }

            $fallbackId = $this->normalizeProviderId((string) ($providers['fallback_provider_id'] ?? ''));
            $primaryId = $this->normalizeProviderId((string) ($primaryConfig['id'] ?? ''));
            if ($fallbackId === '' || $fallbackId === $primaryId) {
                throw $primaryError;
            }

            $fallbackConfig = $this->resolveProviderConfig($providers, $fallbackId);
            try {
                return $this->executeWithProvider(
                    $fallbackConfig,
                    $features,
                    $quotas,
                    $feature,
                    $userId,
                    $characterCount,
                    $operation,
                    'fallback',
                    $targetLocale,
                    false
                );
            } catch (\Throwable $fallbackError) {
                throw new \RuntimeException('Primärer und Fallback-AI-Provider konnten die Anfrage nicht verarbeiten.', 0, $fallbackError);
            }
        }
    }

    /**
     * Explicit admin health probe. It does not use content and does not consume feature quotas.
     *
     * @param array<string, mixed> $configuration
     * @return array{provider:AiProviderInterface,provider_config:array<string,mixed>,attempts:int}
     */
    public function checkHealth(array $configuration, string $providerId = ''): array
    {
        $providers = is_array($configuration['providers'] ?? null) ? $configuration['providers'] : [];
        $features = is_array($configuration['features'] ?? null) ? $configuration['features'] : [];
        $quotas = is_array($configuration['quotas'] ?? null) ? $configuration['quotas'] : [];
        $providerConfig = $this->resolveProviderConfig($providers, $providerId !== '' ? $providerId : (string) ($providers['active_provider_id'] ?? ''));
        $this->policyService->assertFeatureAllowed($features, $providerConfig, 'health');
        $this->providerFactory->assertReady($providerConfig);
        $provider = $this->providerFactory->create($providerConfig, $quotas);

        if ($provider->isMock()) {
            return ['provider' => $provider, 'provider_config' => $providerConfig, 'attempts' => 1];
        }

        $quotaAwareProvider = new QuotaAwareAiProvider($provider, $this->quotaService, $quotas);

        $attempts = 0;
        $retryCount = min(2, max(0, (int) ($quotas['retry_count'] ?? 0)));
        do {
            ++$attempts;
            try {
                $response = $quotaAwareProvider->complete([
                    ['role' => 'system', 'content' => 'You are a service health probe. Return exactly OK.'],
                    ['role' => 'user', 'content' => 'health-check'],
                ], ['temperature' => 0]);
                if (trim($response) === '') {
                    throw new \RuntimeException('Der Provider lieferte keine Healthcheck-Antwort.');
                }

                return ['provider' => $provider, 'provider_config' => $providerConfig, 'attempts' => $attempts];
            } catch (\Throwable $e) {
                if ($attempts > $retryCount || !$this->isTransientFailure($e)) {
                    throw $e;
                }
            }
        } while (true);
    }

    /**
     * @param array<string, mixed> $providerConfig
     * @param array<string, mixed> $features
     * @param array<string, mixed> $quotas
     * @param callable(AiProviderInterface): mixed $operation
     * @return array{result:mixed,provider:AiProviderInterface,provider_config:array<string,mixed>,resolved_via:string,attempts:int,used_fallback:bool}
     */
    private function executeWithProvider(
        array $providerConfig,
        array $features,
        array $quotas,
        string $feature,
        int $userId,
        int $characterCount,
        callable $operation,
        string $resolvedVia,
        string $targetLocale,
        bool $countUserQuota
    ): array {
        $this->policyService->assertFeatureAllowed($features, $providerConfig, $feature);
        $this->providerFactory->assertReady($providerConfig, $targetLocale);
        $provider = $this->providerFactory->create($providerConfig, $quotas);
        if ($countUserQuota) {
            $this->quotaService->reserveUserOperation($userId, $characterCount, $quotas);
        }
        $provider = new QuotaAwareAiProvider($provider, $this->quotaService, $quotas);

        $attempts = 0;
        $retryCount = min(2, max(0, (int) ($quotas['retry_count'] ?? 0)));
        do {
            ++$attempts;
            try {
                return [
                    'result' => $operation($provider),
                    'provider' => $provider,
                    'provider_config' => $providerConfig,
                    'resolved_via' => $resolvedVia,
                    'attempts' => $attempts,
                    'used_fallback' => $resolvedVia === 'fallback',
                ];
            } catch (\Throwable $e) {
                if ($attempts > $retryCount || !$this->isTransientFailure($e)) {
                    throw $e;
                }
            }
        } while (true);
    }

    /** @param array<string, mixed> $providers */
    private function resolveProviderConfig(array $providers, string $providerId): array
    {
        $providerId = $this->normalizeProviderId($providerId);
        foreach ((array) ($providers['entries'] ?? []) as $provider) {
            if (is_array($provider) && $this->normalizeProviderId((string) ($provider['id'] ?? '')) === $providerId) {
                return $provider;
            }
        }

        throw new \RuntimeException('Der konfigurierte AI-Provider wurde nicht gefunden.');
    }

    private function isTransientFailure(\Throwable $error): bool
    {
        $message = strtolower($error->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'curl error 28')
            || str_contains($message, 'status 429')
            || preg_match('/status 5\d\d\b/', $message) === 1
            || str_contains($message, 'temporarily unavailable')
            || str_contains($message, 'connection reset');
    }

    private function normalizeProviderId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';

        return trim($value, '-');
    }
}
