<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Services\ImageService;
use CMS\Services\OpcacheWarmupService;

final class PerformanceModule
{
    private const MEDIA_EXCLUDED_PATH_PARTS = [
        '/uploads/.elfinder/.tmb/',
        '\\uploads\\.elfinder\\.tmb\\',
    ];

    private const DEFAULT_SETTINGS = [
        'perf_lazy_loading' => '1',
        'perf_minify_css' => '0',
        'perf_minify_js' => '0',
        'perf_gzip' => '0',
        'perf_browser_cache' => '1',
        'perf_page_cache' => '1',
        'perf_browser_cache_ttl' => '604800',
        'perf_html_cache_ttl' => '300',
        'perf_webp_uploads' => '0',
        'perf_strip_exif' => '0',
        'perf_auto_clear_content_cache' => '1',
        'perf_session_timeout_admin' => '28800',
        'perf_session_timeout_member' => '2592000',
    ];

    private const BOOLEAN_SETTING_KEYS = [
        'perf_lazy_loading',
        'perf_minify_css',
        'perf_minify_js',
        'perf_gzip',
        'perf_browser_cache',
        'perf_page_cache',
        'perf_webp_uploads',
        'perf_strip_exif',
        'perf_auto_clear_content_cache',
    ];

    private const MAX_AUDIT_STRING_LENGTH = 240;

    private readonly \CMS\Database $db;
    private readonly \CMS\CacheManager $cacheManager;
    private readonly \CMS\Services\SystemService $systemService;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->cacheManager = \CMS\CacheManager::instance();
        $this->systemService = \CMS\Services\SystemService::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $settings = $this->getSettings();
        $cache = $this->getCacheMetrics();
        $media = $this->getMediaMetrics();
        $database = $this->getDatabaseMetrics();
        $sessions = $this->getSessionMetrics();
        $phpInfo = $this->getPhpInfo();

        return [
            'cache_size' => (int)($cache['file_cache']['size_bytes'] ?? 0),
            'cache_files' => (int)($cache['file_cache']['files'] ?? 0),
            'session_size' => (int)($sessions['session_dir_size'] ?? 0),
            'session_files' => (int)($sessions['session_dir_files'] ?? 0),
            'upload_size' => (int)($media['upload_size'] ?? 0),
            'db_size' => (int)($database['total_size_bytes'] ?? 0),
            'php_info' => $phpInfo,
            'settings' => $settings,
            'overview' => [
                'cache_score' => $cache['health_score'],
                'media_score' => $media['health_score'],
                'database_score' => $database['health_score'],
                'session_score' => $sessions['health_score'],
            ],
            'cache' => $cache,
            'media' => $media,
            'database' => $database,
            'sessions' => $sessions,
        ];
    }

    public function handleAction(string $section, string $action, array $post): array
    {
        return match ($action) {
            'clear_all_cache' => $this->clearAllCacheLayers(),
            'clear_file_cache' => $this->clearFileCache(),
            'clear_opcache' => $this->clearOpcache(),
            'warmup_opcache' => $this->warmupOpcache(),
            'optimize_database' => $this->optimizeDatabase(),
            'repair_tables' => $this->repairDatabase(),
            'clear_expired_sessions' => $this->clearExpiredSessions(),
            'convert_media_to_webp' => $this->convertMediaLibraryToWebp(),
            'save_settings', 'save_cache_settings', 'save_media_settings', 'save_session_settings' => $this->saveSettings($post),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };
    }

    private function getSettings(): array
    {
        $keys = array_keys(self::DEFAULT_SETTINGS);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $keys
        );

        $settings = self::DEFAULT_SETTINGS;
        foreach ($rows as $row) {
            $name = (string)($row->option_name ?? '');
            if ($name !== '' && array_key_exists($name, $settings)) {
                $settings[$name] = (string)($row->option_value ?? '');
            }
        }

        return $settings;
    }

    private function getPhpInfo(): array
    {
        $opcacheStatus = $this->getOpcacheStatus();

        return [
            'version' => PHP_VERSION,
            'memory_limit' => (string)ini_get('memory_limit'),
            'max_execution' => (string)ini_get('max_execution_time'),
            'upload_max' => (string)ini_get('upload_max_filesize'),
            'post_max' => (string)ini_get('post_max_size'),
            'opcache_enabled' => $opcacheStatus !== false,
            'gzip_enabled' => extension_loaded('zlib'),
            'opcache_memory_used' => isset($opcacheStatus['memory_usage']['used_memory']) ? (int)$opcacheStatus['memory_usage']['used_memory'] : 0,
            'opcache_memory_free' => isset($opcacheStatus['memory_usage']['free_memory']) ? (int)$opcacheStatus['memory_usage']['free_memory'] : 0,
        ];
    }

    private function getCacheMetrics(): array
    {
        $status = $this->cacheManager->getStatus();
        $warmupStatus = OpcacheWarmupService::getInstance()->getStatus(30);
        $cacheDir = ABSPATH . 'cache/';
        $oldestAge = null;
        $newestAge = null;

        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '*') ?: [];
            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $age = time() - (int)filemtime($file);
                $oldestAge = $oldestAge === null ? $age : max($oldestAge, $age);
                $newestAge = $newestAge === null ? $age : min($newestAge, $age);
            }
        }

        $activeDbCache = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}cache WHERE expires_at IS NULL OR expires_at > NOW()") ?? 0);
        $expiredDbCache = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}cache WHERE expires_at IS NOT NULL AND expires_at <= NOW()") ?? 0);

        $apcuInfo = $status['apcu']['info'] ?? null;
        $apcuHits = is_array($apcuInfo) ? (int)($apcuInfo['num_hits'] ?? 0) : 0;
        $apcuMisses = is_array($apcuInfo) ? (int)($apcuInfo['num_misses'] ?? 0) : 0;
        $hitRatio = ($apcuHits + $apcuMisses) > 0 ? round(($apcuHits / ($apcuHits + $apcuMisses)) * 100, 1) : null;

        $fileCacheFiles = (int)($status['file_cache']['files'] ?? 0);
        $fileCacheSizeText = (string)($status['file_cache']['size'] ?? '0 B');
        $fileCacheSizeBytes = $this->getDirSize($cacheDir);

        $healthScore = 100;
        if ($expiredDbCache > 100) {
            $healthScore -= 15;
        }
        if ($oldestAge !== null && $oldestAge > 86400) {
            $healthScore -= 10;
        }
        if ($hitRatio !== null && $hitRatio < 70.0) {
            $healthScore -= 10;
        }

        return [
            'health_score' => max(0, $healthScore),
            'file_cache' => [
                'files' => $fileCacheFiles,
                'size' => $fileCacheSizeText,
                'size_bytes' => $fileCacheSizeBytes,
                'writable' => (bool)($status['file_cache']['writable'] ?? false),
                'directory' => '/cache/',
                'oldest_age' => $oldestAge,
                'newest_age' => $newestAge,
            ],
            'apcu' => [
                'enabled' => (bool)($status['apcu']['enabled'] ?? false),
                'hits' => $apcuHits,
                'misses' => $apcuMisses,
                'hit_ratio' => $hitRatio,
            ],
            'opcache' => [
                'enabled' => (bool)($status['opcache']['enabled'] ?? false),
                'used_memory' => (int)($status['opcache']['status']['memory_usage']['used_memory'] ?? 0),
                'free_memory' => (int)($status['opcache']['status']['memory_usage']['free_memory'] ?? 0),
                'cached_scripts' => (int)($status['opcache']['status']['opcache_statistics']['num_cached_scripts'] ?? 0),
                'hits' => (int)($status['opcache']['status']['opcache_statistics']['hits'] ?? 0),
                'misses' => (int)($status['opcache']['status']['opcache_statistics']['misses'] ?? 0),
                'warmup' => $warmupStatus,
            ],
            'db_cache' => [
                'active_entries' => $activeDbCache,
                'expired_entries' => $expiredDbCache,
            ],
        ];
    }

    private function getMediaMetrics(): array
    {
        $uploadDir = ABSPATH . 'uploads/';
        $uploadSize = is_dir($uploadDir) ? $this->getDirSize($uploadDir, [$this, 'shouldExcludeMediaPath']) : 0;
        $imageService = ImageService::getInstance();
        $imageSupport = $imageService->getInfo();

        $mediaTotals = [
            'total_files' => 0,
            'total_size' => 0,
            'missing_alt' => 0,
            'webp_files' => 0,
        ];

        try {
            $totals = $this->db->get_row(
                "SELECT COUNT(*) AS total_files,
                        COALESCE(SUM(filesize), 0) AS total_size,
                        SUM(CASE WHEN COALESCE(alt_text, '') = '' THEN 1 ELSE 0 END) AS missing_alt,
                        SUM(CASE WHEN LOWER(filetype) LIKE '%webp%' OR LOWER(filename) LIKE '%.webp' THEN 1 ELSE 0 END) AS webp_files
                 FROM {$this->prefix}media"
            );

            if ($totals !== null) {
                $mediaTotals = [
                    'total_files' => (int)($totals->total_files ?? 0),
                    'total_size' => (int)($totals->total_size ?? 0),
                    'missing_alt' => (int)($totals->missing_alt ?? 0),
                    'webp_files' => (int)($totals->webp_files ?? 0),
                ];
            }
        } catch (\Throwable) {
        }

        $largestImages = [];
        $conversionCandidates = [];
        $convertibleFiles = 0;
        $convertibleBytes = 0;
        if (is_dir($uploadDir)) {
            $iterator = $this->createMediaIterator($uploadDir);
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $extension = strtolower((string)$file->getExtension());
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    continue;
                }

                $dimensions = $this->getImageSize($file->getPathname()) ?: [0, 0];
                $largestImages[] = [
                    'path' => $this->toRelativeRuntimePath($file->getPathname()),
                    'size' => $file->getSize(),
                    'width' => (int)($dimensions[0] ?? 0),
                    'height' => (int)($dimensions[1] ?? 0),
                    'is_webp' => $extension === 'webp',
                ];

                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    $convertibleFiles++;
                    $convertibleBytes += $file->getSize();
                    $conversionCandidates[] = [
                        'path' => $this->toRelativeRuntimePath($file->getPathname()),
                        'size' => $file->getSize(),
                        'extension' => $extension,
                    ];
                }
            }
        }

        usort($largestImages, static fn(array $a, array $b): int => $b['size'] <=> $a['size']);
        $largestImages = array_slice($largestImages, 0, 10);
        usort($conversionCandidates, static fn(array $a, array $b): int => $b['size'] <=> $a['size']);
        $conversionCandidates = array_slice($conversionCandidates, 0, 15);

        $oversizedCount = 0;
        foreach ($largestImages as $image) {
            if ($image['size'] > 500 * 1024 || $image['width'] > 2560) {
                $oversizedCount++;
            }
        }

        $healthScore = 100;
        if ($mediaTotals['missing_alt'] > 0) {
            $healthScore -= min(25, $mediaTotals['missing_alt']);
        }
        if ($oversizedCount > 0) {
            $healthScore -= min(20, $oversizedCount * 5);
        }

        return [
            'health_score' => max(0, $healthScore),
            'upload_size' => $uploadSize,
            'library' => $mediaTotals,
            'largest_images' => $largestImages,
            'oversized_images' => $oversizedCount,
            'conversion' => [
                'supported' => !empty($imageSupport['available']) && !empty($imageSupport['webp_support']),
                'convertible_files' => $convertibleFiles,
                'convertible_bytes' => $convertibleBytes,
                'candidates' => $conversionCandidates,
            ],
        ];
    }

    private function convertMediaLibraryToWebp(): array
    {
        $imageService = ImageService::getInstance();
        $imageInfo = $imageService->getInfo();

        if (empty($imageInfo['available']) || empty($imageInfo['webp_support'])) {
            return ['success' => false, 'error' => 'WebP-Konvertierung ist auf diesem Server nicht verfügbar.'];
        }

        $uploadDir = ABSPATH . 'uploads/';
        if (!is_dir($uploadDir)) {
            return ['success' => false, 'error' => 'Uploads-Verzeichnis nicht gefunden.'];
        }

        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $savedBytes = 0;
        $updatedReferences = 0;

        $iterator = $this->createMediaIterator($uploadDir);
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $sourcePath = $file->getPathname();
            $extension = strtolower((string)$file->getExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                continue;
            }

            $originalSize = (int)$file->getSize();
            $webpPath = $imageService->convertToWebP($sourcePath, 82, false);
            if ($webpPath === null || !is_file($webpPath)) {
                $failed++;
                continue;
            }

            $webpSize = (int)filesize($webpPath);
            if ($webpSize <= 0 || $webpSize >= $originalSize) {
                if ($webpPath !== $sourcePath && is_file($webpPath)) {
                    $this->deleteFileIfExists($webpPath);
                }
                $skipped++;
                continue;
            }

            $updatedReferences += $this->replaceMediaReferences($sourcePath, $webpPath, $webpSize);

            if (is_file($sourcePath)) {
                $this->deleteFileIfExists($sourcePath);
            }

            $converted++;
            $savedBytes += max(0, $originalSize - $webpSize);
        }

        if ($converted === 0 && $failed === 0) {
            return ['success' => true, 'message' => 'Keine geeigneten Bilder für eine kleinere WebP-Version gefunden.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_MEDIA,
            'performance.media.convert_webp',
            'Medienbibliothek in WebP konvertiert',
            'media',
            null,
            [
                'converted' => $converted,
                'skipped' => $skipped,
                'failed' => $failed,
                'saved_bytes' => $savedBytes,
                'updated_references' => $updatedReferences,
            ],
            $failed > 0 ? 'warning' : 'info'
        );

        return [
            'success' => $converted > 0,
            'message' => sprintf(
                '%d Bild%s in WebP umgewandelt, %s eingespart, %d Referenz%s aktualisiert, %d übersprungen, %d fehlgeschlagen.',
                $converted,
                $converted === 1 ? '' : 'er',
                $this->formatBytes($savedBytes),
                $updatedReferences,
                $updatedReferences === 1 ? '' : 'en',
                $skipped,
                $failed
            ),
        ];
    }

    private function getDatabaseMetrics(): array
    {
        $tables = [];
        $totalSize = 0;
        $totalOverhead = 0;

        try {
            $tables = $this->db->get_results(
                "SELECT table_name,
                        table_rows,
                        data_length,
                        index_length,
                        data_free
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 AND table_name LIKE ?
                 ORDER BY (data_length + index_length) DESC",
                [$this->prefix . '%']
            );
        } catch (\Throwable) {
            $tables = [];
        }

        $tableMetrics = [];
        foreach ($tables as $table) {
            $size = (int)($table->data_length ?? 0) + (int)($table->index_length ?? 0);
            $overhead = (int)($table->data_free ?? 0);
            $totalSize += $size;
            $totalOverhead += $overhead;
            $tableMetrics[] = [
                'name' => (string)($table->table_name ?? ''),
                'rows' => (int)($table->table_rows ?? 0),
                'size' => $size,
                'overhead' => $overhead,
            ];
        }

        $revisionCount = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}page_revisions") ?? 0);
        $expiredSessions = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}sessions WHERE expires_at IS NOT NULL AND expires_at <= NOW()") ?? 0);
        $expiredCache = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}cache WHERE expires_at IS NOT NULL AND expires_at <= NOW()") ?? 0);
        $failedLogins24h = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}login_attempts WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)") ?? 0);

        $healthScore = 100;
        if ($totalOverhead > 20 * 1024 * 1024) {
            $healthScore -= 20;
        }
        if ($revisionCount > 500) {
            $healthScore -= 10;
        }
        if ($expiredSessions > 0 || $expiredCache > 0) {
            $healthScore -= 10;
        }

        return [
            'health_score' => max(0, $healthScore),
            'total_size_bytes' => $totalSize,
            'total_overhead_bytes' => $totalOverhead,
            'table_count' => count($tableMetrics),
            'top_tables' => array_slice($tableMetrics, 0, 8),
            'revision_count' => $revisionCount,
            'expired_sessions' => $expiredSessions,
            'expired_cache_entries' => $expiredCache,
            'failed_logins_last_24h' => $failedLogins24h,
        ];
    }

    private function getSessionMetrics(): array
    {
        $sessionDir = ABSPATH . 'sessions/';
        $sessionDirFiles = is_dir($sessionDir) ? count(glob($sessionDir . '*') ?: []) : 0;
        $sessionDirSize = is_dir($sessionDir) ? $this->getDirSize($sessionDir) : 0;

        $activeSessions = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}sessions WHERE expires_at IS NULL OR expires_at > NOW()") ?? 0);
        $expiredSessions = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}sessions WHERE expires_at IS NOT NULL AND expires_at <= NOW()") ?? 0);
        $recentSessions = [];

        try {
            $recentSessions = $this->db->get_results(
                "SELECT id, user_id, ip_address, user_agent, last_activity, expires_at
                 FROM {$this->prefix}sessions
                 ORDER BY last_activity DESC
                 LIMIT 10"
            );
        } catch (\Throwable) {
            $recentSessions = [];
        }

        $settings = $this->getSettings();
        $healthScore = 100;
        if ($expiredSessions > 50) {
            $healthScore -= 20;
        }
        if ($activeSessions > 200) {
            $healthScore -= 10;
        }

        return [
            'health_score' => max(0, $healthScore),
            'active_sessions' => $activeSessions,
            'expired_sessions' => $expiredSessions,
            'session_dir_files' => $sessionDirFiles,
            'session_dir_size' => $sessionDirSize,
            'recent_sessions' => array_map(function (object $session): array {
                return [
                    'id' => (string)($session->id ?? ''),
                    'user_id' => (int)($session->user_id ?? 0),
                    'ip_address' => $this->maskIpAddress((string)($session->ip_address ?? '')),
                    'user_agent' => $this->sanitizeUserAgent((string)($session->user_agent ?? '')),
                    'last_activity' => (string)($session->last_activity ?? ''),
                    'expires_at' => (string)($session->expires_at ?? ''),
                ];
            }, $recentSessions),
            'timeouts' => [
                'admin' => (int)$settings['perf_session_timeout_admin'],
                'member' => (int)$settings['perf_session_timeout_member'],
            ],
        ];
    }

    private function clearAllCacheLayers(): array
    {
        $report = $this->cacheManager->clearAll();
        $warmup = (!empty($report['opcache']))
            ? OpcacheWarmupService::getInstance()->warmTopFiles(30, true)
            : ['success' => false, 'message' => 'Warmup übersprungen, weil OPcache nicht geleert wurde.'];
        $details = [];
        foreach (($report['details'] ?? []) as $label => $message) {
            $details[] = $label . ': ' . $message;
        }
        if (!empty($report['opcache'])) {
            $details[] = 'warmup: ' . ($warmup['message'] ?? 'nicht ausgeführt');
        }

        // ADDED: Vollständige Cache-Bereinigung im Audit-Log erfassen.
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.cache.clear_all',
            'Alle Cache-Layer bereinigt',
            'cache',
            null,
            ['details' => $this->sanitizeAuditArray((array)($report['details'] ?? [])), 'warmup' => $this->summarizeWarmupResult($warmup)],
            'warning'
        );

        return [
            'success' => true,
            'message' => 'Alle Cache-Layer bereinigt. ' . implode(' | ', $details),
        ];
    }

    private function clearFileCache(): array
    {
        $cacheDir = ABSPATH . 'cache/';
        if (!is_dir($cacheDir)) {
            return ['success' => false, 'error' => 'Cache-Verzeichnis nicht gefunden.'];
        }

        $count = 0;
        foreach (glob($cacheDir . '*') ?: [] as $file) {
            if (is_link($file)) {
                continue;
            }

            if (is_file($file) && unlink($file)) {
                $count++;
            }
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.cache.clear_file',
            'Datei-Cache geleert',
            'cache',
            null,
            ['deleted_files' => $count],
            'warning'
        );

        return ['success' => true, 'message' => $count . ' Datei-Cache(s) gelöscht.'];
    }

    private function clearOpcache(): array
    {
        if (!function_exists('opcache_reset')) {
            return ['success' => false, 'error' => 'OPcache ist nicht verfügbar.'];
        }

        opcache_reset();
        $warmup = OpcacheWarmupService::getInstance()->warmTopFiles(30, true);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.cache.clear_opcache',
            'OPcache zurückgesetzt',
            'cache',
            null,
            ['warmup' => $this->summarizeWarmupResult($warmup)],
            'warning'
        );

        $message = 'OPcache wurde geleert. ' . ($warmup['message'] ?? 'Warmup nicht ausgeführt.');

        return [
            'success' => true,
            'message' => $message,
        ];
    }

    private function warmupOpcache(): array
    {
        $warmup = OpcacheWarmupService::getInstance()->warmTopFiles(30, true);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.cache.warmup_opcache',
            'OPcache-Warmup ausgeführt',
            'cache',
            null,
            $this->summarizeWarmupResult($warmup),
            $warmup['success'] ?? false ? 'info' : 'warning'
        );

        return [
            'success' => (bool)($warmup['success'] ?? false),
            'message' => (string)($warmup['message'] ?? 'OPcache-Warmup wurde ausgeführt.'),
            'error' => empty($warmup['success']) ? (string)($warmup['message'] ?? 'OPcache-Warmup fehlgeschlagen.') : null,
        ];
    }

    private function optimizeDatabase(): array
    {
        $result = $this->systemService->optimizeTables();
        $success = 0;
        foreach ($result as $row) {
            if (!empty($row['success'])) {
                $success++;
            }
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.database.optimize',
            'Datenbanktabellen optimiert',
            'database',
            null,
            ['success_count' => $success],
            'warning'
        );

        return ['success' => true, 'message' => $success . ' Tabelle(n) optimiert.'];
    }

    private function repairDatabase(): array
    {
        $result = $this->systemService->repairTables();
        $success = 0;
        foreach ($result as $row) {
            if (!empty($row['success'])) {
                $success++;
            }
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.database.repair',
            'Datenbanktabellen geprüft oder repariert',
            'database',
            null,
            ['success_count' => $success],
            'warning'
        );

        return ['success' => true, 'message' => $success . ' Tabelle(n) repariert bzw. geprüft.'];
    }

    private function clearExpiredSessions(): array
    {
        $dbDeleted = false;
        try {
            $this->db->execute("DELETE FROM {$this->prefix}sessions WHERE expires_at IS NOT NULL AND expires_at <= NOW()");
            $dbDeleted = true;
        } catch (\Throwable) {
        }

        $sessionDir = ABSPATH . 'sessions/';
        $fileCount = 0;
        if (is_dir($sessionDir)) {
            $threshold = time() - 86400;
            foreach (glob($sessionDir . '*') ?: [] as $file) {
                if (is_link($file)) {
                    continue;
                }

                if (is_file($file) && filemtime($file) < $threshold && unlink($file)) {
                    $fileCount++;
                }
            }
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'performance.sessions.clear_expired',
            'Abgelaufene Sessions bereinigt',
            'session',
            null,
            ['db_deleted' => $dbDeleted, 'file_deleted' => $fileCount],
            'warning'
        );

        return [
            'success' => true,
            'message' => 'Abgelaufene Sessions bereinigt' . ($dbDeleted ? ' (DB)' : '') . '; Dateisessions gelöscht: ' . $fileCount . '.',
        ];
    }

    private function saveSettings(array $post): array
    {
        $settings = $this->getSettings();

        foreach ($settings as $key => $default) {
            if (in_array($key, self::BOOLEAN_SETTING_KEYS, true)) {
                $settings[$key] = !empty($post[$key]) ? '1' : '0';
                continue;
            }

            $settings[$key] = match ($key) {
                'perf_browser_cache_ttl' => (string)max(0, min(31536000, (int)($post[$key] ?? $default))),
                'perf_html_cache_ttl' => (string)max(0, min(86400, (int)($post[$key] ?? $default))),
                'perf_session_timeout_admin' => (string)max(300, min(604800, (int)($post[$key] ?? $default))),
                'perf_session_timeout_member' => (string)max(300, min(31536000, (int)($post[$key] ?? $default))),
                default => (string)max(0, (int)($post[$key] ?? $default)),
            };
        }

        try {
            $existing = $this->loadExistingSettingNames(array_keys($settings));

            foreach ($settings as $key => $value) {
                if (isset($existing[$key])) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
        } catch (\Throwable $e) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'performance.settings.save_failed',
                'Performance-Einstellungen konnten nicht gespeichert werden',
                'setting',
                null,
                ['exception' => $this->sanitizeAuditString($e->getMessage())],
                'error'
            );

            return ['success' => false, 'error' => 'Performance-Einstellungen konnten nicht gespeichert werden. Bitte Logs prüfen.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'performance.settings.save',
            'Performance-Einstellungen gespeichert',
            'setting',
            null,
            $settings,
            'warning'
        );

        return ['success' => true, 'message' => 'Performance-Einstellungen gespeichert.'];
    }

    /** @param list<string> $keys @return array<string, true> */
    private function loadExistingSettingNames(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $keys
        ) ?: [];

        $existing = [];
        foreach ($rows as $row) {
            $name = (string)($row->option_name ?? '');
            if ($name !== '') {
                $existing[$name] = true;
            }
        }

        return $existing;
    }

    private function getDirSize(string $dir, ?callable $excludePath = null): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                continue;
            }

            if ($excludePath !== null && $excludePath($file->getPathname())) {
                continue;
            }

            $size += $file->getSize();
        }

        return $size;
    }

    private function createMediaIterator(string $uploadDir): \RecursiveIteratorIterator
    {
        $directoryIterator = new \RecursiveDirectoryIterator($uploadDir, \FilesystemIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator(
            $directoryIterator,
            function (\SplFileInfo $current): bool {
                if ($current->isLink()) {
                    return false;
                }

                return !$this->shouldExcludeMediaPath($current->getPathname());
            }
        );

        return new \RecursiveIteratorIterator($filter);
    }

    private function shouldExcludeMediaPath(string $path): bool
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        foreach (self::MEDIA_EXCLUDED_PATH_PARTS as $excludedPart) {
            $normalizedExcludedPart = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $excludedPart);
            if (str_contains($normalizedPath, $normalizedExcludedPart)) {
                return true;
            }
        }

        return false;
    }

    private function replaceMediaReferences(string $sourcePath, string $webpPath, int $webpSize): int
    {
        $oldRelative = ltrim(str_replace('\\', '/', str_replace(ABSPATH, '', $sourcePath)), '/');
        $newRelative = ltrim(str_replace('\\', '/', str_replace(ABSPATH, '', $webpPath)), '/');
        $oldPublic = '/' . $oldRelative;
        $newPublic = '/' . $newRelative;
        $oldUrl = rtrim((string)SITE_URL, '/') . $oldPublic;
        $newUrl = rtrim((string)SITE_URL, '/') . $newPublic;

        $updates = 0;

        $stmt = $this->db->prepare(
            "UPDATE {$this->prefix}media
             SET filename = ?, filepath = ?, filetype = ?, filesize = ?
             WHERE REPLACE(filepath, '\\\\', '/') IN (?, ?, ?) OR filename = ?"
        );
        $stmt->execute([
            basename($webpPath),
            $newRelative,
            'image/webp',
            $webpSize,
            $oldRelative,
            $oldPublic,
            $oldUrl,
            basename($sourcePath),
        ]);
        $updates += $stmt->rowCount();

        foreach ([
            ['table' => 'pages', 'columns' => ['featured_image', 'content']],
            ['table' => 'posts', 'columns' => ['featured_image', 'content', 'excerpt']],
            ['table' => 'seo_meta', 'columns' => ['og_image', 'twitter_image']],
        ] as $target) {
            foreach ($target['columns'] as $column) {
                $updates += $this->replaceInColumn($target['table'], $column, $oldUrl, $newUrl);
                $updates += $this->replaceInColumn($target['table'], $column, $oldPublic, $newPublic);
                $updates += $this->replaceInColumn($target['table'], $column, $oldRelative, $newRelative);
            }
        }

        return $updates;
    }

    private function replaceInColumn(string $table, string $column, string $search, string $replace): int
    {
        if ($search === '' || $search === $replace) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->prefix}{$table}
             SET {$column} = REPLACE({$column}, ?, ?)
             WHERE {$column} IS NOT NULL AND {$column} LIKE ?"
        );
        $stmt->execute([$search, $replace, '%' . $search . '%']);

        return $stmt->rowCount();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }

    private function getOpcacheStatus(): array|false
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        return $this->runSuppressedOperation(static fn() => opcache_get_status(false));
    }

    private function getImageSize(string $path): array|false
    {
        return $this->runSuppressedOperation(static fn() => getimagesize($path));
    }

    private function deleteFileIfExists(string $path): bool
    {
        if (is_link($path)) {
            return false;
        }

        if (!is_file($path)) {
            return true;
        }

        return unlink($path);
    }

    private function toRelativeRuntimePath(string $path): string
    {
        $normalizedBase = str_replace('\\', '/', rtrim(ABSPATH, '\\/')) . '/';
        $normalizedPath = str_replace('\\', '/', $path);

        if (str_starts_with($normalizedPath, $normalizedBase)) {
            return ltrim(substr($normalizedPath, strlen($normalizedBase)), '/');
        }

        return basename($normalizedPath);
    }

    private function maskIpAddress(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';

                return implode('.', $parts);
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $segments = explode(':', $ip);
            $segments = array_pad(array_slice($segments, 0, 4), 4, '');

            return implode(':', $segments) . '::';
        }

        return $this->sanitizeAuditString($ip, 64);
    }

    private function sanitizeUserAgent(string $userAgent): string
    {
        return $this->sanitizeAuditString($userAgent, 160);
    }

    /** @param array<string, mixed> $values @return array<string, string> */
    private function sanitizeAuditArray(array $values): array
    {
        $sanitized = [];
        foreach ($values as $key => $value) {
            $encodedValue = is_scalar($value)
                ? (string)$value
                : ((json_encode($value, JSON_UNESCAPED_UNICODE) ?: ''));
            $sanitized[(string)$key] = $this->sanitizeAuditString($encodedValue, 160);
        }

        return $sanitized;
    }

    /** @param array<string, mixed> $warmup */
    private function summarizeWarmupResult(array $warmup): array
    {
        return [
            'success' => !empty($warmup['success']),
            'message' => $this->sanitizeAuditString((string)($warmup['message'] ?? ''), 160),
            'processed' => isset($warmup['processed']) ? (int)$warmup['processed'] : null,
            'warmed' => isset($warmup['warmed']) ? (int)$warmup['warmed'] : null,
            'failed' => isset($warmup['failed']) ? (int)$warmup['failed'] : null,
        ];
    }

    private function sanitizeAuditString(string $value, int $maxLength = self::MAX_AUDIT_STRING_LENGTH): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function runSuppressedOperation(callable $operation): mixed
    {
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}
