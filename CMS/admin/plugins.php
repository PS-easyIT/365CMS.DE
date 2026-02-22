<?php
/**
 * Plugin Management Admin Page
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
use CMS\PluginManager;

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
$pluginManager = PluginManager::instance();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'plugin_management')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'activate':
                $plugin = $_POST['plugin'] ?? '';
                $result = $pluginManager->activatePlugin($plugin);
                
                if ($result === true) {
                    $message = 'Plugin erfolgreich aktiviert';
                    $messageType = 'success';
                } else {
                    $message = $result;
                    $messageType = 'error';
                }
                break;
                
            case 'deactivate':
                $plugin = $_POST['plugin'] ?? '';
                $result = $pluginManager->deactivatePlugin($plugin);
                
                if ($result === true) {
                    $message = 'Plugin erfolgreich deaktiviert';
                    $messageType = 'success';
                } else {
                    $message = $result;
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $plugin = $_POST['plugin'] ?? '';
                $confirm = $_POST['confirm_delete'] ?? '';
                
                if ($confirm !== 'DELETE') {
                    $message = 'Löschen abgebrochen. Bitte "DELETE" eingeben zur Bestätigung.';
                    $messageType = 'error';
                } else {
                    $result = $pluginManager->deletePlugin($plugin);
                    
                    if ($result === true) {
                        $message = 'Plugin erfolgreich gelöscht';
                        $messageType = 'success';
                    } else {
                        $message = $result;
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'upload':
                if (!isset($_FILES['plugin_file'])) {
                    $message = 'Keine Datei ausgewählt';
                    $messageType = 'error';
                } else {
                    $result = $pluginManager->installPlugin($_FILES['plugin_file']);
                    
                    if ($result === true) {
                        $message = 'Plugin erfolgreich hochgeladen und installiert';
                        $messageType = 'success';
                    } else {
                        $message = $result;
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Get all plugins
$plugins = $pluginManager->getAvailablePlugins();

// Generate CSRF token
$csrfToken = $security->generateToken('plugin_management');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plugin-Verwaltung - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('plugins'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-page-header">
            <div>
                <h2>🔌 Plugin-Verwaltung</h2>
                <p>Plugins aktivieren, deaktivieren, löschen oder neue installieren.</p>
            </div>
        </div>
        
        <?php if ($message && $messageType === 'success'): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php elseif ($message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Plugins</h3>
                <div class="stat-number"><?php echo count($plugins); ?></div>
                <div class="stat-label">Installierte Plugins</div>
            </div>
            <div class="stat-card">
                <h3>Aktiv</h3>
                <div class="stat-number"><?php echo count(array_filter($plugins, fn($p) => $p['active'])); ?></div>
                <div class="stat-label">Aktive Plugins</div>
            </div>
            <div class="stat-card">
                <h3>Inaktiv</h3>
                <div class="stat-number"><?php echo count(array_filter($plugins, fn($p) => !$p['active'])); ?></div>
                <div class="stat-label">Inaktive Plugins</div>
            </div>
        </div>
        
        <!-- Upload Section -->
        <div class="admin-card">
            <h3>📦 Neues Plugin installieren</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="upload">
                
                <div class="form-group">
                    <label class="form-label">ZIP-Datei auswählen <span style="color:#ef4444;">*</span></label>
                    <input type="file" name="plugin_file" accept=".zip" required class="form-control">
                    <small class="form-text">Maximale Dateigröße: 50 MB | Format: ZIP</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">📤 Plugin hochladen &amp; installieren</button>
                </div>
            </form>
        </div>
        
        <!-- Plugins List -->
        <div class="admin-card">
        <h3>🔌 Installierte Plugins</h3>
        
        <?php if (empty($plugins)): ?>
            <div class="empty-state">
                <p style="font-size:2.5rem;margin:0;">🔌</p>
                <p><strong>Noch keine Plugins installiert</strong></p>
                <p class="text-muted">Laden Sie Ihr erstes Plugin über das Formular oben hoch.</p>
            </div>
        <?php else: ?>
            <div class="plugin-list">
                <?php foreach ($plugins as $folder => $plugin): ?>
                    <div class="plugin-item <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
                        <!-- Status Badge -->
                        <div class="plugin-status-badge">
                            <span class="plugin-status <?php echo $plugin['active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $plugin['active'] ? '✓ Aktiv' : '○ Inaktiv'; ?>
                            </span>
                        </div>
                        
                        <!-- Plugin Info -->
                        <div class="plugin-info">
                            <div class="plugin-header">
                                <h3><?php echo htmlspecialchars($plugin['name'] ?? $folder); ?></h3>
                            </div>
                            
                            <div class="plugin-meta">
                                <?php if (!empty($plugin['version'])): ?>
                                    <span class="plugin-meta-item">
                                        <span>📌</span> 
                                        <span>Version <?php echo htmlspecialchars($plugin['version']); ?></span>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($plugin['author'])): ?>
                                    <span class="plugin-meta-item">
                                        <span>👤</span>
                                        <span><?php echo htmlspecialchars($plugin['author']); ?></span>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($plugin['requires'])): ?>
                                    <span class="plugin-meta-item">
                                        <span>⚙️</span>
                                        <span>Benötigt: <?php echo htmlspecialchars($plugin['requires']); ?></span>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="plugin-meta-item">
                                    <span>📁</span>
                                    <span><?php echo htmlspecialchars($folder); ?></span>
                                </span>
                            </div>
                            
                            <?php if (!empty($plugin['description'])): ?>
                                <p class="plugin-description">
                                    <?php echo htmlspecialchars($plugin['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="plugin-actions">
                            <?php if ($plugin['active']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($folder); ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">⏸ Deaktivieren</button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($folder); ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">▶ Aktivieren</button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm"
                                        onclick="showDeleteModal('<?php echo htmlspecialchars($folder, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($plugin['name'] ?? $folder, ENT_QUOTES); ?>')">🗑️ Löschen</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div><!-- /.admin-card -->
        
    </div><!-- /.admin-content -->
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🗑️ Plugin löschen</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>Möchten Sie das Plugin <strong id="pluginNameToDelete"></strong> wirklich löschen?</p>
                <div class="alert alert-error">⚠️ Diese Aktion kann nicht rückgängig gemacht werden!</div>
                
                <form method="post" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="plugin" id="pluginFolderToDelete" value="">
                    
                    <div class="form-group">
                        <label for="confirmDelete" class="form-label">
                            Zum Bestätigen <code>DELETE</code> eingeben: <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" name="confirm_delete" id="confirmDelete" class="form-control"
                               placeholder="DELETE" autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Abbrechen</button>
                <button type="submit" form="deleteForm" class="btn btn-danger">🗑️ Plugin löschen</button>
            </div>
        </div>
    </div>
    
    <script>
        function showDeleteModal(folder, name) {
            document.getElementById('pluginFolderToDelete').value = folder;
            document.getElementById('pluginNameToDelete').textContent = name;
            document.getElementById('confirmDelete').value = '';
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDeleteModal();
        });
        
        // Close modal on background click
        window.addEventListener('click', function(e) {
            const m = document.getElementById('deleteModal');
            if (e.target === m) closeDeleteModal();
        });
    </script>
    
</body>
</html>
