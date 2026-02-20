<?php
/**
 * Admin: Member Dashboard – Verwaltung
 *
 * URL: /admin/design-dashboard-widgets
 * Tabs: plugins | widgets | layout | design
 *
 * Tabs:
 *   plugins  – Welche CMS-Plugins im Member-Dashboard sichtbar sind
 *   widgets  – Bis zu 3 Info-Widgets (Icon, Titel, Beschreibung, Link, Buttontext)
 *   layout   – Spaltenanzahl, Reihenfolge der Sektionen
 *   design   – Farbgebung des Member-Dashboards
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

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

$security  = Security::instance();
$db        = Database::instance();
$user      = Auth::instance()->getCurrentUser();
$messages  = [];

// ── Aktiver Tab ───────────────────────────────────────────────────────────────
$activeTab = in_array($_GET['tab'] ?? '', ['plugins', 'widgets', 'layout', 'design'], true)
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

// ── Verfügbare Plugins im Dashboard ──────────────────────────────────────────
// Lädt alle CMS-Plugins, die aktiv sind, und stellt sie zur Auswahl, 
// ob sie im Member-Dashboard angezeigt werden sollen.

$dashboardPlugins = [];
if (class_exists('\\CMS\\PluginManager')) {
    $pm = \CMS\PluginManager::instance();
    $allPlugins = $pm->getAvailablePlugins();
    
    // Default-Icons Mapping based on common slugs (optional enhancement)
    $iconMap = [
        'booking'     => '📅',
        'calendar'    => '📅',
        'shop'        => '🛒',
        'market'      => '🛒',
        'marketplace' => '🛒',
        'message'     => '✉️',
        'chat'        => '💬',
        'project'     => '📋',
        'task'        => '✅',
        'premium'     => '⭐',
        'promo'       => '🎁',
        'support'     => '🎧',
        'ticket'      => '🎫',
        'forum'       => '💬',
        'expert'      => '👨‍💼',
        'member'      => '👤',
        'profile'     => '🆔',
    ];

    foreach ($allPlugins as $slug => $data) {
        // Nur aktive Plugins berücksichtigen
        if (isset($data['active']) && $data['active'] === true) {
            // Versuche ein passendes Icon zu finden
            $icon = '🧩'; // Default
            foreach ($iconMap as $key => $mapIcon) {
                if (stripos($slug, $key) !== false || stripos($data['name'], $key) !== false) {
                    $icon = $mapIcon;
                    break;
                }
            }
            
            $dashboardPlugins[$slug] = [
                'label' => $data['name'],
                'icon'  => $icon,
                'desc'  => $data['description'] ?? ''
            ];
        }
    }
}

// Fallback, falls keine Plugins aktiv oder PluginManager nicht verfügbar (sollte nicht passieren)
if (empty($dashboardPlugins)) {
    // Behalte leeres Array oder Hardcoded Fallback nur wenn nötig
    // $dashboardPlugins['core'] = ['label' => 'Core Module', 'icon' => '⚙️'];
}

// ── POST-Verarbeitung ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTab = $security->sanitize($_POST['active_tab'] ?? 'widgets', 'text');
    
    // Check CSRF first for all actions
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'dashboard_widgets')) {
        $messages[] = ['type' => 'error', 'text' => 'Ungültiger Sicherheits-Token.'];
    } else {

        // Handle File Upload for Layout Tab (Logo)
        if ($postTab === 'layout' && isset($_FILES['member_dashboard_logo']) && $_FILES['member_dashboard_logo']['size'] > 0) {
            if ($_FILES['member_dashboard_logo']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['member_dashboard_logo']['name'], PATHINFO_EXTENSION);
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
                
                if (in_array(strtolower($ext), $allowedExts)) {
                   $filename = 'member-logo-' . time() . '.' . $ext;
                   // Upload to assets/uploads/logos
                   $uploadRel = '/assets/uploads/logos/';
                   $uploadPath = dirname(__DIR__) . $uploadRel; 
                   
                   if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
                   
                   if (move_uploaded_file($_FILES['member_dashboard_logo']['tmp_name'], $uploadPath . $filename)) {
                       dw_save_setting($db, 'member_dashboard_logo', SITE_URL . $uploadRel . $filename);
                       $messages[] = ['type' => 'success', 'text' => 'Logo erfolgreich hochgeladen.'];
                   } else {
                       $messages[] = ['type' => 'error', 'text' => 'Fehler beim Verschieben der Datei.'];
                   }
                } else {
                   $messages[] = ['type' => 'error', 'text' => 'Ungültiges Dateiformat.'];
                }
            }
        } else if ($postTab === 'layout' && isset($_POST['delete_logo'])) {
            dw_save_setting($db, 'member_dashboard_logo', '');
            $messages[] = ['type' => 'success', 'text' => 'Logo entfernt.'];
        }
        // ── Tab: Plugins ──────────────────────────────────────────────────────
        if ($postTab === 'plugins') {
            foreach (array_keys($dashboardPlugins) as $slug) {
                $enabled = isset($_POST["plugin_enabled_{$slug}"]) ? '1' : '0';
                dw_save_setting($db, "member_dashboard_plugin_{$slug}", $enabled);
            }
            $messages[] = ['type' => 'success', 'text' => 'Plugin-Sichtbarkeit gespeichert.'];
        }

        // ── Tab: Widgets ──────────────────────────────────────────────────────
        if ($postTab === 'widgets') {
            for ($i = 1; $i <= 4; $i++) {
                $icon    = $security->sanitize(trim($_POST["widget_{$i}_icon"]    ?? ''), 'text');
                $title   = $security->sanitize(trim($_POST["widget_{$i}_title"]   ?? ''), 'text');
                $link    = filter_var(trim($_POST["widget_{$i}_link"]  ?? ''), FILTER_SANITIZE_URL);
                $btntext = $security->sanitize(trim($_POST["widget_{$i}_btntext"] ?? ''), 'text');
                $content = trim($_POST["widget_{$i}_content"] ?? '');
                $content = strip_tags(
                    html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    '<p><a><strong><em><br><ul><ol><li>'
                );
                dw_save_setting($db, "member_widget_{$i}_icon",    $icon);
                dw_save_setting($db, "member_widget_{$i}_title",   $title);
                dw_save_setting($db, "member_widget_{$i}_content", $content);
                dw_save_setting($db, "member_widget_{$i}_link",    $link);
                dw_save_setting($db, "member_widget_{$i}_btntext", $btntext);
            }
            $messages[] = ['type' => 'success', 'text' => 'Widgets gespeichert.'];
        }

        // ── Tab: Layout ───────────────────────────────────────────────────────
        if ($postTab === 'layout') {
            $cols  = in_array((int)($_POST['dashboard_columns'] ?? 2), [1, 2, 3, 4], true)
                ? (string)(int)$_POST['dashboard_columns']
                : '2';
            $order = $security->sanitize(trim($_POST['dashboard_section_order'] ?? ''), 'text');
            dw_save_setting($db, 'member_dashboard_columns',       $cols);
            dw_save_setting($db, 'member_dashboard_section_order', $order);
            $messages[] = ['type' => 'success', 'text' => 'Layout gespeichert.'];
        }

        // ── Tab: Design ───────────────────────────────────────────────────────
        if ($postTab === 'design') {
            $colorKeys = [
                'member_dashboard_color_primary',
                'member_dashboard_color_accent',
                'member_dashboard_color_bg',
                'member_dashboard_color_card_bg',
                'member_dashboard_color_text',
                'member_dashboard_color_border',
            ];
            foreach ($colorKeys as $ckey) {
                $shortKey = str_replace('member_dashboard_color_', '', $ckey);
                $raw = trim($_POST["color_{$shortKey}"] ?? '');
                if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $raw)) {
                    dw_save_setting($db, $ckey, $raw);
                }
            }
            $messages[] = ['type' => 'success', 'text' => 'Farbschema gespeichert.'];
        }
    }
}

// ── Aktuell gespeicherte Werte laden ─────────────────────────────────────────
$widgets = [];
for ($i = 1; $i <= 4; $i++) {
    $widgets[$i] = [
        'icon'    => dw_get_setting($db, "member_widget_{$i}_icon",    '📌'),
        'title'   => dw_get_setting($db, "member_widget_{$i}_title",   ''),
        'content' => dw_get_setting($db, "member_widget_{$i}_content", ''),
        'link'    => dw_get_setting($db, "member_widget_{$i}_link",    ''),
        'btntext' => dw_get_setting($db, "member_widget_{$i}_btntext", ''),
    ];
}

$pluginEnabled = [];
foreach (array_keys($dashboardPlugins) as $slug) {
    $pluginEnabled[$slug] = dw_get_setting($db, "member_dashboard_plugin_{$slug}", '1') === '1';
}

$layoutCols   = (int)dw_get_setting($db, 'member_dashboard_columns', '2');
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

$csrfToken = $security->generateToken('dashboard_widgets');

// ── Admin-Menü laden ──────────────────────────────────────────────────────────
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard – Verwaltung &bull; <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Tabs ── */
        .dw-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.75rem;
            flex-wrap: wrap;
        }
        .dw-tab-btn {
            padding: .6rem 1.25rem;
            font-size: .875rem;
            font-weight: 600;
            color: #64748b;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-family: 'Consolas','Monaco','Courier New',monospace;
            transition: color .15s, border-color .15s;
            margin-bottom: -2px;
        }
        .dw-tab-btn:hover { color: #1e293b; }
        .dw-tab-btn.active { color: #6366f1; border-bottom-color: #6366f1; }

        /* ── Tab Panels ── */
        .dw-panel { display: none; }
        .dw-panel.active { display: block; }

        /* ── Cards / Grid ── */
        .dw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .dw-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .dw-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem; padding-bottom: .75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dw-card-header h3 { margin: 0; font-size: 1rem; color: #1e293b; }
        .dw-badge {
            font-size: .75rem; padding: .2rem .6rem;
            background: #fef9c3; color: #92400e;
            border: 1px solid #fde68a; border-radius: 20px;
        }
        .dw-badge.active { background: #dcfce7; color: #166534; border-color: #86efac; }

        /* ── Form Elements ── */
        .dw-form-group { margin-bottom: 1rem; }
        .dw-form-group label {
            display: block; font-size: .8125rem; font-weight: 600;
            color: #374151; margin-bottom: .375rem;
        }
        .dw-form-group input,
        .dw-form-group textarea,
        .dw-form-group select {
            width: 100%; box-sizing: border-box;
            padding: .5rem .75rem;
            border: 1px solid #d1d5db; border-radius: 6px;
            font-size: .875rem; font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .dw-form-group input:focus,
        .dw-form-group textarea:focus,
        .dw-form-group select:focus {
            outline: none; border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .dw-form-group textarea { min-height: 90px; resize: vertical; }
        .dw-hint { font-size: .75rem; color: #94a3b8; margin-top: .25rem; }
        .dw-icon-row { display: flex; gap: .5rem; align-items: center; }
        .dw-icon-row input { flex: 1; }
        .dw-icon-preview { font-size: 1.625rem; min-width: 2rem; text-align: center; }

        /* ── Preview Box ── */
        .dw-preview-box {
            background: #fefce8; border: 1px solid #fde68a;
            border-radius: 8px; padding: 1rem 1.25rem;
            margin-top: .75rem;
        }
        .dw-preview-box .preview-icon { font-size: 1.5rem; margin-bottom: .35rem; }
        .dw-preview-box h4 { margin: 0 0 .35rem; font-size: .9375rem; color: #92400e; }
        .dw-preview-box p  { margin: 0; font-size: .8125rem; color: #78350f; }
        .dw-preview-link {
            display: inline-block;
            margin-top: .5rem;
            padding: .3rem .9rem;
            background: #92400e;
            color: #fff;
            border-radius: 6px;
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
        }
        .dw-empty-note { font-size: .8125rem; color: #94a3b8; font-style: italic; }

        /* ── Plugin Toggle ── */
        .dw-plugin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }
        .dw-plugin-row {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: .85rem 1.1rem;
            gap: .75rem;
        }
        .dw-plugin-info { display: flex; align-items: center; gap: .6rem; }
        .dw-plugin-icon { font-size: 1.2rem; }
        .dw-plugin-label { font-size: .875rem; font-weight: 600; color: #1e293b; }
        /* Toggle Switch */
        .dw-toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
        .dw-toggle input { opacity: 0; width: 0; height: 0; }
        .dw-toggle-slider {
            position: absolute; inset: 0; background: #cbd5e1;
            border-radius: 24px; cursor: pointer;
            transition: background .2s;
        }
        .dw-toggle-slider::before {
            content: '';
            position: absolute; width: 18px; height: 18px;
            left: 3px; top: 3px;
            background: #fff; border-radius: 50%;
            transition: transform .2s;
        }
        .dw-toggle input:checked + .dw-toggle-slider { background: #6366f1; }
        .dw-toggle input:checked + .dw-toggle-slider::before { transform: translateX(20px); }

        /* ── Layout ── */
        .dw-layout-cols {
            display: flex; gap: 1rem; margin-top: .75rem; flex-wrap: wrap;
        }
        .dw-col-option {
            flex: 1; min-width: 130px; max-width: 180px;
            border: 2px solid #e2e8f0; border-radius: 10px;
            padding: 1rem; text-align: center; cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .dw-col-option:hover { border-color: #a5b4fc; background: #eef2ff; }
        .dw-col-option input[type=radio] { display: none; }
        .dw-col-option.selected { border-color: #6366f1; background: #eef2ff; }
        .dw-col-preview {
            display: flex; gap: 4px; justify-content: center; margin-bottom: .4rem;
        }
        .dw-col-cell {
            height: 32px; background: #c7d2fe; border-radius: 3px;
        }
        .dw-col-option span { font-size: .8125rem; font-weight: 600; color: #4f46e5; }
        .dw-section-order {
            margin-top: 1rem;
        }
        .dw-section-order label { font-size: .8125rem; font-weight: 600; color: #374151; }

        /* ── Design / Colors ── */
        .dw-color-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.25rem;
        }
        .dw-color-row {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 1rem; display: flex; align-items: center; gap: .75rem;
        }
        .dw-color-row input[type=color] {
            width: 42px; height: 42px; border: 2px solid #e2e8f0;
            border-radius: 8px; cursor: pointer; padding: 2px; background: none;
        }
        .dw-color-label { font-size: .825rem; font-weight: 600; color: #374151; }
        .dw-color-hex { font-size: .75rem; color: #94a3b8; }

        /* ── Live-Preview Banner ── */
        .dw-design-preview {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid var(--dw-border,#e2e8f0);
        }
        .dw-design-preview-title { font-size: 1rem; font-weight: 700; margin: 0 0 .5rem; }

        /* ── Save Button ── */
        .btn-save-dw {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .75rem 2rem;
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            color: #fff; border: none; border-radius: 8px;
            font-size: .9375rem; font-weight: 700; cursor: pointer;
            margin-top: 1.5rem; transition: opacity .15s;
            font-family: 'Consolas','Monaco','Courier New',monospace;
        }
        .btn-save-dw:hover { opacity: .9; }

        /* ── Alerts ── */
        .admin-alert-success {
            background:#f0fdf4; border:1px solid #86efac; color:#166534;
            border-radius:8px; padding:.875rem 1.25rem; margin-bottom:1.25rem;
        }
        .admin-alert-error {
            background:#fef2f2; border:1px solid #fca5a5; color:#991b1b;
            border-radius:8px; padding:.875rem 1.25rem; margin-bottom:1.25rem;
        }
        .dw-live-link {
            display: inline-flex; align-items: center; gap: .375rem;
            color: #6366f1; text-decoration: none; font-size: .875rem;
        }
        .dw-live-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('design-dashboard-widgets'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1>🧩 Member Dashboard – Verwaltung</h1>
                <p class="admin-page-subtitle">
                    Plugin-Sichtbarkeit, Info-Widgets, Layout und Farbschema des Member-Dashboards konfigurieren.
                </p>
            </div>
            <a href="<?php echo SITE_URL; ?>/member" target="_blank" class="dw-live-link">
                👁️ Dashboard ansehen
            </a>
        </div>

        <!-- Flash-Messages -->
        <?php foreach ($messages as $msg): ?>
        <div class="admin-alert-<?php echo $msg['type']; ?>">
            <?php echo htmlspecialchars($msg['text']); ?>
        </div>
        <?php endforeach; ?>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: Pluginverwaltung                                  -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="dw-panel-plugins" class="dw-panel <?php echo $activeTab === 'plugins' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=plugins">
                <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab"  value="plugins">

                <p style="color:#64748b;font-size:.875rem;margin-top:0;">
                    Hier kannst du bestimmte Plugins im Member-Dashboard aktivieren oder deaktivieren.
                    Deaktivierte Plugins werden den Mitgliedern im Dashboard nicht mehr angezeigt.
                </p>

                <div class="dw-plugin-grid">
                    <?php if (empty($dashboardPlugins)): ?>
                        <div style="grid-column: 1/-1; padding: 1.5rem; text-align: center; background: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1; border-radius: 6px;">
                            Keine aktiven Plugins im System gefunden.
                        </div>
                    <?php else: ?>
                        <?php foreach ($dashboardPlugins as $slug => $info): ?>
                        <div class="dw-plugin-row" style="flex-wrap: wrap;">
                            <div class="dw-plugin-info" style="flex: 1; min-width: 200px;">
                                <span class="dw-plugin-icon"><?php echo $info['icon']; ?></span>
                                <div style="display:flex; flex-direction:column;">
                                    <span class="dw-plugin-label"><?php echo htmlspecialchars($info['label']); ?></span>
                                    <?php if (!empty($info['desc'])): ?>
                                        <span style="font-size:0.75rem; color:#94a3b8; margin-top:0.25rem;">
                                            <?php echo htmlspecialchars($info['desc']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <label class="dw-toggle" 
                                   title="<?php echo $pluginEnabled[$slug] ? 'Im Dashboard sichtbar' : 'Im Dashboard ausgeblendet'; ?>">
                                <input type="checkbox" name="plugin_enabled_<?php echo $slug; ?>"
                                       <?php echo $pluginEnabled[$slug] ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-save-dw">💾 Änderungen speichern</button>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: Info Widgets                                      -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="dw-panel-widgets" class="dw-panel <?php echo $activeTab === 'widgets' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=widgets">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="widgets">

                <p style="color:#64748b;font-size:.875rem;margin-top:0;">
                    Bis zu <strong>4 eigene Info-Widgets</strong> für das Member-Dashboard.
                    Jedes Widget zeigt Icon, Überschrift, Beschreibung und optional einen Button mit Link.
                </p>

                <div class="dw-grid">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                    <?php $w = $widgets[$i]; $hasContent = !empty($w['title']) || !empty($w['content']); ?>
                    <div class="dw-card" id="dw-card-<?php echo $i; ?>">
                        <div class="dw-card-header">
                            <h3>Widget <?php echo $i; ?></h3>
                            <span class="dw-badge <?php echo $hasContent ? 'active' : ''; ?>">
                                <?php echo $hasContent ? 'konfiguriert' : 'leer'; ?>
                            </span>
                        </div>

                        <!-- Icon -->
                        <div class="dw-form-group">
                            <label for="w<?php echo $i; ?>_icon">Icon (Emoji)</label>
                            <div class="dw-icon-row">
                                <input type="text" id="w<?php echo $i; ?>_icon"
                                       name="widget_<?php echo $i; ?>_icon"
                                       value="<?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>"
                                       placeholder="📌" maxlength="8"
                                       oninput="dwUpdatePreview(<?php echo $i; ?>)">
                                <span class="dw-icon-preview" id="prev<?php echo $i; ?>icon">
                                    <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                                </span>
                            </div>
                            <p class="dw-hint">Emoji einfügen, z.&thinsp;B. 📢 🎯 💡</p>
                        </div>

                        <!-- Titel -->
                        <div class="dw-form-group">
                            <label for="w<?php echo $i; ?>_title">Überschrift (neben dem Icon)</label>
                            <input type="text" id="w<?php echo $i; ?>_title"
                                   name="widget_<?php echo $i; ?>_title"
                                   value="<?php echo htmlspecialchars($w['title']); ?>"
                                   placeholder="Widget-Überschrift" maxlength="120"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                        </div>

                        <!-- Beschreibung -->
                        <div class="dw-form-group">
                            <label for="w<?php echo $i; ?>_content">Beschreibung</label>
                            <textarea id="w<?php echo $i; ?>_content"
                                      name="widget_<?php echo $i; ?>_content"
                                      placeholder="Kurze Info, Ankündigung …"
                                      oninput="dwUpdatePreview(<?php echo $i; ?>)"
                            ><?php echo htmlspecialchars($w['content']); ?></textarea>
                            <p class="dw-hint">Erlaubt: &lt;p&gt; &lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;li&gt;</p>
                        </div>

                        <!-- Weblink -->
                        <div class="dw-form-group">
                            <label for="w<?php echo $i; ?>_link">Weblink (optional)</label>
                            <input type="url" id="w<?php echo $i; ?>_link"
                                   name="widget_<?php echo $i; ?>_link"
                                   value="<?php echo htmlspecialchars($w['link']); ?>"
                                   placeholder="https://…"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                        </div>

                        <!-- Button-Text -->
                        <div class="dw-form-group">
                            <label for="w<?php echo $i; ?>_btntext">Button-Text (optional)</label>
                            <input type="text" id="w<?php echo $i; ?>_btntext"
                                   name="widget_<?php echo $i; ?>_btntext"
                                   value="<?php echo htmlspecialchars($w['btntext']); ?>"
                                   placeholder="z.&thinsp;B. »Mehr erfahren«" maxlength="60"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                            <p class="dw-hint">Nur angezeigt wenn auch ein Weblink eingetragen ist.</p>
                        </div>

                        <!-- Live-Vorschau -->
                        <div class="dw-preview-box" id="prev<?php echo $i; ?>box"
                             style="<?php echo $hasContent ? '' : 'display:none;'; ?>; display: flex; flex-direction: column;">
                            <div class="preview-icon" id="prev<?php echo $i; ?>iconbox">
                                <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                            </div>
                            <h4 id="prev<?php echo $i; ?>title"><?php echo htmlspecialchars($w['title']); ?></h4>
                            <div id="prev<?php echo $i; ?>body" style="font-size:.8125rem;color:#78350f; flex:1;">
                                <?php echo strip_tags($w['content'], '<p><a><strong><em><br><ul><ol><li>'); ?>
                            </div>
                            <?php if (!empty($w['link']) && !empty($w['btntext'])): ?>
                            <div style="margin-top:1rem;">
                                <a id="prev<?php echo $i; ?>btn"
                                   href="<?php echo htmlspecialchars($w['link']); ?>"
                                   style="display:inline-block; padding:0.4rem 0.8rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; font-size:0.75rem; color:#475569; text-decoration:none;"
                                   class="dw-preview-link"><?php echo htmlspecialchars($w['btntext']); ?></a>
                            </div>
                            <?php else: ?>
                            <div style="margin-top:1rem; display:none;">
                                <a id="prev<?php echo $i; ?>btn" class="dw-preview-link" style="display:none;"></a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!$hasContent): ?>
                        <p class="dw-empty-note" id="prev<?php echo $i; ?>note">
                            Widget ist leer – trage Überschrift und Inhalt ein.
                        </p>
                        <?php else: ?>
                        <p class="dw-empty-note" id="prev<?php echo $i; ?>note" style="display:none;"></p>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>

                <button type="submit" class="btn-save-dw">💾 Widgets speichern</button>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: Layout                                           -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="dw-panel-layout" class="dw-panel <?php echo $activeTab === 'layout' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=layout" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="layout">

                <!-- Logo Einstellung -->
                <div class="dw-card">
                    <div class="dw-card-header"><h3>Dashboard Logo</h3></div>
                    <div style="display:flex; gap:2rem; align-items:center;">
                        <div style="flex:1;">
                            <label>Logo links vom Seitentitel</label>
                            <input type="file" name="member_dashboard_logo" accept="image/*">
                            <p class="dw-hint" style="margin-top:0.5rem;">Empfohlen: SVG oder PNG, ca. 40px Höhe. Wird links neben "Member Dashboard" im Sidebar-Header angezeigt.</p>
                        </div>
                        <?php if (!empty($memberLogo)): ?>
                        <div style="text-align:center;">
                            <img src="<?php echo htmlspecialchars($memberLogo); ?>" style="max-height:40px; display:block; margin-bottom:0.5rem; background:#1e293b; padding:4px; border-radius:4px;">
                            <button type="submit" name="delete_logo" class="btn-delete-link" style="color:#ef4444; border:none; background:none; cursor:pointer; font-size:0.85rem;">Logo entfernen</button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Spaltenanzahl -->
                <div class="dw-card" style="max-width:600px;">
                    <div class="dw-card-header"><h3>Spalten für Stats & Plugins</h3></div>
                    <p style="font-size:.8125rem;color:#64748b;margin-top:0;">
                        Wie viele Spalten soll das Dashboard-Grid der Widgets haben?
                    </p>
                    <div class="dw-layout-cols" id="dwColOptions">
                        <?php foreach ([1 => '1 Spalte', 2 => '2 Spalten', 3 => '3 Spalten', 4 => '4 Spalten'] as $n => $label): ?>
                        <label class="dw-col-option <?php echo $layoutCols === $n ? 'selected' : ''; ?>"
                               onclick="dwSelectCols(this, <?php echo $n; ?>)">
                            <input type="radio" name="dashboard_columns"
                                   value="<?php echo $n; ?>"
                                   <?php echo $layoutCols === $n ? 'checked' : ''; ?>>
                            <div class="dw-col-preview">
                                <?php for ($c = 0; $c < $n; $c++): ?>
                                <div class="dw-col-cell" style="width: <?php echo round(52 / $n); ?>px;"></div>
                                <?php endfor; ?>
                            </div>
                            <span><?php echo $label; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sektions-Reihenfolge -->
                <div class="dw-card" style="max-width:600px;margin-top:1.25rem;">
                    <div class="dw-card-header"><h3>Sektions-Reihenfolge</h3></div>
                    <p style="font-size:.8125rem;color:#64748b;margin-top:0;">
                        Kommagetrennte Reihenfolge der Sektionen im Member-Dashboard
                        (z.&thinsp;B. <code>quick_start,stats,widgets,plugins</code>).
                    </p>
                    <div class="dw-form-group">
                        <label for="section_order">Reihenfolge</label>
                        <input type="text" id="section_order" name="dashboard_section_order"
                               value="<?php echo htmlspecialchars($layoutOrder); ?>"
                               placeholder="stats,quick_start,widgets,plugins">
                        <p class="dw-hint">Mögliche Werte: stats, quick_start, widgets, plugins</p>
                    </div>
                </div>

                <button type="submit" class="btn-save-dw">💾 Layout speichern</button>
            </form>
        </div>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- TAB: Design (Farben)                                  -->
        <!-- ══════════════════════════════════════════════════════ -->
        <div id="dw-panel-design" class="dw-panel <?php echo $activeTab === 'design' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=design">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="design">

                <p style="color:#64748b;font-size:.875rem;margin-top:0;">
                    Passe das Farbschema des Member-Dashboards an. Änderungen werden sofort als Vorschau angezeigt.
                </p>

                <div class="dw-color-grid">
                    <?php
                    $colorLabels = [
                        'primary'  => 'Primärfarbe (Buttons, Akzente)',
                        'accent'   => 'Akzentfarbe (Badges, Hover)',
                        'bg'       => 'Seitenhintergrund',
                        'card_bg'  => 'Karten-Hintergrund',
                        'text'     => 'Textfarbe',
                        'border'   => 'Rahmenfarbe',
                    ];
                    foreach ($colorLabels as $ckey => $clabel): ?>
                    <div class="dw-color-row">
                        <input type="color" name="color_<?php echo $ckey; ?>"
                               id="dw-color-<?php echo $ckey; ?>"
                               value="<?php echo htmlspecialchars($designColors[$ckey]); ?>"
                               oninput="dwUpdateDesignPreview()">
                        <div>
                            <div class="dw-color-label"><?php echo htmlspecialchars($clabel); ?></div>
                            <div class="dw-color-hex" id="hex-<?php echo $ckey; ?>"><?php echo htmlspecialchars($designColors[$ckey]); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Live-Vorschau -->
                <div class="dw-design-preview" id="dwDesignPreview"
                     style="background:<?php echo htmlspecialchars($designColors['bg']); ?>;border-color:<?php echo htmlspecialchars($designColors['border']); ?>;">
                    <p class="dw-design-preview-title"
                       style="color:<?php echo htmlspecialchars($designColors['text']); ?>;">
                        Vorschau – Member Dashboard
                    </p>
                    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                        <div style="background:<?php echo htmlspecialchars($designColors['card_bg']); ?>;
                                    border:1px solid <?php echo htmlspecialchars($designColors['border']); ?>;
                                    border-radius:8px;padding:.75rem 1rem;font-size:.8125rem;
                                    color:<?php echo htmlspecialchars($designColors['text']); ?>;">
                            Beispiel-Karte
                        </div>
                        <button type="button"
                                style="background:<?php echo htmlspecialchars($designColors['primary']); ?>;
                                       color:#fff;border:none;border-radius:6px;
                                       padding:.5rem 1.1rem;font-size:.8125rem;cursor:pointer;">
                            Primär-Button
                        </button>
                        <button type="button"
                                style="background:<?php echo htmlspecialchars($designColors['accent']); ?>;
                                       color:#fff;border:none;border-radius:6px;
                                       padding:.5rem 1.1rem;font-size:.8125rem;cursor:pointer;">
                            Akzent-Button
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-save-dw">💾 Farbschema speichern</button>
            </form>
        </div>

    </div><!-- /.admin-content -->

    <script>
    // ── Tab-Switching ─────────────────────────────────────────────────────────
    function dwSwitchTab(tab) {
        document.querySelectorAll('.dw-panel').forEach(function(p) { p.classList.remove('active'); });
        document.querySelectorAll('.dw-tab-btn').forEach(function(b) { b.classList.remove('active'); });
        var panel = document.getElementById('dw-panel-' + tab);
        if (panel) { panel.classList.add('active'); }
        document.querySelectorAll('.dw-tab-btn').forEach(function(b) {
            if (b.getAttribute('onclick') === "dwSwitchTab('" + tab + "')") {
                b.classList.add('active');
            }
        });
        // Update URL ohne Reload
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        history.replaceState(null, '', url.toString());
    }

    // ── Widget Live-Vorschau ──────────────────────────────────────────────────
    function dwUpdatePreview(i) {
        var icon    = (document.getElementById('w' + i + '_icon')    || {}).value || '📌';
        var title   = (document.getElementById('w' + i + '_title')   || {}).value || '';
        var content = (document.getElementById('w' + i + '_content') || {}).value || '';
        var link    = (document.getElementById('w' + i + '_link')    || {}).value || '';
        var btntext = (document.getElementById('w' + i + '_btntext') || {}).value || '';

        var elIcon    = document.getElementById('prev' + i + 'icon');
        var elIconBox = document.getElementById('prev' + i + 'iconbox');
        var elTitle   = document.getElementById('prev' + i + 'title');
        var elBody    = document.getElementById('prev' + i + 'body');
        var elBox     = document.getElementById('prev' + i + 'box');
        var elNote    = document.getElementById('prev' + i + 'note');
        var elBtn     = document.getElementById('prev' + i + 'btn');

        if (elIcon)    elIcon.textContent    = icon;
        if (elIconBox) elIconBox.textContent = icon;
        if (elTitle)   elTitle.textContent   = title;
        if (elBody)    elBody.textContent    = content;

        var hasContent = title.trim() !== '' || content.trim() !== '';
        if (elBox)  elBox.style.display  = hasContent ? '' : 'none';
        if (elNote) elNote.style.display = hasContent ? 'none' : '';

        if (elBtn) {
            if (link && btntext) {
                elBtn.href        = link;
                elBtn.textContent = btntext;
                elBtn.style.display = '';
            } else {
                elBtn.style.display = 'none';
            }
        }
    }

    // ── Layout-Spalten ────────────────────────────────────────────────────────
    function dwSelectCols(el, n) {
        document.querySelectorAll('.dw-col-option').forEach(function(o) {
            o.classList.remove('selected');
        });
        el.classList.add('selected');
        var radio = el.querySelector('input[type=radio]');
        if (radio) { radio.checked = true; }
    }

    // ── Design Live-Vorschau ──────────────────────────────────────────────────
    function dwUpdateDesignPreview() {
        var primary  = document.getElementById('dw-color-primary')  ? document.getElementById('dw-color-primary').value  : '#6366f1';
        var accent   = document.getElementById('dw-color-accent')   ? document.getElementById('dw-color-accent').value   : '#8b5cf6';
        var bg       = document.getElementById('dw-color-bg')       ? document.getElementById('dw-color-bg').value       : '#f1f5f9';
        var cardBg   = document.getElementById('dw-color-card_bg')  ? document.getElementById('dw-color-card_bg').value  : '#ffffff';
        var text     = document.getElementById('dw-color-text')     ? document.getElementById('dw-color-text').value     : '#1e293b';
        var border   = document.getElementById('dw-color-border')   ? document.getElementById('dw-color-border').value   : '#e2e8f0';

        var prev = document.getElementById('dwDesignPreview');
        if (prev) {
            prev.style.background   = bg;
            prev.style.borderColor  = border;
            var title = prev.querySelector('.dw-design-preview-title');
            if (title) title.style.color = text;
            var cards = prev.querySelectorAll('div[style*="border-radius:8px"]');
            cards.forEach(function(c) {
                c.style.background   = cardBg;
                c.style.borderColor  = border;
                c.style.color        = text;
            });
            var btns = prev.querySelectorAll('button[type=button]');
            if (btns[0]) btns[0].style.background = primary;
            if (btns[1]) btns[1].style.background = accent;
        }

        // Hex labels aktualisieren
        ['primary','accent','bg','card_bg','text','border'].forEach(function(k) {
            var el = document.getElementById('dw-color-' + k);
            var hex = document.getElementById('hex-' + k);
            if (el && hex) hex.textContent = el.value;
        });
    }
    </script>

</body>
</html>
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .dw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .dw-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
        }
        .dw-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem; padding-bottom: .75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .dw-card-header h3 { margin: 0; font-size: 1rem; color: #1e293b; }
        .dw-badge {
            font-size: .75rem; padding: .2rem .6rem;
            background: #fef9c3; color: #92400e;
            border: 1px solid #fde68a; border-radius: 20px;
        }
        .dw-form-group { margin-bottom: 1rem; }
        .dw-form-group label {
            display: block; font-size: .8125rem; font-weight: 600;
            color: #374151; margin-bottom: .375rem;
        }
        .dw-form-group input,
        .dw-form-group textarea {
            width: 100%; box-sizing: border-box;
            padding: .5rem .75rem;
            border: 1px solid #d1d5db; border-radius: 6px;
            font-size: .875rem; font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .dw-form-group input:focus,
        .dw-form-group textarea:focus {
            outline: none; border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .dw-form-group textarea { min-height: 90px; resize: vertical; }
        .dw-hint { font-size: .75rem; color: #94a3b8; margin-top: .25rem; }
        .dw-icon-row { display: flex; gap: .5rem; align-items: center; }
        .dw-icon-row input { flex: 1; }
        .dw-icon-preview { font-size: 1.625rem; min-width: 2rem; text-align: center; }
        .dw-preview-box {
            background: #fefce8; border: 1px solid #fde68a;
            border-radius: 8px; padding: 1rem 1.25rem;
            margin-top: .75rem;
        }
        .dw-preview-box h4 { margin: 0 0 .35rem; font-size: .9375rem; color: #92400e; }
        .dw-preview-box p  { margin: 0; font-size: .8125rem; color: #78350f; }
        .dw-empty-note { font-size: .8125rem; color: #94a3b8; font-style: italic; }
        .btn-save-widgets {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .75rem 2rem;
            background: linear-gradient(135deg,#6366f1,#8b5cf6);
            color: #fff; border: none; border-radius: 8px;
            font-size: .9375rem; font-weight: 700; cursor: pointer;
            margin-top: 1.5rem; transition: opacity .15s;
        }
        .btn-save-widgets:hover { opacity: .9; }
        .admin-alert-success {
            background:#f0fdf4; border:1px solid #86efac; color:#166534;
            border-radius:8px; padding:.875rem 1.25rem; margin-bottom:1.25rem;
        }
        .admin-alert-error {
            background:#fef2f2; border:1px solid #fca5a5; color:#991b1b;
            border-radius:8px; padding:.875rem 1.25rem; margin-bottom:1.25rem;
        }
        .dw-live-link {
            display: inline-flex; align-items: center; gap: .375rem;
            color: #6366f1; text-decoration: none; font-size: .875rem;
        }
        .dw-live-link:hover { text-decoration: underline; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('design-dashboard-widgets'); ?>

    <div class="admin-content">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1>🧩 Member Dashboard – Benutzerdefinierte Widgets</h1>
                <p class="admin-page-subtitle">
                    Lege bis zu <strong>3 eigene Info-Widgets</strong> fest, die allen Mitgliedern
                    auf ihrer Dashboard-Übersicht angezeigt werden.
                </p>
            </div>
            <a href="<?php echo SITE_URL; ?>/member" target="_blank" class="dw-live-link">
                👁️ Dashboard ansehen
            </a>
        </div>

        <!-- Flash-Messages -->
        <?php foreach ($messages as $msg): ?>
        <div class="admin-alert-<?php echo $msg['type']; ?>">
            <?php echo htmlspecialchars($msg['text']); ?>
        </div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="dw-grid">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                <?php $w = $widgets[$i]; $isEmpty = empty($w['title']) && empty($w['content']); ?>
                <div class="dw-card">
                    <div class="dw-card-header">
                        <h3>Widget <?php echo $i; ?></h3>
                        <span class="dw-badge"><?php echo $isEmpty ? 'leer' : 'konfiguriert'; ?></span>
                    </div>

                    <!-- Icon -->
                    <div class="dw-form-group">
                        <label for="w<?php echo $i; ?>_icon">Icon (Emoji)</label>
                        <div class="dw-icon-row">
                            <input type="text" id="w<?php echo $i; ?>_icon"
                                   name="widget_<?php echo $i; ?>_icon"
                                   value="<?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>"
                                   placeholder="📌"
                                   maxlength="8"
                                   oninput="document.getElementById('prev<?php echo $i; ?>icon').textContent=this.value">
                            <span class="dw-icon-preview" id="prev<?php echo $i; ?>icon">
                                <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                            </span>
                        </div>
                        <p class="dw-hint">Einfach ein Emoji einfügen, z.&thinsp;B. 📢 🎯 💡</p>
                    </div>

                    <!-- Titel -->
                    <div class="dw-form-group">
                        <label for="w<?php echo $i; ?>_title">Titel</label>
                        <input type="text" id="w<?php echo $i; ?>_title"
                               name="widget_<?php echo $i; ?>_title"
                               value="<?php echo htmlspecialchars($w['title']); ?>"
                               placeholder="Widget-Überschrift"
                               maxlength="120"
                               oninput="document.getElementById('prev<?php echo $i; ?>title').textContent=this.value">
                    </div>

                    <!-- Inhalt -->
                    <div class="dw-form-group">
                        <label for="w<?php echo $i; ?>_content">Inhalt (kurzer Text / HTML)</label>
                        <textarea id="w<?php echo $i; ?>_content"
                                  name="widget_<?php echo $i; ?>_content"
                                  placeholder="Kurze Info, Link oder Ankündigung …"
                                  oninput="document.getElementById('prev<?php echo $i; ?>body').innerHTML=this.value"
                        ><?php echo htmlspecialchars($w['content']); ?></textarea>
                        <p class="dw-hint">Erlaubt: &lt;p&gt; &lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;li&gt;</p>
                    </div>

                    <!-- Live-Vorschau -->
                    <?php if (!$isEmpty): ?>
                    <div class="dw-preview-box">
                        <div style="font-size:1.25rem;margin-bottom:.35rem;" id="prev<?php echo $i; ?>iconbox">
                            <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                        </div>
                        <h4 id="prev<?php echo $i; ?>title"><?php echo htmlspecialchars($w['title']); ?></h4>
                        <div id="prev<?php echo $i; ?>body" style="font-size:.8125rem;color:#78350f;">
                            <?php echo strip_tags($w['content'], '<p><a><strong><em><br><ul><ol><li>'); ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="dw-empty-note">
                        Widget ist leer – trage Titel und Inhalt ein, damit es angezeigt wird.
                    </p>
                    <!-- Versteckte Preview-Elemente für Live-Update -->
                    <div class="dw-preview-box" style="display:none;">
                        <h4 id="prev<?php echo $i; ?>title"></h4>
                        <div id="prev<?php echo $i; ?>body"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <button type="submit" class="btn-save-widgets">
                💾 Alle Widgets speichern
            </button>
        </form>

    </div>

    <script>
    // Live-Vorschau: Karte einblenden sobald Titel oder Inhalt gefüllt
    document.querySelectorAll('.dw-card').forEach(function(card) {
        var preview = card.querySelector('.dw-preview-box');
        var note    = card.querySelector('.dw-empty-note');
        if (!preview || !note) { return; }
        var titleInput   = card.querySelector('input[name$="_title"]');
        var contentInput = card.querySelector('textarea[name$="_content"]');

        function togglePreview() {
            var hasContent = (titleInput && titleInput.value.trim()) ||
                             (contentInput && contentInput.value.trim());
            preview.style.display = hasContent ? '' : 'none';
            note.style.display    = hasContent ? 'none' : '';
        }

        [titleInput, contentInput].forEach(function(el) {
            if (el) { el.addEventListener('input', togglePreview); }
        });
    });
    </script>

</body>
</html>
