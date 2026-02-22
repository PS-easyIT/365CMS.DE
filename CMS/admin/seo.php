<?php
/**
 * Admin: SEO Dashboard
 *
 * URL:  /admin/seo
 * Tabs: general | social | structured | permalinks | indexing
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\Services\SEOService;

if (!defined('ABSPATH')) { exit; }

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security   = Security::instance();
$db         = Database::instance();
$user       = Auth::instance()->getCurrentUser();
$seoService = SEOService::getInstance();
$messages   = [];

// ── Aktiver Tab ────────────────────────────────────────────────────────────────
$activeTab   = $_GET['tab'] ?? 'general';
$allowedTabs = ['general', 'social', 'structured', 'permalinks', 'indexing', 'analytics'];
if (!in_array($activeTab, $allowedTabs, true)) { $activeTab = 'general'; }

// ── Helper ─────────────────────────────────────────────────────────────────────
function seo_get(Database $db, string $key, string $default = ''): string
{
    try {
        $r = $db->fetchOne(
            "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
            [$key]
        );
        return $r ? (string)$r['option_value'] : $default;
    } catch (\Exception) { return $default; }
}

function seo_save(Database $db, string $key, string $value): void
{
    try {
        $existing = $db->fetchAll(
            "SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?",
            [$key]
        );
        if ($existing) {
            $db->execute(
                "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
                [$value, $key]
            );
        } else {
            $db->execute(
                "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
                [$key, $value]
            );
        }
    } catch (\Exception $e) { /* caller handles messaging */ }
}

// ── POST-Verarbeitung ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'seo_settings')) {
        $messages[] = ['type' => 'error', 'text' => 'Ungültiger Sicherheits-Token.'];
    } else {
        $action = $_POST['action'] ?? '';
        try {
            // ── GENERAL ──────────────────────────────────────────────────────
            if ($action === 'save_seo_general') {
                $fields = [
                    'seo_site_title_format'    => $security->sanitize($_POST['site_title_format']    ?? '{title} | {sitename}', 'text'),
                    'seo_title_separator'      => $security->sanitize($_POST['title_separator']      ?? '|', 'text'),
                    'seo_homepage_title'       => $security->sanitize($_POST['homepage_title']       ?? '', 'text'),
                    'seo_homepage_description' => $security->sanitize($_POST['homepage_description'] ?? '', 'text'),
                    'seo_meta_description'     => $security->sanitize($_POST['meta_description']     ?? '', 'text'),
                    'seo_meta_keywords'        => $security->sanitize($_POST['meta_keywords']        ?? '', 'text'),
                    'seo_canonical_url'        => in_array($_POST['canonical_url'] ?? 'auto', ['auto','manual'], true)
                                                      ? $_POST['canonical_url'] : 'auto',
                    'seo_noindex_archives'     => isset($_POST['noindex_archives'])  ? '1' : '0',
                    'seo_noindex_search'       => isset($_POST['noindex_search'])    ? '1' : '0',
                    'seo_noindex_author'       => isset($_POST['noindex_author'])    ? '1' : '0',
                    'seo_noindex_tag_pages'    => isset($_POST['noindex_tag_pages']) ? '1' : '0',
                ];
                foreach ($fields as $k => $v) { seo_save($db, $k, $v); }
                $messages[] = ['type' => 'success', 'text' => 'Allgemeine SEO-Einstellungen gespeichert.'];
            }
            // ── SOCIAL ───────────────────────────────────────────────────────
            elseif ($action === 'save_seo_social') {
                $url = fn($v) => filter_var(trim($v), FILTER_SANITIZE_URL);
                $fields = [
                    'seo_og_title'        => $security->sanitize($_POST['og_title']        ?? '', 'text'),
                    'seo_og_description'  => $security->sanitize($_POST['og_description']  ?? '', 'text'),
                    'seo_og_image'        => $url($_POST['og_image']        ?? ''),
                    'seo_og_locale'       => $security->sanitize($_POST['og_locale']       ?? 'de_DE', 'text'),
                    'seo_og_type'         => in_array($_POST['og_type'] ?? 'website', ['website','article','profile'], true)
                                                ? $_POST['og_type'] : 'website',
                    'seo_twitter_card'    => in_array($_POST['twitter_card'] ?? 'summary_large_image',
                                                ['summary','summary_large_image','app','player'], true)
                                                ? $_POST['twitter_card'] : 'summary_large_image',
                    'seo_twitter_site'    => $security->sanitize($_POST['twitter_site']    ?? '', 'text'),
                    'seo_twitter_creator' => $security->sanitize($_POST['twitter_creator'] ?? '', 'text'),
                    'seo_linkedin_company'=> $url($_POST['linkedin_company'] ?? ''),
                    'seo_author_meta'     => $security->sanitize($_POST['author_meta']     ?? '', 'text'),
                    'seo_publisher_meta'  => $url($_POST['publisher_meta']  ?? ''),
                ];
                foreach ($fields as $k => $v) { seo_save($db, $k, $v); }
                $messages[] = ['type' => 'success', 'text' => 'Social-Media-Einstellungen gespeichert.'];
            }
            // ── STRUCTURED DATA ───────────────────────────────────────────────
            elseif ($action === 'save_seo_structured') {
                $url = fn($v) => filter_var(trim($v), FILTER_SANITIZE_URL);
                $fields = [
                    'seo_schema_type'            => in_array($_POST['schema_type'] ?? 'Organization',
                                                       ['Organization','LocalBusiness','Person','WebSite'], true)
                                                       ? $_POST['schema_type'] : 'Organization',
                    'seo_schema_name'            => $security->sanitize($_POST['schema_name']            ?? '', 'text'),
                    'seo_schema_url'             => $url($_POST['schema_url']             ?? ''),
                    'seo_schema_logo'            => $url($_POST['schema_logo']            ?? ''),
                    'seo_schema_email'           => $security->sanitize($_POST['schema_email']           ?? '', 'email'),
                    'seo_schema_phone'           => $security->sanitize($_POST['schema_phone']           ?? '', 'text'),
                    'seo_schema_address'         => $security->sanitize($_POST['schema_address']         ?? '', 'text'),
                    'seo_schema_social_profiles' => $security->sanitize($_POST['schema_social_profiles'] ?? '', 'text'),
                    'seo_breadcrumbs_enabled'    => isset($_POST['breadcrumbs_enabled'])   ? '1' : '0',
                    'seo_schema_jsonld_enabled'  => isset($_POST['schema_jsonld_enabled']) ? '1' : '0',
                ];
                foreach ($fields as $k => $v) { seo_save($db, $k, $v); }
                $messages[] = ['type' => 'success', 'text' => 'Strukturierte Daten gespeichert.'];
            }
            // ── PERMALINKS ────────────────────────────────────────────────────
            elseif ($action === 'save_seo_permalinks') {
                $structure = $security->sanitize($_POST['permalink_structure'] ?? '/%postname%/', 'text');
                $custom    = $security->sanitize($_POST['permalink_custom']    ?? '', 'text');
                if ($structure === 'custom' && !empty($custom)) { $structure = $custom; }
                $fields = [
                    'setting_permalink_structure'      => $structure,
                    'setting_category_base'            => $security->sanitize($_POST['category_base'] ?? 'category', 'text'),
                    'setting_tag_base'                 => $security->sanitize($_POST['tag_base']       ?? 'tag', 'text'),
                    'setting_strip_category_base'      => isset($_POST['strip_category_base']) ? '1' : '0',
                    'setting_permalink_trailing_slash' => isset($_POST['trailing_slash'])      ? '1' : '0',
                ];
                foreach ($fields as $k => $v) { seo_save($db, $k, $v); }
                $messages[] = ['type' => 'success', 'text' => 'Permalink-Struktur gespeichert.'];
            }
            // ── INDEXING ──────────────────────────────────────────────────────
            elseif ($action === 'save_seo_indexing') {
                $fields = [
                    'seo_robots_index'             => in_array($_POST['robots_index']  ?? 'index',  ['index','noindex'],   true) ? $_POST['robots_index']  : 'index',
                    'seo_robots_follow'            => in_array($_POST['robots_follow'] ?? 'follow', ['follow','nofollow'], true) ? $_POST['robots_follow'] : 'follow',
                    'seo_sitemap_enabled'          => isset($_POST['sitemap_enabled'])          ? '1' : '0',
                    'seo_sitemap_include_images'   => isset($_POST['sitemap_include_images'])   ? '1' : '0',
                    'seo_sitemap_include_news'     => isset($_POST['sitemap_include_news'])     ? '1' : '0',
                    'seo_sitemap_default_priority' => $security->sanitize($_POST['sitemap_default_priority'] ?? '0.7', 'text'),
                    'seo_sitemap_change_freq'      => in_array($_POST['sitemap_change_freq'] ?? 'weekly',
                                                        ['always','hourly','daily','weekly','monthly','yearly','never'], true)
                                                        ? $_POST['sitemap_change_freq'] : 'weekly',
                    'seo_indexnow_enabled'         => isset($_POST['indexnow_enabled']) ? '1' : '0',
                    'seo_indexnow_key'             => $security->sanitize($_POST['indexnow_key'] ?? '', 'text'),
                    'seo_google_site_verification' => $security->sanitize($_POST['google_site_verification'] ?? '', 'text'),
                    'seo_bing_site_verification'   => $security->sanitize($_POST['bing_site_verification']   ?? '', 'text'),
                    'seo_yandex_verification'      => $security->sanitize($_POST['yandex_verification']      ?? '', 'text'),
                    'seo_baidu_verification'       => $security->sanitize($_POST['baidu_verification']       ?? '', 'text'),
                    'seo_robots_txt_content'       => $_POST['robots_txt_content'] ?? '',
                ];
                foreach ($fields as $k => $v) { seo_save($db, $k, $v); }
                if (method_exists($seoService, 'saveRobotsTxt')) { $seoService->saveRobotsTxt(); }
                if (method_exists($seoService, 'saveSitemap'))   { $seoService->saveSitemap(); }
                $messages[] = ['type' => 'success', 'text' => 'Indexierungs-Einstellungen gespeichert.'];
            }
            // ── ANALYTICS CODE ────────────────────────────────────────────────
            elseif ($action === 'save_seo_analytics') {
                $analyticsFields = [
                    'seo_analytics_matomo_enabled'  => isset($_POST['matomo_enabled'])   ? '1' : '0',
                    'seo_analytics_matomo_url'      => filter_var(trim($_POST['matomo_url'] ?? ''), FILTER_SANITIZE_URL),
                    'seo_analytics_matomo_site_id'  => $security->sanitize($_POST['matomo_site_id'] ?? '', 'text'),
                    'seo_analytics_matomo_code'     => $_POST['matomo_code'] ?? '',
                    'seo_analytics_ga4_enabled'     => isset($_POST['ga4_enabled'])      ? '1' : '0',
                    'seo_analytics_ga4_id'          => $security->sanitize($_POST['ga4_id'] ?? '', 'text'),
                    'seo_analytics_gtm_enabled'     => isset($_POST['gtm_enabled'])      ? '1' : '0',
                    'seo_analytics_gtm_id'          => $security->sanitize($_POST['gtm_id'] ?? '', 'text'),
                    'seo_analytics_fb_pixel_enabled'=> isset($_POST['fb_pixel_enabled']) ? '1' : '0',
                    'seo_analytics_fb_pixel_id'     => $security->sanitize($_POST['fb_pixel_id'] ?? '', 'text'),
                    'seo_analytics_custom_head'     => $_POST['custom_head_code'] ?? '',
                    'seo_analytics_custom_body'     => $_POST['custom_body_code'] ?? '',
                    'seo_analytics_exclude_admins'  => isset($_POST['exclude_admins'])   ? '1' : '0',
                    'seo_analytics_anonymize_ip'    => isset($_POST['anonymize_ip'])     ? '1' : '0',
                    'seo_analytics_respect_dnt'     => isset($_POST['respect_dnt'])      ? '1' : '0',
                ];
                foreach ($analyticsFields as $k => $v) { seo_save($db, $k, $v); }
                $messages[] = ['type' => 'success', 'text' => 'Analytics-Einstellungen gespeichert.'];
            }
        } catch (\Exception $e) {
            $messages[] = ['type' => 'error', 'text' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }
}

// ── Alle Settings laden ────────────────────────────────────────────────────────
$s = [];
$allKeys = [
    'seo_site_title_format', 'seo_title_separator', 'seo_homepage_title',
    'seo_homepage_description', 'seo_meta_description', 'seo_meta_keywords',
    'seo_canonical_url', 'seo_noindex_archives', 'seo_noindex_search',
    'seo_noindex_author', 'seo_noindex_tag_pages',
    'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_locale', 'seo_og_type',
    'seo_twitter_card', 'seo_twitter_site', 'seo_twitter_creator', 'seo_linkedin_company',
    'seo_author_meta', 'seo_publisher_meta',
    'seo_schema_type', 'seo_schema_name', 'seo_schema_url', 'seo_schema_logo',
    'seo_schema_email', 'seo_schema_phone', 'seo_schema_address',
    'seo_schema_social_profiles', 'seo_breadcrumbs_enabled', 'seo_schema_jsonld_enabled',
    'setting_permalink_structure', 'setting_category_base', 'setting_tag_base',
    'setting_strip_category_base', 'setting_permalink_trailing_slash',
    'seo_robots_index', 'seo_robots_follow',
    'seo_sitemap_enabled', 'seo_sitemap_include_images', 'seo_sitemap_include_news',
    'seo_sitemap_default_priority', 'seo_sitemap_change_freq',
    'seo_indexnow_enabled', 'seo_indexnow_key',
    'seo_google_site_verification', 'seo_bing_site_verification',
    'seo_yandex_verification', 'seo_baidu_verification', 'seo_robots_txt_content',
    // Analytics
    'seo_analytics_matomo_enabled', 'seo_analytics_matomo_url', 'seo_analytics_matomo_site_id',
    'seo_analytics_matomo_code', 'seo_analytics_ga4_enabled', 'seo_analytics_ga4_id',
    'seo_analytics_gtm_enabled', 'seo_analytics_gtm_id',
    'seo_analytics_fb_pixel_enabled', 'seo_analytics_fb_pixel_id',
    'seo_analytics_custom_head', 'seo_analytics_custom_body',
    'seo_analytics_exclude_admins', 'seo_analytics_anonymize_ip', 'seo_analytics_respect_dnt',
];
foreach ($allKeys as $k) { $s[$k] = seo_get($db, $k); }

// Defaults
$s['setting_permalink_structure']  = $s['setting_permalink_structure']  ?: '/%postname%/';
$s['seo_robots_index']             = $s['seo_robots_index']             ?: 'index';
$s['seo_robots_follow']            = $s['seo_robots_follow']            ?: 'follow';
$s['seo_site_title_format']        = $s['seo_site_title_format']        ?: '{title} | {sitename}';
$s['seo_title_separator']          = $s['seo_title_separator']          ?: '|';
$s['seo_og_locale']                = $s['seo_og_locale']                ?: 'de_DE';
$s['seo_og_type']                  = $s['seo_og_type']                  ?: 'website';
$s['seo_twitter_card']             = $s['seo_twitter_card']             ?: 'summary_large_image';
$s['seo_schema_type']              = $s['seo_schema_type']              ?: 'Organization';
$s['seo_sitemap_default_priority'] = $s['seo_sitemap_default_priority'] ?: '0.7';
$s['seo_sitemap_change_freq']      = $s['seo_sitemap_change_freq']      ?: 'weekly';
$s['seo_robots_txt_content']       = $s['seo_robots_txt_content'] !== ''
    ? $s['seo_robots_txt_content']
    : "User-agent: *\nDisallow: /admin/\nAllow: /";
$s['seo_analytics_matomo_site_id'] = $s['seo_analytics_matomo_site_id'] ?: '1';
$s['seo_analytics_anonymize_ip']   = $s['seo_analytics_anonymize_ip']   !== '' ? $s['seo_analytics_anonymize_ip'] : '1';
$s['seo_analytics_respect_dnt']    = $s['seo_analytics_respect_dnt']    !== '' ? $s['seo_analytics_respect_dnt']  : '1';

$checked = fn(string $key, string $val = '1') => $s[$key] === $val ? 'checked' : '';
$sel     = fn(string $key, string $val)        => $s[$key] === $val ? 'selected' : '';
$val     = fn(string $key)                     => htmlspecialchars($s[$key]);

$csrfToken = $security->generateToken('seo_settings');
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('SEO Dashboard', 'seo' . ($activeTab !== 'general' ? '-' . $activeTab : ''));
?>


<style>
/* SEO-spezifische Komponenten (nicht in admin.css) */
.seo-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1.5rem; }
.seo-panel { display: none; }
.seo-panel.active { display: block; }
/* Toggle Switch */
.dw-toggle { position: relative; display: inline-flex; width: 44px; height: 24px; flex-shrink: 0; }
.dw-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
.dw-toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 24px; cursor: pointer; transition: background .2s; }
.dw-toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.dw-toggle input:checked + .dw-toggle-slider { background: var(--admin-primary, #3b82f6); }
.dw-toggle input:checked + .dw-toggle-slider::before { transform: translateX(20px); }
.dw-toggle-row { display: flex; align-items: center; justify-content: space-between; padding: .75rem 0; border-bottom: 1px solid #f1f5f9; }
.dw-toggle-row:last-child { border-bottom: none; }
.dw-toggle-label { font-weight: 500; font-size: .9rem; color: #1e293b; }
.dw-toggle-hint { font-size: .8rem; color: #64748b; margin-top: .15rem; }
/* Code-Vorschau */
.code-preview { background: #0f172a; color: #86efac; border-radius: 8px; padding: 1rem; font-family: 'Courier New', monospace; font-size: .8rem; white-space: pre-wrap; word-break: break-all; margin-top: .5rem; }
/* Badges */
.seo-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
.seo-badge-warn { background: #fef3c7; color: #92400e; }
.seo-badge-info { background: #dbeafe; color: #1e40af; }
.seo-badge-ok   { background: #dcfce7; color: #166534; }
/* Radio-Zeilen */
.dw-radio-row { display: flex; align-items: flex-start; gap: .75rem; padding: .75rem .5rem; border-radius: 8px; cursor: pointer; margin-bottom: .5rem; border: 1.5px solid #e2e8f0; background: #fff; transition: border-color .15s, background .15s; }
.dw-radio-row:hover { border-color: var(--admin-primary, #3b82f6); }
/* Card-Header mit Toggle rechts */
.dw-card-header { display: flex; align-items: center; gap: .75rem; margin-bottom: .25rem; }
.dw-card-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; flex: 1; }
/* Card Sub-Beschreibung */
.dw-card-sub { font-size: .82rem; color: #64748b; margin: 0 0 1rem; line-height: 1.5; }
</style>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🔍 SEO &amp; Performance</h2>
        <p>Suchmaschinenoptimierung, Social Media, Strukturierte Daten &amp; Indexierung</p>
    </div>
</div>

<!-- Messages -->
<?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?php echo $msg['type'] === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($msg['text']); ?>
    </div>
<?php endforeach; ?>

<!-- Tab Navigation -->
<nav class="lp-section-nav">
    <?php
    $navTabs = [
        'general'    => ['icon' => '⚙️',  'label' => 'Allgemein',          'desc' => 'Titel, Meta, Canonical'],
        'social'     => ['icon' => '📣',  'label' => 'Social Media',        'desc' => 'OG, Twitter/X, LinkedIn'],
        'structured' => ['icon' => '🗂️', 'label' => 'Strukturierte Daten', 'desc' => 'Schema.org, JSON-LD'],
        'permalinks' => ['icon' => '🔗',  'label' => 'Permalinks',          'desc' => 'URL-Struktur, Basis'],
        'indexing'   => ['icon' => '🤖',  'label' => 'Indexierung',         'desc' => 'Robots, Sitemap, IndexNow'],
        'analytics'  => ['icon' => '📊',  'label' => 'Analytics Code',      'desc' => 'Matomo, GA4, GTM, Pixel'],
    ];
    foreach ($navTabs as $tid => $t):
    ?>
    <a href="<?php echo SITE_URL; ?>/admin/seo?tab=<?php echo $tid; ?>" class="lp-section-nav__item <?php echo $activeTab === $tid ? 'active' : ''; ?>">
        <span class="lp-section-nav__icon"><?php echo $t['icon']; ?></span>
        <span><?php echo $t['label']; ?><br><small style="color:#94a3b8;font-size:.72rem;font-weight:400;"><?php echo $t['desc']; ?></small></span>
    </a>
    <?php endforeach; ?>
</nav>

    <!-- ──────────────────────────────────────────────────────── TAB: GENERAL -->
    <div class="seo-panel <?php echo $activeTab==='general' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=general">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_general">

    <div class="seo-grid">
        <!-- Titel-Template -->
        <div class="admin-card">
            <h3>📋 Titel & Format</h3>
                <div class="form-group">
                    <label>Titel-Template</label>
                    <input type="text" name="site_title_format" class="form-control" value="<?php echo $val('seo_site_title_format'); ?>">
                    <span class="form-text">Platzhalter: <code>{title}</code>, <code>{sitename}</code>, <code>{tagline}</code></span>
                </div>
                <div class="form-group">
                    <label>Trennzeichen</label>
                    <input type="text" name="title_separator" class="form-control" style="max-width:80px;" value="<?php echo $val('seo_title_separator'); ?>">
                </div>
                <div class="form-group">
                    <label>Startseiten-Titel</label>
                    <input type="text" name="homepage_title" class="form-control" value="<?php echo $val('seo_homepage_title'); ?>">
                </div>
                <div class="form-group">
                    <label>Startseiten-Description</label>
                    <textarea name="homepage_description" class="form-control" rows="2"><?php echo $val('seo_homepage_description'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Meta Defaults -->
        <div class="admin-card">
            <h3>🏷️ Standard-Meta</h3>
                <div class="form-group">
                    <label>Standard Meta-Description</label>
                    <textarea name="meta_description" class="form-control" rows="3"><?php echo $val('seo_meta_description'); ?></textarea>
                    <span class="form-text">Fallback für Seiten ohne eigene Description.</span>
                </div>
                <div class="form-group">
                    <label>Meta-Keywords <span class="seo-badge seo-badge-warn">Niedrige Relevanz</span></label>
                    <input type="text" name="meta_keywords" class="form-control" value="<?php echo $val('seo_meta_keywords'); ?>">
                    <span class="form-text">Kommagetrennt. Von den meisten Suchmaschinen ignoriert.</span>
                </div>
                <div class="form-group">
                    <label>Canonical URL Verhalten</label>
                    <select name="canonical_url" class="form-control">
                        <option value="auto"   <?php echo $sel('seo_canonical_url','auto'); ?>>Automatisch (Self-referencing)</option>
                        <option value="manual" <?php echo $sel('seo_canonical_url','manual'); ?>>Manuell überschreibbar</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Noindex Einstellungen -->
        <div class="admin-card">
            <h3>🚫 Noindex-Regeln</h3>
                <span class="form-text" style="display:block;margin-bottom:1rem;">Verhindert das Indexieren bestimmter Seitentypen.</span>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Archive-Seiten (noindex)</div>
                        <div class="dw-toggle-hint">Datums- und Autor-Archive</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="noindex_archives" <?php echo $checked('seo_noindex_archives'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Suchergebnis-Seiten (noindex)</div>
                        <div class="dw-toggle-hint">?s= Suche-Ergebnisse</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="noindex_search" <?php echo $checked('seo_noindex_search'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Autor-Seiten (noindex)</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="noindex_author" <?php echo $checked('seo_noindex_author'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Tag-Seiten (noindex)</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="noindex_tag_pages" <?php echo $checked('seo_noindex_tag_pages'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Allgemein speichern</button>
    </div>
    </form>
    </div><!-- /general -->


    <!-- ──────────────────────────────────────────────────────── TAB: SOCIAL -->
    <div class="seo-panel <?php echo $activeTab==='social' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=social">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_social">

    <div class="seo-grid">
        <!-- Open Graph -->
        <div class="admin-card">
            <h3>📘 Open Graph (Facebook / LinkedIn)</h3>
                <div class="form-group">
                    <label>OG Titel (Standard)</label>
                    <input type="text" name="og_title" class="form-control" value="<?php echo $val('seo_og_title'); ?>">
                </div>
                <div class="form-group">
                    <label>OG Description (Standard)</label>
                    <textarea name="og_description" class="form-control" rows="2"><?php echo $val('seo_og_description'); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Standard OG Bild URL</label>
                    <input type="url" name="og_image" class="form-control" placeholder="https://…/bild.jpg" value="<?php echo $val('seo_og_image'); ?>">
                    <span class="form-text">Empfohlen: 1200×630 px</span>
                </div>
                <div class="form-group">
                    <label>OG Locale</label>
                    <input type="text" name="og_locale" class="form-control" placeholder="de_DE" value="<?php echo $val('seo_og_locale'); ?>">
                </div>
                <div class="form-group">
                    <label>OG Type</label>
                    <select name="og_type" class="form-control">
                        <option value="website" <?php echo $sel('seo_og_type','website'); ?>>website</option>
                        <option value="article" <?php echo $sel('seo_og_type','article'); ?>>article</option>
                        <option value="profile" <?php echo $sel('seo_og_type','profile'); ?>>profile</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Twitter/X -->
        <div class="admin-card">
            <h3>𝕏 Twitter / X Card</h3>
                <div class="form-group">
                    <label>Card Typ</label>
                    <select name="twitter_card" class="form-control">
                        <option value="summary"             <?php echo $sel('seo_twitter_card','summary'); ?>>Summary</option>
                        <option value="summary_large_image" <?php echo $sel('seo_twitter_card','summary_large_image'); ?>>Summary Large Image</option>
                        <option value="app"                 <?php echo $sel('seo_twitter_card','app'); ?>>App</option>
                        <option value="player"              <?php echo $sel('seo_twitter_card','player'); ?>>Player</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Twitter Site <span class="seo-badge seo-badge-info">@handle</span></label>
                    <input type="text" name="twitter_site" class="form-control" placeholder="@deineFirma" value="<?php echo $val('seo_twitter_site'); ?>">
                </div>
                <div class="form-group">
                    <label>Twitter Creator</label>
                    <input type="text" name="twitter_creator" class="form-control" placeholder="@autor" value="<?php echo $val('seo_twitter_creator'); ?>">
                </div>
                <div class="form-group">
                    <label>LinkedIn Unternehmensseite URL</label>
                    <input type="url" name="linkedin_company" class="form-control" placeholder="https://linkedin.com/company/…" value="<?php echo $val('seo_linkedin_company'); ?>">
                </div>
            </div>
        </div>

        <!-- Authorship -->
        <div class="admin-card">
            <h3>👤 Authorship</h3>
                <div class="form-group">
                    <label>Globaler Autor-Name</label>
                    <input type="text" name="author_meta" class="form-control" value="<?php echo $val('seo_author_meta'); ?>">
                </div>
                <div class="form-group">
                    <label>Publisher Meta (Facebook Page URL)</label>
                    <input type="url" name="publisher_meta" class="form-control" placeholder="https://facebook.com/…" value="<?php echo $val('seo_publisher_meta'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Social-Media speichern</button>
    </div>
    </form>
    </div><!-- /social -->


    <!-- ─────────────────────────────────────────────── TAB: STRUCTURED DATA -->
    <div class="seo-panel <?php echo $activeTab==='structured' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=structured">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_structured">

    <div class="seo-grid">
        <!-- Organization Schema -->
        <div class="admin-card">
            <h3>🏢 Schema.org Entität</h3>
                <div class="form-group">
                    <label>Typ</label>
                    <select name="schema_type" class="form-control">
                        <option value="Organization"   <?php echo $sel('seo_schema_type','Organization'); ?>>Organization</option>
                        <option value="LocalBusiness"  <?php echo $sel('seo_schema_type','LocalBusiness'); ?>>LocalBusiness</option>
                        <option value="Person"         <?php echo $sel('seo_schema_type','Person'); ?>>Person</option>
                        <option value="WebSite"        <?php echo $sel('seo_schema_type','WebSite'); ?>>WebSite</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="schema_name" class="form-control" value="<?php echo $val('seo_schema_name'); ?>">
                </div>
                <div class="form-group">
                    <label>URL</label>
                    <input type="url" name="schema_url" class="form-control" value="<?php echo $val('seo_schema_url'); ?>">
                </div>
                <div class="form-group">
                    <label>Logo URL</label>
                    <input type="url" name="schema_logo" class="form-control" placeholder="https://…/logo.png" value="<?php echo $val('seo_schema_logo'); ?>">
                </div>
                <div class="form-group">
                    <label>E-Mail</label>
                    <input type="email" name="schema_email" class="form-control" value="<?php echo $val('seo_schema_email'); ?>">
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="schema_phone" class="form-control" placeholder="+49 …" value="<?php echo $val('seo_schema_phone'); ?>">
                </div>
                <div class="form-group">
                    <label>Adresse</label>
                    <input type="text" name="schema_address" class="form-control" placeholder="Musterstr. 1, 12345 Berlin" value="<?php echo $val('seo_schema_address'); ?>">
                </div>
                <div class="form-group">
                    <label>Social Profile URLs <span class="form-text" style="display:inline">(kommagetrennt)</span></label>
                    <textarea name="schema_social_profiles" class="form-control" rows="2"><?php echo $val('seo_schema_social_profiles'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- JSON-LD & Breadcrumbs -->
        <div class="admin-card">
            <h3>🗂️ JSON-LD & Breadcrumbs</h3>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">JSON-LD Block ausgeben</div>
                        <div class="dw-toggle-hint">Gibt &lt;script type="application/ld+json"&gt; im &lt;head&gt; aus</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="schema_jsonld_enabled" <?php echo $checked('seo_schema_jsonld_enabled'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Breadcrumb Schema aktivieren</div>
                        <div class="dw-toggle-hint">BreadcrumbList Schema für Navigation</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="breadcrumbs_enabled" <?php echo $checked('seo_breadcrumbs_enabled'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>

                <?php if ($s['seo_schema_name'] && $s['seo_schema_jsonld_enabled']): ?>
                <div style="margin-top:1.5rem;">
                    <label style="font-size:.8rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Vorschau JSON-LD</label>
                    <div class="code-preview"><?php
                        $preview = [
                            '@context' => 'https://schema.org',
                            '@type'    => $s['seo_schema_type'],
                            'name'     => $s['seo_schema_name'],
                            'url'      => $s['seo_schema_url'] ?: SITE_URL,
                        ];
                        if ($s['seo_schema_logo'])  { $preview['logo'] = $s['seo_schema_logo']; }
                        if ($s['seo_schema_email']) { $preview['email'] = $s['seo_schema_email']; }
                        echo htmlspecialchars(json_encode($preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Strukturierte Daten speichern</button>
    </div>
    </form>
    </div><!-- /structured -->


    <!-- ──────────────────────────────────────────────────── TAB: PERMALINKS -->
    <div class="seo-panel <?php echo $activeTab==='permalinks' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=permalinks">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_permalinks">

    <div class="seo-grid">
        <div class="admin-card">
            <h3>🔗 URL-Struktur</h3>
                <?php
                $presets = [
                    '/%postname%/'                => ['Beitragsname',   '/beispiel-beitrag/'],
                    '/%year%/%month%/%postname%/' => ['Datum & Name',    '/2024/03/beispiel/'],
                    '/archives/%post_id%'          => ['Numerisch',      '/archives/123'],
                ];
                $curPerm = $s['setting_permalink_structure'];
                $isCustom = !array_key_exists($curPerm, $presets) && $curPerm !== '';
                ?>
                <?php foreach ($presets as $pval => $pinfo): ?>
                <label class="dw-radio-row" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .4rem;border-radius:8px;cursor:pointer;margin-bottom:.5rem;border:1.5px solid <?php echo $curPerm===$pval?'#16a34a':'#e2e8f0'; ?>;background:<?php echo $curPerm===$pval?'#f0fdf4':'#fff'; ?>;">
                    <input type="radio" name="permalink_structure" value="<?php echo $pval; ?>" <?php echo $curPerm===$pval?'checked':''; ?>>
                    <div>
                        <strong style="font-size:.9rem;"><?php echo $pinfo[0]; ?></strong>
                        <code style="display:block;font-size:.8rem;color:#64748b;"><?php echo $pinfo[1]; ?></code>
                    </div>
                </label>
                <?php endforeach; ?>
                <label class="dw-radio-row" style="display:flex;align-items:center;gap:.75rem;padding:.6rem .4rem;border-radius:8px;cursor:pointer;margin-bottom:.5rem;border:1.5px solid <?php echo $isCustom?'#16a34a':'#e2e8f0'; ?>;background:<?php echo $isCustom?'#f0fdf4':'#fff'; ?>;">
                    <input type="radio" name="permalink_structure" value="custom" <?php echo $isCustom?'checked':''; ?>>
                    <div style="flex:1;">
                        <strong style="font-size:.9rem;">Benutzerdefiniert</strong>
                        <input type="text" name="permalink_custom" class="form-control" style="margin-top:.35rem;" placeholder="/%category%/%postname%/" value="<?php echo $isCustom ? $val('setting_permalink_structure') : ''; ?>">
                    </div>
                </label>
            </div>
        </div>

        <div class="admin-card">
            <h3>📁 Kategorie & Tag Basis</h3>
                <div class="form-group">
                    <label>Kategorie-Basis</label>
                    <input type="text" name="category_base" class="form-control" placeholder="category" value="<?php echo $val('setting_category_base'); ?>">
                    <span class="form-text">Beispiel: <code>category</code> → /category/news/</span>
                </div>
                <div class="form-group">
                    <label>Schlagwort-Basis</label>
                    <input type="text" name="tag_base" class="form-control" placeholder="tag" value="<?php echo $val('setting_tag_base'); ?>">
                </div>
                <div class="dw-toggle-row" style="margin-top:1rem;">
                    <div>
                        <div class="dw-toggle-label">Kategorie-Basis aus URLs entfernen</div>
                        <div class="dw-toggle-hint"><code>/news/</code> statt <code>/category/news/</code></div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="strip_category_base" <?php echo $checked('setting_strip_category_base'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Trailing Slash erzwingen</div>
                        <div class="dw-toggle-hint">/beitrag/ statt /beitrag</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="trailing_slash" <?php echo $checked('setting_permalink_trailing_slash'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Permalink-Struktur speichern</button>
    </div>
    </form>
    </div><!-- /permalinks -->


    <!-- ──────────────────────────────────────────────────────── TAB: INDEXING -->
    <div class="seo-panel <?php echo $activeTab==='indexing' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=indexing">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_indexing">

    <div class="seo-grid">
        <!-- Crawler -->
        <div class="admin-card">
            <h3>🤖 Crawler-Steuerung</h3>
                <div class="form-group">
                    <label>Suchmaschinen-Sichtbarkeit</label>
                    <select name="robots_index" class="form-control">
                        <option value="index"   <?php echo $sel('seo_robots_index','index'); ?>>Indexieren erlauben (index)</option>
                        <option value="noindex" <?php echo $sel('seo_robots_index','noindex'); ?>>Suchmaschinen abhalten (noindex)</option>
                    </select>
                    <span class="form-text">Setzt global <code>meta name="robots"</code>.</span>
                </div>
                <div class="form-group">
                    <label>Link-Verfolgung</label>
                    <select name="robots_follow" class="form-control">
                        <option value="follow"   <?php echo $sel('seo_robots_follow','follow'); ?>>Links folgen (follow)</option>
                        <option value="nofollow" <?php echo $sel('seo_robots_follow','nofollow'); ?>>Links ignorieren (nofollow)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>robots.txt Inhalt</label>
                    <textarea name="robots_txt_content" class="form-control" rows="5" style="font-family:monospace;font-size:.85rem;"><?php echo $val('seo_robots_txt_content'); ?></textarea>
                    <span class="form-text">Wird als virtuelle <code>/robots.txt</code> ausgeliefert.</span>
                </div>
            </div>
        </div>

        <!-- Sitemap -->
        <div class="admin-card">
            <h3>🗺️ XML-Sitemap</h3>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">XML Sitemap aktivieren</div>
                        <div class="dw-toggle-hint">Unter <code>/sitemap.xml</code> erreichbar</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="sitemap_enabled" <?php echo $checked('seo_sitemap_enabled'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">Bilder einschließen</div>
                        <div class="dw-toggle-hint">&lt;image:image&gt; Einträge</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="sitemap_include_images" <?php echo $checked('seo_sitemap_include_images'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">News-Sitemap einschließen</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="sitemap_include_news" <?php echo $checked('seo_sitemap_include_news'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="form-group" style="margin-top:1rem;">
                    <label>Standard Priority</label>
                    <select name="sitemap_default_priority" class="form-control">
                        <?php foreach (['1.0','0.9','0.8','0.7','0.6','0.5','0.4','0.3','0.2','0.1'] as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo $sel('seo_sitemap_default_priority',$p); ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Änderungsfrequenz</label>
                    <select name="sitemap_change_freq" class="form-control">
                        <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
                        <option value="<?php echo $f; ?>" <?php echo $sel('seo_sitemap_change_freq',$f); ?>><?php echo ucfirst($f); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- IndexNow -->
        <div class="admin-card">
            <h3>⚡ IndexNow</h3>
                <div class="dw-toggle-row">
                    <div>
                        <div class="dw-toggle-label">IndexNow automatischen Ping</div>
                        <div class="dw-toggle-hint">Benachrichtigt Bing & Yandex sofort bei neuen Inhalten</div>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="indexnow_enabled" <?php echo $checked('seo_indexnow_enabled'); ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="form-group" style="margin-top:1rem;">
                    <label>IndexNow API Key</label>
                    <div style="display:flex;gap:.5rem;">
                        <input type="text" name="indexnow_key" class="form-control" value="<?php echo $val('seo_indexnow_key'); ?>" placeholder="Zufälligen Key generieren…">
                        <button type="button" class="btn btn-primary" style="white-space:nowrap;padding:.5rem .85rem;font-size:.8rem;"
                            onclick="document.querySelector('[name=indexnow_key]').value=Math.random().toString(36).substring(2,34)">
                            🔄 Key generieren
                        </button>
                    </div>
                    <span class="form-text">Wird als <code>/{key}.txt</code> im Root abgelegt.</span>
                </div>
            </div>
        </div>

        <!-- Site Verification -->
        <div class="admin-card">
            <h3>✅ Site Verification</h3>
                <div class="form-group">
                    <label><i class="fab fa-google" style="color:#ea4335;"></i> Google Search Console</label>
                    <input type="text" name="google_site_verification" class="form-control" placeholder="Verification Code" value="<?php echo $val('seo_google_site_verification'); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-microsoft" style="color:#0078d4;"></i> Bing Webmaster Tools</label>
                    <input type="text" name="bing_site_verification" class="form-control" value="<?php echo $val('seo_bing_site_verification'); ?>">
                </div>
                <div class="form-group">
                    <label>Yandex Verification</label>
                    <input type="text" name="yandex_verification" class="form-control" value="<?php echo $val('seo_yandex_verification'); ?>">
                </div>
                <div class="form-group">
                    <label>Baidu Verification</label>
                    <input type="text" name="baidu_verification" class="form-control" value="<?php echo $val('seo_baidu_verification'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Indexierungs-Einstellungen speichern</button>
    </div>
    </form>
    </div><!-- /indexing -->


    <!-- ──────────────────────────────────────────────── TAB: ANALYTICS CODE -->
    <div class="seo-panel <?php echo $activeTab==='analytics' ? 'active' : ''; ?>">
    <form method="post" action="<?php echo SITE_URL; ?>/admin/seo?tab=analytics">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_analytics">

    <!-- Datenschutz-Hinweis -->
    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.5rem;">
        ℹ️ <strong>DSGVO-Hinweis:</strong> Stelle sicher, dass du Analytics-Dienste nur nach Einwilligung der Nutzer aktivierst.
        Verwende die Optionen „DNT respektieren" und „IP anonymisieren" wo möglich.
    </div>

    <div class="seo-grid">

        <!-- ─── Matomo ──────────────────────────────────────────────────────── -->
        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🔵 Matomo / Piwik</h3>
                <label class="dw-toggle" style="margin-left:auto;">
                    <input type="checkbox" name="matomo_enabled" id="matomoToggle"
                           <?php echo $checked('seo_analytics_matomo_enabled'); ?>
                           onchange="document.getElementById('matomoFields').style.display=this.checked?'block':'none'">
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <p class="dw-card-sub">Self-Hosted Analytics – DSGVO-konform ohne Cookie-Einwilligung möglich</p>

            <div id="matomoFields" style="display:<?php echo $s['seo_analytics_matomo_enabled']==='1'?'block':'none'; ?>;">
                <div class="form-group">
                    <label class="form-label">Matomo URL <span style="color:#ef4444;">*</span></label>
                    <input type="url" name="matomo_url" class="form-control"
                           placeholder="https://analytics.deinedomain.de/"
                           value="<?php echo htmlspecialchars($s['seo_analytics_matomo_url'] ?? ''); ?>">
                    <small class="form-text">URL deiner Matomo-Instanz (mit trailing Slash)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Site ID <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="matomo_site_id" class="form-control"
                           placeholder="1"
                           value="<?php echo htmlspecialchars($s['seo_analytics_matomo_site_id'] ?? '1'); ?>"
                           style="max-width:120px;">
                    <small class="form-text">Die ID der Website in Matomo (zu finden unter Verwaltung → Websites)</small>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        Eigener Tracking-Code
                        <span class="seo-badge seo-badge-info" style="margin-left:.5rem;">Optional</span>
                    </label>
                    <textarea name="matomo_code" class="form-control"
                              rows="6"
                              placeholder="<!-- Eigener Matomo-Code hier einfügen (überschreibt Auto-Code oben) -->"
                              style="font-family:'Courier New',monospace;font-size:.8rem;"><?php echo htmlspecialchars($s['seo_analytics_matomo_code'] ?? ''); ?></textarea>
                    <small class="form-text">
                        Leer lassen = Code wird automatisch aus URL + Site-ID generiert.
                        Eigener Code wird 1:1 im <code>&lt;head&gt;</code> ausgegeben.
                    </small>
                </div>
                <?php
                    $mUrl  = trim($s['seo_analytics_matomo_url'] ?? '');
                    $mSite = trim($s['seo_analytics_matomo_site_id'] ?? '1');
                    $mCode = trim($s['seo_analytics_matomo_code'] ?? '');
                    if ($mUrl && !$mCode):
                        $autoCode = "<!-- Matomo -->\n<script>\n  var _paq = window._paq = window._paq || [];\n  _paq.push(['trackPageView']);\n  _paq.push(['enableLinkTracking']);\n  (function() {\n    var u=\"{$mUrl}\";\n    _paq.push(['setTrackerUrl', u+'matomo.php']);\n    _paq.push(['setSiteId', '{$mSite}']);\n    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);\n  })();\n</script>";
                ?>
                <div style="margin-top:.75rem;">
                    <label style="font-size:.8rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Auto-generierter Code (Vorschau)</label>
                    <div class="code-preview"><?php echo htmlspecialchars($autoCode); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Google Analytics 4 ─────────────────────────────────────────── -->
        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🔴 Google Analytics 4 (GA4)</h3>
                <label class="dw-toggle" style="margin-left:auto;">
                    <input type="checkbox" name="ga4_enabled" id="ga4Toggle"
                           <?php echo $checked('seo_analytics_ga4_enabled'); ?>
                           onchange="document.getElementById('ga4Fields').style.display=this.checked?'block':'none'">
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <p class="dw-card-sub">Google Analytics 4 via gtag.js einbinden</p>

            <div id="ga4Fields" style="display:<?php echo $s['seo_analytics_ga4_enabled']==='1'?'block':'none'; ?>;">
                <div class="form-group">
                    <label class="form-label">Measurement ID <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="ga4_id" class="form-control"
                           placeholder="G-XXXXXXXXXX"
                           value="<?php echo htmlspecialchars($s['seo_analytics_ga4_id'] ?? ''); ?>">
                    <small class="form-text">Format: <code>G-XXXXXXXXXX</code> – zu finden in Google Analytics → Verwaltung → Datenstreams</small>
                </div>
                <?php if (!empty($s['seo_analytics_ga4_id'])): ?>
                <div style="margin-top:.75rem;">
                    <label style="font-size:.8rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Generierter Code</label>
                    <div class="code-preview"><?php echo htmlspecialchars(
                        "<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id=" . htmlspecialchars($s['seo_analytics_ga4_id']) . "\"></script>\n<script>\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', '" . htmlspecialchars($s['seo_analytics_ga4_id']) . "');\n</script>"
                    ); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Google Tag Manager ─────────────────────────────────────────── -->
        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🟠 Google Tag Manager (GTM)</h3>
                <label class="dw-toggle" style="margin-left:auto;">
                    <input type="checkbox" name="gtm_enabled" id="gtmToggle"
                           <?php echo $checked('seo_analytics_gtm_enabled'); ?>
                           onchange="document.getElementById('gtmFields').style.display=this.checked?'block':'none'">
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <p class="dw-card-sub">Tag Manager Container – verwaltet alle Tags zentral</p>

            <div id="gtmFields" style="display:<?php echo $s['seo_analytics_gtm_enabled']==='1'?'block':'none'; ?>;">
                <div class="form-group">
                    <label class="form-label">Container ID <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="gtm_id" class="form-control"
                           placeholder="GTM-XXXXXXX"
                           value="<?php echo htmlspecialchars($s['seo_analytics_gtm_id'] ?? ''); ?>">
                    <small class="form-text">Format: <code>GTM-XXXXXXX</code> – im GTM unter Container-Einstellungen</small>
                </div>
                <?php if (!empty($s['seo_analytics_gtm_id'])): ?>
                <div style="margin-top:.75rem;">
                    <label style="font-size:.8rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Head-Code</label>
                    <div class="code-preview"><?php echo htmlspecialchars(
                        "<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','" . htmlspecialchars($s['seo_analytics_gtm_id']) . "');</script>"
                    ); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Facebook / Meta Pixel ─────────────────────────────────────── -->
        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🟣 Facebook / Meta Pixel</h3>
                <label class="dw-toggle" style="margin-left:auto;">
                    <input type="checkbox" name="fb_pixel_enabled" id="fbPixelToggle"
                           <?php echo $checked('seo_analytics_fb_pixel_enabled'); ?>
                           onchange="document.getElementById('fbPixelFields').style.display=this.checked?'block':'none'">
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <p class="dw-card-sub">Meta Pixel für Werbung & Conversion-Tracking</p>

            <div id="fbPixelFields" style="display:<?php echo $s['seo_analytics_fb_pixel_enabled']==='1'?'block':'none'; ?>;">
                <div class="form-group">
                    <label class="form-label">Pixel ID <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="fb_pixel_id" class="form-control"
                           placeholder="123456789012345"
                           value="<?php echo htmlspecialchars($s['seo_analytics_fb_pixel_id'] ?? ''); ?>">
                    <small class="form-text">Die numerische Pixel-ID aus dem Meta Events Manager</small>
                </div>
                <?php if (!empty($s['seo_analytics_fb_pixel_id'])): ?>
                <div style="margin-top:.75rem;">
                    <label style="font-size:.8rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Generierter Code</label>
                    <div class="code-preview"><?php echo htmlspecialchars(
                        "<!-- Meta Pixel -->\n<script>\n!function(f,b,e,v,n,t,s)\n{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window, document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '" . htmlspecialchars($s['seo_analytics_fb_pixel_id']) . "');\nfbq('track', 'PageView');\n</script>"
                    ); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Eigener Code (Head & Body) ────────────────────────────────── -->
        <div class="admin-card" style="grid-column: 1 / -1;">
            <h3>✏️ Eigener Analytics-Code</h3>
            <p class="dw-card-sub">Hier kann beliebiger Tracking-Code (z. B. Piwik PRO, Plausible, Fathom, TikTok Pixel) eingebunden werden</p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Code im <code>&lt;head&gt;</code> einbinden</label>
                    <textarea name="custom_head_code" class="form-control"
                              rows="8"
                              placeholder="<!-- Tracking-Code für den Head hier einfügen -->"
                              style="font-family:'Courier New',monospace;font-size:.8rem;"><?php echo htmlspecialchars($s['seo_analytics_custom_head'] ?? ''); ?></textarea>
                    <small class="form-text">Wird direkt vor <code>&lt;/head&gt;</code> ausgegeben</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Code nach <code>&lt;body&gt;</code>-Tag einbinden</label>
                    <textarea name="custom_body_code" class="form-control"
                              rows="8"
                              placeholder="<!-- Code direkt nach <body> öffnendem Tag -->"
                              style="font-family:'Courier New',monospace;font-size:.8rem;"><?php echo htmlspecialchars($s['seo_analytics_custom_body'] ?? ''); ?></textarea>
                    <small class="form-text">Wird direkt nach dem öffnenden <code>&lt;body&gt;</code>-Tag ausgegeben (nützlich z. B. für GTM noscript)</small>
                </div>
            </div>
        </div>

        <!-- ─── Datenschutz & Optionen ────────────────────────────────────── -->
        <div class="admin-card">
            <h3>🔒 Datenschutz & Optionen</h3>
            <p class="dw-card-sub">Diese Einstellungen gelten für alle aktivierten Analytics-Tools</p>

            <div class="dw-toggle-row">
                <div>
                    <div class="dw-toggle-label">Admins vom Tracking ausschließen</div>
                    <div class="dw-toggle-hint">Eingeloggte Administratoren werden nicht getrackt</div>
                </div>
                <label class="dw-toggle">
                    <input type="checkbox" name="exclude_admins" <?php echo $checked('seo_analytics_exclude_admins'); ?>>
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <div class="dw-toggle-row">
                <div>
                    <div class="dw-toggle-label">IP-Adresse anonymisieren</div>
                    <div class="dw-toggle-hint">Aktiviert anonymizeIp() bei Matomo & GA4 (empfohlen für DSGVO)</div>
                </div>
                <label class="dw-toggle">
                    <input type="checkbox" name="anonymize_ip" <?php echo $checked('seo_analytics_anonymize_ip'); ?>>
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
            <div class="dw-toggle-row">
                <div>
                    <div class="dw-toggle-label">DNT (Do Not Track) respektieren</div>
                    <div class="dw-toggle-hint">Tracking wird deaktiviert, wenn der Browser DNT gesendet hat</div>
                </div>
                <label class="dw-toggle">
                    <input type="checkbox" name="respect_dnt" <?php echo $checked('seo_analytics_respect_dnt'); ?>>
                    <span class="dw-toggle-slider"></span>
                </label>
            </div>
        </div>

        <!-- ─── Status-Übersicht ─────────────────────────────────────────── -->
        <div class="admin-card">
            <h3>📋 Aktive Dienste – Übersicht</h3>
            <p class="dw-card-sub">Aktuell eingebundene Tracking-Dienste</p>

            <?php
            $services = [
                ['label' => 'Matomo',             'key' => 'seo_analytics_matomo_enabled',   'detail' => $s['seo_analytics_matomo_url'] ?? ''],
                ['label' => 'Google Analytics 4', 'key' => 'seo_analytics_ga4_enabled',      'detail' => $s['seo_analytics_ga4_id'] ?? ''],
                ['label' => 'Google Tag Manager', 'key' => 'seo_analytics_gtm_enabled',      'detail' => $s['seo_analytics_gtm_id'] ?? ''],
                ['label' => 'Facebook Pixel',     'key' => 'seo_analytics_fb_pixel_enabled','detail' => $s['seo_analytics_fb_pixel_id'] ?? ''],
                ['label' => 'Eigener Head-Code',  'key' => '',                               'detail' => !empty(trim($s['seo_analytics_custom_head'] ?? '')) ? 'Konfiguriert' : ''],
                ['label' => 'Eigener Body-Code',  'key' => '',                               'detail' => !empty(trim($s['seo_analytics_custom_body'] ?? '')) ? 'Konfiguriert' : ''],
            ];
            ?>
            <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.6rem .75rem;text-align:left;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">Dienst</th>
                        <th style="padding:.6rem .75rem;text-align:left;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">Status</th>
                        <th style="padding:.6rem .75rem;text-align:left;font-weight:600;color:#475569;border-bottom:1px solid #e2e8f0;">Konfiguration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $svc): ?>
                    <?php
                        $isActive = $svc['key']
                            ? ($s[$svc['key']] ?? '0') === '1'
                            : !empty($svc['detail']);
                    ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:.6rem .75rem;color:#1e293b;font-weight:500;"><?php echo htmlspecialchars($svc['label']); ?></td>
                        <td style="padding:.6rem .75rem;">
                            <span class="status-badge <?php echo $isActive ? 'active' : 'inactive'; ?>">
                                <?php echo $isActive ? '✅ Aktiv' : '⭕ Inaktiv'; ?>
                            </span>
                        </td>
                        <td style="padding:.6rem .75rem;color:#64748b;font-size:.82rem;">
                            <?php echo $svc['detail'] ? htmlspecialchars($svc['detail']) : '—'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            $anyActive = $s['seo_analytics_matomo_enabled']==='1'
                      || $s['seo_analytics_ga4_enabled']==='1'
                      || $s['seo_analytics_gtm_enabled']==='1'
                      || $s['seo_analytics_fb_pixel_enabled']==='1'
                      || !empty(trim($s['seo_analytics_custom_head'] ?? ''))
                      || !empty(trim($s['seo_analytics_custom_body'] ?? ''));
            if (!$anyActive): ?>
            <div class="empty-state" style="padding:1.5rem 0 0;">
                <p>⭕ Kein Analytics-Dienst aktiv</p>
                <p class="text-muted" style="font-size:.8rem;">Aktiviere mindestens einen Dienst oben und speichere.</p>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /seo-grid -->

    <div style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary">💾 Analytics-Einstellungen speichern</button>
    </div>
    </form>
    </div><!-- /analytics -->

  </div><!-- admin-content-inner -->
</div><!-- admin-content -->

<?php renderAdminLayoutEnd(); ?>
