<?php
/**
 * CMS Default Theme - Customizer Settings
 *
 * Stellt die vollständige Admin-Oberfläche für Theme-Einstellungen bereit.
 *
 * @package CMSv2\Themes\CmsDefault\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\ThemeCustomizer;
use CMS\Security;

// Helper für Sidebar laden
// Pfad-Logik für verschiedene Deployment-Szenarien
$possiblePaths = [
    // 1. Wenn ABSPATH korrekt gesetzt ist (Standard)
    (defined('ABSPATH') ? rtrim(ABSPATH, '/\\') : '') . '/admin/partials/admin-menu.php',
    // 2. Relativ vom Theme-Ordner, wenn CMS Ordner parallel liegt
    dirname(__DIR__, 3) . '/CMS/admin/partials/admin-menu.php',
    // 3. Wenn alles im Root liegt (kein CMS Unterordner)
    dirname(__DIR__, 2) . '/admin/partials/admin-menu.php',
];

$adminMenuLoaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $adminMenuLoaded = true;
        break;
    }
}

if (!$adminMenuLoaded) {
    // Fallback: Funktions-Dummies definieren, um Fatal Error zu vermeiden
    if (!function_exists('renderAdminSidebar')) {
        function renderAdminSidebar($slug) { echo "<!-- Sidebar fallback for $slug -->"; }
    }
    if (!function_exists('renderAdminSidebarStyles')) {
        function renderAdminSidebarStyles() { }
    }
}

// 1. Konfiguration laden
$config = [
    'header' => [
        'title' => '🖼️ Header & Logo',
        'sections' => [
            'logo_type' => [
                'label'   => 'Logo Typ',
                'type'    => 'select',
                'options' => ['text' => 'Nur Text', 'image' => 'Bild-Logo'],
                'default' => 'text',
            ],
            'logo_url' => [
                'label'       => 'Logo Bild',
                'description' => 'Bild hochladen oder URL eingeben. Gilt nur bei Typ = Bild-Logo.',
                'type'        => 'image_upload',
                'default'     => '',
            ],
            'logo_height' => [
                'label'       => 'Logo Höhe (px)',
                'description' => 'Maximale Höhe des Logo-Bildes im Header.',
                'type'        => 'number',
                'default'     => 40,
            ],
            'logo_text' => [
                'label'       => 'Logo Text',
                'description' => 'Wird angezeigt, wenn Typ = Nur Text.',
                'type'        => 'text',
                'default'     => '365CMS',
            ],
            'logo_tagline' => [
                'label'       => 'Tagline / Untertitel',
                'description' => 'Kleine Zeile rechts neben dem Text-Logo.',
                'type'        => 'text',
                'default'     => '',
            ],
            'header_title' => [
                'label'       => 'Titel rechts neben Logo',
                'description' => 'Optionaler Seitentitel, der rechts neben dem Logo im Header angezeigt wird.',
                'type'        => 'text',
                'default'     => '',
            ],
            'show_search_btn' => [
                'label'   => 'Such-Button im Header anzeigen',
                'type'    => 'checkbox',
                'default' => true,
            ],
            'show_login_btn' => [
                'label'   => 'Anmelden-Button anzeigen',
                'type'    => 'checkbox',
                'default' => true,
            ],
            'show_register_btn' => [
                'label'   => 'Registrieren-Button anzeigen',
                'type'    => 'checkbox',
                'default' => true,
            ],
            'header_stripe_enabled' => [
                'label'   => 'Farbstreifen oben am Header anzeigen',
                'type'    => 'checkbox',
                'default' => true,
            ],
        ]
    ],
    'navigation' => [
        'title' => '🗂️ Navigation',
        'sections' => [
            'header_bar_mode' => [
                'label'       => 'Leiste unter Header',
                'type'        => 'select',
                'options'     => [
                    'none'       => 'Nicht anzeigen',
                    'categories' => 'Kategorien automatisch',
                    'menu'       => 'Sekundäres Menü',
                ],
                'default'     => 'categories',
                'description' => 'Bei "Sekundäres Menü" muss ein Menü der Position "Sekundäres Menü" (Admin → Menüs) zugewiesen sein.',
            ],
            'mobile_menu_enabled' => [
                'label'   => 'Mobile Menü (Hamburger) aktivieren',
                'type'    => 'checkbox',
                'default' => true,
            ],
        ]
    ],
    'layout' => [
        'title' => '📐 Layout & Design',
        'sections' => [
            'max_width' => [
                'label'       => 'Maximale Seiten-Breite (px)',
                'type'        => 'number',
                'default'     => 1140,
            ],
            'sticky_header' => [
                'label'   => 'Sticky Header (bleibt beim Scrollen sichtbar)',
                'type'    => 'checkbox',
                'default' => true,
            ],
            'content_layout' => [
                'label'   => 'Inhalts-Layout',
                'type'    => 'select',
                'options' => [
                    'with_sidebar'    => 'Mit Sidebar (Haupt + Seitenleiste)',
                    'full_width'      => 'Volle Breite (keine Sidebar)',
                ],
                'default' => 'with_sidebar',
            ],
            'post_col_width' => [
                'label'       => 'Text-Spalten-Breite (px)',
                'description' => 'Maximale Breite des Haupt-Textbereichs (z. B. Blogartikel-Text).',
                'type'        => 'number',
                'default'     => 680,
            ],
            'border_radius' => [
                'label'       => 'Eck-Radius (px)',
                'description' => 'Abrundung von Karten, Buttons und Eingabefeldern.',
                'type'        => 'number',
                'default'     => 3,
            ],
        ]
    ],
    'colors' => [
        'title' => '🎨 Farben',
        'sections' => [
            'accent_color' => [
                'label'   => 'Akzentfarbe (Haupt-Highlight)',
                'type'    => 'color',
                'default' => '#c0862a',
            ],
            'accent_dark_color' => [
                'label'       => 'Akzentfarbe Hover/Dunkel',
                'description' => 'Wird bei Hover-Effekten verwendet.',
                'type'        => 'color',
                'default'     => '#a06b18',
            ],
            'ink_color' => [
                'label'   => 'Textfarbe (Primär)',
                'type'    => 'color',
                'default' => '#1a1a18',
            ],
            'ink_soft_color' => [
                'label'       => 'Textfarbe (Weich)',
                'description' => 'Für sekundäre Texte, Nav-Items.',
                'type'        => 'color',
                'default'     => '#3d3d3a',
            ],
            'ink_muted_color' => [
                'label'       => 'Textfarbe (Gedämpft)',
                'description' => 'Für Meta-Infos, Datumsangaben, Labels.',
                'type'        => 'color',
                'default'     => '#7a7a74',
            ],
            'ground_color' => [
                'label'       => 'Seiten-Hintergrundfarbe',
                'description' => 'Haupt-Hintergrund der Website.',
                'type'        => 'color',
                'default'     => '#f7f6f2',
            ],
            'surface_color' => [
                'label'       => 'Karten-/Flächen-Farbe (Surface)',
                'description' => 'Für weiße Karteninhalte.',
                'type'        => 'color',
                'default'     => '#ffffff',
            ],
            'surface_tint_color' => [
                'label'       => 'Surface Tint',
                'description' => 'Leicht getönter Hintergrund für Tabellenköpfe, etc.',
                'type'        => 'color',
                'default'     => '#f2f1ec',
            ],
            'rule_color' => [
                'label'       => 'Trennlinien-Farbe',
                'description' => 'Linien zwischen Elementen, Rahmen.',
                'type'        => 'color',
                'default'     => '#e2e0d8',
            ],
            'header_bg_color' => [
                'label'       => 'Header-Hintergrundfarbe',
                'type'        => 'color',
                'default'     => '#ffffff',
            ],
            'header_stripe_color' => [
                'label'       => 'Header-Akzentstreifen Farbe',
                'description' => 'Der dünne farbige Streifen ganz oben am Header.',
                'type'        => 'color',
                'default'     => '#1a1a18',
            ],
        ]
    ],
    'typography' => [
        'title' => '✏️ Typografie',
        'sections' => [
            'font_size_base' => [
                'label'       => 'Basis Schriftgröße (px)',
                'description' => 'Standard-Schriftgröße für Fließtext.',
                'type'        => 'number',
                'default'     => 15,
            ],
            'line_height' => [
                'label'       => 'Zeilenhöhe',
                'description' => 'z. B. 1.6 für entspanntes Lesen (Dezimalwert).',
                'type'        => 'text',
                'default'     => '1.6',
            ],
            'heading_weight' => [
                'label'   => 'Überschriften Schriftstärke',
                'type'    => 'select',
                'options' => ['600' => '600 (Semi-Bold)', '700' => '700 (Bold)', '800' => '800 (Extra-Bold)', '900' => '900 (Black)'],
                'default' => '700',
            ],
            'google_fonts' => [
                'label'       => 'Google Fonts laden',
                'description' => 'Libre Baskerville (Überschriften) + DM Sans (Text). Deaktivieren für DSGVO-Konformität ohne externe Anfragen.',
                'type'        => 'checkbox',
                'default'     => true,
            ],
        ]
    ],
    'footer' => [
        'title' => '🔻 Footer',
        'sections' => [
            'footer_description' => [
                'label'   => 'Footer Beschreibungstext (Brand-Spalte)',
                'type'    => 'textarea',
                'default' => 'Aktuelle Themen, fundierte Analysen und persönliche Geschichten – täglich neu.',
            ],
            'footer_bg_color' => [
                'label'       => 'Footer Hintergrundfarbe',
                'type'        => 'color',
                'default'     => '#1a1a18',
            ],
            'footer_text_color' => [
                'label'       => 'Footer Textfarbe',
                'type'        => 'color',
                'default'     => '#9a9a94',
            ],
            'footer_accent_color' => [
                'label'       => 'Footer Link-Farbe',
                'type'        => 'color',
                'default'     => '#c0862a',
            ],
            'col1_title' => [
                'label'   => 'Titel Link-Spalte 1',
                'type'    => 'text',
                'default' => 'Rubriken',
            ],
            'col2_title' => [
                'label'   => 'Titel Link-Spalte 2',
                'type'    => 'text',
                'default' => 'Ressourcen',
            ],
            'col3_title' => [
                'label'   => 'Titel Link-Spalte 3',
                'type'    => 'text',
                'default' => 'Über',
            ],
            'show_social_icons' => [
                'label'   => 'Social Icons anzeigen',
                'type'    => 'checkbox',
                'default' => true,
            ],
            'copyright_text' => [
                'label'       => 'Copyright Text',
                'description' => 'Platzhalter: {year}, {site_title}',
                'type'        => 'text',
                'default'     => '© {year} 365CMS. Alle Rechte vorbehalten.',
            ],
            'social_twitter' => [
                'label'   => 'Twitter / X URL',
                'type'    => 'text',
                'default' => '',
            ],
            'social_instagram' => [
                'label'   => 'Instagram URL',
                'type'    => 'text',
                'default' => '',
            ],
            'social_linkedin' => [
                'label'   => 'LinkedIn URL',
                'type'    => 'text',
                'default' => '',
            ],
            'social_youtube' => [
                'label'   => 'YouTube URL',
                'type'    => 'text',
                'default' => '',
            ],
        ]
    ],
    'homepage' => [
        'title' => '🏠 Startseite',
        'sections' => [
            'homepage_mode' => [
                'label'       => 'Startseiten-Modus',
                'type'        => 'select',
                'options'     => [
                    'posts'   => '📰 Beitragsübersicht (Blog)',
                    'landing' => '🎯 Statische Landing Page',
                ],
                'default'     => 'posts',
                'description' => 'Legt fest, was auf der Startseite angezeigt wird.',
            ],
            'homepage_posts_count' => [
                'label'       => 'Anzahl Beiträge auf Startseite',
                'type'        => 'number',
                'default'     => 10,
                'description' => 'Wie viele Artikel direkt auf der Startseite erscheinen.',
            ],
            'homepage_show_hero' => [
                'label'       => 'Hero-Artikel anzeigen',
                'type'        => 'checkbox',
                'default'     => true,
                'description' => 'Den ersten/angehefteten Beitrag groß als Hero darstellen.',
            ],
            'homepage_hero_type' => [
                'label'       => 'Hero-Artikel Quelle',
                'type'        => 'select',
                'options'     => [
                    'latest' => 'Neuester Beitrag',
                    'sticky' => 'Angehefteter Beitrag',
                ],
                'default'     => 'latest',
                'description' => 'Welcher Beitrag soll als Hero hervorgehoben werden?',
            ],
            'homepage_hero_title' => [
                'label'       => 'Hero Überschrift (optional)',
                'type'        => 'text',
                'default'     => '',
                'description' => 'Überschreibt den Artikeltitel im Hero-Bereich. Leer = Artikeltitel.',
            ],
            'homepage_cta_text' => [
                'label'       => 'CTA Button Text',
                'type'        => 'text',
                'default'     => '',
                'description' => 'Text des Call-to-Action Buttons. Leer = Button ausgeblendet.',
            ],
            'homepage_cta_url' => [
                'label'       => 'CTA Button URL',
                'type'        => 'text',
                'default'     => '',
                'description' => 'Ziel-URL des CTA-Buttons (z. B. /blog oder https://...).',
            ],
        ]
    ],
    'advanced' => [
        'title' => '🔧 Erweitert',
        'sections' => [
            'custom_css' => [
                'label'       => 'Eigenes CSS',
                'description' => 'Wird nach allen anderen Styles geladen.',
                'type'        => 'textarea',
                'default'     => '',
            ],
            'custom_head_code' => [
                'label'       => 'Custom Head Code (Tracking, Meta)',
                'description' => 'Wird im &lt;head&gt; ausgegeben. Nur vertrauenswürdigen Code einfügen!',
                'type'        => 'textarea',
                'default'     => '',
            ],
            'custom_footer_code' => [
                'label'       => 'Custom Footer Code (Analytics, Chat-Widgets)',
                'description' => 'Wird vor &lt;/body&gt; ausgegeben.',
                'type'        => 'textarea',
                'default'     => '',
            ],
        ]
    ],
];

$customizer = ThemeCustomizer::instance();
// Sicherstellen, dass das richtige Theme geladen ist
if (class_exists('\CMS\ThemeManager')) {
    $customizer->setTheme(\CMS\ThemeManager::instance()->getActiveThemeSlug());
}
$activeTab  = $_GET['tab'] ?? 'header';
if (!isset($config[$activeTab])) {
    $activeTab = 'header';
}

// 2. Speichern verarbeiten
$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_theme_options') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'theme_customizer')) {
        $error = 'Sicherheitscheck fehlgeschlagen. Bitte erneut versuchen.';
    } else {
        // Logo-Datei-Upload verarbeiten
        if (!empty($_FILES['logo_upload_file']['tmp_name'])) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['logo_upload_file']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedExts, true)) {
                $uploadDir = UPLOAD_PATH . 'theme-logos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = 'logo-' . time() . '.' . $fileExt;
                $destPath    = $uploadDir . '/' . $newFileName;
                if (move_uploaded_file($_FILES['logo_upload_file']['tmp_name'], $destPath)) {
                    $customizer->set('header', 'logo_url', UPLOAD_URL . '/theme-logos/' . $newFileName);
                } else {
                    $error = 'Logo-Upload fehlgeschlagen. Bitte prüfen Sie die Schreibrechte auf uploads/theme-logos/';
                }
            } else {
                $error = 'Ungültiges Dateiformat. Erlaubt: JPG, PNG, GIF, SVG, WebP';
            }
        }

        if (!$error) {
            // NUR den aktuell aktiven Tab speichern – sonst werden andere Tabs überschrieben!
            $saveTab = $_POST['active_section'] ?? $activeTab;
            if (!isset($config[$saveTab])) {
                $saveTab = $activeTab;
            }
            foreach ($config[$saveTab]['sections'] as $fieldKey => $fieldConfig) {
                $sectionKey = $saveTab;
                $inputName  = "{$sectionKey}_{$fieldKey}";
                // logo_url: Wenn per Upload gesetzt → POST-Wert nicht überschreiben (außer explizit befüllt)
                if ($sectionKey === 'header' && $fieldKey === 'logo_url') {
                    $postVal = $_POST[$inputName] ?? '';
                    if ($postVal !== '') {
                        $customizer->set($sectionKey, $fieldKey, $postVal);
                    }
                    continue;
                }
                if ($fieldConfig['type'] === 'checkbox') {
                    $value = isset($_POST[$inputName]) ? '1' : '0';
                } elseif ($fieldConfig['type'] === 'image_upload') {
                    // Nur speichern wenn explizit gesetzt
                    $value = $_POST[$inputName] ?? null;
                    if ($value === null) { continue; }
                } else {
                    $value = $_POST[$inputName] ?? '';
                }
                $customizer->set($sectionKey, $fieldKey, $value);
            }
            $success = 'Einstellungen für &bdquo;' . htmlspecialchars($config[$saveTab]['title']) . '&ldquo; gespeichert.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Customizer – <?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'CMS'; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo date('Ymd'); ?>">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .customizer-layout { display: flex; gap: 2rem; align-items: flex-start; }
        .customizer-nav { width: 240px; flex-shrink: 0; background: #fff; border-radius: var(--card-radius); border: var(--card-border); overflow: hidden; }
        .customizer-nav a { display: block; padding: 1rem 1.5rem; color: #64748b; text-decoration: none; border-left: 3px solid transparent; transition: all .2s; }
        .customizer-nav a:hover { background: #f8fafc; color: var(--admin-primary); }
        .customizer-nav a.active { background: #eff6ff; color: var(--admin-primary); border-left-color: var(--admin-primary); font-weight: 600; }
        .customizer-content { flex: 1; }
        .form-actions-card { position: sticky; bottom: 1rem; z-index: 10; }
    </style>
</head>
<body class="admin-body">

    <?php renderAdminSidebar('theme-customizer'); ?>

    <div class="admin-content">

        <div class="admin-page-header">
            <div>
                <h2>🎨 Theme Customizer</h2>
                <p>Passe das Aussehen deines Themes an.</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/" target="_blank" class="btn btn-secondary">🌐 Seite ansehen</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="?tab=<?php echo htmlspecialchars($activeTab); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_theme_options">
            <input type="hidden" name="active_section" value="<?php echo htmlspecialchars($activeTab); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo Security::instance()->generateToken('theme_customizer'); ?>">

            <div class="customizer-layout">
                <!-- Sidebar Tabs -->
                <nav class="customizer-nav">
                    <?php foreach ($config as $key => $tab): ?>
                        <a href="?tab=<?php echo $key; ?>" class="<?php echo $activeTab === $key ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($tab['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Content Area -->
                <div class="customizer-content">
                    <?php 
                    if (isset($config[$activeTab])): 
                        $currentSection = $config[$activeTab];
                    ?>
                    <div class="admin-card">
                        <h3><?php echo htmlspecialchars($currentSection['title']); ?></h3>
                        
                        <?php foreach ($currentSection['sections'] as $fieldKey => $field): 
                            $val = $customizer->get($activeTab, $fieldKey, $field['default']);
                            $inputId = "field_{$activeTab}_{$fieldKey}";
                            $inputName = "{$activeTab}_{$fieldKey}";
                        ?>
                        <div class="form-group">
                            <label for="<?php echo $inputId; ?>" class="form-label">
                                <?php echo htmlspecialchars($field['label']); ?>
                            </label>
                            
                            <?php if ($field['type'] === 'textarea'): ?>
                                <textarea id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" class="form-control" rows="4"><?php echo htmlspecialchars((string)$val); ?></textarea>
                            
                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                <div style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;">
                                    <input type="checkbox" id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" value="1" <?php echo $val ? 'checked' : ''; ?>>
                                    <label for="<?php echo $inputId; ?>" style="cursor:pointer;">Aktivieren</label>
                                </div>

                            <?php elseif ($field['type'] === 'select'): ?>
                                <select id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" class="form-control">
                                    <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string)$optVal); ?>" <?php echo (string)$val === (string)$optVal ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($optLabel); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($field['type'] === 'image_upload'): ?>
                                <?php $previewUrl = $val ? htmlspecialchars((string)$val) : ''; ?>
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <!-- Vorschau -->
                                    <div id="logo-preview-wrap" style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:6px;padding:12px;display:flex;align-items:center;gap:12px;min-height:60px;">
                                        <?php if ($previewUrl): ?>
                                            <img id="logo-preview-img" src="<?php echo $previewUrl; ?>" alt="Logo" style="max-height:48px;max-width:200px;">
                                        <?php else: ?>
                                            <span id="logo-preview-img" style="color:#94a3b8;font-size:.85rem;">🖼️ Noch kein Logo ausgewählt</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Datei-Upload -->
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:.45rem .9rem;background:#3b82f6;color:#fff;border-radius:5px;font-size:.85rem;font-weight:600;">
                                            📁 Bild hochladen
                                            <input type="file" name="logo_upload_file" accept="image/*" style="display:none;" onchange="previewLogoUpload(this)">
                                        </label>
                                        <span style="color:#64748b;font-size:.8rem;">oder URL eingeben:</span>
                                    </div>
                                    <!-- URL-Eingabe -->
                                    <input type="text" id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" value="<?php echo $previewUrl; ?>" class="form-control" placeholder="https://..." oninput="syncLogoUrlPreview(this.value)">
                                </div>

                            <?php elseif ($field['type'] === 'color'): ?>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <input type="color" id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" value="<?php echo htmlspecialchars((string)$val); ?>" style="height:38px;padding:2px;width:60px;border:1px solid #ddd;border-radius:4px;">
                                    <input type="text" value="<?php echo htmlspecialchars((string)$val); ?>" class="form-control" style="width:120px;" onchange="document.getElementById('<?php echo $inputId; ?>').value = this.value;">
                                </div>

                            <?php else: ?>
                                <input type="<?php echo htmlspecialchars($field['type']); ?>" id="<?php echo $inputId; ?>" name="<?php echo $inputName; ?>" value="<?php echo htmlspecialchars((string)$val); ?>" class="form-control">
                            <?php endif; ?>

                            <?php if (!empty($field['description'])): ?>
                                <small class="form-text"><?php echo htmlspecialchars($field['description']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                    </div>

                    <div class="admin-card form-actions-card">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>

    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    <script>
    // Farb-Picker ↔ Text-Input synchronisieren
    document.querySelectorAll('input[type="color"]').forEach(function(picker) {
        var textInput = picker.nextElementSibling;
        if (!textInput) return;
        picker.addEventListener('input', function() {
            textInput.value = this.value;
        });
        textInput.addEventListener('input', function() {
            var v = this.value.trim();
            if (/^#[0-9a-fA-F]{6}$/.test(v)) { picker.value = v; }
        });
    });

    // Logo-Upload Vorschau
    function previewLogoUpload(input) {
        if (!input.files || !input.files[0]) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            var wrap = document.getElementById('logo-preview-wrap');
            var img  = document.getElementById('logo-preview-img');
            if (img && img.tagName === 'IMG') {
                img.src = e.target.result;
            } else if (wrap) {
                wrap.innerHTML = '<img id="logo-preview-img" src="'+ e.target.result +'" style="max-height:48px;max-width:200px;">';
            }
            // URL-Feld leeren damit Upload-Pfad Vorrang hat
            var urlField = document.querySelector('input[name="header_logo_url"]');
            if (urlField) urlField.value = '';
        };
        reader.readAsDataURL(input.files[0]);
    }

    function syncLogoUrlPreview(url) {
        var wrap = document.getElementById('logo-preview-wrap');
        if (!wrap) return;
        if (url && url.match(/^https?:\/\//)) {
            wrap.innerHTML = '<img id="logo-preview-img" src="'+ url +'" alt="Logo" style="max-height:48px;max-width:200px;" onerror="this.parentElement.innerHTML=\'<span style=color:#ef4444>Bild konnte nicht geladen werden</span>\'">';
        }
    }
    </script>
</body>
</html>
