<?php
declare(strict_types=1);

/**
 * IT Expert Network Theme - Functions
 *
 * Theme-spezifische Funktionen, Hooks und Helper-Klassen für das CMSv2-System.
 *
 * @package IT_Expert_Network_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THEME_VERSION', '2.1.0');
define('THEME_DIR', THEME_PATH . '365Network/');
define('THEME_URL_BASE', \CMS\ThemeManager::instance()->getThemeUrl());

/**
 * Theme Bootstrap - Hooks & Assets registrieren
 */
class IT_Expert_Network_Theme
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
        // Assets im <head> einbinden
        \CMS\Hooks::addAction('head', [$this, 'enqueueStyles']);
        \CMS\Hooks::addAction('head', [$this, 'outputMetaTags']);
        \CMS\Hooks::addAction('head', [$this, 'outputGoogleFonts'], 5);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomStyles'], 20);

        // Performance Hints
        \CMS\Hooks::addAction('head', [$this, 'outputPreconnect'], 1);
        
        // Custom Header Code (SEO/Tracking)
        \CMS\Hooks::addAction('head', [$this, 'outputCustomHeaderCode'], 99);

        // Footer Scripts
        \CMS\Hooks::addAction('before_footer', [$this, 'enqueueScripts']);
        
        // Cookie Banner
        \CMS\Hooks::addAction('before_footer', [$this, 'outputCookieBanner'], 10);

        // Theme-Menüpositionen registrieren
        \CMS\Hooks::addFilter('register_menu_locations', [$this, 'registerMenuLocations']);

        // Standard-Menüeinträge beim ersten Start automatisch anlegen
        \CMS\Hooks::addAction('cms_init', [$this, 'seedDefaultMenus']);
    }
    
    /**
     * Output Custom Header Code from SEO Settings
     */
    public function outputCustomHeaderCode(): void
    {
        $code = \CMS\Services\SEOService::getInstance()->getCustomHeaderCode();
        if (!empty($code)) {
            echo "\n<!-- Custom Header Code -->\n";
            echo $code . "\n";
            echo "<!-- /Custom Header Code -->\n";
        }
    }

    /**
     * Cookie Banner einbinden (wenn aktiviert in Admin > Recht & Sicherheit > Cookie Managed)
     */
    public function outputCookieBanner(): void
    {
        $db = \CMS\Database::instance();
        // Check if cookie banner is enabled
        $enabled = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_consent_enabled'")->fetch();
        if (!$enabled || $enabled->option_value !== '1') {
            return;
        }

        // Load settings
        $settings = [];
        $keys = ['cookie_banner_position', 'cookie_banner_text', 'cookie_accept_text', 'cookie_essential_text', 'cookie_policy_url', 'cookie_primary_color'];
        foreach ($keys as $k) {
            $row = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$k])->fetch();
            $settings[$k] = $row ? $row->option_value : '';
        }

        // Defaults
        $pos = $settings['cookie_banner_position'] ?: 'bottom';
        $text = htmlspecialchars($settings['cookie_banner_text'] ?: 'Wir verwenden Cookies.', ENT_QUOTES, 'UTF-8');
        $btnAccept = htmlspecialchars($settings['cookie_accept_text'] ?: 'Akzeptieren', ENT_QUOTES, 'UTF-8');
        $btnEssential = htmlspecialchars($settings['cookie_essential_text'] ?: 'Nur Essenzielle', ENT_QUOTES, 'UTF-8');
        $linkPolicy = htmlspecialchars($settings['cookie_policy_url'] ?: '#', ENT_QUOTES, 'UTF-8');
        $color = htmlspecialchars($settings['cookie_primary_color'] ?: '#3b82f6', ENT_QUOTES, 'UTF-8');

        // Styles
        $style = 'background:white; color:#333; box-shadow:0 0 10px rgba(0,0,0,0.1); padding:1rem; z-index:9999; display:none;';
        if ($pos === 'bottom') {
            $style .= 'position:fixed; bottom:0; left:0; width:100%; border-top:1px solid #eee;';
        } else {
            $style .= 'position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:90%; max-width:500px; border-radius:8px;';
        }

        // HTML & Logic
        echo <<<HTML
        <style>
            #cms-cookie-banner { {$style} font-family:sans-serif; font-size:14px; line-height:1.5; }
            #cms-cookie-banner p { margin:0 0 1rem 0; }
            #cms-cookie-actions { display:flex; gap:10px; justify-content:flex-end; }
            .cms-cookie-btn { border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-weight:bold; }
            .cms-cookie-accept { background:{$color}; color:white; }
            .cms-cookie-essential { background:#eee; color:#333; }
        </style>
        <div id="cms-cookie-banner">
            <p>{$text} <a href="{$linkPolicy}" style="color:{$color}">Mehr erfahren</a></p>
            <div id="cms-cookie-actions">
                <button class="cms-cookie-btn cms-cookie-essential" onclick="cmsDeclineCookies()">{$btnEssential}</button>
                <button class="cms-cookie-btn cms-cookie-accept" onclick="cmsAcceptCookies()">{$btnAccept}</button>
            </div>
        </div>
        <script>
            (function() {
                var banner = document.getElementById('cms-cookie-banner');
                if (!localStorage.getItem('cms_cookie_consent')) {
                    banner.style.display = 'block';
                }
                window.cmsAcceptCookies = function() {
                    localStorage.setItem('cms_cookie_consent', 'all');
                    banner.style.display = 'none';
                    // Here one could trigger loading of analytics scripts
                };
                window.cmsDeclineCookies = function() {
                    localStorage.setItem('cms_cookie_consent', 'essential');
                    banner.style.display = 'none';
                };
            })();
        </script>
HTML;
    }

    /**
     * Theme-Menüpositionen registrieren
     */
    public function registerMenuLocations(array $locations): array
    {
        $locations[] = ['slug' => 'primary', 'label' => 'Hauptmenü (Header)'];
        $locations[] = ['slug' => 'mobile',  'label' => 'Mobiles Menü'];
        $locations[] = ['slug' => 'footer',  'label' => 'Footer-Navigation'];
        return $locations;
    }

    /**
     * Standard-Menüeinträge beim ersten Start anlegen (nur wenn leer)
     * Andere Themes können dasselbe Muster verwenden.
     */
    public function seedDefaultMenus(): void
    {
        $themeManager = \CMS\ThemeManager::instance();

        $defaults = [
            'primary' => [
                ['label' => 'Startseite',  'url' => '/',          'target' => '_self'],
                ['label' => 'Experten',    'url' => '/experts',   'target' => '_self'],
                ['label' => 'Unternehmen', 'url' => '/companies', 'target' => '_self'],
                ['label' => 'Events',      'url' => '/events',    'target' => '_self'],
            ],
            'mobile'  => [
                ['label' => 'Startseite',  'url' => '/',          'target' => '_self'],
                ['label' => 'Experten',    'url' => '/experts',   'target' => '_self'],
                ['label' => 'Unternehmen', 'url' => '/companies', 'target' => '_self'],
                ['label' => 'Events',      'url' => '/events',    'target' => '_self'],
                ['label' => 'Login',       'url' => '/login',     'target' => '_self'],
            ],
            'footer'  => [
                ['label' => 'Impressum',   'url' => '/impressum',   'target' => '_self'],
                ['label' => 'Datenschutz', 'url' => '/datenschutz', 'target' => '_self'],
                ['label' => 'Kontakt',     'url' => '/kontakt',     'target' => '_self'],
            ],
        ];

        foreach ($defaults as $location => $items) {
            // Nur anlegen wenn noch keine Einträge vorhanden
            if (empty($themeManager->getMenu($location))) {
                $themeManager->saveMenu($location, $items);
            }
        }
    }

    /**
     * Stylesheets einbinden
     */
    public function enqueueStyles(): void
    {
        $v = THEME_VERSION;
        echo '<link rel="stylesheet" href="' . THEME_URL_BASE . '/style.css?v=' . $v . '">' . "\n";
    }

    /**
     * JavaScript einbinden
     */
    public function enqueueScripts(): void
    {
        $v = THEME_VERSION;
        // Kein defer: Scripts stehen bereits am Ende des Body
        echo '<script src="' . THEME_URL_BASE . '/js/navigation.js?v=' . $v . '"></script>' . "\n";
        echo '<script src="' . THEME_URL_BASE . '/js/theme.js?v=' . $v . '"></script>' . "\n";
    }

    /**
     * Meta-Tags ausgeben
     */
    public function outputMetaTags(): void
    {
        $themeManager = \CMS\ThemeManager::instance();
        $siteDesc = htmlspecialchars($themeManager->getSiteDescription(), ENT_QUOTES, 'UTF-8');
        $siteTitle = htmlspecialchars($themeManager->getSiteTitle(), ENT_QUOTES, 'UTF-8');
        $siteUrl = htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8');

        echo '<meta name="description" content="' . $siteDesc . '">' . "\n";
        echo '<meta property="og:site_name" content="' . $siteTitle . '">' . "\n";
        echo '<meta property="og:url" content="' . $siteUrl . '">' . "\n";
        echo '<meta name="theme-color" content="#1e3a5f">' . "\n";
        echo '<meta name="robots" content="index,follow">' . "\n";
    }

    /**
     * Preconnect Hints für Performance
     */
    public function outputPreconnect(): void
    {
        // Don't preconnect to Google if we use local fonts
        $db = \CMS\Database::instance();
        $useLocal = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'")->fetch();
        if ($useLocal && $useLocal->option_value === '1') {
            return;
        }

        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
    }

    /**
     * Google Fonts einbinden (basierend auf Typography-Einstellungen)
     */
    public function outputGoogleFonts(): void
    {
        // Check if local fonts are enabled
        $db = \CMS\Database::instance();
        $useLocal = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'")->fetch();
        
        if ($useLocal && $useLocal->option_value === '1') {
            // Use local CSS if available
            if (file_exists(ASSETS_PATH . 'css/local-fonts.css')) {
                echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/local-fonts.css">' . "\n";
                // Preconnect not needed for local, but maybe keep for other external?
                // For now, we return, to avoid Google request
                return;
            }
        }

        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();
            $typo = $customizer->getCategory('typography');

            $googleFonts = ['inter', 'roboto', 'open-sans', 'lato', 'montserrat', 'poppins', 'raleway'];
            $fontMap = [
                'inter'       => 'Inter:wght@400;500;600;700;800',
                'roboto'      => 'Roboto:wght@400;500;700',
                'open-sans'   => 'Open+Sans:wght@400;600;700',
                'lato'        => 'Lato:wght@400;700',
                'montserrat'  => 'Montserrat:wght@400;500;600;700',
                'poppins'     => 'Poppins:wght@400;500;600;700',
                'raleway'     => 'Raleway:wght@400;600;700',
            ];

            $fontsToLoad = [];
            $baseFont    = $typo['font_family_base']    ?? 'inter';
            $headingFont = $typo['font_family_heading'] ?? 'inter';

            if (in_array($baseFont, $googleFonts, true) && isset($fontMap[$baseFont])) {
                $fontsToLoad[$baseFont] = $fontMap[$baseFont];
            }
            if ($headingFont !== $baseFont && in_array($headingFont, $googleFonts, true) && isset($fontMap[$headingFont])) {
                $fontsToLoad[$headingFont] = $fontMap[$headingFont];
            }

            if (!empty($fontsToLoad)) {
                $families = implode('&family=', array_values($fontsToLoad));
                $url = 'https://fonts.googleapis.com/css2?family=' . $families . '&display=swap';
                echo '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        } catch (\Throwable $e) {
            // Google Fonts nicht verfügbar – System-Fonts greifen
        }
    }

    /**
     * Custom CSS-Variablen aus DB-Einstellungen (ThemeCustomizer)
     */
    public function outputCustomStyles(): void
    {
        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();
            $css = $customizer->generateCSS();

            if (trim($css) !== '') {
                echo '<style id="cms-theme-customizer">' . "\n" . $css . '</style>' . "\n";
            }
        } catch (\Throwable $e) {
            // ThemeCustomizer nicht verfügbar – Default CSS aus style.css greift
        }
    }
}

// Theme initialisieren
IT_Expert_Network_Theme::instance();

/**
 * Helper: Aktuelle URL bestimmen
 */
function theme_current_url(): string
{
    $url = SITE_URL;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return rtrim($url, '/') . $uri;
}

/**
 * Helper: URL aktiv prüfen (für Navigations-Highlighting)
 */
function theme_is_active_url(string $url): bool
{
    $requestUri = rtrim($_SERVER['REQUEST_URI'] ?? '/', '/');
    $path = rtrim(parse_url($url, PHP_URL_PATH) ?? '/', '/');

    if ($path === '' || $path === '/') {
        return $requestUri === '' || $requestUri === '/';
    }

    return str_starts_with($requestUri, $path);
}

/**
 * Helper: CSRF-Token generieren
 */
function theme_csrf_token(string $action = 'form'): string
{
    return \CMS\Security::instance()->generateToken($action);
}

/**
 * Helper: CSRF-Token als Hidden-Field
 */
function theme_csrf_field(string $action = 'form'): void
{
    $token = theme_csrf_token($action);
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

/**
 * Helper: Flash-Message aus Session lesen & löschen
 */
function theme_get_flash(string $type = 'error'): string
{
    $key = ($type === 'success') ? 'success' : 'error';
    $msg = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $msg;
}

/**
 * Helper: Eingeloggter Benutzer?
 */
function theme_is_logged_in(): bool
{
    return \CMS\Auth::instance()->isLoggedIn();
}

/**
 * Helper: Navigationsmenü für eine Menü-Position rendern
 */
function theme_nav_menu(string $location = 'primary'): void
{
    $themeManager = \CMS\ThemeManager::instance();
    $menuItems    = $themeManager->getMenu($location);

    // FIX: Wenn explizit ein leeres Array gespeichert wurde (z.B. User hat alle Items gelöscht),
    // darf NICHT das Default-Menü angezeigt werden.
    // Wir prüfen daher, ob wir überhaupt schon mal was gespeichert haben (Seed-Logic).
    // Da seedDefaultMenus() beim Init läuft, sollte die DB eigentlich gefüllt sein.
    // Wenn hier leer zurückkommt, dann ist es entweder leer gespeichert oder DB-Fehler.
    // Wir zeigen das Fallback NUR, wenn wir wirklich gar nichts haben UND es keine gespeicherte Einstellung gibt.
    
    // Aber für den Moment: Wenn $menuItems leer ist, gehen wir davon aus, dass der User es geleert hat
    // oder etwas schief ging. Das Hardcoded-Fallback überschreibt sonst Benutzereingaben.
    
    // Besserer Ansatz: seedDefaultMenus füllt die DB. Wenn User löscht, ist die Liste leer.
    // Wenn Liste leer, soll nichts angezeigt werden.
    // DAS HIER WAR DER FEHLER: Das hardcoded Fallback hat immer gegriffen, wenn (aus irgendeinem Grund)
    // das Array leer war oder vielleicht nicht geladen werden konnte.
    
    // Wir deaktivieren das hardcoded Fallback hier, da seedDefaultMenus() das übernimmt via DB.
    if (empty($menuItems) && !in_array($location, ['primary', 'mobile', 'footer'])) {
       // nur returnen bei unbekannten locations
       return; 
    }
    
    // Wenn leer, dann leer lassen (User hat vielleicht alles gelöscht)
    if (empty($menuItems)) {
        // Optional: Debug-Ausgabe oder Admin-Hinweis
        // Wir returnen NICHTS, damit nichts gerendert wird (nur leeres UL evtl. vermeiden?)
        return; 
    }

    echo '<ul class="nav-menu" style="list-style:none;padding:0;margin:0;">' . "\n";
    foreach ($menuItems as $item) {
        $isActive = theme_is_active_url($item['url'] ?? '');
        $class    = $isActive ? ' class="active"' : '';
        $target   = !empty($item['target']) && $item['target'] === '_blank'
            ? ' target="_blank" rel="noopener noreferrer"' : '';
        $url   = htmlspecialchars($item['url'] ?? '#',   ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        echo '<li' . $class . '><a href="' . $url . '"' . $target . '>' . $label . '</a></li>' . "\n";
    }
    echo '</ul>' . "\n";
}
