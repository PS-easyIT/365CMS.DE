<?php
declare(strict_types=1);

/**
 * PersonalFlow Theme – Bootstrap & Customizer Integration
 *
 * @package PersonalFlow_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

final class PersonalFlow_Theme {
    private static ?self $instance = null;

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->registerHooks();
    }

    private function registerHooks(): void {
        \CMS\Hooks::addAction('head',          [$this, 'outputGoogleFonts'],   5);
        \CMS\Hooks::addAction('head',          [$this, 'outputCustomStyles'],  15);
        \CMS\Hooks::addAction('before_footer', [$this, 'outputNavigationScript'], 99);
        \CMS\Hooks::addAction('init',          [$this, 'registerNavMenus'],    10);
    }

    public function registerNavMenus(): void {
        \CMS\ThemeManager::instance()->registerMenuLocation('primary-nav', 'Hauptmenü');
        \CMS\ThemeManager::instance()->registerMenuLocation('footer-nav',  'Fußzeilen-Navigation');
        \CMS\ThemeManager::instance()->registerMenuLocation('footer-legal','Rechtliche Links');
    }

    public function outputGoogleFonts(): void {
        $fonts = 'Nunito:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;600;700&family=Oswald:wght@700';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $fonts . '&display=swap">' . "\n";
    }

    public function outputCustomStyles(): void {
        try {
            $c = \CMS\Services\ThemeCustomizer::instance();
        } catch (\Throwable $e) {
            return;
        }
        $p  = fn(string $s, string $k, string $d) => htmlspecialchars($c->get($s, $k, $d), ENT_QUOTES, 'UTF-8');

        echo '<style id="pf-custom-vars">:root{';
        echo '--primary-color:'    . $p('colors', 'primary_color',     '#f59e0b') . ';';
        echo '--primary-dark:'     . $p('colors', 'primary_dark',      '#d97706') . ';';
        echo '--secondary-color:'  . $p('colors', 'secondary_color',   '#7c3aed') . ';';
        echo '--accent-color:'     . $p('colors', 'accent_color',      '#10b981') . ';';
        echo '--bg-primary:'       . $p('colors', 'bg_primary',        '#fffbf5') . ';';
        echo '--bg-secondary:'     . $p('colors', 'bg_secondary',      '#fef3c7') . ';';
        echo '--bg-card:'          . $p('colors', 'bg_card',           '#fffde7') . ';';
        echo '--text-primary:'     . $p('colors', 'text_primary',      '#1c1917') . ';';
        echo '--text-secondary:'   . $p('colors', 'text_secondary',    '#57534e') . ';';
        echo '--muted-color:'      . $p('colors', 'muted_color',       '#a8a29e') . ';';
        echo '--border-color:'     . $p('colors', 'border_color',      '#fde68a') . ';';
        echo '--font-body:'        . $p('typography', 'font_family_body', 'Nunito, sans-serif') . ';';
        echo '--font-heading:'     . $p('typography', 'font_family_heading', '"Nunito Sans", sans-serif') . ';';
        echo '--font-stats:'       . $p('typography', 'font_family_stats', 'Oswald, sans-serif') . ';';
        echo '--header-height:'    . $p('layout', 'header_height', '70px') . ';';
        echo '}';
        $custom = $c->get('advanced', 'custom_css', '');
        if ($custom && trim($custom) !== '') {
            echo $custom;
        }
        echo '</style>' . "\n";
    }

    public function outputNavigationScript(): void {
        $themeUrl = \CMS\ThemeManager::instance()->getThemeUrl('personalflow');
        echo '<script src="' . htmlspecialchars($themeUrl, ENT_QUOTES, 'UTF-8') . '/js/navigation.js" defer></script>' . "\n";
    }
}

PersonalFlow_Theme::instance();
