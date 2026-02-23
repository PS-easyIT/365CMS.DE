<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Academy365_Theme {
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
        $fonts = 'Open+Sans:wght@400;600;700&family=Raleway:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;700';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $fonts . '&display=swap">' . "\n";
    }
    public function outputCustomStyles(): void {
        try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { return; }
        $p = fn(string $s, string $k, string $d) => htmlspecialchars($c->get($s, $k, $d), ENT_QUOTES, 'UTF-8');
        echo '<style id="ac-custom-vars">:root{';
        echo '--primary-color:'   . $p('colors', 'primary_color',   '#7c3aed') . ';';
        echo '--primary-dark:'    . $p('colors', 'primary_dark',    '#6d28d9') . ';';
        echo '--secondary-color:' . $p('colors', 'secondary_color', '#4f46e5') . ';';
        echo '--accent-color:'    . $p('colors', 'accent_color',    '#f59e0b') . ';';
        echo '--bg-primary:'      . $p('colors', 'bg_primary',      '#faf5ff') . ';';
        echo '--bg-secondary:'    . $p('colors', 'bg_secondary',    '#ede9fe') . ';';
        echo '--text-primary:'    . $p('colors', 'text_primary',    '#1e1b4b') . ';';
        echo '--muted-color:'     . $p('colors', 'muted_color',     '#9ca3af') . ';';
        echo '--border-color:'    . $p('colors', 'border_color',    '#ddd6fe') . ';';
        echo '--badge-free:'      . $p('learning_courses', 'badge_color_free', '#10b981') . ';';
        echo '--badge-cert:'      . $p('learning_courses', 'badge_color_cert', '#f59e0b') . ';';
        echo '--badge-new:'       . $p('learning_courses', 'badge_color_new',  '#3b82f6') . ';';
        echo '--badge-best:'      . $p('learning_courses', 'badge_color_best', '#ef4444') . ';';
        echo '--cat-tech:'        . $p('colors', 'category_color_tech',     '#0ea5e9') . ';';
        echo '--cat-business:'    . $p('colors', 'category_color_business', '#f59e0b') . ';';
        echo '--cat-design:'      . $p('colors', 'category_color_design',   '#ec4899') . ';';
        echo '--cat-language:'    . $p('colors', 'category_color_language', '#10b981') . ';';
        echo '--font-body:'       . $p('typography', 'font_family_body',    '"Open Sans", sans-serif') . ';';
        echo '--font-heading:'    . $p('typography', 'font_family_heading', 'Raleway, sans-serif') . ';';
        echo '--font-ui:'         . $p('typography', 'font_family_ui',      '"Plus Jakarta Sans", sans-serif') . ';';
        echo '--header-height:'   . $p('layout', 'header_height', '72px') . ';';
        echo '}';
        $custom = $c->get('advanced', 'custom_css', '');
        if ($custom && trim($custom) !== '') echo $custom;
        echo '</style>' . "\n";
    }
    public function outputNavigationScript(): void {
        $url = \CMS\ThemeManager::instance()->getThemeUrl('academy365');
        echo '<script src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '/js/navigation.js" defer></script>' . "\n";
    }
}
Academy365_Theme::instance();
