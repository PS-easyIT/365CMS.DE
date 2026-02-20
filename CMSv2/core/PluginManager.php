<?php
/**
 * Plugin Manager
 * 
 * Handles plugin loading and management
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class PluginManager
{
    private static ?self $instance = null;
    private array $plugins = [];
    private array $activePlugins = [];
    
    /**
     * Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load all active plugins
     */
    public function loadPlugins(): void
    {
        $this->activePlugins = $this->getActivePlugins();
        
        foreach ($this->activePlugins as $plugin) {
            $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
            
            if (file_exists($pluginFile)) {
                require_once $pluginFile;
                
                // Call plugin init hook
                Hooks::doAction('plugin_loaded', $plugin);
            }
        }
        
        // All plugins loaded
        Hooks::doAction('plugins_loaded');
    }
    
    /**
     * Get all available plugins
     */
    public function getAvailablePlugins(): array
    {
        $plugins = [];
        $pluginDir = PLUGIN_PATH;
        
        if (!is_dir($pluginDir)) {
            return $plugins;
        }
        
        $directories = scandir($pluginDir);
        
        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === '.gitkeep') {
                continue;
            }
            
            $pluginFile = $pluginDir . $dir . '/' . $dir . '.php';
            
            if (file_exists($pluginFile)) {
                $data = $this->getPluginData($pluginFile);
                if ($data) {
                    $data['folder'] = $dir;
                    $data['active'] = in_array($dir, $this->activePlugins);
                    $plugins[$dir] = $data;
                }
            }
        }
        
        return $plugins;
    }
    
    /**
     * Get plugin data from file headers
     */
    private function getPluginData(string $file): array|false
    {
        $content = file_get_contents($file);
        if (!$content) {
            return false;
        }
        
        $data = [];
        $headers = [
            'name' => 'Plugin Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'requires' => 'Requires'
        ];
        
        foreach ($headers as $key => $header) {
            if (preg_match('/' . $header . ':\s*(.+)/i', $content, $matches)) {
                $data[$key] = trim($matches[1]);
            }
        }
        
        return $data ?: false;
    }
    
    /**
     * Get active plugins from database
     */
    public function getActivePlugins(): array
    {
        try {
            $db = Database::instance();
            $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'active_plugins' LIMIT 1");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result->option_value) {
                return json_decode($result->option_value, true) ?: [];
            }
            
            return [];
        } catch (\Exception $e) {
            error_log('PluginManager::getActivePlugins() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Activate plugin
     */
    public function activatePlugin(string $plugin): bool|string
    {
        $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
        
        if (!file_exists($pluginFile)) {
            return 'Plugin-Datei nicht gefunden.';
        }
        
        // Check if already active
        if (in_array($plugin, $this->activePlugins)) {
            return 'Plugin ist bereits aktiviert.';
        }
        
        // Add to active plugins
        $this->activePlugins[] = $plugin;
        $this->saveActivePlugins();
        
        // Load plugin
        require_once $pluginFile;
        
        // Call activation hook
        Hooks::doAction('plugin_activated', $plugin);
        
        return true;
    }
    
    /**
     * Deactivate plugin
     */
    public function deactivatePlugin(string $plugin): bool|string
    {
        if (!in_array($plugin, $this->activePlugins)) {
            return 'Plugin ist nicht aktiviert.';
        }
        
        // Remove from active plugins
        $this->activePlugins = array_diff($this->activePlugins, [$plugin]);
        $this->saveActivePlugins();
        
        // Call deactivation hook
        Hooks::doAction('plugin_deactivated', $plugin);
        
        return true;
    }
    
    /**
     * Delete plugin
     */
    public function deletePlugin(string $plugin): bool|string
    {
        // Check if plugin is active
        if (in_array($plugin, $this->activePlugins)) {
            return 'Plugin muss zuerst deaktiviert werden.';
        }
        
        $pluginDir = PLUGIN_PATH . $plugin;
        
        if (!is_dir($pluginDir)) {
            return 'Plugin-Verzeichnis nicht gefunden.';
        }
        
        // Call uninstall hook before deletion
        Hooks::doAction('plugin_before_delete', $plugin);
        
        // Delete plugin directory recursively
        if (!$this->deleteDirectory($pluginDir)) {
            return 'Plugin-Verzeichnis konnte nicht gelöscht werden.';
        }
        
        // Call deletion hook
        Hooks::doAction('plugin_deleted', $plugin);
        
        return true;
    }
    
    /**
     * Recursively delete directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Install plugin from uploaded ZIP file
     */
    public function installPlugin(array $file): bool|string
    {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Ungültige Datei.';
        }
        
        // Check file type
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, ['application/zip', 'application/x-zip-compressed'])) {
            return 'Datei muss eine ZIP-Datei sein.';
        }
        
        // Check file size (max 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            return 'Datei ist zu groß (max. 50MB).';
        }
        
        $zip = new \ZipArchive();
        
        if ($zip->open($file['tmp_name']) !== true) {
            return 'ZIP-Datei konnte nicht geöffnet werden.';
        }
        
        // Extract to plugins directory
        $extractPath = PLUGIN_PATH;
        
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            return 'Plugin konnte nicht entpackt werden.';
        }
        
        $zip->close();
        
        // Call installation hook
        Hooks::doAction('plugin_installed');
        
        return true;
    }
    
    /**
     * Save active plugins to database
     */
    private function saveActivePlugins(): void
    {
        try {
            $db = Database::instance();
            $value = json_encode(array_values($this->activePlugins));
            
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->getPrefix()}settings WHERE option_name = 'active_plugins'");
            
            if (!$stmt) {
                return;
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result->count > 0) {
                $db->update('settings', ['option_value' => $value], ['option_name' => 'active_plugins']);
            } else {
                $db->insert('settings', ['option_name' => 'active_plugins', 'option_value' => $value]);
            }
        } catch (\Exception $e) {
            error_log('PluginManager::saveActivePlugins() Error: ' . $e->getMessage());
        }
    }
}
