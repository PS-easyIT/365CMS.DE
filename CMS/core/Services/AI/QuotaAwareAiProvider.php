<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Counts every actual provider invocation, including batches, retries and fallback calls.
 */
final class QuotaAwareAiProvider implements AiProviderInterface
{
    /** @param array<string, mixed> $quotaConfig */
    public function __construct(
        private readonly AiProviderInterface $provider,
        private readonly AiQuotaService $quotaService,
        private readonly array $quotaConfig
    ) {
    }

    public function getSlug(): string
    {
        return $this->provider->getSlug();
    }

    public function getLabel(): string
    {
        return $this->provider->getLabel();
    }

    public function isMock(): bool
    {
        return $this->provider->isMock();
    }

    public function getDefaultModel(): string
    {
        return $this->provider->getDefaultModel();
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @param array<string, mixed> $options
     */
    public function complete(array $messages, array $options = []): string
    {
        $this->reserveProviderCall();

        return $this->provider->complete($messages, $options);
    }

    /**
     * @param list<string> $segments
     * @param array<string, mixed> $context
     * @return list<string>
     */
    public function translateBatch(array $segments, array $context = []): array
    {
        $this->reserveProviderCall();

        return $this->provider->translateBatch($segments, $context);
    }

    private function reserveProviderCall(): void
    {
        $this->quotaService->reserveProviderCall($this->provider->getSlug(), $this->quotaConfig);
    }
}
