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
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }

    // ─── Google Fonts ───────────────────────────────────────────────────────

    public function outputGoogleFonts(): void
    {
        try {
            $loadFonts = \CMS\Services\ThemeCustomizer::instance()->get('typography', 'google_fonts', true);
        } catch (\Throwable $e) {
            $loadFonts = true;
        }

        if ($loadFonts) {
            echo '<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">' . "\n";
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
            $seo     = \CMS\Services\SEOService::getInstance();
            $metaDesc = $seo->getMetaDescription();
        } catch (\Throwable $e) {
            $metaDesc = '';
        }

        if ($metaDesc && trim($metaDesc) !== '') {
            echo '<meta name="description" content="' . htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
        echo '<meta name="theme-color" content="#f7f6f2">' . "\n";
    }

    // ─── Custom CSS aus Customizer ───────────────────────────────────────────

    public function outputCustomStyles(): void
    {
        try {
            $c = \CMS\Services\ThemeCustomizer::instance();

            $accent       = $c->get('colors', 'accent_color',        '#c0862a');
            $accentDark   = $c->get('colors', 'accent_dark_color',   '#a06b18');
            $ink          = $c->get('colors', 'ink_color',           '#1a1a18');
            $inkSoft      = $c->get('colors', 'ink_soft_color',      '#3d3d3a');
            $inkMuted     = $c->get('colors', 'ink_muted_color',     '#7a7a74');
            $ground       = $c->get('colors', 'ground_color',        '#f7f6f2');
            $surface      = $c->get('colors', 'surface_color',       '#ffffff');
            $surfaceTint  = $c->get('colors', 'surface_tint_color',  '#f2f1ec');
            $rule         = $c->get('colors', 'rule_color',          '#e2e0d8');
            $headerBg     = $c->get('colors', 'header_bg_color',     '#ffffff');
            $stripe       = $c->get('colors', 'header_stripe_color', '#1a1a18');
            $footerBg     = $c->get('footer', 'footer_bg_color',     '#1a1a18');
            $footerText   = $c->get('footer', 'footer_text_color',   '#9a9a94');
            $footerAccent = $c->get('footer', 'footer_accent_color', '#c0862a');

            $maxWidth     = max(600, (int)$c->get('layout', 'max_width',       1140));
            $colWidth     = max(300, (int)$c->get('layout', 'post_col_width',   680));
            $borderRadius = max(0, (int)$c->get('layout', 'border_radius',       3));
            $stickyHeader = (bool)((string)$c->get('layout', 'sticky_header', true) !== '0');

            $baseFontSz   = max(10, (int)$c->get('typography', 'font_size_base',  15));
            $lineHeight   = (float)str_replace(',', '.', (string)$c->get('typography', 'line_height', '1.6'));
            if ($lineHeight < 1 || $lineHeight > 3) { $lineHeight = 1.6; }
            $headingWeight = (int)$c->get('typography', 'heading_weight', 700);
            if (!in_array($headingWeight, [600, 700, 800, 900])) { $headingWeight = 700; }

            $stripeEnabled = (bool)((string)$c->get('header', 'header_stripe_enabled', true) !== '0');

            $customCss    = $c->get('advanced', 'custom_css',         '');

            // Helper
            $esc = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

            echo '<style id="meridian-custom-vars">' . "\n";
            echo ':root {' . "\n";
            echo '  --accent:           ' . $esc($accent)      . ";\n";
            echo '  --accent-dark:      ' . $esc($accentDark)  . ";\n";
            echo '  --accent-light:     ' . $this->hexToRgba($accent, 0.08) . ";\n";
            echo '  --accent-mid:       ' . $this->hexToRgba($accent, 0.15) . ";\n";
            echo '  --ink:              ' . $esc($ink)         . ";\n";
            echo '  --ink-soft:         ' . $esc($inkSoft)     . ";\n";
            echo '  --ink-muted:        ' . $esc($inkMuted)    . ";\n";
            echo '  --ground:           ' . $esc($ground)      . ";\n";
            echo '  --surface:          ' . $esc($surface)     . ";\n";
            echo '  --surface-tint:     ' . $esc($surfaceTint) . ";\n";
            echo '  --rule:             ' . $esc($rule)        . ";\n";
            echo '  --rule-heavy:       ' . $esc($rule)        . ";\n";
            echo '  --max:              ' . $maxWidth          . "px;\n";
            echo '  --col:              ' . $colWidth          . "px;\n";
            echo '  --r:                ' . $borderRadius      . "px;\n";
            echo '}' . "\n";
            echo 'body { font-size: ' . $baseFontSz . 'px; line-height: ' . $lineHeight . "; }\n";
            echo '.site-header { background: ' . $esc($headerBg) . "; }\n";
            echo '.site-header::before { height: ' . ($stripeEnabled ? '3px' : '0') . '; background: ' . $esc($stripe) . "; }\n";
            if (!$stickyHeader) {
                echo ".site-header { position: static; }\n";
            }
            echo 'h1,h2,h3,h4,h5,h6 { font-weight: ' . $headingWeight . "; }\n";
            echo 'footer { background: ' . $esc($footerBg) . '; color: ' . $esc($footerText) . "; }\n";
            echo 'footer a { color: ' . $esc($footerText) . "; }\n";
            echo 'footer a:hover, .ft-col a:hover { color: ' . $esc($footerAccent) . ' !important; }' . "\n";
            if ($customCss && trim($customCss) !== '') {
                echo "\n/* Custom CSS */\n" . $customCss . "\n";
            }
            echo '</style>' . "\n";
        } catch (\Throwable $e) {
            // Customizer nicht verfügbar – Standard-CSS greift
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
    $text = strip_tags($content);
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
 * Kategorie-Leiste: Holt alle Kategorien aus der DB
 */
function meridian_get_categories(): array
{
    try {
        $db     = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $cats   = $db->get_results(
            "SELECT id, name, slug FROM {$prefix}post_categories ORDER BY sort_order ASC, name ASC"
        );
        return $cats ? (array)$cats : [];
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
        $sql    = "SELECT p.id, p.title, p.slug, p.published_at, c.name AS category_name
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
        return $rows ? (array)$rows : [];
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
        return $rows ? (array)$rows : meridian_get_recent_posts($limit, $excludeId);
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

        // Sticky (if supported by DB schema, else ignore or mock)
        if ($args['sticky'] === true) {
            // Check if column exists or just return latest as sticky
            // For now, let's assume 'is_sticky' column exists or ignore
             // $sql .= " AND p.is_sticky = 1"; 
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
        return $rows ? (array)$rows : [];

    } catch (\Throwable $e) {
        // Fallback or log error
        return [];
    }
}
