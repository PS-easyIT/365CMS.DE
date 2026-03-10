<?php
declare(strict_types=1);

/**
 * PluginsModule – Installierte Plugins verwalten
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;

class PluginsModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $pluginsDir = defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
        $plugins    = [];
        $active     = 0;
        $inactive   = 0;

        if (is_dir($pluginsDir)) {
            foreach (new \DirectoryIterator($pluginsDir) as $item) {
                if ($item->isDot() || !$item->isDir()) continue;
                $slug    = $item->getFilename();
                $mainFile = $pluginsDir . $slug . '/' . $slug . '.php';
                $info    = $this->parsePluginHeader($mainFile);

                if (empty($info['name'])) {
                    $info['name'] = $slug;
                }
                $info['slug']   = $slug;
                $info['path']   = $item->getPathname();
                $info['active'] = $this->isActive($slug);

                // update.json lesen
                $updateFile = $pluginsDir . $slug . '/update.json';
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
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];

        // FIX: Vor Aktivierung immer die erwartete Hauptdatei prüfen.
        $pluginsDir = defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
        $mainFile = $pluginsDir . $slug . '/' . $slug . '.php';
        if (!is_file($mainFile)) {
            return ['success' => false, 'error' => 'Plugin-Hauptdatei wurde nicht gefunden.'];
        }

        if (class_exists('\CMS\PluginManager')) {
            try {
                $result = \CMS\PluginManager::instance()->activatePlugin($slug);
                if ($result !== true) {
                    return ['success' => false, 'error' => is_string($result) ? $result : 'Aktivierung fehlgeschlagen.'];
                }
                // ADDED: Plugin-Aktivierungen im Audit-Log dokumentieren.
                AuditLogger::instance()->pluginAction('activate', $slug);
                return ['success' => true, 'message' => "Plugin \"{$slug}\" aktiviert."];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // Fallback: settings table
        $row   = $this->db->get_row("SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'active_plugins'");
        $list  = $row ? \CMS\Json::decodeArray($row->option_value ?? null, []) : [];
        if (!is_array($list)) $list = [];
        if (!in_array($slug, $list, true)) {
            $list[] = $slug;
        }
        $val = json_encode($list);
        if ($row) {
            $this->db->update('settings', ['option_value' => $val], ['option_name' => 'active_plugins']);
        } else {
            $this->db->insert('settings', ['option_name' => 'active_plugins', 'option_value' => $val]);
        }
        AuditLogger::instance()->pluginAction('activate', $slug);
        return ['success' => true, 'message' => "Plugin \"{$slug}\" aktiviert."];
    }

    public function deactivatePlugin(string $slug): array
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];

        if (class_exists('\CMS\PluginManager')) {
            try {
                $result = \CMS\PluginManager::instance()->deactivatePlugin($slug);
                if ($result !== true) {
                    return ['success' => false, 'error' => is_string($result) ? $result : 'Deaktivierung fehlgeschlagen.'];
                }
                AuditLogger::instance()->pluginAction('deactivate', $slug);
                return ['success' => true, 'message' => "Plugin \"{$slug}\" deaktiviert."];
            } catch (\Throwable $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $row   = $this->db->get_row("SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'active_plugins'");
        $list  = $row ? \CMS\Json::decodeArray($row->option_value ?? null, []) : [];
        if (!is_array($list)) $list = [];
        $list = array_values(array_filter($list, fn($s) => $s !== $slug));
        $val  = json_encode($list);
        if ($row) {
            $this->db->update('settings', ['option_value' => $val], ['option_name' => 'active_plugins']);
        } else {
            $this->db->insert('settings', ['option_name' => 'active_plugins', 'option_value' => $val]);
        }
        AuditLogger::instance()->pluginAction('deactivate', $slug);
        return ['success' => true, 'message' => "Plugin \"{$slug}\" deaktiviert."];
    }

    public function deletePlugin(string $slug): array
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($slug));
        if ($slug === '') return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        if ($this->isActive($slug)) {
            return ['success' => false, 'error' => 'Plugin muss zuerst deaktiviert werden.'];
        }

        $pluginsDir = defined('PLUGINS_PATH') ? PLUGINS_PATH : (defined('ABSPATH') ? ABSPATH . 'plugins/' : '');
        $pluginPath = $pluginsDir . $slug;
        if (!is_dir($pluginPath)) {
            return ['success' => false, 'error' => 'Plugin-Verzeichnis nicht gefunden.'];
        }

        // Path-Traversal-Schutz
        $realPluginsDir = realpath($pluginsDir);
        $realPluginPath = realpath($pluginPath);
        if ($realPluginsDir === false || $realPluginPath === false || !str_starts_with($realPluginPath, $realPluginsDir)) {
            return ['success' => false, 'error' => 'Ungültiger Plugin-Pfad.'];
        }

        try {
            $this->deleteDir($realPluginPath);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Plugin konnte nicht gelöscht werden: ' . $e->getMessage()];
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
        if (class_exists('\CMS\PluginManager')) {
            return \CMS\PluginManager::instance()->isPluginActive($slug);
        }
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'active_plugins'"
        );
        $list = $row ? \CMS\Json::decodeArray($row->option_value ?? null, []) : [];
        return is_array($list) && in_array($slug, $list, true);
    }

    private function parsePluginHeader(string $file): array
    {
        $info = ['name' => '', 'description' => '', 'version' => '-', 'author' => ''];
        if (!file_exists($file)) return $info;

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
}
