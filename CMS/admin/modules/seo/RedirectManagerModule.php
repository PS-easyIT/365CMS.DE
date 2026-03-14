<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class RedirectManagerModule
{
    private readonly \CMS\Services\RedirectService $service;

    public function __construct()
    {
        $this->service = \CMS\Services\RedirectService::getInstance();
    }

    public function getData(): array
    {
        return $this->service->getAdminData();
    }

    public function saveRedirect(array $post): array
    {
        return $this->service->saveRedirect($post);
    }

    public function deleteRedirect(int $id): array
    {
        return $this->service->deleteRedirect($id);
    }

    public function deleteRedirectsBySlug(string $slug): array
    {
        return $this->service->deleteRedirectsBySlug($slug);
    }

    public function toggleRedirect(int $id): array
    {
        return $this->service->toggleRedirect($id);
    }

    public function clearLogs(): array
    {
        return $this->service->clearLogs();
    }
}
