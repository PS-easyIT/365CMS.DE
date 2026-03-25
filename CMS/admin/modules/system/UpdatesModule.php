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

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\UpdateService;

class UpdatesModule
{
    private UpdateService $service;
    private ?array $coreData = null;
    private ?array $pluginData = null;
    private ?array $themeData = null;
    private ?array $historyData = null;
    private ?array $requirementsData = null;

    public function __construct()
    {
        $this->service = UpdateService::getInstance();
    }

    /**
     * Alle Update-Informationen laden
     */
    public function getData(): array
    {
        $core    = $this->getCoreData();
        $plugins = $this->getPluginData();
        $theme   = $this->getThemeData();

        return [
            'core'     => $core,
            'plugins'  => $plugins,
            'theme'    => $theme,
            'history'  => $this->getHistoryData(),
            'requirements' => $this->getRequirementsData(),
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
        $this->refreshUpdateSnapshot();

        return $this->exportUpdateSnapshot();
    }

    public function hydrateUpdateSnapshot(array $snapshot): void
    {
        if (isset($snapshot['core']) && is_array($snapshot['core'])) {
            $this->coreData = $snapshot['core'];
        }

        if (isset($snapshot['plugins']) && is_array($snapshot['plugins'])) {
            $this->pluginData = $snapshot['plugins'];
        }

        if (isset($snapshot['theme']) && is_array($snapshot['theme'])) {
            $this->themeData = $snapshot['theme'];
        }
    }

    public function exportUpdateSnapshot(): array
    {
        return [
            'core' => $this->getCoreData(),
            'plugins' => $this->getPluginData(),
            'theme' => $this->getThemeData(),
        ];
    }

    /**
     * Core-Update installieren
     */
    public function installCoreUpdate(): array
    {
        try {
            $check = $this->getCoreData();
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
            return $this->failResult(
                'updates.core.install_failed',
                'Core-Update konnte nicht installiert werden.',
                $e,
                ['component' => 'core']
            );
        }
    }

    /**
     * Plugin-Update installieren
     */
    public function installPluginUpdate(string $slug): array
    {
        $slug = $this->normalizePluginSlug($slug);

        if (empty($slug)) {
            return ['success' => false, 'error' => 'Kein Plugin angegeben.'];
        }

        try {
            $plugins = $this->getPluginData();
            if (!isset($plugins[$slug]) || empty($plugins[$slug]['new_version'])) {
                return ['success' => false, 'error' => 'Kein Update für dieses Plugin verfügbar.'];
            }

            $plugin = $plugins[$slug];
            if (empty($plugin['install_supported'])) {
                return ['success' => false, 'error' => 'Für dieses Plugin ist nur ein manueller Update-Prozess verfügbar.'];
            }

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
            return $this->failResult(
                'updates.plugin.install_failed',
                'Plugin-Update konnte nicht installiert werden.',
                $e,
                ['component' => 'plugin', 'slug' => $slug]
            );
        }
    }

    private function getCoreSafe(): array
    {
        try {
            return $this->service->checkCoreUpdates();
        } catch (\Throwable $e) {
            $this->logFailure('updates.core.check_failed', 'Core-Update-Prüfung fehlgeschlagen.', $e);

            return [
                'current_version' => defined('CMS_VERSION') ? CMS_VERSION : '?.?.?',
                'update_available' => false,
                'error' => 'Core-Update-Prüfung derzeit nicht verfügbar.',
            ];
        }
    }

    private function getCoreData(): array
    {
        return $this->coreData ??= $this->getCoreSafe();
    }

    private function getPluginsSafe(): array
    {
        try {
            return $this->service->checkPluginUpdates();
        } catch (\Throwable $e) {
            $this->logFailure('updates.plugins.check_failed', 'Plugin-Update-Prüfung fehlgeschlagen.', $e);
            return [];
        }
    }

    private function getPluginData(): array
    {
        return $this->pluginData ??= $this->getPluginsSafe();
    }

    private function getThemeSafe(): array
    {
        try {
            return $this->service->checkThemeUpdates();
        } catch (\Throwable $e) {
            $this->logFailure('updates.theme.check_failed', 'Theme-Update-Prüfung fehlgeschlagen.', $e);

            return ['update_available' => false, 'error' => 'Theme-Update-Prüfung derzeit nicht verfügbar.'];
        }
    }

    private function getThemeData(): array
    {
        return $this->themeData ??= $this->getThemeSafe();
    }

    private function getHistorySafe(): array
    {
        try {
            return $this->service->getUpdateHistory(10);
        } catch (\Throwable $e) {
            $this->logFailure('updates.history.load_failed', 'Update-Historie konnte nicht geladen werden.', $e);
            return [];
        }
    }

    private function getHistoryData(): array
    {
        return $this->historyData ??= $this->getHistorySafe();
    }

    private function getRequirementsSafe(): array
    {
        try {
            return $this->service->getSystemRequirements();
        } catch (\Throwable $e) {
            $this->logFailure('updates.requirements.load_failed', 'Systemanforderungen konnten nicht geladen werden.', $e);
            return [];
        }
    }

    private function getRequirementsData(): array
    {
        return $this->requirementsData ??= $this->getRequirementsSafe();
    }

    private function refreshUpdateSnapshot(): void
    {
        $this->coreData = $this->getCoreSafe();
        $this->pluginData = $this->getPluginsSafe();
        $this->themeData = $this->getThemeSafe();
    }

    private function normalizePluginSlug(string $slug): string
    {
        return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($slug)));
    }

    private function failResult(string $action, string $message, ?\Throwable $exception = null, array $context = []): array
    {
        $this->logFailure($action, $message, $exception, $context);

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    private function logFailure(string $action, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
        }

        Logger::instance()->withChannel('admin.updates')->error($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'updates',
            null,
            $context,
            'error'
        );
    }
}
