<?php
/**
 * Updates Admin Page
 * 
 * System-, Plugin- und Theme-Updates
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
use CMS\Services\UpdateService;

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
$updates = UpdateService::getInstance();

// H-19: AJAX-Handler für Update-Download mit SHA-256-Verifikation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // CSRF prüfen
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'updates')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sicherheitscheck fehlgeschlagen.']);
        exit;
    }

    $action = strip_tags(trim($_POST['action']));

    if ($action === 'download_update') {
        $downloadUrl  = filter_var($_POST['download_url'] ?? '', FILTER_VALIDATE_URL) ?: '';
        $sha256       = preg_replace('/[^0-9a-fA-F]/', '', $_POST['sha256'] ?? '');
        $type         = in_array($_POST['type'] ?? '', ['plugin', 'theme', 'core'], true)
                        ? $_POST['type']
                        : 'plugin';
        $name         = strip_tags(trim($_POST['name'] ?? 'Unbekannt'));
        $version      = preg_replace('/[^0-9a-zA-Z.\-_]/', '', $_POST['version'] ?? '');

        // Zielverzeichnis je Typ
        $targetDir = match($type) {
            'plugin' => PLUGIN_PATH . preg_replace('/[^a-z0-9\-_]/i', '', $_POST['slug'] ?? $name) . '/',
            'theme'  => THEME_PATH  . preg_replace('/[^a-z0-9\-_]/i', '', $_POST['slug'] ?? $name) . '/',
            default  => ABSPATH,
        };

        if (empty($downloadUrl)) {
            echo json_encode(['success' => false, 'message' => 'Keine gültige Download-URL angegeben.', 'sha256_verified' => false]);
            exit;
        }

        $result = $updates->downloadAndInstallUpdate($downloadUrl, $sha256, $targetDir, $type, $name, $version);
        echo json_encode($result);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unbekannte Aktion.']);
    exit;
}

// Get current tab
$activeTab = $_GET['tab'] ?? 'core';

// Get update data
$coreUpdate = $updates->checkCoreUpdates();
$pluginUpdates = $updates->checkPluginUpdates();
$themeUpdate = $updates->checkThemeUpdates();
$systemReqs = $updates->getSystemRequirements();
$updateHistory = $updates->getUpdateHistory(20);

// Generate CSRF token
$csrfToken = $security->generateToken('updates');

// Determine current page for menu
$currentPage = 'updates';

// Load admin menu (VOR DOCTYPE!)
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('System-Updates', $currentPage);
?>

        <div class="admin-page-header">
            <div>
                <h2>🔄 System-Updates</h2>
                <p>CMS-Core, Plugins und Themes aktuell halten.</p>
            </div>
        </div>
        
        <!-- Core Updates -->
        <div class="admin-card <?php echo $coreUpdate['update_available'] ? 'update-available' : ''; ?>">
            <div class="update-header">
                <div>
                    <h3 style="margin: 0 0 0.5rem 0;">365 CMS Core</h3>
                    <p style="color: #64748b; margin: 0;">
                        Version <?php echo htmlspecialchars($coreUpdate['current_version']); ?>
                        <?php if ($coreUpdate['update_available']): ?>
                            → <?php echo htmlspecialchars($coreUpdate['latest_version']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($coreUpdate['update_available']): ?>
                    <span class="update-badge badge-info">Update verfügbar</span>
                <?php else: ?>
                    <span class="update-badge badge-success">✓ Aktuell</span>
                <?php endif; ?>
            </div>
            
            <?php if ($coreUpdate['update_available']): ?>
                <div style="margin: 1.5rem 0;">
                    <strong>Neu in Version <?php echo htmlspecialchars($coreUpdate['latest_version']); ?>:</strong>
                    <?php if (!empty($coreUpdate['changelog'])): ?>
                        <ul class="changelog-list">
                            <?php foreach ($coreUpdate['changelog'] as $change): ?>
                                <li><?php echo htmlspecialchars($change); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php if (!empty($coreUpdate['release_notes'])): ?>
                        <details style="margin-top: 1rem;">
                            <summary style="cursor: pointer; color: #3b82f6;">Vollständige Release Notes anzeigen</summary>
                            <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; white-space: pre-wrap;">
                                <?php echo htmlspecialchars($coreUpdate['release_notes']); ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
                
                <div style="display:flex; gap:1rem; align-items:center;">
                    <button class="btn btn-primary" id="coreUpdateBtn"
                            onclick="startCoreUpdate(this)"
                            data-url="<?php echo htmlspecialchars($coreUpdate['download_url'] ?? '', ENT_QUOTES); ?>"
                            data-sha256="<?php echo htmlspecialchars($coreUpdate['sha256'] ?? '', ENT_QUOTES); ?>"
                            data-version="<?php echo htmlspecialchars($coreUpdate['latest_version'], ENT_QUOTES); ?>">
                        ⬇️ Auf Version <?php echo htmlspecialchars($coreUpdate['latest_version']); ?> aktualisieren
                    </button>
                    <?php if (!empty($coreUpdate['download_url'])): ?>
                        <a href="<?php echo htmlspecialchars($coreUpdate['download_url']); ?>" target="_blank" style="color: #64748b; text-decoration: none;">
                            📦 Download auf GitHub
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p style="color: #10b981; margin-top: 1rem;">
                    ✓ Sie verwenden die neueste Version des CMS. Es sind keine Updates erforderlich.
                </p>
            <?php endif; ?>
        </div>

        <div class="updates-grid-layout">
            <!-- System Requirements -->
            <div>
                <h3 style="margin-bottom:1rem;">⚙️ Systemanforderungen</h3>
                
                <div class="admin-card">
                    <h4 style="margin-top:0;">Environment</h4>
                    <div class="system-req-grid">
                        <div class="req-item <?php echo $systemReqs['php_version']['met'] ? 'met' : 'not-met'; ?>">
                            <strong>PHP Version</strong><br>
                            <?php echo $systemReqs['php_version']['current']; ?> (Min: <?php echo $systemReqs['php_version']['required']; ?>)
                        </div>
                        <div class="req-item <?php echo $systemReqs['mysql_version']['met'] ? 'met' : 'not-met'; ?>">
                            <strong>MySQL Version</strong><br>
                            <?php echo $systemReqs['mysql_version']['current']; ?> (Min: <?php echo $systemReqs['mysql_version']['required']; ?>)
                        </div>
                    </div>
                    
                    <h4 style="margin-top:1.5rem;">Extensions</h4>
                    <div class="system-req-grid">
                        <?php foreach ($systemReqs['extensions'] as $ext => $loaded): ?>
                            <div class="req-item <?php echo $loaded ? 'met' : 'not-met'; ?>">
                                <strong><?php echo strtoupper($ext); ?></strong><br>
                                <?php echo $loaded ? '✓ Installiert' : '✗ Fehlt'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (isset($systemReqs['permissions'])): ?>
                        <h4 style="margin-top:1.5rem;">Permissions</h4>
                        <div class="system-req-grid">
                            <?php foreach ($systemReqs['permissions'] as $dir => $writable): ?>
                                <div class="req-item <?php echo $writable ? 'met' : 'not-met'; ?>">
                                    <strong><?php echo str_replace('_', '/', ucfirst($dir)); ?></strong><br>
                                    <?php echo $writable ? '✓ Writable' : '✗ Read-only'; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Update History -->
            <div>
                <h3 style="margin-bottom:1rem;">📜 Verlauf</h3>
                <div class="admin-card">
                <?php if (!empty($updateHistory)): ?>
                    <div style="display:flex; flex-direction:column; gap:1rem;">
                        <?php foreach ($updateHistory as $entry): ?>
                            <div style="border-bottom:1px solid #f1f5f9; padding-bottom:1rem; last-child:border-bottom:none;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($entry['name'] ?? 'Unknown'); ?></strong>
                                        <span style="color: #64748b; margin-left: 0.5rem; background:#f1f5f9; padding:0.1rem 0.4rem; border-radius:4px; font-size:0.8rem;">
                                            v<?php echo htmlspecialchars($entry['version'] ?? ''); ?>
                                        </span>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="color: #64748b; font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($entry['timestamp'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($entry['user'] ?? 'System'); ?> • <?php echo htmlspecialchars($entry['type'] ?? ''); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #94a3b8; text-align: center; padding: 1rem;">
                        Noch keine Updates durchgeführt
                    </p>
                <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

    <!-- Update-Bestätigungsmodal -->
    <div id="updateConfirmModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>⬆️ CMS-Core aktualisieren</h3>
                <button class="modal-close" onclick="document.getElementById('updateConfirmModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie das CMS-Core jetzt aktualisieren?</p>
                <div class="alert alert-error">⚠️ Bitte erstellen Sie vorher ein Backup!</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('updateConfirmModal').style.display='none'">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="updateConfirmBtn" onclick="executeUpdate()">⬆️ Jetzt aktualisieren</button>
            </div>
        </div>
    </div>

    <script>
    let pendingUpdateBtn = null;

    function startCoreUpdate(btn) {
        pendingUpdateBtn = btn;
        document.getElementById('updateConfirmModal').style.display = 'flex';
    }

    async function executeUpdate() {
        document.getElementById('updateConfirmModal').style.display = 'none';
        const btn = pendingUpdateBtn;
        if (!btn) return;
        const url     = btn.dataset.url;
        const sha256  = btn.dataset.sha256;
        const version = btn.dataset.version;
        if (!url) { alert('Keine Download-URL vorhanden.'); return; }

        btn.disabled = true;
        btn.textContent = '⏳ Update wird heruntergeladen…';

        const fd = new FormData();
        fd.append('action', 'download_update');
        fd.append('csrf_token', '<?php echo $csrfToken; ?>');
        fd.append('download_url', url);
        fd.append('sha256', sha256);
        fd.append('type', 'core');
        fd.append('name', '365CMS');
        fd.append('version', version);

        try {
            const res  = await fetch(window.location.href, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                btn.textContent = '✅ Update erfolgreich!';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-secondary');
                setTimeout(() => location.reload(), 1500);
            } else {
                btn.textContent = '❌ Fehler: ' + (data.message || 'Unbekannt');
                btn.disabled = false;
                setTimeout(() => { btn.textContent = '⬇️ Erneut versuchen'; }, 3000);
            }
        } catch (e) {
            btn.textContent = '❌ Netzwerkfehler';
            btn.disabled = false;
        }
    }

    // Modal schließen via Klick außerhalb oder Escape
    document.getElementById('updateConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') document.getElementById('updateConfirmModal').style.display = 'none';
    });
    </script>
<?php renderAdminLayoutEnd(); ?>
