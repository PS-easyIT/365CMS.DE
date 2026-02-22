<?php
/**
 * Performance Settings Admin Page
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

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'performance_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        if ($_POST['action'] === 'clear_cache') {
            // Clear Cache Action
            try {
                // Clear DB Cache Table
                $db->execute("TRUNCATE TABLE {$db->getPrefix()}cache");
                
                // Clear File Cache (if any)
                $cacheDir = ABSPATH . 'cache/';
                $files = glob($cacheDir . '*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.htaccess' && basename($file) !== 'index.php') {
                        @unlink($file);
                    }
                }
                
                $message = 'Cache erfolgreich geleert (DB + Dateien)';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Leeren des Caches: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($_POST['action'] === 'save_performance') {
            // Save performance settings
            $perfSettings = [
                'perf_enable_lazy_loading' => isset($_POST['enable_lazy_loading']) ? '1' : '0',
                'perf_minify_css' => isset($_POST['minify_css']) ? '1' : '0',
                'perf_minify_js' => isset($_POST['minify_js']) ? '1' : '0',
                'perf_enable_preload_fonts' => isset($_POST['enable_preload_fonts']) ? '1' : '0',
                'perf_enable_gzip' => isset($_POST['enable_gzip']) ? '1' : '0',
                'perf_enable_browser_cache' => isset($_POST['enable_browser_cache']) ? '1' : '0',
                'perf_cache_duration' => intval($_POST['cache_duration'] ?? 86400),
                'perf_defer_js' => isset($_POST['defer_js']) ? '1' : '0',
                'perf_async_css' => isset($_POST['async_css']) ? '1' : '0',
                'perf_preload_critical_css' => isset($_POST['preload_critical_css']) ? '1' : '0',
                'perf_disable_emojis' => isset($_POST['disable_emojis']) ? '1' : '0',
                'perf_limit_revisions' => intval($_POST['limit_revisions'] ?? 5),
            ];
            
            try {
                foreach ($perfSettings as $key => $value) {
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
                
                $message = 'Performance-Einstellungen erfolgreich gespeichert';
                $messageType = 'success';
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Load current settings
$currentSettings = [];
$settingKeys = [
    'perf_enable_lazy_loading', 'perf_minify_css', 'perf_minify_js',
    'perf_enable_preload_fonts', 'perf_enable_gzip', 'perf_enable_browser_cache',
    'perf_cache_duration', 'perf_defer_js', 'perf_async_css',
    'perf_preload_critical_css', 'perf_disable_emojis', 'perf_limit_revisions'
];

foreach ($settingKeys as $key) {
    $result = $db->execute(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    )->fetch();
    
    $currentSettings[$key] = $result ? $result->option_value : '';
}

// Generate CSRF token
$csrfToken = $security->generateToken('performance_settings');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Einstellungen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('performance'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>⚡ Performance Optimierung</h2>
            <p>Beschleunigen Sie Ihre Website durch intelligente Optimierungen</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="save_performance">
            
            <!-- Asset Optimization -->
            <div class="perf-section">
                <h3>Asset-Optimierung <span class="performance-badge">EMPFOHLEN</span></h3>
                <div class="form-grid">
                    <div class="checkbox-group">
                        <input type="checkbox" id="minify_css" name="minify_css" <?php echo $currentSettings['perf_minify_css'] === '1' ? 'checked' : ''; ?>>
                        <label for="minify_css">
                            <strong>CSS Minifizierung</strong>
                            <div class="help-text">Entfernt Leerzeichen und Kommentare aus CSS-Dateien</div>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="minify_js" name="minify_js" <?php echo $currentSettings['perf_minify_js'] === '1' ? 'checked' : ''; ?>>
                        <label for="minify_js">
                            <strong>JavaScript Minifizierung</strong>
                            <div class="help-text">Komprimiert JavaScript-Dateien</div>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="defer_js" name="defer_js" <?php echo $currentSettings['perf_defer_js'] === '1' ? 'checked' : ''; ?>>
                        <label for="defer_js">
                            <strong>JavaScript verzögern (Defer)</strong>
                            <div class="help-text">Lädt JavaScript erst nach dem Seitenaufbau</div>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="async_css" name="async_css" <?php echo $currentSettings['perf_async_css'] === '1' ? 'checked' : ''; ?>>
                        <label for="async_css">
                            <strong>CSS asynchron laden</strong>
                            <div class="help-text">Verhindert Render-Blocking durch CSS</div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Images & Fonts -->
            <div class="perf-section">
                <h3>Bilder & Schriftarten</h3>
                <div class="form-grid">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_lazy_loading" name="enable_lazy_loading" <?php echo $currentSettings['perf_enable_lazy_loading'] === '1' ? 'checked' : ''; ?>>
                        <label for="enable_lazy_loading">
                            <strong>Lazy Loading für Bilder</strong>
                            <div class="help-text">Bilder werden erst beim Scrollen geladen</div>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_preload_fonts" name="enable_preload_fonts" <?php echo $currentSettings['perf_enable_preload_fonts'] === '1' ? 'checked' : ''; ?>>
                        <label for="enable_preload_fonts">
                            <strong>Webfonts vorladen</strong>
                            <div class="help-text">Reduziert Flash of Unstyled Text (FOUT)</div>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="preload_critical_css" name="preload_critical_css" <?php echo $currentSettings['perf_preload_critical_css'] === '1' ? 'checked' : ''; ?>>
                        <label for="preload_critical_css">
                            <strong>Critical CSS vorladen</strong>
                            <div class="help-text">Lädt wichtige Styles inline für schnelleres Rendering</div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Caching -->
            <div class="perf-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>Caching <span class="performance-badge">KRITISCH</span></h3>
                    <button type="submit" name="action" value="clear_cache" class="btn-save" style="padding: 0.5rem 1rem; font-size: 0.875rem; background: #ef4444;">
                        🗑️ Cache leeren
                    </button>
                </div>
                <div class="form-grid">
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_browser_cache" name="enable_browser_cache" <?php echo $currentSettings['perf_enable_browser_cache'] === '1' ? 'checked' : ''; ?>>
                        <label for="enable_browser_cache">
                            <strong>Browser-Caching aktivieren</strong>
                            <div class="help-text">Speichert Assets im Browser-Cache</div>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="cache_duration">Cache-Dauer (Sekunden)</label>
                        <input type="number" id="cache_duration" name="cache_duration" value="<?php echo htmlspecialchars($currentSettings['perf_cache_duration'] ?: '86400'); ?>" min="0" step="3600">
                        <div class="help-text">Standard: 86400 (24 Stunden)</div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="enable_gzip" name="enable_gzip" <?php echo $currentSettings['perf_enable_gzip'] === '1' ? 'checked' : ''; ?>>
                        <label for="enable_gzip">
                            <strong>GZip Kompression</strong>
                            <div class="help-text">Komprimiert übertragene Daten</div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Database & Cleanup -->
            <div class="perf-section">
                <h3>Datenbank & Aufräumen</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="limit_revisions">Maximale Revisionen pro Seite</label>
                        <input type="number" id="limit_revisions" name="limit_revisions" value="<?php echo htmlspecialchars($currentSettings['perf_limit_revisions'] ?: '5'); ?>" min="0" max="100">
                        <div class="help-text">0 = unbegrenzt (nicht empfohlen)</div>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="disable_emojis" name="disable_emojis" <?php echo $currentSettings['perf_disable_emojis'] === '1' ? 'checked' : ''; ?>>
                        <label for="disable_emojis">
                            <strong>Emoji-Scripts deaktivieren</strong>
                            <div class="help-text">Entfernt unnötige Emoji-Detection Scripts</div>
                        </label>
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
