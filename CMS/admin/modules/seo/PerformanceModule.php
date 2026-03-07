<?php
declare(strict_types=1);

/**
 * PerformanceModule – Performance-Analyse & Cache-Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class PerformanceModule
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
        $abspath = defined('ABSPATH') ? ABSPATH : '';

        // Cache-Größe
        $cacheDir  = $abspath . 'cache/';
        $cacheSize = is_dir($cacheDir) ? $this->getDirSize($cacheDir) : 0;
        $cacheFiles = is_dir($cacheDir) ? count(glob($cacheDir . '*')) : 0;

        // Session-Größe
        $sessionDir  = $abspath . 'sessions/';
        $sessionSize = is_dir($sessionDir) ? $this->getDirSize($sessionDir) : 0;
        $sessionFiles = is_dir($sessionDir) ? count(glob($sessionDir . '*')) : 0;

        // Upload-Größe
        $uploadDir  = $abspath . 'uploads/';
        $uploadSize = is_dir($uploadDir) ? $this->getDirSize($uploadDir) : 0;

        // PHP-Info
        $phpInfo = [
            'version'         => PHP_VERSION,
            'memory_limit'    => ini_get('memory_limit'),
            'max_execution'   => ini_get('max_execution_time'),
            'upload_max'      => ini_get('upload_max_filesize'),
            'post_max'        => ini_get('post_max_size'),
            'opcache_enabled' => function_exists('opcache_get_status') && @opcache_get_status() !== false,
            'gzip_enabled'    => extension_loaded('zlib'),
        ];

        // DB-Größe
        $dbSize = 0;
        try {
            $rows = $this->db->get_results(
                "SELECT table_name, data_length + index_length AS size
                 FROM information_schema.tables WHERE table_schema = DATABASE()"
            );
            foreach ($rows ?: [] as $r) {
                $dbSize += (int)$r->size;
            }
        } catch (\Exception $e) {}

        // Performance-Settings (Batch-Abfrage)
        $settingKeys = ['perf_lazy_loading', 'perf_minify_css', 'perf_minify_js', 'perf_gzip', 'perf_browser_cache', 'perf_page_cache'];
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingKeys
        ) ?: [];
        $settings = array_fill_keys($settingKeys, '0');
        foreach ($rows as $row) {
            $settings[$row->option_name] = $row->option_value;
        }

        return [
            'cache_size'    => $cacheSize,
            'cache_files'   => $cacheFiles,
            'session_size'  => $sessionSize,
            'session_files' => $sessionFiles,
            'upload_size'   => $uploadSize,
            'db_size'       => $dbSize,
            'php_info'      => $phpInfo,
            'settings'      => $settings,
        ];
    }

    public function clearCache(): array
    {
        $cacheDir = defined('ABSPATH') ? ABSPATH . 'cache/' : '';
        if (!is_dir($cacheDir)) {
            return ['success' => false, 'error' => 'Cache-Verzeichnis nicht gefunden.'];
        }
        $count = 0;
        foreach (glob($cacheDir . '*') as $file) {
            if (is_file($file) && unlink($file)) {
                $count++;
            }
        }
        return ['success' => true, 'message' => "{$count} Cache-Dateien gelöscht."];
    }

    public function clearExpiredSessions(): array
    {
        $sessionDir = defined('ABSPATH') ? ABSPATH . 'sessions/' : '';
        if (!is_dir($sessionDir)) {
            return ['success' => false, 'error' => 'Session-Verzeichnis nicht gefunden.'];
        }
        $count = 0;
        $threshold = time() - 86400; // 24h
        foreach (glob($sessionDir . '*') as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                if (unlink($file)) {
                    $count++;
                }
            }
        }
        return ['success' => true, 'message' => "{$count} abgelaufene Sessions bereinigt."];
    }

    public function reportImageOptimization(): array
    {
        $uploadDir = defined('ABSPATH') ? ABSPATH . 'uploads/' : '';
        if (!is_dir($uploadDir)) {
            return ['success' => false, 'error' => 'Upload-Verzeichnis nicht gefunden.'];
        }
        $large = 0;
        $threshold = 500 * 1024; // 500 KB
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($uploadDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                if ($file->getSize() > $threshold) {
                    $large++;
                }
            }
        }
        return ['success' => true, 'message' => "{$large} Bilder sind größer als 500 KB und könnten optimiert werden."];
    }

    public function saveSettings(array $post): array
    {
        $keys = ['perf_lazy_loading', 'perf_minify_css', 'perf_minify_js', 'perf_gzip', 'perf_browser_cache', 'perf_page_cache'];
        try {
            foreach ($keys as $key) {
                $value = isset($post[$key]) ? '1' : '0';
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            return ['success' => true, 'message' => 'Performance-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function getDirSize(string $dir): int
    {
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
