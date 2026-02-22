<?php
/**
 * Admin: Member Dashboard – Verwaltung
 *
 * URL: /admin/design-dashboard-widgets
 * Tabs: plugins | widgets | design | settings
 *
 *   plugins  – Welche CMS-Plugins im Member-Dashboard sichtbar sind
 *   widgets  – Bis zu 4 Info-Widgets (Icon, Titel, Beschreibung, Link, Buttontext)
 *   design   – Farbgebung + Layout + Sektions-Reihenfolge des Member-Dashboards
 *   settings – Allgemeine Einstellungen des Member-Dashboard-Bereichs
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

// ── Aktiver Tab ────────────────────────────────────────────────────────────────
$activeTab = in_array($_GET['tab'] ?? '', ['plugins', 'widgets', 'design', 'settings'], true)
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
        'booking' => '📅', 'calendar' => '📅', 'shop' => '🛒',
        'market'  => '🛒', 'marketplace' => '🛒', 'message' => '✉️',
        'chat'    => '💬', 'project' => '📋', 'task' => '✅',
        'premium' => '⭐', 'promo' => '🎁', 'support' => '🎧',
        'ticket'  => '🎫', 'forum' => '💬', 'expert' => '👨‍💼',
        'member'  => '👤', 'profile' => '🆔',
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

// ── POST-Verarbeitung ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTab = $security->sanitize($_POST['active_tab'] ?? 'widgets', 'text');

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'dashboard_widgets')) {
        $messages[] = ['type' => 'error', 'text' => 'Ungültiger Sicherheits-Token.'];
    } else {

        // ── Logo-Upload (aus Design-Tab) ──────────────────────────────────────
        if (($postTab === 'design') && isset($_FILES['member_dashboard_logo']) && $_FILES['member_dashboard_logo']['size'] > 0) {
            $file = $_FILES['member_dashboard_logo'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
                if (in_array($ext, $allowedExts, true)) {
                    $filename   = 'member-logo-' . time() . '.' . $ext;
                    $uploadRel  = '/assets/uploads/logos/';
                    $uploadPath = dirname(__DIR__) . $uploadRel;
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    if (move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
                        dw_save_setting($db, 'member_dashboard_logo', SITE_URL . $uploadRel . $filename);
                        $messages[] = ['type' => 'success', 'text' => 'Logo erfolgreich hochgeladen.'];
                    } else {
                        $messages[] = ['type' => 'error', 'text' => 'Fehler beim Hochladen der Datei.'];
                    }
                } else {
                    $messages[] = ['type' => 'error', 'text' => 'Ungültiges Dateiformat für Logo.'];
                }
            }
        } elseif (($postTab === 'design') && isset($_POST['delete_logo'])) {
            dw_save_setting($db, 'member_dashboard_logo', '');
            $messages[] = ['type' => 'success', 'text' => 'Logo entfernt.'];
        }

        // ── Plugins ───────────────────────────────────────────────────────────
        if ($postTab === 'plugins') {
            foreach (array_keys($dashboardPlugins) as $slug) {
                $enabled = isset($_POST["plugin_enabled_{$slug}"]) ? '1' : '0';
                dw_save_setting($db, "member_dashboard_plugin_{$slug}", $enabled);
            }
            $messages[] = ['type' => 'success', 'text' => 'Plugin-Sichtbarkeit gespeichert.'];
        }

        // ── Widgets ───────────────────────────────────────────────────────────
        if ($postTab === 'widgets') {
            for ($i = 1; $i <= 4; $i++) {
                $icon    = $security->sanitize(trim($_POST["widget_{$i}_icon"]    ?? ''), 'text');
                $title   = $security->sanitize(trim($_POST["widget_{$i}_title"]   ?? ''), 'text');
                $link    = filter_var(trim($_POST["widget_{$i}_link"]  ?? ''), FILTER_SANITIZE_URL);
                $btntext = $security->sanitize(trim($_POST["widget_{$i}_btntext"] ?? ''), 'text');
                $content = strip_tags(
                    html_entity_decode(trim($_POST["widget_{$i}_content"] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
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

        // ── Design + Layout (kombinierter Tab) ────────────────────────────────
        if ($postTab === 'design') {
            // Farben
            foreach (['primary','accent','bg','card_bg','text','border'] as $ckey) {
                $raw = trim($_POST["color_{$ckey}"] ?? '');
                if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $raw)) {
                    dw_save_setting($db, "member_dashboard_color_{$ckey}", $raw);
                }
            }
            // Layout-Spalten
            $cols = in_array((int)($_POST['dashboard_columns'] ?? 2), [1, 2, 3, 4], true)
                ? (string)(int)$_POST['dashboard_columns'] : '2';
            dw_save_setting($db, 'member_dashboard_columns', $cols);
            // Sektions-Reihenfolge
            $allowedSections = ['stats', 'quick_start', 'widgets', 'plugins'];
            $rawOrder = explode(',', $security->sanitize(trim($_POST['dashboard_section_order'] ?? ''), 'text'));
            $sanitized = array_values(array_intersect($rawOrder, $allowedSections));
            // Fehlende Sektionen ans Ende hängen
            foreach ($allowedSections as $s) {
                if (!in_array($s, $sanitized, true)) { $sanitized[] = $s; }
            }
            dw_save_setting($db, 'member_dashboard_section_order', implode(',', $sanitized));
            $messages[] = ['type' => 'success', 'text' => 'Design & Layout gespeichert.'];
        }

        // ── Einstellungen ─────────────────────────────────────────────────────
        if ($postTab === 'settings') {
            // Begrüßung
            dw_save_setting($db, 'member_dashboard_greeting',
                $security->sanitize(trim($_POST['member_dashboard_greeting'] ?? ''), 'text'));
            dw_save_setting($db, 'member_dashboard_welcome_text',
                strip_tags(trim($_POST['member_dashboard_welcome_text'] ?? ''), '<p><a><strong><em>'));
            dw_save_setting($db, 'member_dashboard_show_welcome',
                isset($_POST['member_dashboard_show_welcome']) ? '1' : '0');

            // Registrierung & Profil
            dw_save_setting($db, 'member_registration_open',
                isset($_POST['member_registration_open']) ? '1' : '0');
            dw_save_setting($db, 'member_email_verification',
                isset($_POST['member_email_verification']) ? '1' : '0');
            dw_save_setting($db, 'member_avatar_upload_enabled',
                isset($_POST['member_avatar_upload_enabled']) ? '1' : '0');
            dw_save_setting($db, 'member_profile_public_default',
                isset($_POST['member_profile_public_default']) ? '1' : '0');

            // Medien-Limits
            $maxMb = max(1, min(500, (int)($_POST['member_media_max_upload_mb'] ?? 10)));
            dw_save_setting($db, 'member_media_max_upload_mb', (string)$maxMb);
            $allowedTypes = [];
            foreach (['images', 'documents', 'videos', 'audio'] as $t) {
                if (isset($_POST["member_media_type_{$t}"])) { $allowedTypes[] = $t; }
            }
            dw_save_setting($db, 'member_media_allowed_types', implode(',', $allowedTypes) ?: 'images');

            // Weiterleitungen
            $loginRedir  = filter_var(trim($_POST['member_redirect_after_login']  ?? '/member'), FILTER_SANITIZE_URL);
            $logoutRedir = filter_var(trim($_POST['member_redirect_after_logout'] ?? '/'),       FILTER_SANITIZE_URL);
            $deniedRedir = filter_var(trim($_POST['member_redirect_access_denied'] ?? '/member'), FILTER_SANITIZE_URL);
            dw_save_setting($db, 'member_redirect_after_login',   $loginRedir  ?: '/member');
            dw_save_setting($db, 'member_redirect_after_logout',  $logoutRedir ?: '/');
            dw_save_setting($db, 'member_redirect_access_denied', $deniedRedir ?: '/member');

            // Benachrichtigungen
            dw_save_setting($db, 'member_notifications_email_enabled',
                isset($_POST['member_notifications_email_enabled']) ? '1' : '0');
            dw_save_setting($db, 'member_notifications_email_from',
                $security->sanitize(trim($_POST['member_notifications_email_from'] ?? ''), 'email'));

            // Sicherheit
            $maxLogins = max(1, min(20, (int)($_POST['member_max_login_attempts'] ?? 5)));
            $sessionTimeout = max(5, min(10080, (int)($_POST['member_session_timeout'] ?? 120)));
            dw_save_setting($db, 'member_max_login_attempts', (string)$maxLogins);
            dw_save_setting($db, 'member_session_timeout',    (string)$sessionTimeout);

            $messages[] = ['type' => 'success', 'text' => 'Einstellungen gespeichert.'];
        }
    }
}

// ── Aktuelle Werte laden ───────────────────────────────────────────────────────
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

// Settings-Werte
$settingsData = [
    'greeting'                    => dw_get_setting($db, 'member_dashboard_greeting',             'Guten Tag, {name}!'),
    'welcome_text'                => dw_get_setting($db, 'member_dashboard_welcome_text',         ''),
    'show_welcome'                => dw_get_setting($db, 'member_dashboard_show_welcome',          '1'),
    'registration_open'           => dw_get_setting($db, 'member_registration_open',              '1'),
    'email_verification'          => dw_get_setting($db, 'member_email_verification',             '0'),
    'avatar_upload_enabled'       => dw_get_setting($db, 'member_avatar_upload_enabled',          '1'),
    'profile_public_default'      => dw_get_setting($db, 'member_profile_public_default',         '1'),
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

// Sektions-Labeling für die Drag&Drop-Übersicht
$sectionLabels = [
    'stats'       => ['icon' => '📊', 'label' => 'Statuswidgets',  'desc' => 'Abo-Status, Aktivitätszähler'],
    'quick_start' => ['icon' => '🚀', 'label' => 'Schnellstart',   'desc' => 'Buttons für häufige Aktionen'],
    'widgets'     => ['icon' => '📌', 'label' => 'Infobereich',    'desc' => 'Admin-definierte Info-Widgets'],
    'plugins'     => ['icon' => '🔌', 'label' => 'Plugin-Bereiche','desc' => 'Widgets aktiver CMS-Plugins'],
];
$orderedSections = array_filter(
    array_map('trim', explode(',', $layoutOrder)),
    fn($s) => isset($sectionLabels[$s])
);
// Sicherstellen dass alle Sektionen vorhanden sind
foreach (array_keys($sectionLabels) as $s) {
    if (!in_array($s, $orderedSections, true)) { $orderedSections[] = $s; }
}

$csrfToken = $security->generateToken('dashboard_widgets');
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard – Verwaltung &bull; <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=202602">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('design-dashboard-widgets'); ?>

    <div class="admin-content">

        <div class="admin-page-header">
            <div>
                <h1>🧩 Member Dashboard – Verwaltung</h1>
                <p class="admin-page-subtitle">
                    Plugins, Widgets, Design, Layout und Einstellungen des Member-Bereichs konfigurieren.
                </p>
            </div>
            <a href="<?php echo SITE_URL; ?>/member" target="_blank" class="dw-live-link">
                👁️ Dashboard ansehen
            </a>
        </div>

        <?php foreach ($messages as $msg): ?>
        <div class="admin-alert-<?php echo $msg['type']; ?>">
            <?php echo htmlspecialchars($msg['text']); ?>
        </div>
        <?php endforeach; ?>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB: Plugins                                                       -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="dw-panel-plugins" class="dw-panel <?php echo $activeTab === 'plugins' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=plugins">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="plugins">

                <p style="color:#64748b;font-size:.875rem;margin:0 0 1.25rem;">
                    Steuere, welche aktiven CMS-Plugins im Member-Dashboard als Widget angezeigt werden.
                    Deaktivierte Plugins bleiben für Mitglieder unsichtbar.
                </p>

                <div class="dw-plugin-grid">
                    <?php if (empty($dashboardPlugins)): ?>
                        <div style="grid-column:1/-1;padding:1.5rem;text-align:center;
                                    background:#f8fafc;color:#64748b;
                                    border:1px dashed #cbd5e1;border-radius:6px;">
                            Keine aktiven Plugins im System gefunden.
                        </div>
                    <?php else: ?>
                        <?php foreach ($dashboardPlugins as $slug => $info): ?>
                        <div class="dw-plugin-row">
                            <div class="dw-plugin-info">
                                <span class="dw-plugin-icon"><?php echo $info['icon']; ?></span>
                                <div>
                                    <span class="dw-plugin-label"><?php echo htmlspecialchars($info['label']); ?></span>
                                    <?php if (!empty($info['desc'])): ?>
                                    <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">
                                        <?php echo htmlspecialchars($info['desc']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <label class="dw-toggle" title="<?php echo $pluginEnabled[$slug] ? 'Sichtbar' : 'Ausgeblendet'; ?>">
                                <input type="checkbox" name="plugin_enabled_<?php echo $slug; ?>"
                                       <?php echo $pluginEnabled[$slug] ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-save-dw">💾 Sichtbarkeit speichern</button>
            </form>
        </div>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB: Widgets                                                       -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="dw-panel-widgets" class="dw-panel <?php echo $activeTab === 'widgets' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=widgets">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="widgets">

                <p style="color:#64748b;font-size:.875rem;margin:0 0 1.25rem;">
                    Bis zu <strong>4 eigene Info-Widgets</strong> für das Member-Dashboard.
                    Leere Widgets werden nicht angezeigt.
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

                        <div class="dw-form-group">
                            <label>Icon (Emoji)</label>
                            <div class="dw-icon-row">
                                <input type="text" name="widget_<?php echo $i; ?>_icon"
                                       value="<?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>"
                                       placeholder="📌" maxlength="8"
                                       oninput="dwUpdatePreview(<?php echo $i; ?>)">
                                <span class="dw-icon-preview" id="prev<?php echo $i; ?>icon">
                                    <?php echo htmlspecialchars($w['icon'] ?: '📌'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="dw-form-group">
                            <label>Überschrift</label>
                            <input type="text" name="widget_<?php echo $i; ?>_title"
                                   value="<?php echo htmlspecialchars($w['title']); ?>"
                                   placeholder="Widget-Überschrift" maxlength="120"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                        </div>

                        <div class="dw-form-group">
                            <label>Beschreibung</label>
                            <textarea name="widget_<?php echo $i; ?>_content"
                                      placeholder="Kurze Info oder Ankündigung …"
                                      oninput="dwUpdatePreview(<?php echo $i; ?>)"
                            ><?php echo htmlspecialchars($w['content']); ?></textarea>
                            <p class="dw-hint">Erlaubt: &lt;p&gt; &lt;a&gt; &lt;strong&gt; &lt;em&gt; &lt;ul&gt; &lt;li&gt;</p>
                        </div>

                        <div class="dw-form-group">
                            <label>Weblink (optional)</label>
                            <input type="url" name="widget_<?php echo $i; ?>_link"
                                   value="<?php echo htmlspecialchars($w['link']); ?>"
                                   placeholder="https://…"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                        </div>

                        <div class="dw-form-group">
                            <label>Button-Text (optional)</label>
                            <input type="text" name="widget_<?php echo $i; ?>_btntext"
                                   value="<?php echo htmlspecialchars($w['btntext']); ?>"
                                   placeholder="z. B. »Mehr erfahren«" maxlength="60"
                                   oninput="dwUpdatePreview(<?php echo $i; ?>)">
                            <p class="dw-hint">Wird nur angezeigt, wenn auch ein Weblink eingetragen ist.</p>
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
                                   style="display:<?php echo (!empty($w['link']) && !empty($w['btntext'])) ? 'inline-block' : 'none'; ?>;
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

                <button type="submit" class="btn-save-dw">💾 Widgets speichern</button>
            </form>
        </div>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB: Design & Layout (kombiniert)                                  -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="dw-panel-design" class="dw-panel <?php echo $activeTab === 'design' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=design" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="design">
                <!-- Sektions-Reihenfolge (von Drag&Drop befüllt) -->
                <input type="hidden" name="dashboard_section_order" id="sectionOrderInput"
                       value="<?php echo htmlspecialchars($layoutOrder); ?>">

                <!-- ── Zeile 1: Farben + Layout-Spalten nebeneinander ─────────── -->
                <div class="dw-grid-2">

                    <!-- FARBEN -->
                    <div class="dw-card">
                        <div class="dw-card-header">
                            <h3>🎨 Farbschema</h3>
                        </div>
                        <p style="font-size:.8125rem;color:#64748b;margin:0 0 .75rem;">
                            Farbanpassung für das Member-Dashboard. Änderungen werden sofort in der Vorschau sichtbar.
                        </p>
                        <div class="dw-color-grid">
                            <?php
                            $colorLabels = [
                                'primary'  => ['label' => 'Primärfarbe',       'desc' => 'Buttons, Links, Akzente'],
                                'accent'   => ['label' => 'Akzentfarbe',        'desc' => 'Badges, Hover-Effekte'],
                                'bg'       => ['label' => 'Seitenhintergrund',  'desc' => 'Hintergrundfarbe'],
                                'card_bg'  => ['label' => 'Karten-Hintergrund','desc' => 'Weiße Karten-Füllung'],
                                'text'     => ['label' => 'Textfarbe',          'desc' => 'Haupt-Textfarbe'],
                                'border'   => ['label' => 'Rahmenfarbe',        'desc' => 'Trennlinien, Borders'],
                            ];
                            foreach ($colorLabels as $ckey => $clabel): ?>
                            <div class="dw-color-row">
                                <input type="color" name="color_<?php echo $ckey; ?>"
                                       id="dw-color-<?php echo $ckey; ?>"
                                       value="<?php echo htmlspecialchars($designColors[$ckey]); ?>"
                                       oninput="dwUpdateDesignPreview()">
                                <div>
                                    <div class="dw-color-label"><?php echo htmlspecialchars($clabel['label']); ?></div>
                                    <div class="dw-color-hex" id="hex-<?php echo $ckey; ?>"><?php echo htmlspecialchars($designColors[$ckey]); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Farb-Vorschau -->
                        <div class="dw-design-preview" id="dwDesignPreview"
                             style="background:<?php echo htmlspecialchars($designColors['bg']); ?>;
                                    border-color:<?php echo htmlspecialchars($designColors['border']); ?>;
                                    margin-top:1rem;">
                            <div class="dw-design-preview-title"
                                 style="color:<?php echo htmlspecialchars($designColors['text']); ?>;">
                                Vorschau Member Dashboard
                            </div>
                            <div style="display:flex;gap:.625rem;flex-wrap:wrap;align-items:center;">
                                <div style="background:<?php echo htmlspecialchars($designColors['card_bg']); ?>;
                                            border:1px solid <?php echo htmlspecialchars($designColors['border']); ?>;
                                            border-radius:6px;padding:.5rem .875rem;font-size:.8125rem;
                                            color:<?php echo htmlspecialchars($designColors['text']); ?>;"
                                     id="dpv-card">Beispiel-Karte</div>
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

                    <!-- LAYOUT -->
                    <div class="dw-card">
                        <div class="dw-card-header">
                            <h3>🗂️ Layout & Spalten</h3>
                        </div>
                        <p style="font-size:.8125rem;color:#64748b;margin:0 0 .75rem;">
                            Dashboard-Logo und Anzahl der Widget-Spalten.
                        </p>

                        <!-- Logo -->
                        <div class="dw-form-group">
                            <label>Dashboard Logo</label>
                            <input type="file" name="member_dashboard_logo" accept="image/*"
                                   style="padding:.35rem 0;border:none;">
                            <p class="dw-hint">SVG oder PNG, ca. 40 px Höhe – wird neben dem Sidebar-Seitentitel angezeigt.</p>
                            <?php if (!empty($memberLogo)): ?>
                            <div style="margin-top:.625rem;display:flex;align-items:center;gap:.75rem;">
                                <img src="<?php echo htmlspecialchars($memberLogo); ?>"
                                     style="max-height:40px;background:#1e293b;padding:4px;border-radius:4px;">
                                <button type="submit" name="delete_logo"
                                        style="color:#ef4444;border:none;background:none;cursor:pointer;font-size:.8125rem;padding:0;">
                                    ✕ Logo entfernen
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Spaltenanzahl -->
                        <label style="font-size:.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:.375rem;">
                            Widget-Spalten
                        </label>
                        <div class="dw-layout-cols" id="dwColOptions">
                            <?php foreach ([1 => '1 Spalte', 2 => '2 Spalten', 3 => '3 Spalten', 4 => '4 Spalten'] as $n => $lbl): ?>
                            <label class="dw-col-option <?php echo $layoutCols === $n ? 'selected' : ''; ?>"
                                   onclick="dwSelectCols(this, <?php echo $n; ?>)">
                                <input type="radio" name="dashboard_columns"
                                       value="<?php echo $n; ?>"
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
                </div><!-- /.dw-grid-2 -->


                <!-- ── Sektions-Reihenfolge (Drag & Drop) ────────────────────── -->
                <div class="dw-card" style="margin-top:1.25rem;">
                    <div class="dw-card-header">
                        <h3>↕️ Startseite – Sektionen anordnen</h3>
                        <span style="font-size:.75rem;color:#94a3b8;">Ziehen zum Umordnen</span>
                    </div>
                    <p style="font-size:.8125rem;color:#64748b;margin:0 0 .75rem;">
                        Lege fest, in welcher Reihenfolge die Bereiche auf der Member-Startseite erscheinen.
                    </p>

                    <div class="dw-section-list" id="sectionSorter">
                        <?php foreach ($orderedSections as $sKey):
                            $sInfo = $sectionLabels[$sKey] ?? ['icon' => '🔲', 'label' => $sKey, 'desc' => '']; ?>
                        <div class="dw-section-item" draggable="true" data-section="<?php echo htmlspecialchars($sKey); ?>">
                            <span class="dw-section-handle" title="Ziehen zum Verschieben">⣿</span>
                            <span class="dw-section-icon"><?php echo $sInfo['icon']; ?></span>
                            <div class="dw-section-info">
                                <strong><?php echo htmlspecialchars($sInfo['label']); ?></strong>
                                <span><?php echo htmlspecialchars($sInfo['desc']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn-save-dw">💾 Design & Layout speichern</button>
            </form>
        </div>


        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- TAB: Einstellungen                                                 -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <div id="dw-panel-settings" class="dw-panel <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
            <form method="POST" action="?tab=settings">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="active_tab" value="settings">

                <div class="dw-grid-2">

                    <!-- ── Begrüßung & Willkommen ─────────────────────────────── -->
                    <div class="dw-card">
                        <div class="dw-card-header"><h3>👋 Begrüßung</h3></div>

                        <div class="dw-form-group">
                            <label for="set-greeting">Begrüßungs-Text</label>
                            <input type="text" id="set-greeting" name="member_dashboard_greeting"
                                   value="<?php echo htmlspecialchars($settingsData['greeting']); ?>"
                                   placeholder="Guten Tag, {name}!" maxlength="100">
                            <p class="dw-hint"><code>{name}</code> = Vorname des Mitglieds</p>
                        </div>

                        <div class="dw-form-group">
                            <label for="set-welcome">Willkommens-Nachricht</label>
                            <textarea id="set-welcome" name="member_dashboard_welcome_text"
                                      placeholder="Schön, dass du dabei bist! …"><?php echo htmlspecialchars($settingsData['welcome_text']); ?></textarea>
                            <p class="dw-hint">Erscheint nach dem Begrüßungstext – HTML teilweise erlaubt.</p>
                        </div>

                        <div class="dw-toggle-row">
                            <div class="dw-toggle-info">
                                <strong>Willkommens-Banner anzeigen</strong>
                                <span>Blendet den Willkommensbereich auf der Startseite ein</span>
                            </div>
                            <label class="dw-toggle">
                                <input type="checkbox" name="member_dashboard_show_welcome"
                                       <?php echo $settingsData['show_welcome'] === '1' ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- ── Weiterleitungen ────────────────────────────────────── -->
                    <div class="dw-card">
                        <div class="dw-card-header"><h3>🔀 Weiterleitungen</h3></div>

                        <div class="dw-form-group">
                            <label for="set-redir-login">Nach dem Login</label>
                            <input type="text" id="set-redir-login" name="member_redirect_after_login"
                                   value="<?php echo htmlspecialchars($settingsData['redirect_after_login']); ?>"
                                   placeholder="/member">
                            <p class="dw-hint">Wohin wird nach erfolgreicher Anmeldung weitergeleitet?</p>
                        </div>

                        <div class="dw-form-group">
                            <label for="set-redir-logout">Nach dem Logout</label>
                            <input type="text" id="set-redir-logout" name="member_redirect_after_logout"
                                   value="<?php echo htmlspecialchars($settingsData['redirect_after_logout']); ?>"
                                   placeholder="/">
                        </div>

                        <div class="dw-form-group">
                            <label for="set-redir-denied">Zugriff verweigert</label>
                            <input type="text" id="set-redir-denied" name="member_redirect_access_denied"
                                   value="<?php echo htmlspecialchars($settingsData['redirect_access_denied']); ?>"
                                   placeholder="/member">
                            <p class="dw-hint">Nicht eingeloggte Benutzer werden hierhin geleitet.</p>
                        </div>
                    </div>

                </div><!-- /.dw-grid-2 -->

                <div class="dw-grid-2" style="margin-top:1.25rem;">

                    <!-- ── Registrierung & Profil ─────────────────────────────── -->
                    <div class="dw-card">
                        <div class="dw-card-header"><h3>👤 Registrierung & Profil</h3></div>

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
                                <strong>Profile standardmäßig öffentlich</strong>
                                <span>Neue Profile sind für nicht eingeloggte Besucher sichtbar</span>
                            </div>
                            <label class="dw-toggle">
                                <input type="checkbox" name="member_profile_public_default"
                                       <?php echo $settingsData['profile_public_default'] === '1' ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <!-- ── Sicherheit ─────────────────────────────────────────── -->
                    <div class="dw-card">
                        <div class="dw-card-header"><h3>🔒 Sicherheit</h3></div>

                        <div class="dw-form-group">
                            <label for="set-max-logins">Max. Login-Versuche</label>
                            <input type="number" id="set-max-logins" name="member_max_login_attempts"
                                   value="<?php echo (int)$settingsData['max_login_attempts']; ?>"
                                   min="1" max="20" style="max-width:120px;">
                            <p class="dw-hint">Nach dieser Anzahl Fehler wird der Account temporär gesperrt (1–20).</p>
                        </div>

                        <div class="dw-form-group">
                            <label for="set-session">Session-Timeout (Minuten)</label>
                            <input type="number" id="set-session" name="member_session_timeout"
                                   value="<?php echo (int)$settingsData['session_timeout']; ?>"
                                   min="5" max="10080" style="max-width:120px;">
                            <p class="dw-hint">Inaktive Sessions nach X Minuten beenden (5–10080 / max. 7 Tage).</p>
                        </div>

                        <div class="dw-toggle-row">
                            <div class="dw-toggle-info">
                                <strong>E-Mail-Benachrichtigungen</strong>
                                <span>System-E-Mails an Mitglieder senden</span>
                            </div>
                            <label class="dw-toggle">
                                <input type="checkbox" name="member_notifications_email_enabled"
                                       <?php echo $settingsData['notifications_email_enabled'] === '1' ? 'checked' : ''; ?>>
                                <span class="dw-toggle-slider"></span>
                            </label>
                        </div>

                        <div class="dw-form-group" style="margin-top:.875rem;">
                            <label for="set-noti-from">Absender-E-Mail für Benachrichtigungen</label>
                            <input type="email" id="set-noti-from" name="member_notifications_email_from"
                                   value="<?php echo htmlspecialchars($settingsData['notifications_email_from']); ?>"
                                   placeholder="noreply@meine-seite.de">
                            <p class="dw-hint">Leer = Standard-E-Mail aus allgemeinen Einstellungen</p>
                        </div>
                    </div>

                </div><!-- /.dw-grid-2 -->

                <!-- ── Medien-Limits ──────────────────────────────────────────── -->
                <div class="dw-card" style="margin-top:1.25rem;">
                    <div class="dw-card-header"><h3>📂 Medien-Limits</h3></div>
                    <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:flex-start;">
                        <div class="dw-form-group" style="min-width:200px;max-width:280px;margin:0;">
                            <label for="set-max-mb">Max. Upload-Größe pro Datei (MB)</label>
                            <input type="number" id="set-max-mb" name="member_media_max_upload_mb"
                                   value="<?php echo (int)$settingsData['media_max_upload_mb']; ?>"
                                   min="1" max="500" style="max-width:120px;">
                            <p class="dw-hint">Gilt für Datei-Uploads von Mitgliedern (1–500 MB).</p>
                        </div>
                        <div>
                            <label style="font-size:.8125rem;font-weight:600;color:#374151;display:block;margin-bottom:.5rem;">
                                Erlaubte Dateitypen
                            </label>
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

                <button type="submit" class="btn-save-dw">💾 Einstellungen speichern</button>
            </form>
        </div>

    </div><!-- /.admin-content -->

    <script>
    // ── Widget Live-Vorschau ───────────────────────────────────────────────────
    function dwUpdatePreview(i) {
        var icon    = (document.querySelector('[name="widget_'+i+'_icon"]')    || {}).value || '📌';
        var title   = (document.querySelector('[name="widget_'+i+'_title"]')   || {}).value || '';
        var content = (document.querySelector('[name="widget_'+i+'_content"]') || {}).value || '';
        var link    = (document.querySelector('[name="widget_'+i+'_link"]')    || {}).value || '';
        var btntext = (document.querySelector('[name="widget_'+i+'_btntext"]') || {}).value || '';

        var set = function(id, prop, val) {
            var el = document.getElementById(id);
            if (el) { el[prop] = val; }
        };

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

    // ── Layout: Spalten ───────────────────────────────────────────────────────
    function dwSelectCols(el, n) {
        document.querySelectorAll('.dw-col-option').forEach(function(o) { o.classList.remove('selected'); });
        el.classList.add('selected');
        var radio = el.querySelector('input[type=radio]');
        if (radio) radio.checked = true;
    }

    // ── Design: Live-Vorschau ─────────────────────────────────────────────────
    function dwUpdateDesignPreview() {
        var colors = {};
        ['primary','accent','bg','card_bg','text','border'].forEach(function(k) {
            var el = document.getElementById('dw-color-'+k);
            colors[k] = el ? el.value : '';
            var hex = document.getElementById('hex-'+k);
            if (hex && el) hex.textContent = el.value;
        });
        var prev = document.getElementById('dwDesignPreview');
        if (!prev) return;
        prev.style.background  = colors.bg;
        prev.style.borderColor = colors.border;
        var title = prev.querySelector('.dw-design-preview-title');
        if (title) title.style.color = colors.text;
        var card = document.getElementById('dpv-card');
        if (card) { card.style.background = colors.card_bg; card.style.borderColor = colors.border; card.style.color = colors.text; }
        var bp = document.getElementById('dpv-btn-primary');
        if (bp) bp.style.background = colors.primary;
        var ba = document.getElementById('dpv-btn-accent');
        if (ba) ba.style.background = colors.accent;
    }

    // ── Drag & Drop Section Sorter ────────────────────────────────────────────
    (function() {
        var list    = document.getElementById('sectionSorter');
        var orderIn = document.getElementById('sectionOrderInput');
        if (!list || !orderIn) return;

        var dragged = null;

        function updateHidden() {
            var order = Array.from(list.querySelectorAll('.dw-section-item'))
                             .map(function(el) { return el.dataset.section; });
            orderIn.value = order.join(',');
        }

        list.addEventListener('dragstart', function(e) {
            dragged = e.target.closest('.dw-section-item');
            if (dragged) {
                dragged.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });

        list.addEventListener('dragend', function() {
            if (dragged) {
                dragged.classList.remove('dragging');
                dragged = null;
            }
            list.querySelectorAll('.dw-section-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });
            updateHidden();
        });

        list.addEventListener('dragover', function(e) {
            e.preventDefault();
            var target = e.target.closest('.dw-section-item');
            if (target && dragged && target !== dragged) {
                list.querySelectorAll('.dw-section-item').forEach(function(el) {
                    el.classList.remove('drag-over');
                });
                target.classList.add('drag-over');
                // Insert before or after based on mouse position
                var rect   = target.getBoundingClientRect();
                var middle = rect.top + rect.height / 2;
                if (e.clientY < middle) {
                    list.insertBefore(dragged, target);
                } else {
                    list.insertBefore(dragged, target.nextSibling);
                }
            }
        });

        list.addEventListener('drop', function(e) {
            e.preventDefault();
            list.querySelectorAll('.dw-section-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });
            updateHidden();
        });
    })();
    </script>
</body>
</html>
