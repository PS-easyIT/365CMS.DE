<?php
declare(strict_types=1);

/**
 * Updates-Modul – CMS-, Plugin- und Theme-Updates
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\UpdateService;

class UpdatesModule
{
    private UpdateService $service;

    public function __construct()
    {
        $this->service = UpdateService::getInstance();
    }

    /**
     * Alle Update-Informationen laden
     */
    public function getData(): array
    {
        $core    = $this->getCoreSafe();
        $plugins = $this->getPluginsSafe();
        $theme   = $this->getThemeSafe();

        return [
            'core'     => $core,
            'plugins'  => $plugins,
            'theme'    => $theme,
            'history'  => $this->getHistorySafe(),
            'requirements' => $this->getRequirementsSafe(),
            'has_updates'  => ($core['update_available'] ?? false)
                || !empty(array_filter($plugins, fn($p) => !empty($p['new_version'])))
                || ($theme['update_available'] ?? false),
        ];
    }

    /**
     * Alle Update-Checks durchführen
     */
    public function checkAllUpdates(): array
    {
        return [
            'core'    => $this->getCoreSafe(),
            'plugins' => $this->getPluginsSafe(),
            'theme'   => $this->getThemeSafe(),
        ];
    }

    /**
     * Core-Update installieren
     */
    public function installCoreUpdate(): array
    {
        try {
            $check = $this->service->checkCoreUpdates();
            if (empty($check['update_available'])) {
                return ['success' => false, 'error' => 'Kein Core-Update verfügbar.'];
            }

            $downloadUrl = $check['download_url'] ?? '';
            $sha256      = $check['sha256'] ?? '';
            $version     = $check['latest_version'] ?? '';

            if (empty($downloadUrl)) {
                return ['success' => false, 'error' => 'Download-URL nicht verfügbar.'];
            }

            return $this->service->downloadAndInstallUpdate(
                $downloadUrl,
                $sha256,
                ABSPATH,
                'core',
                '365CMS',
                $version
            );
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Plugin-Update installieren
     */
    public function installPluginUpdate(string $slug): array
    {
        if (empty($slug)) {
            return ['success' => false, 'error' => 'Kein Plugin angegeben.'];
        }

        try {
            $plugins = $this->service->checkPluginUpdates();
            if (!isset($plugins[$slug]) || empty($plugins[$slug]['new_version'])) {
                return ['success' => false, 'error' => 'Kein Update für dieses Plugin verfügbar.'];
            }

            $plugin = $plugins[$slug];
            $downloadUrl = $plugin['download_url'] ?? '';
            $sha256      = $plugin['sha256'] ?? '';

            if (empty($downloadUrl)) {
                return ['success' => false, 'error' => 'Download-URL nicht verfügbar.'];
            }

            $pluginDir = defined('PLUGIN_PATH') ? PLUGIN_PATH : ABSPATH . 'plugins/';

            return $this->service->downloadAndInstallUpdate(
                $downloadUrl,
                $sha256,
                $pluginDir . $slug . '/',
                'plugin',
                $slug,
                $plugin['new_version']
            );
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function getCoreSafe(): array
    {
        try {
            return $this->service->checkCoreUpdates();
        } catch (\Throwable $e) {
            return [
                'current_version' => defined('CMS_VERSION') ? CMS_VERSION : '?.?.?',
                'update_available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getPluginsSafe(): array
    {
        try {
            return $this->service->checkPluginUpdates();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getThemeSafe(): array
    {
        try {
            return $this->service->checkThemeUpdates();
        } catch (\Throwable $e) {
            return ['update_available' => false, 'error' => $e->getMessage()];
        }
    }

    private function getHistorySafe(): array
    {
        try {
            return $this->service->getUpdateHistory(10);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getRequirementsSafe(): array
    {
        try {
            return $this->service->getSystemRequirements();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
