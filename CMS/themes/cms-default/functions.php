<?php
declare(strict_types=1);

/**
 * Meridian CMS Default Theme – Functions
 *
 * @package CMSDefault
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MERIDIAN_THEME_VERSION', '1.0.0');
define('MERIDIAN_THEME_DIR',     THEME_PATH . 'cms-default/');
define('MERIDIAN_THEME_URL',     \CMS\ThemeManager::instance()->getThemeUrl());

/**
 * Meridian CMS Default Theme Bootstrap
 */
class MeridianCMSDefaultTheme
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
        // Head-Assets
        \CMS\Hooks::addAction('head', [$this, 'outputPreconnect'],       1);
        \CMS\Hooks::addAction('head', [$this, 'outputGoogleFonts'],      5);
        \CMS\Hooks::addAction('head', [$this, 'enqueueStyles'],         10);
        \CMS\Hooks::addAction('head', [$this, 'outputMetaTags'],        15);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomStyles'],    20);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomHeaderCode'], 99);

        // Footer-Scripts
        \CMS\Hooks::addAction('before_footer', [$this, 'enqueueScripts'], 10);
        \CMS\Hooks::addAction('before_footer', [$this, 'outputCookieBanner'], 20);

        // Menüpositionen
        \CMS\Hooks::addFilter('register_menu_locations', [$this, 'registerMenuLocations']);

        // Standard-Menü beim ersten Start
        \CMS\Hooks::addAction('cms_init', [$this, 'seedDefaultMenus']);
    }

    // ─── Preconnect / Performance ───────────────────────────────────────────

    public function outputPreconnect(): void
    {
        // Kein Preconnect zu Google, wenn Local Fonts aktiv sind
        if ($this->isLocalFontsEnabled()) {
            return;
        }
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }

    /**
     * Prüft ob Local Fonts (DSGVO) im CMS aktiviert sind
     */
    private function isLocalFontsEnabled(): bool
    {
        try {
            $db  = \CMS\Database::instance();
            $row = $db->execute(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'"
            )->fetch();
            return ($row && $row->option_value === '1');
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ─── Google Fonts / Local Fonts ────────────────────────────────────────────

    public function outputGoogleFonts(): void
    {
        // 1. CMS-weit: Local Fonts (DSGVO) haben Vorrang
        if ($this->isLocalFontsEnabled()) {
            $localCssPath = defined('ASSETS_PATH') ? ASSETS_PATH . 'css/local-fonts.css' : '';
            $localCssUrl  = defined('SITE_URL')    ? SITE_URL    . '/assets/css/local-fonts.css' : '';
            if ($localCssPath && file_exists($localCssPath) && $localCssUrl) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($localCssUrl, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
            return; // Kein Google-Fonts-Request
        }

        // 2. Customizer: google_fonts = false → gar keine externe Schrift
        try {
            $loadFonts = \CMS\Services\ThemeCustomizer::instance()->get('typography', 'google_fonts', true);
        } catch (\Throwable $e) {
            $loadFonts = true;
        }

        if ($loadFonts) {
            // Schriftart-Auswahl aus Customizer berücksichtigen
            try {
                $c           = \CMS\Services\ThemeCustomizer::instance();
                $fontBody    = (string)$c->get('typography', 'font_family_body',    'dm-sans');
                $fontHeading = (string)$c->get('typography', 'font_family_heading', 'libre-baskerville');
            } catch (\Throwable $e) {
                $fontBody    = 'dm-sans';
                $fontHeading = 'libre-baskerville';
            }

            $googleMap = [
                'dm-sans'           => 'DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400',
                'libre-baskerville' => 'Libre+Baskerville:ital,wght@0,400;0,700;1,400',
                'inter'             => 'Inter:wght@400;500;600;700',
                'playfair-display'  => 'Playfair+Display:ital,wght@0,400;0,700;1,400',
                'merriweather'      => 'Merriweather:ital,wght@0,400;0,700;1,400',
            ];

            $families = [];
            if (isset($googleMap[$fontBody]))    { $families[] = $googleMap[$fontBody]; }
            if ($fontHeading !== $fontBody && isset($googleMap[$fontHeading])) {
                $families[] = $googleMap[$fontHeading];
            }
            // DM Mono immer mitladen (für Code-Blöcke)
            if (!in_array('DM+Mono:wght@400;500', $families, true)) {
                $families[] = 'DM+Mono:wght@400;500';
            }

            $url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
            echo '<link href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="stylesheet">' . "\n";
        }
    }

    // ─── Stylesheets ────────────────────────────────────────────────────────

    public function enqueueStyles(): void
    {
        $themeUrl = MERIDIAN_THEME_URL;
        $version  = MERIDIAN_THEME_VERSION;
        echo '<link rel="stylesheet" href="' . htmlspecialchars($themeUrl, ENT_QUOTES, 'UTF-8') . '/style.css?v=' . $version . '">' . "\n";
    }

    // ─── Meta Tags ──────────────────────────────────────────────────────────

    public function outputMetaTags(): void
    {
        try {
            $seo = \CMS\Services\SEOService::getInstance();
            echo $seo->renderCurrentHeadTags();
        } catch (\Throwable $e) {
        }
        echo '<meta name="theme-color" content="#f7f6f2">' . "\n";
    }

    // ─── Custom CSS aus Customizer ───────────────────────────────────────────

    public function outputCustomStyles(): void
    {
        try {
            $c = \CMS\Services\ThemeCustomizer::instance();

            // ── Farben ────────────────────────────────────────────────────────
            $accent       = (string)$c->get('colors', 'accent_color',        '#c0862a');
            $accentDark   = (string)$c->get('colors', 'accent_dark_color',   '#a06b18');
            $ink          = (string)$c->get('colors', 'ink_color',           '#1a1a18');
            $inkSoft      = (string)$c->get('colors', 'ink_soft_color',      '#3d3d3a');
            $inkMuted     = (string)$c->get('colors', 'ink_muted_color',     '#7a7a74');
            $ground       = (string)$c->get('colors', 'ground_color',        '#f7f6f2');
            $surface      = (string)$c->get('colors', 'surface_color',       '#ffffff');
            $surfaceTint  = (string)$c->get('colors', 'surface_tint_color',  '#f2f1ec');
            $rule         = (string)$c->get('colors', 'rule_color',          '#e2e0d8');
            $headerBg     = (string)$c->get('colors', 'header_bg_color',     '#ffffff');
            $stripe       = (string)$c->get('colors', 'header_stripe_color', '#1a1a18');
            $linkColor    = (string)$c->get('colors', 'link_color',          $accent);
            $linkHover    = (string)$c->get('colors', 'link_hover_color',    $accentDark);
            $catBarBg     = (string)$c->get('colors', 'category_bar_bg',     '#f2f1ec');
            $catBarText   = (string)$c->get('colors', 'category_bar_text',   '#3d3d3a');

            // ── Footer ────────────────────────────────────────────────────────
            $footerBg     = (string)$c->get('footer', 'footer_bg_color',     '#1a1a18');
            $footerText   = (string)$c->get('footer', 'footer_text_color',   '#9a9a94');
            $footerAccent = (string)$c->get('footer', 'footer_accent_color', '#c0862a');

            // ── Layout ────────────────────────────────────────────────────────
            $maxWidth     = max(600, (int)$c->get('layout', 'max_width',       1140));
            $colWidth     = max(300, (int)$c->get('layout', 'post_col_width',   680));
            $borderRadius = max(0,   (int)$c->get('layout', 'border_radius',     3));
            $cardGap      = max(4,   (int)$c->get('layout', 'card_gap',          24));
            $stickyHeader = (string)$c->get('layout', 'sticky_header', '1') !== '0';

            // ── Typografie ────────────────────────────────────────────────────
            $baseFontSz    = max(10, (int)$c->get('typography', 'font_size_base',      15));
            $lineHeight    = (float)str_replace(',', '.', (string)$c->get('typography', 'line_height', '1.6'));
            if ($lineHeight < 1.0 || $lineHeight > 3.0) { $lineHeight = 1.6; }
            $headingWeight = (int)$c->get('typography', 'heading_weight', 700);
            if (!in_array($headingWeight, [600, 700, 800, 900])) { $headingWeight = 700; }
            $fontBody      = (string)$c->get('typography', 'font_family_body',    'dm-sans');
            $fontHeading   = (string)$c->get('typography', 'font_family_heading', 'libre-baskerville');
            $lsHeadings    = (string)$c->get('typography', 'letter_spacing_headings', '0');
            $h1Size        = max(18, (int)$c->get('typography', 'h1_size', 38));
            $h2Size        = max(14, (int)$c->get('typography', 'h2_size', 28));
            $h3Size        = max(12, (int)$c->get('typography', 'h3_size', 22));

            // ── Navigation ────────────────────────────────────────────────────
            $navFontSize   = max(11, (int)$c->get('navigation', 'nav_font_size',     14));
            $navUppercase  = (string)$c->get('navigation', 'nav_uppercase', '0') !== '0';
            $navLetterSp   = (string)$c->get('navigation', 'nav_letter_spacing', '0');

            // ── Header-Streifen ───────────────────────────────────────────────
            $stripeEnabled = (string)$c->get('header', 'header_stripe_enabled', '1') !== '0';

            // ── Erweitert ─────────────────────────────────────────────────────
            $customCss     = (string)$c->get('advanced', 'custom_css', '');

            // ── Schrift-Familien-Mapping ──────────────────────────────────────
            $fontBodyCss = match($fontBody) {
                'system-ui'       => 'system-ui, -apple-system, sans-serif',
                'georgia'         => "'Georgia', serif",
                'times-new-roman' => "'Times New Roman', Times, serif",
                'inter'           => "'Inter', system-ui, sans-serif",
                default           => "'DM Sans', system-ui, sans-serif",
            };
            $fontHeadingCss = match($fontHeading) {
                'georgia'          => "Georgia, serif",
                'merriweather'     => "'Merriweather', Georgia, serif",
                'playfair-display' => "'Playfair Display', Georgia, serif",
                'system-ui'        => "system-ui, -apple-system, sans-serif",
                default            => "'Libre Baskerville', Georgia, serif",
            };

            $esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

            echo '<style id="meridian-custom-vars">' . "\n";

            // ── CSS Custom Properties (:root) ─────────────────────────────────
            // Abgeleitete Farben berechnen (leere DB-Werte auf Style.css-Defaults zurückfallen)
            $accent      = $accent      ?: '#c0862a';
            $accentDark  = $accentDark  ?: '#a06b18';
            $ink         = $ink         ?: '#1a1a18';
            $inkSoft     = $inkSoft     ?: '#3d3d3a';
            $inkMuted    = $inkMuted    ?: '#7a7a74';
            $ground      = $ground      ?: '#f7f6f2';
            $surface     = $surface     ?: '#ffffff';
            $surfaceTint = $surfaceTint ?: '#f2f1ec';
            $rule        = $rule        ?: '#e2e0d8';
            $headerBg    = $headerBg    ?: '#ffffff';
            $stripe      = $stripe      ?: '#1a1a18';
            $linkColor   = $linkColor   ?: $accent;
            $linkHover   = $linkHover   ?: $accentDark;
            $catBarBg    = $catBarBg    ?: '#f2f1ec';
            $catBarText  = $catBarText  ?: '#3d3d3a';
            $footerBg    = $footerBg    ?: '#1a1a18';
            $footerText  = $footerText  ?: '#9a9a94';
            $footerAccent = $footerAccent ?: '#c0862a';
            $inkGhost = $this->lightenHex($inkMuted, 0.46); // ~#b7b7b4 (ähnlich #b8b8b0)

            echo ':root {' . "\n";
            echo '  --accent:           ' . $esc($accent)       . ";\n";
            echo '  --accent-dark:      ' . $esc($accentDark)   . ";\n";
            echo '  --accent-light:     ' . $this->hexToRgba($accent, 0.08) . ";\n";
            echo '  --accent-mid:       ' . $this->hexToRgba($accent, 0.15) . ";\n";
            echo '  --ink:              ' . $esc($ink)          . ";\n";
            echo '  --ink-soft:         ' . $esc($inkSoft)      . ";\n";
            echo '  --ink-muted:        ' . $esc($inkMuted)     . ";\n";
            echo '  --ink-ghost:        ' . $esc($inkGhost)     . ";\n";
            echo '  --ground:           ' . $esc($ground)       . ";\n";
            echo '  --surface:          ' . $esc($surface)      . ";\n";
            echo '  --surface-tint:     ' . $esc($surfaceTint)  . ";\n";
            echo '  --rule:             ' . $esc($rule)         . ";\n";
            echo '  --rule-heavy:       ' . $esc($rule)         . ";\n";
            echo '  --tag-bg:           ' . $esc($surfaceTint)  . ";\n";
            echo '  --tag-color:        ' . $esc($inkMuted)     . ";\n";
            echo '  --max:              ' . $maxWidth           . "px;\n";
            echo '  --col:              ' . $colWidth           . "px;\n";
            echo '  --r:                ' . $borderRadius       . "px;\n";
            echo '  --font-sans:        ' . $fontBodyCss        . ";\n";
            echo '  --font-serif:       ' . $fontHeadingCss     . ";\n";
            echo '  --card-gap:         ' . $cardGap            . "px;\n";
            echo '}' . "\n";

            // ── Basis-Elemente ────────────────────────────────────────────────
            echo 'body { font-size:' . $baseFontSz . 'px; line-height:' . $lineHeight . '; background:' . $esc($ground) . '; color:' . $esc($ink) . "; }\n";
            echo 'h1,h2,h3,h4,h5,h6 { font-weight:' . $headingWeight . '; letter-spacing:' . $esc($lsHeadings) . "; }\n";
            echo 'h1 { font-size:' . $h1Size . "px; }\n";
            echo 'h2 { font-size:' . $h2Size . "px; }\n";
            echo 'h3 { font-size:' . $h3Size . "px; }\n";
            // Post-Body-Links separat, damit globaler a:hover nicht Menü/Buttons betrifft
            echo '.post-body a, .article-content a { color:' . $esc($linkColor) . "; }\n";
            echo '.post-body a:hover, .article-content a:hover { color:' . $esc($linkHover) . "; }\n";

            // ── Header ────────────────────────────────────────────────────────
            echo '.site-header { background:' . $esc($headerBg) . "; }\n";
            echo '.site-header::before { height:' . ($stripeEnabled ? '3px' : '0') . '; background:' . $esc($stripe) . "; }\n";
            if (!$stickyHeader) {
                echo ".site-header { position:static !important; top:auto !important; }\n";
            }

            // ── Navigation ────────────────────────────────────────────────────
            $navTransform = $navUppercase ? 'uppercase' : 'none';
            echo '.main-nav a, .nav-link { font-size:' . $navFontSize . 'px; text-transform:' . $navTransform . '; letter-spacing:' . $esc($navLetterSp) . "; }\n";
            echo '.category-bar { background:' . $esc($catBarBg) . "; }\n";
            echo '.category-bar a, .category-bar .cat-label { color:' . $esc($catBarText) . "; }\n";
            echo '.category-bar a:hover, .category-bar a.active { color:' . $esc($accent) . "; }\n";

            // ── Karten & Grid ─────────────────────────────────────────────────
            echo '.card-grid, .articles-grid, .dashboard-grid { gap:' . $cardGap . "px; }\n";
            echo '.article-row + .article-row { margin-top:' . $cardGap . "px; }\n";

            // ── Footer ────────────────────────────────────────────────────────
            echo 'footer, .site-footer { background:' . $esc($footerBg) . '; color:' . $esc($footerText) . "; }\n";
            echo 'footer a, .site-footer a { color:' . $esc($footerText) . "; }\n";
            echo 'footer a:hover, .site-footer a:hover, .ft-col a:hover { color:' . $esc($footerAccent) . " !important; }\n";
            echo '.ft-col h4, .footer-col-title { color:' . $esc($footerAccent) . "; }\n";
            echo '.ft-copyright { color:' . $esc($footerText) . "; opacity:.7; }\n";

            // ── Custom CSS ────────────────────────────────────────────────────
            if ($customCss && trim($customCss) !== '') {
                echo "\n/* ── Custom CSS (Theme Customizer) ── */\n" . $customCss . "\n";
            }

            echo '</style>' . "\n";

        } catch (\Throwable $e) {
            error_log('meridian outputCustomStyles error: ' . $e->getMessage());
        }
    }

    /**
     * Hilfsfunktion: HEX zu rgba()
     */
    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
    }

    /**
     * Hilfsfunktion: Farbe in Richtung Weiß aufhellen (für --ink-ghost Ableitung)
     * $factor 0 = unverändert · 1 = weiß
     */
    private function lightenHex(string $hex, float $factor): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = (int)round(hexdec(substr($hex, 0, 2)) + (255 - hexdec(substr($hex, 0, 2))) * $factor);
        $g = (int)round(hexdec(substr($hex, 2, 2)) + (255 - hexdec(substr($hex, 2, 2))) * $factor);
        $b = (int)round(hexdec(substr($hex, 4, 2)) + (255 - hexdec(substr($hex, 4, 2))) * $factor);
        return sprintf('#%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
    }

    // ─── Custom Header Code (SEO / Tracking) ─────────────────────────────────

    public function outputCustomHeaderCode(): void
    {
        try {
            $code = \CMS\Services\SEOService::getInstance()->getCustomHeaderCode();
            if (!empty($code)) {
                echo "\n<!-- Custom Header Code -->\n" . $code . "\n<!-- /Custom Header Code -->\n";
            }
        } catch (\Throwable $e) {
            // SEO Service nicht verfügbar
        }
        // Customizer: eigener Head-Code (Tracking, Analytics etc.)
        try {
            $customHeadCode = \CMS\Services\ThemeCustomizer::instance()->get('advanced', 'custom_head_code', '');
            if (!empty(trim($customHeadCode))) {
                echo "\n<!-- Theme Custom Head Code -->\n" . $customHeadCode . "\n<!-- /Theme Custom Head Code -->\n";
            }
        } catch (\Throwable $e) {}
    }

    // ─── JavaScript ──────────────────────────────────────────────────────────

    public function enqueueScripts(): void
    {
        $themeUrl = MERIDIAN_THEME_URL;
        $version  = MERIDIAN_THEME_VERSION;
        echo '<script src="' . htmlspecialchars($themeUrl, ENT_QUOTES, 'UTF-8') . '/js/theme.js?v=' . $version . '" defer></script>' . "\n";
        // Footer-Code aus Customizer
        try {
            $footerCode = \CMS\Services\ThemeCustomizer::instance()->get('advanced', 'custom_footer_code', '');
            if (!empty(trim($footerCode))) {
                echo "\n<!-- Theme Custom Footer Code -->\n" . $footerCode . "\n<!-- /Theme Custom Footer Code -->\n";
            }
        } catch (\Throwable $e) {}
    }

    // ─── Cookie Banner ────────────────────────────────────────────────────────

    public function outputCookieBanner(): void
    {
        if (class_exists('\\CMS\\Services\\CookieConsentService')) {
            try {
                if (\CMS\Services\CookieConsentService::getInstance()->isManagedExternally()) {
                    return;
                }
            } catch (\Throwable $e) {
                // Fallback auf Legacy-Banner
            }
        }

        try {
            $db      = \CMS\Database::instance();
            $enabled = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_consent_enabled'")->fetch();
            if (!$enabled || $enabled->option_value !== '1') {
                return;
            }

            $keys = ['cookie_banner_text', 'cookie_accept_text', 'cookie_essential_text', 'cookie_policy_url'];
            $s = [];
            foreach ($keys as $k) {
                $row  = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$k])->fetch();
                $s[$k] = $row ? (string)$row->option_value : '';
            }
            $text    = htmlspecialchars($s['cookie_banner_text']    ?: 'Wir verwenden Cookies.', ENT_QUOTES, 'UTF-8');
            $accept  = htmlspecialchars($s['cookie_accept_text']    ?: 'Akzeptieren',             ENT_QUOTES, 'UTF-8');
            $essential = htmlspecialchars($s['cookie_essential_text'] ?: 'Nur Essenzielle',       ENT_QUOTES, 'UTF-8');
            $policy  = htmlspecialchars($s['cookie_policy_url']     ?: '#',                       ENT_QUOTES, 'UTF-8');
        } catch (\Throwable $e) {
            return;
        }

        echo <<<HTML
<div id="cms-cookie-bar" style="display:none;position:fixed;bottom:0;left:0;width:100%;background:var(--ink);color:rgba(255,255,255,.7);padding:.9rem 1.5rem;z-index:9999;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;box-shadow:0 -2px 10px rgba(0,0,0,.2);">
    <p style="margin:0;font-size:.82rem;line-height:1.6;">{$text} <a href="{$policy}" style="color:var(--accent);text-decoration:underline;">Mehr erfahren</a></p>
    <div style="display:flex;gap:.6rem;flex-shrink:0;">
        <button onclick="cmsDeclineCookies()" style="padding:.35rem .85rem;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:3px;color:rgba(255,255,255,.7);font-size:.78rem;cursor:pointer;">{$essential}</button>
        <button onclick="cmsAcceptCookies()" style="padding:.35rem .85rem;background:var(--accent);border:1px solid var(--accent);border-radius:3px;color:#fff;font-size:.78rem;font-weight:600;cursor:pointer;">{$accept}</button>
    </div>
</div>
<script>
(function(){
    var b = document.getElementById('cms-cookie-bar');
    if (b && !localStorage.getItem('cms_cookie_consent')) { b.style.display = 'flex'; }
    window.cmsAcceptCookies   = function(){ localStorage.setItem('cms_cookie_consent','all');       if(b) b.style.display='none'; };
    window.cmsDeclineCookies  = function(){ localStorage.setItem('cms_cookie_consent','essential'); if(b) b.style.display='none'; };
})();
</script>
HTML;
    }

    // ─── Menüs ────────────────────────────────────────────────────────────────

    public function registerMenuLocations(array $locations): array
    {
        $locations[] = ['slug' => 'primary', 'label' => 'Hauptmenü (Header)'];
        $locations[] = ['slug' => 'secondary', 'label' => 'Sekundäres Menü (unter Header)'];
        $locations[] = ['slug' => 'mobile',  'label' => 'Mobiles Menü'];
        $locations[] = ['slug' => 'footer',  'label' => 'Footer-Navigation'];
        return $locations;
    }

    public function seedDefaultMenus(): void
    {
        $themeManager = \CMS\ThemeManager::instance();

        $defaults = [
            'primary' => [
                ['label' => 'Startseite',  'url' => '/',          'target' => '_self'],
                ['label' => 'Blog',        'url' => '/blog',      'target' => '_self'],
            ],
            'mobile'  => [
                ['label' => 'Startseite',  'url' => '/',          'target' => '_self'],
                ['label' => 'Blog',        'url' => '/blog',      'target' => '_self'],
                ['label' => 'Anmelden',    'url' => '/login',     'target' => '_self'],
            ],
            'footer'  => [
                ['label' => 'Impressum',   'url' => '/impressum',   'target' => '_self'],
                ['label' => 'Datenschutz', 'url' => '/datenschutz', 'target' => '_self'],
                ['label' => 'Kontakt',     'url' => '/kontakt',     'target' => '_self'],
            ],
        ];

        foreach ($defaults as $location => $items) {
            if (empty($themeManager->getMenu($location))) {
                $themeManager->saveMenu($location, $items);
            }
        }
    }
}

// Theme initialisieren
MeridianCMSDefaultTheme::instance();

/**
 * Globaler Wrapper – direkt in header.php o.ä. Templates aufrufbar.
 * Gibt den <style>-Block mit allen CSS-Variablen aus dem Customizer aus.
 */
function meridian_output_custom_styles(): void
{
    MeridianCMSDefaultTheme::instance()->outputCustomStyles();
}

/**
 * Globaler Wrapper für Preconnect + Fonts.
 * Berücksichtigt automatisch lokale Schriften (DSGVO) und Customizer-Einstellungen.
 */
function meridian_output_fonts(): void
{
    MeridianCMSDefaultTheme::instance()->outputPreconnect();
    MeridianCMSDefaultTheme::instance()->outputGoogleFonts();
}

// ─── Template Helper-Funktionen ─────────────────────────────────────────────

/**
 * Lesezeit in Minuten berechnen (ca. 200 Wörter/Min)
 */
function meridian_reading_time(string $content): int
{
    $wordCount = str_word_count(strip_tags($content));
    return max(1, (int)ceil($wordCount / 200));
}

/**
 * Autor-Initialen ermitteln (für Avatar-Fallback)
 */
function meridian_author_initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
    return strtoupper(mb_substr($name, 0, 2));
}

/**
 * Datum auf Deutsch formatieren (kurz)
 */
function meridian_format_date(string $dateStr, bool $short = true): string
{
    if (empty($dateStr)) {
        return '';
    }
    $ts = strtotime($dateStr);
    if (!$ts) {
        return htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8');
    }
    $months = ['Jan.','Feb.','März','Apr.','Mai','Juni','Juli','Aug.','Sep.','Okt.','Nov.','Dez.'];
    $day    = (int)date('j', $ts);
    $month  = $months[(int)date('n', $ts) - 1];
    $year   = (int)date('Y', $ts);
    if ($short) {
        return $day . '. ' . $month . ' ' . $year;
    }
    return $day . '. ' . $month . ' ' . $year;
}

/**
 * Excerpt kürzen
 */
function meridian_excerpt(string $content, int $maxChars = 160): string
{
    $text = meridian_excerpt_plain_text($content);
    if (mb_strlen($text) <= $maxChars) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    $cut = mb_substr($text, 0, $maxChars);
    $lastSpace = mb_strrpos($cut, ' ');
    if ($lastSpace !== false) {
        $cut = mb_substr($cut, 0, $lastSpace);
    }
    return htmlspecialchars($cut, ENT_QUOTES, 'UTF-8') . '…';
}

/**
 * Wandelt HTML oder Editor.js-JSON in reinen Text für Excerpts um.
 */
function meridian_excerpt_plain_text(string $content): string
{
    $content = trim($content);
    if ($content === '') {
        return '';
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
        $html = '';
        if (class_exists('\\CMS\\Services\\EditorJsRenderer')) {
            try {
                $html = \CMS\Services\EditorJsRenderer::getInstance()->render($decoded);
            } catch (\Throwable $e) {
                $html = '';
            }
        }

        if ($html !== '') {
            $content = $html;
        } else {
            $parts = [];
            foreach ($decoded['blocks'] as $block) {
                if (!is_array($block)) {
                    continue;
                }

                $data = $block['data'] ?? null;
                if (!is_array($data)) {
                    continue;
                }

                $candidateKeys = ['text', 'caption', 'message', 'title'];
                foreach ($candidateKeys as $key) {
                    if (!empty($data[$key]) && is_string($data[$key])) {
                        $parts[] = $data[$key];
                    }
                }

                if (!empty($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $parts[] = $item;
                        }
                    }
                }
            }

            $content = implode(' ', $parts);
        }
    }

    $text = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    return preg_replace('/\s+/u', ' ', $text) ?? '';
}

/**
 * Post-Tags als Array liefern
 */
function meridian_post_tags(string $tags): array
{
    if (empty($tags)) {
        return [];
    }
    return array_filter(array_map('trim', explode(',', $tags)));
}

/**
 * Kategorie-Farb-Gradient (für Thumbnail-Hintergründe)
 */
function meridian_cat_gradient(string $category = ''): string
{
    $map = [
        'microsoft'    => 'linear-gradient(135deg,#1e2a3e,#1a1a18)',
        'exchange'     => 'linear-gradient(135deg,#1e2a2a,#1a1a18)',
        'powershell'   => 'linear-gradient(135deg,#1a2a1a,#1a1a18)',
        'security'     => 'linear-gradient(135deg,#2a1a1a,#1a1a18)',
        'azure'        => 'linear-gradient(135deg,#1a1a2a,#1a1a18)',
        'linux'        => 'linear-gradient(135deg,#2a2a1a,#1a1a18)',
        'guides'       => 'linear-gradient(135deg,#2a1a2a,#1a1a18)',
        'tutorials'    => 'linear-gradient(135deg,#1a2a2e,#1a1a18)',
    ];
    $key = strtolower($category);
    foreach ($map as $k => $v) {
        if (str_contains($key, $k)) {
            return $v;
        }
    }
    return 'linear-gradient(135deg,#2a2a26,#1a1a18)';
}

/**
 * Gibt das aktive Menü für eine Position aus (HTML-Liste)
 * Unterstützt Dropdowns für Meridan-Layout
 */
function meridian_nav_menu(string $location, string $currentPath = ''): void
{
    $items = [];
    try {
        $items = \CMS\ThemeManager::instance()->getMenu($location);
    } catch (\Throwable $e) {
        // Kein Menü vorhanden
    }

    if (empty($items)) {
        return;
    }

    $siteUrl = defined('SITE_URL') ? SITE_URL : '';
    
    // Helper für recursive items
    $renderItems = function(array $list) use (&$renderItems, $siteUrl, $currentPath) {
        foreach ($list as $item) {
            $label    = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $url      = $item['url'] ?? '#';
            $fullUrl  = str_starts_with($url, 'http') ? $url : rtrim($siteUrl, '/') . '/' . ltrim($url, '/');
            $target   = ($item['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
            
            // Active check
            $path     = parse_url($fullUrl, PHP_URL_PATH) ?: '/';
            $isActive = ($currentPath !== '' && rtrim($currentPath, '/') === rtrim($path, '/'));
            $activeClass = $isActive ? ' active' : '';
            
            // Children
            $children = $item['children'] ?? [];
            $hasChild = !empty($children);
            
            if ($hasChild) {
                echo '<div class="nav-group' . $activeClass . '">' . "\n";
                echo '  <a href="' . htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8') . '"' . $target . '>' . $label . ' <svg viewBox="0 0 10 10"><polyline points="2,3 5,7 8,3"/></svg></a>' . "\n";
                echo '  <div class="nav-dropdown">' . "\n";
                $renderItems($children);
                echo '  </div>' . "\n";
                echo '</div>' . "\n";
            } else {
                echo '<a href="' . htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8') . '" class="nav-link' . $activeClass . '"' . $target . '>' . $label . '</a>' . "\n";
            }
        }
    };

    $renderItems($items);
}

/**
 * Helper: Eingeloggter Benutzer?
 */
function meridian_is_logged_in(): bool
{
    try {
        return \CMS\Auth::instance()->isLoggedIn();
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Helper: Flash-Message
 */
function meridian_get_flash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        return null;
    }
    // Check various keys
    foreach (['success', 'error', 'info', 'warning'] as $type) {
        if (isset($_SESSION[$type])) {
            $msg = $_SESSION[$type];
            unset($_SESSION[$type]);
            return ['type' => $type, 'message' => $msg];
        }
    }
    return null;
}


/**
 * ThemeCustomizer-Wert sicher auslesen
 */
function meridian_setting(string $section, string $key, mixed $default = null): mixed
{
    try {
        return \CMS\Services\ThemeCustomizer::instance()->get($section, $key, $default);
    } catch (\Throwable $e) {
        return $default;
    }
}

/**
 * Kategorie-Leiste: Holt Kategorien aus der DB
 *
 * @param int $limit 0 = alle
 */
function meridian_get_categories(int $limit = 0): array
{
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $sql    = "SELECT id, name, slug,
                          (SELECT COUNT(*) FROM {$prefix}posts p WHERE p.category_id = c.id AND p.status = 'published') AS post_count
                   FROM {$prefix}post_categories c ORDER BY c.sort_order ASC, c.name ASC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        $cats = $db->get_results($sql);
        return $cats ? array_map(fn($c) => (array)$c, $cats) : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Tag-Cloud: Holt alle verwendeten Tags aus der DB
 *
 * @param int $limit 0 = alle
 */
function meridian_get_tags(int $limit = 30): array
{
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $stmt   = $db->execute("SELECT tags FROM {$prefix}posts WHERE status = 'published' AND tags IS NOT NULL AND tags != ''");
        $rows   = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $counts = [];
        foreach ($rows as $row) {
            foreach (array_filter(array_map('trim', explode(',', $row))) as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }
        arsort($counts);
        if ($limit > 0) {
            $counts = array_slice($counts, 0, $limit, true);
        }
        $out = [];
        foreach ($counts as $name => $count) {
            $out[] = [
                'name'  => $name,
                'slug'  => urlencode(strtolower($name)),
                'count' => $count,
            ];
        }
        return $out;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Neueste Posts aus der DB
 */
function meridian_get_recent_posts(int $limit = 5, ?int $excludeId = null): array
{
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $sql    = "SELECT p.id, p.title, p.slug, p.featured_image, p.published_at, p.created_at, c.name AS category_name
                   FROM {$prefix}posts p
                   LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                   WHERE p.status = 'published'";
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND p.id != ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY p.published_at DESC LIMIT ?';
        $params[] = $limit;
        $rows = $db->get_results($sql, $params);
        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Verwandte Posts (gleiche Kategorie)
 */
function meridian_get_related_posts(int $categoryId, int $excludeId, int $limit = 3): array
{
    if ($categoryId <= 0) {
        return meridian_get_recent_posts($limit, $excludeId);
    }
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $rows   = $db->get_results(
            "SELECT p.id, p.title, p.slug, p.featured_image, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND p.category_id = ? AND p.id != ?
             ORDER BY p.published_at DESC LIMIT ?",
            [$categoryId, $excludeId, $limit]
        );
        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : meridian_get_recent_posts($limit, $excludeId);
    } catch (\Throwable $e) {
        return meridian_get_recent_posts($limit, $excludeId);
    }
}

/**
 * Universelle Funktion zum Abrufen von Beiträgen (für index.php)
 */
function meridian_get_posts(array $args = []): array
{
    $defaults = [
        'limit'   => 5,
        'offset'  => 0,
        'sticky'  => false,
        'exclude' => [], // array of IDs
        'category'=> null,
        'orderby' => 'published_at',
        'order'   => 'DESC'
    ];
    $args = array_merge($defaults, $args);

    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
    
        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.username AS author_name 
                FROM {$prefix}posts p 
                LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id 
                LEFT JOIN {$prefix}users u ON u.id = p.author_id 
                WHERE p.status = 'published'";
        
        $params = [];

        // Exclude
        if (!empty($args['exclude'])) {
            $ids = array_map('intval', (array)$args['exclude']);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $ids);
            }
        }

        // Sticky: Wenn is_sticky-Spalte existiert, bevorzuge angeheftete Beiträge
        if ($args['sticky'] === true) {
            try {
                $db->execute("SELECT is_sticky FROM {$prefix}posts LIMIT 1");
                $sql .= " AND p.is_sticky = 1";
            } catch (\Throwable $e) {
                // is_sticky-Spalte existiert nicht – ignorieren
            }
        }

        // Order
        $validOrders = ['published_at', 'created_at', 'title', 'views'];
        $orderby = in_array($args['orderby'], $validOrders) ? $args['orderby'] : 'published_at';
        $order   = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY p.{$orderby} {$order}";

        // Limit / Offset
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$args['limit'];
        $params[] = (int)$args['offset'];

        $rows = $db->get_results($sql, $params);
        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : [];

    } catch (\Throwable $e) {
        // Fallback or log error
        return [];
    }
}

// ─── Template-Alias-Funktionen ───────────────────────────────────────────────
// Werden in header.php, login.php, register.php etc. genutzt.

/**
 * Alias: Eingeloggter Benutzer?
 */
function theme_is_logged_in(): bool
{
    return meridian_is_logged_in();
}

/**
 * Alias: Flash-Message aus der Session
 */
function theme_get_flash(): ?array
{
    return meridian_get_flash();
}

/**
 * Alias: Aktives Menü für eine Position
 */
function theme_get_menu(string $location): array
{
    try {
        return \CMS\ThemeManager::instance()->getMenu($location) ?? [];
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Benutzer einloggen (delegiert an Auth-Service)
 * Gibt true zurück oder eine Fehlermeldung als String.
 */
function theme_login_user(string $email, string $password): bool|string
{
    try {
        $auth   = \CMS\Auth::instance();
        $result = $auth->login($email, $password);
        if ($result === true) {
            return true;
        }
        return is_string($result) ? $result : 'Ungültige Zugangsdaten.';
    } catch (\Throwable $e) {
        return 'Anmeldung fehlgeschlagen: ' . $e->getMessage();
    }
}

/**
 * Neues Konto registrieren (delegiert an Auth/User-Service)
 * Gibt true zurück oder eine Fehlermeldung als String.
 */
function theme_register_user(string $email, string $username, string $password): bool|string
{
    try {
        // Prüfen ob E-Mail oder Benutzername verfügbar
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $exists = $db->execute(
            "SELECT id FROM {$prefix}users WHERE email = ? OR username = ? LIMIT 1",
            [$email, $username]
        )->fetch();
        if ($exists) {
            return 'E-Mail-Adresse oder Benutzername bereits vergeben.';
        }

        // Auth-Service verwenden wenn vorhanden, sonst direkt einfügen
        if (method_exists(\CMS\Auth::instance(), 'register')) {
            $result = \CMS\Auth::instance()->register($email, $username, $password);
            return ($result === true) ? true : (is_string($result) ? $result : 'Registrierung fehlgeschlagen.');
        }

        // Fallback: Direkt in DB schreiben
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->execute(
            "INSERT INTO {$prefix}users (email, username, password, role, created_at) VALUES (?, ?, ?, 'member', NOW())",
            [$email, $username, $hash]
        );
        return true;
    } catch (\Throwable $e) {
        return 'Registrierung fehlgeschlagen: ' . $e->getMessage();
    }
}

/**
 * Gibt den Beitragszähler für eine gegebene Kategorie zurück
 */
function meridian_get_category_post_count(int $categoryId): int
{
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $row    = $db->execute(
            "SELECT COUNT(*) AS cnt FROM {$prefix}posts WHERE category_id = ? AND status = 'published'",
            [$categoryId]
        )->fetch();
        return $row ? (int)$row->cnt : 0;
    } catch (\Throwable $e) {
        return 0;
    }
}

/**
 * Copyright-Text mit Platzhaltern auflösen
 * Unterstützte Platzhalter: {year}, {site_title}
 */
function meridian_copyright(string $template = ''): string
{
    if ($template === '') {
        $template = meridian_setting('footer', 'copyright_text', '© {year} {site_title}. Alle Rechte vorbehalten.');
    }
    $siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
    $text = str_replace(['{year}', '{site_title}'], [date('Y'), $siteName], $template);
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
