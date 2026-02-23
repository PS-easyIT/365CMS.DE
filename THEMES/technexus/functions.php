<?php
declare(strict_types=1);

/**
 * TechNexus Theme - Functions
 *
 * IT & Tech Hub Theme für CMSv2
 *
 * @package TechNexus_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TECHNEXUS_VERSION', '1.0.0');
define('TECHNEXUS_DIR', THEME_PATH . 'technexus/');
define('TECHNEXUS_URL', \CMS\ThemeManager::instance()->getThemeUrl());

/**
 * TechNexus Theme Bootstrap
 */
class TechNexus_Theme
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        \CMS\Hooks::addAction('head', [$this, 'enqueueStyles']);
        \CMS\Hooks::addAction('head', [$this, 'outputGoogleFonts'], 5);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomStyles'], 20);
        \CMS\Hooks::addAction('head', [$this, 'outputMetaTags']);
        \CMS\Hooks::addAction('head', [$this, 'outputPreconnect'], 1);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomHeaderCode'], 99);
        \CMS\Hooks::addAction('before_footer', [$this, 'enqueueScripts']);
        \CMS\Hooks::addAction('before_footer', [$this, 'outputCookieBanner'], 10);
        \CMS\Hooks::addFilter('register_menu_locations', [$this, 'registerMenuLocations']);
        \CMS\Hooks::addAction('cms_init', [$this, 'seedDefaultMenus']);
    }

    public function enqueueStyles(): void
    {
        $cssFile = TECHNEXUS_DIR . 'style.css';
        $cssUrl  = TECHNEXUS_URL . 'style.css';
        $version = file_exists($cssFile) ? filemtime($cssFile) : TECHNEXUS_VERSION;
        echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl, ENT_QUOTES, 'UTF-8') . '?v=' . $version . '">' . "\n";
    }

    public function outputGoogleFonts(): void
    {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">' . "\n";
    }

    public function outputCustomStyles(): void
    {
        try {
            $c = \CMS\Services\ThemeCustomizer::instance();
        } catch (\Throwable $e) {
            return;
        }

        $primary      = $c->get('colors', 'primary_color', '#2563eb');
        $secondary    = $c->get('colors', 'secondary_color', '#1d4ed8');
        $accent       = $c->get('colors', 'accent_color', '#06b6d4');
        $text         = $c->get('colors', 'text_color', '#0f172a');
        $bg           = $c->get('colors', 'bg_color', '#f8fafc');
        $headerBg     = $c->get('header', 'header_bg_color', '#0f172a');
        $headerText   = $c->get('header', 'header_text_color', '#f1f5f9');
        $headerHeight = (int)$c->get('header', 'header_height', 72);
        $logoH        = (int)$c->get('header', 'logo_max_height', 40);
        $footerBg     = $c->get('footer', 'footer_bg_color', '#0f172a');
        $footerText   = $c->get('footer', 'footer_text_color', '#94a3b8');
        $borderRadius = (int)$c->get('layout', 'border_radius', 8);
        $container    = (int)$c->get('layout', 'container_width', 1280);
        $gridBg       = $c->get('layout', 'enable_grid_background', true);
        $customCss    = $c->get('advanced', 'custom_css', '');
        $heroStart    = $c->get('tech_hero', 'hero_gradient_start', '#0f172a');
        $heroEnd      = $c->get('tech_hero', 'hero_gradient_end', '#1e3a5f');

        $safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        echo '<style id="technexus-custom-styles">' . "\n";
        echo ':root {' . "\n";
        echo '  --primary-color: ' . $safe($primary) . ';' . "\n";
        echo '  --primary-hover: ' . $safe($secondary) . ';' . "\n";
        echo '  --accent-color: ' . $safe($accent) . ';' . "\n";
        echo '  --text-color: ' . $safe($text) . ';' . "\n";
        echo '  --text-primary: ' . $safe($text) . ';' . "\n";
        echo '  --heading-color: ' . $safe($text) . ';' . "\n";
        echo '  --background-color: ' . $safe($bg) . ';' . "\n";
        echo '  --header-bg: ' . $safe($headerBg) . ';' . "\n";
        echo '  --header-text: ' . $safe($headerText) . ';' . "\n";
        echo '  --footer-bg: ' . $safe($footerBg) . ';' . "\n";
        echo '  --footer-text: ' . $safe($footerText) . ';' . "\n";
        echo '  --border-radius: ' . $borderRadius . 'px;' . "\n";
        echo '  --container-max-width: ' . $container . 'px;' . "\n";
        echo '  --logo-max-height: ' . $logoH . 'px;' . "\n";
        echo '}' . "\n";
        echo '.site-header { height: ' . $headerHeight . 'px; }' . "\n";
        echo '.home-hero { --hero-grad-start: ' . $safe($heroStart) . '; --hero-grad-end: ' . $safe($heroEnd) . '; }' . "\n";
        if ($gridBg) {
            echo 'body { background-image: linear-gradient(rgba(37,99,235,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(37,99,235,0.04) 1px, transparent 1px); background-size: 32px 32px; }' . "\n";
        }
        if (!empty($customCss)) {
            echo '/* Custom CSS */' . "\n" . $customCss . "\n";
        }
        echo '</style>' . "\n";
    }

    public function outputMetaTags(): void
    {
        $title = \CMS\ThemeManager::instance()->getSiteTitle();
        $desc  = \CMS\ThemeManager::instance()->getSiteDescription();
        echo '<meta name="theme-color" content="#0f172a">' . "\n";
        if (!empty($desc)) {
            echo '<meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        echo '<meta property="og:site_name" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function outputPreconnect(): void
    {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>' . "\n";
    }

    public function outputCustomHeaderCode(): void
    {
        try {
            $code = \CMS\Services\SEOService::getInstance()->getCustomHeaderCode();
            if (!empty($code)) {
                echo "\n<!-- Custom Header Code -->\n" . $code . "\n<!-- /Custom Header Code -->\n";
            }
        } catch (\Throwable $e) {}
    }

    public function outputCookieBanner(): void
    {
        try {
            $cookieTpl = TECHNEXUS_DIR . 'templates/cookie-banner.php';
            if (file_exists($cookieTpl)) {
                include $cookieTpl;
            }
        } catch (\Throwable $e) {}
    }

    public function enqueueScripts(): void
    {
        $jsFile = TECHNEXUS_DIR . 'js/navigation.js';
        $jsUrl  = TECHNEXUS_URL . 'js/navigation.js';
        if (file_exists($jsFile)) {
            $version = filemtime($jsFile);
            echo '<script src="' . htmlspecialchars($jsUrl, ENT_QUOTES, 'UTF-8') . '?v=' . $version . '" defer></script>' . "\n";
        }
    }

    public function registerMenuLocations(array $locations): array
    {
        $locations['primary']    = 'Hauptnavigation';
        $locations['footer-nav'] = 'Footer-Navigation';
        $locations['tech-cats']  = 'Tech-Kategorien';
        return $locations;
    }

    public function seedDefaultMenus(): void
    {
        // Default-Menüs beim ersten Start anlegen (idempotent)
    }
}

// Theme bootstrap
TechNexus_Theme::instance();
