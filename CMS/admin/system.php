<?php
declare(strict_types=1);

use CMS\Auth;
use CMS\Security;
use CMS\Services\SystemService;

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

// Check authentication
$auth = Auth::instance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

$systemService = SystemService::instance();
$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'system_management')) {
        $error = 'Sicherheitsüberprüfung fehlgeschlagen';
    } else {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'clear_cache':
                if ($systemService->clearCache()) {
                    $message = 'Cache erfolgreich geleert';
                } else {
                    $error = 'Cache konnte nicht geleert werden';
                }
                break;
                
            case 'clear_sessions':
                if ($systemService->clearOldSessions()) {
                    $message = 'Alte Sitzungen erfolgreich gelöscht';
                } else {
                    $error = 'Fehler beim Löschen der Sitzungen';
                }
                break;
                
            case 'clear_failed_logins':
                if ($systemService->clearOldFailedLogins()) {
                    $message = 'Fehlgeschlagene Logins erfolgreich gelöscht';
                } else {
                    $error = 'Fehler beim Löschen der Login-Versuche';
                }
                break;
                
            case 'repair_tables':
                $results = $systemService->repairTables();
                $success_count = count(array_filter($results, fn($r) => $r['success']));
                $message = "{$success_count} von " . count($results) . " Tabellen erfolgreich repariert";
                break;
                
            case 'optimize_tables':
                $results = $systemService->optimizeTables();
                $success_count = count(array_filter($results, fn($r) => $r['success']));
                $message = "{$success_count} von " . count($results) . " Tabellen erfolgreich optimiert";
                break;
                
            case 'clear_logs':
                if ($systemService->clearErrorLogs()) {
                    $message = 'Fehler-Logs erfolgreich geleert';
                } else {
                    $error = 'Logs konnten nicht geleert werden';
                }
                break;
                
            case 'create_missing_tables':
                try {
                    $db = \CMS\Database::instance();
                    $db->repairTables();
                    $message = 'Fehlende Tabellen wurden erfolgreich erstellt';
                } catch (\Exception $e) {
                    $error = 'Fehler beim Erstellen der Tabellen: ' . $e->getMessage();
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        if ($message || $error) {
            $_SESSION['system_message'] = $message;
            $_SESSION['system_error'] = $error;
            // Verwende relativen Pfad ohne Query-Parameter
            $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
            header('Location: ' . $redirect_url);
            exit;
        }
    }
}

// Get session messages
if (isset($_SESSION['system_message'])) {
    $message = $_SESSION['system_message'];
    unset($_SESSION['system_message']);
}
if (isset($_SESSION['system_error'])) {
    $error = $_SESSION['system_error'];
    unset($_SESSION['system_error']);
}

// Get all system data
$systemInfo = $systemService->getSystemInfo();
$databaseStatus = $systemService->getDatabaseStatus();
$tableStatus = $systemService->checkDatabaseTables();
$filePermissions = $systemService->checkFilePermissions();
$directorySizes = $systemService->getDirectorySizes();
$cmsStats = $systemService->getCMSStatistics();
$securityStatus = $systemService->getSecurityStatus();
$errorLogs = $systemService->getErrorLogs(50);

$csrf_token = Security::instance()->generateToken('system_management');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System & Diagnose - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo CMS_VERSION; ?>">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('system'); ?>
    
    <main class="admin-content">
        <div class="admin-page-header">
            <h2>System & Diagnose</h2>
            <div class="header-actions">
                <span class="status-indicator <?php echo $databaseStatus['connected'] ? 'status-online' : 'status-offline'; ?>">
                    <?php echo $databaseStatus['connected'] ? '● Online' : '● Offline'; ?>
                </span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="overview">Übersicht</button>
            <button class="tab-btn" data-tab="database">Datenbank</button>
            <button class="tab-btn" data-tab="files">Dateisystem</button>
            <button class="tab-btn" data-tab="security">Sicherheit</button>
            <button class="tab-btn" data-tab="tools">Tools</button>
            <button class="tab-btn" data-tab="logs">Logs</button>
        </div>
        
        <!-- Overview Tab -->
        <div class="tab-content active" id="tab-overview">
            <div class="system-grid">
                <!-- System Info Card -->
                <div class="system-card">
                    <h2>📊 System-Informationen</h2>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo $systemInfo['php_version']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">MySQL Version:</span>
                            <span class="info-value"><?php echo $systemInfo['mysql_version']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Server:</span>
                            <span class="info-value"><?php echo $systemInfo['server_software']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Betriebssystem:</span>
                            <span class="info-value"><?php echo $systemInfo['os']; ?> (<?php echo $systemInfo['architecture']; ?>)</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Memory Limit:</span>
                            <span class="info-value"><?php echo $systemInfo['memory_limit']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Max Execution Time:</span>
                            <span class="info-value"><?php echo $systemInfo['max_execution_time']; ?>s</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Upload Max:</span>
                            <span class="info-value"><?php echo $systemInfo['upload_max_filesize']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">POST Max:</span>
                            <span class="info-value"><?php echo $systemInfo['post_max_size']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Database Status Card -->
                <div class="system-card">
                    <h2>💾 Datenbank-Status</h2>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Verbindung:</span>
                            <span class="info-value status-badge <?php echo $databaseStatus['connected'] ? 'status-active' : 'status-banned'; ?>">
                                <?php echo $databaseStatus['connected'] ? 'Aktiv' : 'Fehler'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Datenbank:</span>
                            <span class="info-value"><?php echo $databaseStatus['database_name']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gesamt Tabellen:</span>
                            <span class="info-value"><?php echo $databaseStatus['total_tables']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">CMS Tabellen:</span>
                            <span class="info-value"><?php echo $databaseStatus['cms_tables']; ?> / 22</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Datenbank-Größe:</span>
                            <span class="info-value"><?php echo $systemService->formatBytes($databaseStatus['database_size']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- CMS Statistics Card -->
                <div class="system-card">
                    <h2>📈 CMS-Statistiken</h2>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">Benutzer (Gesamt):</span>
                            <span class="info-value"><?php echo $cmsStats['total_users']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Aktive Benutzer:</span>
                            <span class="info-value"><?php echo $cmsStats['active_users']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Seiten:</span>
                            <span class="info-value"><?php echo $cmsStats['total_pages']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Aktive Sitzungen:</span>
                            <span class="info-value"><?php echo $cmsStats['total_sessions']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cache-Einträge:</span>
                            <span class="info-value"><?php echo $cmsStats['cache_entries']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fehllogins (Heute):</span>
                            <span class="info-value"><?php echo $cmsStats['failed_logins_today']; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Directory Sizes Card -->
                <div class="system-card">
                    <h2>📁 Verzeichnis-Größen</h2>
                    <div class="info-list">
                        <?php foreach ($directorySizes as $dir): ?>
                        <div class="info-row">
                            <span class="info-label"><?php echo ucfirst($dir['path']); ?>:</span>
                            <span class="info-value"><?php echo $dir['formatted']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Database Tab -->
        <div class="tab-content" id="tab-database">
            <div class="table-container">
                <h2>Datenbank-Tabellen</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tabelle</th>
                            <th>Status</th>
                            <th>Einträge</th>
                            <th>Größe</th>
                            <th>Prüfung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tableStatus as $table): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($table['label']); ?></strong><br>
                                <small class="text-muted"><?php echo DB_PREFIX . $table['name']; ?></small>
                            </td>
                            <td>
                                <?php if ($table['exists']): ?>
                                    <span class="status-badge status-active">Vorhanden</span>
                                <?php else: ?>
                                    <span class="status-badge status-banned">Fehlt</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($table['rows'], 0, ',', '.'); ?></td>
                            <td><?php echo $systemService->formatBytes($table['size']); ?></td>
                            <td>
                                <?php if ($table['status'] === 'OK'): ?>
                                    <span class="status-badge status-active">OK</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive"><?php echo htmlspecialchars($table['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Files Tab -->
        <div class="tab-content" id="tab-files">
            <div class="table-container">
                <h2>Datei-Berechtigungen</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pfad</th>
                            <th>Status</th>
                            <th>Lesbar</th>
                            <th>Schreibbar</th>
                            <th>Berechtigungen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filePermissions as $perm): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($perm['path']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($perm['full_path']); ?></small>
                            </td>
                            <td>
                                <?php 
                                $statusClass = 'status-inactive';
                                if ($perm['status'] === 'OK') $statusClass = 'status-active';
                                elseif ($perm['status'] === 'Missing') $statusClass = 'status-banned';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($perm['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($perm['readable']): ?>
                                    <span class="status-icon">✓</span>
                                <?php else: ?>
                                    <span class="status-icon error">✗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($perm['writable']): ?>
                                    <span class="status-icon">✓</span>
                                <?php else: ?>
                                    <span class="status-icon error">✗</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($perm['permissions'] ?? 'N/A'); ?></code>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Security Tab -->
        <div class="tab-content" id="tab-security">
            <div class="system-card">
                <h2>🔒 Sicherheits-Status</h2>
                <div class="info-list">
                    <?php foreach ($securityStatus as $key => $value): ?>
                    <div class="info-row">
                        <span class="info-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                        <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="security-warnings">
                    <h3>Empfehlungen</h3>
                    <ul>
                        <?php if (ini_get('display_errors')): ?>
                        <li class="warning">⚠️ Display Errors sollte in Produktion deaktiviert sein</li>
                        <?php endif; ?>
                        
                        <?php if (!ini_get('session.cookie_secure')): ?>
                        <li class="warning">⚠️ Session Cookie Secure sollte aktiviert sein (HTTPS erforderlich)</li>
                        <?php endif; ?>
                        
                        <?php if (!ini_get('session.cookie_httponly')): ?>
                        <li class="warning">⚠️ Session Cookie HTTPOnly sollte aktiviert sein</li>
                        <?php endif; ?>
                        
                        <?php if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off'): ?>
                        <li class="warning">⚠️ HTTPS wird empfohlen für sichere Verbindungen</li>
                        <?php endif; ?>
                        
                        <?php if (defined('DEBUG') && DEBUG): ?>
                        <li class="error">🔴 Debug-Modus ist aktiv - NUR in Entwicklung verwenden!</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Tools Tab -->
        <div class="tab-content" id="tab-tools">
            <div class="tools-grid">
                <div class="tool-card">
                    <h3>🗑️ Cache leeren</h3>
                    <p>Löscht alle Cache-Einträge aus der Datenbank und dem Cache-Verzeichnis.</p>
                    <form method="POST" onsubmit="return confirm('Cache wirklich leeren?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="btn btn-primary">Cache leeren</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>🔄 Alte Sitzungen löschen</h3>
                    <p>Entfernt abgelaufene Sitzungen aus der Datenbank.</p>
                    <form method="POST" onsubmit="return confirm('Alte Sitzungen löschen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="clear_sessions">
                        <button type="submit" class="btn btn-primary">Sitzungen löschen</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>🚫 Fehllogins löschen</h3>
                    <p>Löscht fehlgeschlagene Login-Versuche älter als 24 Stunden.</p>
                    <form method="POST" onsubmit="return confirm('Fehlgeschlagene Logins löschen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="clear_failed_logins">
                        <button type="submit" class="btn btn-primary">Fehllogins löschen</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>🔧 Tabellen reparieren</h3>
                    <p>Führt REPAIR TABLE auf allen CMS-Tabellen aus.</p>
                    <form method="POST" onsubmit="return confirm('Tabellen reparieren? Dies kann einige Zeit dauern.');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="repair_tables">
                        <button type="submit" class="btn btn-warning">Tabellen reparieren</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>⚡ Tabellen optimieren</h3>
                    <p>Führt OPTIMIZE TABLE auf allen CMS-Tabellen aus.</p>
                    <form method="POST" onsubmit="return confirm('Tabellen optimieren? Dies kann einige Zeit dauern.');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="optimize_tables">
                        <button type="submit" class="btn btn-warning">Tabellen optimieren</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>📋 Logs leeren</h3>
                    <p>Löscht alle Einträge aus der Fehler-Log-Datei.</p>
                    <form method="POST" onsubmit="return confirm('Logs wirklich löschen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn btn-danger">Logs leeren</button>
                    </form>
                </div>
                
                <div class="tool-card">
                    <h3>🔨 Fehlende Tabellen erstellen</h3>
                    <p>Erstellt alle fehlenden CMS-Tabellen in der Datenbank.</p>
                    <form method="POST" onsubmit="return confirm('Fehlende Tabellen jetzt erstellen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="create_missing_tables">
                        <button type="submit" class="btn btn-primary">Tabellen erstellen</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Logs Tab -->
        <div class="tab-content" id="tab-logs">
            <div class="logs-container">
                <h2>Fehler-Logs (letzte 50 Einträge)</h2>
                <?php if (empty($errorLogs)): ?>
                    <p class="text-muted">Keine Fehler-Logs gefunden.</p>
                <?php else: ?>
                <div class="logs-list">
                    <?php foreach ($errorLogs as $log): ?>
                    <div class="log-entry log-type-<?php echo strtolower($log['type']); ?>">
                        <div class="log-header">
                            <span class="log-type"><?php echo htmlspecialchars($log['type']); ?></span>
                            <span class="log-timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></span>
                        </div>
                        <div class="log-message">
                            <?php echo htmlspecialchars($log['message']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Update buttons
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update content
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById('tab-' + tabName).classList.add('active');
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
