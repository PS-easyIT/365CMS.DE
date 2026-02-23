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
     * Load all active plugins (C-07: Einzelnes Plugin-Laden via try/catch gesichert)
     */
    public function loadPlugins(): void
    {
        $this->activePlugins = $this->getActivePlugins();
        $disabledPlugins     = [];

        foreach ($this->activePlugins as $plugin) {
            $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';

            if (!file_exists($pluginFile)) {
                continue;
            }

            try {
                require_once $pluginFile;

                // Call plugin init hook
                Hooks::doAction('plugin_loaded', $plugin);
            } catch (\Throwable $e) {
                // H-25: Strukturiertes Fehler-Logging mit Kontext-Metadaten
                $context = [
                    'plugin'    => $plugin,
                    'error'     => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'exception' => get_class($e),
                ];

                error_log(sprintf(
                    'PluginManager [C-07/H-25]: Fatal-Error beim Laden von "%s" – Plugin deaktiviert. Fehler: %s in %s:%d',
                    $plugin,
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));

                // Strukturiertes Audit-Log (H-25)
                if (class_exists(AuditLogger::class)) {
                    AuditLogger::instance()->log(
                        'plugin',
                        'plugin.load_error',
                        sprintf('Plugin "%s" verursachte einen Fatal-Error beim Laden und wurde automatisch deaktiviert.', $plugin),
                        'plugin',
                        null,
                        $context,
                        'critical'
                    );
                }

                $disabledPlugins[] = $plugin;
            }
        }

        // Auto-Disable fehlerhafte Plugins + in DB speichern
        if (!empty($disabledPlugins)) {
            $this->activePlugins = array_values(array_diff($this->activePlugins, $disabledPlugins));
            $this->saveActivePlugins();
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
     * Activate plugin (C-08: Sicherheitsscan vor Aktivierung)
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

        // H-18: Abhängigkeits-Check
        $depCheck = $this->checkDependencies($plugin);
        if ($depCheck !== true) {
            return $depCheck;
        }

        // Sicherheitsscan vor Aktivierung (C-08)
        $scanResult = $this->securityScanPlugin($plugin);
        if ($scanResult !== true) {
            return $scanResult;
        }

        // Add to active plugins
        $this->activePlugins[] = $plugin;
        $this->saveActivePlugins();

        // Load plugin
        require_once $pluginFile;

        // M-13: Lifecycle-Hook – Plugin kann eigene Aktivierungslogik
        //        (z. B. DB-Tabellen anlegen) via <plugin>_activate() bereitstellen.
        $this->runLifecycleCallback($plugin, 'activate');

        // Call activation hook
        Hooks::doAction('plugin_activated', $plugin);

        // H-01: Plugin-Aktivierung protokollieren
        AuditLogger::instance()->pluginAction('activate', $plugin);

        return true;
    }

    /**
     * H-18: Prüft, ob alle im Plugin-Header deklarierten Abhängigkeiten (Feld "Requires")
     * bereits aktiv sind, bevor ein Plugin aktiviert wird.
     *
     * Format im Plugin-Header:
     *   Requires: plugin-a, plugin-b
     *
     * @return true|string  true = alle Abhängigkeiten erfüllt, string = Fehlermeldung
     */
    private function checkDependencies(string $plugin): bool|string
    {
        $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
        $data = $this->getPluginData($pluginFile);

        if (!$data || empty($data['requires'])) {
            return true; // keine Abhängigkeiten deklariert
        }

        $required = array_map('trim', explode(',', $data['requires']));
        $required = array_filter($required); // Leerzeichen-Einträge entfernen

        $missing = [];
        foreach ($required as $dep) {
            if (!in_array($dep, $this->activePlugins, true)) {
                $missing[] = $dep;
            }
        }

        if (!empty($missing)) {
            return sprintf(
                'Fehlende Abhängigkeit(en): %s. Bitte zuerst aktivieren.',
                implode(', ', $missing)
            );
        }

        return true;
    }

    /**
     * Sicherheitsscan eines Plugins vor der Aktivierung (C-08)
     *
     * Prüft:
     *  - PHP-Syntaxfehler aller .php-Dateien via token_get_all(TOKEN_PARSE)
     *  - Vorkommen gefährlicher Funktionen (eval, exec, system …)
     *
     * @return true|string  true = OK, string = Fehlermeldung
     */
    private function securityScanPlugin(string $plugin): bool|string
    {
        $pluginDir = PLUGIN_PATH . $plugin . '/';
        if (!is_dir($pluginDir)) {
            return 'Plugin-Verzeichnis nicht gefunden.';
        }

        $dangerousFunctions = [
            'eval(',
            'shell_exec(',
            'exec(',
            'system(',
            'passthru(',
            'popen(',
            'proc_open(',
            'pcntl_exec(',
        ];

        // Alle PHP-Dateien im Plugin-Verzeichnis sammeln
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            // --- 1. PHP-Syntaxprüfung ---
            try {
                token_get_all($content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                return sprintf(
                    'PHP-Syntaxfehler in %s: %s',
                    str_replace($pluginDir, '', $file->getPathname()),
                    $e->getMessage()
                );
            }

            // --- 2. Scan auf gefährliche Funktionen ---
            foreach ($dangerousFunctions as $func) {
                if (stripos($content, $func) !== false) {
                    error_log(sprintf(
                        'PluginManager [C-08]: Gefährliche Funktion "%s" in Plugin "%s" (%s) gefunden. Aktivierung abgebrochen.',
                        $func,
                        $plugin,
                        str_replace($pluginDir, '', $file->getPathname())
                    ));
                    return sprintf(
                        'Sicherheitswarnung: Plugin enthält potenziell gefährliche Funktion „%s“ in %s. Aktivierung abgebrochen.',
                        rtrim($func, '('),
                        str_replace($pluginDir, '', $file->getPathname())
                    );
                }
            }
        }

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

        // M-13: Lifecycle-Hook Deaktivierung (Plugin läuft noch im Speicher)
        $this->runLifecycleCallback($plugin, 'deactivate');
        
        // Call deactivation hook
        Hooks::doAction('plugin_deactivated', $plugin);

        // H-01: Plugin-Deaktivierung protokollieren
        AuditLogger::instance()->pluginAction('deactivate', $plugin);
        
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
        
        // M-13: Lifecycle-Hook Uninstall – Plugin-Datei laden um Callback aufzurufen
        $pluginFile = $pluginDir . '/' . $plugin . '.php';
        if (file_exists($pluginFile)) {
            // Plugin einmalig includen (falls noch nicht geladen)
            if (!in_array(realpath($pluginFile), get_included_files(), true)) {
                try {
                    include_once $pluginFile; // M-03: try/catch statt @include
                } catch (\Throwable $e) {
                    error_log('PluginManager [M-13]: Plugin für Uninstall nicht ladbar: ' . $e->getMessage());
                }
            }
            $this->runLifecycleCallback($plugin, 'uninstall');
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
     * M-13: Ruft den Plugin-Lifecycle-Callback auf (falls definiert).
     *
     * Konvention: Plugin-Datei kann folgende Funktionen definieren:
     *   {plugin_folder}_activate()   – Läuft nach Aktivierung (z. B. CREATE TABLE)
     *   {plugin_folder}_deactivate() – Läuft nach Deaktivierung (z. B. geplante Tasks entfernen)
     *   {plugin_folder}_uninstall()  – Läuft vor Löschung (z. B. DROP TABLE, Optionen löschen)
     *
     * Der Callback wird in try/catch gewrappt, damit ein Fehler darin
     * den Haupt-Aktivierungs/Deaktivierungsfluss nicht blockiert.
     *
     * @param string $plugin   Plugin-Ordnername (Slug)
     * @param string $lifecycle 'activate' | 'deactivate' | 'uninstall'
     */
    private function runLifecycleCallback(string $plugin, string $lifecycle): void
    {
        // Callback-Name: z. B. cms_experts_activate
        $callbackName = str_replace(['-', ' '], '_', $plugin) . '_' . $lifecycle;

        if (!function_exists($callbackName)) {
            return; // kein Callback definiert – kein Fehler
        }

        try {
            $callbackName();
        } catch (\Throwable $e) {
            error_log(sprintf(
                'PluginManager [M-13]: Lifecycle-Callback "%s" für Plugin "%s" fehlgeschlagen: %s',
                $callbackName,
                $plugin,
                $e->getMessage()
            ));

            if (class_exists(AuditLogger::class)) {
                AuditLogger::instance()->log(
                    'plugin',
                    'plugin.lifecycle_error',
                    sprintf('Lifecycle-Callback "%s" für Plugin "%s" fehlgeschlagen.', $lifecycle, $plugin),
                    'plugin',
                    null,
                    ['plugin' => $plugin, 'lifecycle' => $lifecycle, 'error' => $e->getMessage()],
                    'warning'
                );
            }
        }
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
