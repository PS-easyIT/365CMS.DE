<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\CmsAuthPageService;

final class CmsLoginPageModule
{
    private CmsAuthPageService $service;

    public function __construct()
    {
        $this->service = CmsAuthPageService::getInstance();
    }

    public function getData(): array
    {
        $settings = $this->service->getSettings();

        return [
            'settings' => $settings,
            'page_options' => $this->service->getPageOptions(),
            'registration_enabled' => $this->service->isRegistrationEnabled($settings),
            'passkey_enabled' => $this->service->isPasskeyLoginEnabled($settings),
            'preview_urls' => [
                'login' => $this->service->getPath('login'),
                'register' => $this->service->getPath('register'),
                'forgot_password' => $this->service->getPath('forgot-password'),
            ],
        ];
    }

    public function saveSettings(array $post): array
    {
        return $this->service->saveSettings($post);
    }
}
