<?php
declare(strict_types=1);

/**
 * PluginsModule – Installierte Plugins verwalten
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;

class PluginsModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;
    private readonly Logger $logger;
    /** @var array<string, bool>|null */
    private ?array $activePluginsLookup = null;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->logger = Logger::instance()->withChannel('admin.plugins');
    }

    public function getData(): array
    {
        $pluginsDir = $this->getPluginsDirectory();
        $plugins    = [];
        $active     = 0;
        $inactive   = 0;
        $activePluginsLookup = $this->getActivePluginsLookup();

        if (is_dir($pluginsDir)) {
            foreach (new \DirectoryIterator($pluginsDir) as $item) {
                if ($item->isDot() || !$item->isDir()) continue;
                $directoryName = $item->getFilename();
                $slug = $this->normalizePluginSlug($directoryName);
                if ($slug === '') {
                    continue;
                }

                $mainFile = $item->getPathname() . DIRECTORY_SEPARATOR . $directoryName . '.php';
                if (!is_file($mainFile)) {
                    $mainFile = $this->getPluginMainFilePath($slug);
                }
                $info    = $this->parsePluginHeader($mainFile);

                if (empty($info['name'])) {
                    $info['name'] = $slug;
                }
                $info['slug']   = $slug;
                $info['path']   = $item->getPathname();
                $info['active'] = isset($activePluginsLookup[$slug]);
                $info['protected'] = $this->isProtectedPlugin($slug);

                // update.json lesen
                $updateFile = $item->getPathname() . DIRECTORY_SEPARATOR . 'update.json';
                if (file_exists($updateFile)) {
                    $upd = \CMS\Json::decodeArray(file_get_contents($updateFile), []);
                    $info['version']     = $upd['version'] ?? $info['version'] ?? '-';
                    $info['last_update'] = $upd['date'] ?? '';
                }

                $plugins[] = $info;
                if ($info['active']) $active++; else $inactive++;
            }
        }

        usort($plugins, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return [
            'plugins' => $plugins,
            'stats'   => [
                'total'    => count($plugins),
                'active'   => $active,
                'inactive' => $inactive,
            ],
        ];
    }

    public function activatePlugin(string $slug): array
    {
        $slug = $this->normalizePluginSlug($slug);
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];

        $mainFile = $this->getPluginMainFilePath($slug);
        if (!is_file($mainFile)) {
            return ['success' => false, 'error' => 'Plugin-Hauptdatei wurde nicht gefunden.'];
        }

        if (class_exists('\CMS\PluginManager')) {
            try {
                $result = \CMS\PluginManager::instance()->activatePlugin($slug);
                if ($result !== true) {
                    return ['success' => false, 'error' => is_string($result) ? $result : 'Aktivierung fehlgeschlagen.'];
                }
                $this->resetActivePluginsLookup();
                return ['success' => true, 'message' => "Plugin \"{$slug}\" aktiviert."];
            } catch (\Throwable $e) {
                $this->logger->error('Plugin-Aktivierung fehlgeschlagen.', [
                    'slug' => $slug,
                    'exception' => $e,
                ]);

                return ['success' => false, 'error' => 'Plugin konnte nicht aktiviert werden.'];
            }
        }

        $list = array_keys($this->getActivePluginsLookup());
        if (!isset($this->getActivePluginsLookup()[$slug])) {
            $list[] = $slug;
        }
        $this->persistActivePlugins($list);
        AuditLogger::instance()->pluginAction('activate', $slug);
        return ['success' => true, 'message' => "Plugin \"{$slug}\" aktiviert."];
    }

    public function deactivatePlugin(string $slug): array
    {
        $slug = $this->normalizePluginSlug($slug);
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];

        if (class_exists('\CMS\PluginManager')) {
            try {
                $result = \CMS\PluginManager::instance()->deactivatePlugin($slug);
                if ($result !== true) {
                    return ['success' => false, 'error' => is_string($result) ? $result : 'Deaktivierung fehlgeschlagen.'];
                }
                $this->resetActivePluginsLookup();
                return ['success' => true, 'message' => "Plugin \"{$slug}\" deaktiviert."];
            } catch (\Throwable $e) {
                $this->logger->error('Plugin-Deaktivierung fehlgeschlagen.', [
                    'slug' => $slug,
                    'exception' => $e,
                ]);

                return ['success' => false, 'error' => 'Plugin konnte nicht deaktiviert werden.'];
            }
        }

        $list = array_values(array_filter(
            array_keys($this->getActivePluginsLookup()),
            static fn(string $activeSlug): bool => $activeSlug !== $slug
        ));
        $this->persistActivePlugins($list);
        AuditLogger::instance()->pluginAction('deactivate', $slug);
        return ['success' => true, 'message' => "Plugin \"{$slug}\" deaktiviert."];
    }

    public function deletePlugin(string $slug): array
    {
        $slug = $this->normalizePluginSlug($slug);
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        if ($this->isProtectedPlugin($slug)) {
            return ['success' => false, 'error' => 'Das mitgelieferte Kern-Plugin „cms-importer“ kann nicht gelöscht werden.'];
        }
        if ($this->isActive($slug)) {
            return ['success' => false, 'error' => 'Plugin muss zuerst deaktiviert werden.'];
        }

        $realPluginPath = $this->resolvePluginDirectoryPath($slug);
        if ($realPluginPath === null || !is_dir($realPluginPath)) {
            return ['success' => false, 'error' => 'Plugin-Verzeichnis nicht gefunden.'];
        }

        try {
            if (class_exists('\CMS\PluginManager')) {
                $result = \CMS\PluginManager::instance()->deletePlugin($slug);
                if ($result !== true) {
                    return ['success' => false, 'error' => is_string($result) ? $result : 'Plugin konnte nicht gelöscht werden.'];
                }
                $this->resetActivePluginsLookup();
            } else {
                $this->deleteDir($realPluginPath);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Plugin-Löschung fehlgeschlagen.', [
                'slug' => $slug,
                'path' => $realPluginPath,
                'exception' => $e,
            ]);

            return ['success' => false, 'error' => 'Plugin konnte nicht gelöscht werden.'];
        }

        // ADDED: Löschaktionen im Audit-Log festhalten.
        AuditLogger::instance()->log(
            AuditLogger::CAT_PLUGIN,
            'plugin.delete',
            'Plugin gelöscht',
            'plugin',
            null,
            ['slug' => $slug, 'path' => $realPluginPath],
            'warning'
        );

        return ['success' => true, 'message' => "Plugin \"{$slug}\" gelöscht."];
    }

    private function isActive(string $slug): bool
    {
        return isset($this->getActivePluginsLookup()[$slug]);
    }

    /** @return array<string, bool> */
    private function getActivePluginsLookup(): array
    {
        if ($this->activePluginsLookup !== null) {
            return $this->activePluginsLookup;
        }

        $activePlugins = [];

        if (class_exists('\CMS\PluginManager')) {
            $activePlugins = \CMS\PluginManager::instance()->getActivePlugins();
        } else {
            $row = $this->db->get_row(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'active_plugins'"
            );
            $activePlugins = $row ? \CMS\Json::decodeArray($row->option_value ?? null, []) : [];
        }

        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }

        $lookup = [];
        foreach ($activePlugins as $activePlugin) {
            $normalizedSlug = $this->normalizePluginSlug((string) $activePlugin);
            if ($normalizedSlug === '') {
                continue;
            }

            $lookup[$normalizedSlug] = true;
        }

        $this->activePluginsLookup = $lookup;

        return $this->activePluginsLookup;
    }

    private function resetActivePluginsLookup(): void
    {
        $this->activePluginsLookup = null;
    }

    /** @param string[] $plugins */
    private function persistActivePlugins(array $plugins): void
    {
        $normalizedPlugins = [];
        foreach ($plugins as $plugin) {
            $normalizedSlug = $this->normalizePluginSlug((string) $plugin);
            if ($normalizedSlug === '') {
                continue;
            }

            $normalizedPlugins[] = $normalizedSlug;
        }

        $normalizedPlugins = array_values(array_unique($normalizedPlugins));
        $row = $this->db->get_row("SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'active_plugins'");
        $value = json_encode($normalizedPlugins);

        if ($row) {
            $this->db->update('settings', ['option_value' => $value], ['option_name' => 'active_plugins']);
        } else {
            $this->db->insert('settings', ['option_name' => 'active_plugins', 'option_value' => $value]);
        }

        $this->resetActivePluginsLookup();
    }

    private function parsePluginHeader(string $file): array
    {
        $info = ['name' => '', 'description' => '', 'version' => '-', 'author' => ''];
        if (!is_file($file) || !is_readable($file)) return $info;

        $content = file_get_contents($file, false, null, 0, 4096);
        if (!is_string($content) || $content === '') {
            return $info;
        }
        if (preg_match('/Plugin\s*Name:\s*(.+)/i', $content, $m)) $info['name'] = trim($m[1]);
        if (preg_match('/Description:\s*(.+)/i', $content, $m))   $info['description'] = trim($m[1]);
        if (preg_match('/Version:\s*([\d.]+)/i', $content, $m))   $info['version'] = trim($m[1]);
        if (preg_match('/Author:\s*(.+)/i', $content, $m))        $info['author'] = trim($m[1]);
        return $info;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            if ($item->isDir()) {
                if (!rmdir($item->getPathname())) {
                    throw new \RuntimeException('Ordner konnte nicht gelöscht werden: ' . $item->getPathname());
                }
            } else {
                if (!unlink($item->getPathname())) {
                    throw new \RuntimeException('Datei konnte nicht gelöscht werden: ' . $item->getPathname());
                }
            }
        }
        if (!rmdir($dir)) {
            throw new \RuntimeException('Plugin-Verzeichnis konnte nicht gelöscht werden: ' . $dir);
        }
    }

    private function isProtectedPlugin(string $slug): bool
    {
        if (!class_exists('\CMS\PluginManager')) {
            return $slug === 'cms-importer';
        }

        return \CMS\PluginManager::instance()->isProtectedPlugin($slug);
    }

    private function normalizePluginSlug(string $slug): string
    {
        return preg_replace('/[^a-z0-9_-]/', '', strtolower($slug)) ?? '';
    }

    private function getPluginsDirectory(): string
    {
        return defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
    }

    private function getPluginMainFilePath(string $slug): string
    {
        $pluginsDir = rtrim($this->getPluginsDirectory(), '/\\');

        return $pluginsDir . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . $slug . '.php';
    }

    private function resolvePluginDirectoryPath(string $slug): ?string
    {
        $pluginsDir = $this->getPluginsDirectory();
        if ($pluginsDir === '') {
            return null;
        }

        $realPluginsDir = realpath($pluginsDir);
        $realPluginPath = realpath(rtrim($pluginsDir, '/\\') . DIRECTORY_SEPARATOR . $slug);
        if ($realPluginsDir === false || $realPluginPath === false) {
            return null;
        }

        return $this->isPathWithin($realPluginPath, $realPluginsDir) ? $realPluginPath : null;
    }

    private function isPathWithin(string $path, string $basePath): bool
    {
        $normalizedPath = rtrim(str_replace('\\', '/', strtolower($path)), '/');
        $normalizedBasePath = rtrim(str_replace('\\', '/', strtolower($basePath)), '/');

        return $normalizedPath === $normalizedBasePath
            || str_starts_with($normalizedPath, $normalizedBasePath . '/');
    }
}
