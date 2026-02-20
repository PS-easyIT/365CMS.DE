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
    private array $settings = [];
    
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
        $this->themePath = THEME_PATH . $this->activeTheme . '/';
        
        // Load settings
        $db = Database::instance();
        $stmt = $db->query("SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name LIKE 'color_%' OR option_name LIKE 'font_%' OR option_name LIKE 'site_%'");
        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            $this->settings[$row->option_name] = $row->option_value;
        }
        
        // Hooks
        Hooks::addAction('head', [$this, 'renderCustomStyles']);
    }
    
    /**
     * Load theme
     */
    public function loadTheme(): void
    {
        // Sync ThemeCustomizer to the active theme (before loading functions.php)
        if (class_exists(\CMS\Services\ThemeCustomizer::class)) {
            \CMS\Services\ThemeCustomizer::instance()->setTheme($this->activeTheme);
        }

        // Load theme functions
        $functionsFile = $this->themePath . 'functions.php';
        if (file_exists($functionsFile)) {
            require_once $functionsFile;
        }
        
        Hooks::doAction('theme_loaded', $this->activeTheme);
    }
    
    /**
     * Render template
     */
    public function render(string $template, array $data = []): void
    {
        // Extract data for template
        extract($data);
        
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
                    $slug = $result->option_value;
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
     */
    public function switchTheme(string $theme): bool|string
    {
        $themeFile = THEME_PATH . $theme . '/style.css';

        if (!file_exists($themeFile)) {
            return 'Theme nicht gefunden.';
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

            return true;
        } catch (\Exception $e) {
            error_log('ThemeManager::switchTheme() Error: ' . $e->getMessage());
            return 'Fehler beim Wechseln des Themes.';
        }
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
        return $this->settings['site_title'] ?? SITE_NAME;
    }

    /**
     * Get Site Description
     */
    public function getSiteDescription(): string
    {
        return $this->settings['site_description'] ?? '';
    }

    /**
     * Get Site Menu Items (legacy – liest 'site_menu' aus Settings)
     */
    public function getSiteMenu(): array
    {
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
        // Built-in defaults from active theme (via filter)
        $locations = Hooks::applyFilters('register_menu_locations', []);

        // Additional user-created locations
        try {
            $db   = Database::instance();
            $stmt = $db->prepare("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'menu_custom_locations' LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && $result->option_value) {
                $custom = json_decode($result->option_value, true) ?: [];
                foreach ($custom as $loc) {
                    // Avoid duplicates from theme-registered locations
                    $slugs = array_column($locations, 'slug');
                    if (!in_array($loc['slug'], $slugs, true)) {
                        $locations[] = $loc;
                    }
                }
            }
        } catch (\Exception $e) {
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
