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
    private ?array $preflightData = null;

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
            'preflight' => $this->getPreflightData(),
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

            $preflightError = $this->getPreflightBlockMessage('core');
            if ($preflightError !== null) {
                return ['success' => false, 'error' => $preflightError];
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

            $preflightError = $this->getPreflightBlockMessage('plugin', $slug);
            if ($preflightError !== null) {
                return ['success' => false, 'error' => $preflightError];
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

    /**
     * Theme-Update installieren
     */
    public function installThemeUpdate(): array
    {
        try {
            $theme = $this->getThemeData();
            if (empty($theme['update_available'])) {
                return ['success' => false, 'error' => 'Kein Theme-Update verfügbar.'];
            }

            $preflightError = $this->getPreflightBlockMessage('theme');
            if ($preflightError !== null) {
                return ['success' => false, 'error' => $preflightError];
            }

            if (empty($theme['install_supported'])) {
                return ['success' => false, 'error' => 'Für dieses Theme ist nur ein manueller Update-Prozess verfügbar.'];
            }

            $downloadUrl = (string) ($theme['download_url'] ?? '');
            $sha256 = (string) ($theme['sha256'] ?? '');
            $slug = $this->normalizeThemeSlug((string) ($theme['slug'] ?? ''));
            $version = (string) ($theme['latest_version'] ?? '');

            if ($slug === '') {
                return ['success' => false, 'error' => 'Aktives Theme konnte nicht bestimmt werden.'];
            }

            if ($downloadUrl === '') {
                return ['success' => false, 'error' => 'Download-URL nicht verfügbar.'];
            }

            $themeDir = defined('THEME_PATH') ? THEME_PATH : ABSPATH . 'themes/';

            return $this->service->downloadAndInstallUpdate(
                $downloadUrl,
                $sha256,
                rtrim((string) $themeDir, '/\\') . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR,
                'theme',
                $slug,
                $version
            );
        } catch (\Throwable $e) {
            return $this->failResult(
                'updates.theme.install_failed',
                'Theme-Update konnte nicht installiert werden.',
                $e,
                ['component' => 'theme']
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

    private function getPreflightData(): array
    {
        if ($this->preflightData !== null) {
            return $this->preflightData;
        }

        $requirements = $this->getRequirementsData();
        $core = $this->getCoreData();
        $plugins = $this->getPluginData();
        $theme = $this->getThemeData();

        $globalChecks = [];

        $phpCheck = is_array($requirements['php_version'] ?? null) ? $requirements['php_version'] : [];
        if ($phpCheck !== []) {
            $globalChecks[] = [
                'label' => 'PHP-Version',
                'current' => (string) ($phpCheck['current'] ?? '—'),
                'required' => (string) ($phpCheck['required'] ?? '—'),
                'status' => !empty($phpCheck['met']) ? 'ok' : 'blocked',
                'instruction' => (string) ($phpCheck['instruction'] ?? ''),
            ];
        }

        $mysqlCheck = is_array($requirements['mysql_version'] ?? null) ? $requirements['mysql_version'] : [];
        if ($mysqlCheck !== []) {
            $globalChecks[] = [
                'label' => 'MySQL / MariaDB',
                'current' => (string) ($mysqlCheck['current'] ?? '—'),
                'required' => (string) ($mysqlCheck['required'] ?? '—'),
                'status' => array_key_exists('met', $mysqlCheck) && $mysqlCheck['met'] === false ? 'blocked' : 'ok',
                'instruction' => (string) ($mysqlCheck['instruction'] ?? ''),
            ];
        }

        $extensionChecks = (array) ($requirements['extension_checks'] ?? []);
        foreach ($extensionChecks as $extensionKey => $extension) {
            if (!is_array($extension)) {
                continue;
            }

            $required = !empty($extension['required']);
            $loaded = !empty($extension['loaded']);
            $globalChecks[] = [
                'label' => 'PHP-Erweiterung: ' . (string) ($extension['label'] ?? $extensionKey),
                'current' => $loaded ? 'Aktiv' : 'Fehlt',
                'required' => $required ? 'Erforderlich' : 'Empfohlen',
                'status' => $loaded ? 'ok' : ($required ? 'blocked' : 'warning'),
                'instruction' => (string) ($extension['instruction'] ?? ''),
            ];
        }

        $diskCheck = is_array($requirements['disk_space'] ?? null) ? $requirements['disk_space'] : [];
        if ($diskCheck !== []) {
            $freeBytes = $this->formatBytesLabel($diskCheck['free_bytes'] ?? null);
            $requiredBytes = $this->formatBytesLabel($diskCheck['required_free_bytes'] ?? null);
            $status = 'warning';
            if (($diskCheck['met'] ?? null) === true && empty($diskCheck['warning'])) {
                $status = 'ok';
            } elseif (($diskCheck['met'] ?? null) === false) {
                $status = 'blocked';
            }

            $globalChecks[] = [
                'label' => 'Freier Speicher',
                'current' => $freeBytes,
                'required' => 'mind. ' . $requiredBytes,
                'status' => $status,
                'instruction' => (string) ($diskCheck['instruction'] ?? ''),
                'path' => (string) ($diskCheck['path'] ?? ''),
            ];
        }

        $runtimePaths = (array) (($requirements['permissions']['paths']['runtime'] ?? []));
        foreach ($runtimePaths as $pathCheck) {
            if (!is_array($pathCheck)) {
                continue;
            }

            $exists = !empty($pathCheck['exists']);
            $writable = !empty($pathCheck['writable']);
            $globalChecks[] = [
                'label' => (string) ($pathCheck['label'] ?? 'Verzeichnis'),
                'current' => $writable ? 'Beschreibbar' : ($exists ? 'Nicht beschreibbar' : 'Fehlt'),
                'required' => 'Beschreibbar',
                'status' => !empty($pathCheck['met']) ? 'ok' : 'blocked',
                'instruction' => (string) ($pathCheck['instruction'] ?? ''),
                'path' => (string) ($pathCheck['path'] ?? ''),
            ];
        }

        $globalBlockingMessages = [];
        foreach ($globalChecks as $check) {
            if (($check['status'] ?? 'warning') === 'blocked') {
                $globalBlockingMessages[] = (string) ($check['label'] ?? 'Prüfung') . ': ' . (string) ($check['instruction'] ?? 'Bitte prüfen.');
            }
        }
        $globalBlockingMessages = array_values(array_unique($globalBlockingMessages));

        $pluginPreflight = [];
        foreach ($plugins as $slug => $plugin) {
            $pluginPreflight[$slug] = $this->buildComponentPreflight('plugin', (array) $plugin, $requirements, $globalBlockingMessages);
        }

        return $this->preflightData = [
            'global' => [
                'ready' => $globalBlockingMessages === [],
                'checks' => $globalChecks,
                'blocking_messages' => $globalBlockingMessages,
            ],
            'core' => $this->buildComponentPreflight('core', $core, $requirements, $globalBlockingMessages),
            'theme' => $this->buildComponentPreflight('theme', $theme, $requirements, $globalBlockingMessages),
            'plugins' => $pluginPreflight,
        ];
    }

    private function buildComponentPreflight(string $component, array $payload, array $requirements, array $globalBlockingMessages): array
    {
        $checks = [];
        $blockingMessages = $globalBlockingMessages;

        foreach ($this->getTargetChecksForComponent($component, $requirements) as $targetCheck) {
            if (!is_array($targetCheck)) {
                continue;
            }

            $exists = !empty($targetCheck['exists']);
            $writable = !empty($targetCheck['writable']);
            $status = !empty($targetCheck['met']) ? 'ok' : 'blocked';

            $checks[] = [
                'label' => (string) ($targetCheck['label'] ?? 'Zielpfad'),
                'current' => $writable ? 'Beschreibbar' : ($exists ? 'Nicht beschreibbar' : 'Fehlt'),
                'required' => 'Beschreibbar',
                'status' => $status,
                'instruction' => (string) ($targetCheck['instruction'] ?? ''),
            ];

            if ($status === 'blocked') {
                $blockingMessages[] = (string) ($targetCheck['label'] ?? 'Zielpfad') . ': ' . (string) ($targetCheck['instruction'] ?? 'Bitte prüfen.');
            }
        }

        $requiresPhp = trim((string) ($payload['requires_php'] ?? ''));
        if ($requiresPhp !== '') {
            $phpMet = version_compare(PHP_VERSION, $requiresPhp, '>=');
            $checks[] = [
                'label' => 'Paketanforderung: PHP',
                'current' => PHP_VERSION,
                'required' => $requiresPhp,
                'status' => $phpMet ? 'ok' : 'blocked',
                'instruction' => 'PHP auf mindestens ' . $requiresPhp . ' aktualisieren, bevor dieses Update installiert wird.',
            ];

            if (!$phpMet) {
                $blockingMessages[] = 'Paketanforderung PHP: mindestens ' . $requiresPhp . ' erforderlich.';
            }
        }

        $requiresCms = trim((string) ($payload['requires_cms'] ?? ''));
        if ($requiresCms !== '') {
            $currentCmsVersion = defined('CMS_VERSION') ? (string) CMS_VERSION : (string) \CMS\Version::CURRENT;
            $cmsMet = version_compare($currentCmsVersion, $requiresCms, '>=');
            $checks[] = [
                'label' => 'Paketanforderung: 365CMS',
                'current' => $currentCmsVersion,
                'required' => $requiresCms,
                'status' => $cmsMet ? 'ok' : 'blocked',
                'instruction' => 'Zuerst den Core auf mindestens ' . $requiresCms . ' anheben oder ein passendes Paket verwenden.',
            ];

            if (!$cmsMet) {
                $blockingMessages[] = 'Paketanforderung 365CMS: mindestens ' . $requiresCms . ' erforderlich.';
            }
        }

        $blockingMessages = array_values(array_unique($blockingMessages));

        return [
            'ready' => $blockingMessages === [],
            'checks' => $checks,
            'blocking_messages' => $blockingMessages,
        ];
    }

    private function getTargetChecksForComponent(string $component, array $requirements): array
    {
        $targetChecks = is_array($requirements['permissions']['paths']['targets'] ?? null)
            ? $requirements['permissions']['paths']['targets']
            : [];

        return match ($component) {
            'core' => array_values(array_filter([
                $targetChecks['core_root'] ?? null,
                $targetChecks['core_parent'] ?? null,
            ], 'is_array')),
            'theme' => array_values(array_filter([
                $targetChecks['themes_root'] ?? null,
            ], 'is_array')),
            'plugin' => array_values(array_filter([
                $targetChecks['plugins_root'] ?? null,
            ], 'is_array')),
            default => [],
        };
    }

    private function getPreflightBlockMessage(string $component, ?string $slug = null): ?string
    {
        $preflight = $this->getPreflightData();

        $entry = match ($component) {
            'core' => is_array($preflight['core'] ?? null) ? $preflight['core'] : [],
            'theme' => is_array($preflight['theme'] ?? null) ? $preflight['theme'] : [],
            'plugin' => ($slug !== null && is_array($preflight['plugins'][$slug] ?? null)) ? $preflight['plugins'][$slug] : [],
            default => [],
        };

        if (!empty($entry['ready'])) {
            return null;
        }

        $messages = array_values(array_filter((array) ($entry['blocking_messages'] ?? []), static fn ($message): bool => is_string($message) && trim($message) !== ''));

        if ($messages === []) {
            return 'Die Update-Vorabprüfung blockiert diese Installation. Bitte die Systemhinweise prüfen.';
        }

        return 'Die Update-Vorabprüfung blockiert diese Installation: ' . implode(' | ', $messages);
    }

    private function formatBytesLabel(mixed $bytes): string
    {
        if (!is_numeric($bytes) || (float) $bytes < 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            ++$unitIndex;
        }

        return number_format($value, $unitIndex === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unitIndex];
    }

    private function refreshUpdateSnapshot(): void
    {
        $this->coreData = $this->getCoreSafe();
        $this->pluginData = $this->getPluginsSafe();
        $this->themeData = $this->getThemeSafe();
        $this->preflightData = null;
    }

    private function normalizePluginSlug(string $slug): string
    {
        return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($slug)));
    }

    private function normalizeThemeSlug(string $slug): string
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
