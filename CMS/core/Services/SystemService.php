<?php
declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Debug;
use CMS\Security;
use PDO;

/**
 * System Service
 * Handles system status, diagnostics, and troubleshooting
 * 
 * @package CMS\Services
 */
class SystemService {
    private static ?SystemService $instance = null;
    private Database $db;
    
    /**
     * Get singleton instance
     */
    public static function instance(): SystemService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->db = Database::instance();
    }
    
    /**
     * Get system information
     */
    public function getSystemInfo(): array {
        $pdo = $this->db->getConnection();
        
        // Get MySQL version
        $mysql_version = 'Unknown';
        try {
            $result = $pdo->query("SELECT VERSION() as version");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $mysql_version = $row['version'];
            }
        } catch (\Exception $e) {
            $mysql_version = 'Error: ' . $e->getMessage();
        }
        
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => $mysql_version,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'timezone' => date_default_timezone_get(),
            'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
            'error_reporting' => error_reporting(),
            'session_save_path' => session_save_path(),
            'temp_dir' => sys_get_temp_dir(),
            'os' => PHP_OS,
            'architecture' => php_uname('m'),
            'hostname' => gethostname()
        ];
    }
    
    /**
     * Get database status
     */
    public function getDatabaseStatus(): array {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        $status = [
            'connected' => false,
            'database_name' => DB_NAME,
            'database_size' => 0,
            'total_tables' => 0,
            'cms_tables' => 0,
            'connection_error' => null
        ];
        
        try {
            // Test connection
            $result = $pdo->query("SELECT 1");
            if ($result) {
                $status['connected'] = true;
            }
            
            // Get database size
            $result = $pdo->query("
                SELECT 
                    SUM(data_length + index_length) as size
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "'
            ");
            
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $status['database_size'] = (int)($row['size'] ?? 0);
            }
            
            // Count total tables
            $result = $pdo->query("
                SELECT COUNT(*) as total 
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "'
            ");
            
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $status['total_tables'] = (int)($row['total'] ?? 0);
            }
            
            // Count CMS tables
            $result = $pdo->query("
                SELECT COUNT(*) as total 
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "'
                AND table_name LIKE '{$prefix}%'
            ");
            
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $status['cms_tables'] = (int)($row['total'] ?? 0);
            }
            
        } catch (\Exception $e) {
            $status['connection_error'] = $e->getMessage();
        }
        
        return $status;
    }
    
    /**
     * Check all CMS database tables
     */
    public function checkDatabaseTables(): array {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        $tables = [
            'users' => 'Benutzer',
            'user_meta' => 'Benutzer-Metadaten',
            'roles' => 'Rollen',
            'sessions' => 'Sitzungen',
            'settings' => 'Einstellungen',
            'pages' => 'Seiten',
            'page_revisions' => 'Seiten-Revisionen',
            'landing_sections' => 'Landing Sections',
            'activity_log' => 'Aktivitätslog',
            'cache' => 'Cache',
            'login_attempts' => 'Login-Versuche',
            'blocked_ips' => 'Blockierte IPs',
            'media' => 'Media-Bibliothek',
            'plugins' => 'Plugins',
            'plugin_meta' => 'Plugin-Metadaten',
            'theme_customizations' => 'Theme-Anpassungen',
            
            // Content
            'post_categories' => 'Blog-Kategorien',
            'posts' => 'Blog-Beiträge',
            'comments' => 'Kommentare',
            'page_views' => 'Seitenaufrufe',
            'orders' => 'Bestellungen',
            'messages' => 'Nachrichten',

            // Subscription System
            'subscription_plans' => 'Abo-Pakete',
            'user_subscriptions' => 'Benutzer-Abos',
            'user_groups' => 'Benutzer-Gruppen',
            'user_group_members' => 'Gruppen-Mitglieder',
            'subscription_usage' => 'Abo-Nutzung',

            // Security & Audit
            'failed_logins' => 'Fehlgeschlagene Logins',
            'audit_log' => 'Audit-Log',

            // Custom Fonts
            'custom_fonts' => 'Eigene Schriften',
        ];
        
        $table_status = [];
        
        foreach ($tables as $table => $label) {
            $full_table = $prefix . $table;
            $status = [
                'name' => $table,
                'label' => $label,
                'exists' => false,
                'rows' => 0,
                'size' => 0,
                'status' => 'OK',
                'error' => null
            ];
            
            try {
                // Check if table exists
                $result = $pdo->query("SHOW TABLES LIKE '{$full_table}'");
                if ($result && $result->rowCount() > 0) {
                    $status['exists'] = true;
                    
                    // Count rows
                    $result = $pdo->query("SELECT COUNT(*) as total FROM `{$full_table}`");
                    if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $status['rows'] = (int)($row['total'] ?? 0);
                    }
                    
                    // Get table size
                    $result = $pdo->query("
                        SELECT 
                            data_length + index_length as size
                        FROM information_schema.TABLES 
                        WHERE table_schema = '" . DB_NAME . "'
                        AND table_name = '{$full_table}'
                    ");
                    
                    if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $status['size'] = (int)($row['size'] ?? 0);
                    }
                    
                    // Check table status
                    $result = $pdo->query("CHECK TABLE `{$full_table}`");
                    if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $status['status'] = $row['Msg_text'] ?? 'OK';
                    }
                } else {
                    $status['status'] = 'Missing';
                    $status['error'] = 'Tabelle existiert nicht';
                }
                
            } catch (\Exception $e) {
                $status['error'] = $e->getMessage();
                $status['status'] = 'Error';
            }
            
            $table_status[] = $status;
        }
        
        return $table_status;
    }
    
    /**
     * Check file and directory permissions
     */
    public function checkFilePermissions(): array {
        $base_path = dirname(dirname(__DIR__));
        
        $paths = [
            'uploads' => $base_path . '/uploads',
            'cache' => $base_path . '/cache',
            'logs' => $base_path . '/logs',
            'config' => $base_path . '/config',
            'assets/css' => $base_path . '/assets/css',
            'assets/js' => $base_path . '/assets/js',
            'assets/images' => $base_path . '/assets/images'
        ];
        
        $permissions = [];
        
        foreach ($paths as $name => $path) {
            $status = [
                'path' => $name,
                'full_path' => $path,
                'exists' => false,
                'readable' => false,
                'writable' => false,
                'permissions' => null,
                'status' => 'Error'
            ];
            
            if (file_exists($path)) {
                $status['exists'] = true;
                $status['readable'] = is_readable($path);
                $status['writable'] = is_writable($path);
                $status['permissions'] = substr(sprintf('%o', fileperms($path)), -4);
                
                if ($status['readable'] && $status['writable']) {
                    $status['status'] = 'OK';
                } elseif ($status['readable']) {
                    $status['status'] = 'Read-Only';
                } else {
                    $status['status'] = 'No Access';
                }
            } else {
                $status['status'] = 'Missing';
            }
            
            $permissions[] = $status;
        }
        
        return $permissions;
    }
    
    /**
     * Get directory sizes
     */
    public function getDirectorySizes(): array {
        $base_path = dirname(dirname(__DIR__));
        
        $directories = [
            'uploads' => $base_path . '/uploads',
            'cache' => $base_path . '/cache',
            'logs' => $base_path . '/logs',
            'assets' => $base_path . '/assets'
        ];
        
        $sizes = [];
        
        foreach ($directories as $name => $path) {
            $size = 0;
            
            if (is_dir($path)) {
                $size = $this->getDirectorySize($path);
            }
            
            $sizes[$name] = [
                'path' => $name,
                'size' => $size,
                'formatted' => $this->formatBytes($size)
            ];
        }
        
        return $sizes;
    }
    
    /**
     * Calculate directory size recursively
     */
    private function getDirectorySize(string $path): int {
        $size = 0;
        
        try {
            if (is_dir($path)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $size += $file->getSize();
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors (permission denied, etc.)
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable
     */
    public function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Get CMS statistics
     */
    public function getCMSStatistics(): array {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'total_pages' => 0,
            'total_sessions' => 0,
            'cache_entries' => 0,
            'failed_logins_today' => 0
        ];
        
        try {
            // Users
            $result = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}users`");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['total_users'] = (int)($row['total'] ?? 0);
            }
            
            $result = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}users` WHERE status = 'active'");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['active_users'] = (int)($row['total'] ?? 0);
            }
            
            // Pages
            $result = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}pages`");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['total_pages'] = (int)($row['total'] ?? 0);
            }
            
            // Sessions
            $result = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}sessions` WHERE expires_at > NOW()");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['total_sessions'] = (int)($row['total'] ?? 0);
            }
            
            // Cache
            $result = $pdo->query("SELECT COUNT(*) as total FROM `{$prefix}cache` WHERE expires_at > NOW()");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['cache_entries'] = (int)($row['total'] ?? 0);
            }
            
            // Failed logins today (Tabelle: login_attempts, enthält nur Fehlschläge)
            $result = $pdo->query("
                SELECT COUNT(*) as total 
                FROM `{$prefix}login_attempts` 
                WHERE DATE(attempted_at) = CURDATE()
            ");
            if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                $stats['failed_logins_today'] = (int)($row['total'] ?? 0);
            }
        } catch (\Exception $e) {
            // Log error but return partial stats
        }
        
        return $stats;
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): bool {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        try {
            $pdo->exec("DELETE FROM `{$prefix}cache`");
            
            // Also clear cache directory
            $cache_dir = dirname(dirname(__DIR__)) . '/cache';
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file); // M-03: kein @, is_file vorher geprüft
                    }
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear old sessions
     */
    public function clearOldSessions(): bool {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        try {
            $pdo->exec("DELETE FROM `{$prefix}sessions` WHERE expires_at < NOW()");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear failed login attempts older than 24h
     */
    public function clearOldFailedLogins(): bool {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        try {
            $pdo->exec("
                DELETE FROM `{$prefix}login_attempts` 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Repair database tables
     */
    public function repairTables(): array {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        $tables = [
            'users', 'roles', 'sessions', 'settings', 'pages', 
            'page_revisions', 'landing_sections', 'activity_log', 
            'cache', 'login_attempts'
        ];
        
        $results = [];
        
        foreach ($tables as $table) {
            $full_table = $prefix . $table;
            
            try {
                $result = $pdo->query("REPAIR TABLE `{$full_table}`");
                
                if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $results[$table] = [
                        'success' => true,
                        'message' => $row['Msg_text'] ?? 'OK'
                    ];
                } else {
                    $results[$table] = [
                        'success' => false,
                        'message' => 'Repair fehlgeschlagen'
                    ];
                }
            } catch (\Exception $e) {
                $results[$table] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Optimize database tables
     */
    public function optimizeTables(): array {
        $pdo = $this->db->getConnection();
        $prefix = $this->db->getPrefix();
        
        $tables = [
            'users', 'roles', 'sessions', 'settings', 'pages', 
            'page_revisions', 'landing_sections', 'activity_log', 
            'cache', 'login_attempts', 'plugins', 'plugin_meta'
        ];
        
        $results = [];
        
        foreach ($tables as $table) {
            $full_table = $prefix . $table;
            
            try {
                $result = $pdo->query("OPTIMIZE TABLE `{$full_table}`");
                
                if ($result && $row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $results[$table] = [
                        'success' => true,
                        'message' => $row['Msg_text'] ?? 'OK'
                    ];
                } else {
                    $results[$table] = [
                        'success' => false,
                        'message' => 'Optimierung fehlgeschlagen'
                    ];
                }
            } catch (\Exception $e) {
                $results[$table] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Get recent error logs
     */
    public function getErrorLogs(int $limit = 100): array {
        $log_file = dirname(dirname(__DIR__)) . '/logs/error.log';
        $logs = [];
        
        if (!file_exists($log_file)) {
            return $logs;
        }
        
        try {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if ($lines === false) {
                return $logs;
            }
            
            // Get last N lines
            $lines = array_slice($lines, -$limit);
            
            foreach ($lines as $line) {
                // Parse log line (format: [YYYY-MM-DD HH:MM:SS] TYPE: Message)
                if (preg_match('/^\[(.*?)\]\s+(\w+):\s+(.*)$/', $line, $matches)) {
                    $logs[] = [
                        'timestamp' => $matches[1],
                        'type' => $matches[2],
                        'message' => $matches[3]
                    ];
                } else {
                    $logs[] = [
                        'timestamp' => '',
                        'type' => 'UNKNOWN',
                        'message' => $line
                    ];
                }
            }
            
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return array_reverse($logs);
    }
    
    /**
     * Clear error logs
     */
    public function clearErrorLogs(): bool {
        $log_file = dirname(dirname(__DIR__)) . '/logs/error.log';
        
        try {
            if (file_exists($log_file)) {
                return file_put_contents($log_file, '') !== false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available CMS log files (daily rotation pattern cms-YYYY-MM-DD.log)
     *
     * @return array<string, string> Filename => full path, sorted newest first
     */
    public function getCmsLogFiles(): array
    {
        $logDir = defined('LOG_PATH') ? LOG_PATH : (ABSPATH . 'logs/');
        $files  = [];

        if (!is_dir($logDir)) {
            return $files;
        }

        $globResult = glob($logDir . 'cms-*.log');
        if ($globResult === false) {
            return $files;
        }

        foreach ($globResult as $path) {
            $files[basename($path)] = $path;
        }

        krsort($files); // newest date first

        return $files;
    }

    /**
     * Read CMS log entries from a specific log file.
     *
     * @param string $filename Log filename (must match cms-YYYY-MM-DD.log pattern)
     * @param int    $limit    Max number of entries
     * @return array<int, array{timestamp: string, level: string, channel: string, message: string}>
     */
    public function getCmsLogEntries(string $filename, int $limit = 200): array
    {
        // Validate filename to prevent path traversal
        if (!preg_match('/^cms-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
            return [];
        }

        $logDir  = defined('LOG_PATH') ? LOG_PATH : (ABSPATH . 'logs/');
        $logFile = $logDir . $filename;

        if (!file_exists($logFile)) {
            return [];
        }

        $entries = [];
        $lines   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        $lines = array_slice($lines, -$limit);

        foreach ($lines as $line) {
            // Logger format: [2026-02-22 14:30:00] [WARNING] [cms] Message {context}
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+\[(\w+)\]\s+\[(\w+)\]\s+(.*)$/', $line, $m)) {
                $entries[] = [
                    'timestamp' => $m[1],
                    'level'     => $m[2],
                    'channel'   => $m[3],
                    'message'   => $m[4],
                ];
            } else {
                $entries[] = [
                    'timestamp' => '',
                    'level'     => 'RAW',
                    'channel'   => '',
                    'message'   => $line,
                ];
            }
        }

        return array_reverse($entries);
    }

    /**
     * Delete a CMS log file.
     */
    public function clearCmsLogFile(string $filename): bool
    {
        if (!preg_match('/^cms-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
            return false;
        }

        $logDir  = defined('LOG_PATH') ? LOG_PATH : (ABSPATH . 'logs/');
        $logFile = $logDir . $filename;

        if (file_exists($logFile)) {
            return unlink($logFile);
        }

        return true;
    }
    
    /**
     * Get security status
     */
    public function getSecurityStatus(): array {
        $headerProfile = Security::instance()->getSecurityHeaderProfile();

        return [
            'debug_mode' => defined('CMS_DEBUG') && CMS_DEBUG ? 'Aktiv ⚠️' : 'Deaktiviert ✓',
            'display_errors' => ini_get('display_errors') ? 'Aktiv ⚠️' : 'Deaktiviert ✓',
            'session_secure' => ini_get('session.cookie_secure') ? 'Aktiv ✓' : 'Deaktiviert ⚠️',
            'session_httponly' => ini_get('session.cookie_httponly') ? 'Aktiv ✓' : 'Deaktiviert ⚠️',
            'session_samesite' => ini_get('session.cookie_samesite') ?: 'Nicht gesetzt ⚠️',
            'max_upload' => ini_get('upload_max_filesize'),
            'memory_limit' => ini_get('memory_limit'),
            'https_enabled' => $headerProfile['https'] ? 'Ja ✓' : 'Nein ⚠️',
            'csp_mode' => $headerProfile['csp_mode'] === 'enforced' ? 'Erzwungen ✓' : 'Report-Only ⚠️',
            'csp_nonce' => $headerProfile['csp_uses_nonce'] ? 'Aktiv ✓' : 'Nicht aktiv ⚠️',
            'trusted_types' => $headerProfile['trusted_types_enforced']
                ? 'Erzwungen ✓'
                : ($headerProfile['trusted_types_report_only'] ? 'Report-Only ⚠️' : 'Nicht aktiv ⚠️'),
            'hsts' => $headerProfile['hsts_enabled'] ? 'Aktiv ✓' : ($headerProfile['https'] ? 'Nicht aktiv ⚠️' : 'Nur mit HTTPS ℹ️'),
            'hsts_include_subdomains' => $headerProfile['hsts_include_subdomains'] ? 'Aktiv ✓' : ($headerProfile['https'] ? 'Nicht aktiv ⚠️' : 'Nur mit HTTPS ℹ️'),
            'hsts_preload' => $headerProfile['hsts_preload'] ? 'Aktiv ✓' : ($headerProfile['https'] ? 'Nicht aktiv ⚠️' : 'Nur mit HTTPS ℹ️'),
        ];
    }

    /**
     * Debug-only Runtime-/Query-Telemetrie für Diagnoseansichten.
     */
    public function getRuntimeTelemetry(): array
    {
        if (!defined('CMS_DEBUG') || !CMS_DEBUG) {
            return [
                'enabled' => false,
                'message' => 'Runtime-Telemetrie ist nur im Debug-Modus aktiv.',
            ];
        }

        return Debug::getRuntimeTelemetry();
    }
    
    /**
     * Run all system checks
     */
    public function runSystemCheck(): array {
        return [
            'system_info' => $this->getSystemInfo(),
            'database_status' => $this->getDatabaseStatus(),
            'table_status' => $this->checkDatabaseTables(),
            'file_permissions' => $this->checkFilePermissions(),
            'directory_sizes' => $this->getDirectorySizes(),
            'cms_statistics' => $this->getCMSStatistics(),
            'security_status' => $this->getSecurityStatus(),
            'runtime_telemetry' => $this->getRuntimeTelemetry()
        ];
    }
}
