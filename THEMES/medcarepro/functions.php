<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class MedCare_Theme {
    private static ?self $instance = null;
    public static function instance(): self { if (self::$instance === null) self::$instance = new self(); return self::$instance; }
    private function __construct() { $this->registerHooks(); }
    private function registerHooks(): void {
        \CMS\Hooks::addAction('head',          [$this, 'outputGoogleFonts'],      5);
        \CMS\Hooks::addAction('head',          [$this, 'outputCustomStyles'],    15);
        \CMS\Hooks::addAction('before_footer', [$this, 'outputNavigationScript'],99);
        \CMS\Hooks::addAction('init',          [$this, 'registerNavMenus'],      10);
    }
    public function registerNavMenus(): void {
        \CMS\ThemeManager::instance()->registerMenuLocation('primary-nav', 'Hauptmenü');
        \CMS\ThemeManager::instance()->registerMenuLocation('footer-nav',  'Fußzeilen-Navigation');
        \CMS\ThemeManager::instance()->registerMenuLocation('footer-legal','Rechtliche Links');
    }
    public function outputGoogleFonts(): void {
        $fonts = 'Open+Sans:wght@400;600;700&family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:wght@400;700';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $fonts . '&display=swap">' . "\n";
    }
    public function outputCustomStyles(): void {
        try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { return; }
        $p = fn(string $s, string $k, string $d) => htmlspecialchars($c->get($s, $k, $d), ENT_QUOTES, 'UTF-8');
        echo '<style id="mc-custom-vars">:root{';
        echo '--primary-color:'    . $p('colors', 'primary_color',   '#0ea5e9') . ';';
        echo '--primary-dark:'     . $p('colors', 'primary_dark',    '#0284c7') . ';';
        echo '--secondary-color:'  . $p('colors', 'secondary_color', '#0c4a6e') . ';';
        echo '--accent-color:'     . $p('colors', 'accent_color',    '#10b981') . ';';
        echo '--bg-primary:'       . $p('colors', 'bg_primary',      '#f0f9ff') . ';';
        echo '--bg-secondary:'     . $p('colors', 'bg_secondary',    '#e0f2fe') . ';';
        echo '--text-primary:'     . $p('colors', 'text_primary',    '#0c1a2e') . ';';
        echo '--muted-color:'      . $p('colors', 'muted_color',     '#9ca3af') . ';';
        echo '--border-color:'     . $p('colors', 'border_color',    '#bae6fd') . ';';
        echo '--font-body:'        . $p('typography', 'font_family_body',    '"Open Sans", sans-serif') . ';';
        echo '--font-heading:'     . $p('typography', 'font_family_heading', '"Source Sans Pro", sans-serif') . ';';
        echo '--font-serif:'       . $p('typography', 'font_family_serif',   '"Source Serif Pro", serif') . ';';
        echo '--header-height:'    . $p('layout', 'header_height', '72px') . ';';
        echo '}';
        $custom = $c->get('advanced', 'custom_css', '');
        if ($custom && trim($custom) !== '') echo $custom;
        echo '</style>' . "\n";
    }
    public function outputNavigationScript(): void {
        $url = \CMS\ThemeManager::instance()->getThemeUrl('medcarepro');
        echo '<script src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '/js/navigation.js" defer></script>' . "\n";
    }
}
MedCare_Theme::instance();

// ── Theme Helper Functions ──────────────────────────────────────────────────

if (!function_exists('theme_is_logged_in')) {
    /**
     * Check whether the current visitor is logged in.
     */
    function theme_is_logged_in(): bool {
        try { return \CMS\Auth::instance()->isLoggedIn(); } catch (\Throwable $e) { return false; }
    }
}

if (!function_exists('theme_nav_menu')) {
    /**
     * Render a registered navigation menu as a <ul> list.
     *
     * @param string $location  Menu location slug (e.g. 'primary-nav')
     */
    function theme_nav_menu(string $location): void {
        try {
            $items = \CMS\ThemeManager::instance()->getMenu($location);
        } catch (\Throwable $e) {
            $items = [];
        }
        if (empty($items)) return;
        $cur = $_SERVER['REQUEST_URI'] ?? '';
        echo '<ul>';
        foreach ($items as $item) {
            $url    = htmlspecialchars($item['url']   ?? '#', ENT_QUOTES, 'UTF-8');
            $label  = htmlspecialchars($item['label'] ?? '',  ENT_QUOTES, 'UTF-8');
            $active = (!empty($item['active']) || (isset($item['url']) && rtrim($item['url'], '/') === rtrim(strtok($cur, '?'), '/')))
                      ? ' class="current-menu-item"' : '';
            echo '<li' . $active . '><a href="' . $url . '">' . $label . '</a></li>';
        }
        echo '</ul>';
    }
}

if (!function_exists('get_header')) {
    /**
     * Include the theme header template.
     */
    function get_header(): void {
        $path = __DIR__ . '/header.php';
        try {
            $tp = \CMS\ThemeManager::instance()->getThemePath('medcarepro') . '/header.php';
            if (file_exists($tp)) $path = $tp;
        } catch (\Throwable $e) {}
        if (file_exists($path)) require $path;
    }
}

if (!function_exists('get_footer')) {
    /**
     * Include the theme footer template.
     */
    function get_footer(): void {
        $path = __DIR__ . '/footer.php';
        try {
            $tp = \CMS\ThemeManager::instance()->getThemePath('medcarepro') . '/footer.php';
            if (file_exists($tp)) $path = $tp;
        } catch (\Throwable $e) {}
        if (file_exists($path)) require $path;
    }
}

if (!function_exists('mc_get_setting')) {
    /**
     * Shorthand for ThemeCustomizer::get() with safe fallback.
     *
     * @param string $section  Customizer section slug
     * @param string $key      Setting key
     * @param mixed  $default  Fallback value
     */
    function mc_get_setting(string $section, string $key, mixed $default = ''): mixed {
        try {
            return \CMS\Services\ThemeCustomizer::instance()->get($section, $key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('mc_get_flash')) {
    /**
     * Return and clear a session flash message array or null.
     *
     * @return array{type:string,message:string}|null
     */
    function mc_get_flash(): ?array {
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
        if (!empty($_SESSION['mc_flash'])) {
            $flash = $_SESSION['mc_flash'];
            unset($_SESSION['mc_flash']);
            return $flash;
        }
        return null;
    }
}

if (!function_exists('mc_set_flash')) {
    /**
     * Store a flash message in the session.
     *
     * @param string $type    'success' | 'error' | 'info'
     * @param string $message Flash message text
     */
    function mc_set_flash(string $type, string $message): void {
        if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
        $_SESSION['mc_flash'] = ['type' => $type, 'message' => $message];
    }
}
