<?php
/**
 * SEO Settings Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\Services\SEOService;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$user = $auth->getCurrentUser();
$security = Security::instance();
$db = Database::instance();
$seoService = SEOService::getInstance();

// Handle form submissions
$message = '';
$messageType = '';

// Determine active tab
$activeTab = $_GET['tab'] ?? 'general';
$allowedTabs = ['general', 'permalinks', 'indexing'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'general';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'seo_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        
        // --- SAVE GENERAL ---
        if ($_POST['action'] === 'save_seo_general') {
            $seoSettings = [
                'seo_meta_description' => $_POST['meta_description'] ?? '',
                'seo_meta_keywords' => $_POST['meta_keywords'] ?? '',
                'seo_og_title' => $_POST['og_title'] ?? '',
                'seo_og_description' => $_POST['og_description'] ?? '',
                'seo_og_image' => $_POST['og_image'] ?? '',
                'seo_twitter_card' => $_POST['twitter_card'] ?? 'summary_large_image',
                'seo_twitter_site' => $_POST['twitter_site'] ?? '',
                'seo_twitter_creator' => $_POST['twitter_creator'] ?? '',
                'seo_canonical_url' => $_POST['canonical_url'] ?? 'auto',
                'seo_author_meta' => $_POST['author_meta'] ?? '',
                'seo_publisher_meta' => $_POST['publisher_meta'] ?? '',
            ];
            
            try {
                foreach ($seoSettings as $key => $value) {
                    // Check if setting exists
                    $existing = $db->fetchAll("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
                    
                    if ($existing) {
                        $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$value, $key]);
                    } else {
                        $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$key, $value]);
                    }
                }
                $message = 'Allgemeine SEO-Einstellungen gespeichert.';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }

        // --- SAVE PERMALINKS ---
        } elseif ($_POST['action'] === 'save_seo_permalinks') {
            $permalinkSettings = [
                'setting_permalink_structure' => $_POST['permalink_structure'] ?? '/%postname%/',
                'setting_category_base' => $_POST['category_base'] ?? 'category',
                'setting_tag_base' => $_POST['tag_base'] ?? 'tag',
                'setting_strip_category_base' => isset($_POST['strip_category_base']) ? '1' : '0'
            ];

            try {
                foreach ($permalinkSettings as $key => $value) {
                    $existing = $db->fetchAll("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
                    if ($existing) {
                        $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$value, $key]);
                    } else {
                        $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$key, $value]);
                    }
                }
                $message = 'Permalink-Struktur aktualisiert.';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }

        // --- SAVE INDEXING ---
        } elseif ($_POST['action'] === 'save_seo_indexing') {
            $indexingSettings = [
                'seo_robots_index' => $_POST['robots_index'] ?? 'index',
                'seo_robots_follow' => $_POST['robots_follow'] ?? 'follow',
                'seo_sitemap_enabled' => isset($_POST['sitemap_enabled']) ? '1' : '0',
                'seo_indexnow_enabled' => isset($_POST['indexnow_enabled']) ? '1' : '0',
                'seo_indexnow_key' => $_POST['indexnow_key'] ?? '',
                'seo_google_site_verification' => $_POST['google_site_verification'] ?? '',
                'seo_bing_site_verification' => $_POST['bing_site_verification'] ?? '',
                'seo_yandex_verification' => $_POST['yandex_verification'] ?? '',
                'seo_baidu_verification' => $_POST['baidu_verification'] ?? '',
                'seo_robots_txt_content' => $_POST['robots_txt_content'] ?? ''
            ];

            try {
                foreach ($indexingSettings as $key => $value) {
                    $existing = $db->fetchAll("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
                    if ($existing) {
                        $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$value, $key]);
                    } else {
                        $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$key, $value]);
                    }
                }
                
                // Regenerate Files using Service if available
                if (method_exists($seoService, 'saveRobotsTxt')) {
                    $seoService->saveRobotsTxt();
                }
                if (method_exists($seoService, 'saveSitemap')) {
                    $seoService->saveSitemap();
                }

                $message = 'Indexierungs-Einstellungen gespeichert und Files generiert.';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Load current settings (All possible keys)
$settingKeys = [
    // General
    'seo_meta_description', 'seo_meta_keywords', 'seo_og_title', 'seo_og_description',
    'seo_og_image', 'seo_twitter_card', 'seo_twitter_site', 'seo_twitter_creator',
    'seo_canonical_url', 'seo_author_meta', 'seo_publisher_meta',
    // Permalinks
    'setting_permalink_structure', 'setting_category_base', 'setting_tag_base', 'setting_strip_category_base',
    // Indexing
    'seo_robots_index', 'seo_robots_follow', 'seo_sitemap_enabled', 
    'seo_indexnow_enabled', 'seo_indexnow_key',
    'seo_google_site_verification', 'seo_bing_site_verification', 
    'seo_yandex_verification', 'seo_baidu_verification',
    'seo_robots_txt_content'
];

$currentSettings = [];
foreach ($settingKeys as $key) {
    try {
        $result = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]);
        $currentSettings[$key] = $result ? $result['option_value'] : '';
    } catch (\Exception $e) {
        $currentSettings[$key] = '';
    }
}

// Defaults
if (empty($currentSettings['setting_permalink_structure'])) $currentSettings['setting_permalink_structure'] = '/%postname%/';
if (empty($currentSettings['seo_robots_index'])) $currentSettings['seo_robots_index'] = 'index';
if (empty($currentSettings['seo_robots_follow'])) $currentSettings['seo_robots_follow'] = 'follow';

// Generate CSRF token
$csrfToken = $security->generateToken('seo_settings');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Einstellungen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <style>
        /* Specific header overrides for SEO page to match layout */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0;
            background: transparent;
            box-shadow: none;
            position: relative;
            z-index: 10;
        }

        .header-content h1 {
            color: #1e293b;
            margin: 0;
            font-size: 1.8rem;
        }
        
        .card-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .card-grid-2 { grid-template-columns: 1fr; }
        }
        .help-text {
            display: block;
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
            white-space: pre-wrap;
        }
    </style>
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <?php renderAdminSidebar('seo' . ($activeTab !== 'general' ? '-' . $activeTab : '')); ?>

    <!-- Main Content -->
    <div class="admin-content">
        <header class="admin-header">
            <div class="header-content">
                <h1>
                    <?php 
                    if ($activeTab === 'permalinks') echo 'Permalinks';
                    elseif ($activeTab === 'indexing') echo 'Indexierung & Sitemaps';
                    else echo 'SEO Dashboard'; 
                    ?>
                </h1>
            </div>
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-external-link-alt"></i> Website öffnen
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <div class="admin-content-inner">
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
