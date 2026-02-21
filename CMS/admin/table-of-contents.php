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

$db = Database::instance();
$security = Security::instance();
$message = '';
$messageType = '';

// Default Settings
$defaultSettings = [
    // General
    'support_types' => ['post', 'page'],
    'auto_insert_types' => ['post'],
    'position' => 'before',
    'show_limit' => 4, // Show when 4 or more headings
    'word_count_limit' => 0,
    'show_header_label' => true,
    'toggle_header_text' => true,
    'header_label' => 'Inhaltsverzeichnis',
    'header_label_tag' => 'h3',
    'allow_toggle' => true,
    'show_hierarchy' => true,
    'show_counter' => true,
    'counter_style' => 'decimal', // decimal, numeric, etc.
    'smooth_scroll' => true,
    'smooth_scroll_offset' => 30,
    'mobile_scroll_offset' => 0,
    
    // Design
    'width' => 'auto',
    'alignment' => 'none', // left, right, center, none
    'wrapping' => false,
    'font_size' => '95%',
    'title_font_size' => '100%',
    'theme' => 'grey', // grey, light-blue, white, black, transparent, custom
    'custom_bg_color' => '#f9f9f9',
    'custom_border_color' => '#aaaaaa',
    'custom_title_color' => '#333333',
    'custom_link_color' => '#0073aa',
    'custom_link_hover_color' => '#005177',
    'custom_visited_link_color' => '#333333',

    // Advanced
    'lowercase' => true,
    'hyphenate' => true,
    'homepage_toc' => false,
    'exclude_css' => false,
    'headings' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    'exclude_headings' => '',
    'limit_path' => '',
    'anchor_prefix' => '',
    'remove_toc_links' => false,
    
    // Sticky
    'sticky_toggle' => false,
    'sticky_height' => 0,
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_toc_settings') {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'toc_settings')) {
        $message = 'Sicherheitsprüfung fehlgeschlagen.';
        $messageType = 'error';
    } else {
        $newSettings = $defaultSettings; // Start with defaults
        
        // Process Post Data
        foreach ($defaultSettings as $key => $default) {
            if (is_bool($default)) {
                $newSettings[$key] = isset($_POST[$key]);
            } elseif (is_array($default)) {
                $newSettings[$key] = $_POST[$key] ?? [];
            } else {
                $newSettings[$key] = $_POST[$key] ?? $default;
            }
        }
        
        // Save to DB
        $json = json_encode($newSettings);
        
        // Check if exists
        $exists = $db->fetchOne("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = 'toc_settings'");
        
        if ($exists) {
            $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = 'toc_settings'", [$json]);
        } else {
            $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES ('toc_settings', ?)", [$json]);
        }
        
        $message = 'Einstellungen erfolgreich gespeichert.';
        $messageType = 'success';
    }
}

// Load Settings
$savedSettingsJson = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'toc_settings'");
$settings = $savedSettingsJson ? array_merge($defaultSettings, json_decode($savedSettingsJson['option_value'], true)) : $defaultSettings;

// Load Admin Menu
require_once __DIR__ . '/partials/admin-menu.php';

// Helper for checkboxes
function checked($val, $compare) {
    if (is_array($compare)) return in_array($val, $compare) ? 'checked' : '';
    return $val == $compare ? 'checked' : '';
}
function selected($val, $compare) {
    echo $val == $compare ? 'selected' : '';
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table of Contents - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .toc-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 2rem; overflow: hidden; }
        .toc-header { background: #f8fafc; padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
        .toc-body { padding: 1.5rem; }
        .form-row { display: grid; grid-template-columns: 250px 1fr; gap: 2rem; margin-bottom: 1.5rem; align-items: start; }
        .form-row label { font-weight: 500; color: #475569; padding-top: 0.5rem; }
        .form-row .description { font-size: 0.85rem; color: #94a3b8; margin-top: 0.25rem; display: block; }
        .checkbox-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .checkbox-list label { display: flex; align-items: center; gap: 0.5rem; font-weight: normal; padding: 0; }
        
        .color-picker-wrapper { display: flex; align-items: center; gap: 1rem; }
        .heading-toggles { display: flex; flex-wrap: wrap; gap: 1rem; }
        .heading-toggles label { background: #f1f5f9; padding: 0.25rem 0.75rem; border-radius: 4px; cursor: pointer; user-select: none; }
        .heading-toggles input:checked + span { color: #2563eb; font-weight: 600; }
    </style>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('table-of-contents'); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <h2>📑 Table of Contents</h2>
            <p>Konfigurieren Sie das automatische Inhaltsverzeichnis für Ihre Website.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="action" value="save_toc_settings">
            <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('toc_settings'); ?>">

            <!-- 1. Allgemeines -->
            <div class="toc-section">
                <div class="toc-header">⚙️ Allgemeines</div>
                <div class="toc-body">
                    <div class="form-row">
                        <div>
                            <label>Unterstützung aktivieren</label>
                            <span class="description">Wähle die Inhaltstypen aus, für die das Inhaltsverzeichnis zur Verfügung stehen soll.</span>
                        </div>
                        <div class="checkbox-list">
                            <label><input type="checkbox" name="support_types[]" value="post" <?php echo checked('post', $settings['support_types']); ?>> Beiträge</label>
                            <label><input type="checkbox" name="support_types[]" value="page" <?php echo checked('page', $settings['support_types']); ?>> Seiten</label>
                            <label><input type="checkbox" name="support_types[]" value="product" <?php echo checked('product', $settings['support_types']); ?>> Produkte</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label>Automatisches Einfügen</label>
                            <span class="description">Wähle die Inhaltstypen aus, bei denen das Inhaltsverzeichnis automatisch eingefügt werden soll.</span>
                        </div>
                        <div class="checkbox-list">
                            <label><input type="checkbox" name="auto_insert_types[]" value="post" <?php echo checked('post', $settings['auto_insert_types']); ?>> Beiträge</label>
                            <label><input type="checkbox" name="auto_insert_types[]" value="page" <?php echo checked('page', $settings['auto_insert_types']); ?>> Seiten</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Position</label>
                        <select name="position" class="form-control" style="max-width:300px;">
                            <option value="before" <?php selected('before', $settings['position']); ?>>Vor der ersten Überschrift (Standard)</option>
                            <option value="after" <?php selected('after', $settings['position']); ?>>Nach der ersten Überschrift</option>
                            <option value="top" <?php selected('top', $settings['position']); ?>>Oben (Inhalt-Start)</option>
                            <option value="bottom" <?php selected('bottom', $settings['position']); ?>>Unten (Inhalt-Ende)</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Anzeigen, wenn</label>
                        <select name="show_limit" class="form-control" style="max-width:100px; display:inline-block;">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, $settings['show_limit']); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <span style="margin-left:0.5rem;">oder mehr Überschriften vorhanden sind.</span>
                    </div>

                    <div class="form-row">
                        <label>Header-Label anzeigen</label>
                        <label>
                            <input type="checkbox" name="show_header_label" value="1" <?php echo checked(true, $settings['show_header_label']); ?>>
                            Header-Text über dem Inhaltsverzeichnis anzeigen
                        </label>
                    </div>

                    <div class="form-row">
                        <label>Header-Beschriftung</label>
                        <input type="text" name="header_label" value="<?php echo htmlspecialchars($settings['header_label']); ?>" class="form-control">
                        <span class="description">Z. B: Inhalt, Inhaltsverzeichnis, Seiteninhalt</span>
                    </div>
                    
                    <div class="form-row">
                         <label>Hierarchie & Zähler</label>
                         <div class="checkbox-list">
                             <label><input type="checkbox" name="show_hierarchy" value="1" <?php echo checked(true, $settings['show_hierarchy']); ?>> Als Hierarchie anzeigen</label>
                             <label><input type="checkbox" name="show_counter" value="1" <?php echo checked(true, $settings['show_counter']); ?>> Nummerierung anzeigen</label>
                         </div>
                    </div>

                    <div class="form-row">
                        <label>Sanfter Bildlauf</label>
                        <div>
                             <label><input type="checkbox" name="smooth_scroll" value="1" <?php echo checked(true, $settings['smooth_scroll']); ?>> Aktivieren</label>
                             <div style="margin-top:0.5rem; display:flex; align-items:center; gap:1rem;">
                                 <span>Versatz Desktop (px): <input type="number" name="smooth_scroll_offset" value="<?php echo $settings['smooth_scroll_offset']; ?>" style="width:70px; padding:4px;"></span>
                                 <span>Versatz Mobile (px):  <input type="number" name="mobile_scroll_offset" value="<?php echo $settings['mobile_scroll_offset']; ?>" style="width:70px; padding:4px;"></span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Design -->
            <div class="toc-section">
                <div class="toc-header">🎨 Design</div>
                <div class="toc-body">
                    <div class="form-row">
                        <label>Breite</label>
                        <select name="width" class="form-control" style="max-width:200px;">
                            <option value="auto" <?php selected('auto', $settings['width']); ?>>Automatisch</option>
                            <option value="100%" <?php selected('100%', $settings['width']); ?>>100% (Volle Breite)</option>
                            <option value="custom" <?php selected('custom', $settings['width']); ?>>Benutzerdefiniert</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Ausrichtung</label>
                        <select name="alignment" class="form-control" style="max-width:200px;">
                            <option value="none" <?php selected('none', $settings['alignment']); ?>>Keine (Standard)</option>
                            <option value="left" <?php selected('left', $settings['alignment']); ?>>Links</option>
                            <option value="right" <?php selected('right', $settings['alignment']); ?>>Rechts</option>
                            <option value="center" <?php selected('center', $settings['alignment']); ?>>Zentriert</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Theme</label>
                        <select name="theme" class="form-control" style="max-width:200px;" onchange="document.getElementById('custom-colors').style.display = (this.value === 'custom') ? 'block' : 'none';">
                            <option value="grey" <?php selected('grey', $settings['theme']); ?>>Grau (Standard)</option>
                            <option value="light-blue" <?php selected('light-blue', $settings['theme']); ?>>Hellblau</option>
                            <option value="white" <?php selected('white', $settings['theme']); ?>>Weiß</option>
                            <option value="black" <?php selected('black', $settings['theme']); ?>>Schwarz</option>
                            <option value="transparent" <?php selected('transparent', $settings['theme']); ?>>Transparent</option>
                            <option value="custom" <?php selected('custom', $settings['theme']); ?>>Individuell</option>
                        </select>
                    </div>

                    <div id="custom-colors" style="display: <?php echo ($settings['theme'] === 'custom') ? 'block' : 'none'; ?>; border-left:3px solid #3b82f6; padding-left:1rem; margin-bottom:1.5rem;">
                        <h4 style="margin-top:0;">Individuelle Farben</h4>
                        <div class="form-row">
                            <label>Hintergrundfarbe</label>
                            <input type="color" name="custom_bg_color" value="<?php echo $settings['custom_bg_color']; ?>">
                        </div>
                        <div class="form-row">
                            <label>Randfarbe</label>
                            <input type="color" name="custom_border_color" value="<?php echo $settings['custom_border_color']; ?>">
                        </div>
                        <div class="form-row">
                            <label>Titelfarbe</label>
                            <input type="color" name="custom_title_color" value="<?php echo $settings['custom_title_color']; ?>">
                        </div>
                        <div class="form-row">
                            <label>Linkfarbe</label>
                            <input type="color" name="custom_link_color" value="<?php echo $settings['custom_link_color']; ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Erweitert -->
            <div class="toc-section">
                <div class="toc-header">🛠️ Erweitert</div>
                <div class="toc-body">
                    <div class="form-row">
                        <label>Anker-Einstellungen</label>
                        <div class="checkbox-list">
                            <label><input type="checkbox" name="lowercase" value="1" <?php echo checked(true, $settings['lowercase']); ?>> Kleinbuchstaben erzwingen</label>
                            <label><input type="checkbox" name="hyphenate" value="1" <?php echo checked(true, $settings['hyphenate']); ?>> Bindestriche statt Unterstriche</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Überschriften wählen</label>
                        <div class="heading-toggles">
                            <?php foreach(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h): ?>
                                <label>
                                    <input type="checkbox" name="headings[]" value="<?php echo $h; ?>" <?php echo in_array($h, $settings['headings']) ? 'checked' : ''; ?>> 
                                    <span><?php echo strtoupper($h); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <span class="description">Wähle die Überschriften, die berücksichtigt werden sollen.</span>
                    </div>

                    <div class="form-row">
                        <label>Ausschließen</label>
                        <input type="text" name="exclude_headings" value="<?php echo htmlspecialchars($settings['exclude_headings']); ?>" class="form-control" placeholder="Beispiel: Ähnliche Beiträge|Kommentare">
                        <span class="description">Überschriften mit diesem Text ausschließen (Pipe | trennen).</span>
                    </div>

                    <div class="form-row">
                        <label>Pfad einschränken</label>
                        <input type="text" name="limit_path" value="<?php echo htmlspecialchars($settings['limit_path']); ?>" class="form-control" placeholder="/blog/kategorie/">
                        <span class="description"> TOC nur auf Seiten anzeigen, die mit diesem Pfad beginnen (leer lassen für alle).</span>
                    </div>

                    <div class="form-row">
                        <label>Anker-Präfix</label>
                        <input type="text" name="anchor_prefix" value="<?php echo htmlspecialchars($settings['anchor_prefix']); ?>" class="form-control" placeholder="i-">
                        <span class="description">Optionales Präfix für Anker-Links (z.B. "toc-").</span>
                    </div>

                    <div class="form-row">
                         <label>Weitere Optionen</label>
                         <div class="checkbox-list">
                             <label><input type="checkbox" name="homepage_toc" value="1" <?php echo checked(true, $settings['homepage_toc']); ?>> Auf der Startseite anzeigen</label>
                             <label><input type="checkbox" name="remove_toc_links" value="1" <?php echo checked(true, $settings['remove_toc_links']); ?>> Verlinkung entfernen (nur Liste)</label>
                             <label><input type="checkbox" name="exclude_css" value="1" <?php echo checked(true, $settings['exclude_css']); ?>> CSS nicht laden (für eigene Styles)</label>
                         </div>
                    </div>
                </div>
            </div>
            
            <!-- Sticky TOC -->
            <div class="toc-section">
                 <div class="toc-header">📌 Sticky Sidebar TOC</div>
                 <div class="toc-body">
                     <div class="form-row">
                         <label>Sticky TOC</label>
                         <label><input type="checkbox" name="sticky_toggle" value="1" <?php echo checked(true, $settings['sticky_toggle']); ?>> Inhaltsverzeichnis als Sticky Widget aktivieren</label>
                     </div>
                 </div>
            </div>
            
             <!-- Shortcodes & Info -->
            <div class="toc-section">
                 <div class="toc-header">ℹ️ Shortcodes & Info</div>
                 <div class="toc-body">
                     <p>Du kannst das Inhaltsverzeichnis auch manuell per Shortcode einfügen:</p>
                     <code style="display:block; background:#f1f5f9; padding:1rem; margin:0.5rem 0;">[ez-toc]</code>
                     <p><small>Verfügbare Attribute: <code>[ez-toc header_label="Mein Inhalt" display_counter="no"]</code></small></p>
                 </div>
            </div>

            <div style="padding-bottom: 3rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size:1.1rem;">Einstellungen speichern</button>
            </div>

        </form>
    </div>
    
    <?php renderAdminLayoutEnd(); ?>
