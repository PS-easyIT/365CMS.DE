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

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load all active plugins (C-07)
     */
    public function loadPlugins(): void
    {
        $this->activePlugins = $this->getActivePlugins();
        $disabledPlugins = [];

        foreach ($this->activePlugins as $plugin) {
            $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
            if (!file_exists($pluginFile)) {
                continue;
            }
            try {
                require_once $pluginFile;
                Hooks::doAction('plugin_loaded', $plugin);
            } catch (\Throwable $e) {
                $context = [
                    'plugin'    => $plugin,
                    'error'     => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'exception' => get_class($e),
                ];
                error_log(sprintf(
                    'PluginManager [C-07/H-25]: Fatal-Error beim Laden von "%s" – Plugin deaktiviert. Fehler: %s in %s:%d',
                    $plugin, $e->getMessage(), $e->getFile(), $e->getLine()
                ));
                if (class_exists(AuditLogger::class)) {
                    AuditLogger::instance()->log(
                        'plugin', 'plugin.load_error',
                        sprintf('Plugin "%s" verursachte einen Fatal-Error und wurde automatisch deaktiviert.', $plugin),
                        'plugin', null, $context, 'critical'
                    );
                }
                $disabledPlugins[] = $plugin;
            }
        }

        if (!empty($disabledPlugins)) {
            $this->activePlugins = array_values(array_diff($this->activePlugins, $disabledPlugins));
            $this->saveActivePlugins();
        }

        Hooks::doAction('plugins_loaded');
    }

    /**
     * Get all available plugins
     */
    public function getAvailablePlugins(): array
    {
        $plugins   = [];
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
                    $plugins[$dir]  = $data;
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

        $data    = [];
        $headers = [
            'name'        => 'Plugin Name',
            'description' => 'Description',
            'version'     => 'Version',
            'author'      => 'Author',
            'requires'    => 'Requires',
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
            $db   = Database::instance();
            $stmt = $db->prepare(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'active_plugins' LIMIT 1"
            );
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
     * Activate plugin (C-08)
     */
    public function activatePlugin(string $plugin): bool|string
    {
        $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
        if (!file_exists($pluginFile)) {
            return 'Plugin-Datei nicht gefunden.';
        }

        if (in_array($plugin, $this->activePlugins)) {
            return 'Plugin ist bereits aktiviert.';
        }

        $depCheck = $this->checkDependencies($plugin);
        if ($depCheck !== true) {
            return $depCheck;
        }

        $scanResult = $this->securityScanPlugin($plugin);
        if ($scanResult !== true) {
            return $scanResult;
        }

        $this->activePlugins[] = $plugin;
        $this->saveActivePlugins();

        require_once $pluginFile;
        $this->runLifecycleCallback($plugin, 'activate');
        Hooks::doAction('plugin_activated', $plugin);
        AuditLogger::instance()->pluginAction('activate', $plugin);

        return true;
    }

    /**
     * Check plugin dependencies (H-18)
     */
    private function checkDependencies(string $plugin): bool|string
    {
        $pluginFile = PLUGIN_PATH . $plugin . '/' . $plugin . '.php';
        $data       = $this->getPluginData($pluginFile);

        if (!$data || empty($data['requires'])) {
            return true;
        }

        $required = array_filter(array_map('trim', explode(',', $data['requires'])));
        $missing  = [];

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
     * Security scan before activation (C-08).
     *
     * Uses regex with lookbehind to distinguish PHP built-in functions
     * (exec, system …) from harmless OOP method calls ($pdo->exec(), etc.).
     *
     * @return true|string  true = clean, string = error message
     */
    private function securityScanPlugin(string $plugin): bool|string
    {
        $pluginDir = PLUGIN_PATH . $plugin . '/';
        if (!is_dir($pluginDir)) {
            return 'Plugin-Verzeichnis nicht gefunden.';
        }

        // Lookbehind (?<!->) / (?<!::) excludes method/static calls like
        // $pdo->exec(), PDO::exec(), $stmt->execute() etc.
        // \b prevents partial matches like "hexec".
        $dangerousFunctions = [
            'eval'       => '/(?<!->)(?<!::)\beval\s*\(/i',
            'exec'       => '/(?<!->)(?<!::)\bexec\s*\(/i',
            'shell_exec' => '/(?<!->)(?<!::)\bshell_exec\s*\(/i',
            'system'     => '/(?<!->)(?<!::)\bsystem\s*\(/i',
            'passthru'   => '/(?<!->)(?<!::)\bpassthru\s*\(/i',
            'popen'      => '/(?<!->)(?<!::)\bpopen\s*\(/i',
            'proc_open'  => '/(?<!->)(?<!::)\bproc_open\s*\(/i',
            'pcntl_exec' => '/(?<!->)(?<!::)\bpcntl_exec\s*\(/i',
        ];

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

            // 1. Syntax check
            try {
                token_get_all($content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                return sprintf(
                    'PHP-Syntaxfehler in %s: %s',
                    str_replace($pluginDir, '', $file->getPathname()),
                    $e->getMessage()
                );
            }

            // 2. Dangerous function scan (regex, method-call-aware)
            foreach ($dangerousFunctions as $funcName => $pattern) {
                if (preg_match($pattern, $content) === 1) {
                    error_log(sprintf(
                        'PluginManager [C-08]: Dangerous function "%s" in plugin "%s" (%s). Activation aborted.',
                        $funcName, $plugin,
                        str_replace($pluginDir, '', $file->getPathname())
                    ));
                    return sprintf(
                        'Sicherheitswarnung: Plugin enthält potenziell gefährliche Funktion „%s" in %s. Aktivierung abgebrochen.',
                        $funcName,
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

        $this->activePlugins = array_diff($this->activePlugins, [$plugin]);
        $this->saveActivePlugins();
        $this->runLifecycleCallback($plugin, 'deactivate');
        Hooks::doAction('plugin_deactivated', $plugin);
        AuditLogger::instance()->pluginAction('deactivate', $plugin);

        return true;
    }

    /**
     * Delete plugin
     */
    public function deletePlugin(string $plugin): bool|string
    {
        if (in_array($plugin, $this->activePlugins)) {
            return 'Plugin muss zuerst deaktiviert werden.';
        }

        $pluginDir  = PLUGIN_PATH . $plugin;
        if (!is_dir($pluginDir)) {
            return 'Plugin-Verzeichnis nicht gefunden.';
        }

        $pluginFile = $pluginDir . '/' . $plugin . '.php';
        if (file_exists($pluginFile)) {
            if (!in_array(realpath($pluginFile), get_included_files(), true)) {
                try {
                    include_once $pluginFile;
                } catch (\Throwable $e) {
                    error_log('PluginManager [M-13]: Plugin für Uninstall nicht ladbar: ' . $e->getMessage());
                }
            }
            $this->runLifecycleCallback($plugin, 'uninstall');
        }

        Hooks::doAction('plugin_before_delete', $plugin);

        if (!$this->deleteDirectory($pluginDir)) {
            return 'Plugin-Verzeichnis konnte nicht gelöscht werden.';
        }

        Hooks::doAction('plugin_deleted', $plugin);

        return true;
    }

    /**
     * Run plugin lifecycle callback (M-13)
     */
    private function runLifecycleCallback(string $plugin, string $lifecycle): void
    {
        $callbackName = str_replace(['-', ' '], '_', $plugin) . '_' . $lifecycle;

        if (!function_exists($callbackName)) {
            return;
        }

        try {
            $callbackName();
        } catch (\Throwable $e) {
            error_log(sprintf(
                'PluginManager [M-13]: Lifecycle-Callback "%s" für Plugin "%s" fehlgeschlagen: %s',
                $callbackName, $plugin, $e->getMessage()
            ));
            if (class_exists(AuditLogger::class)) {
                AuditLogger::instance()->log(
                    'plugin', 'plugin.lifecycle_error',
                    sprintf('Lifecycle-Callback "%s" für Plugin "%s" fehlgeschlagen.', $lifecycle, $plugin),
                    'plugin', null,
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
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'Ungültige Datei.';
        }

        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, ['application/zip', 'application/x-zip-compressed'])) {
            return 'Datei muss eine ZIP-Datei sein.';
        }

        if ($file['size'] > 50 * 1024 * 1024) {
            return 'Datei ist zu groß (max. 50MB).';
        }

        $zip = new \ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            return 'ZIP-Datei konnte nicht geöffnet werden.';
        }

        if (!$zip->extractTo(PLUGIN_PATH)) {
            $zip->close();
            return 'Plugin konnte nicht entpackt werden.';
        }

        $zip->close();
        Hooks::doAction('plugin_installed');

        return true;
    }

    /**
     * Save active plugins to database
     */
    private function saveActivePlugins(): void
    {
        try {
            $db    = Database::instance();
            $value = json_encode(array_values($this->activePlugins));
            $stmt  = $db->prepare(
                "SELECT COUNT(*) as count FROM {$db->getPrefix()}settings WHERE option_name = 'active_plugins'"
            );
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