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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'seo_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        if ($_POST['action'] === 'save_seo') {
            // Save SEO settings
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
                'seo_robots_index' => $_POST['robots_index'] ?? 'index',
                'seo_robots_follow' => $_POST['robots_follow'] ?? 'follow',
                'seo_google_analytics' => $_POST['google_analytics'] ?? '',
                'seo_google_site_verification' => $_POST['google_site_verification'] ?? '',
                'seo_bing_site_verification' => $_POST['bing_site_verification'] ?? '',
                'seo_favicon_url' => $_POST['favicon_url'] ?? '/assets/images/favicon.ico',
                'seo_apple_touch_icon' => $_POST['apple_touch_icon'] ?? '',
                'seo_robots_txt_content' => $_POST['robots_txt_content'] ?? '',
                'seo_custom_header_code' => $_POST['custom_header_code'] ?? '',
            ];
            
            try {
                foreach ($seoSettings as $key => $value) {
                    // Check if setting exists
                    $existing = $db->execute(
                        "SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?",
                        [$key]
                    )->fetch();
                    
                    if ($existing) {
                        // Update
                        $db->execute(
                            "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
                            [$value, $key]
                        );
                    } else {
                        // Insert
                        $db->execute(
                            "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
                            [$key, $value]
                        );
                    }
                }
                
                // Regenerate Files using Service
                $seoService->saveRobotsTxt();
                $seoService->saveSitemap();
                
                $message = 'SEO-Einstellungen inkl. robots.txt und Sitemap erfolgreich gespeichert.';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'regenerate_assets') {
             try {
                $seoService->saveRobotsTxt();
                $seoService->saveSitemap();
                $message = 'Sitemap.xml und robots.txt wurden erfolgreich neu generiert.';
                $messageType = 'success';
             } catch (\Exception $e) {
                $message = 'Fehler beim Generieren: ' . $e->getMessage();
                $messageType = 'error';
             }
        }
    }
}

// Load current settings
$currentSettings = [];
$settingKeys = [
    'seo_meta_description', 'seo_meta_keywords', 'seo_og_title', 'seo_og_description',
    'seo_og_image', 'seo_twitter_card', 'seo_twitter_site', 'seo_twitter_creator',
    'seo_canonical_url', 'seo_robots_index', 'seo_robots_follow',
    'seo_google_analytics', 'seo_google_site_verification', 'seo_bing_site_verification',
    'seo_favicon_url', 'seo_apple_touch_icon', 'seo_robots_txt_content',
    'seo_custom_header_code'
];

foreach ($settingKeys as $key) {
    $result = $db->execute(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    )->fetch();
    
    $currentSettings[$key] = $result ? $result->option_value : '';
}

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
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .seo-section {
            background: white;
            padding: 2.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .seo-section h3 {
            margin: 0 0 1.5rem 0;
            color: #1e293b;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 1400px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
        }
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .help-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.375rem;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.875rem 2.5rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-size: 0.9375rem;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 1rem;
        }
        
        .info-card p {
            margin: 0 0 0.5rem 0;
            font-size: 0.875rem;
            color: #475569;
        }
        
        .info-card .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('seo'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🔍 SEO & Meta Tags</h2>
                <p style="margin: 0.5rem 0 0 0; color: #64748b;">Suchmaschinenoptimierung und Social Media Einstellungen</p>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="save_seo">
            
            <!-- Meta Tags -->
            <div class="seo-section">
                <h3>📝 Meta Tags</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="3"><?php echo htmlspecialchars($currentSettings['seo_meta_description']); ?></textarea>
                        <div class="help-text">Standard-Beschreibung für Suchmaschinen (max. 160 Zeichen empfohlen)</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" value="<?php echo htmlspecialchars($currentSettings['seo_meta_keywords']); ?>">
                        <div class="help-text">Komma-getrennte Keywords (optional, wird von den meisten Suchmaschinen ignoriert)</div>
                    </div>
                </div>
            </div>
            
            <!-- Open Graph -->
            <div class="seo-section">
                <h3>📱 Open Graph (Facebook, LinkedIn)</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="og_title">OG Titel</label>
                        <input type="text" name="og_title" id="og_title" value="<?php echo htmlspecialchars($currentSettings['seo_og_title']); ?>">
                        <div class="help-text">Leer = Seitentitel verwenden</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="og_description">OG Beschreibung</label>
                        <input type="text" name="og_description" id="og_description" value="<?php echo htmlspecialchars($currentSettings['seo_og_description']); ?>">
                        <div class="help-text">Kurzbeschreibung für Social Media</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="og_image">OG Bild URL</label>
                        <input type="text" name="og_image" id="og_image" value="<?php echo htmlspecialchars($currentSettings['seo_og_image']); ?>">
                        <div class="help-text">Absolute URL, min. 1200x630px empfohlen</div>
                    </div>
                </div>
            </div>
            
            <!-- Twitter Card -->
            <div class="seo-section">
                <h3>🐦 Twitter Card</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="twitter_card">Twitter Card Typ</label>
                        <select name="twitter_card" id="twitter_card">
                            <option value="summary" <?php echo $currentSettings['seo_twitter_card'] === 'summary' ? 'selected' : ''; ?>>Summary</option>
                            <option value="summary_large_image" <?php echo $currentSettings['seo_twitter_card'] === 'summary_large_image' ? 'selected' : ''; ?>>Summary Large Image</option>
                            <option value="app" <?php echo $currentSettings['seo_twitter_card'] === 'app' ? 'selected' : ''; ?>>App Card</option>
                            <option value="player" <?php echo $currentSettings['seo_twitter_card'] === 'player' ? 'selected' : ''; ?>>Player Card</option>
                        </select>
                        <div class="help-text">Art der Twitter-Vorschau</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_site">Twitter Site Account</label>
                        <input type="text" name="twitter_site" id="twitter_site" value="<?php echo htmlspecialchars($currentSettings['seo_twitter_site']); ?>" placeholder="@IhrAccount">
                        <div class="help-text">Haupt-Twitter-Account</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="twitter_creator">Twitter Creator Account</label>
                        <input type="text" name="twitter_creator" id="twitter_creator" value="<?php echo htmlspecialchars($currentSettings['seo_twitter_creator']); ?>" placeholder="@AutorAccount">
                        <div class="help-text">Autor des Inhalts</div>
                    </div>
                </div>
            </div>
            
            <!-- Robots & Canonical -->
            <div class="seo-section">
                <h3>🤖 Suchmaschinen-Steuerung</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="canonical_url">Canonical URL Modus</label>
                        <select name="canonical_url" id="canonical_url">
                            <option value="auto" <?php echo $currentSettings['seo_canonical_url'] === 'auto' ? 'selected' : ''; ?>>Automatisch generieren</option>
                            <option value="custom" <?php echo $currentSettings['seo_canonical_url'] === 'custom' ? 'selected' : ''; ?>>Eigene URL verwenden</option>
                            <option value="disable" <?php echo $currentSettings['seo_canonical_url'] === 'disable' ? 'selected' : ''; ?>>Deaktiviert</option>
                        </select>
                        <div class="help-text">Verhindert Duplicate Content</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="robots_index">Indexierung</label>
                        <select name="robots_index" id="robots_index">
                            <option value="index" <?php echo $currentSettings['seo_robots_index'] === 'index' ? 'selected' : ''; ?>>Index (Erlauben)</option>
                            <option value="noindex" <?php echo $currentSettings['seo_robots_index'] === 'noindex' ? 'selected' : ''; ?>>NoIndex (Verbieten)</option>
                        </select>
                        <div class="help-text">In Suchmaschinen anzeigen</div>
                    </div>

                    <div class="form-group">
                        <label for="robots_follow">Links folgen</label>
                        <select name="robots_follow" id="robots_follow">
                            <option value="follow" <?php echo $currentSettings['seo_robots_follow'] === 'follow' ? 'selected' : ''; ?>>Follow (Folgen)</option>
                            <option value="nofollow" <?php echo $currentSettings['seo_robots_follow'] === 'nofollow' ? 'selected' : ''; ?>>NoFollow (Nicht folgen)</option>
                        </select>
                        <div class="help-text">Links auf der Seite crawlen</div>
                    </div>
                </div>
            </div>

            <!-- Custom Header Code & Tracking -->
            <div class="seo-section">
                <h3>🛠️ Custom Code (Header)</h3>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="custom_header_code">Eigener HTML/JS Code</label>
                        <textarea name="custom_header_code" id="custom_header_code" rows="6" 
                                  placeholder="<script>...</script> oder <style>...</style>"><?php echo htmlspecialchars($currentSettings['seo_custom_header_code']); ?></textarea>
                        <div class="help-text">
                            Dieser Code wird direkt vor dem schließenden <code>&lt;/head&gt;</code> Tag eingefügt.<br>
                            Nutzen Sie dies für Analytics (z.B. Matomo), Tracking-Pixel oder Verifizierungs-Tags.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sitemap & Robots.txt -->
            <div class="seo-section">
                <h3>🗺️ Sitemap & Robots.txt</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>XML Sitemap</label>
                        <div class="info-card">
                            <p>Die XML Sitemap wird automatisch generiert.</p>
                            <a href="<?php echo SITE_URL; ?>/sitemap.xml" target="_blank" class="btn btn-secondary">
                                📄 Sitemap öffnen
                            </a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Robots.txt Vorschau</label>
                        <div class="info-card">
                            <p>Die robots.txt wird automatisch generiert.</p>
                            <a href="<?php echo SITE_URL; ?>/robots.txt" target="_blank" class="btn btn-secondary">
                                🤖 Robots.txt öffnen
                            </a>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="robots_txt_content">Robots.txt Inhalt</label>
                        <textarea name="robots_txt_content" id="robots_txt_content" rows="6" style="font-family: monospace;"><?php echo htmlspecialchars($currentSettings['seo_robots_txt_content'] ?: "User-agent: *\nDisallow: /admin/\nDisallow: /includes/\nAllow: /"); ?></textarea>
                        <div class="help-text">Dieser Inhalt wird unter <?php echo SITE_URL; ?>/robots.txt ausgegeben.</div>
                    </div>

                    <div class="form-group full-width" style="margin-top: 1rem;">
                        <button type="submit" name="action" value="regenerate_assets" class="btn btn-secondary" style="margin-left: 0;">
                            🔄 Assets neu generieren
                        </button>
                        <span class="help-text" style="margin-left: 0.5rem; display: inline-block;">(Sitemap.xml & robots.txt physisch neu schreiben)</span>
                    </div>
                </div>
            </div>

            <!-- Analytics & Verification -->
            <div class="seo-section">
                <h3>📊 Analytics & Webmaster Tools</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="google_analytics">Google Analytics ID</label>
                        <input type="text" name="google_analytics" id="google_analytics" value="<?php echo htmlspecialchars($currentSettings['seo_google_analytics']); ?>" placeholder="G-XXXXXXXXXX">
                        <div class="help-text">GA4: G-XXXXXXXXXX oder Universal: UA-XXXXXXXX-X</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="google_site_verification">Google Site Verification</label>
                        <input type="text" name="google_site_verification" id="google_site_verification" value="<?php echo htmlspecialchars($currentSettings['seo_google_site_verification']); ?>">
                        <div class="help-text">Google Search Console Verification Code</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bing_site_verification">Bing Site Verification</label>
                        <input type="text" name="bing_site_verification" id="bing_site_verification" value="<?php echo htmlspecialchars($currentSettings['seo_bing_site_verification']); ?>">
                        <div class="help-text">Bing Webmaster Tools Verification Code</div>
                    </div>
                </div>
            </div>
            
            <!-- Icons -->
            <div class="seo-section">
                <h3>🎨 Favicon & Icons</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="favicon_url">Favicon URL</label>
                        <input type="text" name="favicon_url" id="favicon_url" value="<?php echo htmlspecialchars($currentSettings['seo_favicon_url']); ?>" placeholder="/assets/images/favicon.ico">
                        <div class="help-text">32x32px .ico oder .png</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="apple_touch_icon">Apple Touch Icon URL</label>
                        <input type="text" name="apple_touch_icon" id="apple_touch_icon" value="<?php echo htmlspecialchars($currentSettings['seo_apple_touch_icon']); ?>">
                        <div class="help-text">180x180px .png für iOS Geräte</div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: right; margin-top: 2rem;">
                <button type="submit" class="btn-save">
                    💾 Einstellungen Speichern
                </button>
            </div>
        </form>
        
    </div>
    
</body>
</html>
