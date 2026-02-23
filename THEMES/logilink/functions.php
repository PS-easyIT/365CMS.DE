<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class LogiLink_Theme {
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
        $fonts = 'Inter:wght@400;500;600;700;800&family=Roboto+Condensed:wght@600;700&family=JetBrains+Mono:wght@400;600';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $fonts . '&display=swap">' . "\n";
    }
    public function outputCustomStyles(): void {
        try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { return; }
        $p = fn(string $s, string $k, string $d) => htmlspecialchars($c->get($s, $k, $d), ENT_QUOTES, 'UTF-8');
        echo '<style id="ll-custom-vars">:root{';
        echo '--primary-color:'    . $p('colors', 'primary_color',   '#0284c7') . ';';
        echo '--primary-dark:'     . $p('colors', 'primary_dark',    '#0369a1') . ';';
        echo '--secondary-color:'  . $p('colors', 'secondary_color', '#1e3a5f') . ';';
        echo '--accent-color:'     . $p('colors', 'accent_color',    '#fbbf24') . ';';
        echo '--bg-primary:'       . $p('colors', 'bg_primary',      '#f0f9ff') . ';';
        echo '--bg-secondary:'     . $p('colors', 'bg_secondary',    '#e0f2fe') . ';';
        echo '--text-primary:'     . $p('colors', 'text_primary',    '#0c1a2e') . ';';
        echo '--muted-color:'      . $p('colors', 'muted_color',     '#9ca3af') . ';';
        echo '--border-color:'     . $p('colors', 'border_color',    '#bae6fd') . ';';
        echo '--status-warehouse:' . $p('logistics_tracking', 'color_warehouse', '#6b7280') . ';';
        echo '--status-picked:'    . $p('logistics_tracking', 'color_picked',    '#3b82f6') . ';';
        echo '--status-transit:'   . $p('logistics_tracking', 'color_transit',   '#f59e0b') . ';';
        echo '--status-delivered:' . $p('logistics_tracking', 'color_delivered', '#10b981') . ';';
        echo '--status-delayed:'   . $p('logistics_tracking', 'color_delayed',   '#ef4444') . ';';
        echo '--status-returned:'  . $p('logistics_tracking', 'color_returned',  '#8b5cf6') . ';';
        echo '--font-body:'        . $p('typography', 'font_family_body',    'Inter, sans-serif') . ';';
        echo '--font-mono:'        . $p('typography', 'font_family_mono',    '"JetBrains Mono", monospace') . ';';
        echo '--header-height:'    . $p('layout', 'header_height', '68px') . ';';
        echo '}';
        $custom = $c->get('advanced', 'custom_css', '');
        if ($custom && trim($custom) !== '') echo $custom;
        echo '</style>' . "\n";
    }
    public function outputNavigationScript(): void {
        $url = \CMS\ThemeManager::instance()->getThemeUrl('logilink');
        echo '<script src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '/js/navigation.js" defer></script>' . "\n";
    }
}
LogiLink_Theme::instance();
