<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meridian CMS Default Theme Bootstrap
 */
final class MeridianCMSDefaultTheme
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
        \CMS\Hooks::addAction('head', [$this, 'outputPreconnect'], 1);
        \CMS\Hooks::addAction('head', [$this, 'outputGoogleFonts'], 5);
        \CMS\Hooks::addAction('head', [$this, 'enqueueStyles'], 10);
        \CMS\Hooks::addAction('head', [$this, 'outputMetaTags'], 15);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomStyles'], 20);
        \CMS\Hooks::addAction('head', [$this, 'outputCustomHeaderCode'], 99);

        \CMS\Hooks::addAction('before_footer', [$this, 'enqueueScripts'], 10);
        \CMS\Hooks::addAction('before_footer', [$this, 'outputCookieBanner'], 20);

        \CMS\Hooks::addFilter('register_menu_locations', [$this, 'registerMenuLocations']);
        \CMS\Hooks::addAction('cms_init', [$this, 'seedDefaultMenus']);
    }

    public function outputPreconnect(): void
    {
        if ($this->isLocalFontsEnabled()) {
            return;
        }

        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }

    private function isLocalFontsEnabled(): bool
    {
        try {
            $db = \CMS\Database::instance();
            $row = $db->execute(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts'"
            )->fetch();

            return $row && $row->option_value === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function outputGoogleFonts(): void
    {
        if ($this->isLocalFontsEnabled()) {
            return;
        }

        try {
            $loadFonts = \CMS\Services\ThemeCustomizer::instance()->get('typography', 'google_fonts', true);
        } catch (\Throwable $e) {
            $loadFonts = true;
        }

        if (!$loadFonts) {
            return;
        }

        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();
            $fontBody = (string)$customizer->get('typography', 'font_family_body', 'dm-sans');
            $fontHeading = (string)$customizer->get('typography', 'font_family_heading', 'libre-baskerville');
        } catch (\Throwable $e) {
            $fontBody = 'dm-sans';
            $fontHeading = 'libre-baskerville';
        }

        $googleMap = [
            'dm-sans' => 'DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400',
            'libre-baskerville' => 'Libre+Baskerville:ital,wght@0,400;0,700;1,400',
            'inter' => 'Inter:wght@400;500;600;700',
            'playfair-display' => 'Playfair+Display:ital,wght@0,400;0,700;1,400',
            'merriweather' => 'Merriweather:ital,wght@0,400;0,700;1,400',
        ];

        $families = [];

        if (isset($googleMap[$fontBody])) {
            $families[] = $googleMap[$fontBody];
        }

        if ($fontHeading !== $fontBody && isset($googleMap[$fontHeading])) {
            $families[] = $googleMap[$fontHeading];
        }

        if (!in_array('DM+Mono:wght@400;500', $families, true)) {
            $families[] = 'DM+Mono:wght@400;500';
        }

        $url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $families) . '&display=swap';
        echo '<link href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" rel="stylesheet">' . "\n";
    }

    public function enqueueStyles(): void
    {
        $cssPath = MERIDIAN_THEME_DIR . 'style.css';
        $version = file_exists($cssPath) ? (string)filemtime($cssPath) : MERIDIAN_THEME_VERSION;

        echo '<link rel="stylesheet" href="' . htmlspecialchars(MERIDIAN_THEME_URL, ENT_QUOTES, 'UTF-8') . '/style.css?v=' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function outputMetaTags(): void
    {
        try {
            $seo = \CMS\Services\SEOService::getInstance();
            echo $seo->renderCurrentHeadTags();
        } catch (\Throwable $e) {
        }

        echo '<meta name="theme-color" content="#f7f6f2">' . "\n";
    }

    public function outputCustomStyles(): void
    {
        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();

            $accent = (string)$customizer->get('colors', 'accent_color', '#c0862a');
            $accentDark = (string)$customizer->get('colors', 'accent_dark_color', '#a06b18');
            $ink = (string)$customizer->get('colors', 'ink_color', '#1a1a18');
            $inkSoft = (string)$customizer->get('colors', 'ink_soft_color', '#3d3d3a');
            $inkMuted = (string)$customizer->get('colors', 'ink_muted_color', '#7a7a74');
            $ground = (string)$customizer->get('colors', 'ground_color', '#f7f6f2');
            $surface = (string)$customizer->get('colors', 'surface_color', '#ffffff');
            $surfaceTint = (string)$customizer->get('colors', 'surface_tint_color', '#f2f1ec');
            $rule = (string)$customizer->get('colors', 'rule_color', '#e2e0d8');
            $headerBg = (string)$customizer->get('colors', 'header_bg_color', '#ffffff');
            $stripe = (string)$customizer->get('colors', 'header_stripe_color', '#1a1a18');
            $linkColor = (string)$customizer->get('colors', 'link_color', $accent);
            $linkHover = (string)$customizer->get('colors', 'link_hover_color', $accentDark);
            $catBarBg = (string)$customizer->get('colors', 'category_bar_bg', '#f2f1ec');
            $catBarText = (string)$customizer->get('colors', 'category_bar_text', '#3d3d3a');

            $footerBg = (string)$customizer->get('footer', 'footer_bg_color', '#1a1a18');
            $footerText = (string)$customizer->get('footer', 'footer_text_color', '#9a9a94');
            $footerAccent = (string)$customizer->get('footer', 'footer_accent_color', '#c0862a');

            $maxWidth = max(600, (int)$customizer->get('layout', 'max_width', 1140));
            $colWidth = max(300, (int)$customizer->get('layout', 'post_col_width', 680));
            $borderRadius = max(0, (int)$customizer->get('layout', 'border_radius', 3));
            $cardGap = max(4, (int)$customizer->get('layout', 'card_gap', 24));
            $stickyHeader = (string)$customizer->get('layout', 'sticky_header', '1') !== '0';

            $baseFontSz = max(10, (int)$customizer->get('typography', 'font_size_base', 15));
            $lineHeight = (float)str_replace(',', '.', (string)$customizer->get('typography', 'line_height', '1.6'));
            if ($lineHeight < 1.0 || $lineHeight > 3.0) {
                $lineHeight = 1.6;
            }
            $headingWeight = (int)$customizer->get('typography', 'heading_weight', 700);
            if (!in_array($headingWeight, [600, 700, 800, 900], true)) {
                $headingWeight = 700;
            }
            $fontBody = (string)$customizer->get('typography', 'font_family_body', 'dm-sans');
            $fontHeading = (string)$customizer->get('typography', 'font_family_heading', 'libre-baskerville');
            $lsHeadings = (string)$customizer->get('typography', 'letter_spacing_headings', '0');
            $h1Size = max(18, (int)$customizer->get('typography', 'h1_size', 38));
            $h2Size = max(14, (int)$customizer->get('typography', 'h2_size', 28));
            $h3Size = max(12, (int)$customizer->get('typography', 'h3_size', 22));

            $navFontSize = max(11, (int)$customizer->get('navigation', 'nav_font_size', 14));
            $navUppercase = (string)$customizer->get('navigation', 'nav_uppercase', '0') !== '0';
            $navLetterSp = (string)$customizer->get('navigation', 'nav_letter_spacing', '0');

            $stripeEnabled = (string)$customizer->get('header', 'header_stripe_enabled', '1') !== '0';
            $customCss = (string)$customizer->get('advanced', 'custom_css', '');

            $fontBodyCss = match ($fontBody) {
                'system-ui' => 'system-ui, -apple-system, sans-serif',
                'georgia' => "'Georgia', serif",
                'times-new-roman' => "'Times New Roman', Times, serif",
                'inter' => "'Inter', system-ui, sans-serif",
                default => "'DM Sans', system-ui, sans-serif",
            };
            $fontHeadingCss = match ($fontHeading) {
                'georgia' => 'Georgia, serif',
                'merriweather' => "'Merriweather', Georgia, serif",
                'playfair-display' => "'Playfair Display', Georgia, serif",
                'system-ui' => 'system-ui, -apple-system, sans-serif',
                default => "'Libre Baskerville', Georgia, serif",
            };

            $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            echo '<style id="meridian-custom-vars">' . "\n";

            $accent = $accent ?: '#c0862a';
            $accentDark = $accentDark ?: '#a06b18';
            $ink = $ink ?: '#1a1a18';
            $inkSoft = $inkSoft ?: '#3d3d3a';
            $inkMuted = $inkMuted ?: '#7a7a74';
            $ground = $ground ?: '#f7f6f2';
            $surface = $surface ?: '#ffffff';
            $surfaceTint = $surfaceTint ?: '#f2f1ec';
            $rule = $rule ?: '#e2e0d8';
            $headerBg = $headerBg ?: '#ffffff';
            $stripe = $stripe ?: '#1a1a18';
            $linkColor = $linkColor ?: $accent;
            $linkHover = $linkHover ?: $accentDark;
            $catBarBg = $catBarBg ?: '#f2f1ec';
            $catBarText = $catBarText ?: '#3d3d3a';
            $footerBg = $footerBg ?: '#1a1a18';
            $footerText = $footerText ?: '#9a9a94';
            $footerAccent = $footerAccent ?: '#c0862a';
            $inkGhost = $this->lightenHex($inkMuted, 0.46);

            echo ':root {' . "\n";
            echo '  --accent:           ' . $esc($accent) . ";\n";
            echo '  --accent-dark:      ' . $esc($accentDark) . ";\n";
            echo '  --accent-light:     ' . $this->hexToRgba($accent, 0.08) . ";\n";
            echo '  --accent-mid:       ' . $this->hexToRgba($accent, 0.15) . ";\n";
            echo '  --ink:              ' . $esc($ink) . ";\n";
            echo '  --ink-soft:         ' . $esc($inkSoft) . ";\n";
            echo '  --ink-muted:        ' . $esc($inkMuted) . ";\n";
            echo '  --ink-ghost:        ' . $esc($inkGhost) . ";\n";
            echo '  --ground:           ' . $esc($ground) . ";\n";
            echo '  --surface:          ' . $esc($surface) . ";\n";
            echo '  --surface-tint:     ' . $esc($surfaceTint) . ";\n";
            echo '  --rule:             ' . $esc($rule) . ";\n";
            echo '  --rule-heavy:       ' . $esc($rule) . ";\n";
            echo '  --tag-bg:           ' . $esc($surfaceTint) . ";\n";
            echo '  --tag-color:        ' . $esc($inkMuted) . ";\n";
            echo '  --max:              ' . $maxWidth . "px;\n";
            echo '  --col:              ' . $colWidth . "px;\n";
            echo '  --r:                ' . $borderRadius . "px;\n";
            echo '  --font-sans:        ' . $fontBodyCss . ";\n";
            echo '  --font-serif:       ' . $fontHeadingCss . ";\n";
            echo '  --card-gap:         ' . $cardGap . "px;\n";
            echo '}' . "\n";

            echo 'body { font-size:' . $baseFontSz . 'px; line-height:' . $lineHeight . '; background:' . $esc($ground) . '; color:' . $esc($ink) . "; }\n";
            echo 'h1,h2,h3,h4,h5,h6 { font-weight:' . $headingWeight . '; letter-spacing:' . $esc($lsHeadings) . "; }\n";
            echo 'h1 { font-size:' . $h1Size . "px; }\n";
            echo 'h2 { font-size:' . $h2Size . "px; }\n";
            echo 'h3 { font-size:' . $h3Size . "px; }\n";
            echo '.post-body a, .article-content a { color:' . $esc($linkColor) . "; }\n";
            echo '.post-body a:hover, .article-content a:hover { color:' . $esc($linkHover) . "; }\n";

            echo '.site-header { background:' . $esc($headerBg) . "; }\n";
            echo '.site-header::before { height:' . ($stripeEnabled ? '3px' : '0') . '; background:' . $esc($stripe) . "; }\n";
            if (!$stickyHeader) {
                echo ".site-header { position:static !important; top:auto !important; }\n";
            }

            $navTransform = $navUppercase ? 'uppercase' : 'none';
            echo '.main-nav a, .nav-link { font-size:' . $navFontSize . 'px; text-transform:' . $navTransform . '; letter-spacing:' . $esc($navLetterSp) . "; }\n";
            echo '.category-bar { background:' . $esc($catBarBg) . "; }\n";
            echo '.category-bar a, .category-bar .cat-label { color:' . $esc($catBarText) . "; }\n";
            echo '.category-bar a:hover, .category-bar a.active { color:' . $esc($accent) . "; }\n";

            echo '.card-grid, .articles-grid, .dashboard-grid { gap:' . $cardGap . "px; }\n";
            echo '.article-row + .article-row { margin-top:' . $cardGap . "px; }\n";

            echo 'footer, .site-footer { background:' . $esc($footerBg) . '; color:' . $esc($footerText) . "; }\n";
            echo 'footer a, .site-footer a { color:' . $esc($footerText) . "; }\n";
            echo 'footer a:hover, .site-footer a:hover, .ft-col a:hover { color:' . $esc($footerAccent) . " !important; }\n";
            echo '.ft-col h4, .footer-col-title { color:' . $esc($footerAccent) . "; }\n";
            echo '.ft-copyright { color:' . $esc($footerText) . "; opacity:.7; }\n";

            if ($customCss && trim($customCss) !== '') {
                echo "\n/* ── Custom CSS (Theme Customizer) ── */\n" . $customCss . "\n";
            }

            echo '</style>' . "\n";
        } catch (\Throwable $e) {
            error_log('meridian outputCustomStyles error: ' . $e->getMessage());
        }
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
    }

    private function lightenHex(string $hex, float $factor): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = (int)round(hexdec(substr($hex, 0, 2)) + (255 - hexdec(substr($hex, 0, 2))) * $factor);
        $g = (int)round(hexdec(substr($hex, 2, 2)) + (255 - hexdec(substr($hex, 2, 2))) * $factor);
        $b = (int)round(hexdec(substr($hex, 4, 2)) + (255 - hexdec(substr($hex, 4, 2))) * $factor);

        return sprintf('#%02x%02x%02x', min(255, $r), min(255, $g), min(255, $b));
    }

    public function outputCustomHeaderCode(): void
    {
        try {
            $code = \CMS\Services\SEOService::getInstance()->getCustomHeaderCode();
            if (!empty($code)) {
                echo "\n<!-- Custom Header Code -->\n" . $code . "\n<!-- /Custom Header Code -->\n";
            }
        } catch (\Throwable $e) {
        }

        try {
            $customHeadCode = \CMS\Services\ThemeCustomizer::instance()->get('advanced', 'custom_head_code', '');
            if (!empty(trim($customHeadCode))) {
                echo "\n<!-- Theme Custom Head Code -->\n" . $customHeadCode . "\n<!-- /Theme Custom Head Code -->\n";
            }
        } catch (\Throwable $e) {
        }
    }

    public function enqueueScripts(): void
    {
        $jsPath = MERIDIAN_THEME_DIR . 'js/theme.js';
        $version = file_exists($jsPath) ? (string)filemtime($jsPath) : MERIDIAN_THEME_VERSION;

        echo '<script src="' . htmlspecialchars(MERIDIAN_THEME_URL, ENT_QUOTES, 'UTF-8') . '/js/theme.js?v=' . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";

        try {
            $footerCode = \CMS\Services\ThemeCustomizer::instance()->get('advanced', 'custom_footer_code', '');
            if (!empty(trim($footerCode))) {
                echo "\n<!-- Theme Custom Footer Code -->\n" . $footerCode . "\n<!-- /Theme Custom Footer Code -->\n";
            }
        } catch (\Throwable $e) {
        }
    }

    public function outputCookieBanner(): void
    {
        if (class_exists('\\CMS\\Services\\CookieConsentService')) {
            try {
                if (\CMS\Services\CookieConsentService::getInstance()->isManagedExternally()) {
                    return;
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $db = \CMS\Database::instance();
            $enabled = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'cookie_consent_enabled'")->fetch();
            if (!$enabled || $enabled->option_value !== '1') {
                return;
            }

            $keys = ['cookie_banner_text', 'cookie_accept_text', 'cookie_essential_text', 'cookie_policy_url'];
            $settings = [];
            foreach ($keys as $key) {
                $row = $db->execute("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key])->fetch();
                $settings[$key] = $row ? (string)$row->option_value : '';
            }

            $text = htmlspecialchars($settings['cookie_banner_text'] ?: 'Wir verwenden Cookies.', ENT_QUOTES, 'UTF-8');
            $accept = htmlspecialchars($settings['cookie_accept_text'] ?: 'Akzeptieren', ENT_QUOTES, 'UTF-8');
            $essential = htmlspecialchars($settings['cookie_essential_text'] ?: 'Nur Essenzielle', ENT_QUOTES, 'UTF-8');
            $policy = htmlspecialchars($settings['cookie_policy_url'] ?: '#', ENT_QUOTES, 'UTF-8');
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

    public function registerMenuLocations(array $locations): array
    {
        $locations[] = ['slug' => 'primary', 'label' => 'Hauptmenü (Header)'];
        $locations[] = ['slug' => 'secondary', 'label' => 'Sekundäres Menü (unter Header)'];
        $locations[] = ['slug' => 'mobile', 'label' => 'Mobiles Menü'];
        $locations[] = ['slug' => 'footer', 'label' => 'Footer-Navigation'];

        return $locations;
    }

    public function seedDefaultMenus(): void
    {
        $themeManager = \CMS\ThemeManager::instance();

        $defaults = [
            'primary' => [
                ['label' => 'Startseite', 'url' => '/', 'target' => '_self'],
                ['label' => 'Blog', 'url' => '/blog', 'target' => '_self'],
            ],
            'mobile' => [
                ['label' => 'Startseite', 'url' => '/', 'target' => '_self'],
                ['label' => 'Blog', 'url' => '/blog', 'target' => '_self'],
                ['label' => 'Anmelden', 'url' => function_exists('meridian_auth_path') ? meridian_auth_path('login') : '/cms-login', 'target' => '_self'],
            ],
            'footer' => [
                ['label' => 'Impressum', 'url' => '/impressum', 'target' => '_self'],
                ['label' => 'Datenschutz', 'url' => '/datenschutz', 'target' => '_self'],
                ['label' => 'Kontakt', 'url' => '/contact', 'target' => '_self'],
            ],
        ];

        foreach ($defaults as $location => $items) {
            if (empty($themeManager->getMenu($location))) {
                $themeManager->saveMenu($location, $items);
            }
        }
    }

    public function registerRequiredLocalFonts(array $fontSlugs): array
    {
        $required = [];

        try {
            $customizer = \CMS\Services\ThemeCustomizer::instance();
            $required[] = (string)$customizer->get('typography', 'font_family_body', 'dm-sans');
            $required[] = (string)$customizer->get('typography', 'font_family_heading', 'libre-baskerville');
        } catch (\Throwable $e) {
            $required[] = 'dm-sans';
            $required[] = 'libre-baskerville';
        }

        $required[] = 'dm-mono';

        foreach ($required as $slug) {
            $slug = strtolower(trim((string)$slug));
            $slug = preg_replace('/[^a-z0-9_-]+/i', '-', $slug) ?? '';
            $slug = trim($slug, '-');

            if ($slug === '') {
                continue;
            }

            if (!in_array($slug, $fontSlugs, true)) {
                $fontSlugs[] = $slug;
            }
        }

        return $fontSlugs;
    }
}
