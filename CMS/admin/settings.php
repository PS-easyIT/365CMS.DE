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
$activeTab = $_GET['tab'] ?? 'general';

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
                    'default_role' => $_POST['default_role'] ?? 'subscriber',
                    'posts_per_page' => intval($_POST['posts_per_page'] ?? 10),
                    'home_page_id' => intval($_POST['home_page_id'] ?? 0),
                    'timezone' => $_POST['timezone'] ?? 'Europe/Berlin',
                    'date_format' => $_POST['date_format'] ?? 'd.m.Y',
                    'time_format' => $_POST['time_format'] ?? 'H:i',
                    'permalink_structure' => $_POST['permalink_structure'] ?? '/%postname%/',
                    'legal_page_id' => intval($_POST['legal_page_id'] ?? 0),
                    'privacy_page_id' => intval($_POST['privacy_page_id'] ?? 0),
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
    'setting_maintenance_mode', 'setting_allow_registration', 'setting_default_role',
    'setting_posts_per_page', 'setting_home_page_id',
    'setting_timezone', 'setting_date_format', 'setting_time_format', 'setting_permalink_structure',
    'setting_legal_page_id', 'setting_privacy_page_id'
];

foreach ($settingKeys as $key) {
    $result = $db->execute(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    )->fetch();
    
    $currentSettings[$key] = $result ? $result->option_value : '';
}

// Set defaults if not in database
$defaults = [
    'setting_site_name' => SITE_NAME,
    'setting_admin_email' => ADMIN_EMAIL,
    'setting_posts_per_page' => '10',
    'setting_timezone' => 'Europe/Berlin',
    'setting_date_format' => 'd.m.Y',
    'setting_time_format' => 'H:i',
    'setting_default_role' => 'subscriber',
    'setting_permalink_structure' => '/%postname%/'
];

foreach ($defaults as $key => $val) {
    if (empty($currentSettings[$key])) $currentSettings[$key] = $val;
}

// Fetch Pages for Dropdowns
$pages = $db->execute("SELECT id as ID, title as post_title FROM {$db->getPrefix()}pages WHERE status = 'published' ORDER BY title ASC")->fetchAll();

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
            <h2><?php echo ($activeTab === 'legal') ? 'Rechtstexte Generator' : 'Systemeinstellungen'; ?></h2>
            <p><?php echo ($activeTab === 'legal') ? 'Automatische Erstellung von Impressum und Datenschutz' : 'Grundlegende Konfiguration der Website'; ?></p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="analytics-tabs" style="margin-bottom: 2rem;">
            <a href="?tab=general" class="tab-button <?php echo $activeTab === 'general' ? 'active' : ''; ?>">⚙️ Allgemein</a>
            <a href="/admin/legal-sites" class="tab-button">➡️ Zu Legal Sites</a>
        </div>
        
        <?php if ($activeTab === 'general'): ?>

        <form method="POST" action="/admin/settings" class="settings-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="settings-grid">
                
                <!-- 1. General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>🏢 Allgemein</h3>
                        <p>Grundlegende Informationen zur Website</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="site_name">Website-Name</label>
                            <input type="text" id="site_name" name="site_name" 
                                   value="<?php echo htmlspecialchars($currentSettings['setting_site_name']); ?>"
                                   class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_description">Untertitel / Slogan</label>
                            <textarea id="site_description" name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($currentSettings['setting_site_description'] ?? ''); ?></textarea>
                            <small>Wird in Suchmaschinen und im Browser-Titel angezeigt.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin E-Mail</label>
                            <input type="email" id="admin_email" name="admin_email" 
                                   value="<?php echo htmlspecialchars($currentSettings['setting_admin_email']); ?>"
                                   class="form-control" required>
                            <small>Empfänger für System-Benachrichtigungen.</small>
                        </div>
                    </div>
                </div>

                <!-- 2. Localization -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>🌍 Lokalisierung</h3>
                        <p>Sprache, Zeit und Datumsformate</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="timezone">Zeitzone</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Europe/Berlin" <?php echo $currentSettings['setting_timezone'] === 'Europe/Berlin' ? 'selected' : ''; ?>>Europa/Berlin (MEZ)</option>
                                <option value="Europe/Vienna" <?php echo $currentSettings['setting_timezone'] === 'Europe/Vienna' ? 'selected' : ''; ?>>Europa/Wien (MEZ)</option>
                                <option value="Europe/Zurich" <?php echo $currentSettings['setting_timezone'] === 'Europe/Zurich' ? 'selected' : ''; ?>>Europa/Zürich (MEZ)</option>
                                <option value="UTC" <?php echo $currentSettings['setting_timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                            </select>
                        </div>
                        
                        <div class="split-group">
                            <div class="form-group">
                                <label for="date_format">Datumsformat</label>
                                <select id="date_format" name="date_format" class="form-control">
                                    <option value="d.m.Y" <?php echo $currentSettings['setting_date_format'] === 'd.m.Y' ? 'selected' : ''; ?>>18.02.2026</option>
                                    <option value="Y-m-d" <?php echo $currentSettings['setting_date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2026-02-18</option>
                                    <option value="m/d/Y" <?php echo $currentSettings['setting_date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>02/18/2026</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="time_format">Zeitformat</label>
                                <select id="time_format" name="time_format" class="form-control">
                                    <option value="H:i" <?php echo $currentSettings['setting_time_format'] === 'H:i' ? 'selected' : ''; ?>>14:30 (24h)</option>
                                    <option value="h:i A" <?php echo $currentSettings['setting_time_format'] === 'h:i A' ? 'selected' : ''; ?>>02:30 PM (12h)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Content & Display -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>🎨 Inhalt & Darstellung</h3>
                        <p>Einstellungen für Frontend und Blog</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="home_page_id">Startseite</label>
                            <select id="home_page_id" name="home_page_id" class="form-control">
                                <option value="0">-- Neueste Beiträge --</option>
                                <?php foreach($pages as $p): ?>
                                    <option value="<?php echo $p->ID; ?>" <?php echo ($currentSettings['setting_home_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="posts_per_page">Einträge pro Seite</label>
                            <input type="number" id="posts_per_page" name="posts_per_page" 
                                   value="<?php echo htmlspecialchars($currentSettings['setting_posts_per_page']); ?>"
                                   class="form-control" min="1" max="100">
                        </div>

                        <div class="form-group">
                            <label for="permalink_structure">Permalink Struktur</label>
                            <select id="permalink_structure" name="permalink_structure" class="form-control">
                                <option value="/?p=%post_id%" <?php echo ($currentSettings['setting_permalink_structure'] == '/?p=%post_id%') ? 'selected' : ''; ?>>Einfach (/?p=123)</option>
                                <option value="/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%postname%/') ? 'selected' : ''; ?>>Beitragsname (/beispiel-beitrag/)</option>
                                <option value="/%year%/%monthnum%/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%year%/%monthnum%/%postname%/') ? 'selected' : ''; ?>>Monat & Name (/2026/02/beitrag/)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 4. System & Users -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>⚙️ System & Benutzer</h3>
                        <p>Zugriffssteuerung und Systemstatus</p>
                    </div>
                    <div class="card-body">
                        <div class="toggle-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance_mode" value="1" <?php echo $currentSettings['setting_maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label">
                                <strong>Wartungsmodus</strong>
                                <p>Website ist für Besucher nicht erreichbar.</p>
                            </div>
                        </div>

                        <div class="toggle-group">
                            <label class="toggle-switch">
                                <input type="checkbox" name="allow_registration" value="1" <?php echo $currentSettings['setting_allow_registration'] === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="toggle-label">
                                <strong>Registrierung erlauben</strong>
                                <p>Jeder kann sich registrieren.</p>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:1rem;">
                            <label for="default_role">Standard-Rolle für neue Benutzer</label>
                            <select id="default_role" name="default_role" class="form-control">
                                <option value="subscriber" <?php echo ($currentSettings['setting_default_role'] == 'subscriber') ? 'selected' : ''; ?>>Abonnent</option>
                                <option value="contributor" <?php echo ($currentSettings['setting_default_role'] == 'contributor') ? 'selected' : ''; ?>>Mitarbeiter</option>
                                <option value="author" <?php echo ($currentSettings['setting_default_role'] == 'author') ? 'selected' : ''; ?>>Autor</option>
                                <option value="editor" <?php echo ($currentSettings['setting_default_role'] == 'editor') ? 'selected' : ''; ?>>Redakteur</option>
                                <option value="admin" <?php echo ($currentSettings['setting_default_role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 5. Legal & Privacy -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>⚖️ Rechtliches</h3>
                        <p>Verknüpfungen zu rechtlichen Seiten</p>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="legal_page_id">Impressum Seite</label>
                            <select id="legal_page_id" name="legal_page_id" class="form-control">
                                <option value="0">-- Nicht ausgewählt --</option>
                                <?php foreach($pages as $p): ?>
                                    <option value="<?php echo $p->ID; ?>" <?php echo ($currentSettings['setting_legal_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="privacy_page_id">Datenschutzerklärung</label>
                            <select id="privacy_page_id" name="privacy_page_id" class="form-control">
                                <option value="0">-- Nicht ausgewählt --</option>
                                <?php foreach($pages as $p): ?>
                                    <option value="<?php echo $p->ID; ?>" <?php echo ($currentSettings['setting_privacy_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- 6. Features & Modules (Placeholder for future) -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3>🧩 Module</h3>
                        <p>Erweiterte Funktionen aktivieren</p>
                    </div>
                    <div class="card-body">
                         <div class="info-list-item">
                            <span>📝 Blog Modul</span>
                            <span class="badge badge-success">Aktiv</span>
                        </div>
                        <div class="info-list-item">
                            <span>🛒 Shop System</span>
                            <span class="badge badge-secondary">Inaktiv</span>
                        </div>
                    </div>
                </div>

            </div>
            
            <div class="sticky-footer">
                <button type="submit" class="btn-save btn-large">
                    💾 Alle Einstellungen speichern
                </button>
            </div>
        </form>
        
        <!-- CMS Status Info (ReadOnly) -->
        <div class="admin-section" style="margin-top:3rem;">
            <h3>System Status</h3>
            <div class="settings-grid">
                 <div class="settings-card">
                    <div class="card-body">
                        <h4 style="margin-top:0;">🔒 Security Headers</h4>
                         <ul class="status-list">
                            <li><span class="status-icon check">✓</span> HTTPS Active</li>
                            <li><span class="status-icon check">✓</span> CSRF Protection</li>
                            <li><span class="status-icon check">✓</span> XSS Protection</li>
                        </ul>
                    </div>
                 </div>
                 <div class="settings-card">
                    <div class="card-body">
                         <h4 style="margin-top:0;">💻 Server</h4>
                         <ul class="status-list">
                            <li><strong>PHP:</strong> <?php echo phpversion(); ?></li>
                            <li><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
                            <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                         </ul>
                    </div>
                 </div>
            </div>
        </div>

    </div>

    <!-- Styles for Settings Page -->
    <style>
        .settings-form {
            max-width: 100%;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 1000px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            background: #f8fafc;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }
        .card-header p { margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b; }

        .card-body {
            padding: 1.5rem;
            flex: 1;
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 0.5rem; color: #334155; }
        .form-control { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; }
        .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .form-group small { display: block; color: #94a3b8; font-size: 0.8rem; margin-top: 0.25rem; }

        .split-group { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        /* Toggle Switch */
        .toggle-group { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem; }
        .toggle-switch { position: relative; width: 48px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #3b82f6; }
        input:checked + .slider:before { transform: translateX(22px); }
        .toggle-label strong { display: block; color: #334155; }
        .toggle-label p { margin: 0; font-size: 0.85rem; color: #64748b; }

        .sticky-footer {
            position: sticky;
            bottom: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            z-index: 10;
        }

        .btn-large { padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600; }
        
        .status-list { list-style: none; padding: 0; margin: 0; }
        .status-list li { padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; }
        .status-list li:last-child { border-bottom: none; }
        .status-icon.check { color: #10b981; font-weight: bold; margin-right: 0.5rem; }
        .badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-secondary { background: #f1f5f9; color: #475569; }
        
        .info-list-item { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
        .info-list-item:last-child { border-bottom: none; }
    </style>

    <?php endif; // End of tabs ?>
    
    <!-- Plugin Settings Hook -->
    <?php Hooks::doAction('admin_settings_page'); ?>

    <?php renderAdminLayoutEnd(); ?>
