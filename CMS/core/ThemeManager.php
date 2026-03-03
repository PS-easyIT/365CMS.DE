<?php
/**
 * Theme Manager
 * 
 * Handles theme loading and rendering
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class ThemeManager
{
    private static ?self $instance = null;
    private string $activeTheme;
    private string $themePath;
    /** @var array<string,string>|null  null = noch nicht geladen (H-22 Lazy Loading) */
    private ?array $settings = null;
    
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
     * Private constructor
     */
    private function __construct()
    {
        $this->activeTheme = $this->getActiveTheme();
        $this->themePath   = THEME_PATH . $this->activeTheme . '/';

        // H-22: DB-Zugriff auf Lazy Loading verschoben → kein DB-Query bei jeder Instanziierung.
        // Settings werden erst in loadSettings() beim ersten echten Zugriff geladen.

        // Hooks
        Hooks::addAction('head', [$this, 'renderCustomStyles']);
    }

    /**
     * H-22: Lädt Theme-Settings aus der DB beim ersten Zugriff (Lazy Loading).
     * Wird intern immer aufgerufen bevor $this->settings gelesen wird.
     */
    private function loadSettings(): void
    {
        if ($this->settings !== null) {
            return; // bereits geladen
        }

        $this->settings = [];

        try {
            $db   = Database::instance();
            $stmt = $db->query(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings"
                . " WHERE option_name LIKE 'color_%' OR option_name LIKE 'font_%' OR option_name LIKE 'site_%'"
            );
            while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
                $this->settings[$row->option_name] = $row->option_value;
            }
        } catch (\Exception $e) {
            error_log('ThemeManager::loadSettings() Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Load theme
     *
     * C-14: realpath()-Guard stellt sicher, dass das Theme-Verzeichnis
     * wirklich innerhalb von THEME_PATH liegt (Path-Traversal-Schutz).
     * H-21: Rollback auf DEFAULT_THEME bei Ladefehler.
     */
    public function loadTheme(): void
    {
        // C-14: Pfad-Validierung
        if (!$this->validateThemePath($this->themePath)) {
            error_log('ThemeManager: Ungültiger Theme-Pfad abgewiesen: ' . $this->themePath);
            $this->rollbackToDefaultTheme('Ungültiger Theme-Pfad');
            return;
        }

        // Sync ThemeCustomizer to the active theme (before loading functions.php)
        if (class_exists(\CMS\Services\ThemeCustomizer::class)) {
            \CMS\Services\ThemeCustomizer::instance()->setTheme($this->activeTheme);
        }

        // Load theme functions
        $functionsFile = $this->themePath . 'functions.php';
        if (file_exists($functionsFile)) {
            // C-14: Gefährliche Funktionsaufrufe im Theme scannen
            if ($this->hasUnsafeCode($functionsFile)) {
                error_log('ThemeManager: Theme "' . $this->activeTheme . '" enthält unsichere Funktionsaufrufe und wird nicht geladen.');
                $this->rollbackToDefaultTheme('Unsicherer Code in functions.php');
                return;
            }

            try {
                require_once $functionsFile;
            } catch (\Throwable $e) {
                error_log('ThemeManager: functions.php des Themes "' . $this->activeTheme . '" verursachte einen Fehler: ' . $e->getMessage());
                $this->rollbackToDefaultTheme('Laufzeitfehler in functions.php: ' . $e->getMessage());
                return;
            }
        }

        Hooks::doAction('theme_loaded', $this->activeTheme);
    }

    /**
     * C-14: Verifiziert, dass $path wirklich innerhalb von THEME_PATH liegt.
     * Verhindert Path-Traversal-Angriffe (z. B. ../../etc/).
     */
    private function validateThemePath(string $path): bool
    {
        $realThemePath = realpath(THEME_PATH);
        $realPath      = realpath($path);

        if ($realThemePath === false || $realPath === false) {
            return false;
        }

        // Pfad muss mit dem erlaubten Basisverzeichnis beginnen
        return str_starts_with($realPath . DIRECTORY_SEPARATOR, $realThemePath . DIRECTORY_SEPARATOR);
    }

    /**
     * C-14: Einfacher Scan auf gefährliche PHP-Funktionen in einer Theme-Datei.
     * Gibt true zurück wenn unsicherer Code gefunden wurde.
     */
    private function hasUnsafeCode(string $file): bool
    {
        $dangerousFunctions = [
            'eval', 'exec', 'system', 'shell_exec',
            'passthru', 'proc_open', 'popen',
            'base64_decode', // häufig für verschleierten Code genutzt
        ];

        $content = is_file($file) ? file_get_contents($file) : false; // M-03: kein @
        if ($content === false) {
            return false;
        }

        try {
            $tokens = token_get_all($content, TOKEN_PARSE);
        } catch (\ParseError $e) {
            error_log('ThemeManager::hasUnsafeCode() Parse-Fehler in ' . $file . ': ' . $e->getMessage());
            return true; // Syntaxfehler = trotzdem ablehnen
        }

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING) {
                if (in_array(strtolower($token[1]), $dangerousFunctions, true)) {
                    error_log('ThemeManager: Gefährliche Funktion "' . $token[1] . '" in ' . $file . ' gefunden.');
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * H-21: Rollback auf DEFAULT_THEME bei einem Ladefehler des aktiven Themes.
     *
     * Wird von loadTheme() aufgerufen wenn das Theme nicht sicher geladen werden kann.
     * Schreibt DEFAULT_THEME in die DB und setzt $this->activeTheme + $this->themePath
     * zurück, sodass nachfolgende Render-Aufrufe noch funktionieren.
     */
    private function rollbackToDefaultTheme(string $reason = ''): void
    {
        $default = DEFAULT_THEME;

        // Nicht in eine Endlosschleife laufen wenn das Default-Theme selbst defekt ist
        if ($this->activeTheme === $default) {
            error_log('ThemeManager: Rollback abgebrochen – DEFAULT_THEME "' . $default . '" ist selbst fehlerhaft. Grund: ' . $reason);
            return;
        }

        error_log(sprintf(
            'ThemeManager [H-21]: Theme "%s" fehlerhaft (%s) – Rollback auf DEFAULT_THEME "%s".',
            $this->activeTheme,
            $reason,
            $default
        ));

        // Audit-Log
        if (class_exists(AuditLogger::class)) {
            AuditLogger::instance()->log(
                'theme',
                'theme.rollback',
                sprintf('Theme "%s" automatisch auf DEFAULT_THEME "%s" zurückgesetzt. Grund: %s', $this->activeTheme, $default, $reason),
                'theme',
                null,
                ['from' => $this->activeTheme, 'to' => $default, 'reason' => $reason],
                'warning'
            );
        }

        // DB-Eintrag aktualisieren
        try {
            $db    = Database::instance();
            $check = $db->prepare("SELECT COUNT(*) AS cnt FROM {$db->getPrefix()}settings WHERE option_name = 'active_theme'");
            $check->execute();
            $result = $check->fetch();

            if ($result && (int)$result->cnt > 0) {
                $db->execute(
                    "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = 'active_theme'",
                    [$default]
                );
            } else {
                $db->execute(
                    "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('active_theme', ?)",
                    [$default]
                );
            }
        } catch (\Exception $e) {
            error_log('ThemeManager::rollbackToDefaultTheme() DB-Fehler: ' . $e->getMessage());
        }

        // Runtime-Zustand aktualisieren
        $this->activeTheme = $default;
        $this->themePath   = THEME_PATH . $default . '/';
    }

    /**
     * Render template
     */
    public function render(string $template, array $data = []): void
    {
        // Extract data for template – EXTR_SKIP verhindert das Überschreiben existierender Variablen (LFI-Schutz)
        extract($data, EXTR_SKIP);
        
        // Allow plugins to modify template
        $template = Hooks::applyFilters('template_name', $template);
        
        // Template hierarchy
        $templates = [
            $this->themePath . $template . '.php',
            $this->themePath . 'index.php'
        ];
        
        foreach ($templates as $file) {
            if (file_exists($file)) {
                // Pre-render hook
                Hooks::doAction('before_render', $template);
                
                // Include header
                $this->getHeader();
                
                // Include template
                include $file;
                
                // Include footer
                $this->getFooter();
                
                // Track page view (for analytics)
                $this->trackPageView($template, $data);
                
                // Post-render hook
                Hooks::doAction('after_render', $template);
                
                return;
            }
        }
        
        // Template not found
        echo '<h1>Template nicht gefunden</h1>';
    }
    
    /**
     * Track page view for analytics
     */
    private function trackPageView(string $template, array $data): void
    {
        try {
            // Only track if TrackingService exists
            if (!class_exists(\CMS\Services\TrackingService::class)) {
                return;
            }
            
            $tracking = \CMS\Services\TrackingService::getInstance();
            
            // Determine page info
            $pageId = null;
            $pageSlug = $template;
            $pageTitle = $template;
            
            // Check if we have page data
            if (isset($data['page']) && is_array($data['page'])) {
                $pageId = $data['page']['id'] ?? null;
                $pageSlug = $data['page']['slug'] ?? $pageSlug;
                $pageTitle = $data['page']['title'] ?? $pageTitle;
            }
            
            // Get user ID if logged in
            $userId = null;
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }
            
            // Track the view
            $tracking->trackPageView($pageId, $pageSlug, $pageTitle, $userId);
        } catch (\Throwable $e) {
            // Silently fail - tracking should never break the site
            error_log('Tracking Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get header
     */
    public function getHeader(): void
    {
        $headerFile = $this->themePath . 'header.php';
        if (file_exists($headerFile)) {
            Hooks::doAction('before_header');
            include $headerFile;
            Hooks::doAction('after_header');
        }
    }
    
    /**
     * Get footer
     */
    public function getFooter(): void
    {
        $footerFile = $this->themePath . 'footer.php';
        if (file_exists($footerFile)) {
            Hooks::doAction('before_footer');
            include $footerFile;
            Hooks::doAction('after_footer');
        }
    }
    
    /**
     * Get active theme slug
     */
    public function getActiveThemeSlug(): string
    {
        return $this->activeTheme;
    }

    /**
     * Get active theme from database, with folder-existence fallback
     */
    private function getActiveTheme(): string
    {
        $slug = DEFAULT_THEME;

        try {
            $db = Database::instance();
            $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'active_theme' LIMIT 1");

            if ($stmt) {
                $stmt->execute();
                $result = $stmt->fetch();
                if ($result && $result->option_value) {
                    // Defense-in-depth: Slug darf nur sichere Zeichen enthalten
                    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $result->option_value);
                }
            }
        } catch (\Exception $e) {
            error_log('ThemeManager::getActiveTheme() Error: ' . $e->getMessage());
        }

        // Verify theme folder exists; if not, use first folder that has a style.css
        if (!is_dir(THEME_PATH . $slug)) {
            foreach (scandir(THEME_PATH) ?: [] as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }
                if (file_exists(THEME_PATH . $dir . '/style.css')) {
                    $slug = $dir;
                    break;
                }
            }
        }

        return $slug;
    }
    
    /**
     * Get available themes
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $themeDir = THEME_PATH;
        
        if (!is_dir($themeDir)) {
            return $themes;
        }
        
        $directories = scandir($themeDir);
        
        foreach ($directories as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            
            $themeFile = $themeDir . $dir . '/style.css';
            
            if (file_exists($themeFile)) {
                $data = $this->getThemeData($themeFile);
                if ($data) {
                    $data['folder'] = $dir;
                    $data['active'] = ($dir === $this->activeTheme);
                    $themes[$dir] = $data;
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * Get current theme data
     */
    public function getCurrentTheme(): array
    {
        $themeFile = $this->themePath . 'style.css';
        
        if (file_exists($themeFile)) {
            $data = $this->getThemeData($themeFile);
            if ($data) {
                $data['folder'] = $this->activeTheme;
                $data['active'] = true;
                return $data;
            }
        }
        
        // Fallback if theme data cannot be read
        return [
            'name' => $this->activeTheme,
            'folder' => $this->activeTheme,
            'active' => true
        ];
    }
    
    /**
     * Get theme data from style.css headers
     */
    private function getThemeData(string $file): array|false
    {
        $content = file_get_contents($file);
        if (!$content) {
            return false;
        }
        
        $data = [];
        $headers = [
            'name' => 'Theme Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author'
        ];
        
        foreach ($headers as $key => $header) {
            if (preg_match('/' . $header . ':\s*(.+)/i', $content, $matches)) {
                $data[$key] = trim($matches[1]);
            }
        }
        
        return $data ?: false;
    }
    
    /**
     * Switch theme
     *
     * C-15: Audit-Log für Theme-Wechsel
     * H-20: Gesundheitscheck vor dem Aktivieren
     */
    public function switchTheme(string $theme): bool|string
    {
        $themeFile = THEME_PATH . $theme . '/style.css';

        if (!file_exists($themeFile)) {
            return 'Theme nicht gefunden.';
        }

        // H-20: Gesundheitscheck (Pflichtdateien + Syntax aller PHP-Dateien)
        $healthResult = $this->healthCheckTheme($theme);
        if ($healthResult !== true) {
            error_log('ThemeManager::switchTheme() Health-Check fehlgeschlagen für "' . $theme . '": ' . $healthResult);
            return 'Theme-Gesundheitscheck fehlgeschlagen: ' . $healthResult;
        }

        try {
            $db = Database::instance();

            $check = $db->prepare("SELECT COUNT(*) AS cnt FROM {$db->getPrefix()}settings WHERE option_name = 'active_theme'");
            $check->execute();
            $result = $check->fetch();

            if ($result && (int)$result->cnt > 0) {
                $db->execute(
                    "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = 'active_theme'",
                    [$theme]
                );
            } else {
                $db->execute(
                    "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('active_theme', ?)",
                    [$theme]
                );
            }

            // C-15: Theme-Wechsel protokollieren
            AuditLogger::instance()->themeSwitch($this->activeTheme, $theme);

            return true;
        } catch (\Exception $e) {
            error_log('ThemeManager::switchTheme() Error: ' . $e->getMessage());
            return 'Fehler beim Wechseln des Themes.';
        }
    }

    /**
     * H-20: Gesundheitscheck für ein Theme vor der Aktivierung.
     *
     * Prüft:
     *   1. style.css vorhanden (Pflicht)
     *   2. Mindestens eine Template-Datei (index.php oder functions.php)
     *   3. PHP-Syntaxprüfung aller .php-Dateien via token_get_all()
     *
     * @return true|string  true = gesund, string = Fehlerbeschreibung
     */
    public function healthCheckTheme(string $theme): bool|string
    {
        $themeDir = THEME_PATH . basename($theme) . '/';

        // 1. style.css Pflicht
        if (!file_exists($themeDir . 'style.css')) {
            return 'Pflichtdatei style.css fehlt.';
        }

        // 2. Mindestens eine Template-Datei
        $hasTemplate = file_exists($themeDir . 'index.php') || file_exists($themeDir . 'functions.php');
        if (!$hasTemplate) {
            return 'Keine Template-Datei gefunden (index.php oder functions.php erforderlich).';
        }

        // 3. PHP-Syntaxprüfung aller .php-Dateien
        if (!is_dir($themeDir)) {
            return 'Theme-Verzeichnis nicht gefunden.';
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themeDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // M-03: is_readable statt @ für lesbare Dateien prüfen
            $content = (is_file($file->getPathname()) && is_readable($file->getPathname()))
                ? file_get_contents($file->getPathname())
                : false;
            if ($content === false) {
                continue;
            }

            try {
                token_get_all($content, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $relPath = str_replace($themeDir, '', $file->getPathname());
                return sprintf('PHP-Syntaxfehler in %s: %s', $relPath, $e->getMessage());
            }
        }

        return true;
    }
    
    /**
     * Get theme path
     */
    public function getThemePath(): string
    {
        return $this->themePath;
    }

    /**
     * Delete a theme (only non-active, only if at least 2 themes exist)
     *
     * C-15: Audit-Log für Theme-Löschung
     */
    public function deleteTheme(string $folder): bool|string
    {
        $folder = basename($folder); // prevent path traversal

        if ($folder === $this->activeTheme) {
            return 'Das aktive Theme kann nicht gelöscht werden.';
        }

        $themeDir = THEME_PATH . $folder;
        if (!is_dir($themeDir)) {
            return 'Theme-Verzeichnis nicht gefunden.';
        }

        if (count($this->getAvailableThemes()) <= 1) {
            return 'Das letzte verfügbare Theme kann nicht gelöscht werden.';
        }

        try {
            $this->deleteDirectory($themeDir);

            // C-15: Theme-Löschung protokollieren
            AuditLogger::instance()->themeDelete($folder);

            return true;
        } catch (\Throwable $e) {
            error_log('ThemeManager::deleteTheme() Error: ' . $e->getMessage());
            return 'Fehler beim Löschen: ' . $e->getMessage();
        }
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    /**
     * Get theme URL
     */
    public function getThemeUrl(): string
    {
        return SITE_URL . '/themes/' . $this->activeTheme;
    }

    /**
     * Get Site Title
     */
    public function getSiteTitle(): string
    {
        $this->loadSettings(); // H-22
        return $this->settings['site_title'] ?? SITE_NAME;
    }

    /**
     * Get Site Description
     */
    public function getSiteDescription(): string
    {
        $this->loadSettings(); // H-22
        return $this->settings['site_description'] ?? '';
    }

    /**
     * Get Site Menu Items (legacy – liest 'site_menu' aus Settings)
     */
    public function getSiteMenu(): array
    {
        $this->loadSettings(); // H-22
        if (isset($this->settings['site_menu'])) {
            return json_decode($this->settings['site_menu'], true) ?: [];
        }
        return [];
    }

    /**
     * Get menu items for a named location.
     * Falls back to legacy 'site_menu' for the 'primary' location.
     */
    public function getMenu(string $location): array
    {
        try {
            $db = Database::instance();
            $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1");
            $stmt->execute(['menu_' . $location]);
            $result = $stmt->fetch();

            if ($result && $result->option_value) {
                return json_decode($result->option_value, true) ?: [];
            }
        } catch (\Exception $e) {
            error_log('ThemeManager::getMenu() Error: ' . $e->getMessage());
        }

        // Legacy fallback for primary nav
        if ($location === 'primary') {
            return $this->getSiteMenu();
        }

        return [];
    }

    /**
     * Save menu items for a named location.
     */
    public function saveMenu(string $location, array $items): bool
    {
        $key   = 'menu_' . $location;
        $value = json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        try {
            $db      = Database::instance();
            $check   = $db->prepare("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1");
            $check->execute([$key]);

            if ($check->fetch()) {
                $db->execute(
                    "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
                    [$value, $key]
                );
            } else {
                $db->execute(
                    "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
                    [$key, $value]
                );
            }
            return true;
        } catch (\Exception $e) {
            error_log('ThemeManager::saveMenu() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all registered menu locations.
     * Themes register their locations via the 'register_menu_locations' filter.
     * Additional custom locations are saved as 'menu_custom_locations' in settings.
     */
    public function getMenuLocations(): array
    {
        // Built-in defaults from active theme (via filter – registered by functions.php in frontend)
        $locations = Hooks::applyFilters('register_menu_locations', []);

        // M-NEW: Fallback – Menüpositionen direkt aus theme.json lesen,
        // damit sie auch im Admin-Bereich ohne geladene functions.php verfügbar sind.
        $themeJsonFile = $this->themePath . 'theme.json';
        if (file_exists($themeJsonFile)) {
            try {
                $json = json_decode((string)file_get_contents($themeJsonFile), true);
                if (is_array($json) && isset($json['menus']) && is_array($json['menus'])) {
                    $existingSlugs = array_column($locations, 'slug');
                    foreach ($json['menus'] as $slug => $label) {
                        if (!in_array($slug, $existingSlugs, true)) {
                            $locations[] = ['slug' => (string)$slug, 'label' => (string)$label];
                            $existingSlugs[] = (string)$slug;
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('ThemeManager::getMenuLocations() theme.json error: ' . $e->getMessage());
            }
        }

        // Additional user-created locations
        try {
            $db   = Database::instance();
            $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && $result->option_value) {
                $custom = json_decode($result->option_value, true) ?: [];
                foreach ($custom as $loc) {
                    // Defensive: nur valide Array-Einträge mit 'slug'-Schlüssel verarbeiten
                    if (!is_array($loc) || !isset($loc['slug'], $loc['label'])) {
                        continue;
                    }
                    // Avoid duplicates from theme-registered locations
                    $slugs = array_column($locations, 'slug');
                    if (!in_array($loc['slug'], $slugs, true)) {
                        $locations[] = $loc;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('ThemeManager::getMenuLocations() Error: ' . $e->getMessage());
        }

        return $locations;
    }

    /**
     * Save the list of custom (user-created) menu locations.
     */
    public function saveCustomMenuLocations(array $locations): bool
    {
        $value = json_encode(array_values($locations), JSON_UNESCAPED_UNICODE);

        try {
            $db    = Database::instance();
            $check = $db->prepare("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
            $check->execute();

            if ($check->fetch()) {
                $db->execute(
                    "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = 'menu_custom_locations'",
                    [$value]
                );
            } else {
                $db->execute(
                    "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('menu_custom_locations', ?)",
                    [$value]
                );
            }
            return true;
        } catch (\Exception $e) {
            error_log('ThemeManager::saveCustomMenuLocations() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Render Customizer Styles
     */
    public function renderCustomStyles(): void
    {
        $this->loadSettings(); // H-22

        if (empty($this->settings)) {
            return;
        }

        echo "<style id='cms-customizer'>\n:root {\n";
        
        $s = $this->settings;
        if (isset($s['color_primary'])) echo "    --primary-color: " . htmlspecialchars($s['color_primary']) . ";\n";
        if (isset($s['color_secondary'])) echo "    --secondary-color: " . htmlspecialchars($s['color_secondary']) . ";\n";
        if (isset($s['color_bg'])) echo "    --bg-secondary: " . htmlspecialchars($s['color_bg']) . ";\n";
        if (isset($s['color_text'])) echo "    --text-primary: " . htmlspecialchars($s['color_text']) . ";\n";
        
        echo "}\n";
        
        if (isset($s['font_family']) || isset($s['font_size'])) {
            echo "body {\n";
            if (isset($s['font_family'])) echo "    font-family: " . htmlspecialchars($s['font_family'], ENT_QUOTES, 'UTF-8') . ";\n";
            if (isset($s['font_size'])) echo "    font-size: " . htmlspecialchars($s['font_size']) . ";\n";
            echo "}\n";
        }
        
        echo "</style>\n";
    }
}
