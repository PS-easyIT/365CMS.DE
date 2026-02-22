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
                $backupName = $_POST['backup_name'] ?? '';
                if ($backupName && $backupService->deleteBackup($backupName)) {
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
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($ext, ['zip', 'sql', 'gz'])) {
                        $backupName = 'import_' . pathinfo($file['name'], PATHINFO_FILENAME) . '_' . date('YmdHis');
                        $targetDir = ABSPATH . 'backups/' . $backupName;
                        
                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }
                        
                        $targetFile = $targetDir . '/' . $file['name'];
                        
                        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                            // Create manifest so it appears in the list
                            $manifest = [
                                'timestamp' => time(),
                                'date' => date('Y-m-d_H-i-s'),
                                'type' => 'import',
                                'files' => $file['name'],
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
                $backupName = $_POST['backup_name'] ?? '';
                // Placeholder for restore logic
                // In a real implementation, this would trigger BackupService::restoreBackup($backupName)
                $message = 'Wiederherstellungs-Funktion ist in dieser Version noch nicht vollständig implementiert. Bitte manuell wiederherstellen.';
                $messageType = 'info';
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
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup & Restore - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=202602">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar($currentPage); ?>
    
    <div class="admin-content">
        <div class="page-header">
            <h1>💾 Backup & Restore</h1>
            <p style="color: #64748b;">Datensicherung und -Wiederherstellung</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="backup-page-grid">
            
            <!-- Left Column: Create Backups -->
            <div class="backup-create-section">
                <div class="section-header">
                    <h2>Backup erstellen</h2>
                </div>

                <!-- Full Backup -->
                <div class="backup-card">
                    <h3><span>🗄️</span> Vollständiges Backup</h3>
                    <p>Sichert alle Datenbank-Tabellen und Dateien (Uploads, Themes, Plugins) in ein Archiv.</p>
                    <form method="post" class="backup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="create_full_backup">
                        <button type="submit" class="btn btn-primary" style="width:100%">
                            ⬇️ Vollbackup jetzt erstellen
                        </button>
                    </form>
                </div>
                
                <!-- Database Backup -->
                <div class="backup-card">
                    <h3><span>🗃️</span> Nur Datenbank</h3>
                    <p>Exportiert nur die Datenbank als SQL-Dump (.sql). Schneller, aber ohne Dateien.</p>
                    <form method="post" class="backup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="create_db_backup">
                        <button type="submit" class="btn btn-secondary" style="width:100%">
                            ⬇️ SQL-Dump erstellen
                        </button>
                    </form>
                </div>

                <!-- Email Backup -->
                <div class="backup-card">
                    <h3><span>📧</span> Backup per E-Mail</h3>
                    <p>Sendet das Datenbank-Backup direkt an Ihre E-Mail-Adresse.</p>
                    <form method="post" class="backup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="email_backup">
                        <div class="form-group">
                            <input type="email" name="email" value="<?php echo htmlspecialchars(ADMIN_EMAIL); ?>" class="form-control" placeholder="E-Mail Adresse" required>
                        </div>
                        <button type="submit" class="btn btn-secondary" style="width:100%; margin-top:0.5rem;">
                            📤 An E-Mail senden
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Restore & Manage -->
            <div class="backup-restore-section">
                <div class="section-header">
                    <h2>Restore & Verwaltung</h2>
                </div>

                <!-- Upload Section -->
                <div class="backup-card">
                    <h3><span>⬆️</span> Backup importieren</h3>
                    <p>Laden Sie ein bestehendes Backup (.zip oder .sql) hoch, um es wiederherzustellen.</p>
                    <form method="post" enctype="multipart/form-data" class="backup-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="upload_backup">
                        
                        <div class="upload-area">
                            <span style="font-size:2rem; display:block; margin-bottom:0.5rem;">📂</span>
                            <span style="color:#64748b; font-weight:500;">Datei hier ablegen oder klicken</span>
                            <input type="file" name="backup_file" accept=".zip,.sql,.gz" onchange="this.form.submit()">
                        </div>
                    </form>
                </div>

                <!-- Existing Backups List -->
                <h3 style="margin: 0 0 1rem 0; font-size:1.1rem; color:#334155;">Verfügbare Backups</h3>
                
                <?php if (empty($backups)): ?>
                    <div class="backup-card" style="text-align:center; padding:3rem;">
                        <span style="font-size:3rem; display:block; margin-bottom:1rem; opacity:0.5;">📭</span>
                        <p>Keine Backups gefunden.</p>
                    </div>
                <?php else: ?>
                    <div class="backup-list-container">
                        <?php foreach ($backups as $backup): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <span class="backup-name"><?php echo htmlspecialchars($backup['name']); ?></span>
                                    <div class="backup-meta">
                                        <span><?php echo date('d.m.Y H:i', $backup['timestamp']); ?></span>
                                        <span class="backup-size"><?php echo $backup['size_formatted'] ?? '0 B'; ?></span>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <!-- Download -->
                                    <a href="?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn-icon btn-download" title="Herunterladen">
                                        ⬇
                                    </a>
                                    
                                    <!-- Restore (Trigger only) -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('WARNUNG: Dies wird die aktuelle Datenbank/Dateien überschreiben! Fortfahren?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="restore_backup">
                                        <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <button type="submit" class="btn-icon btn-restore" title="Wiederherstellen">
                                            ↺
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Wirklich löschen?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="delete_backup">
                                        <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars($backup['name']); ?>">
                                        <button type="submit" class="btn-icon btn-delete" title="Löschen">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
    
</body>
</html>

