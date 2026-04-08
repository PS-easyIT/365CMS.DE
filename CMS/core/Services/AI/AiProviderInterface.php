<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

interface AiProviderInterface
{
    public function getSlug(): string;

    public function getLabel(): string;

    public function isMock(): bool;

    public function getDefaultModel(): string;

    /**
     * @param list<string> $segments
     * @param array<string, mixed> $context
     * @return list<string>
     */
    public function translateBatch(array $segments, array $context = []): array;
}