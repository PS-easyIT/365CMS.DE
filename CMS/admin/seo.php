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
$allowedTabs = ['general', 'social', 'structured', 'permalinks', 'indexing'];
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

$checked = fn(string $key, string $val = '1') => $s[$key] === $val ? 'checked' : '';
$sel     = fn(string $key, string $val)        => $s[$key] === $val ? 'selected' : '';
$val     = fn(string $key)                     => htmlspecialchars($s[$key]);

$csrfToken = $security->generateToken('seo_settings');
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('SEO Dashboard', 'seo' . ($activeTab !== 'general' ? '-' . $activeTab : ''));
?>


<style>
/* ── SEO Dashboard Styles ─────────────────────────────────── */
.seo-page-header {
    background: linear-gradient(135deg, #052e16 0%, #14532d 45%, #16a34a 100%);
    border-radius: 12px; padding: 2rem 2rem 1.6rem; margin-bottom: 2rem;
    display: flex; align-items: center; gap: 1.5rem; color: #fff;
    box-shadow: 0 4px 20px rgba(22,163,74,.35);
}
.seo-page-header-icon {
    width: 56px; height: 56px; background: rgba(255,255,255,.15);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; flex-shrink: 0;
}
.seo-page-header-text h1 { margin: 0 0 .25rem; font-size: 1.5rem; font-weight: 700; }
.seo-page-header-text p  { margin: 0; opacity: .8; font-size: .9rem; }
.seo-nav { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 2rem; }
.seo-nav-tab {
    display: flex; align-items: center; gap: .7rem; padding: .65rem 1.1rem;
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
    color: #475569; font-size: .85rem; font-weight: 500; text-decoration: none;
    transition: all .2s; cursor: pointer;
}
.seo-nav-tab:hover { border-color: #16a34a; color: #16a34a; background: #f0fdf4; }
.seo-nav-tab.active { border-color: #16a34a; background: #f0fdf4; color: #15803d; font-weight: 700; }
.seo-nav-icon { width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9;
    display: flex; align-items: center; justify-content: center; font-size: .9rem; }
.seo-nav-tab.active .seo-nav-icon { background: #dcfce7; }
.seo-nav-label { display: flex; flex-direction: column; }
.seo-nav-label span:first-child { line-height: 1.2; }
.seo-nav-desc { font-size: .72rem; color: #94a3b8; font-weight: 400; }
.seo-nav-tab.active .seo-nav-desc { color: #86efac; }
.seo-panel { display: none; }
.seo-panel.active { display: block; }
.seo-save-btn {
    background: linear-gradient(135deg, #15803d, #16a34a); color: #fff;
    border: none; border-radius: 8px; padding: .65rem 1.5rem; font-size: .9rem;
    font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: .5rem;
    transition: transform .15s, box-shadow .15s;
}
.seo-save-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(22,163,74,.4); }
.code-preview {
    background: #0f172a; color: #86efac; border-radius: 8px; padding: 1rem;
    font-family: 'Courier New', monospace; font-size: .8rem; white-space: pre-wrap;
    word-break: break-all; margin-top: .5rem;
}
.seo-badge { display: inline-block; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
.seo-badge-warn { background: #fef3c7; color: #92400e; }
.seo-badge-info { background: #dbeafe; color: #1e40af; }
.seo-badge-ok   { background: #dcfce7; color: #166534; }
</style>

<div class="admin-content">
  <div class="admin-content-inner">

    <!-- Page Header -->
    <div class="seo-page-header">
        <div class="seo-page-header-icon"><i class="fas fa-search"></i></div>
        <div class="seo-page-header-text">
            <h1>SEO Dashboard</h1>
            <p>Suchmaschinenoptimierung, Social Media, Strukturierte Daten & Indexierung</p>
        </div>
    </div>

    <!-- Messages -->
    <?php foreach ($messages as $msg): ?>
        <div class="notice-<?php echo $msg['type']; ?>" style="margin-bottom:1rem;">
            <i class="fas <?php echo $msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg['text']); ?>
        </div>
    <?php endforeach; ?>

    <!-- Tab Navigation -->
    <nav class="seo-nav">
        <?php
        $tabs = [
            'general'    => ['icon'=>'fa-cogs',          'label'=>'Allgemein',           'desc'=>'Titel, Meta, Canonical'],
            'social'     => ['icon'=>'fa-share-alt',     'label'=>'Social Media',         'desc'=>'OG, Twitter/X, LinkedIn'],
            'structured' => ['icon'=>'fa-code',          'label'=>'Strukturierte Daten',  'desc'=>'Schema.org, JSON-LD'],
            'permalinks' => ['icon'=>'fa-link',          'label'=>'Permalinks',           'desc'=>'URL-Struktur, Basis'],
            'indexing'   => ['icon'=>'fa-robot',         'label'=>'Indexierung',          'desc'=>'Robots, Sitemap, IndexNow'],
        ];
        foreach ($tabs as $tid => $t):
            $active = $activeTab === $tid ? 'active' : '';
        ?>
        <a href="seo.php?tab=<?php echo $tid; ?>" class="seo-nav-tab <?php echo $active; ?>">
            <div class="seo-nav-icon"><i class="fas <?php echo $t['icon']; ?>"></i></div>
            <div class="seo-nav-label">
                <span><?php echo $t['label']; ?></span>
                <span class="seo-nav-desc"><?php echo $t['desc']; ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- ──────────────────────────────────────────────────────── TAB: GENERAL -->
    <div class="seo-panel <?php echo $activeTab==='general' ? 'active' : ''; ?>">
    <form method="post" action="seo.php?tab=general">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_general">

    <div class="dw-grid-2">
        <!-- Titel-Template -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-heading"></i> Titel & Format</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Titel-Template</label>
                    <input type="text" name="site_title_format" class="dw-input" value="<?php echo $val('seo_site_title_format'); ?>">
                    <span class="dw-hint">Platzhalter: <code>{title}</code>, <code>{sitename}</code>, <code>{tagline}</code></span>
                </div>
                <div class="dw-form-group">
                    <label>Trennzeichen</label>
                    <input type="text" name="title_separator" class="dw-input" style="max-width:80px;" value="<?php echo $val('seo_title_separator'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Startseiten-Titel</label>
                    <input type="text" name="homepage_title" class="dw-input" value="<?php echo $val('seo_homepage_title'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Startseiten-Description</label>
                    <textarea name="homepage_description" class="dw-input" rows="2"><?php echo $val('seo_homepage_description'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Meta Defaults -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-tags"></i> Standard-Meta</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Standard Meta-Description</label>
                    <textarea name="meta_description" class="dw-input" rows="3"><?php echo $val('seo_meta_description'); ?></textarea>
                    <span class="dw-hint">Fallback für Seiten ohne eigene Description.</span>
                </div>
                <div class="dw-form-group">
                    <label>Meta-Keywords <span class="seo-badge seo-badge-warn">Niedrige Relevanz</span></label>
                    <input type="text" name="meta_keywords" class="dw-input" value="<?php echo $val('seo_meta_keywords'); ?>">
                    <span class="dw-hint">Kommagetrennt. Von den meisten Suchmaschinen ignoriert.</span>
                </div>
                <div class="dw-form-group">
                    <label>Canonical URL Verhalten</label>
                    <select name="canonical_url" class="dw-input">
                        <option value="auto"   <?php echo $sel('seo_canonical_url','auto'); ?>>Automatisch (Self-referencing)</option>
                        <option value="manual" <?php echo $sel('seo_canonical_url','manual'); ?>>Manuell überschreibbar</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Noindex Einstellungen -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-eye-slash"></i> Noindex-Regeln</h3></div>
            <div class="dw-card-body">
                <span class="dw-hint" style="display:block;margin-bottom:1rem;">Verhindert das Indexieren bestimmter Seitentypen.</span>
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
        <button type="submit" class="seo-save-btn"><i class="fas fa-save"></i> Allgemein speichern</button>
    </div>
    </form>
    </div><!-- /general -->


    <!-- ──────────────────────────────────────────────────────── TAB: SOCIAL -->
    <div class="seo-panel <?php echo $activeTab==='social' ? 'active' : ''; ?>">
    <form method="post" action="seo.php?tab=social">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_social">

    <div class="dw-grid-2">
        <!-- Open Graph -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fab fa-facebook"></i> Open Graph (Facebook / LinkedIn)</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>OG Titel (Standard)</label>
                    <input type="text" name="og_title" class="dw-input" value="<?php echo $val('seo_og_title'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>OG Description (Standard)</label>
                    <textarea name="og_description" class="dw-input" rows="2"><?php echo $val('seo_og_description'); ?></textarea>
                </div>
                <div class="dw-form-group">
                    <label>Standard OG Bild URL</label>
                    <input type="url" name="og_image" class="dw-input" placeholder="https://…/bild.jpg" value="<?php echo $val('seo_og_image'); ?>">
                    <span class="dw-hint">Empfohlen: 1200×630 px</span>
                </div>
                <div class="dw-form-group">
                    <label>OG Locale</label>
                    <input type="text" name="og_locale" class="dw-input" placeholder="de_DE" value="<?php echo $val('seo_og_locale'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>OG Type</label>
                    <select name="og_type" class="dw-input">
                        <option value="website" <?php echo $sel('seo_og_type','website'); ?>>website</option>
                        <option value="article" <?php echo $sel('seo_og_type','article'); ?>>article</option>
                        <option value="profile" <?php echo $sel('seo_og_type','profile'); ?>>profile</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Twitter/X -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fab fa-x-twitter"></i> Twitter / X Card</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Card Typ</label>
                    <select name="twitter_card" class="dw-input">
                        <option value="summary"             <?php echo $sel('seo_twitter_card','summary'); ?>>Summary</option>
                        <option value="summary_large_image" <?php echo $sel('seo_twitter_card','summary_large_image'); ?>>Summary Large Image</option>
                        <option value="app"                 <?php echo $sel('seo_twitter_card','app'); ?>>App</option>
                        <option value="player"              <?php echo $sel('seo_twitter_card','player'); ?>>Player</option>
                    </select>
                </div>
                <div class="dw-form-group">
                    <label>Twitter Site <span class="seo-badge seo-badge-info">@handle</span></label>
                    <input type="text" name="twitter_site" class="dw-input" placeholder="@deineFirma" value="<?php echo $val('seo_twitter_site'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Twitter Creator</label>
                    <input type="text" name="twitter_creator" class="dw-input" placeholder="@autor" value="<?php echo $val('seo_twitter_creator'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>LinkedIn Unternehmensseite URL</label>
                    <input type="url" name="linkedin_company" class="dw-input" placeholder="https://linkedin.com/company/…" value="<?php echo $val('seo_linkedin_company'); ?>">
                </div>
            </div>
        </div>

        <!-- Authorship -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-user-tag"></i> Authorship</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Globaler Autor-Name</label>
                    <input type="text" name="author_meta" class="dw-input" value="<?php echo $val('seo_author_meta'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Publisher Meta (Facebook Page URL)</label>
                    <input type="url" name="publisher_meta" class="dw-input" placeholder="https://facebook.com/…" value="<?php echo $val('seo_publisher_meta'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="seo-save-btn"><i class="fas fa-save"></i> Social-Media speichern</button>
    </div>
    </form>
    </div><!-- /social -->


    <!-- ─────────────────────────────────────────────── TAB: STRUCTURED DATA -->
    <div class="seo-panel <?php echo $activeTab==='structured' ? 'active' : ''; ?>">
    <form method="post" action="seo.php?tab=structured">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_structured">

    <div class="dw-grid-2">
        <!-- Organization Schema -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-building"></i> Schema.org Entität</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Typ</label>
                    <select name="schema_type" class="dw-input">
                        <option value="Organization"   <?php echo $sel('seo_schema_type','Organization'); ?>>Organization</option>
                        <option value="LocalBusiness"  <?php echo $sel('seo_schema_type','LocalBusiness'); ?>>LocalBusiness</option>
                        <option value="Person"         <?php echo $sel('seo_schema_type','Person'); ?>>Person</option>
                        <option value="WebSite"        <?php echo $sel('seo_schema_type','WebSite'); ?>>WebSite</option>
                    </select>
                </div>
                <div class="dw-form-group">
                    <label>Name</label>
                    <input type="text" name="schema_name" class="dw-input" value="<?php echo $val('seo_schema_name'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>URL</label>
                    <input type="url" name="schema_url" class="dw-input" value="<?php echo $val('seo_schema_url'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Logo URL</label>
                    <input type="url" name="schema_logo" class="dw-input" placeholder="https://…/logo.png" value="<?php echo $val('seo_schema_logo'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>E-Mail</label>
                    <input type="email" name="schema_email" class="dw-input" value="<?php echo $val('seo_schema_email'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Telefon</label>
                    <input type="text" name="schema_phone" class="dw-input" placeholder="+49 …" value="<?php echo $val('seo_schema_phone'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Adresse</label>
                    <input type="text" name="schema_address" class="dw-input" placeholder="Musterstr. 1, 12345 Berlin" value="<?php echo $val('seo_schema_address'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Social Profile URLs <span class="dw-hint" style="display:inline">(kommagetrennt)</span></label>
                    <textarea name="schema_social_profiles" class="dw-input" rows="2"><?php echo $val('seo_schema_social_profiles'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- JSON-LD & Breadcrumbs -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-code"></i> JSON-LD & Breadcrumbs</h3></div>
            <div class="dw-card-body">
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
        <button type="submit" class="seo-save-btn"><i class="fas fa-save"></i> Strukturierte Daten speichern</button>
    </div>
    </form>
    </div><!-- /structured -->


    <!-- ──────────────────────────────────────────────────── TAB: PERMALINKS -->
    <div class="seo-panel <?php echo $activeTab==='permalinks' ? 'active' : ''; ?>">
    <form method="post" action="seo.php?tab=permalinks">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_permalinks">

    <div class="dw-grid-2">
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-link"></i> URL-Struktur</h3></div>
            <div class="dw-card-body">
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
                        <input type="text" name="permalink_custom" class="dw-input" style="margin-top:.35rem;" placeholder="/%category%/%postname%/" value="<?php echo $isCustom ? $val('setting_permalink_structure') : ''; ?>">
                    </div>
                </label>
            </div>
        </div>

        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-folder-tree"></i> Kategorie & Tag Basis</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Kategorie-Basis</label>
                    <input type="text" name="category_base" class="dw-input" placeholder="category" value="<?php echo $val('setting_category_base'); ?>">
                    <span class="dw-hint">Beispiel: <code>category</code> → /category/news/</span>
                </div>
                <div class="dw-form-group">
                    <label>Schlagwort-Basis</label>
                    <input type="text" name="tag_base" class="dw-input" placeholder="tag" value="<?php echo $val('setting_tag_base'); ?>">
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
        <button type="submit" class="seo-save-btn"><i class="fas fa-save"></i> Permalink-Struktur speichern</button>
    </div>
    </form>
    </div><!-- /permalinks -->


    <!-- ──────────────────────────────────────────────────────── TAB: INDEXING -->
    <div class="seo-panel <?php echo $activeTab==='indexing' ? 'active' : ''; ?>">
    <form method="post" action="seo.php?tab=indexing">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="action"     value="save_seo_indexing">

    <div class="dw-grid-2">
        <!-- Crawler -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-robot"></i> Crawler-Steuerung</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label>Suchmaschinen-Sichtbarkeit</label>
                    <select name="robots_index" class="dw-input">
                        <option value="index"   <?php echo $sel('seo_robots_index','index'); ?>>Indexieren erlauben (index)</option>
                        <option value="noindex" <?php echo $sel('seo_robots_index','noindex'); ?>>Suchmaschinen abhalten (noindex)</option>
                    </select>
                    <span class="dw-hint">Setzt global <code>meta name="robots"</code>.</span>
                </div>
                <div class="dw-form-group">
                    <label>Link-Verfolgung</label>
                    <select name="robots_follow" class="dw-input">
                        <option value="follow"   <?php echo $sel('seo_robots_follow','follow'); ?>>Links folgen (follow)</option>
                        <option value="nofollow" <?php echo $sel('seo_robots_follow','nofollow'); ?>>Links ignorieren (nofollow)</option>
                    </select>
                </div>
                <div class="dw-form-group">
                    <label>robots.txt Inhalt</label>
                    <textarea name="robots_txt_content" class="dw-input" rows="5" style="font-family:monospace;font-size:.85rem;"><?php echo $val('seo_robots_txt_content'); ?></textarea>
                    <span class="dw-hint">Wird als virtuelle <code>/robots.txt</code> ausgeliefert.</span>
                </div>
            </div>
        </div>

        <!-- Sitemap -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-sitemap"></i> XML-Sitemap</h3></div>
            <div class="dw-card-body">
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
                <div class="dw-form-group" style="margin-top:1rem;">
                    <label>Standard Priority</label>
                    <select name="sitemap_default_priority" class="dw-input">
                        <?php foreach (['1.0','0.9','0.8','0.7','0.6','0.5','0.4','0.3','0.2','0.1'] as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo $sel('seo_sitemap_default_priority',$p); ?>><?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="dw-form-group">
                    <label>Änderungsfrequenz</label>
                    <select name="sitemap_change_freq" class="dw-input">
                        <?php foreach (['always','hourly','daily','weekly','monthly','yearly','never'] as $f): ?>
                        <option value="<?php echo $f; ?>" <?php echo $sel('seo_sitemap_change_freq',$f); ?>><?php echo ucfirst($f); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- IndexNow -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-bolt"></i> IndexNow</h3></div>
            <div class="dw-card-body">
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
                <div class="dw-form-group" style="margin-top:1rem;">
                    <label>IndexNow API Key</label>
                    <div style="display:flex;gap:.5rem;">
                        <input type="text" name="indexnow_key" class="dw-input" value="<?php echo $val('seo_indexnow_key'); ?>" placeholder="Zufälligen Key generieren…">
                        <button type="button" class="seo-save-btn" style="white-space:nowrap;padding:.5rem .85rem;font-size:.8rem;"
                            onclick="document.querySelector('[name=indexnow_key]').value=Math.random().toString(36).substring(2,34)">
                            <i class="fas fa-sync"></i> Key generieren
                        </button>
                    </div>
                    <span class="dw-hint">Wird als <code>/{key}.txt</code> im Root abgelegt.</span>
                </div>
            </div>
        </div>

        <!-- Site Verification -->
        <div class="dw-card">
            <div class="dw-card-header"><h3><i class="fas fa-check-circle"></i> Site Verification</h3></div>
            <div class="dw-card-body">
                <div class="dw-form-group">
                    <label><i class="fab fa-google" style="color:#ea4335;"></i> Google Search Console</label>
                    <input type="text" name="google_site_verification" class="dw-input" placeholder="Verification Code" value="<?php echo $val('seo_google_site_verification'); ?>">
                </div>
                <div class="dw-form-group">
                    <label><i class="fab fa-microsoft" style="color:#0078d4;"></i> Bing Webmaster Tools</label>
                    <input type="text" name="bing_site_verification" class="dw-input" value="<?php echo $val('seo_bing_site_verification'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Yandex Verification</label>
                    <input type="text" name="yandex_verification" class="dw-input" value="<?php echo $val('seo_yandex_verification'); ?>">
                </div>
                <div class="dw-form-group">
                    <label>Baidu Verification</label>
                    <input type="text" name="baidu_verification" class="dw-input" value="<?php echo $val('seo_baidu_verification'); ?>">
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top:1.5rem;">
        <button type="submit" class="seo-save-btn"><i class="fas fa-save"></i> Indexierungs-Einstellungen speichern</button>
    </div>
    </form>
    </div><!-- /indexing -->

  </div><!-- admin-content-inner -->
</div><!-- admin-content -->

<?php renderAdminLayoutEnd(); ?>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

                <!-- TAB: GENERAL -->
                <?php if ($activeTab === 'general'): ?>
                <form method="post" action="seo.php?tab=general">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_seo_general">
                    
                    <div class="card-grid-2">
                        <!-- Meta Defaults -->
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-tags"></i> Meta Defaults</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Standard Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3"><?php echo htmlspecialchars($currentSettings['seo_meta_description'] ?? ''); ?></textarea>
                                    <span class="help-text">Standardbeschreibung für Seiten ohne eigene Description.</span>
                                </div>
                                <div class="form-group">
                                    <label>Standard Meta Keywords</label>
                                    <input type="text" name="meta_keywords" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_meta_keywords'] ?? ''); ?>">
                                    <span class="help-text">Kommagetrennt. Weniger relevant für moderne Suchmaschinen.</span>
                                </div>
                                <div class="form-group">
                                    <label>Canonical URL Verhalten</label>
                                    <select name="canonical_url" class="form-control">
                                        <option value="auto" <?php echo ($currentSettings['seo_canonical_url'] == 'auto') ? 'selected' : ''; ?>>Automatisch (Self-referencing)</option>
                                        <option value="manual" <?php echo ($currentSettings['seo_canonical_url'] == 'manual') ? 'selected' : ''; ?>>Manuell überschreibbar</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media / Open Graph -->
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-share-alt"></i> Social Media (OG & Twitter)</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Default OG Image URL</label>
                                    <input type="text" name="og_image" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_og_image'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Twitter Site (z.B. @firma)</label>
                                    <input type="text" name="twitter_site" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_twitter_site'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Twitter Card Typ</label>
                                    <select name="twitter_card" class="form-control">
                                        <option value="summary" <?php echo ($currentSettings['seo_twitter_card'] == 'summary') ? 'selected' : ''; ?>>Summary</option>
                                        <option value="summary_large_image" <?php echo ($currentSettings['seo_twitter_card'] == 'summary_large_image') ? 'selected' : ''; ?>>Summary Large Image</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Authorship -->
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-user-tag"></i> Authorship</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Publisher Meta (Facebook Page URL)</label>
                                    <input type="text" name="publisher_meta" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_publisher_meta'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Global Author Name</label>
                                    <input type="text" name="author_meta" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_author_meta'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Einstellungen speichern</button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- TAB: PERMALINKS -->
                <?php if ($activeTab === 'permalinks'): ?>
                <form method="post" action="seo.php?tab=permalinks">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_seo_permalinks">

                    <div class="card-grid-2">
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-link"></i> URL Struktur</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Permalink Struktur</label>
                                    <div style="margin-bottom: 15px;">
                                        <label class="radio-label" style="display:block; margin-bottom:8px;">
                                            <input type="radio" name="permalink_structure" value="/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%postname%/') ? 'checked' : ''; ?>> 
                                            Beitragsname <code style="color:#666">/beispiel-beitrag/</code>
                                        </label>
                                        <label class="radio-label" style="display:block; margin-bottom:8px;">
                                            <input type="radio" name="permalink_structure" value="/%year%/%month%/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%year%/%month%/%postname%/') ? 'checked' : ''; ?>> 
                                            Datum & Name <code style="color:#666">/2024/03/beispiel-beitrag/</code>
                                        </label>
                                        <label class="radio-label" style="display:block; margin-bottom:8px;">
                                            <input type="radio" name="permalink_structure" value="/archives/%post_id%" <?php echo ($currentSettings['setting_permalink_structure'] == '/archives/%post_id%') ? 'checked' : ''; ?>> 
                                            Numerisch <code style="color:#666">/archives/123</code>
                                        </label>
                                        <label class="radio-label" style="display:block; margin-bottom:8px;">
                                            <input type="radio" name="permalink_structure" value="custom" <?php echo (strpos($currentSettings['setting_permalink_structure'], '%') === false && $currentSettings['setting_permalink_structure'] != '') ? 'checked' : ''; ?>> 
                                            Benutzerdefiniert
                                        </label>
                                    </div>
                                    <span class="help-text">Wähle, wie deine URLs aussehen sollen. Dies beeinflusst die SEO aller Beiträge.</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-folder-tree"></i> Kategorie & Tag Basis</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Kategorie-Basis</label>
                                    <input type="text" name="category_base" class="form-control" value="<?php echo htmlspecialchars($currentSettings['setting_category_base'] ?? 'category'); ?>">
                                    <span class="help-text">Standard: <code>category</code> (z.B. /category/news/)</span>
                                </div>
                                <div class="form-group">
                                    <label>Schlagwort-Basis</label>
                                    <input type="text" name="tag_base" class="form-control" value="<?php echo htmlspecialchars($currentSettings['setting_tag_base'] ?? 'tag'); ?>">
                                    <span class="help-text">Standard: <code>tag</code></span>
                                </div>
                                <div class="form-group" style="margin-top:20px;">
                                    <label class="checkbox-container">
                                        <input type="checkbox" name="strip_category_base" <?php echo ($currentSettings['setting_strip_category_base'] == '1') ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        Kategorie-Basis aus URLs entfernen
                                    </label>
                                    <span class="help-text" style="display:block; margin-left:30px;">Macht URLs kürzer: <code>/news/</code> statt <code>/category/news/</code>. Vorsicht bei Konflikten mit Seitennamen!</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Permalink-Struktur speichern</button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- TAB: INDEXING -->
                <?php if ($activeTab === 'indexing'): ?>
                <form method="post" action="seo.php?tab=indexing">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_seo_indexing">

                    <div class="card-grid-2">
                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-robot"></i> Crawler Steuerung</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Suchmaschinen-Sichtbarkeit</label>
                                    <select name="robots_index" class="form-control">
                                        <option value="index" <?php echo ($currentSettings['seo_robots_index'] == 'index') ? 'selected' : ''; ?>>Indexieren erlauben (index)</option>
                                        <option value="noindex" <?php echo ($currentSettings['seo_robots_index'] == 'noindex') ? 'selected' : ''; ?>>Suchmaschinen abhalten (noindex)</option>
                                    </select>
                                    <span class="help-text">Setzt global das <code>meta name="robots"</code> Tag.</span>
                                </div>
                                <div class="form-group">
                                    <label>Link-Verfolgung</label>
                                    <select name="robots_follow" class="form-control">
                                        <option value="follow" <?php echo ($currentSettings['seo_robots_follow'] == 'follow') ? 'selected' : ''; ?>>Links folgen (follow)</option>
                                        <option value="nofollow" <?php echo ($currentSettings['seo_robots_follow'] == 'nofollow') ? 'selected' : ''; ?>>Links ignorieren (nofollow)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>robots.txt Inhalt</label>
                                    <textarea name="robots_txt_content" class="form-control" rows="5" style="font-family:monospace;"><?php echo htmlspecialchars($currentSettings['seo_robots_txt_content'] ?? "User-agent: *\nDisallow: /admin/\nAllow: /"); ?></textarea>
                                    <span class="help-text">Wird in die virtuelle robots.txt geschrieben.</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-sitemap"></i> Sitemap & IndexNow</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="checkbox-container">
                                        <input type="checkbox" name="sitemap_enabled" <?php echo ($currentSettings['seo_sitemap_enabled'] == '1') ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        XML Sitemap generieren
                                    </label>
                                    <span class="help-text" style="display:block; margin-left:30px;">Unter <code>/sitemap.xml</code> erreichbar. Aktualisiert sich bei neuen Beiträgen.</span>
                                </div>
                                
                                <hr style="margin: 20px 0; border:0; border-top:1px solid #eee;">

                                <div class="form-group">
                                    <label class="checkbox-container">
                                        <input type="checkbox" name="indexnow_enabled" <?php echo ($currentSettings['seo_indexnow_enabled'] == '1') ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        IndexNow automatischen Ping aktivieren
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>IndexNow API Key</label>
                                    <input type="text" name="indexnow_key" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_indexnow_key'] ?? ''); ?>">
                                    <span class="help-text">Bing/Yandex IndexNow Key. Muss im Root als txt Datei liegen (wird automatisch erstellt).</span>
                                </div>
                            </div>
                        </div>

                        <div class="admin-card">
                            <div class="card-header">
                                <h3><i class="fas fa-check-circle"></i> Site Verification</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Google Search Console</label>
                                    <input type="text" name="google_site_verification" class="form-control" placeholder="content='...'" value="<?php echo htmlspecialchars($currentSettings['seo_google_site_verification'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Bing Webmaster Tools</label>
                                    <input type="text" name="bing_site_verification" class="form-control" value="<?php echo htmlspecialchars($currentSettings['seo_bing_site_verification'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Yandex / Baidu</label>
                                    <div style="display:flex; gap:10px;">
                                        <input type="text" name="yandex_verification" class="form-control" placeholder="Yandex Code" value="<?php echo htmlspecialchars($currentSettings['seo_yandex_verification'] ?? ''); ?>">
                                        <input type="text" name="baidu_verification" class="form-control" placeholder="Baidu Code" value="<?php echo htmlspecialchars($currentSettings['seo_baidu_verification'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Indexierungseinstellungen speichern</button>
                    </div>
                </form>
                <?php endif; ?>

            </div> <!-- End admin-content-inner -->
        </div> <!-- End admin-content (main wrapper) -->
    
</body>
</html>
