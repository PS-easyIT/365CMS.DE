<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

/**
 * Dashboard Service - Business Logic für Dashboard-Statistiken
 * 
 * @package CMS\Services
 */
class DashboardService {
    
    private Database $db;
    private string $prefix;
    private static ?DashboardService $instance = null;
    
    private function __construct() {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }
    
    public static function getInstance(): DashboardService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Alle Dashboard-Statistiken abrufen
     * 
     * @return array Alle Stats
     */
    public function getAllStats(): array {
        return [
            'users' => $this->getUserStats(),
            'pages' => $this->getPageStats(),
            'media' => $this->getMediaStats(),
            'sessions' => $this->getSessionStats(),
            'security' => $this->getSecurityStats(),
            'performance' => $this->getPerformanceStats(),
            'system' => $this->getSystemInfo()
        ];
    }
    
    /**
     * Benutzer-Statistiken
     */
    public function getUserStats(): array {
        $total = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users");
        $active = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'active'");
        $inactive = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'inactive'");
        
        // Benutzer, die heute eingeloggt waren
        $active_today = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}users WHERE DATE(last_login) = CURDATE()"
        );
        
        $new_today = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}users WHERE DATE(created_at) = CURDATE()"
        );
        $new_this_week = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $new_this_month = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // Rollen-Verteilung
        $roles = $this->db->get_results(
            "SELECT role, COUNT(*) as count FROM {$this->prefix}users GROUP BY role"
        );
        
        $role_distribution = [];
        foreach ($roles as $role) {
            $role_distribution[$role->role] = (int)$role->count;
        }
        
        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'active_today' => $active_today,
            'new_today' => $new_today,
            'new_this_week' => $new_this_week,
            'new_this_month' => $new_this_month,
            'roles' => $role_distribution,
            'growth_rate' => $total > 0 ? round(($new_this_month / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Seiten-Statistiken
     */
    public function getPageStats(): array {
        // Dynamic table via prefix
        $total = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages") ?: 0;
        $published = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'published'") ?: 0;
        $drafts = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'draft'") ?: 0;
        $private = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}pages WHERE status = 'private'") ?: 0;
        
        // Seiten mit zukünftigem Veröffentlichungsdatum (optional - Spalte existiert möglicherweise nicht)
        $scheduled = 0;
        try {
            $scheduled = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}pages WHERE published_at IS NOT NULL AND published_at > NOW()"
            ) ?: 0;
        } catch (\Throwable $e) {
            // Spalte published_at existiert nicht - ignorieren
            error_log('DashboardService: published_at column not found - ' . $e->getMessage());
        }
        
        return [
            'total' => $total,
            'published' => $published,
            'drafts' => $drafts,
            'private' => $private,
            'scheduled' => $scheduled
        ];
    }
    
    /**
     * Medien-Statistiken
     */
    public function getMediaStats(): array {
        // Disk usage
        $upload_dir = ABSPATH . '/uploads';
        $total_size = 0;
        $file_count = 0;
        
        if (is_dir($upload_dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($upload_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $total_size += $file->getSize();
                    $file_count++;
                }
            }
        }
        
        // Dateitypen-Verteilung
        $types = [
            'images' => 0,
            'videos' => 0,
            'documents' => 0,
            'archives' => 0,
            'other' => 0
        ];
        
        if (is_dir($upload_dir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($upload_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($files as $file) {
                if (!$file->isFile()) continue;
                
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                    $types['images']++;
                } elseif (in_array($ext, ['mp4', 'avi', 'mov', 'wmv', 'webm'])) {
                    $types['videos']++;
                } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                    $types['documents']++;
                } elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])) {
                    $types['archives']++;
                } else {
                    $types['other']++;
                }
            }
        }
        
        return [
            'total' => $file_count,
            'total_files' => $file_count,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1048576, 2),
            'total_size_formatted' => $this->formatBytes($total_size),
            'types' => $types
        ];
    }
    
    /**
     * Session-Statistiken
     */
    public function getSessionStats(): array {
        try {
            $active_sessions = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM cms_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
            ) ?: 0;
            
            $total_sessions_today = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM cms_sessions WHERE DATE(last_activity) = CURDATE()"
            ) ?: 0;
            
            $avg_session_duration = $this->db->get_var(
                "SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, last_activity)) 
                 FROM cms_sessions 
                 WHERE DATE(created_at) = CURDATE()"
            ) ?: 0;
            
            // Browser-Statistik
            $browsers = $this->db->get_results(
                "SELECT 
                    CASE 
                        WHEN user_agent LIKE '%Chrome%' THEN 'Chrome'
                        WHEN user_agent LIKE '%Firefox%' THEN 'Firefox'
                        WHEN user_agent LIKE '%Safari%' AND user_agent NOT LIKE '%Chrome%' THEN 'Safari'
                        WHEN user_agent LIKE '%Edge%' THEN 'Edge'
                        ELSE 'Other'
                    END as browser,
                    COUNT(*) as count
                 FROM cms_sessions 
                 WHERE DATE(last_activity) = CURDATE()
                 GROUP BY browser"
            ) ?: [];
            
            $browser_stats = [];
            foreach ($browsers as $browser) {
                $browser_stats[$browser->browser] = (int)$browser->count;
            }
            
            $total_sessions = (int)$this->db->get_var("SELECT COUNT(*) FROM cms_sessions") ?: 0;
            
            return [
                'active' => $active_sessions,
                'active_now' => $active_sessions,
                'today' => $total_sessions_today,
                'total' => $total_sessions,
                'avg_duration' => round((float)$avg_session_duration / 60, 2), // in Minuten
                'browsers' => $browser_stats
            ];
        } catch (\Throwable $e) {
            error_log('DashboardService: Session stats error - ' . $e->getMessage());
            return [
                'active' => 0,
                'active_now' => 0,
                'today' => 0,
                'total' => 0,
                'avg_duration' => 0,
                'browsers' => []
            ];
        }
    }
    
    /**
     * Sicherheits-Statistiken
     */
    public function getSecurityStats(): array {
        $failed_logins_24h = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM cms_login_attempts 
             WHERE success = 0 
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ) ?: 0;
        
        $successful_logins_24h = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM cms_login_attempts 
             WHERE success = 1 
             AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        ) ?: 0;
        
        // Note: cms_blocked_ips table doesn't exist yet
        // This functionality can be implemented by a security plugin
        $blocked_ips = 0;
        
        // Security Score berechnen
        $score = 100;
        
        // PHP-Version
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $score -= 10;
        }
        
        // HTTPS
        $https_enabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                      || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        
        if (!$https_enabled) {
            $score -= 20;
        }
        
        // Debug-Modus
        if (defined('CMS_DEBUG') && CMS_DEBUG) {
            $score -= 10;
        }
        
        // Zu viele Failed Logins
        if ($failed_logins_24h > 10) {
            $score -= 15;
        }
        
        return [
            'failed_logins_24h' => $failed_logins_24h,
            'successful_logins_24h' => $successful_logins_24h,
            'blocked_ips' => $blocked_ips,
            'https_enabled' => $https_enabled,
            'security_score' => max(0, $score),
            'status' => $score >= 80 ? 'good' : ($score >= 60 ? 'warning' : 'critical')
        ];
    }
    
    /**
     * Performance-Statistiken
     */
    public function getPerformanceStats(): array {
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        
        $disk_free = disk_free_space(ABSPATH);
        $disk_total = disk_total_space(ABSPATH);
        
        // OPcache
        $opcache_enabled = function_exists('opcache_get_status');
        $opcache_stats = [];
        if ($opcache_enabled) {
            $status = @opcache_get_status();
            if ($status) {
                $opcache_stats = [
                    'enabled' => true,
                    'hits' => $status['opcache_statistics']['hits'] ?? 0,
                    'misses' => $status['opcache_statistics']['misses'] ?? 0,
                    'hit_rate' => $status['opcache_statistics']['opcache_hit_rate'] ?? 0,
                    'memory_used' => $status['memory_usage']['used_memory'] ?? 0,
                    'memory_free' => $status['memory_usage']['free_memory'] ?? 0
                ];
            }
        }
        
        // Performance Score
        $score = 0;
        
        // PHP Version (10 Punkte)
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) $score += 10;
        
        // Memory (10 Punkte)
        $memory_limit_bytes = $this->returnBytes($memory_limit);
        $memory_percent = ($memory_usage / $memory_limit_bytes) * 100;
        if ($memory_percent < 70) $score += 10;
        elseif ($memory_percent < 85) $score += 5;
        
        // Disk Space (10 Punkte)
        $disk_percent = (($disk_total - $disk_free) / $disk_total) * 100;
        if ($disk_percent < 70) $score += 10;
        elseif ($disk_percent < 85) $score += 5;
        
        // OPcache (10 Punkte)
        if (!empty($opcache_stats['enabled'])) $score += 10;
        
        // Extensions (10 Punkte)
        $required_extensions = ['pdo_mysql', 'mbstring', 'curl', 'gd', 'zip', 'json'];
        $loaded = 0;
        foreach ($required_extensions as $ext) {
            if (extension_loaded($ext)) $loaded++;
        }
        $score += round(($loaded / count($required_extensions)) * 10);
        
        return [
            'memory_limit' => $memory_limit,
            'memory_usage' => $memory_usage,
            'memory_usage_formatted' => $this->formatBytes($memory_usage),
            'memory_peak' => $memory_peak,
            'memory_peak_formatted' => $this->formatBytes($memory_peak),
            'memory_percent' => round($memory_percent, 2),
            'disk_free' => $disk_free,
            'disk_free_formatted' => $this->formatBytes($disk_free),
            'disk_total' => $disk_total,
            'disk_total_formatted' => $this->formatBytes($disk_total),
            'disk_percent' => round($disk_percent, 2),
            'opcache' => $opcache_stats,
            'performance_score' => $score
        ];
    }
    
    /**
     * System-Informationen
     */
    public function getSystemInfo(): array {
        return [
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->db->get_var("SELECT VERSION()"),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'os' => PHP_OS,
            'cms_version' => '2.0.0',
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
    }
    
    /**
     * Aktivitäts-Feed abrufen
     * 
     * @param int $limit Anzahl Einträge
     * @return array
     */
    public function getActivityFeed(int $limit = 50): array {
        $activities = $this->db->get_results(
            "SELECT al.*, u.username 
             FROM {$this->prefix}activity_log al
             LEFT JOIN {$this->prefix}users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT ?",
            [$limit]
        ) ?: [];
        
        foreach ($activities as $activity) {
            $activity->details = json_decode($activity->details ?? '{}');
        }
        
        return $activities;
    }
    
    /**
     * Bytes formatieren
     */
    private function formatBytes(int|float $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = (float)$bytes; // Konvertiere zu float für Division
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * String zu Bytes konvertieren (z.B. "128M" -> 134217728)
     */
    private function returnBytes(string $size_str): int|float {
        $size_str = trim($size_str);
        
        if (empty($size_str)) {
            return 0;
        }
        
        $last = strtolower($size_str[strlen($size_str)-1]);
        $size = (float)$size_str; // Use float to handle large numbers
        
        switch($last) {
            case 'g':
                $size *= 1024;
                // fall through
            case 'm':
                $size *= 1024;
                // fall through
            case 'k':
                $size *= 1024;
                break;
        }
        
        return $size;
    }
}
