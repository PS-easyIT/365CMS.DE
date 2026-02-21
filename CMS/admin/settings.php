<?php
/**
 * Admin Settings
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
use CMS\Hooks;
use CMS\Database;
use CMS\Services\EditorService;

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
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'admin_settings')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        if ($_POST['action'] === 'save_settings') {
            try {
                // Save general settings
                $settings = [
                    'site_name' => $_POST['site_name'] ?? SITE_NAME,
                    'site_description' => $_POST['site_description'] ?? '',
                    'admin_email' => $_POST['admin_email'] ?? ADMIN_EMAIL,
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                    'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
                    'posts_per_page' => intval($_POST['posts_per_page'] ?? 10),
                    'timezone' => $_POST['timezone'] ?? 'Europe/Berlin',
                    'date_format' => $_POST['date_format'] ?? 'd.m.Y',
                    'time_format' => $_POST['time_format'] ?? 'H:i',
                ];
                
                // Save each setting to database
                foreach ($settings as $key => $value) {
                    $settingKey = 'setting_' . $key;
                    
                    // Check if setting exists
                    $existing = $db->execute(
                        "SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ?",
                        [$settingKey]
                    )->fetch();
                    
                    if ($existing) {
                        // Update existing setting
                        $db->execute(
                            "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
                            [$value, $settingKey]
                        );
                    } else {
                        // Insert new setting
                        $db->execute(
                            "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
                            [$settingKey, $value]
                        );
                    }
                }
                
                $message = 'Einstellungen erfolgreich gespeichert';
                $messageType = 'success';
                
            } catch (\Exception $e) {
                $message = 'Fehler beim Speichern: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Load current settings from database
$currentSettings = [];
$settingKeys = [
    'setting_site_name', 'setting_site_description', 'setting_admin_email',
    'setting_maintenance_mode', 'setting_allow_registration', 'setting_posts_per_page',
    'setting_timezone', 'setting_date_format', 'setting_time_format'
];

foreach ($settingKeys as $key) {
    $result = $db->execute(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    )->fetch();
    
    $currentSettings[$key] = $result ? $result->option_value : '';
}

// Set defaults if not in database
if (empty($currentSettings['setting_site_name'])) {
    $currentSettings['setting_site_name'] = SITE_NAME;
}
if (empty($currentSettings['setting_admin_email'])) {
    $currentSettings['setting_admin_email'] = ADMIN_EMAIL;
}
if (empty($currentSettings['setting_posts_per_page'])) {
    $currentSettings['setting_posts_per_page'] = '10';
}
if (empty($currentSettings['setting_timezone'])) {
    $currentSettings['setting_timezone'] = 'Europe/Berlin';
}
if (empty($currentSettings['setting_date_format'])) {
    $currentSettings['setting_date_format'] = 'd.m.Y';
}
if (empty($currentSettings['setting_time_format'])) {
    $currentSettings['setting_time_format'] = 'H:i';
}

// Generate CSRF token
$csrfToken = $security->generateToken('admin_settings');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('settings'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>Systemeinstellungen</h2>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Settings Form -->
        <div class="admin-section">
            <h3>Allgemeine Einstellungen</h3>
            
            <form method="POST" action="/admin/settings" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="site_name">
                        <strong>Website-Name</strong>
                    </label>
                    <input type="text" 
                           id="site_name" 
                           name="site_name" 
                           value="<?php echo htmlspecialchars($currentSettings['setting_site_name']); ?>"
                           class="form-control"
                           required>
                    <small class="form-text">Der Name Ihrer Website</small>
                </div>
                
                <div class="form-group">
                    <label for="site_description">
                        <strong>Website-Beschreibung</strong>
                    </label>
                    <?php echo EditorService::getInstance()->render('site_description', $currentSettings['setting_site_description'] ?? '', ['height' => 200]); ?>
                    <small class="form-text">Kurze Beschreibung Ihrer Website (für Suchmaschinen)</small>
                </div>
                
                <div class="form-group">
                    <label for="admin_email">
                        <strong>Administrator E-Mail</strong>
                    </label>
                    <input type="email" 
                           id="admin_email" 
                           name="admin_email" 
                           value="<?php echo htmlspecialchars($currentSettings['setting_admin_email']); ?>"
                           class="form-control"
                           required>
                    <small class="form-text">E-Mail-Adresse für System-Benachrichtigungen</small>
                </div>
                
                <div class="form-group">
                    <label for="posts_per_page">
                        <strong>Beiträge pro Seite</strong>
                    </label>
                    <input type="number" 
                           id="posts_per_page" 
                           name="posts_per_page" 
                           value="<?php echo htmlspecialchars($currentSettings['setting_posts_per_page']); ?>"
                           class="form-control"
                           min="1"
                           max="100">
                    <small class="form-text">Anzahl der Beiträge in Übersichtslisten</small>
                </div>
                
                <div class="form-group">
                    <label for="timezone">
                        <strong>Zeitzone</strong>
                    </label>
                    <select id="timezone" name="timezone" class="form-control">
                        <option value="Europe/Berlin" <?php echo $currentSettings['setting_timezone'] === 'Europe/Berlin' ? 'selected' : ''; ?>>Europa/Berlin (MEZ)</option>
                        <option value="Europe/Vienna" <?php echo $currentSettings['setting_timezone'] === 'Europe/Vienna' ? 'selected' : ''; ?>>Europa/Wien (MEZ)</option>
                        <option value="Europe/Zurich" <?php echo $currentSettings['setting_timezone'] === 'Europe/Zurich' ? 'selected' : ''; ?>>Europa/Zürich (MEZ)</option>
                        <option value="UTC" <?php echo $currentSettings['setting_timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                    </select>
                    <small class="form-text">Zeitzone für Datumsangaben</small>
                </div>
                
                <div class="form-group">
                    <label for="date_format">
                        <strong>Datumsformat</strong>
                    </label>
                    <select id="date_format" name="date_format" class="form-control">
                        <option value="d.m.Y" <?php echo $currentSettings['setting_date_format'] === 'd.m.Y' ? 'selected' : ''; ?>>dd.mm.YYYY (z.B. 18.02.2026)</option>
                        <option value="Y-m-d" <?php echo $currentSettings['setting_date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-mm-dd (z.B. 2026-02-18)</option>
                        <option value="m/d/Y" <?php echo $currentSettings['setting_date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>mm/dd/YYYY (z.B. 02/18/2026)</option>
                    </select>
                    <small class="form-text">Format für Datumsanzeigen</small>
                </div>
                
                <div class="form-group">
                    <label for="time_format">
                        <strong>Zeitformat</strong>
                    </label>
                    <select id="time_format" name="time_format" class="form-control">
                        <option value="H:i" <?php echo $currentSettings['setting_time_format'] === 'H:i' ? 'selected' : ''; ?>>24-Stunden (z.B. 14:30)</option>
                        <option value="h:i A" <?php echo $currentSettings['setting_time_format'] === 'h:i A' ? 'selected' : ''; ?>>12-Stunden (z.B. 02:30 PM)</option>
                    </select>
                    <small class="form-text">Format für Zeitanzeigen</small>
                </div>
                
                <hr style="margin: 2rem 0;">
                
                <h4 style="margin-bottom: 1rem;">Optionen</h4>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" 
                               name="maintenance_mode" 
                               value="1"
                               <?php echo $currentSettings['setting_maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                        <strong>Wartungsmodus aktivieren</strong>
                    </label>
                    <small class="form-text">Website für normale Benutzer deaktivieren (nur Admins haben Zugriff)</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" 
                               name="allow_registration" 
                               value="1"
                               <?php echo $currentSettings['setting_allow_registration'] === '1' ? 'checked' : ''; ?>>
                        <strong>Registrierung erlauben</strong>
                    </label>
                    <small class="form-text">Neue Benutzer können sich registrieren</small>
                </div>
                
                <button type="submit" class="btn-save">
                    💾 Einstellungen speichern
                </button>
            </form>
        </div>
        
        <!-- Security Settings -->
        <div class="admin-section">
            <h3>Sicherheitseinstellungen</h3>
            
            <div class="info-grid">
                <div class="info-card">
                    <h4>🔒 Session</h4>
                    <ul class="info-list">
                        <li><strong>Session Timeout:</strong> 30 Minuten</li>
                        <li><strong>Remember Me:</strong> 30 Tage</li>
                        <li><strong>HTTPS erzwingen:</strong> ✅ Aktiv</li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h4>🛡️ Schutz</h4>
                    <ul class="info-list">
                        <li><strong>CSRF Protection:</strong> ✅ Aktiv</li>
                        <li><strong>XSS Protection:</strong> ✅ Aktiv</li>
                        <li><strong>SQL Injection Protection:</strong> ✅ Aktiv</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Plugin Settings Hook -->
        <?php Hooks::doAction('admin_settings_page'); ?>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    
</body>
</html>
