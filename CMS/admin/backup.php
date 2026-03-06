<?php
/**
 * Backup Admin Page
 * 
 * Datensicherung und -Wiederherstellung
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
use CMS\Services\BackupService;

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
$backupService = BackupService::getInstance();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'backup')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create_full_backup':
                set_time_limit(300); // 5 Minuten für große Backups
                $result = $backupService->createFullBackup();
                
                if ($result['success']) {
                    $message = 'Vollständiges Backup erfolgreich erstellt: ' . $result['name'];
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Erstellen des Backups: ' . ($result['error'] ?? 'Unbekannter Fehler');
                    $messageType = 'error';
                }
                break;
                
            case 'create_db_backup':
                set_time_limit(120);
                try {
                    $filename = $backupService->createDatabaseBackup();
                    $message = 'Datenbank-Backup erfolgreich erstellt: ' . $filename;
                    $messageType = 'success';
                } catch (\Exception $e) {
                    $message = 'Fehler beim Erstellen des DB-Backups: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'email_backup':
                $email = $_POST['email'] ?? ADMIN_EMAIL;
                $sent = $backupService->emailDatabaseBackup($email);
                
                if ($sent) {
                    $message = 'Datenbank-Backup wurde an ' . htmlspecialchars($email) . ' versendet';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Versenden des Backups';
                    $messageType = 'error';
                }
                break;
                
            case 'delete_backup':
                $backupName = basename($_POST['backup_name'] ?? '');
                if (empty($backupName) || preg_match('/[\.]{2}|[\/\\\\]/', $backupName)) {
                    $message = 'Ungültiger Backup-Name';
                    $messageType = 'error';
                } elseif ($backupService->deleteBackup($backupName)) {
                    $message = 'Backup erfolgreich gelöscht';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Löschen des Backups';
                    $messageType = 'error';
                }
                break;

            case 'upload_backup':
                if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['backup_file'];
                    $safeName = basename($file['name']);
                    $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, ['zip', 'sql', 'gz'])) {
                        $backupName = 'import_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', pathinfo($safeName, PATHINFO_FILENAME)) . '_' . date('YmdHis');
                        $targetDir = ABSPATH . 'backups/' . $backupName;
                        
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        
                        $targetFile = $targetDir . '/' . $safeName;
                        
                        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                            // Create manifest so it appears in the list
                            $manifest = [
                                'timestamp' => time(),
                                'date' => date('Y-m-d_H-i-s'),
                                'type' => 'import',
                                'files' => $safeName,
                                'size' => filesize($targetFile),
                                'cms_version' => 'external'
                            ];
                            file_put_contents($targetDir . '/manifest.json', json_encode($manifest));
                            
                            $message = 'Backup erfolgreich hochgeladen.';
                            $messageType = 'success';
                        } else {
                            $message = 'Fehler beim Verschieben der Datei.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Ungültiges Dateiformat. Erlaubt: .zip, .sql, .gz';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Keine Datei ausgewählt oder Upload-Fehler.';
                    $messageType = 'error';
                }
                break;

            case 'restore_backup':
                $backupName = basename($_POST['backup_name'] ?? '');
                if (empty($backupName) || preg_match('/[\.]{2}|[\/\\\\]/', $backupName)) {
                    $message = 'Ungültiger Backup-Name';
                    $messageType = 'error';
                } else {
                    // Placeholder for restore logic
                    $message = 'Wiederherstellungs-Funktion ist in dieser Version noch nicht vollständig implementiert. Bitte manuell wiederherstellen.';
                    $messageType = 'info';
                }
                break;
        }
    }
}

// Get backup list and history
$backups = $backupService->listBackups();
$history = $backupService->getBackupHistory(20);

// Generate CSRF token
$csrfToken = $security->generateToken('backup');

// Determine current page for menu
$currentPage = 'backup';

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<?php renderAdminLayoutStart('Backup & Restore', $currentPage); ?>
        
                <div class="page-header d-print-none mb-3">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="page-pretitle">Datensicherung und -Wiederherstellung</div>
                    <h2 class="page-title">💾 Backup & Restore</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-secondary" onclick="location.reload()">🔄 Liste aktualisieren</button>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="backup-layout" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
            
            <!-- Left Column: Create Backups -->
            <div class="card">
                <h3>Backup erstellen</h3>
                <p style="color:#64748b; margin-bottom:1.5rem;">Erstellen Sie hier Sicherungen Ihrer Datenbank und Dateien.</p>

                <!-- Full Backup -->
                <div class="backup-option" style="border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; margin-bottom:1.25rem;">
                    <div style="display:flex; align-items:flex-start; margin-bottom:1rem;">
                        <span style="font-size:1.5rem; margin-right:1rem;">🗄️</span>
                        <div>
                            <h4 style="margin:0 0 0.25rem 0;">Vollständiges Backup</h4>
                            <p style="margin:0; font-size:0.875rem; color:#64748b;">Sichert alle Datenbank-Tabellen und Dateien (Uploads, Themes, Plugins).</p>
                        </div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="create_full_backup">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            ⬇️ Vollbackup jetzt erstellen
                        </button>
                    </form>
                </div>
                
                <!-- Database Backup -->
                <div class="backup-option" style="border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem; margin-bottom:1.25rem;">
                    <div style="display:flex; align-items:flex-start; margin-bottom:1rem;">
                        <span style="font-size:1.5rem; margin-right:1rem;">🗃️</span>
                        <div>
                            <h4 style="margin:0 0 0.25rem 0;">Nur Datenbank</h4>
                            <p style="margin:0; font-size:0.875rem; color:#64748b;">Exportiert nur die Datenbank als SQL-Dump (.sql). Schneller.</p>
                        </div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="create_db_backup">
                        <button type="submit" class="btn btn-secondary" style="width:100%">
                            ⬇️ SQL-Dump erstellen
                        </button>
                    </form>
                </div>

                <!-- Email Backup -->
                <div class="backup-option" style="border:1px solid #e2e8f0; border-radius:8px; padding:1.25rem;">
                    <div style="display:flex; align-items:flex-start; margin-bottom:1rem;">
                        <span style="font-size:1.5rem; margin-right:1rem;">📧</span>
                        <div>
                            <h4 style="margin:0 0 0.25rem 0;">Backup per E-Mail</h4>
                            <p style="margin:0; font-size:0.875rem; color:#64748b;">Sendet das DB-Backup an Ihre Adresse.</p>
                        </div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="email_backup">
                        <div class="form-group" style="margin-bottom:0.75rem;">
                            <input type="email" name="email" value="<?php echo htmlspecialchars(ADMIN_EMAIL); ?>" class="form-control" placeholder="E-Mail Adresse" required>
                        </div>
                        <button type="submit" class="btn btn-secondary" style="width:100%;">
                            📤 An E-Mail senden
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Restore & Manage -->
            <div class="card">
                <h3>Restore & Verwaltung</h3>
                
                <!-- Upload Section -->
                <div class="upload-section" style="background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; padding:2rem; text-align:center; margin-bottom:2rem; cursor:pointer;" onclick="document.getElementById('backup_file_input').click()">
                    <span style="font-size:2.5rem; display:block; margin-bottom:0.5rem;">📂</span>
                    <p style="margin:0; color:#475569; font-weight:500;">Datei hier ablegen oder klicken</p>
                    <p style="margin:0; color:#94a3b8; font-size:0.85rem;">.zip, .sql, .gz</p>
                    
                    <form method="post" enctype="multipart/form-data" id="upload_form">
                         <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="upload_backup">
                        <input type="file" id="backup_file_input" name="backup_file" accept=".zip,.sql,.gz" style="display:none;" onchange="document.getElementById('upload_form').submit()">
                    </form>
                </div>

                <!-- Existing Backups List -->
                <h4 style="margin-bottom:1rem;">Verfügbare Backups</h4>
                
                <?php if (empty($backups)): ?>
                    <div class="empty-state" style="text-align:center; padding:2rem; background:#f8fafc; border-radius:8px;">
                        <p style="font-size:2rem; margin:0;">📭</p>
                        <p style="color:#64748b;">Keine Backups gefunden.</p>
                    </div>
                <?php else: ?>
                    <div class="card">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Größe</th>
                                    <th style="text-align:right;">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <strong style="display:block; font-size:0.9rem;"><?php echo htmlspecialchars($backup['name']); ?></strong>
                                        <span style="font-size:0.8rem; color:#64748b;"><?php echo date('d.m.Y H:i', $backup['timestamp']); ?></span>
                                    </td>
                                    <td><?php echo $backup['size_formatted'] ?? '0 B'; ?></td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                            <!-- Download -->
                                            <a href="?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn btn-sm btn-secondary" title="Herunterladen">⬇️</a>
                                            
                                            <!-- Restore -->
                                            <form method="post" class="js-needs-confirm"
                                                  data-msg="&#x26A0;&#xFE0F; WARNUNG: Dies überschreibt die gesamte Datenbank und alle Dateien. Wirklich wiederherstellen?">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="restore_backup">
                                                <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Wiederherstellen">↺</button>
                                            </form>

                                            <!-- Delete -->
                                            <form method="post" class="js-needs-confirm"
                                                  data-msg="Backup wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Löschen">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>

    <!-- .js-needs-confirm → globales cmsConfirm()-Modal aus admin-menu.php -->
    <script>
    (function(){
        document.querySelectorAll('.js-needs-confirm').forEach(function(form){
            form.addEventListener('submit', function(e){
                e.preventDefault();
                var f = form;
                cmsConfirm(f.dataset.msg || 'Wirklich fortfahren?', function(){ f.submit(); });
            });
        });
    })();
    </script>
<?php renderAdminLayoutEnd(); ?>

