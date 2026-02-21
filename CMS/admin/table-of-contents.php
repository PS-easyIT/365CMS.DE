<?php
/**
 * Table of Contents Settings
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) { exit; }
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$db       = Database::instance();
$security = Security::instance();
$message  = '';
$msgType  = '';

$defaultSettings = [
    'support_types'       => ['post', 'page'],
    'auto_insert_types'   => ['post'],
    'position'            => 'before',
    'show_limit'          => 4,
    'show_header_label'   => true,
    'header_label'        => 'Inhaltsverzeichnis',
    'allow_toggle'        => true,
    'show_hierarchy'      => true,
    'show_counter'        => true,
    'smooth_scroll'       => true,
    'smooth_scroll_offset'=> 30,
    'mobile_scroll_offset'=> 0,
    'width'               => 'auto',
    'alignment'           => 'none',
    'theme'               => 'grey',
    'custom_bg_color'     => '#f9f9f9',
    'custom_border_color' => '#aaaaaa',
    'custom_title_color'  => '#333333',
    'custom_link_color'   => '#0073aa',
    'lowercase'           => true,
    'hyphenate'           => true,
    'homepage_toc'        => false,
    'exclude_css'         => false,
    'headings'            => ['h2', 'h3', 'h4'],
    'exclude_headings'    => '',
    'limit_path'          => '',
    'anchor_prefix'       => '',
    'remove_toc_links'    => false,
    'sticky_toggle'       => false,
];

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_toc_settings') {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'toc_settings')) {
        $message = 'Sicherheitsprüfung fehlgeschlagen.';
        $msgType = 'error';
    } else {
        $new = $defaultSettings;
        foreach ($defaultSettings as $key => $default) {
            if (is_bool($default)) {
                $new[$key] = isset($_POST[$key]);
            } elseif (is_array($default)) {
                $new[$key] = (array) ($_POST[$key] ?? []);
            } else {
                $new[$key] = $_POST[$key] ?? $default;
            }
        }
        $json   = json_encode($new);
        $exists = $db->fetchOne("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = 'toc_settings'");
        if ($exists) {
            $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = 'toc_settings'", [$json]);
        } else {
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('toc_settings', ?)", [$json]);
        }
        $message = 'Einstellungen gespeichert.';
        $msgType = 'success';
    }
}

// Load
$row      = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'toc_settings'");
$settings = $row ? array_merge($defaultSettings, (array) json_decode($row['option_value'], true)) : $defaultSettings;

require_once __DIR__ . '/partials/admin-menu.php';

// Local helpers with unique prefix to avoid collision with includes/functions.php
function toc_chk(mixed $val, mixed $compare): string {
    if (is_array($compare)) {
        return in_array((string)$val, array_map('strval', $compare)) ? 'checked' : '';
    }
    return ((string)$val === (string)$compare) ? 'checked' : '';
}
function toc_sel(mixed $val, mixed $compare): void {
    echo ((string)$val === (string)$compare) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table of Contents – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .toc-card{background:#fff;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:2rem;overflow:hidden}
        .toc-card-head{background:#f8fafc;padding:1rem 1.5rem;border-bottom:1px solid #e2e8f0;font-weight:600;color:#1e293b;display:flex;align-items:center;gap:.5rem}
        .toc-card-body{padding:1.5rem}
        .toc-row{display:grid;grid-template-columns:260px 1fr;gap:2rem;margin-bottom:1.5rem;align-items:start}
        .toc-row>label{font-weight:500;color:#475569;padding-top:.4rem}
        .toc-hint{font-size:.82rem;color:#94a3b8;margin-top:.25rem;display:block}
        .toc-chk-list{display:flex;flex-direction:column;gap:.5rem}
        .toc-chk-list label{display:flex;align-items:center;gap:.5rem;font-weight:normal;padding:0}
        .heading-pills{display:flex;flex-wrap:wrap;gap:.75rem}
        .heading-pills label{background:#f1f5f9;padding:.25rem .75rem;border-radius:4px;cursor:pointer;font-weight:normal}
        .heading-pills input[type=checkbox]:checked+span{color:#2563eb;font-weight:700}
    </style>
</head>
<body class="admin-body">
<?php renderAdminSidebar('table-of-contents'); ?>
<div class="admin-content">
    <div class="admin-page-header">
        <h2>📑 Table of Contents</h2>
        <p>Konfigurieren Sie das automatische Inhaltsverzeichnis Ihrer Website.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="action" value="save_toc_settings">
        <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('toc_settings'); ?>">

        <!-- Allgemeines -->
        <div class="toc-card">
            <div class="toc-card-head">⚙️ Allgemeines</div>
            <div class="toc-card-body">
                <div class="toc-row">
                    <div><label>Unterstützte Inhaltstypen</label><span class="toc-hint">TOC steht für diese Typen zur Verfügung.</span></div>
                    <div class="toc-chk-list">
                        <label><input type="checkbox" name="support_types[]" value="post" <?php echo toc_chk('post', $settings['support_types']); ?>> Beiträge</label>
                        <label><input type="checkbox" name="support_types[]" value="page" <?php echo toc_chk('page', $settings['support_types']); ?>> Seiten</label>
                        <label><input type="checkbox" name="support_types[]" value="product" <?php echo toc_chk('product', $settings['support_types']); ?>> Produkte</label>
                    </div>
                </div>
                <div class="toc-row">
                    <div><label>Automatisch einfügen</label><span class="toc-hint">TOC wird hier auto-generiert.</span></div>
                    <div class="toc-chk-list">
                        <label><input type="checkbox" name="auto_insert_types[]" value="post" <?php echo toc_chk('post', $settings['auto_insert_types']); ?>> Beiträge</label>
                        <label><input type="checkbox" name="auto_insert_types[]" value="page" <?php echo toc_chk('page', $settings['auto_insert_types']); ?>> Seiten</label>
                    </div>
                </div>
                <div class="toc-row">
                    <label>Position</label>
                    <select name="position" class="form-control" style="max-width:300px">
                        <option value="before" <?php toc_sel('before', $settings['position']); ?>>Vor der ersten Überschrift (Standard)</option>
                        <option value="after"  <?php toc_sel('after',  $settings['position']); ?>>Nach der ersten Überschrift</option>
                        <option value="top"    <?php toc_sel('top',    $settings['position']); ?>>Oben (Inhalt-Start)</option>
                        <option value="bottom" <?php toc_sel('bottom', $settings['position']); ?>>Unten (Inhalt-Ende)</option>
                    </select>
                </div>
                <div class="toc-row">
                    <label>Anzeigen ab</label>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <select name="show_limit" class="form-control" style="width:80px">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php toc_sel($i, $settings['show_limit']); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <span>oder mehr Überschriften</span>
                    </div>
                </div>
                <div class="toc-row">
                    <label>Header-Label</label>
                    <div>
                        <div class="toc-chk-list" style="margin-bottom:.5rem">
                            <label><input type="checkbox" name="show_header_label" value="1" <?php echo toc_chk('1', $settings['show_header_label'] ? '1' : '0'); ?>> Anzeigen</label>
                            <label><input type="checkbox" name="allow_toggle" value="1" <?php echo toc_chk('1', $settings['allow_toggle'] ? '1' : '0'); ?>> Auf-/Zuklappen erlauben</label>
                        </div>
                        <input type="text" name="header_label" value="<?php echo htmlspecialchars($settings['header_label']); ?>" class="form-control" placeholder="Inhaltsverzeichnis">
                    </div>
                </div>
                <div class="toc-row">
                    <label>Hierarchie & Nummerierung</label>
                    <div class="toc-chk-list">
                        <label><input type="checkbox" name="show_hierarchy" value="1" <?php echo toc_chk('1', $settings['show_hierarchy'] ? '1' : '0'); ?>> Als Hierarchie anzeigen</label>
                        <label><input type="checkbox" name="show_counter" value="1" <?php echo toc_chk('1', $settings['show_counter'] ? '1' : '0'); ?>> Nummerierung anzeigen</label>
                    </div>
                </div>
                <div class="toc-row">
                    <label>Sanfter Bildlauf</label>
                    <div>
                        <label class="toc-chk-list" style="margin-bottom:.5rem">
                            <input type="checkbox" name="smooth_scroll" value="1" <?php echo toc_chk('1', $settings['smooth_scroll'] ? '1' : '0'); ?>> Aktivieren
                        </label>
                        <div style="display:flex;gap:1.5rem;margin-top:.25rem">
                            <label style="font-weight:normal">Versatz Desktop (px): <input type="number" name="smooth_scroll_offset" value="<?php echo (int)$settings['smooth_scroll_offset']; ?>" style="width:70px;padding:4px"></label>
                            <label style="font-weight:normal">Versatz Mobile (px): <input type="number" name="mobile_scroll_offset" value="<?php echo (int)$settings['mobile_scroll_offset']; ?>" style="width:70px;padding:4px"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Design -->
        <div class="toc-card">
            <div class="toc-card-head">🎨 Design</div>
            <div class="toc-card-body">
                <div class="toc-row">
                    <label>Breite</label>
                    <select name="width" class="form-control" style="max-width:200px">
                        <option value="auto"   <?php toc_sel('auto',   $settings['width']); ?>>Automatisch</option>
                        <option value="100%"   <?php toc_sel('100%',   $settings['width']); ?>>100%</option>
                        <option value="custom" <?php toc_sel('custom', $settings['width']); ?>>Benutzerdefiniert</option>
                    </select>
                </div>
                <div class="toc-row">
                    <label>Ausrichtung</label>
                    <select name="alignment" class="form-control" style="max-width:200px">
                        <option value="none"   <?php toc_sel('none',   $settings['alignment']); ?>>Keine (Standard)</option>
                        <option value="left"   <?php toc_sel('left',   $settings['alignment']); ?>>Links</option>
                        <option value="right"  <?php toc_sel('right',  $settings['alignment']); ?>>Rechts</option>
                        <option value="center" <?php toc_sel('center', $settings['alignment']); ?>>Zentriert</option>
                    </select>
                </div>
                <div class="toc-row">
                    <label>Theme</label>
                    <select name="theme" class="form-control" style="max-width:200px"
                            onchange="document.getElementById('toc-custom-colors').style.display=(this.value==='custom'?'block':'none')">
                        <option value="grey"        <?php toc_sel('grey',        $settings['theme']); ?>>Grau (Standard)</option>
                        <option value="light-blue"  <?php toc_sel('light-blue',  $settings['theme']); ?>>Hellblau</option>
                        <option value="white"       <?php toc_sel('white',       $settings['theme']); ?>>Weiß</option>
                        <option value="black"       <?php toc_sel('black',       $settings['theme']); ?>>Schwarz</option>
                        <option value="transparent" <?php toc_sel('transparent', $settings['theme']); ?>>Transparent</option>
                        <option value="custom"      <?php toc_sel('custom',      $settings['theme']); ?>>Individuell</option>
                    </select>
                </div>
                <div id="toc-custom-colors" style="display:<?php echo $settings['theme']==='custom'?'block':'none'; ?>;border-left:3px solid #3b82f6;padding-left:1rem;margin-top:.5rem">
                    <div class="toc-row"><label>Hintergrund</label><input type="color" name="custom_bg_color" value="<?php echo htmlspecialchars($settings['custom_bg_color']); ?>"></div>
                    <div class="toc-row"><label>Rahmen</label><input type="color" name="custom_border_color" value="<?php echo htmlspecialchars($settings['custom_border_color']); ?>"></div>
                    <div class="toc-row"><label>Titelfarbe</label><input type="color" name="custom_title_color" value="<?php echo htmlspecialchars($settings['custom_title_color']); ?>"></div>
                    <div class="toc-row"><label>Linkfarbe</label><input type="color" name="custom_link_color" value="<?php echo htmlspecialchars($settings['custom_link_color']); ?>"></div>
                </div>
            </div>
        </div>

        <!-- Erweitert -->
        <div class="toc-card">
            <div class="toc-card-head">🛠️ Erweitert</div>
            <div class="toc-card-body">
                <div class="toc-row">
                    <label>Anker-Format</label>
                    <div class="toc-chk-list">
                        <label><input type="checkbox" name="lowercase" value="1" <?php echo toc_chk('1', $settings['lowercase'] ? '1' : '0'); ?>> Kleinbuchstaben erzwingen</label>
                        <label><input type="checkbox" name="hyphenate" value="1" <?php echo toc_chk('1', $settings['hyphenate'] ? '1' : '0'); ?>> Bindestriche statt Unterstriche</label>
                    </div>
                </div>
                <div class="toc-row">
                    <div><label>Überschriften</label><span class="toc-hint">Welche Tags berücksichtigen?</span></div>
                    <div class="heading-pills">
                        <?php foreach (['h1','h2','h3','h4','h5','h6'] as $h): ?>
                            <label>
                                <input type="checkbox" name="headings[]" value="<?php echo $h; ?>" <?php echo toc_chk($h, $settings['headings']); ?>>
                                <span><?php echo strtoupper($h); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="toc-row">
                    <div><label>Überschriften ausschließen</label><span class="toc-hint">Pipe | als Trennzeichen.</span></div>
                    <input type="text" name="exclude_headings" value="<?php echo htmlspecialchars($settings['exclude_headings']); ?>" class="form-control" placeholder="Ähnliche Beiträge|Kommentare">
                </div>
                <div class="toc-row">
                    <div><label>Pfad einschränken</label><span class="toc-hint">Nur auf Seiten mit diesem Pfadpräfix.</span></div>
                    <input type="text" name="limit_path" value="<?php echo htmlspecialchars($settings['limit_path']); ?>" class="form-control" placeholder="/blog/">
                </div>
                <div class="toc-row">
                    <div><label>Anker-Präfix</label><span class="toc-hint">Optional, z. B. „toc-".</span></div>
                    <input type="text" name="anchor_prefix" value="<?php echo htmlspecialchars($settings['anchor_prefix']); ?>" class="form-control" placeholder="toc-">
                </div>
                <div class="toc-row">
                    <label>Sonstige Optionen</label>
                    <div class="toc-chk-list">
                        <label><input type="checkbox" name="homepage_toc" value="1" <?php echo toc_chk('1', $settings['homepage_toc'] ? '1' : '0'); ?>> Auf der Startseite anzeigen</label>
                        <label><input type="checkbox" name="remove_toc_links" value="1" <?php echo toc_chk('1', $settings['remove_toc_links'] ? '1' : '0'); ?>> Verlinkung entfernen (nur Liste)</label>
                        <label><input type="checkbox" name="exclude_css" value="1" <?php echo toc_chk('1', $settings['exclude_css'] ? '1' : '0'); ?>> CSS nicht laden</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky -->
        <div class="toc-card">
            <div class="toc-card-head">📌 Sticky Sidebar TOC</div>
            <div class="toc-card-body">
                <div class="toc-row">
                    <label>Sticky TOC</label>
                    <label class="toc-chk-list">
                        <input type="checkbox" name="sticky_toggle" value="1" <?php echo toc_chk('1', $settings['sticky_toggle'] ? '1' : '0'); ?>>
                        Als mitscrollendes Sidebar-Widget aktivieren
                    </label>
                </div>
            </div>
        </div>

        <!-- Shortcode -->
        <div class="toc-card">
            <div class="toc-card-head">ℹ️ Shortcode-Referenz</div>
            <div class="toc-card-body">
                <p>Manuelles Einbetten per Shortcode:</p>
                <code style="display:block;background:#f1f5f9;padding:1rem;border-radius:4px">[ez-toc]</code>
                <p style="margin-top:.75rem"><small>Attribute: <code>[ez-toc header_label="Inhalt" display_counter="no"]</code></small></p>
            </div>
        </div>

        <div style="padding-bottom:3rem">
            <button type="submit" class="btn btn-primary" style="padding:.75rem 2rem;font-size:1.05rem">Einstellungen speichern</button>
        </div>
    </form>
</div>
<?php renderAdminLayoutEnd(); ?>
