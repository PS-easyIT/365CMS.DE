<?php
declare(strict_types=1);

/**
 * Admin: Member Dashboard – Verwaltung
 *
 * URL:  /admin/member-dashboard
 * Tabs: plugins | navigation | quickstart | widgets | design | settings
 *
 *   plugins    – Welche CMS-Plugins im Member-Dashboard sichtbar sind
 *   navigation – Mitglieder-Sidebar-Menü konfigurieren
 *   quickstart – Schnellstart-Buttons auf der Dashboard-Startseite
 *   widgets    – Bis zu 6 Info-Widgets (Icon, Titel, Text, Link, Buttontext)
 *   design     – Farbgebung + Layout + Sektions-Reihenfolge + Stats-Widgets
 *   settings   – Allgemeine Einstellungen (Begrüßung, Registrierung, Medien, Sicherheit)
 *
 * @package CMSv2\Admin
 */

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security = Security::instance();
$db       = Database::instance();
$messages = [];

// ── Aktiver Tab ────────────────────────────────────────────────────────────────
$allowedTabs = ['plugins', 'navigation', 'quickstart', 'widgets', 'design', 'settings'];
$activeTab   = in_array($_GET['tab'] ?? '', $allowedTabs, true)
    ? $_GET['tab']
    : 'widgets';

// ── Helper: setting lesen / schreiben ─────────────────────────────────────────
function dw_get_setting(Database $db, string $key, string $default = ''): string
{
    $val = $db->get_var(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    );
    return $val !== null ? (string)$val : $default;
}

function dw_save_setting(Database $db, string $key, string $value): void
{
    $exists = (int)$db->get_var(
        "SELECT COUNT(*) FROM {$db->getPrefix()}settings WHERE option_name = ?",
        [$key]
    );
    if ($exists > 0) {
        $db->execute(
            "UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?",
            [$value, $key]
        );
    } else {
        $db->execute(
            "INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)",
            [$key, $value]
        );
    }
}

// ── Verfügbare Plugins ─────────────────────────────────────────────────────────
$dashboardPlugins = [];
if (class_exists('\\CMS\\PluginManager')) {
    $pm         = \CMS\PluginManager::instance();
    $allPlugins = $pm->getAvailablePlugins();
    $iconMap    = [
        'booking'     => '📅', 'calendar'  => '📅', 'shop'    => '🛒',
        'market'      => '🛒', 'marketplace'=> '🛒', 'message' => '✉️',
        'chat'        => '💬', 'project'   => '📋', 'task'    => '✅',
        'premium'     => '⭐', 'promo'     => '🎁', 'support' => '🎧',
        'ticket'      => '🎫', 'forum'     => '💬', 'expert'  => '👨‍💼',
        'member'      => '👤', 'profile'   => '🆔',
    ];
    foreach ($allPlugins as $slug => $data) {
        if (isset($data['active']) && $data['active'] === true) {
            $icon = '🧩';
            foreach ($iconMap as $key => $mapIcon) {
                if (stripos($slug, $key) !== false || stripos($data['name'] ?? '', $key) !== false) {
                    $icon = $mapIcon;
                    break;
                }
            }
            $dashboardPlugins[$slug] = [
                'label' => $data['name'],
                'icon'  => $icon,
                'desc'  => $data['description'] ?? '',
            ];
        }
    }
}

// ── Standard-Navigationspunkte ─────────────────────────────────────────────────
$defaultNavItems = [
    ['icon' => '🏠', 'label' => 'Dashboard',         'url' => '/member',               'visible' => true, 'login_required' => true],
    ['icon' => '👤', 'label' => 'Mein Profil',       'url' => '/member/profile',       'visible' => true, 'login_required' => true],
    ['icon' => '🔔', 'label' => 'Benachrichtigungen','url' => '/member/notifications', 'visible' => true, 'login_required' => true],
    ['icon' => '📂', 'label' => 'Meine Dateien',     'url' => '/member/files',         'visible' => true, 'login_required' => true],
    ['icon' => '⚙️', 'label' => 'Einstellungen',     'url' => '/member/settings',      'visible' => true, 'login_required' => true],
];

// ── Standard-Quick-Start ───────────────────────────────────────────────────────
$defaultQsButtons = [
    ['icon' => '✏️', 'label' => 'Profil bearbeiten',   'url' => '/member/profile',       'color' => 'primary',   'visible' => true],
    ['icon' => '📂', 'label' => 'Dateien verwalten',   'url' => '/member/files',         'color' => 'secondary', 'visible' => true],
    ['icon' => '🔔', 'label' => 'Benachrichtigungen',  'url' => '/member/notifications', 'color' => 'secondary', 'visible' => true],
    ['icon' => '⚙️', 'label' => 'Konto-Einstellungen', 'url' => '/member/settings',      'color' => 'secondary', 'visible' => true],
];

// ── POST-Verarbeitung ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTab = $security->sanitize($_POST['active_tab'] ?? 'widgets', 'text');

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'dashboard_widgets')) {
        $messages[] = ['type' => 'error', 'text' => 'Ungültiger Sicherheits-Token.'];
    } else {

        // ── Logo-Upload ───────────────────────────────────────────────────────
        if ($postTab === 'design' && isset($_FILES['member_dashboard_logo'])
            && $_FILES['member_dashboard_logo']['size'] > 0) {
            $file        = $_FILES['member_dashboard_logo'];
            $allowedExts = ['jpg','jpeg','png','gif','svg','webp'];
            $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file['error'] === UPLOAD_ERR_OK && in_array($ext, $allowedExts, true)) {
                $filename   = 'member-logo-' . time() . '.' . $ext;
                $uploadRel  = '/assets/uploads/logos/';
                $uploadPath = dirname(__DIR__) . $uploadRel;
                if (!is_dir($uploadPath)) { mkdir($uploadPath, 0755, true); }
                if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                    dw_save_setting($db, 'member_dashboard_logo', SITE_URL . $uploadRel . $filename);
                    $messages[] = ['type' => 'success', 'text' => 'Logo erfolgreich hochgeladen.'];
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Fehler beim Hochladen.'];
                }
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Ungültiges Dateiformat.'];
            }
        } elseif ($postTab === 'design' && isset($_POST['delete_logo'])) {
            dw_save_setting($db, 'member_dashboard_logo', '');
            $messages[] = ['type' => 'success', 'text' => 'Logo entfernt.'];
        }

        // ── Plugins ───────────────────────────────────────────────────────────
        if ($postTab === 'plugins') {
            foreach (array_keys($dashboardPlugins) as $slug) {
                dw_save_setting($db, "member_dashboard_plugin_{$slug}",
                    isset($_POST["plugin_enabled_{$slug}"]) ? '1' : '0');
            }
            $messages[] = ['type' => 'success', 'text' => 'Plugin-Sichtbarkeit gespeichert.'];
        }

        // ── Navigation ────────────────────────────────────────────────────────
        if ($postTab === 'navigation') {
            $navItems = [];
            $count    = (int)($_POST['nav_count'] ?? 0);
            for ($i = 0; $i < min($count, 20); $i++) {
                $navItems[] = [
                    'icon'           => $security->sanitize(trim($_POST["nav_{$i}_icon"]  ?? ''), 'text'),
                    'label'          => $security->sanitize(trim($_POST["nav_{$i}_label"] ?? ''), 'text'),
                    'url'            => filter_var(trim($_POST["nav_{$i}_url"] ?? ''), FILTER_SANITIZE_URL),
                    'visible'        => isset($_POST["nav_{$i}_visible"]),
                    'login_required' => isset($_POST["nav_{$i}_login_required"]),
                ];
            }
            dw_save_setting($db, 'member_nav_items', json_encode($navItems, JSON_UNESCAPED_UNICODE));
            $messages[] = ['type' => 'success', 'text' => 'Navigation gespeichert.'];
        }

        // ── Quick-Start ───────────────────────────────────────────────────────
        if ($postTab === 'quickstart') {
            $qsItems     = [];
            $count       = (int)($_POST['qs_count'] ?? 0);
            $validColors = ['primary','secondary','success','danger'];
            for ($i = 0; $i < min($count, 12); $i++) {
                $color     = $_POST["qs_{$i}_color"] ?? 'secondary';
                $qsItems[] = [
                    'icon'    => $security->sanitize(trim($_POST["qs_{$i}_icon"]  ?? ''), 'text'),
                    'label'   => $security->sanitize(trim($_POST["qs_{$i}_label"] ?? ''), 'text'),
                    'url'     => filter_var(trim($_POST["qs_{$i}_url"] ?? ''), FILTER_SANITIZE_URL),
                    'color'   => in_array($color, $validColors, true) ? $color : 'secondary',
                    'visible' => isset($_POST["qs_{$i}_visible"]),
                ];
            }
            dw_save_setting($db, 'member_quickstart_buttons', json_encode($qsItems, JSON_UNESCAPED_UNICODE));
            $messages[] = ['type' => 'success', 'text' => 'Schnellstart-Buttons gespeichert.'];
        }

        // ── Widgets ───────────────────────────────────────────────────────────
        if ($postTab === 'widgets') {
            for ($i = 1; $i <= 6; $i++) {
                $content = strip_tags(
                    html_entity_decode(trim($_POST["widget_{$i}_content"] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    '<p><a><strong><em><br><ul><ol><li>'
                );
                dw_save_setting($db, "member_widget_{$i}_icon",    $security->sanitize(trim($_POST["widget_{$i}_icon"]    ?? ''), 'text'));
                dw_save_setting($db, "member_widget_{$i}_title",   $security->sanitize(trim($_POST["widget_{$i}_title"]   ?? ''), 'text'));
                dw_save_setting($db, "member_widget_{$i}_content", $content);
                dw_save_setting($db, "member_widget_{$i}_link",    filter_var(trim($_POST["widget_{$i}_link"] ?? ''), FILTER_SANITIZE_URL));
                dw_save_setting($db, "member_widget_{$i}_btntext", $security->sanitize(trim($_POST["widget_{$i}_btntext"] ?? ''), 'text'));
            }
            $messages[] = ['type' => 'success', 'text' => 'Widgets gespeichert.'];
        }

        // ── Design & Layout ───────────────────────────────────────────────────
        if ($postTab === 'design') {
            foreach (['primary','accent','bg','card_bg','text','border'] as $ckey) {
                $raw = trim($_POST["color_{$ckey}"] ?? '');
                if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $raw)) {
                    dw_save_setting($db, "member_dashboard_color_{$ckey}", $raw);
                }
            }
            $cols = in_array((int)($_POST['dashboard_columns'] ?? 3), [1,2,3,4], true)
                ? (string)(int)$_POST['dashboard_columns'] : '3';
            dw_save_setting($db, 'member_dashboard_columns', $cols);

            $allowedSections = ['stats','quick_start','widgets','plugins'];
            $rawOrder        = explode(',', $security->sanitize(trim($_POST['dashboard_section_order'] ?? ''), 'text'));
            $sanitizedOrder  = array_values(array_intersect($rawOrder, $allowedSections));
            foreach ($allowedSections as $s) {
                if (!in_array($s, $sanitizedOrder, true)) { $sanitizedOrder[] = $s; }
            }
            dw_save_setting($db, 'member_dashboard_section_order', implode(',', $sanitizedOrder));

            foreach (['subscription','activity','notifications','messages','files','projects'] as $sk) {
                dw_save_setting($db, "member_stats_show_{$sk}", isset($_POST["stats_show_{$sk}"]) ? '1' : '0');
            }
            if (empty($messages)) {
                $messages[] = ['type' => 'success', 'text' => 'Design & Layout gespeichert.'];
            }
        }

        // ── Einstellungen ─────────────────────────────────────────────────────
        if ($postTab === 'settings') {
            dw_save_setting($db, 'member_dashboard_greeting',
                $security->sanitize(trim($_POST['member_dashboard_greeting'] ?? ''), 'text'));
            dw_save_setting($db, 'member_dashboard_welcome_text',
                strip_tags(trim($_POST['member_dashboard_welcome_text'] ?? ''), '<p><a><strong><em><br>'));
            dw_save_setting($db, 'member_dashboard_show_welcome',
                isset($_POST['member_dashboard_show_welcome']) ? '1' : '0');

            dw_save_setting($db, 'member_registration_open',      isset($_POST['member_registration_open'])      ? '1' : '0');
            dw_save_setting($db, 'member_email_verification',     isset($_POST['member_email_verification'])     ? '1' : '0');
            dw_save_setting($db, 'member_avatar_upload_enabled',  isset($_POST['member_avatar_upload_enabled'])  ? '1' : '0');
            dw_save_setting($db, 'member_profile_public_default', isset($_POST['member_profile_public_default']) ? '1' : '0');
            dw_save_setting($db, 'member_profile_edit_enabled',   isset($_POST['member_profile_edit_enabled'])   ? '1' : '0');
            dw_save_setting($db, 'member_profile_delete_enabled', isset($_POST['member_profile_delete_enabled']) ? '1' : '0');

            $maxMb = max(1, min(500, (int)($_POST['member_media_max_upload_mb'] ?? 10)));
            dw_save_setting($db, 'member_media_max_upload_mb', (string)$maxMb);
            $allowedTypes = [];
            foreach (['images','documents','videos','audio'] as $t) {
                if (isset($_POST["member_media_type_{$t}"])) { $allowedTypes[] = $t; }
            }
            dw_save_setting($db, 'member_media_allowed_types', implode(',', $allowedTypes) ?: 'images');

            dw_save_setting($db, 'member_redirect_after_login',
                filter_var(trim($_POST['member_redirect_after_login']   ?? '/member'), FILTER_SANITIZE_URL) ?: '/member');
            dw_save_setting($db, 'member_redirect_after_logout',
                filter_var(trim($_POST['member_redirect_after_logout']  ?? '/'),       FILTER_SANITIZE_URL) ?: '/');
            dw_save_setting($db, 'member_redirect_access_denied',
                filter_var(trim($_POST['member_redirect_access_denied'] ?? '/member'), FILTER_SANITIZE_URL) ?: '/member');

            dw_save_setting($db, 'member_notifications_email_enabled',
                isset($_POST['member_notifications_email_enabled']) ? '1' : '0');
            dw_save_setting($db, 'member_notifications_email_from',
                $security->sanitize(trim($_POST['member_notifications_email_from'] ?? ''), 'email'));

            dw_save_setting($db, 'member_max_login_attempts',
                (string)max(1, min(20,    (int)($_POST['member_max_login_attempts'] ?? 5))));
            dw_save_setting($db, 'member_session_timeout',
                (string)max(5, min(10080, (int)($_POST['member_session_timeout']    ?? 120))));

            $messages[] = ['type' => 'success', 'text' => 'Einstellungen gespeichert.'];
        }
    }
}

// ── Werte laden ────────────────────────────────────────────────────────────────

// Widgets
$widgets = [];
for ($i = 1; $i <= 6; $i++) {
    $widgets[$i] = [
        'icon'    => dw_get_setting($db, "member_widget_{$i}_icon",    '📌'),
        'title'   => dw_get_setting($db, "member_widget_{$i}_title",   ''),
        'content' => dw_get_setting($db, "member_widget_{$i}_content", ''),
        'link'    => dw_get_setting($db, "member_widget_{$i}_link",    ''),
        'btntext' => dw_get_setting($db, "member_widget_{$i}_btntext", ''),
    ];
}

// Plugins
$pluginEnabled = [];
foreach (array_keys($dashboardPlugins) as $slug) {
    $pluginEnabled[$slug] = dw_get_setting($db, "member_dashboard_plugin_{$slug}", '1') === '1';
}

// Navigation
$navItems = [];
$navRaw   = dw_get_setting($db, 'member_nav_items', '');
if (!empty($navRaw)) {
    $decoded = json_decode($navRaw, true);
    if (is_array($decoded)) { $navItems = $decoded; }
}
if (empty($navItems)) { $navItems = $defaultNavItems; }

// Quick-Start
$qsButtons = [];
$qsRaw     = dw_get_setting($db, 'member_quickstart_buttons', '');
if (!empty($qsRaw)) {
    $decoded = json_decode($qsRaw, true);
    if (is_array($decoded)) { $qsButtons = $decoded; }
}
if (empty($qsButtons)) { $qsButtons = $defaultQsButtons; }

// Design
$layoutCols   = (int)dw_get_setting($db, 'member_dashboard_columns', '3');
$layoutOrder  = dw_get_setting($db, 'member_dashboard_section_order', 'stats,quick_start,widgets,plugins');
$memberLogo   = dw_get_setting($db, 'member_dashboard_logo', '');
$designColors = [
    'primary'  => dw_get_setting($db, 'member_dashboard_color_primary',  '#6366f1'),
    'accent'   => dw_get_setting($db, 'member_dashboard_color_accent',   '#8b5cf6'),
    'bg'       => dw_get_setting($db, 'member_dashboard_color_bg',       '#f1f5f9'),
    'card_bg'  => dw_get_setting($db, 'member_dashboard_color_card_bg',  '#ffffff'),
    'text'     => dw_get_setting($db, 'member_dashboard_color_text',     '#1e293b'),
    'border'   => dw_get_setting($db, 'member_dashboard_color_border',   '#e2e8f0'),
];

// Stats
$statsShow = [];
foreach (['subscription','activity','notifications','messages','files','projects'] as $sk) {
    $statsShow[$sk] = dw_get_setting($db, "member_stats_show_{$sk}", '1') === '1';
}

// Einstellungen
$settingsData = [
    'greeting'                    => dw_get_setting($db, 'member_dashboard_greeting',             'Guten Tag, {name}!'),
    'welcome_text'                => dw_get_setting($db, 'member_dashboard_welcome_text',         ''),
    'show_welcome'                => dw_get_setting($db, 'member_dashboard_show_welcome',          '1'),
    'registration_open'           => dw_get_setting($db, 'member_registration_open',              '1'),
    'email_verification'          => dw_get_setting($db, 'member_email_verification',             '0'),
    'avatar_upload_enabled'       => dw_get_setting($db, 'member_avatar_upload_enabled',          '1'),
    'profile_public_default'      => dw_get_setting($db, 'member_profile_public_default',         '1'),
    'profile_edit_enabled'        => dw_get_setting($db, 'member_profile_edit_enabled',           '1'),
    'profile_delete_enabled'      => dw_get_setting($db, 'member_profile_delete_enabled',         '0'),
    'media_max_upload_mb'         => dw_get_setting($db, 'member_media_max_upload_mb',            '10'),
    'media_allowed_types'         => dw_get_setting($db, 'member_media_allowed_types',            'images,documents'),
    'redirect_after_login'        => dw_get_setting($db, 'member_redirect_after_login',           '/member'),
    'redirect_after_logout'       => dw_get_setting($db, 'member_redirect_after_logout',          '/'),
    'redirect_access_denied'      => dw_get_setting($db, 'member_redirect_access_denied',         '/member'),
    'notifications_email_enabled' => dw_get_setting($db, 'member_notifications_email_enabled',    '1'),
    'notifications_email_from'    => dw_get_setting($db, 'member_notifications_email_from',       ''),
    'max_login_attempts'          => dw_get_setting($db, 'member_max_login_attempts',             '5'),
    'session_timeout'             => dw_get_setting($db, 'member_session_timeout',                '120'),
];
$allowedMediaTypes = explode(',', $settingsData['media_allowed_types']);

// Sektions-Labels
$sectionLabels = [
    'stats'       => ['icon' => '📊', 'label' => 'Statuswidgets',   'desc' => 'Abo-Status, Aktivitätszähler'],
    'quick_start' => ['icon' => '🚀', 'label' => 'Schnellstart',    'desc' => 'Buttons für häufige Aktionen'],
    'widgets'     => ['icon' => '📌', 'label' => 'Infobereich',     'desc' => 'Admin-definierte Info-Widgets'],
    'plugins'     => ['icon' => '🔌', 'label' => 'Plugin-Bereiche', 'desc' => 'Widgets aktiver CMS-Plugins'],
];
$orderedSections = array_filter(
    array_map('trim', explode(',', $layoutOrder)),
    fn($s) => isset($sectionLabels[$s])
);
foreach (array_keys($sectionLabels) as $s) {
    if (!in_array($s, $orderedSections, true)) { $orderedSections[] = $s; }
}

$csrfToken = $security->generateToken('dashboard_widgets');
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Member Dashboard', 'member-dashboard');
?>

<style>
/* ── Tab-Navigation ──────────────────────────────────────────────────────────── */
.md-nav {
    display: flex;
    gap: .4rem;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: .375rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}
.md-nav-tab {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .55rem .875rem;
    border-radius: 8px;
    text-decoration: none;
    color: #64748b;
    font-size: .8rem;
    font-weight: 500;
    transition: background .15s, color .15s, box-shadow .15s;
    white-space: nowrap;
    flex: 1 1 auto;
    min-width: 0;
    border: 1px solid transparent;
}
.md-nav-tab:hover:not(.active) { background: #fff; color: #334155; border-color: #e2e8f0; }
.md-nav-tab.active {
    background: #fff;
    color: #4f46e5;
    border-color: #c7d2fe;
    box-shadow: 0 1px 6px rgba(79,70,229,.14);
    font-weight: 600;
}
.md-nav-icon {
    width: 28px; height: 28px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: .875rem;
    background: #e2e8f0;
    flex-shrink: 0;
    transition: background .15s;
}
.md-nav-tab.active .md-nav-icon { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); }
.md-nav-text  { display: flex; flex-direction: column; min-width: 0; }
.md-nav-label { font-size: .8rem; line-height: 1.2; white-space: nowrap; }
.md-nav-desc  { font-size: .67rem; color: #94a3b8; font-weight: 400; line-height: 1.2; white-space: nowrap; }
.md-nav-tab.active .md-nav-desc { color: #818cf8; }

/* ── Panels ──────────────────────────────────────────────────────────────────── */
.dw-panel        { display: none; }
.dw-panel.active { display: block; }

/* ── Grids ───────────────────────────────────────────────────────────────────── */
.dw-grid   { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: 1.25rem; }
.dw-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.dw-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 1rem; }
@media (max-width: 960px) { .dw-grid-2, .dw-grid-3 { grid-template-columns: 1fr; } }

/* ── Card-Header ─────────────────────────────────────────────────────────────── */
.dw-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .75rem;
    padding-bottom: .625rem;
    border-bottom: 1px solid #f1f5f9;
}
.dw-card-header h3 { margin: 0; border: none; padding: 0; font-size: 1rem; font-weight: 700; color: #1e293b; }
.dw-card-sub { font-size: .8rem; color: #64748b; margin: -.25rem 0 .875rem; }

/* ── Badge ───────────────────────────────────────────────────────────────────── */
.dw-badge { font-size: .7rem; font-weight: 600; padding: .2rem .6rem; border-radius: 20px;
    background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
.dw-badge.active { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }

/* ── Toggle-Switch ───────────────────────────────────────────────────────────── */
.dw-toggle { position: relative; display: inline-flex; width: 42px; height: 24px; flex-shrink: 0; cursor: pointer; }
.dw-toggle input { opacity: 0; width: 0; height: 0; }
.dw-toggle-slider {
    position: absolute; inset: 0;
    background: #cbd5e1;
    border-radius: 24px;
    transition: .25s;
}
.dw-toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; bottom: 3px;
    background: #fff;
    border-radius: 50%;
    transition: .25s;
}
.dw-toggle input:checked + .dw-toggle-slider { background: #6366f1; }
.dw-toggle input:checked + .dw-toggle-slider::before { transform: translateX(18px); }

/* ── Toggle-Zeile ────────────────────────────────────────────────────────────── */
.dw-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .75rem 0;
    border-bottom: 1px solid #f8fafc;
}
.dw-toggle-row:last-child { border-bottom: none; }
.dw-toggle-info { display: flex; flex-direction: column; }
.dw-toggle-info strong { font-size: .875rem; color: #1e293b; font-weight: 600; }
.dw-toggle-info span   { font-size: .78rem; color: #64748b; margin-top: .1rem; }

/* ── Plugin-Grid ─────────────────────────────────────────────────────────────── */
.dw-plugin-grid { display: flex; flex-direction: column; gap: .5rem; }
.dw-plugin-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    gap: 1rem;
    transition: background .12s;
}
.dw-plugin-row:hover { background: #f1f5f9; }
.dw-plugin-info  { display: flex; align-items: center; gap: .75rem; flex: 1; min-width: 0; }
.dw-plugin-icon  { font-size: 1.25rem; flex-shrink: 0; }
.dw-plugin-label { font-size: .875rem; font-weight: 600; color: #1e293b; }

/* ── Navigation-Liste ────────────────────────────────────────────────────────── */
.dw-nav-list { display: flex; flex-direction: column; gap: .45rem; margin-bottom: 1rem; }
.dw-nav-item {
    display: grid;
    grid-template-columns: 32px 1fr 2fr auto auto auto;
    gap: .625rem;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: .6rem .875rem;
    transition: background .12s;
}
.dw-nav-item:hover { background: #f1f5f9; }
.dw-nav-handle { cursor: grab; color: #94a3b8; font-size: 1.1rem; text-align: center; user-select: none; }
.dw-nav-item.dragging  { opacity: .45; background: #eff6ff; }
.dw-nav-item.drag-over { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.25); }
.dw-nav-delete { background: none; border: none; color: #ef4444; cursor: pointer; font-size: .9rem; padding: .2rem; border-radius: 4px; }
.dw-nav-delete:hover { background: #fee2e2; }
.dw-nav-input {
    font-size: .8125rem;
    padding: .35rem .6rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    width: 100%;
    transition: border-color .12s;
}
.dw-nav-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.15); }
.dw-nav-icon-input { width: 52px; font-size: .875rem; text-align: center; }

/* ── Quick-Start ─────────────────────────────────────────────────────────────── */
.dw-qs-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(270px,1fr)); gap: .875rem; margin-bottom: 1rem; }
.dw-qs-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .875rem 1rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
    transition: background .12s;
}
.dw-qs-item:hover { background: #f1f5f9; }
.dw-qs-item-head { display: flex; align-items: center; justify-content: space-between; }
.dw-qs-item-head strong { font-size: .8125rem; font-weight: 600; color: #1e293b; }
.dw-qs-delete { background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: .875rem; border-radius: 4px; }
.dw-qs-delete:hover { background: #fee2e2; }
.dw-qs-row   { display: grid; grid-template-columns: 80px 1fr; gap: .5rem; align-items: center; }
.dw-qs-label { font-size: .75rem; color: #64748b; font-weight: 500; }
.dw-qs-input {
    font-size: .8125rem;
    padding: .3rem .6rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
    width: 100%;
}
.dw-qs-input:focus { outline: none; border-color: #6366f1; }
.dw-color-pills { display: flex; gap: .35rem; flex-wrap: wrap; }
.dw-color-pill {
    cursor: pointer;
    border-radius: 20px;
    padding: .2rem .65rem;
    font-size: .7rem;
    font-weight: 600;
    border: 2px solid transparent;
    transition: all .15s;
    white-space: nowrap;
}
.dw-color-pill.primary   { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
.dw-color-pill.secondary { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
.dw-color-pill.success   { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
.dw-color-pill.danger    { background: #fef2f2; color: #ef4444; border-color: #fecaca; }
.dw-color-pill.selected.primary   { background: #3b82f6; color: #fff; border-color: #3b82f6; }
.dw-color-pill.selected.secondary { background: #64748b; color: #fff; border-color: #64748b; }
.dw-color-pill.selected.success   { background: #16a34a; color: #fff; border-color: #16a34a; }
.dw-color-pill.selected.danger    { background: #ef4444; color: #fff; border-color: #ef4444; }

/* ── Widgets ─────────────────────────────────────────────────────────────────── */
.dw-icon-row { display: flex; align-items: center; gap: .5rem; }
.dw-icon-preview { font-size: 1.5rem; min-width: 2rem; text-align: center; }
.dw-preview-box {
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: .875rem 1rem;
    margin-top: .75rem;
}
.dw-preview-box .preview-icon { font-size: 1.75rem; margin-bottom: .375rem; }
.dw-preview-box h4 { margin: 0 0 .375rem; font-size: .9rem; color: #92400e; font-weight: 700; }
.dw-empty-note { color: #94a3b8; font-size: .8125rem; font-style: italic; margin-top: .5rem; }
.dw-hint { font-size: .75rem; color: #94a3b8; margin: .2rem 0 0; }

/* ── Farben ──────────────────────────────────────────────────────────────────── */
.dw-color-grid { display: flex; flex-direction: column; gap: .5rem; }
.dw-color-row  { display: flex; align-items: center; gap: .75rem; padding: .35rem 0; }
.dw-color-row input[type=color] {
    width: 40px; height: 40px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    cursor: pointer;
    padding: 2px;
    flex-shrink: 0;
}
.dw-color-label { font-size: .8125rem; font-weight: 600; color: #374151; }
.dw-color-hex   { font-size: .75rem; color: #94a3b8; font-family: monospace; }
.dw-color-desc  { flex: 1; font-size: .72rem; color: #94a3b8; text-align: right; }

/* ── Design-Vorschau ─────────────────────────────────────────────────────────── */
.dw-design-preview {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}
.dw-design-preview-title { font-size: .875rem; font-weight: 700; margin-bottom: .625rem; }

/* ── Layout-Spalten ──────────────────────────────────────────────────────────── */
.dw-layout-cols { display: flex; gap: .625rem; flex-wrap: wrap; }
.dw-col-option {
    cursor: pointer;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: .5rem .75rem;
    text-align: center;
    font-size: .75rem;
    color: #64748b;
    transition: all .15s;
    user-select: none;
}
.dw-col-option input { display: none; }
.dw-col-option:hover { border-color: #a5b4fc; }
.dw-col-option.selected { border-color: #6366f1; color: #4f46e5; background: #eff6ff; }
.dw-col-preview { display: flex; gap: 3px; justify-content: center; margin-bottom: .35rem; }
.dw-col-cell { height: 24px; background: #e2e8f0; border-radius: 2px; }
.dw-col-option.selected .dw-col-cell { background: #c7d2fe; }

/* ── Stats-Grid ──────────────────────────────────────────────────────────────── */
.dw-stats-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(210px,1fr)); gap: .75rem; }
.dw-stat-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: .75rem 1rem;
    gap: .75rem;
    transition: background .12s;
}
.dw-stat-toggle:hover { background: #f1f5f9; }
.dw-stat-info strong { display: block; font-size: .8125rem; color: #1e293b; font-weight: 600; }
.dw-stat-info span   { font-size: .72rem; color: #94a3b8; }

/* ── Sektions-Sortierer ──────────────────────────────────────────────────────── */
.dw-section-list { display: flex; flex-direction: column; gap: .5rem; }
.dw-section-item {
    display: flex;
    align-items: center;
    gap: .875rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: .75rem 1rem;
    cursor: grab;
    transition: background .12s;
}
.dw-section-item:hover    { background: #f1f5f9; }
.dw-section-item.dragging { opacity: .45; }
.dw-section-item.drag-over { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,.2); }
.dw-section-handle { font-size: 1.1rem; color: #94a3b8; user-select: none; }
.dw-section-icon   { font-size: 1.2rem; }
.dw-section-info   { display: flex; flex-direction: column; }
.dw-section-info strong { font-size: .875rem; color: #1e293b; }
.dw-section-info span   { font-size: .75rem;  color: #94a3b8; }

/* ── Checkboxen ──────────────────────────────────────────────────────────────── */
.dw-checkbox-group { display: flex; align-items: center; gap: .5rem; padding: .35rem 0; }
.dw-checkbox-group input[type=checkbox] { width: 16px; height: 16px; cursor: pointer; }
.dw-checkbox-group label { font-size: .8125rem; color: #374151; cursor: pointer; }

/* ── Hinzufügen-Button ───────────────────────────────────────────────────────── */
.dw-add-btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem 1rem;
    border: 1px dashed #94a3b8;
    border-radius: 8px;
    background: none;
    color: #64748b;
    cursor: pointer;
    font-size: .8125rem;
    transition: all .15s;
}
.dw-add-btn:hover { border-color: #6366f1; color: #4f46e5; background: #eff6ff; }

/* ── Label ───────────────────────────────────────────────────────────────────── */
.adm-lbl { font-size: .8125rem; font-weight: 600; color: #374151; display: block; margin-bottom: .3rem; }

/* ── Info-Box ────────────────────────────────────────────────────────────────── */
.dw-info-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: .75rem 1rem;
    font-size: .8125rem;
    color: #1e40af;
    margin-bottom: 1.25rem;
    display: flex;
    gap: .625rem;
    align-items: flex-start;
}

/* ── Spalten-Kopfzeile (Navigation) ─────────────────────────────────────────── */
.dw-nav-cols-header {
    display: grid;
    grid-template-columns: 32px 1fr 2fr auto auto auto;
    gap: .625rem;
    padding: .3rem .875rem;
    font-size: .72rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .3rem;
}
</style>

<!-- ── Page Header ────────────────────────────────────────────────────────── -->
<div class="admin-page-header">
    <div>
        <h2>🧩 Member Dashboard</h2>
        <p>Plugins, Navigation, Design &amp; alle Einstellungen des Mitgliederbereichs</p>
    </div>
    <div class="header-actions">
        <a href="<?php echo SITE_URL; ?>/member" target="_blank" class="btn btn-secondary btn-sm">
            👁️ Live-Ansicht
        </a>
    </div>
</div>

<?php foreach ($messages as $msg):
    $cls = $msg['type'] === 'success' ? 'alert-success' : 'alert-error';
?>
<div class="alert <?php echo $cls; ?>"><?php echo htmlspecialchars($msg['text']); ?></div>
<?php endforeach; ?>

<!-- ── Tab-Navigation ────────────────────────────────────────────────────── -->
<nav class="md-nav" role="tablist" aria-label="Member Dashboard Konfiguration">
    <?php
    $tabConfig = [
        'plugins'    => ['icon' => '🔌', 'label' => 'Plugins',       'desc' => 'Sichtbarkeit der Plugin-Widgets'],
        'navigation' => ['icon' => '🗂️', 'label' => 'Navigation',    'desc' => 'Mitglieder-Sidebar-Menü'],
        'quickstart' => ['icon' => '🚀', 'label' => 'Schnellstart',  'desc' => 'Schnellstart-Buttons'],
        'widgets'    => ['icon' => '📌', 'label' => 'Widgets',       'desc' => 'Info-Widgets (bis zu 6)'],
        'design'     => ['icon' => '🎨', 'label' => 'Design',        'desc' => 'Farben, Layout, Stats'],
        'settings'   => ['icon' => '⚙️', 'label' => 'Einstellungen', 'desc' => 'Registrierung, Sicherheit'],
    ];
    foreach ($tabConfig as $tKey => $tInfo): ?>
    <a href="?tab=<?php echo $tKey; ?>"
       class="md-nav-tab <?php echo $activeTab === $tKey ? 'active' : ''; ?>"
       role="tab" aria-selected="<?php echo $activeTab === $tKey ? 'true' : 'false'; ?>">
        <span class="md-nav-icon"><?php echo $tInfo['icon']; ?></span>
        <span class="md-nav-text">
            <span class="md-nav-label"><?php echo htmlspecialchars($tInfo['label']); ?></span>
            <span class="md-nav-desc"><?php echo htmlspecialchars($tInfo['desc']); ?></span>
        </span>
    </a>
    <?php endforeach; ?>
</nav>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Plugins                                                               -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-plugins" class="dw-panel <?php echo $activeTab === 'plugins' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=plugins">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="plugins">

        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🔌 Plugin-Sichtbarkeit</h3>
                <span class="dw-badge"><?php echo count($dashboardPlugins); ?> Plugins</span>
            </div>
            <p class="dw-card-sub">
                Steuere, welche aktiven CMS-Plugins im Member-Dashboard als Widget erscheinen.
                Deaktivierte Plugins sind für Mitglieder unsichtbar.
            </p>

            <?php if (empty($dashboardPlugins)): ?>
            <div class="empty-state">
                <p style="font-size:2rem;margin:0;">🔌</p>
                <p><strong>Keine aktiven Plugins gefunden</strong></p>
                <p style="color:#64748b;font-size:.875rem;">
                    Aktiviere Plugins im Plugin-Manager, damit sie hier konfiguriert werden können.
                </p>
            </div>
            <?php else: ?>
            <div class="dw-plugin-grid">
                <?php foreach ($dashboardPlugins as $slug => $info): ?>
                <div class="dw-plugin-row">
                    <div class="dw-plugin-info">
                        <span class="dw-plugin-icon"><?php echo $info['icon']; ?></span>
                        <div>
                            <div class="dw-plugin-label"><?php echo htmlspecialchars($info['label']); ?></div>
                            <?php if (!empty($info['desc'])): ?>
                            <div style="font-size:.75rem;color:#94a3b8;margin-top:.1rem;">
                                <?php echo htmlspecialchars($info['desc']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <label class="dw-toggle" title="<?php echo $pluginEnabled[$slug] ? 'Aktiviert' : 'Deaktiviert'; ?>">
                        <input type="checkbox" name="plugin_enabled_<?php echo htmlspecialchars($slug); ?>"
                               <?php echo $pluginEnabled[$slug] ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Sichtbarkeit speichern</button>
        </div>
    </form>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Navigation                                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-navigation" class="dw-panel <?php echo $activeTab === 'navigation' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=navigation" id="navForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="navigation">
        <input type="hidden" name="nav_count" id="navCount" value="<?php echo count($navItems); ?>">

        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🗂️ Sidebar-Navigation der Mitglieder</h3>
                <span class="dw-badge"><?php echo count($navItems); ?> Einträge</span>
            </div>
            <p class="dw-card-sub">
                Konfiguriere die Menüpunkte in der linken Sidebar des Mitgliederbereichs.
                Reihenfolge per Drag &amp; Drop anpassbar. Maximal 20 Einträge.
            </p>

            <div class="dw-info-box">
                ℹ️ <strong>Login-Icon:</strong> Aktiviert = nur für eingeloggte Mitglieder sichtbar.
                <strong>Sichtbar-Icon:</strong> Deaktiviert = Eintrag wird ausgeblendet, bleibt aber gespeichert.
            </div>

            <div class="dw-nav-cols-header">
                <span></span>
                <span>Icon &amp; Bezeichnung</span>
                <span>URL / Pfad</span>
                <span style="text-align:center;">Sichtbar</span>
                <span style="text-align:center;">🔒 Login</span>
                <span></span>
            </div>

            <div class="dw-nav-list" id="navSorter">
                <?php foreach ($navItems as $idx => $navItem): ?>
                <div class="dw-nav-item" draggable="true" data-nav-idx="<?php echo $idx; ?>">
                    <span class="dw-nav-handle" title="Ziehen zum Sortieren">⣿</span>
                    <div style="display:flex;flex-direction:column;gap:.3rem;">
                        <input type="text" name="nav_<?php echo $idx; ?>_icon"
                               value="<?php echo htmlspecialchars($navItem['icon']); ?>"
                               class="dw-nav-input dw-nav-icon-input" placeholder="🏠" maxlength="8"
                               title="Icon (Emoji)">
                        <input type="text" name="nav_<?php echo $idx; ?>_label"
                               value="<?php echo htmlspecialchars($navItem['label']); ?>"
                               class="dw-nav-input" placeholder="Menüpunkt-Name" maxlength="60"
                               title="Bezeichnung">
                    </div>
                    <input type="text" name="nav_<?php echo $idx; ?>_url"
                           value="<?php echo htmlspecialchars($navItem['url']); ?>"
                           class="dw-nav-input" placeholder="/member/..." title="URL-Pfad">
                    <label class="dw-toggle" title="Navigationspunkt anzeigen">
                        <input type="checkbox" name="nav_<?php echo $idx; ?>_visible"
                               <?php echo !empty($navItem['visible']) ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                    <label class="dw-toggle" title="Nur für eingeloggte Mitglieder">
                        <input type="checkbox" name="nav_<?php echo $idx; ?>_login_required"
                               <?php echo !empty($navItem['login_required']) ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                    <button type="button" class="dw-nav-delete" onclick="dwNavDelete(this)" title="Eintrag entfernen">✕</button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="dw-add-btn" onclick="dwNavAdd()">
                ➕ Navigationspunkt hinzufügen
            </button>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Navigation speichern</button>
            <span style="font-size:.78rem;color:#94a3b8;margin-left:1rem;">
                Reihenfolge per Drag &amp; Drop anpassen – dann speichern
            </span>
        </div>
    </form>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Quick-Start                                                           -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-quickstart" class="dw-panel <?php echo $activeTab === 'quickstart' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=quickstart" id="qsForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="quickstart">
        <input type="hidden" name="qs_count" id="qsCount" value="<?php echo count($qsButtons); ?>">

        <div class="admin-card">
            <div class="dw-card-header">
                <h3>🚀 Schnellstart-Buttons</h3>
                <span class="dw-badge"><?php echo count($qsButtons); ?> Buttons</span>
            </div>
            <p class="dw-card-sub">
                Diese Buttons erscheinen prominent im Schnellstart-Bereich der Dashboard-Startseite.
                Bis zu 12 Buttons möglich. Deaktivierte Buttons werden ausgeblendet.
            </p>

            <div class="dw-qs-grid" id="qsGrid">
                <?php foreach ($qsButtons as $qi => $qsBtn): ?>
                <div class="dw-qs-item">
                    <div class="dw-qs-item-head">
                        <strong>Button <?php echo $qi + 1; ?></strong>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <span style="font-size:.72rem;color:#94a3b8;">Sichtbar</span>
                            <label class="dw-toggle" title="Sichtbar">
                                <input type="checkbox" name="qs_<?php echo $qi; ?>_visible"
                                       <?php echo !empty($qsBtn['visible']) ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                            <button type="button" class="dw-qs-delete" onclick="dwQsDelete(this)" title="Entfernen">✕</button>
                        </div>
                    </div>
                    <div class="dw-qs-row">
                        <span class="dw-qs-label">Icon (Emoji)</span>
                        <input type="text" name="qs_<?php echo $qi; ?>_icon"
                               value="<?php echo htmlspecialchars($qsBtn['icon']); ?>"
                               class="dw-qs-input" placeholder="✏️" maxlength="8">
                    </div>
                    <div class="dw-qs-row">
                        <span class="dw-qs-label">Bezeichnung</span>
                        <input type="text" name="qs_<?php echo $qi; ?>_label"
                               value="<?php echo htmlspecialchars($qsBtn['label']); ?>"
                               class="dw-qs-input" placeholder="Button-Text" maxlength="60">
                    </div>
                    <div class="dw-qs-row">
                        <span class="dw-qs-label">URL / Pfad</span>
                        <input type="text" name="qs_<?php echo $qi; ?>_url"
                               value="<?php echo htmlspecialchars($qsBtn['url']); ?>"
                               class="dw-qs-input" placeholder="/member/...">
                    </div>
                    <div class="dw-qs-row">
                        <span class="dw-qs-label">Farbe</span>
                        <div class="dw-color-pills">
                            <?php
                            $btnColorLabels = ['primary'=>'Blau','secondary'=>'Grau','success'=>'Grün','danger'=>'Rot'];
                            $currentColor   = $qsBtn['color'] ?? 'secondary';
                            foreach ($btnColorLabels as $colorOpt => $colorName):
                                $sel = $currentColor === $colorOpt ? 'selected' : '';
                            ?>
                            <label class="dw-color-pill <?php echo $colorOpt; ?> <?php echo $sel; ?>">
                                <input type="radio" name="qs_<?php echo $qi; ?>_color"
                                       value="<?php echo $colorOpt; ?>" style="display:none;"
                                       <?php echo $currentColor === $colorOpt ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($colorName); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="dw-add-btn" onclick="dwQsAdd()">
                ➕ Button hinzufügen
            </button>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Schnellstart speichern</button>
        </div>
    </form>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Widgets                                                               -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-widgets" class="dw-panel <?php echo $activeTab === 'widgets' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=widgets">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="widgets">

        <div class="admin-card">
            <div class="dw-card-header">
                <h3>📌 Info-Widgets</h3>
            </div>
            <p class="dw-card-sub">
                Bis zu <strong>6 eigene Info-Widgets</strong> für das Member-Dashboard.
                Widgets ohne Überschrift und Text werden automatisch ausgeblendet.
            </p>

            <div class="dw-grid">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                <?php $w = $widgets[$i]; $hasContent = !empty($w['title']) || !empty($w['content']); ?>
                <div class="admin-card" style="margin:0;">
                    <div class="dw-card-header">
                        <h3>Widget <?php echo $i; ?></h3>
                        <span class="dw-badge <?php echo $hasContent ? 'active' : ''; ?>">
                            <?php echo $hasContent ? 'konfiguriert' : 'leer'; ?>
                        </span>
                    </div>

                    <div class="form-group">
                        <label class="adm-lbl">Icon (Emoji)</label>
                        <div class="dw-icon-row">
                            <input type="text" name="widget_<?php echo $i; ?>_icon"
                                   value="<?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>"
                                   placeholder="📌" maxlength="8"
                                   class="form-control" style="max-width:80px;text-align:center;font-size:1.1rem;"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                            <span class="dw-icon-preview" id="prev<?php echo $i; ?>icon">
                                <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="adm-lbl">Überschrift</label>
                        <input type="text" name="widget_<?php echo $i; ?>_title" class="form-control"
                               value="<?php echo htmlspecialchars($w['title']); ?>"
                               placeholder="Widget-Überschrift" maxlength="120"
                               oninput="dwUpdatePreview(<?php echo $i; ?>)">
                    </div>

                    <div class="form-group">
                        <label class="adm-lbl">Beschreibung</label>
                        <textarea name="widget_<?php echo $i; ?>_content" class="form-control"
                                  rows="3" placeholder="Kurze Info oder Ankündigung …"
                                  oninput="dwUpdatePreview(<?php echo $i; ?>)"><?php echo htmlspecialchars($w['content']); ?></textarea>
                        <p class="dw-hint">Erlaubt: &lt;p&gt; &lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;li&gt;</p>
                    </div>

                    <div class="form-group">
                        <label class="adm-lbl">Weblink (optional)</label>
                        <input type="url" name="widget_<?php echo $i; ?>_link" class="form-control"
                               value="<?php echo htmlspecialchars($w['link']); ?>"
                               placeholder="https://…"
                               oninput="dwUpdatePreview(<?php echo $i; ?>)">
                    </div>

                    <div class="form-group">
                        <label class="adm-lbl">Button-Text (optional)</label>
                        <input type="text" name="widget_<?php echo $i; ?>_btntext" class="form-control"
                               value="<?php echo htmlspecialchars($w['btntext']); ?>"
                               placeholder="z. B. »Mehr erfahren«" maxlength="60"
                               oninput="dwUpdatePreview(<?php echo $i; ?>)">
                        <p class="dw-hint">Wird nur angezeigt wenn auch ein Weblink eingetragen ist.</p>
                    </div>

                    <!-- Live-Vorschau -->
                    <div class="dw-preview-box" id="prev<?php echo $i; ?>box"
                         style="<?php echo !$hasContent ? 'display:none;' : ''; ?>">
                        <div class="preview-icon" id="prev<?php echo $i; ?>iconbox">
                            <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                        </div>
                        <h4 id="prev<?php echo $i; ?>title"><?php echo htmlspecialchars($w['title']); ?></h4>
                        <div id="prev<?php echo $i; ?>body" style="font-size:.8125rem;color:#78350f;">
                            <?php echo strip_tags($w['content'], '<p><a><strong><em><br><ul><ol><li>'); ?>
                        </div>
                        <div style="margin-top:.75rem;">
                            <a id="prev<?php echo $i; ?>btn"
                               href="<?php echo htmlspecialchars($w['link']); ?>"
                               style="display:<?php echo (!empty($w['link'])&&!empty($w['btntext']))?'inline-block':'none';?>;
                                      padding:.3rem .8rem;background:#92400e;color:#fff;
                                      border-radius:5px;font-size:.75rem;font-weight:600;text-decoration:none;">
                                <?php echo htmlspecialchars($w['btntext']); ?>
                            </a>
                        </div>
                    </div>
                    <p class="dw-empty-note" id="prev<?php echo $i; ?>note"
                       style="<?php echo $hasContent ? 'display:none;' : ''; ?>">
                        Widget ist leer – Überschrift oder Text eintragen.
                    </p>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Widgets speichern</button>
        </div>
    </form>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Design & Layout                                                       -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-design" class="dw-panel <?php echo $activeTab === 'design' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=design" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="design">
        <input type="hidden" name="dashboard_section_order" id="sectionOrderInput"
               value="<?php echo htmlspecialchars($layoutOrder); ?>">

        <div class="dw-grid-2">

            <!-- Farbschema -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>🎨 Farbschema</h3></div>
                <p class="dw-card-sub">Passe Farben des Mitglieder-Dashboards individuell an.</p>

                <div class="dw-color-grid">
                    <?php
                    $colorDefs = [
                        'primary'  => ['label' => 'Primärfarbe',        'desc' => 'Buttons, Links, Akzente'],
                        'accent'   => ['label' => 'Akzentfarbe',         'desc' => 'Badges, Hover-Effekte'],
                        'bg'       => ['label' => 'Seitenhintergrund',   'desc' => 'Hintergrund der Seite'],
                        'card_bg'  => ['label' => 'Karten-Hintergrund',  'desc' => 'Hintergrund der Karten'],
                        'text'     => ['label' => 'Textfarbe',           'desc' => 'Haupt-Textfarbe'],
                        'border'   => ['label' => 'Rahmenfarbe',         'desc' => 'Trennlinien und Borders'],
                    ];
                    foreach ($colorDefs as $ckey => $clabel): ?>
                    <div class="dw-color-row">
                        <input type="color" name="color_<?php echo $ckey; ?>"
                               id="dw-color-<?php echo $ckey; ?>"
                               value="<?php echo htmlspecialchars($designColors[$ckey]); ?>"
                               oninput="dwUpdateDesignPreview()">
                        <div>
                            <div class="dw-color-label"><?php echo htmlspecialchars($clabel['label']); ?></div>
                            <div class="dw-color-hex" id="hex-<?php echo $ckey; ?>"><?php echo htmlspecialchars($designColors[$ckey]); ?></div>
                        </div>
                        <div class="dw-color-desc"><?php echo htmlspecialchars($clabel['desc']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Live-Vorschau -->
                <div class="dw-design-preview" id="dwDesignPreview"
                     style="background:<?php echo htmlspecialchars($designColors['bg']); ?>;
                            border-color:<?php echo htmlspecialchars($designColors['border']); ?>;">
                    <div class="dw-design-preview-title"
                         style="color:<?php echo htmlspecialchars($designColors['text']); ?>;">
                        Live-Vorschau Member Dashboard
                    </div>
                    <div style="display:flex;gap:.625rem;flex-wrap:wrap;align-items:center;">
                        <div id="dpv-card"
                             style="background:<?php echo htmlspecialchars($designColors['card_bg']); ?>;
                                    border:1px solid <?php echo htmlspecialchars($designColors['border']); ?>;
                                    border-radius:6px;padding:.5rem .875rem;
                                    font-size:.8125rem;
                                    color:<?php echo htmlspecialchars($designColors['text']); ?>;">
                            Beispiel-Karte
                        </div>
                        <button type="button" id="dpv-btn-primary"
                                style="background:<?php echo htmlspecialchars($designColors['primary']); ?>;
                                       color:#fff;border:none;border-radius:5px;
                                       padding:.4rem .9rem;font-size:.8125rem;cursor:default;">
                            Primär
                        </button>
                        <button type="button" id="dpv-btn-accent"
                                style="background:<?php echo htmlspecialchars($designColors['accent']); ?>;
                                       color:#fff;border:none;border-radius:5px;
                                       padding:.4rem .9rem;font-size:.8125rem;cursor:default;">
                            Akzent
                        </button>
                    </div>
                </div>
            </div>

            <!-- Layout & Logo -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>🗂️ Layout &amp; Logo</h3></div>
                <p class="dw-card-sub">Dashboard-Logo und Anzahl der Widget-Spalten.</p>

                <div class="form-group">
                    <label class="adm-lbl">Dashboard-Logo</label>
                    <input type="file" name="member_dashboard_logo" accept="image/*"
                           class="form-control" style="padding:.35rem 0;border:none;background:none;">
                    <p class="dw-hint">SVG oder PNG, ca. 40 px Höhe – erscheint neben dem Seitentitel.</p>
                    <?php if (!empty($memberLogo)): ?>
                    <div style="margin-top:.75rem;display:flex;align-items:center;gap:.75rem;">
                        <img src="<?php echo htmlspecialchars($memberLogo); ?>" alt="Dashboard Logo"
                             style="max-height:44px;background:#1e293b;padding:4px;border-radius:6px;">
                        <button type="submit" name="delete_logo" class="btn btn-danger btn-sm">
                            ✕ Logo entfernen
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="adm-lbl">Widget-Spalten</label>
                    <div class="dw-layout-cols" id="dwColOptions">
                        <?php foreach ([1=>'1 Spalte',2=>'2 Spalten',3=>'3 Spalten',4=>'4 Spalten'] as $n => $lbl): ?>
                        <label class="dw-col-option <?php echo $layoutCols === $n ? 'selected' : ''; ?>"
                               onclick="dwSelectCols(this, <?php echo $n; ?>)">
                            <input type="radio" name="dashboard_columns" value="<?php echo $n; ?>"
                                   <?php echo $layoutCols === $n ? 'checked' : ''; ?>>
                            <div class="dw-col-preview">
                                <?php for ($c = 0; $c < $n; $c++): ?>
                                <div class="dw-col-cell" style="width:<?php echo round(48/$n); ?>px;"></div>
                                <?php endfor; ?>
                            </div>
                            <span><?php echo $lbl; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statuswidgets -->
        <div class="admin-card" style="margin-top:1.25rem;">
            <div class="dw-card-header"><h3>📊 Statuswidgets</h3></div>
            <p class="dw-card-sub">
                Wähle, welche Zähler und Status-Kacheln auf dem Mitglieder-Dashboard angezeigt werden.
            </p>
            <div class="dw-stats-grid">
                <?php
                $statsConfig = [
                    'subscription'  => ['icon' => '⭐', 'label' => 'Abo-Status',           'desc' => 'Aktuelles Abonnement anzeigen'],
                    'activity'      => ['icon' => '📈', 'label' => 'Aktivitäts-Score',      'desc' => 'Login-Strähne & Punkte'],
                    'notifications' => ['icon' => '🔔', 'label' => 'Benachrichtigungen',    'desc' => 'Ungelesene Meldungen'],
                    'messages'      => ['icon' => '✉️', 'label' => 'Nachrichten',            'desc' => 'Ungelesene Nachrichten'],
                    'files'         => ['icon' => '📂', 'label' => 'Meine Dateien',          'desc' => 'Anzahl hochgeladener Dateien'],
                    'projects'      => ['icon' => '📋', 'label' => 'Projekte',               'desc' => 'Aktive Projekte'],
                ];
                foreach ($statsConfig as $sk => $sInfo): ?>
                <div class="dw-stat-toggle">
                    <div class="dw-stat-info">
                        <strong><?php echo $sInfo['icon']; ?> <?php echo htmlspecialchars($sInfo['label']); ?></strong>
                        <span><?php echo htmlspecialchars($sInfo['desc']); ?></span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="stats_show_<?php echo $sk; ?>"
                               <?php echo $statsShow[$sk] ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sektions-Reihenfolge -->
        <div class="admin-card" style="margin-top:1.25rem;">
            <div class="dw-card-header">
                <h3>↕️ Sektionen anordnen</h3>
                <span style="font-size:.75rem;color:#94a3b8;">Ziehen &amp; Ablegen zum Umsortieren</span>
            </div>
            <p class="dw-card-sub">
                Bestimme die Reihenfolge der Bereiche auf der Mitglieder-Startseite.
            </p>
            <div class="dw-section-list" id="sectionSorter">
                <?php foreach ($orderedSections as $sKey):
                    $sInfo = $sectionLabels[$sKey] ?? ['icon'=>'🔲','label'=>$sKey,'desc'=>'']; ?>
                <div class="dw-section-item" draggable="true" data-section="<?php echo htmlspecialchars($sKey); ?>">
                    <span class="dw-section-handle">⣿</span>
                    <span class="dw-section-icon"><?php echo $sInfo['icon']; ?></span>
                    <div class="dw-section-info">
                        <strong><?php echo htmlspecialchars($sInfo['label']); ?></strong>
                        <span><?php echo htmlspecialchars($sInfo['desc']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Design &amp; Layout speichern</button>
        </div>
    </form>
</div>


<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAB: Einstellungen                                                         -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div id="dw-panel-settings" class="dw-panel <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
    <form method="POST" action="?tab=settings">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="active_tab" value="settings">

        <div class="dw-grid-2">

            <!-- Begrüßung -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>👋 Begrüßung</h3></div>
                <div class="form-group">
                    <label class="adm-lbl" for="set-greeting">Begrüßungs-Text</label>
                    <input type="text" id="set-greeting" name="member_dashboard_greeting"
                           class="form-control"
                           value="<?php echo htmlspecialchars($settingsData['greeting']); ?>"
                           placeholder="Guten Tag, {name}!" maxlength="100">
                    <p class="dw-hint"><code>{name}</code> wird durch den Vornamen des Mitglieds ersetzt.</p>
                </div>
                <div class="form-group">
                    <label class="adm-lbl" for="set-welcome">Willkommens-Nachricht</label>
                    <textarea id="set-welcome" name="member_dashboard_welcome_text"
                              class="form-control" rows="3"
                              placeholder="Schön, dass du dabei bist! …"><?php echo htmlspecialchars($settingsData['welcome_text']); ?></textarea>
                    <p class="dw-hint">Erscheint unterhalb des Begrüßungstexts. Einfaches HTML erlaubt.</p>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Willkommens-Banner anzeigen</strong>
                        <span>Blendet Begrüßungsbereich auf der Startseite ein</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_dashboard_show_welcome"
                               <?php echo $settingsData['show_welcome'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Weiterleitungen -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>🔀 Weiterleitungen</h3></div>
                <div class="form-group">
                    <label class="adm-lbl" for="set-redir-login">Nach dem Login</label>
                    <input type="text" id="set-redir-login" name="member_redirect_after_login"
                           class="form-control"
                           value="<?php echo htmlspecialchars($settingsData['redirect_after_login']); ?>"
                           placeholder="/member">
                    <p class="dw-hint">Wohin wird nach erfolgreicher Anmeldung weitergeleitet?</p>
                </div>
                <div class="form-group">
                    <label class="adm-lbl" for="set-redir-logout">Nach dem Logout</label>
                    <input type="text" id="set-redir-logout" name="member_redirect_after_logout"
                           class="form-control"
                           value="<?php echo htmlspecialchars($settingsData['redirect_after_logout']); ?>"
                           placeholder="/">
                </div>
                <div class="form-group">
                    <label class="adm-lbl" for="set-redir-denied">Zugriff verweigert</label>
                    <input type="text" id="set-redir-denied" name="member_redirect_access_denied"
                           class="form-control"
                           value="<?php echo htmlspecialchars($settingsData['redirect_access_denied']); ?>"
                           placeholder="/member">
                    <p class="dw-hint">Nicht eingeloggte Besucher werden hierhin weitergeleitet.</p>
                </div>
            </div>
        </div>

        <div class="dw-grid-2" style="margin-top:1.25rem;">

            <!-- Registrierung & Profil -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>👤 Registrierung &amp; Profil</h3></div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Registrierung aktiv</strong>
                        <span>Neue Mitglieder können sich registrieren</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_registration_open"
                               <?php echo $settingsData['registration_open'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>E-Mail-Verifizierung</strong>
                        <span>Konto erst nach E-Mail-Bestätigung aktiv</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_email_verification"
                               <?php echo $settingsData['email_verification'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Avatar-Upload erlauben</strong>
                        <span>Mitglieder können ein Profilbild hochladen</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_avatar_upload_enabled"
                               <?php echo $settingsData['avatar_upload_enabled'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Profile öffentlich (Standard)</strong>
                        <span>Neue Profile sind für Besucher sichtbar</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_profile_public_default"
                               <?php echo $settingsData['profile_public_default'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Profil bearbeiten erlauben</strong>
                        <span>Mitglieder können ihre Profildaten selbst ändern</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_profile_edit_enabled"
                               <?php echo $settingsData['profile_edit_enabled'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
                <div class="dw-toggle-row">
                    <div class="dw-toggle-info">
                        <strong>Konto-Löschung erlauben</strong>
                        <span>Mitglieder können ihr Konto selbst löschen</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_profile_delete_enabled"
                               <?php echo $settingsData['profile_delete_enabled'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Sicherheit -->
            <div class="admin-card">
                <div class="dw-card-header"><h3>🔒 Sicherheit</h3></div>

                <div class="form-group">
                    <label class="adm-lbl" for="set-max-logins">Max. Login-Versuche</label>
                    <input type="number" id="set-max-logins" name="member_max_login_attempts"
                           class="form-control" style="max-width:130px;"
                           value="<?php echo (int)$settingsData['max_login_attempts']; ?>"
                           min="1" max="20">
                    <p class="dw-hint">Konto wird nach dieser Anzahl Fehlversuche temporär gesperrt (1–20).</p>
                </div>

                <div class="form-group">
                    <label class="adm-lbl" for="set-session">Session-Timeout (Minuten)</label>
                    <input type="number" id="set-session" name="member_session_timeout"
                           class="form-control" style="max-width:130px;"
                           value="<?php echo (int)$settingsData['session_timeout']; ?>"
                           min="5" max="10080">
                    <p class="dw-hint">Inaktive Sessions nach X Minuten beenden (5 Min – 7 Tage / 10080 Min).</p>
                </div>

                <div class="dw-toggle-row" style="margin-top:.5rem;">
                    <div class="dw-toggle-info">
                        <strong>E-Mail-Benachrichtigungen</strong>
                        <span>System-E-Mails an Mitglieder versenden</span>
                    </div>
                    <label class="dw-toggle">
                        <input type="checkbox" name="member_notifications_email_enabled"
                               <?php echo $settingsData['notifications_email_enabled'] === '1' ? 'checked' : ''; ?>>
                        <span class="dw-toggle-slider"></span>
                    </label>
                </div>

                <div class="form-group" style="margin-top:.875rem;">
                    <label class="adm-lbl" for="set-noti-from">Absender-E-Mail für Benachrichtigungen</label>
                    <input type="email" id="set-noti-from" name="member_notifications_email_from"
                           class="form-control"
                           value="<?php echo htmlspecialchars($settingsData['notifications_email_from']); ?>"
                           placeholder="noreply@meine-seite.de">
                    <p class="dw-hint">Leer = Standard-E-Mail aus den globalen Einstellungen</p>
                </div>
            </div>
        </div>

        <!-- Medien-Limits -->
        <div class="admin-card" style="margin-top:1.25rem;">
            <div class="dw-card-header"><h3>📂 Medien &amp; Dateilimits</h3></div>
            <p class="dw-card-sub">Upload-Beschränkungen und erlaubte Dateitypen für Mitglieder.</p>

            <div class="dw-grid-2" style="gap:2rem;align-items:start;">
                <div class="form-group" style="margin:0;">
                    <label class="adm-lbl" for="set-max-mb">Max. Upload-Größe pro Datei (MB)</label>
                    <input type="number" id="set-max-mb" name="member_media_max_upload_mb"
                           class="form-control" style="max-width:130px;"
                           value="<?php echo (int)$settingsData['media_max_upload_mb']; ?>"
                           min="1" max="500">
                    <p class="dw-hint">Gilt für alle Datei-Uploads von Mitgliedern (1–500 MB).</p>
                </div>
                <div>
                    <label class="adm-lbl">Erlaubte Dateitypen</label>
                    <?php
                    $mediaTypeLabels = [
                        'images'    => '🖼️ Bilder (JPG, PNG, GIF, WebP, SVG)',
                        'documents' => '📄 Dokumente (PDF, DOC, XLS, PPT)',
                        'videos'    => '🎬 Videos (MP4, WebM)',
                        'audio'     => '🎵 Audio (MP3, WAV, OGG)',
                    ];
                    foreach ($mediaTypeLabels as $typeKey => $typeLabel): ?>
                    <div class="dw-checkbox-group">
                        <input type="checkbox" id="mtype-<?php echo $typeKey; ?>"
                               name="member_media_type_<?php echo $typeKey; ?>"
                               <?php echo in_array($typeKey, $allowedMediaTypes, true) ? 'checked' : ''; ?>>
                        <label for="mtype-<?php echo $typeKey; ?>"><?php echo htmlspecialchars($typeLabel); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="admin-card" style="margin-top:1.25rem;">
            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </div>
    </form>
</div>


<script>
/* ── Widget Live-Vorschau ─────────────────────────────────────────────────── */
function dwUpdatePreview(i) {
    var get = function(n) {
        var el = document.querySelector('[name="widget_'+i+'_'+n+'"]');
        return el ? el.value : '';
    };
    var icon    = get('icon')    || '📌';
    var title   = get('title')   || '';
    var content = get('content') || '';
    var link    = get('link')    || '';
    var btntext = get('btntext') || '';

    var set = function(id, prop, val) { var el=document.getElementById(id); if(el) el[prop]=val; };
    set('prev'+i+'icon',    'textContent', icon);
    set('prev'+i+'iconbox', 'textContent', icon);
    set('prev'+i+'title',   'textContent', title);
    set('prev'+i+'body',    'textContent', content);

    var box  = document.getElementById('prev'+i+'box');
    var note = document.getElementById('prev'+i+'note');
    var btn  = document.getElementById('prev'+i+'btn');
    var has  = title.trim() || content.trim();

    if (box)  box.style.display  = has ? '' : 'none';
    if (note) note.style.display = has ? 'none' : '';
    if (btn) {
        if (link && btntext) {
            btn.href = link; btn.textContent = btntext; btn.style.display = 'inline-block';
        } else {
            btn.style.display = 'none';
        }
    }
}

/* ── Spalten-Auswahl ──────────────────────────────────────────────────────── */
function dwSelectCols(el, n) {
    document.querySelectorAll('.dw-col-option').forEach(function(o) { o.classList.remove('selected'); });
    el.classList.add('selected');
    var radio = el.querySelector('input[type=radio]');
    if (radio) radio.checked = true;
}

/* ── Design: Live-Farb-Vorschau ───────────────────────────────────────────── */
function dwUpdateDesignPreview() {
    var c = {};
    ['primary','accent','bg','card_bg','text','border'].forEach(function(k) {
        var el  = document.getElementById('dw-color-'+k);
        c[k]    = el ? el.value : '';
        var hex = document.getElementById('hex-'+k);
        if (hex && el) hex.textContent = el.value;
    });
    var prev = document.getElementById('dwDesignPreview');
    if (!prev) return;
    prev.style.background  = c.bg;
    prev.style.borderColor = c.border;
    var title = prev.querySelector('.dw-design-preview-title');
    if (title) title.style.color = c.text;
    var card = document.getElementById('dpv-card');
    if (card) {
        card.style.background   = c.card_bg;
        card.style.borderColor  = c.border;
        card.style.color        = c.text;
    }
    var bp = document.getElementById('dpv-btn-primary');
    if (bp) bp.style.background = c.primary;
    var ba = document.getElementById('dpv-btn-accent');
    if (ba) ba.style.background = c.accent;
}

/* ── Drag & Drop: Sektionen ───────────────────────────────────────────────── */
(function() {
    var list    = document.getElementById('sectionSorter');
    var orderIn = document.getElementById('sectionOrderInput');
    if (!list || !orderIn) return;
    var dragged = null;

    function updateHidden() {
        orderIn.value = Array.from(list.querySelectorAll('.dw-section-item'))
                             .map(function(el) { return el.dataset.section; })
                             .join(',');
    }

    list.addEventListener('dragstart', function(e) {
        dragged = e.target.closest('.dw-section-item');
        if (dragged) { dragged.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; }
    });
    list.addEventListener('dragend', function() {
        if (dragged) { dragged.classList.remove('dragging'); dragged = null; }
        list.querySelectorAll('.dw-section-item').forEach(function(el) { el.classList.remove('drag-over'); });
        updateHidden();
    });
    list.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('.dw-section-item');
        if (target && dragged && target !== dragged) {
            list.querySelectorAll('.dw-section-item').forEach(function(el) { el.classList.remove('drag-over'); });
            target.classList.add('drag-over');
            var rect = target.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) { list.insertBefore(dragged, target); }
            else { list.insertBefore(dragged, target.nextSibling); }
        }
    });
    list.addEventListener('drop', function(e) {
        e.preventDefault();
        list.querySelectorAll('.dw-section-item').forEach(function(el) { el.classList.remove('drag-over'); });
        updateHidden();
    });
})();

/* ── Quick-Start: Farb-Pills ──────────────────────────────────────────────── */
document.addEventListener('click', function(e) {
    var pill = e.target.closest('.dw-color-pill');
    if (!pill) return;
    var parent = pill.closest('.dw-qs-row');
    if (!parent) return;
    parent.querySelectorAll('.dw-color-pill').forEach(function(p) { p.classList.remove('selected'); });
    pill.classList.add('selected');
    var radio = pill.querySelector('input[type=radio]');
    if (radio) radio.checked = true;
});

/* ── Navigation: Eintrag hinzufügen ──────────────────────────────────────── */
function dwNavAdd() {
    var list  = document.getElementById('navSorter');
    var count = document.getElementById('navCount');
    if (!list || !count) return;
    var idx = parseInt(count.value, 10);
    if (idx >= 20) { alert('Maximal 20 Navigationspunkte erlaubt.'); return; }

    var item = document.createElement('div');
    item.className = 'dw-nav-item';
    item.draggable = true;
    item.dataset.navIdx = idx;
    item.innerHTML =
        '<span class="dw-nav-handle" title="Ziehen zum Sortieren">⣿</span>'
      + '<div style="display:flex;flex-direction:column;gap:.3rem;">'
      +   '<input type="text" name="nav_'+idx+'_icon" value="🔗" class="dw-nav-input dw-nav-icon-input" placeholder="🔗" maxlength="8" title="Icon">'
      +   '<input type="text" name="nav_'+idx+'_label" value="" class="dw-nav-input" placeholder="Menüpunkt-Name" maxlength="60" title="Bezeichnung">'
      + '</div>'
      + '<input type="text" name="nav_'+idx+'_url" value="" class="dw-nav-input" placeholder="/member/..." title="URL">'
      + '<label class="dw-toggle" title="Sichtbar"><input type="checkbox" name="nav_'+idx+'_visible" checked><span class="dw-toggle-slider"></span></label>'
      + '<label class="dw-toggle" title="Login erforderlich"><input type="checkbox" name="nav_'+idx+'_login_required" checked><span class="dw-toggle-slider"></span></label>'
      + '<button type="button" class="dw-nav-delete" onclick="dwNavDelete(this)" title="Eintrag entfernen">✕</button>';
    list.appendChild(item);
    count.value = idx + 1;
    item.querySelector('input[type=text]').focus();
}

function dwNavDelete(btn) {
    if (!confirm('Navigationspunkt wirklich entfernen?')) return;
    btn.closest('.dw-nav-item').remove();
    var count = document.getElementById('navCount');
    var items = document.querySelectorAll('#navSorter .dw-nav-item');
    if (count) count.value = items.length;
    items.forEach(function(item, i) {
        item.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/nav_\d+_/, 'nav_'+i+'_');
        });
    });
}

/* ── Quick-Start: Button hinzufügen ──────────────────────────────────────── */
function dwQsAdd() {
    var grid  = document.getElementById('qsGrid');
    var count = document.getElementById('qsCount');
    if (!grid || !count) return;
    var idx = parseInt(count.value, 10);
    if (idx >= 12) { alert('Maximal 12 Schnellstart-Buttons erlaubt.'); return; }

    var colorNames = {primary:'Blau',secondary:'Grau',success:'Grün',danger:'Rot'};
    var pills = Object.keys(colorNames).map(function(c) {
        var sel = c === 'secondary' ? 'selected' : '';
        return '<label class="dw-color-pill '+c+' '+sel+'">'
             + '<input type="radio" name="qs_'+idx+'_color" value="'+c+'" style="display:none;"'+(c==='secondary'?' checked':''+'>')
             + colorNames[c]+'</label>';
    }).join('');

    var item = document.createElement('div');
    item.className = 'dw-qs-item';
    item.innerHTML =
        '<div class="dw-qs-item-head">'
      + '<strong>Button '+(idx+1)+'</strong>'
      + '<div style="display:flex;align-items:center;gap:.5rem;">'
      + '<span style="font-size:.72rem;color:#94a3b8;">Sichtbar</span>'
      + '<label class="dw-toggle"><input type="checkbox" name="qs_'+idx+'_visible" checked><span class="dw-toggle-slider"></span></label>'
      + '<button type="button" class="dw-qs-delete" onclick="dwQsDelete(this)">✕</button>'
      + '</div></div>'
      + '<div class="dw-qs-row"><span class="dw-qs-label">Icon (Emoji)</span>'
      +   '<input type="text" name="qs_'+idx+'_icon" value="🔗" class="dw-qs-input" placeholder="🔗" maxlength="8"></div>'
      + '<div class="dw-qs-row"><span class="dw-qs-label">Bezeichnung</span>'
      +   '<input type="text" name="qs_'+idx+'_label" value="" class="dw-qs-input" placeholder="Button-Text" maxlength="60"></div>'
      + '<div class="dw-qs-row"><span class="dw-qs-label">URL / Pfad</span>'
      +   '<input type="text" name="qs_'+idx+'_url" value="" class="dw-qs-input" placeholder="/member/..."></div>'
      + '<div class="dw-qs-row"><span class="dw-qs-label">Farbe</span>'
      +   '<div class="dw-color-pills">'+pills+'</div></div>';
    grid.appendChild(item);
    count.value = idx + 1;
}

function dwQsDelete(btn) {
    if (!confirm('Button wirklich entfernen?')) return;
    btn.closest('.dw-qs-item').remove();
    var count = document.getElementById('qsCount');
    if (count) count.value = document.querySelectorAll('#qsGrid .dw-qs-item').length;
}
</script>

<?php renderAdminLayoutEnd(); ?>
