<?php
/**
 * Theme Editor Admin Page
 * 
 * Umfassender Theme-Customizer mit Live-Vorschau
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
use CMS\ThemeManager;
use CMS\Services\ThemeCustomizer;

if (!defined('ABSPATH')) {
    exit;
}

$auth = Auth::instance();
$security = Security::instance();

// Security check – konsistent mit allen anderen Admin-Dateien
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$customizer = ThemeCustomizer::instance();

// Sicherstellen, dass der Customizer das wirklich aktive Theme verwendet
// (ThemeManager liest den Slug aus der DB – zuverlässiger als der Singleton-Fallback)
$customizer->setTheme(ThemeManager::instance()->getActiveThemeSlug());

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'theme_editor')) {
        $message = 'Sicherheitsüberprüfung fehlgeschlagen';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'save_customization':
                $category = $_POST['category'] ?? '';
                $settings = $_POST['settings'] ?? [];

                // Bild-Upload verarbeiten (für image-Typ Felder)
                foreach ($_FILES as $fileKey => $fileData) {
                    if (!str_starts_with($fileKey, 'logo_upload_')) {
                        continue;
                    }
                    if (($fileData['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $settingKey = substr($fileKey, strlen('logo_upload_'));
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
                    $finfo    = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($fileData['tmp_name']);
                    if (!in_array($mimeType, $allowedMimes, true)) {
                        $message     = 'Ungültiger Dateityp. Erlaubt: JPG, PNG, GIF, WebP, SVG';
                        $messageType = 'error';
                        break 2;
                    }
                    if ($fileData['size'] > 2 * 1024 * 1024) {
                        $message     = 'Logo-Datei zu groß (Maximum: 2 MB)';
                        $messageType = 'error';
                        break 2;
                    }
                    $uploadDir = ABSPATH . 'uploads/theme/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $ext      = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
                    $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($fileData['tmp_name'], $uploadDir . $filename)) {
                        $settings[$settingKey] = SITE_URL . '/uploads/theme/' . $filename;
                    }
                }

                if ($category && is_array($settings)) {
                    $result = $customizer->setMultiple([$category => $settings]);
                    if ($result) {
                        $message = 'Theme-Anpassungen erfolgreich gespeichert';
                        $messageType = 'success';
                    } else {
                        $message = 'Fehler beim Speichern der Anpassungen';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'reset_category':
                $category = $_POST['category'] ?? '';
                if ($category) {
                    $options = $customizer->getCustomizationOptions();
                    if (isset($options[$category]['settings'])) {
                        foreach (array_keys($options[$category]['settings']) as $key) {
                            $customizer->reset($category, $key);
                        }
                        $message = 'Kategorie erfolgreich zurückgesetzt';
                        $messageType = 'success';
                    }
                }
                break;
                
            case 'reset_all':
                if ($customizer->resetAll()) {
                    $message = 'Alle Anpassungen wurden zurückgesetzt';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Zurücksetzen';
                    $messageType = 'error';
                }
                break;
                
            case 'export_settings':
                $export = $customizer->export();
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="theme-customizations-' . date('Y-m-d') . '.json"');
                echo json_encode($export, JSON_PRETTY_PRINT);
                exit;
                break;
                
            case 'import_settings':
                if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                    $json = file_get_contents($_FILES['import_file']['tmp_name']);
                    $data = json_decode($json, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if ($customizer->import($data)) {
                            $message = 'Einstellungen erfolgreich importiert';
                            $messageType = 'success';
                        } else {
                            $message = 'Fehler beim Importieren';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Ungültige JSON-Datei';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'generate_css':
                $cssContent = $customizer->generateCSS();
                $cssPath = ABSPATH . 'themes/' . $customizer->getTheme() . '/customizations.css';
                
                if (file_put_contents($cssPath, $cssContent)) {
                    $message = 'CSS erfolgreich generiert: customizations.css';
                    $messageType = 'success';
                } else {
                    $message = 'Fehler beim Schreiben der CSS-Datei';
                    $messageType = 'error';
                }
                break;
        }
        
        // Redirect to prevent form resubmission – Tab beibehalten
        if ($messageType) {
            // $category aus POST, Fallback auf GET-Tab (für reset_all, generate_css etc.)
            $activeTab = ($category ?? '') ?: ($_GET['tab'] ?? 'colors');
            $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
            $redirect_url .= '?tab=' . urlencode($activeTab)
                           . '&message=' . urlencode($message)
                           . '&type=' . $messageType;
            header('Location: ' . $redirect_url);
            exit;
        }
    }
}

// Check for redirect messages – GET-Parameter vor XSS schützen
if (isset($_GET['message'])) {
    $allowedTypes = ['success', 'error', 'info', 'warning'];
    $message = htmlspecialchars(strip_tags($_GET['message']), ENT_QUOTES, 'UTF-8');
    $messageType = in_array($_GET['type'] ?? '', $allowedTypes, true) ? $_GET['type'] : 'info';
}

$currentTab = $_GET['tab'] ?? 'colors';
$themeConfig = $customizer->getThemeConfig();
$customizationOptions = $customizer->getCustomizationOptions();
$themeMetadata = $customizer->getThemeMetadata();

// ── Diagnose-Route: /admin/theme-editor?diag=1 ───────────────────────────
if (isset($_GET['diag']) && Auth::instance()->isAdmin()) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== Theme-Editor Diagnose ===\n";
    echo "Aktives Theme (ThemeManager DB): " . ThemeManager::instance()->getActiveThemeSlug() . "\n";
    echo "Aktives Theme (Customizer):       " . $customizer->getTheme() . "\n";
    $themePath = defined('ABSPATH') ? ABSPATH . 'themes/' . $customizer->getTheme() . '/' : '(ABSPATH not defined)';
    echo "Theme-Pfad:                       " . $themePath . "\n";
    echo "theme.json existiert:             " . (file_exists($themePath . 'theme.json') ? 'JA' : 'NEIN – Datei fehlt!') . "\n";
    echo "Customization-Kategorien:         " . (empty($customizationOptions) ? '(leer – theme.json nicht geladen!)' : implode(', ', array_keys($customizationOptions))) . "\n";
    echo "\nVerfügbare Themes:\n";
    foreach (ThemeManager::instance()->getAvailableThemes() as $t) {
        echo "  - " . ($t['folder'] ?? '?') . " (" . ($t['Name'] ?? $t['name'] ?? '?') . ")\n";
    }
    exit;
}
$allThemes = ThemeManager::instance()->getAvailableThemes();

// Generate CSRF token ONCE for all forms
$csrfToken = $security->generateToken('theme_editor');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Editor - <?php echo htmlspecialchars($themeMetadata['name']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        /* Theme Editor Specific Styles */
        .theme-editor-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            padding: 2rem;
        }
        
        @media (min-width: 1200px) {
            .theme-editor-wrapper {
                grid-template-columns: 350px 1fr;
            }
        }
        
        .theme-sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .theme-info {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .theme-info h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .theme-version {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .theme-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .theme-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .theme-main {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .theme-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }
        
        .theme-tab {
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .theme-tab:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .theme-tab.active {
            background: white;
            color: #667eea;
        }
        
        .theme-content {
            padding: 2rem;
        }
        
        /* Tab Content - Override global admin.css with specific selectors */
        .theme-editor-wrapper .tab-content {
            display: none !important;
        }
        
        .theme-editor-wrapper .tab-content.active {
            display: block !important;
        }
        
        .settings-group {
            margin-bottom: 2rem;
        }
        
        .settings-group:last-child {
            margin-bottom: 0;
        }
        
        .settings-group-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .settings-group-header h3 {
            margin: 0 0 0.25rem 0;
            color: #1e293b;
            font-size: 1.25rem;
        }
        
        .settings-group-header p {
            margin: 0;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .setting-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .setting-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 500;
            color: #334155;
            font-size: 0.875rem;
        }
        
        .setting-description {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: -0.25rem;
        }
        
        .setting-control {
            position: relative;
        }
        
        .setting-control input[type="color"] {
            width: 100%;
            height: 50px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            padding: 4px;
        }
        
        .setting-control input[type="range"] {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #667eea 0%, #764ba2 100%);
            outline: none;
            -webkit-appearance: none;
        }
        
        .setting-control input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .setting-control input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            border: none;
        }
        
        .range-value {
            display: inline-block;
            min-width: 60px;
            text-align: right;
            color: #667eea;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .setting-control select,
        .setting-control input[type="text"],
        .setting-control textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .setting-control select:focus,
        .setting-control input:focus,
        .setting-control textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .setting-control textarea {
            min-height: 200px;
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .preview-frame {
            width: 100%;
            height: 600px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
        }
        
        .import-export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .import-export-card {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }
        
        .import-export-card h4 {
            margin: 0 0 1rem 0;
            color: #1e293b;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Image Upload Control */
        .image-upload-control {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .logo-preview {
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
        }
        .logo-preview img {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
        }
        .logo-preview-empty {
            color: #94a3b8;
            font-size: 0.8rem;
            padding: 1rem 0;
        }
        .image-upload-control input[type="text"] {
            font-size: 0.8rem;
            color: #64748b;
        }
        .logo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.25rem;
            background: #f1f5f9;
            border: 2px solid #cbd5e1;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            transition: all 0.2s;
            /* Button-Reset: admin.css Overrides verhindern */
            appearance: none;
            -webkit-appearance: none;
            text-decoration: none;
            line-height: 1.4;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        button.logo-upload-btn {
            /* Explizit als Button-Element: Browser-Defaults überschreiben */
            font-family: inherit;
        }
        .logo-upload-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f0f0ff;
        }
        .logo-remove-btn {
            align-self: flex-start;
            padding: 0.35rem 0.75rem;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .logo-remove-btn:hover { background: #fecaca; }
    </style>
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('theme'); ?>
    
    <main class="admin-content">
        <div class="admin-page-header">
            <h2>🎨 Theme Editor</h2>
            <p>Passen Sie das Erscheinungsbild Ihrer Website an</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="theme-editor-wrapper">
            <!-- Sidebar -->
            <aside class="theme-sidebar">
                <div class="theme-info">
                    <h2><?php echo htmlspecialchars($themeMetadata['name']); ?></h2>
                    <div class="theme-version">
                        Version <?php echo htmlspecialchars($themeMetadata['version']); ?>
                    </div>
                    <div class="theme-version">
                        von <?php echo htmlspecialchars($themeMetadata['author']); ?>
                    </div>
                </div>
                
                <!-- ── Installierte Themes (Verschoben nach Themeverwaltung) ── -->
                <div style="margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:2px solid #f1f5f9; display:none;">
                    <!-- Content moved to themes.php -->
                </div>

                <div class="theme-actions">
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="generate_css">
                        <button type="submit" class="btn btn-primary">
                            <span class="dashicons dashicons-art"></span>
                            CSS Generieren
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-secondary" id="btn-custom-css">
                        <span class="dashicons dashicons-editor-code"></span>
                        Custom CSS
                    </button>
                    
                    <form method="post" onsubmit="return confirm('Wirklich alle Anpassungen zurücksetzen?');" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="reset_all">
                        <button type="submit" class="btn btn-warning">
                            <span class="dashicons dashicons-image-rotate"></span>
                            Alles Zurücksetzen
                        </button>
                    </form>
                    
                    <div style="border-top: 2px solid #f1f5f9; padding-top: 0.75rem; margin-top: 0.75rem;">
                        <form method="post" style="margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="export_settings">
                            <button type="submit" class="btn btn-secondary">
                                <span class="dashicons dashicons-download"></span>
                                Exportieren
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content -->
            <div class="theme-main">
                <div class="theme-tabs">
                    <?php foreach ($customizationOptions as $categoryKey => $categoryData): ?>
                        <button 
                            type="button" 
                            class="theme-tab <?php echo $currentTab === $categoryKey ? 'active' : ''; ?>" 
                            id="tab-<?php echo $categoryKey; ?>">
                            <?php echo htmlspecialchars($categoryData['label']); ?>
                        </button>
                    <?php endforeach; ?>
                    
                    <button type="button" class="theme-tab" id="tab-import-export">
                        Import/Export
                    </button>
                </div>
                
                <div class="theme-content">
                    <?php if (empty($customizationOptions)): ?>
                        <div style="padding:2rem; background:#fef9c3; border:2px solid #f59e0b; border-radius:10px; color:#92400e;">
                            <h3 style="margin:0 0 1rem 0;">⚠️ Keine Design-Einstellungen gefunden</h3>
                            <p><strong>Erkanntes Theme:</strong> <code><?php echo htmlspecialchars($customizer->getTheme()); ?></code></p>
                            <?php
                                $tPath = defined('ABSPATH') ? ABSPATH . 'themes/' . $customizer->getTheme() . '/theme.json' : '';
                                $exists = $tPath && file_exists($tPath);
                            ?>
                            <p><strong>theme.json Pfad:</strong> <code><?php echo htmlspecialchars($tPath ?: '(unbekannt)'); ?></code></p>
                            <p><strong>theme.json vorhanden:</strong> <?php echo $exists ? '✅ Ja' : '❌ <strong>Nein – Datei fehlt auf dem Server!</strong>'; ?></p>
                            <?php if (!$exists): ?>
                                <hr style="border-color:#f59e0b; margin:1rem 0;">
                                <p><strong>Lösung:</strong> Das Theme-Verzeichnis <code>themes/<?php echo htmlspecialchars($customizer->getTheme()); ?>/</code> existiert nicht auf diesem Server. Bitte lade den kompletten Theme-Ordner hoch.</p>
                                <p>Zusätzlich müssen folgende Core-Dateien aktualisiert werden:</p>
                                <ul>
                                    <li><code>core/Services/ThemeCustomizer.php</code></li>
                                    <li><code>core/ThemeManager.php</code></li>
                                    <li><code>config.php</code></li>
                                </ul>
                            <?php endif; ?>
                            <p><small><a href="?diag=1" style="color:#92400e;">→ Vollständige Diagnose anzeigen</a></small></p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($customizationOptions as $categoryKey => $categoryData): ?>
                        <div id="content-<?php echo $categoryKey; ?>" class="tab-content <?php echo $currentTab === $categoryKey ? 'active' : ''; ?>">
                            <?php
                                $formEnctype = '';
                                foreach ($categoryData['settings'] as $_s) {
                                    if (($_s['type'] ?? '') === 'image') {
                                        $formEnctype = 'enctype="multipart/form-data"';
                                        break;
                                    }
                                }
                            ?>
                            <form method="post" id="form-<?php echo $categoryKey; ?>" <?php echo $formEnctype; ?>>
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="save_customization">
                                <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                                
                                <div class="settings-group">
                                    <div class="settings-group-header">
                                        <h3><?php echo htmlspecialchars($categoryData['label'] ?? ''); ?></h3>
                                        <?php if (!empty($categoryData['description'])): ?>
                                        <p><?php echo htmlspecialchars($categoryData['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="settings-grid">
                                        <?php foreach ($categoryData['settings'] as $settingKey => $setting): 
                                            $currentValue = $customizer->get($categoryKey, $settingKey, $setting['default'] ?? '');
                                        ?>
                                            <div class="setting-item">
                                                <label class="setting-label" for="<?php echo $categoryKey . '_' . $settingKey; ?>">
                                                    <?php echo htmlspecialchars($setting['label']); ?>
                                                    <?php if ($setting['type'] === 'range'): ?>
                                                        <span class="range-value" id="value-<?php echo $categoryKey . '_' . $settingKey; ?>">
                                                            <?php echo $currentValue; ?><?php echo $setting['unit'] ?? ''; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <?php if (!empty($setting['description']) && is_string($setting['description'])): ?>
                                                    <div class="setting-description">
                                                        <?php echo htmlspecialchars($setting['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="setting-control">
                                                    <?php if ($setting['type'] === 'color'): ?>
                                                        <input 
                                                            type="color" 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]"
                                                            value="<?php echo htmlspecialchars((string)$currentValue); ?>">
                                                    
                                                    <?php elseif ($setting['type'] === 'range'): ?>
                                                        <input 
                                                            type="range" 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]"
                                                            min="<?php echo $setting['min']; ?>"
                                                            max="<?php echo $setting['max']; ?>"
                                                            step="<?php echo $setting['step']; ?>"
                                                            value="<?php echo htmlspecialchars((string)$currentValue); ?>"
                                                            oninput="updateRangeValue('<?php echo $categoryKey . '_' . $settingKey; ?>', this.value, '<?php echo $setting['unit'] ?? ''; ?>')">
                                                    
                                                    <?php elseif ($setting['type'] === 'select'): ?>
                                                        <select 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]">
                                                            <?php foreach ($setting['options'] as $optionValue => $optionLabel): ?>
                                                                <option 
                                                                    value="<?php echo htmlspecialchars((string)$optionValue); ?>"
                                                                    <?php echo $currentValue === $optionValue ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars((string)$optionLabel); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    
                                                    <?php elseif ($setting['type'] === 'checkbox'): ?>
                                                        <div class="checkbox-wrapper">
                                                            <!-- Hidden input sichert value=0 wenn Checkbox deaktiviert -->
                                                            <input type="hidden"
                                                                name="settings[<?php echo $settingKey; ?>]"
                                                                value="0">
                                                            <input 
                                                                type="checkbox" 
                                                                id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                                name="settings[<?php echo $settingKey; ?>]"
                                                                value="1"
                                                                <?php echo $currentValue ? 'checked' : ''; ?>>
                                                            <label for="<?php echo $categoryKey . '_' . $settingKey; ?>">
                                                                Aktivieren
                                                            </label>
                                                        </div>
                                                    
                                                    <?php elseif ($setting['type'] === 'text'): ?>
                                                        <input 
                                                            type="text" 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]"
                                                            value="<?php echo htmlspecialchars((string)$currentValue); ?>">

                                                    <?php elseif ($setting['type'] === 'textarea'): ?>
                                                        <textarea 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]"><?php echo htmlspecialchars((string)$currentValue); ?></textarea>
                                                    
                                                    <?php elseif ($setting['type'] === 'image'):
                                                        $uid = $categoryKey . '_' . $settingKey; ?>
                                                        <div class="image-upload-control">
                                                            <!-- Vorschau -->
                                                            <div class="logo-preview" id="preview-<?php echo $uid; ?>">
                                                                <?php if (!empty($currentValue)): ?>
                                                                    <img src="<?php echo htmlspecialchars((string)$currentValue, ENT_QUOTES, 'UTF-8'); ?>"
                                                                         alt="Logo Vorschau"
                                                                         id="preview-img-<?php echo $uid; ?>">
                                                                <?php else: ?>
                                                                    <span class="logo-preview-empty" id="preview-img-<?php echo $uid; ?>">
                                                                        Noch kein Logo hinterlegt
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <!-- Upload-Buttons Zeile -->
                                                            <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
                                                                <button type="button"
                                                                    class="logo-upload-btn"
                                                                    onclick="document.getElementById('logo_upload_<?php echo $settingKey; ?>').click()">
                                                                    📁 Logo hochladen
                                                                </button>
                                                                <?php if (!empty($currentValue)): ?>
                                                                    <button type="button" class="logo-remove-btn"
                                                                        onclick="removeLogo('<?php echo $uid; ?>')">
                                                                        🗑 Entfernen
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <!-- Versteckter File-Input -->
                                                            <input type="file"
                                                                id="logo_upload_<?php echo $settingKey; ?>"
                                                                name="logo_upload_<?php echo $settingKey; ?>"
                                                                accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                                                                style="display:none;"
                                                                onchange="previewSelectedFile(this, '<?php echo $uid; ?>')">
                                                            <!-- URL-Feld (kollabiert, als Fallback) -->
                                                            <input type="text"
                                                                id="<?php echo $uid; ?>"
                                                                name="settings[<?php echo $settingKey; ?>]"
                                                                value="<?php echo htmlspecialchars((string)$currentValue, ENT_QUOTES, 'UTF-8'); ?>"
                                                                placeholder="oder Logo-URL direkt einfügen …"
                                                                onchange="updateLogoPreview('<?php echo $uid; ?>', this.value)"
                                                                style="font-size:0.78rem;color:#94a3b8;">
                                                        </div>
                                                    <?php else: ?>
                                                        <input 
                                                            type="text" 
                                                            id="<?php echo $categoryKey . '_' . $settingKey; ?>"
                                                            name="settings[<?php echo $settingKey; ?>]"
                                                            value="<?php echo htmlspecialchars((string)$currentValue); ?>">
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f1f5f9; display: flex; gap: 1rem;">
                                        <button type="submit" class="btn btn-primary">
                                            <span class="dashicons dashicons-saved"></span>
                                            Änderungen Speichern
                                        </button>
                                        
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary"
                                            onclick="if(confirm('Diese Kategorie zurücksetzen?')) { document.getElementById('reset-form-<?php echo $categoryKey; ?>').submit(); }">
                                            <span class="dashicons dashicons-image-rotate"></span>
                                            Zurücksetzen
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <form method="post" id="reset-form-<?php echo $categoryKey; ?>" style="display: none;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="reset_category">
                                <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                            </form>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Import/Export Tab -->
                    <div id="content-import-export" class="tab-content">
                        <div class="settings-group">
                            <div class="settings-group-header">
                                <h3>Import & Export</h3>
                                <p>Sichern Sie Ihre Theme-Einstellungen oder importieren Sie bestehende Konfigurationen</p>
                            </div>
                            
                            <div class="import-export-grid">
                                <div class="import-export-card">
                                    <h4>Einstellungen Exportieren</h4>
                                    <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.875rem;">
                                        Laden Sie alle aktuellen Theme-Anpassungen als JSON-Datei herunter.
                                    </p>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="export_settings">
                                        <button type="submit" class="btn btn-primary">
                                            <span class="dashicons dashicons-download"></span>
                                            Jetzt Exportieren
                                        </button>
                                    </form>
                                </div>
                                
                                <div class="import-export-card">
                                    <h4>Einstellungen Importieren</h4>
                                    <p style="margin-bottom: 1rem; color: #64748b; font-size: 0.875rem;">
                                        Laden Sie eine zuvor exportierte JSON-Datei hoch.
                                    </p>
                                    <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="import_settings">
                                        
                                        <div class="file-input-wrapper">
                                            <input type="file" name="import_file" id="import-file" accept=".json" required>
                                            <label for="import-file" class="file-input-label">
                                                <span class="dashicons dashicons-upload"></span>
                                                Datei Auswählen
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
                                            <span class="dashicons dashicons-upload"></span>
                                            Importieren
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Tab switching function
        function switchTab(tabId) {
            console.log('=== SWITCHING TO TAB:', tabId, '===');
            
            // Hide all tab contents and remove active class
            const allContents = document.querySelectorAll('.theme-content .tab-content');
            allContents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none'; // Force hide
            });
            
            // Remove active class from all tabs
            const allTabs = document.querySelectorAll('.theme-tabs .theme-tab');
            allTabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected content
            const contentId = 'content-' + tabId;
            const content = document.getElementById(contentId);
            if (content) {
                content.classList.add('active');
                content.style.display = 'block'; // Force show
                console.log('✓ Content shown:', contentId);
            } else {
                console.error('✗ Content not found:', contentId);
            }
            
            // Activate clicked tab
            const newTabId = 'tab-' + tabId;
            const tab = document.getElementById(newTabId);
            if (tab) {
                tab.classList.add('active');
                console.log('✓ Tab activated:', newTabId);
            } else {
                console.error('✗ Tab not found:', newTabId);
            }
            
            console.log('=== END SWITCH ===');
        }
        
        // Initialize tabs when DOM is ready
        function initThemeEditor() {
            console.log('Theme Editor: Initializing...');
            
            // Attach click event listeners to all tabs
            const tabs = document.querySelectorAll('.theme-tabs .theme-tab');
            console.log('Theme Editor: Found', tabs.length, 'tabs');
            
            tabs.forEach((tab, index) => {
                const tabId = tab.id.replace('tab-', '');
                console.log('  Tab', index + 1, ':', tab.id, '→', tabId);
                
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Theme Editor: Tab clicked:', tabId);
                    switchTab(tabId);
                });
            });
            
            // Custom CSS button
            const btnCustomCss = document.getElementById('btn-custom-css');
            if (btnCustomCss) {
                btnCustomCss.addEventListener('click', function(e) {
                    e.preventDefault();
                    switchTab('advanced');
                });
            }
            
            // Log all content divs
            const contents = document.querySelectorAll('.theme-content .tab-content');
            console.log('Theme Editor: Found', contents.length, 'content divs');
            contents.forEach((content, index) => {
                const isActive = content.classList.contains('active');
                console.log('  Content', index + 1, ':', content.id, 'active:', isActive);
                // Force initial state based on class
                if (isActive) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
            
            console.log('Theme Editor: Initialization complete');
        }
        
        // Run immediately if DOM already loaded, otherwise wait
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initThemeEditor);
        } else {
            initThemeEditor();
        }
        
        function updateRangeValue(id, value, unit) {
            const display = document.getElementById('value-' + id);
            if (display) {
                display.textContent = value + (unit || '');
            }
        }

        function updateLogoPreview(id, url) {
            const previewEl = document.getElementById('preview-img-' + id);
            if (!previewEl) return;
            if (url) {
                if (previewEl.tagName === 'IMG') {
                    previewEl.src = url;
                } else {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = 'Logo Vorschau';
                    img.id  = 'preview-img-' + id;
                    previewEl.replaceWith(img);
                }
            } else {
                const p = document.createElement('p');
                p.className = 'logo-preview-empty';
                p.id        = 'preview-img-' + id;
                p.textContent = 'Noch kein Logo hinterlegt';
                previewEl.replaceWith(p);
            }
        }

        function previewSelectedFile(input, id) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                updateLogoPreview(id, e.target.result);
                // Clear URL field – wird durch Server-Upload befüllt
                const urlInput = document.getElementById(id);
                if (urlInput) urlInput.value = '';
            };
            reader.readAsDataURL(file);
        }

        function removeLogo(id) {
            const urlInput = document.getElementById(id);
            if (urlInput) urlInput.value = '';
            updateLogoPreview(id, '');
            // Datei-Input leeren
            const parts  = id.split('_');
            const sKey   = parts.slice(1).join('_');
            const fileEl = document.getElementById('logo_upload_' + sKey);
            if (fileEl) fileEl.value = '';
        }
    </script>
</body>
</html>
