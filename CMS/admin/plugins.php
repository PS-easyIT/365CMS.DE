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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=202602">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('plugins'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <h1>Plugin-Verwaltung</h1>
            <p>Verwalten Sie Ihre Plugins - Aktivieren, Deaktivieren, Löschen oder Neue installieren</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($plugins); ?></div>
                <div class="stat-label">Installierte Plugins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_filter($plugins, fn($p) => $p['active'])); ?></div>
                <div class="stat-label">Aktive Plugins</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count(array_filter($plugins, fn($p) => !$p['active'])); ?></div>
                <div class="stat-label">Inaktive Plugins</div>
            </div>
        </div>
        
        <!-- Upload Section -->
        <div class="upload-section">
            <h3 style="margin: 0 0 1rem 0;">Neues Plugin installieren</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="upload">
                
                <div class="upload-area">
                    <div class="upload-icon">📦</div>
                    <p style="margin: 0 0 1rem 0; font-weight: 500;">Plugin als ZIP-Datei hochladen</p>
                    <input type="file" name="plugin_file" accept=".zip" required style="margin-bottom: 1rem;">
                    <p style="color: #64748b; font-size: 0.875rem; margin: 1rem 0 0 0;">
                        Maximale Dateigröße: 50MB | Format: ZIP
                    </p>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="submit" class="btn-save">
                        📤 Plugin hochladen & installieren
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Plugins List -->
        <h2 style="margin-bottom: 1rem;">Installierte Plugins</h2>
        
        <?php if (empty($plugins)): ?>
            <div class="empty-state">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔌</div>
                <p style="color: #64748b; font-size: 1.125rem; margin: 0 0 0.5rem 0; font-weight: 500;">Noch keine Plugins installiert</p>
                <p style="color: #94a3b8; margin: 0;">Laden Sie Ihr erstes Plugin hoch, um zu beginnen.</p>
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
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="deactivate">
                                    <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($folder); ?>">
                                    <button type="submit" class="btn-plugin btn-deactivate">
                                        ⏸ Deaktivieren
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="activate">
                                    <input type="hidden" name="plugin" value="<?php echo htmlspecialchars($folder); ?>">
                                    <button type="submit" class="btn-plugin btn-activate">
                                        ▶ Aktivieren
                                    </button>
                                </form>
                                
                                <button type="button" 
                                        class="btn-plugin btn-delete" 
                                        onclick="showDeleteModal('<?php echo htmlspecialchars($folder); ?>', '<?php echo htmlspecialchars($plugin['name'] ?? $folder); ?>')">
                                    🗑 Löschen
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="delete-modal">
        <div class="delete-modal-content">
            <h3 style="margin: 0 0 1rem 0; color: #dc2626;">Plugin löschen</h3>
            <p style="margin-bottom: 1rem;">
                Möchten Sie das Plugin <strong id="pluginNameToDelete"></strong> wirklich löschen?
            </p>
            <p style="color: #ef4444; font-weight: 500; margin-bottom: 1rem;">
                ⚠️ Diese Aktion kann nicht rückgängig gemacht werden!
            </p>
            
            <form method="post" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="plugin" id="pluginFolderToDelete" value="">
                
                <label for="confirmDelete" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                    Zum Bestätigen "DELETE" eingeben:
                </label>
                <input type="text" 
                       name="confirm_delete" 
                       id="confirmDelete" 
                       class="delete-confirm-input"
                       placeholder="DELETE"
                       autocomplete="off">
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" 
                            onclick="closeDeleteModal()" 
                            style="flex: 1; padding: 0.75rem; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer;">
                        Abbrechen
                    </button>
                    <button type="submit" 
                            class="btn-plugin btn-delete" 
                            style="flex: 1; padding: 0.75rem;">
                        Plugin löschen
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showDeleteModal(folder, name) {
            document.getElementById('pluginFolderToDelete').value = folder;
            document.getElementById('pluginNameToDelete').textContent = name;
            document.getElementById('confirmDelete').value = '';
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }
        
        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
        
        // Close modal on background click
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
    
</body>
</html>
