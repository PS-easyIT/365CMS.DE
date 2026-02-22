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
renderAdminLayoutStart('Performance', 'performance');

$c   = fn(string $k) => ($currentSettings[$k] ?? '') === '1' ? 'checked' : '';
$num = fn(string $k, int $def) => (int)($currentSettings[$k] ?: $def);
?>

<div class="admin-page-header">
    <div>
        <h2>⚡ Performance Optimierung</h2>
        <p>Assets, Caching, Lazy Loading und Datenbank-Optimierung</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
<input type="hidden" name="action"     value="save_performance">

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:1.5rem;">

    <!-- Asset Optimierung -->
    <div class="admin-card">
        <h3>⚡ Asset-Optimierung <span class="status-badge active" style="font-size:.72rem;padding:.15rem .5rem;">Empfohlen</span></h3>

        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">CSS Minifizierung</label>
                <small class="form-text">Entfernt Leerzeichen und Kommentare aus CSS-Dateien</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="minify_css" <?php echo $c('perf_minify_css'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">JavaScript Minifizierung</label>
                <small class="form-text">Komprimiert JS-Dateien für schnelleren Transfer</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="minify_js" <?php echo $c('perf_minify_js'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">JavaScript verzögern (Defer)</label>
                <small class="form-text">Lädt JS erst nach dem Seitenaufbau → schnelleres FCP</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="defer_js" <?php echo $c('perf_defer_js'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">CSS asynchron laden</label>
                <small class="form-text">Verhindert Render-Blocking durch CSS</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="async_css" <?php echo $c('perf_async_css'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">Critical CSS vorladen</label>
                <small class="form-text">Inline-Styles für Above-the-Fold-Rendering</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="preload_critical_css" <?php echo $c('perf_preload_critical_css'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
    </div>

    <!-- Bilder & Fonts -->
    <div class="admin-card">
        <h3>🖼️ Bilder &amp; Schriftarten</h3>

        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">Lazy Loading für Bilder</label>
                <small class="form-text">Bilder werden erst beim Scrollen geladen → spart Bandbreite</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="enable_lazy_loading" <?php echo $c('perf_enable_lazy_loading'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">Webfonts vorladen</label>
                <small class="form-text">Reduziert Flash of Unstyled Text (FOUT)</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="enable_preload_fonts" <?php echo $c('perf_enable_preload_fonts'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">Emoji-Scripts deaktivieren</label>
                <small class="form-text">Entfernt unnötige Emoji-Detection Scripts aus dem &lt;head&gt;</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="disable_emojis" <?php echo $c('perf_disable_emojis'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
    </div>

    <!-- Caching -->
    <div class="admin-card">
        <h3>💾 Caching <span class="status-badge" style="font-size:.72rem;padding:.15rem .5rem;background:#fee2e2;color:#991b1b;">Kritisch</span></h3>

        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">Browser-Caching aktivieren</label>
                <small class="form-text">Speichert statische Assets im Browser-Cache</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="enable_browser_cache" <?php echo $c('perf_enable_browser_cache'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;">
            <div>
                <label class="form-label" style="margin:0;">GZip Kompression</label>
                <small class="form-text">Komprimiert HTTP-Antworten (spart 60–80 % Datengröße)</small>
            </div>
            <label class="dw-toggle"><input type="checkbox" name="enable_gzip" <?php echo $c('perf_enable_gzip'); ?>><span class="dw-toggle-slider"></span></label>
        </div>
        <div class="form-group" style="margin-top:1rem;">
            <label class="form-label" for="cache_duration">Cache-Dauer <small class="form-text" style="display:inline;">(in Sekunden)</small></label>
            <input type="number" id="cache_duration" name="cache_duration" class="form-control" style="max-width:160px;"
                value="<?php echo $num('perf_cache_duration', 86400); ?>" min="0" step="3600">
            <small class="form-text">Standard: 86400 = 24 Stunden</small>
        </div>
        <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #e2e8f0;">
            <button type="submit" name="action" value="clear_cache" class="btn btn-danger btn-sm">
                🗑️ Cache jetzt leeren
            </button>
            <small class="form-text" style="display:block;margin-top:.5rem;">Löscht DB-Cache und alle Cache-Dateien sofort.</small>
        </div>
    </div>

    <!-- Datenbank -->
    <div class="admin-card">
        <h3>🗄️ Datenbank &amp; Aufräumen</h3>

        <div class="form-group">
            <label class="form-label" for="limit_revisions">Maximale Revisionen pro Seite</label>
            <input type="number" id="limit_revisions" name="limit_revisions" class="form-control" style="max-width:120px;"
                value="<?php echo $num('perf_limit_revisions', 5); ?>" min="0" max="100">
            <small class="form-text">0 = unbegrenzt (nicht empfohlen – vergrößert die DB)</small>
        </div>
    </div>

</div><!-- /grid -->

<div class="admin-card form-actions-card" style="margin-top:1.5rem;">
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">💾 Performance-Einstellungen speichern</button>
    </div>
</div>
</form>

<?php renderAdminLayoutEnd(); ?>
