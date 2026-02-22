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

use CMS\Contracts\CacheInterface;

if (!defined('ABSPATH')) {
    exit;
}

/** @implements CacheInterface */
class CacheManager implements CacheInterface
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
     * HMAC-Key aus Konfigurations-Konstante lesen
     */
    private function getHmacKey(): string
    {
        return defined('NONCE_KEY') ? NONCE_KEY : 'cms-cache-hmac-fallback-key';
    }

    /**
     * Get item from cache (JSON + HMAC-Verifikation – kein unserialize())
     */
    public function get(string $key): mixed
    {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return null;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        // Format: <hmac>:<base64-encoded-json>
        $sep = strpos($raw, ':');
        if ($sep === false) {
            // Altes Format (serialize) – Datei ungültig löschen
            @unlink($file);
            return null;
        }

        $storedHmac = substr($raw, 0, $sep);
        $payload    = base64_decode(substr($raw, $sep + 1), true);

        if ($payload === false) {
            @unlink($file);
            return null;
        }

        // HMAC-Integritätsprüfung (verhindert PHP Object Injection)
        $expectedHmac = hash_hmac('sha256', $payload, $this->getHmacKey());
        if (!hash_equals($expectedHmac, $storedHmac)) {
            @unlink($file);
            error_log('CacheManager: HMAC-Fehlschlag für Cache-Schlüssel ' . $key . ' – mögliche Manipulation!');
            return null;
        }

        $data = json_decode($payload, true);
        if (!is_array($data) || !array_key_exists('v', $data) || !isset($data['e'])) {
            @unlink($file);
            return null;
        }

        // Ablaufzeit prüfen
        if ($data['e'] < time()) {
            @unlink($file);
            return null;
        }

        return $data['v'];
    }

    /**
     * Set item in cache (JSON + HMAC – kein serialize())
     * Rückgabe bool für CacheInterface-Kompatibilität.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl  = $ttl ?? 3600;
        $file = $this->getCacheFile($key);

        try {
            $payload = json_encode(['v' => $value, 'e' => time() + $ttl], JSON_THROW_ON_ERROR);
            $hmac    = hash_hmac('sha256', $payload, $this->getHmacKey());
            // Format: <hmac>:<base64-encoded-json>
            return (bool) file_put_contents($file, $hmac . ':' . base64_encode($payload), LOCK_EX);
        } catch (\JsonException $e) {
            error_log('CacheManager::set() JSON error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete item from cache
     * Rückgabe bool für CacheInterface-Kompatibilität.
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true; // Nicht vorhanden = bereits gelöscht
    }

    /**
     * Prüft ob ein gültiger Cache-Eintrag existiert (CacheInterface::has)
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Flush entire cache (Alias: clear() für CacheInterface)
     */
    public function flush(): bool
    {
        return $this->clear();
    }

    /**
     * Leert den gesamten File-Cache (CacheInterface::clear)
     */
    public function clear(): bool
    {
        $files  = glob($this->cacheDir . '*') ?: [];
        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = @unlink($file) && $success;
            }
        }
        // LiteSpeed Purge
        if ($this->useLiteSpeed) {
            header('X-LiteSpeed-Purge: *');
        }
        return $success;
    }

    // ── CacheInterface: Batch-Methoden ────────────────────────────────────────

    /**
     * Liest mehrere Werte in einem Aufruf (CacheInterface::getMultiple)
     *
     * @param  string[] $keys
     * @return array<string, mixed>
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        foreach ($keys as $key) {
            $val = $this->get($key);
            $result[$key] = ($val !== null) ? $val : $default;
        }
        return $result;
    }

    /**
     * Speichert mehrere Werte (CacheInterface::setMultiple)
     *
     * @param  array<string, mixed> $values
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set((string) $key, $value, $ttl) && $success;
        }
        return $success;
    }

    /**
     * Löscht mehrere Einträge (CacheInterface::deleteMultiple)
     *
     * @param  string[] $keys
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
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
