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
    <title>Backups - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .backup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .backup-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .backup-card h3 {
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.25rem;
        }
        
        .backup-card p {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        
        .backup-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .backup-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-item:hover {
            background: #f8fafc;
        }
        
        .backup-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .backup-meta {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .backup-size {
            color: #3b82f6;
            font-weight: 600;
        }
        
        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-download {
            background: #3b82f6;
            color: white;
        }
        
        .btn-download:hover {
            background: #2563eb;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .backup-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .backup-form .form-group {
            margin: 0;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-full {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-db {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-email {
            background: #fef3c7;
            color: #92400e;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar($currentPage); ?>
    
    <div class="admin-content">
        <div class="page-header">
            <h1>💾 Backup-Verwaltung</h1>
            <p style="color: #64748b;">Datensicherung und -Wiederherstellung</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Backup Actions -->
        <div class="backup-grid">
            <!-- Full Backup -->
            <div class="backup-card">
                <h3>
                    <span>🗄️</span>
                    Vollständiges Backup
                </h3>
                <p>Erstellt ein komplettes Backup aller Datenbank-Tabellen und Dateien (Uploads, Themes, Plugins)</p>
                <form method="post" class="backup-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="create_full_backup">
                    <button type="submit" class="btn btn-primary">
                        ⬇️ Vollbackup erstellen
                    </button>
                </form>
            </div>
            
            <!-- Database Backup -->
            <div class="backup-card">
                <h3>
                    <span>💾</span>
                    Datenbank-Backup
                </h3>
                <p>Erstellt ein komprimiertes Backup nur der Datenbank (schneller und kleiner)</p>
                <form method="post" class="backup-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="create_db_backup">
                    <button type="submit" class="btn btn-primary">
                        💾 DB-Backup erstellen
                    </button>
                </form>
            </div>
            
            <!-- Email Backup -->
            <div class="backup-card">
                <h3>
                    <span>📧</span>
                    E-Mail Backup
                </h3>
                <p>Versendet ein Datenbank-Backup per E-Mail (nur für kleinere Datenbanken geeignet)</p>
                <form method="post" class="backup-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="email_backup">
                    <div class="form-group">
                        <input type="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars(ADMIN_EMAIL); ?>" 
                               class="form-control"
                               placeholder="E-Mail Adresse">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        📧 Per E-Mail senden
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Backup List -->
        <h2 style="margin: 2rem 0 1rem 0;">📦 Gespeicherte Backups</h2>
        
        <?php if (empty($backups)): ?>
            <div class="backup-list">
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Keine Backups vorhanden</h3>
                    <p>Erstellen Sie Ihr erstes Backup mit den Optionen oben</p>
                </div>
            </div>
        <?php else: ?>
            <div class="backup-list">
                <?php foreach ($backups as $backup): ?>
                    <div class="backup-item">
                        <div>
                            <div class="backup-name">
                                <?php echo htmlspecialchars((string)$backup['name']); ?>
                                <?php if ($backup['type'] === 'full'): ?>
                                    <span class="badge badge-full">Vollbackup</span>
                                <?php else: ?>
                                    <span class="badge badge-db">Datenbank</span>
                                <?php endif; ?>
                            </div>
                            <div class="backup-meta">
                                <?php echo date('d.m.Y H:i', $backup['timestamp']); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="backup-size">
                                <?php 
                                $size = $backup['size'];
                                $units = ['B', 'KB', 'MB', 'GB'];
                                $i = 0;
                                while ($size >= 1024 && $i < count($units) - 1) {
                                    $size /= 1024;
                                    $i++;
                                }
                                echo round($size, 2) . ' ' . $units[$i];
                                ?>
                            </div>
                            <div class="backup-meta">
                                CMS v<?php echo htmlspecialchars((string)($backup['cms_version'] ?? 'N/A')); ?>
                            </div>
                        </div>
                        
                        <div>
                            <?php if ($backup['type'] === 'full'): ?>
                                <small style="color: #64748b;">
                                    DB: <?php echo basename($backup['database']); ?><br>
                                    Files: <?php echo basename($backup['files']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="backup-actions">
                            <a href="<?php echo SITE_URL; ?>/backups/<?php echo htmlspecialchars((string)$backup['name']); ?>" 
                               class="btn-action btn-download"
                               title="Download">
                                ⬇️
                            </a>
                            <form method="post" style="display: inline;" 
                                  onsubmit="return confirm('Backup wirklich löschen?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_backup">
                                <input type="hidden" name="backup_name" value="<?php echo htmlspecialchars((string)$backup['name']); ?>">
                                <button type="submit" class="btn-action btn-delete" title="Löschen">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Backup History -->
        <h2 style="margin: 2rem 0 1rem 0;">📜 Backup-Verlauf</h2>
        
        <?php if (empty($history)): ?>
            <div class="backup-list">
                <div class="empty-state">
                    <p>Kein Verlauf vorhanden</p>
                </div>
            </div>
        <?php else: ?>
            <div class="backup-list">
                <?php foreach ($history as $entry): ?>
                    <div class="backup-item" style="grid-template-columns: 2fr 1fr 1fr;">
                        <div>
                            <div class="backup-name">
                                <?php echo htmlspecialchars((string)($entry['name'] ?? '')); ?>
                                <?php if ($entry['type'] === 'full'): ?>
                                    <span class="badge badge-full">Vollbackup</span>
                                <?php elseif ($entry['type'] === 'email'): ?>
                                    <span class="badge badge-email">E-Mail</span>
                                <?php else: ?>
                                    <span class="badge badge-db">Datenbank</span>
                                <?php endif; ?>
                            </div>
                            <div class="backup-meta">
                                Erstellt von: <?php echo htmlspecialchars((string)($entry['user'] ?? 'System')); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="backup-meta">
                                <?php echo htmlspecialchars((string)($entry['timestamp'] ?? '')); ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="backup-size">
                                <?php echo htmlspecialchars((string)($entry['size_formatted'] ?? '')); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Info Box -->
        <div class="backup-card" style="margin-top: 2rem; background: #eff6ff; border-left: 4px solid #3b82f6;">
            <h3>ℹ️ Hinweise zu Backups</h3>
            <ul style="color: #475569; font-size: 0.875rem; margin: 1rem 0 0 1.5rem;">
                <li><strong>Vollbackup:</strong> Beinhaltet Datenbank + alle Dateien (Uploads, Themes, Plugins)</li>
                <li><strong>Datenbank-Backup:</strong> Nur SQL-Dump (komprimiert), ideal für regelmäßige Sicherungen</li>
                <li><strong>E-Mail-Backup:</strong> Nur für kleine Datenbanken geeignet (E-Mail-Größenlimit beachten)</li>
                <li><strong>Speicherort:</strong> <?php echo ABSPATH; ?>backups/</li>
                <li><strong>Empfehlung:</strong> Täglich automatische DB-Backups, wöchentlich Vollbackups</li>
                <li><strong>Sicherheit:</strong> .htaccess verhindert direkten Zugriff auf Backup-Dateien</li>
            </ul>
        </div>
    </div>
    
</body>
</html>
