<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Backward-compatible facade for legacy callers.
 * New features must use AiService directly so provider routing stays global.
 */
final class AiProviderGateway
{
    private static ?self $instance = null;

    private AiService $service;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->service = AiService::getInstance();
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function translateEditorJsDraft(array $request): array
    {
        return $this->service->translateEditorJsDraft($request);
    }
}
