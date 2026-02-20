<?php
/**
 * Cache Manager
 * 
 * Handles File-based caching and LiteSpeed integration
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class CacheManager
{
    private static ?self $instance = null;
    private string $cacheDir;
    private bool $useLiteSpeed;
    
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->cacheDir = ABSPATH . 'cache/';
        $this->useLiteSpeed = isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed') !== false;
        
        if (!file_exists($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get item from cache
     */
    public function get(string $key): mixed
    {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        // Check expiration
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set item in cache
     */
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($file, serialize($data));
    }

    /**
     * Delete item from cache
     */
    public function delete(string $key): void
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Flush entire cache
     */
    public function flush(): void
    {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // LiteSpeed Purge
        if ($this->useLiteSpeed) {
            header('X-LiteSpeed-Purge: *');
        }
    }
    
    /**
     * Clear ALL caches (OPcache, APCu, File-Cache, Realpath, etc.)
     * 
     * @return array Status-Report mit Details zu jedem Cache-Typ
     */
    public function clearAll(): array
    {
        $report = [
            'file_cache' => false,
            'opcache' => false,
            'apcu' => false,
            'realpath_cache' => false,
            'stat_cache' => false,
            'litespeed' => false,
            'details' => []
        ];
        
        // 1. File-based Cache
        try {
            $files = glob($this->cacheDir . '*');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            $report['file_cache'] = true;
            $report['details']['file_cache'] = "$count Dateien gelöscht";
        } catch (\Exception $e) {
            $report['details']['file_cache'] = 'Fehler: ' . $e->getMessage();
        }
        
        // 2. OPcache (Bytecode-Cache)
        if (function_exists('opcache_reset')) {
            try {
                opcache_reset();
                $report['opcache'] = true;
                $report['details']['opcache'] = 'Erfolgreich geleert';
            } catch (\Exception $e) {
                $report['details']['opcache'] = 'Fehler: ' . $e->getMessage();
            }
        } else {
            $report['details']['opcache'] = 'Nicht installiert oder deaktiviert';
        }
        
        // 3. APCu (User-Cache)
        if (function_exists('apcu_clear_cache')) {
            try {
                apcu_clear_cache();
                $report['apcu'] = true;
                $report['details']['apcu'] = 'Erfolgreich geleert';
            } catch (\Exception $e) {
                $report['details']['apcu'] = 'Fehler: ' . $e->getMessage();
            }
        } else {
            $report['details']['apcu'] = 'Nicht installiert oder deaktiviert';
        }
        
        // 4. Realpath Cache (PHP Pfad-Cache)
        if (function_exists('clearstatcache')) {
            clearstatcache(true); // true = auch Realpath-Cache leeren
            $report['realpath_cache'] = true;
            $report['details']['realpath_cache'] = 'Erfolgreich geleert';
        }
        
        // 5. Stat Cache (Datei-Status-Cache)
        clearstatcache();
        $report['stat_cache'] = true;
        $report['details']['stat_cache'] = 'Erfolgreich geleert';
        
        // 6. LiteSpeed Cache
        if ($this->useLiteSpeed) {
            header('X-LiteSpeed-Purge: *');
            $report['litespeed'] = true;
            $report['details']['litespeed'] = 'Purge-Header gesendet';
        } else {
            $report['details']['litespeed'] = 'Nicht verfügbar (kein LiteSpeed)';
        }
        
        return $report;
    }
    
    /**
     * Cache-Status abrufen
     * 
     * @return array Informationen über alle Cache-Typen
     */
    public function getStatus(): array
    {
        $status = [
            'file_cache' => [
                'enabled' => true,
                'directory' => $this->cacheDir,
                'writable' => is_writable($this->cacheDir),
                'files' => count(glob($this->cacheDir . '*.cache') ?: []),
                'size' => $this->getCacheDirSize()
            ],
            'opcache' => [
                'enabled' => function_exists('opcache_get_status') && opcache_get_status(false) !== false,
                'status' => function_exists('opcache_get_status') ? opcache_get_status(false) : null
            ],
            'apcu' => [
                'enabled' => function_exists('apcu_cache_info') && apcu_enabled(),
                'info' => function_exists('apcu_cache_info') ? @apcu_cache_info(true) : null
            ],
            'litespeed' => [
                'enabled' => $this->useLiteSpeed,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]
        ];
        
        return $status;
    }
    
    /**
     * Berechne Größe des Cache-Verzeichnisses
     */
    private function getCacheDirSize(): string
    {
        $bytes = 0;
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $bytes += filesize($file);
            }
        }
        
        // Formatieren (B, KB, MB, GB)
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Set LiteSpeed Cache Control Header
     */
    public function setCacheHeaders(int $ttl = 300, bool $private = false): void
    {
        if ($this->useLiteSpeed) {
            $type = $private ? 'private' : 'public';
            header("X-LiteSpeed-Cache-Control: {$type}, max-age={$ttl}");
        } else {
            // Fallback for standard browsers/proxies
            $ts = gmdate("D, d M Y H:i:s", time() + $ttl) . " GMT";
            header("Expires: $ts");
            header("Pragma: cache");
            header("Cache-Control: max-age=$ttl");
        }
    }

    private function getCacheFile(string $key): string
    {
        return $this->cacheDir . md5($key) . '.cache';
    }
}
