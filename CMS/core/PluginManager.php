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
    private const PROTECTED_PLUGINS = ['cms-importer'];

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
     * Prüft ob ein Plugin aktiv ist.
     *
     * @param string $slug Plugin-Slug (z. B. 'cms-companies')
     */
    public function isPluginActive(string $slug): bool
    {
        return in_array($slug, $this->activePlugins, true);
    }

    public function isProtectedPlugin(string $slug): bool
    {
        $slug = strtolower(trim($slug));

        return in_array($slug, self::PROTECTED_PLUGINS, true);
    }

    /**
     * Load all active plugins (C-07)
     */
    public function loadPlugins(): void
    {
        $this->activePlugins = $this->getActivePlugins();
        $availableBootstrapFiles = $this->getPluginBootstrapMap();
        $disabledPlugins = array_values(array_diff($this->activePlugins, array_keys($availableBootstrapFiles)));

        foreach ($disabledPlugins as $pluginSlug) {
            $context = [
                'plugin' => $pluginSlug,
                'expected_file' => (string) PLUGIN_PATH . $pluginSlug . '/' . $pluginSlug . '.php',
                'plugin_path' => defined('PLUGIN_PATH') ? (string) PLUGIN_PATH : null,
            ];
            error_log(sprintf(
                'PluginManager [C-07/H-25]: Aktives Plugin "%s" wurde unter "%s" nicht gefunden – Plugin wird deaktiviert.',
                $pluginSlug,
                (string) PLUGIN_PATH . $pluginSlug . '/' . $pluginSlug . '.php'
            ));
            if (class_exists(AuditLogger::class)) {
                AuditLogger::instance()->log(
                    'plugin',
                    'plugin.missing_file',
                    sprintf('Aktives Plugin "%s" fehlt im Laufzeitordner und wurde automatisch deaktiviert.', $pluginSlug),
                    'plugin',
                    null,
                    $context,
                    'critical'
                );
            }
        }

        foreach ($availableBootstrapFiles as $pluginSlug => $pluginFile) {
            if (!in_array($pluginSlug, $this->activePlugins, true)) {
                continue;
            }

            try {
                require_once $pluginFile;
                Hooks::doAction('plugin_loaded', $pluginSlug);
            } catch (\Throwable $e) {
                $context = [
                    'plugin'    => $pluginSlug,
                    'error'     => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'exception' => get_class($e),
                ];
                error_log(sprintf(
                    'PluginManager [C-07/H-25]: Fatal-Error beim Laden von "%s" – Plugin deaktiviert. Fehler: %s in %s:%d',
                    $pluginSlug, $e->getMessage(), $e->getFile(), $e->getLine()
                ));
                if (class_exists(AuditLogger::class)) {
                    AuditLogger::instance()->log(
                        'plugin', 'plugin.load_error',
                        sprintf('Plugin "%s" verursachte einen Fatal-Error und wurde automatisch deaktiviert.', $pluginSlug),
                        'plugin', null, $context, 'critical'
                    );
                }
                $disabledPlugins[] = $pluginSlug;
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
            'requires_plugins' => 'Requires Plugins',
            'requires_cms' => 'Requires CMS',
        ];

        foreach ($headers as $key => $header) {
            if (preg_match('/' . preg_quote($header, '/') . ':\s*(.+)/i', $content, $matches)) {
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
            $plugins = [];
            if ($result && $result->option_value) {
                $plugins = Json::decodeArray($result->option_value ?? null, []);
            }

            if (!is_array($plugins)) {
                $plugins = [];
            }

            $plugins = array_values(array_unique(array_filter(array_map(
                static fn($plugin): string => strtolower(trim((string) $plugin)),
                $plugins
            ))));

            return $plugins;
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
        $pluginFile = $this->resolvePluginBootstrapFile($plugin);
        if ($pluginFile === '' || !file_exists($pluginFile)) {
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
        $updateData = $this->getPluginUpdateData($plugin);

        $cmsRequirement = $this->resolveCmsRequirement($data ?: [], $updateData);
        if ($cmsRequirement !== null && defined('CMS_VERSION') && version_compare(CMS_VERSION, $cmsRequirement, '<')) {
            return sprintf(
                'Benötigt 365CMS %s oder höher. Aktuell installiert: %s.',
                $cmsRequirement,
                CMS_VERSION
            );
        }

        if (!$data) {
            return true;
        }

        $required = $this->resolvePluginDependencies($data);
        if ($required === []) {
            return true;
        }

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
     * @return array<string, mixed>
     */
    private function getPluginUpdateData(string $plugin): array
    {
        $pluginDir = $this->resolvePluginDirectory($plugin);
        if ($pluginDir === '') {
            return [];
        }

        $updateFile = $pluginDir . DIRECTORY_SEPARATOR . 'update.json';
        if (!file_exists($updateFile)) {
            return [];
        }

        $raw = file_get_contents($updateFile);
        if ($raw === false || $raw === '') {
            return [];
        }

        return Json::decodeArray($raw, []);
    }

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    private function resolvePluginDependencies(array $data): array
    {
        $raw = (string) ($data['requires_plugins'] ?? $data['requires'] ?? '');
        if ($raw === '') {
            return [];
        }

        $required = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $token) {
            $slug = $this->normalizeDependencySlug($token);
            if ($slug === '') {
                continue;
            }
            $required[] = $slug;
        }

        return array_values(array_unique($required));
    }

    /**
     * @param array<string, mixed> $headerData
     * @param array<string, mixed> $updateData
     */
    private function resolveCmsRequirement(array $headerData, array $updateData): ?string
    {
        $candidates = [
            (string) ($headerData['requires_cms'] ?? ''),
            (string) ($updateData['requires_cms'] ?? ''),
            (string) ($updateData['min_cms_version'] ?? ''),
        ];

        if (!empty($headerData['requires']) && empty($headerData['requires_plugins']) && $this->looksLikeVersionConstraint((string) $headerData['requires'])) {
            $candidates[] = (string) $headerData['requires'];
        }

        foreach ($candidates as $candidate) {
            $version = $this->extractVersionNumber($candidate);
            if ($version !== null) {
                return $version;
            }
        }

        return null;
    }

    private function normalizeDependencySlug(string $dependency): string
    {
        $dependency = trim($dependency);
        if ($dependency === '' || $this->looksLikeVersionConstraint($dependency)) {
            return '';
        }

        if (preg_match('/^([a-z0-9][a-z0-9_-]*)/i', $dependency, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return '';
    }

    private function looksLikeVersionConstraint(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return preg_match('/^(?:365cms\s*)?(?:[><=~^]+\s*)?v?\d+(?:\.\d+)*(?:\+)?$/i', $value) === 1;
    }

    private function extractVersionNumber(string $value): ?string
    {
        if (preg_match('/\d+(?:\.\d+)+/', $value, $matches) === 1) {
            return $matches[0];
        }

        return null;
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
        $pluginDir = $this->resolvePluginDirectory($plugin);
        if ($pluginDir === '' || !is_dir($pluginDir)) {
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
        if ($this->isProtectedPlugin($plugin)) {
            return 'Das mitgelieferte Kern-Plugin „cms-importer“ kann nicht gelöscht werden.';
        }

        if (in_array($plugin, $this->activePlugins)) {
            return 'Plugin muss zuerst deaktiviert werden.';
        }

        $pluginDir  = $this->resolvePluginDirectory($plugin);
        if ($pluginDir === '' || !is_dir($pluginDir)) {
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

        // ZIP-Slip-Schutz: Alle Pfade validieren bevor entpackt wird
        $realPluginPath = realpath(PLUGIN_PATH);
        if ($realPluginPath === false) {
            $zip->close();
            return 'Plugin-Verzeichnis nicht gefunden.';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }
            // Path-Traversal-Prüfung: kein .., kein absoluter Pfad
            $fullPath = realpath(PLUGIN_PATH) . DIRECTORY_SEPARATOR . $entryName;
            $resolved = realpath(dirname(PLUGIN_PATH . DIRECTORY_SEPARATOR . $entryName));
            // Für neue Verzeichnisse: normalisierten Pfad prüfen
            $normalized = PLUGIN_PATH . DIRECTORY_SEPARATOR . $entryName;
            if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                $zip->close();
                return 'ZIP-Archiv enthält unsichere Pfade.';
            }
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

    private function isValidPluginSlug(string $plugin): bool
    {
        return preg_match('/^[a-z0-9][a-z0-9_-]*$/', strtolower(trim($plugin))) === 1;
    }

    private function resolvePluginDirectory(string $plugin): string
    {
        $plugin = strtolower(trim($plugin));
        if (!$this->isValidPluginSlug($plugin)) {
            return '';
        }

        $pluginRoot = realpath((string) PLUGIN_PATH);
        if ($pluginRoot === false || !is_dir($pluginRoot)) {
            return '';
        }

        $pluginDir = $pluginRoot . DIRECTORY_SEPARATOR . $plugin;
        $realPluginDir = realpath($pluginDir);
        $normalizedRoot = rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($realPluginDir === false || !is_dir($realPluginDir) || !str_starts_with($realPluginDir . DIRECTORY_SEPARATOR, $normalizedRoot)) {
            return '';
        }

        return $realPluginDir;
    }

    private function resolvePluginBootstrapFile(string $plugin): string
    {
        $plugin = strtolower(trim($plugin));
        if (!$this->isValidPluginSlug($plugin)) {
            return '';
        }

        $pluginDir = $this->resolvePluginDirectory($plugin);
        if ($pluginDir === '') {
            return '';
        }

        $pluginFile = $pluginDir . DIRECTORY_SEPARATOR . $plugin . '.php';
        $realPluginFile = realpath($pluginFile);
        if ($realPluginFile === false || !is_file($realPluginFile)) {
            return $pluginFile;
        }

        $pluginRoot = realpath((string) PLUGIN_PATH);
        if ($pluginRoot === false) {
            return '';
        }

        $normalizedRoot = rtrim($pluginRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($realPluginFile, $normalizedRoot)) {
            return '';
        }

        return $realPluginFile;
    }

    /**
     * @return array<string, string>
     */
    private function getPluginBootstrapMap(): array
    {
        $pluginRoot = realpath((string) PLUGIN_PATH);
        if ($pluginRoot === false || !is_dir($pluginRoot)) {
            return [];
        }

        $map = [];
        $entries = scandir($pluginRoot);
        if (!is_array($entries)) {
            return [];
        }

        foreach ($entries as $entry) {
            $slug = strtolower(trim((string) $entry));
            if ($slug === '.' || $slug === '..' || $slug === '.gitkeep' || !$this->isValidPluginSlug($slug)) {
                continue;
            }

            $bootstrap = $this->resolvePluginBootstrapFile($slug);
            if ($bootstrap !== '' && is_file($bootstrap)) {
                $map[$slug] = $bootstrap;
            }
        }

        return $map;
    }
}