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
    <title>Einstellungen – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Settings Page – Spacing & Layout ─────────────────────── */
        .settings-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.75rem;
            align-items: start;
        }
        @media (max-width: 1100px) {
            .settings-layout { grid-template-columns: 1fr; }
        }
        .settings-layout .admin-card {
            margin-bottom: 0;
        }
        .settings-layout .admin-card h3 {
            margin-bottom: 1.5rem;
        }
        /* Section divider between form groups */
        .settings-layout .form-group {
            margin-bottom: 1.5rem;
        }
        .settings-layout .form-group:last-child {
            margin-bottom: 0;
        }
        .settings-layout .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.45rem;
            color: #374151;
            font-size: 0.9rem;
        }
        .settings-layout .form-control {
            padding: 0.6rem 0.85rem;
            font-size: 0.95rem;
        }
        .settings-layout .form-text {
            margin-top: 0.35rem;
            font-size: 0.82rem;
            color: #64748b;
        }
        /* Two-column row for date/time */
        .settings-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        @media (max-width: 600px) {
            .settings-two-col { grid-template-columns: 1fr; }
        }
        /* Toggle row */
        .settings-toggle-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .settings-toggle-row:last-of-type {
            border-bottom: none;
            padding-bottom: 0;
        }
        .settings-toggle-row:first-of-type {
            padding-top: 0;
        }
        .settings-toggle-row .toggle-switch {
            flex-shrink: 0;
            margin-top: 2px;
        }
        .settings-toggle-row .toggle-info strong {
            display: block;
            font-size: 0.95rem;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }
        .settings-toggle-row .toggle-info p {
            margin: 0;
            font-size: 0.83rem;
            color: #64748b;
        }
        /* Module list */
        .module-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.85rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.93rem;
            color: #1e293b;
        }
        .module-row:last-child { border-bottom: none; padding-bottom: 0; }
        .module-row:first-child { padding-top: 0; }
        .check-icon { color: #10b981; font-weight: 700; }
        /* Save bar */
        .settings-save-bar {
            position: sticky;
            bottom: 1.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .settings-save-bar span {
            font-size: 0.85rem;
            color: #64748b;
        }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('settings'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>⚙️ Systemeinstellungen</h2>
                <p>Grundlegende Konfiguration der Website</p>
            </div>
            <div class="header-actions">
                <a href="/admin/legal-sites" class="btn btn-secondary">⚖️ Legal Sites</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($activeTab === 'general'): ?>

        <form method="POST" action="/admin/settings">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="settings-layout">

                <!-- 1. Allgemein -->
                <div class="admin-card">
                    <h3>🏢 Allgemein</h3>

                    <div class="form-group">
                        <label class="form-label" for="site_name">Website-Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="site_name" name="site_name"
                               value="<?php echo htmlspecialchars($currentSettings['setting_site_name']); ?>"
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="site_description">Untertitel / Slogan</label>
                        <textarea id="site_description" name="site_description" class="form-control" rows="3"><?php echo htmlspecialchars($currentSettings['setting_site_description'] ?? ''); ?></textarea>
                        <small class="form-text">Wird in Suchmaschinen und im Browser-Titel angezeigt.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="admin_email">Admin E-Mail <span style="color:#ef4444;">*</span></label>
                        <input type="email" id="admin_email" name="admin_email"
                               value="<?php echo htmlspecialchars($currentSettings['setting_admin_email']); ?>"
                               class="form-control" required>
                        <small class="form-text">Empfänger für System-Benachrichtigungen.</small>
                    </div>
                </div>

                <!-- 2. Lokalisierung -->
                <div class="admin-card">
                    <h3>🌍 Lokalisierung</h3>

                    <div class="form-group">
                        <label class="form-label" for="timezone">Zeitzone</label>
                        <select id="timezone" name="timezone" class="form-control">
                            <option value="Europe/Berlin" <?php echo $currentSettings['setting_timezone'] === 'Europe/Berlin' ? 'selected' : ''; ?>>Europa/Berlin (MEZ)</option>
                            <option value="Europe/Vienna" <?php echo $currentSettings['setting_timezone'] === 'Europe/Vienna' ? 'selected' : ''; ?>>Europa/Wien (MEZ)</option>
                            <option value="Europe/Zurich" <?php echo $currentSettings['setting_timezone'] === 'Europe/Zurich' ? 'selected' : ''; ?>>Europa/Zürich (MEZ)</option>
                            <option value="UTC" <?php echo $currentSettings['setting_timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        </select>
                    </div>

                    <div class="settings-two-col">
                        <div class="form-group">
                            <label class="form-label" for="date_format">Datumsformat</label>
                            <select id="date_format" name="date_format" class="form-control">
                                <option value="d.m.Y" <?php echo $currentSettings['setting_date_format'] === 'd.m.Y' ? 'selected' : ''; ?>>18.02.2026</option>
                                <option value="Y-m-d" <?php echo $currentSettings['setting_date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>2026-02-18</option>
                                <option value="m/d/Y" <?php echo $currentSettings['setting_date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>02/18/2026</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_format">Zeitformat</label>
                            <select id="time_format" name="time_format" class="form-control">
                                <option value="H:i" <?php echo $currentSettings['setting_time_format'] === 'H:i' ? 'selected' : ''; ?>>14:30 (24h)</option>
                                <option value="h:i A" <?php echo $currentSettings['setting_time_format'] === 'h:i A' ? 'selected' : ''; ?>>02:30 PM (12h)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Inhalt & Darstellung -->
                <div class="admin-card">
                    <h3>🎨 Inhalt &amp; Darstellung</h3>

                    <div class="form-group">
                        <label class="form-label" for="home_page_id">Startseite</label>
                        <select id="home_page_id" name="home_page_id" class="form-control">
                            <option value="0">– Neueste Beiträge –</option>
                            <?php foreach ($pages as $p): ?>
                                <option value="<?php echo (int)$p->ID; ?>" <?php echo ($currentSettings['setting_home_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Wähle eine statische Seite als Startseite aus.</small>
                    </div>

                    <div class="settings-two-col">
                        <div class="form-group">
                            <label class="form-label" for="posts_per_page">Einträge pro Seite</label>
                            <input type="number" id="posts_per_page" name="posts_per_page"
                                   value="<?php echo htmlspecialchars($currentSettings['setting_posts_per_page']); ?>"
                                   class="form-control" min="1" max="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="permalink_structure">Permalink-Struktur</label>
                            <select id="permalink_structure" name="permalink_structure" class="form-control">
                                <option value="/?p=%post_id%" <?php echo ($currentSettings['setting_permalink_structure'] == '/?p=%post_id%') ? 'selected' : ''; ?>>Einfach (/?p=123)</option>
                                <option value="/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%postname%/') ? 'selected' : ''; ?>>Beitragsname (/beispiel/)</option>
                                <option value="/%year%/%monthnum%/%postname%/" <?php echo ($currentSettings['setting_permalink_structure'] == '/%year%/%monthnum%/%postname%/') ? 'selected' : ''; ?>>Monat &amp; Name (/2026/02/beitrag/)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 4. System & Benutzer -->
                <div class="admin-card">
                    <h3>🔧 System &amp; Benutzer</h3>

                    <div class="settings-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="maintenance_mode" value="1" <?php echo $currentSettings['setting_maintenance_mode'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Wartungsmodus</strong>
                            <p>Website ist für Besucher gesperrt. Admins können sich weiterhin einloggen.</p>
                        </div>
                    </div>

                    <div class="settings-toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_registration" value="1" <?php echo $currentSettings['setting_allow_registration'] === '1' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                        <div class="toggle-info">
                            <strong>Registrierung erlauben</strong>
                            <p>Jeder kann sich ein Konto erstellen.</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:1.5rem;">
                        <label class="form-label" for="default_role">Standard-Rolle für neue Benutzer</label>
                        <select id="default_role" name="default_role" class="form-control">
                            <option value="subscriber" <?php echo ($currentSettings['setting_default_role'] == 'subscriber') ? 'selected' : ''; ?>>Abonnent</option>
                            <option value="contributor" <?php echo ($currentSettings['setting_default_role'] == 'contributor') ? 'selected' : ''; ?>>Mitarbeiter</option>
                            <option value="author" <?php echo ($currentSettings['setting_default_role'] == 'author') ? 'selected' : ''; ?>>Autor</option>
                            <option value="editor" <?php echo ($currentSettings['setting_default_role'] == 'editor') ? 'selected' : ''; ?>>Redakteur</option>
                            <option value="admin" <?php echo ($currentSettings['setting_default_role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                        <small class="form-text">Rolle die neu registrierten Benutzern automatisch zugewiesen wird.</small>
                    </div>
                </div>

                <!-- 5. Rechtliches -->
                <div class="admin-card">
                    <h3>⚖️ Rechtliches &amp; Datenschutz</h3>

                    <div class="settings-two-col">
                        <div class="form-group">
                            <label class="form-label" for="legal_page_id">Impressum</label>
                            <select id="legal_page_id" name="legal_page_id" class="form-control">
                                <option value="0">– Nicht ausgewählt –</option>
                                <?php foreach ($pages as $p): ?>
                                    <option value="<?php echo (int)$p->ID; ?>" <?php echo ($currentSettings['setting_legal_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="privacy_page_id">Datenschutzerklärung</label>
                            <select id="privacy_page_id" name="privacy_page_id" class="form-control">
                                <option value="0">– Nicht ausgewählt –</option>
                                <?php foreach ($pages as $p): ?>
                                    <option value="<?php echo (int)$p->ID; ?>" <?php echo ($currentSettings['setting_privacy_page_id'] == $p->ID) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <small class="form-text">Diese Seiten werden automatisch im Footer und bei Formularen verlinkt.</small>
                </div>

                <!-- 6. Module -->
                <div class="admin-card">
                    <h3>🧩 Aktive Module</h3>
                    <div class="module-row">
                        <span>📝 Blog Modul</span>
                        <span class="status-badge active">Aktiv</span>
                    </div>
                    <div class="module-row">
                        <span>🛒 Shop System</span>
                        <span class="status-badge inactive">Inaktiv</span>
                    </div>
                </div>

            </div><!-- /.settings-layout -->

            <!-- Save Bar -->
            <div class="settings-save-bar">
                <span>Änderungen werden sofort aktiv nach dem Speichern.</span>
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </div>

        </form>

        <?php endif; // End general tab ?>

        <!-- Plugin Settings Hook -->
        <?php Hooks::doAction('admin_settings_page'); ?>

    </div><!-- /.admin-content -->

    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
