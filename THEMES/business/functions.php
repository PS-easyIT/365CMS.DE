<?php
declare(strict_types=1);

/**
 * Business Presentation Theme – functions.php
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Business Theme Singleton
 */
class IT_Business_Theme
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Assets
        \CMS\Hooks::addAction('head',           [$this, 'enqueueStyles']);
        \CMS\Hooks::addAction('before_footer',  [$this, 'enqueueScripts']);

        // Meta-Tags
        \CMS\Hooks::addAction('head', [$this, 'outputMetaTags']);

        // Menü-Positionen via Filter registrieren
        \CMS\Hooks::addFilter('register_menu_locations', [$this, 'registerMenuLocations']);

        // Standard-Menüs beim ersten Start anlegen
        \CMS\Hooks::addAction('cms_init', [$this, 'seedDefaultMenus']);
    }

    // ── Assets ───────────────────────────────────────────────────────────────

    public function enqueueStyles(): void
    {
        $v   = defined('THEME_VERSION') ? THEME_VERSION : '1.0.0';
        $url = \CMS\ThemeManager::instance()->getThemeUrl();
        echo '<link rel="stylesheet" href="' . $url . '/style.css?v=' . $v . '">' . "\n";
    }

    public function enqueueScripts(): void
    {
        $v   = defined('THEME_VERSION') ? THEME_VERSION : '1.0.0';
        $url = \CMS\ThemeManager::instance()->getThemeUrl();
        echo '<script src="' . $url . '/js/navigation.js?v=' . $v . '"></script>' . "\n";
    }

    // ── Meta ─────────────────────────────────────────────────────────────────

    public function outputMetaTags(): void
    {
        $tm       = \CMS\ThemeManager::instance();
        $siteDesc = htmlspecialchars($tm->getSiteDescription(), ENT_QUOTES, 'UTF-8');
        $siteUrl  = htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8');
        echo '<meta name="description" content="' . $siteDesc . '">' . "\n";
        echo '<meta property="og:url" content="' . $siteUrl . '">' . "\n";
        echo '<meta name="theme-color" content="#0f172a">' . "\n";
        echo '<meta name="robots" content="index,follow">' . "\n";
    }

    // ── Menü-Positionen ───────────────────────────────────────────────────────

    public function registerMenuLocations(array $locations): array
    {
        $locations[] = ['slug' => 'primary',      'label' => 'Hauptnavigation (Header)'];
        $locations[] = ['slug' => 'footer-nav',   'label' => 'Footer-Navigation'];
        $locations[] = ['slug' => 'footer-legal', 'label' => 'Footer Rechtliche Links'];
        return $locations;
    }

    /**
     * Standard-Menüeinträge beim ersten Start anlegen (nur wenn leer)
     */
    public function seedDefaultMenus(): void
    {
        $tm = \CMS\ThemeManager::instance();

        $defaults = [
            'primary' => [
                ['label' => 'Start',       'url' => '/',            'target' => '_self'],
                ['label' => 'Über uns',    'url' => '/#ueber-uns',  'target' => '_self'],
                ['label' => 'Leistungen',  'url' => '/#leistungen', 'target' => '_self'],
                ['label' => 'Kontakt',     'url' => '/#kontakt',    'target' => '_self'],
            ],
            'footer-nav' => [
                ['label' => 'Startseite',  'url' => '/',            'target' => '_self'],
                ['label' => 'Über uns',    'url' => '/#ueber-uns',  'target' => '_self'],
                ['label' => 'Leistungen',  'url' => '/#leistungen', 'target' => '_self'],
                ['label' => 'Kontakt',     'url' => '/#kontakt',    'target' => '_self'],
            ],
            'footer-legal' => [
                ['label' => 'Impressum',   'url' => '/impressum',   'target' => '_self'],
                ['label' => 'Datenschutz', 'url' => '/datenschutz', 'target' => '_self'],
                ['label' => 'AGB',         'url' => '/agb',         'target' => '_self'],
            ],
        ];

        foreach ($defaults as $location => $items) {
            if (empty($tm->getMenu($location))) {
                $tm->saveMenu($location, $items);
            }
        }
    }

    /**
     * Theme-Konfiguration aus theme.json lesen
     */
    public function getConfig(string $key, mixed $default = ''): mixed
    {
        static $config = null;

        if ($config === null) {
            $jsonFile = THEME_PATH . 'business/theme.json';
            if (file_exists($jsonFile)) {
                $decoded = json_decode(file_get_contents($jsonFile), true);
                $config  = $decoded['settings'] ?? [];
            } else {
                $config = [];
            }
        }

        return $config[$key] ?? $default;
    }
}

// Theme initialisieren
IT_Business_Theme::instance();

// ── Global Helper ──────────────────────────────────────────────────────────────

if (!function_exists('biz_nav_menu')) {
    function biz_nav_menu(string $location = 'primary'): void
    {
        $tm    = \CMS\ThemeManager::instance();
        $items = $tm->getMenu($location);

        if (empty($items)) {
            return;
        }

        $requestUri = rtrim($_SERVER['REQUEST_URI'] ?? '/', '/');
        echo '<ul>' . "\n";
        foreach ($items as $item) {
            $path    = rtrim(parse_url($item['url'], PHP_URL_PATH) ?? '/', '/');
            $isActive = ($path === '' || $path === '/') ? ($requestUri === '' || $requestUri === '/') : str_starts_with($requestUri, $path);
            $class   = $isActive ? ' class="active"' : '';
            $target  = !empty($item['target']) && $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '';
            $url     = htmlspecialchars($item['url'],   ENT_QUOTES, 'UTF-8');
            $label   = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
            echo '<li' . $class . '><a href="' . $url . '"' . $target . '>' . $label . '</a></li>' . "\n";
        }
        echo '</ul>' . "\n";
    }
}

if (!function_exists('biz_config')) {
    function biz_config(string $key, mixed $default = ''): mixed
    {
        return IT_Business_Theme::instance()->getConfig($key, $default);
    }
}

if (!function_exists('biz_site_url')) {
    function biz_site_url(): string { return rtrim(SITE_URL, '/'); }
}

if (!function_exists('biz_site_title')) {
    function biz_site_title(): string
    {
        return htmlspecialchars(\CMS\ThemeManager::instance()->getSiteTitle(), ENT_QUOTES, 'UTF-8');
    }
}
