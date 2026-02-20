<?php
/**
 * Update Service
 * 
 * Manages system, plugin and theme updates via GitHub API
 * Repository: https://github.com/PS-easyIT/365CMS.DE
 * 
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\PluginManager;

if (!defined('ABSPATH')) {
    exit;
}

class UpdateService
{
    private static ?self $instance = null;
    private Database $db;
    
    private const GITHUB_REPO = 'PS-easyIT/365CMS.DE';
    private const GITHUB_API = 'https://api.github.com';
    private const CACHE_DURATION = 3600; // 1 hour
    
    /**
     * Singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->db = Database::instance();
    }
    
    /**
     * Check for CMS core updates via GitHub
     */
    public function checkCoreUpdates(): array
    {
        $currentVersion = CMS_VERSION ?? '2.0.0';
        
        // Check cache first
        $cacheKey = 'core_update_check';
        $cached = $this->getCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Fetch latest release from GitHub
        $url = self::GITHUB_API . '/repos/' . self::GITHUB_REPO . '/releases/latest';
        $release = $this->fetchGitHubData($url);
        
        if (!$release) {
            // No release found, return current version
            return [
                'current_version' => $currentVersion,
                'latest_version' => $currentVersion,
                'update_available' => false,
                'changelog' => [],
                'release_date' => '',
                'download_url' => '',
            ];
        }
        
        $latestVersion = ltrim($release['tag_name'] ?? $currentVersion, 'v');
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        
        $result = [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'changelog' => $this->parseChangelog($release['body'] ?? ''),
            'release_date' => isset($release['published_at']) 
                ? date('Y-m-d', strtotime($release['published_at'])) 
                : '',
            'download_url' => $release['zipball_url'] ?? '',
            'release_notes' => $release['body'] ?? '',
        ];
        
        // Cache result
        $this->setCache($cacheKey, $result);
        
        return $result;
    }
    
    /**
     * Check for plugin updates
     */
    public function checkPluginUpdates(): array
    {
        $pluginManager = PluginManager::instance();
        $plugins = $pluginManager->getAvailablePlugins();
        
        $updates = [];
        
        foreach ($plugins as $folder => $plugin) {
            $currentVersion = $plugin['version'] ?? '1.0.0';
            
            // Check if plugin has update URL in metadata
            if (isset($plugin['update_url']) && !empty($plugin['update_url'])) {
                $updateInfo = $this->fetchPluginUpdate($plugin['update_url']);
                
                if ($updateInfo && version_compare($updateInfo['version'], $currentVersion, '>')) {
                    $updates[$folder] = [
                        'name' => $plugin['name'],
                        'current_version' => $currentVersion,
                        'new_version' => $updateInfo['version'],
                        'update_available' => true,
                        'download_url' => $updateInfo['download_url'] ?? '',
                        'changelog' => $updateInfo['changelog'] ?? '',
                    ];
                }
            }
        }
        
        return $updates;
    }
    
    /**
     * Check for theme updates
     */
    public function checkThemeUpdates(): array
    {
        // Get current theme
        $themeDir = ABSPATH . 'themes/default';
        $themeJsonFile = $themeDir . '/theme.json';
        
        if (!file_exists($themeJsonFile)) {
            return [];
        }
        
        $themeData = json_decode(file_get_contents($themeJsonFile), true);
        $currentVersion = $themeData['version'] ?? '1.0.0';
        
        // Check GitHub for theme updates
        $url = self::GITHUB_API . '/repos/' . self::GITHUB_REPO . '/contents/themes/default/theme.json';
        $response = $this->fetchGitHubData($url);
        
        if (!$response || !isset($response['content'])) {
            return [];
        }
        
        // Decode base64 content
        $remoteThemeData = json_decode(base64_decode($response['content']), true);
        $latestVersion = $remoteThemeData['version'] ?? $currentVersion;
        
        $updateAvailable = version_compare($latestVersion, $currentVersion, '>');
        
        return [
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'update_available' => $updateAvailable,
            'changelog' => $remoteThemeData['changelog'] ?? [],
        ];
    }
    
    /**
     * Fetch data from GitHub API
     */
    private function fetchGitHubData(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: 365CMS-UpdateChecker/1.0',
                    'Accept: application/vnd.github.v3+json',
                ],
                'timeout' => 10,
            ],
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log('UpdateService: Failed to fetch from GitHub: ' . $url);
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('UpdateService: Invalid JSON from GitHub: ' . json_last_error_msg());
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            error_log('UpdateService::fetchGitHubData() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch plugin update information
     */
    private function fetchPluginUpdate(string $url): ?array
    {
        try {
            $response = @file_get_contents($url);
            
            if ($response === false) {
                return null;
            }
            
            return json_decode($response, true);
        } catch (\Exception $e) {
            error_log('UpdateService::fetchPluginUpdate() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse changelog from release notes
     */
    private function parseChangelog(string $body): array
    {
        $lines = explode("\n", $body);
        $changelog = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match lines starting with - or * (bullet points)
            if (preg_match('/^[-*]\s+(.+)/', $line, $matches)) {
                $changelog[] = trim($matches[1]);
            }
        }
        
        return $changelog;
    }
    
    /**
     * Get update history (REAL DATA)
     */
    public function getUpdateHistory(int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM {$this->db->getPrefix()}settings
                WHERE option_name LIKE 'update_log_%'
                ORDER BY option_name DESC
                LIMIT ?
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute([$limit]);
            
            $history = [];
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $data = json_decode($row->option_value, true);
                if ($data) {
                    $history[] = $data;
                }
            }
            
            return $history;
        } catch (\Exception $e) {
            error_log('UpdateService::getUpdateHistory() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log update
     */
    public function logUpdate(string $type, string $name, string $version): bool
    {
        $logEntry = [
            'type' => $type,
            'name' => $name,
            'version' => $version,
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $_SESSION['user_id'] ?? 'System',
        ];
        
        $optionName = 'update_log_' . time();
        
        try {
            return $this->db->insert('settings', [
                'option_name' => $optionName,
                'option_value' => json_encode($logEntry),
                'autoload' => 0,
            ]) !== false;
        } catch (\Exception $e) {
            error_log('UpdateService::logUpdate() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get system requirements (REAL DATA)
     */
    public function getSystemRequirements(): array
    {
        return [
            'php_version' => [
                'required' => '8.1.0',
                'current' => PHP_VERSION,
                'met' => version_compare(PHP_VERSION, '8.1.0', '>='),
            ],
            'mysql_version' => [
                'required' => '5.7.0',
                'current' => $this->getMySQLVersion(),
                'met' => version_compare($this->getMySQLVersion(), '5.7.0', '>='),
            ],
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'zip' => extension_loaded('zip'),
                'curl' => extension_loaded('curl'),
                'gd' => extension_loaded('gd'),
            ],
            'permissions' => [
                'uploads_writable' => is_writable(ABSPATH . 'uploads'),
                'cache_writable' => is_writable(ABSPATH . 'cache'),
                'logs_writable' => is_writable(ABSPATH . 'logs'),
            ],
        ];
    }
    
    /**
     * Get MySQL version (REAL DATA)
     */
    private function getMySQLVersion(): string
    {
        try {
            $stmt = $this->db->prepare("SELECT VERSION() as version");
            
            if (!$stmt) {
                return 'Unknown';
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($result && $result->version) {
                // Extract version number (remove MariaDB suffix etc.)
                preg_match('/^\d+\.\d+\.\d+/', $result->version, $matches);
                return $matches[0] ?? $result->version;
            }
            
            return 'Unknown';
        } catch (\Exception $e) {
            error_log('UpdateService::getMySQLVersion() Error: ' . $e->getMessage());
            return 'Unknown';
        }
    }
    
    /**
     * Get cached data
     */
    private function getCache(string $key): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cache_value 
                FROM {$this->db->getPrefix()}cache
                WHERE cache_key = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            
            if (!$stmt) {
                return null;
            }
            
            $stmt->execute([$key]);
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if ($result) {
                return json_decode($result->cache_value, true);
            }
            
            return null;
        } catch (\Exception $e) {
            error_log('UpdateService::getCache() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Set cache data
     */
    private function setCache(string $key, array $data): bool
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + self::CACHE_DURATION);
            
            // Delete existing cache
            $stmt = $this->db->prepare("DELETE FROM {$this->db->getPrefix()}cache WHERE cache_key = ?");
            if ($stmt) {
                $stmt->execute([$key]);
            }
            
            // Insert new cache
            return $this->db->insert('cache', [
                'cache_key' => $key,
                'cache_value' => json_encode($data),
                'expires_at' => $expiresAt,
            ]) !== false;
        } catch (\Exception $e) {
            error_log('UpdateService::setCache() Error: ' . $e->getMessage());
            return false;
        }
    }
}

