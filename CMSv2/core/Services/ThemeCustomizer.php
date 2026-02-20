<?php
/**
 * Theme Customizer Service
 * 
 * Verwaltet Theme-Anpassungen und -Einstellungen
 * 
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use PDO;

if (!defined('ABSPATH')) {
    exit;
}

class ThemeCustomizer
{
    private static ?ThemeCustomizer $instance = null;
    private Database $db;
    private string $currentTheme = 'default';
    private ?array $themeConfig = null;
    private array $customizations = [];
    
    /**
     * Get singleton instance
     */
    public static function instance(): ThemeCustomizer
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct()
    {
        $this->db = Database::instance();
        $this->currentTheme = $this->detectActiveTheme();
        $this->loadThemeConfig();
        $this->loadCustomizations();
    }

    /**
     * Auto-detect active theme from database, with folder-existence fallback
     */
    private function detectActiveTheme(): string
    {
        $slug = defined('DEFAULT_THEME') ? DEFAULT_THEME : 'cms-default';

        try {
            $stmt = $this->db->prepare(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = 'active_theme' LIMIT 1"
            );
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            if ($result && !empty($result->option_value)) {
                $slug = $result->option_value;
            }
        } catch (\Throwable $e) {
            error_log('ThemeCustomizer::detectActiveTheme() Error: ' . $e->getMessage());
        }

        // Verify theme folder + theme.json actually exist; fallback to first available theme
        $themePath = defined('ABSPATH') ? ABSPATH . 'themes/' . $slug . '/theme.json' : '';
        if (!file_exists($themePath)) {
            $base = defined('ABSPATH') ? ABSPATH . 'themes/' : '';
            if ($base && is_dir($base)) {
                foreach (scandir($base) as $dir) {
                    if ($dir === '.' || $dir === '..') {
                        continue;
                    }
                    if (file_exists($base . $dir . '/theme.json')) {
                        $slug = $dir;
                        break;
                    }
                }
            }
        }

        return $slug;
    }
    
    /**
     * Set current theme
     */
    public function setTheme(string $themeSlug): void
    {
        $this->currentTheme = $themeSlug;
        $this->loadThemeConfig();
        $this->loadCustomizations();
    }
    
    /**
     * Get current theme slug
     */
    public function getTheme(): string
    {
        return $this->currentTheme;
    }
    
    /**
     * Load theme configuration from theme.json
     */
    private function loadThemeConfig(): void
    {
        $configPath = ABSPATH . 'themes/' . $this->currentTheme . '/theme.json';
        
        if (!file_exists($configPath)) {
            error_log("Theme config not found: {$configPath}");
            $this->themeConfig = [];
            return;
        }
        
        $json = file_get_contents($configPath);
        $config = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid theme.json: " . json_last_error_msg());
            $this->themeConfig = [];
            return;
        }
        
        $this->themeConfig = $config;
    }
    
    /**
     * Get complete theme configuration
     */
    public function getThemeConfig(): array
    {
        return $this->themeConfig ?? [];
    }
    
    /**
     * Get theme metadata
     */
    public function getThemeMetadata(): array
    {
        $config = $this->getThemeConfig();
        
        return [
            'name' => $config['name'] ?? '',
            'slug' => $config['slug'] ?? $this->currentTheme,
            'version' => $config['version'] ?? '1.0.0',
            'author' => $config['author'] ?? '',
            'description' => $config['description'] ?? '',
            'tags' => $config['tags'] ?? [],
            'supports' => $config['supports'] ?? []
        ];
    }
    
    /**
     * Get customization options from theme.json
     */
    public function getCustomizationOptions(): array
    {
        $config = $this->getThemeConfig();
        return $config['customization'] ?? [];
    }
    
    /**
     * Load customizations from database
     */
    private function loadCustomizations(?int $userId = null): void
    {
        try {
            $sql = "SELECT setting_category, setting_key, setting_value 
                    FROM {$this->db->getPrefix()}theme_customizations 
                    WHERE theme_slug = ?";
            $params = [$this->currentTheme];
            
            if ($userId !== null) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            } else {
                $sql .= " AND user_id IS NULL";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->customizations = [];
            foreach ($results as $row) {
                if (!isset($this->customizations[$row['setting_category']])) {
                    $this->customizations[$row['setting_category']] = [];
                }
                $this->customizations[$row['setting_category']][$row['setting_key']] = $row['setting_value'];
            }
            
        } catch (\PDOException $e) {
            error_log("Error loading theme customizations: " . $e->getMessage());
            $this->customizations = [];
        }
    }
    
    /**
     * Get customization value with fallback to default
     */
    public function get(string $category, string $key, $default = null)
    {
        // Check if custom value exists
        if (isset($this->customizations[$category][$key])) {
            return $this->customizations[$category][$key];
        }
        
        // Fallback to theme.json default
        $options = $this->getCustomizationOptions();
        if (isset($options[$category]['settings'][$key]['default'])) {
            return $options[$category]['settings'][$key]['default'];
        }
        
        // Fallback to provided default
        return $default;
    }
    
    /**
     * Get all customizations for a category
     */
    public function getCategory(string $category): array
    {
        $options = $this->getCustomizationOptions();
        $categorySettings = $options[$category]['settings'] ?? [];
        
        $result = [];
        foreach ($categorySettings as $key => $config) {
            $result[$key] = $this->get($category, $key, $config['default'] ?? null);
        }
        
        return $result;
    }
    
    /**
     * Save customization value
     */
    public function set(string $category, string $key, $value, ?int $userId = null): bool
    {
        try {
            // Validate that setting exists in theme.json
            $options = $this->getCustomizationOptions();
            if (!isset($options[$category]['settings'][$key])) {
                error_log("Invalid theme setting: {$category}.{$key}");
                return false;
            }
            
            // Check if record exists
            $sql = "SELECT id FROM {$this->db->getPrefix()}theme_customizations 
                    WHERE theme_slug = ? AND setting_category = ? AND setting_key = ?";
            $params = [$this->currentTheme, $category, $key];
            
            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " AND user_id IS NULL";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing
                $updateSql = "UPDATE {$this->db->getPrefix()}theme_customizations 
                              SET setting_value = ?, updated_at = NOW() 
                              WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $result = $updateStmt->execute([$value, $existing['id']]);
            } else {
                // Insert new
                $insertSql = "INSERT INTO {$this->db->getPrefix()}theme_customizations 
                              (theme_slug, setting_category, setting_key, setting_value, user_id) 
                              VALUES (?, ?, ?, ?, ?)";
                $insertStmt = $this->db->prepare($insertSql);
                $result = $insertStmt->execute([
                    $this->currentTheme,
                    $category,
                    $key,
                    $value,
                    $userId
                ]);
            }
            
            // Reload customizations
            if ($result) {
                $this->loadCustomizations($userId);
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Error saving theme customization: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save multiple customizations at once
     */
    public function setMultiple(array $settings, ?int $userId = null): bool
    {
        $success = true;
        
        foreach ($settings as $category => $categorySettings) {
            if (!is_array($categorySettings)) {
                continue;
            }
            
            foreach ($categorySettings as $key => $value) {
                if (!$this->set($category, $key, $value, $userId)) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Reset customization to default
     */
    public function reset(string $category, string $key, ?int $userId = null): bool
    {
        try {
            $sql = "DELETE FROM {$this->db->getPrefix()}theme_customizations 
                    WHERE theme_slug = ? AND setting_category = ? AND setting_key = ?";
            $params = [$this->currentTheme, $category, $key];
            
            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " AND user_id IS NULL";
            }
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            // Reload customizations
            if ($result) {
                $this->loadCustomizations($userId);
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Error resetting theme customization: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset all customizations for theme
     */
    public function resetAll(?int $userId = null): bool
    {
        try {
            $sql = "DELETE FROM {$this->db->getPrefix()}theme_customizations 
                    WHERE theme_slug = ?";
            $params = [$this->currentTheme];
            
            if ($userId !== null) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            } else {
                $sql .= " AND user_id IS NULL";
            }
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            // Reload customizations
            if ($result) {
                $this->loadCustomizations($userId);
            }
            
            return $result;
            
        } catch (\PDOException $e) {
            error_log("Error resetting all theme customizations: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate CSS from customizations
     * Variablennamen entsprechen exakt den CSS-Variablen in themes/default/style.css
     */
    public function generateCSS(): string
    {
        $css = "/* Theme Customizations - Auto-generated */\n\n";
        $css .= ":root {\n";

        // ── Farben ──────────────────────────────────────────────────────────
        $colors = $this->getCategory('colors');
        if (!empty($colors)) {
            $css .= "    /* Farben */\n";
            $map = [
                'primary_color'    => ['--primary-color'],
                'secondary_color'  => ['--secondary-color', '--primary-hover'],
                'accent_color'     => ['--accent-color'],
                'text_color'       => ['--text-color', '--text-primary'],
                'bg_color'         => ['--background-color', '--bg-secondary', '--light-bg'],
                'link_color'       => ['--link-color'],
                'link_hover_color' => ['--link-hover-color'],
                'muted_color'      => ['--muted-color'],
                'border_color'     => ['--border-color'],
                'success_color'    => ['--success-color'],
                'error_color'      => ['--error-color'],
            ];
            foreach ($map as $key => $vars) {
                if (!empty($colors[$key])) {
                    foreach ($vars as $var) {
                        $css .= "    {$var}: {$colors[$key]};\n";
                    }
                }
            }
            $css .= "\n";
        }

        // ── Header ──────────────────────────────────────────────────────────
        $header = $this->getCategory('header');
        if (!empty($header)) {
            $css .= "    /* Header */\n";
            if (!empty($header['header_bg_color'])) {
                // style.css erwartet --header-bg (kein -color Suffix)
                $css .= "    --header-bg: {$header['header_bg_color']};\n";
                $css .= "    --header-bg-color: {$header['header_bg_color']};\n";
            }
            if (!empty($header['header_text_color'])) {
                $css .= "    --header-text: {$header['header_text_color']};\n";
                $css .= "    --header-text-color: {$header['header_text_color']};\n";
            }
            if (!empty($header['header_height'])) {
                $css .= "    --header-height: {$header['header_height']}px;\n";
            }
            if (!empty($header['logo_max_height'])) {
                $css .= "    --logo-max-height: {$header['logo_max_height']}px;\n";
            }
            $css .= "\n";
        }

        // ── Footer ──────────────────────────────────────────────────────────
        $footer = $this->getCategory('footer');
        if (!empty($footer)) {
            $css .= "    /* Footer */\n";
            if (!empty($footer['footer_bg_color'])) {
                $css .= "    --footer-bg: {$footer['footer_bg_color']};\n";
                $css .= "    --footer-bg-color: {$footer['footer_bg_color']};\n";
            }
            if (!empty($footer['footer_text_color'])) {
                $css .= "    --footer-text: {$footer['footer_text_color']};\n";
                $css .= "    --footer-text-color: {$footer['footer_text_color']};\n";
            }
            if (!empty($footer['footer_link_color'])) {
                $css .= "    --footer-link: {$footer['footer_link_color']};\n";
                $css .= "    --footer-link-color: {$footer['footer_link_color']};\n";
            }
            $css .= "\n";
        }

        // ── Typografie ──────────────────────────────────────────────────────
        $typography = $this->getCategory('typography');
        if (!empty($typography)) {
            $css .= "    /* Typografie */\n";
            if (!empty($typography['font_family_base']) && $typography['font_family_base'] !== 'system') {
                $fontFamily = $this->getFontFamilyStack($typography['font_family_base']);
                // style.css verwendet --font-body und --font-menu
                $css .= "    --font-body: {$fontFamily};\n";
                $css .= "    --font-menu: {$fontFamily};\n";
                $css .= "    --font-family-base: {$fontFamily};\n";
            }
            if (!empty($typography['font_family_heading']) && $typography['font_family_heading'] !== 'system') {
                $fontFamily = $this->getFontFamilyStack($typography['font_family_heading']);
                // style.css verwendet --font-heading
                $css .= "    --font-heading: {$fontFamily};\n";
                $css .= "    --font-family-heading: {$fontFamily};\n";
            }
            if (!empty($typography['font_size_base'])) {
                $css .= "    --font-size-base: {$typography['font_size_base']}px;\n";
            }
            if (!empty($typography['line_height_base'])) {
                $css .= "    --line-height-base: {$typography['line_height_base']};\n";
            }
            if (!empty($typography['font_weight_heading'])) {
                $css .= "    --font-weight-heading: {$typography['font_weight_heading']};\n";
            }
            $css .= "\n";
        }

        // ── Layout ──────────────────────────────────────────────────────────
        $layout = $this->getCategory('layout');
        if (!empty($layout)) {
            $css .= "    /* Layout */\n";
            if (!empty($layout['container_width'])) {
                // style.css verwendet --container-max-width
                $css .= "    --container-max-width: {$layout['container_width']}px;\n";
                $css .= "    --container-width: {$layout['container_width']}px;\n";
            }
            if (!empty($layout['content_padding'])) {
                // style.css verwendet --container-padding
                $css .= "    --container-padding: {$layout['content_padding']}rem;\n";
                $css .= "    --content-padding: {$layout['content_padding']}rem;\n";
            }
            if (!empty($layout['border_radius'])) {
                $r = (int)$layout['border_radius'];
                // style.css verwendet --radius-sm / -md / -lg Abstufungen
                $css .= "    --border-radius: {$r}px;\n";
                $css .= "    --radius-sm: " . max(2, intval($r / 2)) . "px;\n";
                $css .= "    --radius-md: {$r}px;\n";
                $css .= "    --radius-lg: " . ($r * 2) . "px;\n";
            }
            if (!empty($layout['section_spacing'])) {
                $css .= "    --section-spacing: {$layout['section_spacing']}rem;\n";
            }
            $css .= "\n";
        }

        // ── Buttons ─────────────────────────────────────────────────────────
        $buttons = $this->getCategory('buttons');
        if (!empty($buttons)) {
            $css .= "    /* Buttons */\n";
            if (!empty($buttons['button_border_radius'])) {
                $css .= "    --button-border-radius: {$buttons['button_border_radius']}px;\n";
            }
            if (!empty($buttons['button_padding_x'])) {
                $css .= "    --button-padding-x: {$buttons['button_padding_x']}rem;\n";
            }
            if (!empty($buttons['button_padding_y'])) {
                $css .= "    --button-padding-y: {$buttons['button_padding_y']}rem;\n";
            }
            if (!empty($buttons['button_font_weight'])) {
                $css .= "    --button-font-weight: {$buttons['button_font_weight']};\n";
            }
            if (!empty($buttons['button_transform'])) {
                $css .= "    --button-text-transform: {$buttons['button_transform']};\n";
            }
            $css .= "\n";
        }

        $css .= "}\n\n";

        // ── Elemente-CSS ────────────────────────────────────────────────────

        // Body-Schrift
        if (!empty($typography['font_family_base']) && $typography['font_family_base'] !== 'system') {
            $css .= "body {\n";
            $css .= "    font-family: var(--font-body);\n";
            if (!empty($typography['font_size_base'])) {
                $css .= "    font-size: var(--font-size-base, 16px);\n";
            }
            if (!empty($typography['line_height_base'])) {
                $css .= "    line-height: var(--line-height-base, 1.6);\n";
            }
            $css .= "}\n\n";
        }

        // Überschriften-Schrift
        if (!empty($typography['font_family_heading']) && $typography['font_family_heading'] !== 'system') {
            $css .= "h1, h2, h3, h4, h5, h6 {\n";
            $css .= "    font-family: var(--font-heading, inherit);\n";
            if (!empty($typography['font_weight_heading'])) {
                $css .= "    font-weight: var(--font-weight-heading, 700);\n";
            }
            $css .= "}\n\n";
        }

        // Container
        if (!empty($layout['container_width'])) {
            $css .= ".container {\n";
            $css .= "    max-width: var(--container-max-width, 1200px);\n";
            if (!empty($layout['content_padding'])) {
                $css .= "    padding-left: var(--container-padding, 2rem);\n";
                $css .= "    padding-right: var(--container-padding, 2rem);\n";
            }
            $css .= "}\n\n";
        }

        // Sticky Header
        if (!empty($layout['enable_sticky_header'])) {
            $css .= ".site-header {\n";
            $css .= "    position: sticky;\n";
            $css .= "    top: 0;\n";
            $css .= "    z-index: 1000;\n";
            $css .= "}\n\n";
        }

        // Header Styling
        if (!empty($header['header_bg_color'])) {
            $borderBottom = !empty($header['show_header_shadow'])
                ? "    box-shadow: 0 2px 8px rgba(0,0,0,0.15);\n" : '';
            $css .= ".site-header {\n";
            $css .= "    background: var(--header-bg);\n";
            if (!empty($header['header_text_color'])) {
                $css .= "    color: var(--header-text);\n";
            }
            if (!empty($header['header_height'])) {
                $css .= "    min-height: var(--header-height);\n";
            }
            $css .= $borderBottom;
            $css .= "}\n\n";

            // Nav-Link Farbe
            if (!empty($header['header_text_color'])) {
                $css .= ".main-nav a, .site-header .nav a {\n";
                $css .= "    color: var(--header-text);\n";
                $css .= "}\n\n";
            }
        }

        // Logo Höhe
        if (!empty($header['logo_max_height'])) {
            $css .= ".site-logo img, .site-logo svg {\n";
            $css .= "    max-height: var(--logo-max-height, 48px);\n";
            $css .= "}\n\n";
        }

        // Footer Styling
        if (!empty($footer['footer_bg_color'])) {
            $css .= ".site-footer {\n";
            $css .= "    background: var(--footer-bg);\n";
            if (!empty($footer['footer_text_color'])) {
                $css .= "    color: var(--footer-text);\n";
            }
            $css .= "}\n\n";
            if (!empty($footer['footer_link_color'])) {
                $css .= ".site-footer a {\n";
                $css .= "    color: var(--footer-link);\n";
                $css .= "}\n\n";
            }
        }

        // Buttons
        if (!empty($buttons['button_border_radius']) || !empty($buttons['button_padding_x'])) {
            $css .= ".btn, button.btn, a.btn {\n";
            if (!empty($buttons['button_border_radius'])) {
                $css .= "    border-radius: var(--button-border-radius);\n";
            }
            if (!empty($buttons['button_padding_x']) && !empty($buttons['button_padding_y'])) {
                $css .= "    padding: var(--button-padding-y) var(--button-padding-x);\n";
            }
            if (!empty($buttons['button_font_weight'])) {
                $css .= "    font-weight: var(--button-font-weight);\n";
            }
            if (!empty($buttons['button_transform'])) {
                $css .= "    text-transform: var(--button-text-transform);\n";
            }
            $css .= "}\n\n";
        }

        // Custom CSS
        $advanced = $this->getCategory('advanced');
        if (!empty($advanced['custom_css'])) {
            $css .= "/* Custom CSS */\n";
            $css .= $advanced['custom_css'] . "\n";
        }

        return $css;
    }
    
    /**
     * Get font family stack
     */
    private function getFontFamilyStack(string $font): string
    {
        $stacks = [
            'inter' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'roboto' => "'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'open-sans' => "'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'lato' => "'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'montserrat' => "'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'poppins' => "'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
            'raleway' => "'Raleway', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
        ];
        
        return $stacks[$font] ?? $stacks['inter'];
    }
    
    /**
     * Export all customizations
     */
    public function export(?int $userId = null): array
    {
        $export = [
            'theme' => $this->currentTheme,
            'exported_at' => date('Y-m-d H:i:s'),
            'customizations' => []
        ];
        
        $options = $this->getCustomizationOptions();
        foreach ($options as $category => $categoryData) {
            $export['customizations'][$category] = $this->getCategory($category);
        }
        
        return $export;
    }
    
    /**
     * Import customizations
     */
    public function import(array $data, ?int $userId = null): bool
    {
        if (empty($data['customizations']) || !is_array($data['customizations'])) {
            return false;
        }
        
        return $this->setMultiple($data['customizations'], $userId);
    }
}
