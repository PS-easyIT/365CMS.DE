<?php
/**
 * Media Management Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\MediaService;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$mediaService = new MediaService();

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Verify Request (Nonce verification should be added here ideally)
    // if (!Security::verifyNonce($_POST['nonce'], 'media_action')) {
    //     wp_send_json_error('Invalid nonce');
    // }

    header('Content-Type: application/json');
    $action = $_POST['action'];
    $currentPath = $_POST['path'] ?? '';

    try {
        switch ($action) {
            case 'list_files':
                $result = $mediaService->getItems($currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true, 'data' => $result]);
                }
                break;

            case 'create_folder':
                $folderName = $_POST['name'] ?? '';
                if (empty($folderName)) {
                    echo json_encode(['success' => false, 'error' => 'Ordnername ist erforderlich']);
                    break;
                }
                $result = $mediaService->createFolder($folderName, $currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case 'upload_file':
                if (!isset($_FILES['file'])) {
                    echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen']);
                    break;
                }
                $result = $mediaService->uploadFile($_FILES['file'], $currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true, 'filename' => $result]);
                }
                break;

            case 'delete_item':
                $itemPath = $_POST['item_path'] ?? ''; // This is the relative path to file/folder
                if (empty($itemPath)) {
                    echo json_encode(['success' => false, 'error' => 'Item path is required']);
                    break;
                }
                $result = $mediaService->deleteItem($itemPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;
            
            case 'rename_item':
                $oldPath = $_POST['old_path'] ?? '';
                $newName = $_POST['new_name'] ?? '';
                if (empty($oldPath) || empty($newName)) {
                    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
                    break;
                }
                $result = $mediaService->renameItem($oldPath, $newName);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case 'save_settings':
                $settings = [
                    // Upload
                    'max_upload_size'          => $_POST['max_upload_size'] ?? '64M',
                    'allowed_types'            => $_POST['allowed_types'] ?? [],
                    // Image processing
                    'auto_webp'                => isset($_POST['auto_webp']),
                    'strip_exif'               => isset($_POST['strip_exif']),
                    'jpeg_quality'             => (int)($_POST['jpeg_quality'] ?? 85),
                    'max_width'                => (int)($_POST['max_width'] ?? 2560),
                    'max_height'               => (int)($_POST['max_height'] ?? 2560),
                    // Thumbnails
                    'generate_thumbnails'      => isset($_POST['generate_thumbnails']),
                    'thumb_small_w'            => (int)($_POST['thumb_small_w'] ?? 150),
                    'thumb_small_h'            => (int)($_POST['thumb_small_h'] ?? 150),
                    'thumb_medium_w'           => (int)($_POST['thumb_medium_w'] ?? 300),
                    'thumb_medium_h'           => (int)($_POST['thumb_medium_h'] ?? 300),
                    'thumb_large_w'            => (int)($_POST['thumb_large_w'] ?? 1024),
                    'thumb_large_h'            => (int)($_POST['thumb_large_h'] ?? 1024),
                    'thumb_banner_w'           => (int)($_POST['thumb_banner_w'] ?? 1200),
                    'thumb_banner_h'           => (int)($_POST['thumb_banner_h'] ?? 400),
                    // Organisation
                    'organize_month_year'      => isset($_POST['organize_month_year']),
                    'sanitize_filenames'       => isset($_POST['sanitize_filenames']),
                    'unique_filenames'         => isset($_POST['unique_filenames']),
                    'lowercase_filenames'      => isset($_POST['lowercase_filenames']),
                    // Member permissions
                    'member_uploads_enabled'   => isset($_POST['member_uploads_enabled']),
                    'member_max_upload_size'   => $_POST['member_max_upload_size'] ?? '5M',
                    'member_allowed_types'     => $_POST['member_allowed_types'] ?? [],
                    'member_delete_own'        => isset($_POST['member_delete_own']),
                    // Security
                    'block_dangerous_types'    => isset($_POST['block_dangerous_types']),
                    'validate_image_content'   => isset($_POST['validate_image_content']),
                    'require_login_for_upload' => isset($_POST['require_login_for_upload']),
                    'protect_uploads_dir'      => isset($_POST['protect_uploads_dir']),
                ];
                
                $result = $mediaService->saveSettings($settings);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Einstellungen erfolgreich gespeichert.']);
                }
                break;

            case 'get_categories':
                echo json_encode(['success' => true, 'data' => $mediaService->getCategories()]);
                break;

            case 'add_category':
                $name = $_POST['name'] ?? '';
                if (empty($name)) {
                    echo json_encode(['success' => false, 'error' => 'Name ist erforderlich']);
                    break;
                }
                $result = $mediaService->addCategory($name);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message()]);
                } else {
                    echo json_encode(['success' => true]);
                }
                break;

            case 'delete_category':
                $slug = $_POST['slug'] ?? '';
                if (empty($slug)) {
                    echo json_encode(['success' => false, 'error' => 'Slug ist erforderlich']);
                    break;
                }
                $result = $mediaService->deleteCategory($slug);
                echo json_encode(['success' => true]);
                break;

            case 'assign_category':
                $filePath = $_POST['file_path'] ?? '';
                $slug = $_POST['slug'] ?? '';
                if (empty($filePath)) {
                    echo json_encode(['success' => false, 'error' => 'Datei ist erforderlich']);
                    break;
                }
                // allow empty slug to unassign
                $result = $mediaService->assignCategory($filePath, $slug);
                echo json_encode(['success' => true]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Ungültige Aktion']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Extract active tab to determine initial filter
$activeTab = $_GET['tab'] ?? 'all';
$initialFilter = 'all';

switch ($activeTab) {
    case 'settings':
    case 'categories':
        $viewMode = $activeTab;
        break;
    default:
        $viewMode = 'files';
        // Map old tabs to filters
        switch ($activeTab) {
            case 'images': $initialFilter = 'image'; break;
            case 'videos': $initialFilter = 'video'; break;
            case 'documents': $initialFilter = 'document'; break;
            case 'audio': $initialFilter = 'audio'; break;
            case 'folders': $initialFilter = 'folder'; break;
            case 'plugins': $initialFilter = 'plugin'; break;
            case 'themes': $initialFilter = 'theme'; break;
            case 'fonts': $initialFilter = 'font'; break;
            default: $initialFilter = 'all'; break;
        }
        break;
}

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';

// Load settings for view (and categories)
$settings = $mediaService->getSettings();
$categories = $mediaService->getCategories();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medienverwaltung - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222c">
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('media-' . ($activeTab === 'all' ? 'library' : $activeTab)); ?>
    
    <!-- Main Content -->
    <div class="admin-content" <?php if($viewMode === 'files') echo 'ondragover="event.preventDefault(); this.classList.add(\'drag-over\');" ondragleave="this.classList.remove(\'drag-over\');" ondrop="handleDrop(event)"'; ?>>
        
        <?php if ($viewMode === 'settings'): ?>
            <!-- SETTINGS VIEW -->
            <div class="admin-page-header">
                <div>
                    <h2>⚙️ Medieneinstellungen</h2>
                    <p class="text-muted">Uploads, Bildverarbeitung, Organisation, Berechtigungen &amp; Sicherheit konfigurieren.</p>
                </div>
            </div>

            <?php
            $isChecked          = fn($key) => !empty($settings[$key]) ? 'checked' : '';
            $isTypeAllowed      = fn($type) => in_array($type, $settings['allowed_types'] ?? []) ? 'checked' : '';
            $isMemberTypeAllowed = fn($type) => in_array($type, $settings['member_allowed_types'] ?? []) ? 'checked' : '';
            $isSelected         = fn($key, $val) => ($settings[$key] ?? '') === $val ? 'selected' : '';
            ?>

            <form id="media-settings-form" onsubmit="event.preventDefault(); saveSettings();">

                <!-- ═══ ROW 1: Upload & Bildverarbeitung ═══ -->
                <div class="settings-grid">

                    <!-- Card: Upload-Einstellungen -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">📁</span>
                            <div>
                                <h3>Upload-Einstellungen</h3>
                                <p>Dateigröße &amp; erlaubte Typen</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="form-group">
                                <label class="form-label">
                                    Maximale Dateigröße
                                    <span class="badge-muted">Server: <?php echo ini_get('upload_max_filesize'); ?></span>
                                </label>
                                <select name="max_upload_size" class="form-control form-control-sm">
                                    <option value="2M"   <?php echo $isSelected('max_upload_size', '2M');   ?>>2 MB</option>
                                    <option value="5M"   <?php echo $isSelected('max_upload_size', '5M');   ?>>5 MB</option>
                                    <option value="10M"  <?php echo $isSelected('max_upload_size', '10M');  ?>>10 MB</option>
                                    <option value="32M"  <?php echo $isSelected('max_upload_size', '32M');  ?>>32 MB</option>
                                    <option value="64M"  <?php echo $isSelected('max_upload_size', '64M');  ?>>64 MB</option>
                                    <option value="128M" <?php echo $isSelected('max_upload_size', '128M'); ?>>128 MB</option>
                                </select>
                                <small class="text-muted">Gilt unabhängig vom PHP-Serverlimit für alle Uploads.</small>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Erlaubte Dateitypen</label>
                                <div class="type-grid">
                                    <label class="type-check">
                                        <input type="checkbox" name="allowed_types[]" value="image" <?php echo $isTypeAllowed('image'); ?>>
                                        <span>🖼️ Bilder</span>
                                        <small>jpg, png, webp, gif</small>
                                    </label>
                                    <label class="type-check">
                                        <input type="checkbox" name="allowed_types[]" value="document" <?php echo $isTypeAllowed('document'); ?>>
                                        <span>📄 Dokumente</span>
                                        <small>pdf, docx, xlsx</small>
                                    </label>
                                    <label class="type-check">
                                        <input type="checkbox" name="allowed_types[]" value="video" <?php echo $isTypeAllowed('video'); ?>>
                                        <span>🎬 Videos</span>
                                        <small>mp4, webm, mov</small>
                                    </label>
                                    <label class="type-check">
                                        <input type="checkbox" name="allowed_types[]" value="audio" <?php echo $isTypeAllowed('audio'); ?>>
                                        <span>🎵 Audio</span>
                                        <small>mp3, wav, ogg</small>
                                    </label>
                                    <label class="type-check">
                                        <input type="checkbox" name="allowed_types[]" value="archive" <?php echo $isTypeAllowed('archive'); ?>>
                                        <span>📦 Archive</span>
                                        <small>zip, rar, 7z</small>
                                    </label>
                                    <label class="type-check type-check--warn">
                                        <input type="checkbox" name="allowed_types[]" value="svg" <?php echo $isTypeAllowed('svg'); ?>>
                                        <span>⚠️ SVG</span>
                                        <small>Sicherheitsrisiko</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Bildverarbeitung -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">🖼️</span>
                            <div>
                                <h3>Bildverarbeitung</h3>
                                <p>Qualität, Konvertierung &amp; Metadaten</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="auto_webp" <?php echo $isChecked('auto_webp'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Automatische WebP-Konvertierung</strong>
                                    <p>JPEGs &amp; PNGs werden beim Upload in WebP umgewandelt.</p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="strip_exif" <?php echo $isChecked('strip_exif'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>EXIF-Metadaten entfernen</strong>
                                    <p>GPS- &amp; Kameradaten werden aus Bildern gelöscht.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">JPEG-Qualität beim Speichern</label>
                                <div class="range-row">
                                    <input type="range" name="jpeg_quality" min="60" max="100" step="5"
                                        value="<?php echo (int)($settings['jpeg_quality'] ?? 85); ?>"
                                        oninput="document.getElementById('jpeg_q_val').textContent = this.value + '%'">
                                    <span id="jpeg_q_val" class="range-val"><?php echo (int)($settings['jpeg_quality'] ?? 85); ?>%</span>
                                </div>
                                <small class="text-muted">Niedrigere Werte = kleinere Dateien, höhere = bessere Qualität.</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Maximale Bilddimensionen (px)</label>
                                <div class="dim-row">
                                    <input type="number" class="form-control form-control-sm" name="max_width"
                                        placeholder="Breite"
                                        value="<?php echo htmlspecialchars((string)($settings['max_width'] ?? 2560)); ?>"
                                        style="width:90px;">
                                    <span class="dim-x">×</span>
                                    <input type="number" class="form-control form-control-sm" name="max_height"
                                        placeholder="Höhe"
                                        value="<?php echo htmlspecialchars((string)($settings['max_height'] ?? 2560)); ?>"
                                        style="width:90px;">
                                </div>
                                <small class="text-muted">Größere Bilder werden beim Upload automatisch skaliert.</small>
                            </div>
                        </div>
                    </div>

                </div><!-- /.settings-grid ROW 1 -->

                <!-- ═══ ROW 2: Organisation & Mitglieder ═══ -->
                <div class="settings-grid">

                    <!-- Card: Organisation & Dateinamen -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">📂</span>
                            <div>
                                <h3>Organisation &amp; Dateinamen</h3>
                                <p>Ordnerstruktur &amp; Benennungsregeln</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="organize_month_year" <?php echo $isChecked('organize_month_year'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Ordner nach Monat/Jahr</strong>
                                    <p>Uploads werden unter <code>uploads/2026/02/</code> abgelegt.</p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="sanitize_filenames" <?php echo $isChecked('sanitize_filenames'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Dateinamen bereinigen</strong>
                                    <p>Sonderzeichen &amp; Leerzeichen werden ersetzt: <code>mein bild.jpg</code> → <code>mein-bild.jpg</code></p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="unique_filenames" <?php echo $isChecked('unique_filenames'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Eindeutige Dateinamen erzwingen</strong>
                                    <p>Verhindert Überschreiben — Duplikate erhalten ein Suffix: <code>bild-1.jpg</code></p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="lowercase_filenames" <?php echo $isChecked('lowercase_filenames'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Kleinbuchstaben erzwingen</strong>
                                    <p>Dateinamen werden automatisch in Kleinbuchstaben umgewandelt.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Mitglieder-Berechtigungen -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">👥</span>
                            <div>
                                <h3>Mitglieder-Berechtigungen</h3>
                                <p>Upload-Rechte für angemeldete Benutzer</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="member_uploads_enabled" id="member_uploads_toggle"
                                        <?php echo $isChecked('member_uploads_enabled'); ?>
                                        onchange="toggleMemberSettings(this.checked)">
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Mitglieder-Uploads aktivieren</strong>
                                    <p>Mitglieder können Dateien in <code>uploads/member/{user}/</code> hochladen.</p>
                                </div>
                            </div>
                            <div id="member-settings-panel" class="member-subsettings" style="<?php echo empty($settings['member_uploads_enabled']) ? 'opacity:0.4;pointer-events:none;' : ''; ?>">
                                <div class="form-group">
                                    <label class="form-label">Max. Dateigröße für Mitglieder</label>
                                    <select name="member_max_upload_size" class="form-control form-control-sm">
                                        <option value="1M"  <?php echo $isSelected('member_max_upload_size', '1M');  ?>>1 MB</option>
                                        <option value="2M"  <?php echo $isSelected('member_max_upload_size', '2M');  ?>>2 MB</option>
                                        <option value="5M"  <?php echo $isSelected('member_max_upload_size', '5M');  ?>>5 MB</option>
                                        <option value="10M" <?php echo $isSelected('member_max_upload_size', '10M'); ?>>10 MB</option>
                                        <option value="32M" <?php echo $isSelected('member_max_upload_size', '32M'); ?>>32 MB</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Erlaubte Typen für Mitglieder</label>
                                    <div class="type-grid type-grid--compact">
                                        <label class="type-check"><input type="checkbox" name="member_allowed_types[]" value="image" <?php echo $isMemberTypeAllowed('image'); ?>><span>🖼️ Bilder</span></label>
                                        <label class="type-check"><input type="checkbox" name="member_allowed_types[]" value="document" <?php echo $isMemberTypeAllowed('document'); ?>><span>📄 Dokumente</span></label>
                                        <label class="type-check"><input type="checkbox" name="member_allowed_types[]" value="video" <?php echo $isMemberTypeAllowed('video'); ?>><span>🎬 Videos</span></label>
                                        <label class="type-check"><input type="checkbox" name="member_allowed_types[]" value="audio" <?php echo $isMemberTypeAllowed('audio'); ?>><span>🎵 Audio</span></label>
                                    </div>
                                </div>
                                <div class="toggle-group">
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="member_delete_own" <?php echo $isChecked('member_delete_own'); ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <div class="toggle-label">
                                        <strong>Eigene Dateien löschen erlauben</strong>
                                        <p>Mitglieder können ihre eigenen Uploads selbst entfernen.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.settings-grid ROW 2 -->

                <!-- ═══ ROW 3: Thumbnail-Größen & Sicherheit ═══ -->
                <div class="settings-grid">

                    <!-- Card: Thumbnail-Größen -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">📐</span>
                            <div>
                                <h3>Thumbnail-Größen</h3>
                                <p>Automatisch generierte Bildgrößen beim Upload</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="toggle-group" style="margin-bottom:1.25rem;">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="generate_thumbnails" <?php echo $isChecked('generate_thumbnails'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Thumbnails automatisch erstellen</strong>
                                    <p>Alle definierten Bildgrößen werden beim Upload generiert.</p>
                                </div>
                            </div>
                            <table class="thumb-sizes-table">
                                <thead>
                                    <tr><th>Größe</th><th>Breite (px)</th><th>Höhe (px)</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="thumb-badge thumb-badge--sm">Klein</span></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_small_w" value="<?php echo (int)($settings['thumb_small_w'] ?? 150); ?>" style="width:80px;"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_small_h" value="<?php echo (int)($settings['thumb_small_h'] ?? 150); ?>" style="width:80px;"></td>
                                    </tr>
                                    <tr>
                                        <td><span class="thumb-badge thumb-badge--md">Mittel</span></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_medium_w" value="<?php echo (int)($settings['thumb_medium_w'] ?? 300); ?>" style="width:80px;"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_medium_h" value="<?php echo (int)($settings['thumb_medium_h'] ?? 300); ?>" style="width:80px;"></td>
                                    </tr>
                                    <tr>
                                        <td><span class="thumb-badge thumb-badge--lg">Groß</span></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_large_w" value="<?php echo (int)($settings['thumb_large_w'] ?? 1024); ?>" style="width:80px;"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_large_h" value="<?php echo (int)($settings['thumb_large_h'] ?? 1024); ?>" style="width:80px;"></td>
                                    </tr>
                                    <tr>
                                        <td><span class="thumb-badge thumb-badge--banner">Banner</span></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_banner_w" value="<?php echo (int)($settings['thumb_banner_w'] ?? 1200); ?>" style="width:80px;"></td>
                                        <td><input type="number" class="form-control form-control-sm" name="thumb_banner_h" value="<?php echo (int)($settings['thumb_banner_h'] ?? 400); ?>" style="width:80px;"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Card: Sicherheit & Schutz -->
                    <div class="settings-card">
                        <div class="sc-header">
                            <span class="sc-icon">🔒</span>
                            <div>
                                <h3>Sicherheit &amp; Schutz</h3>
                                <p>Schutz vor gefährlichen Uploads</p>
                            </div>
                        </div>
                        <div class="sc-body">
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="block_dangerous_types" <?php echo $isChecked('block_dangerous_types'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Gefährliche Dateitypen blockieren</strong>
                                    <p>Verhindert Uploads von <code>.php</code>, <code>.exe</code>, <code>.sh</code> u.&nbsp;a.</p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="validate_image_content" <?php echo $isChecked('validate_image_content'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Bildinhalt validieren</strong>
                                    <p>Überprüft, ob als Bild deklarierte Dateien tatsächlich Bilddaten enthalten.</p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="require_login_for_upload" <?php echo $isChecked('require_login_for_upload'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Login für Uploads erforderlich</strong>
                                    <p>Nur angemeldete Benutzer können Dateien hochladen.</p>
                                </div>
                            </div>
                            <div class="toggle-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="protect_uploads_dir" <?php echo $isChecked('protect_uploads_dir'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <div class="toggle-label">
                                    <strong>Upload-Verzeichnis schützen</strong>
                                    <p>Erstellt eine <code>.htaccess</code>, die direkte PHP-Ausführung verhindert.</p>
                                </div>
                            </div>
                            <div class="settings-info-box">
                                ℹ️ Aktivieren Sie alle Optionen für maximale Sicherheit (empfohlen für Produktivumgebungen).
                            </div>
                        </div>
                    </div>

                </div><!-- /.settings-grid ROW 3 -->

                <!-- Sticky Save Footer -->
                <div class="sticky-footer">
                    <span id="settings-save-status"></span>
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>

            </form>

            <script>
                function toggleMemberSettings(enabled) {
                    const panel = document.getElementById('member-settings-panel');
                    panel.style.opacity       = enabled ? '1' : '0.4';
                    panel.style.pointerEvents = enabled ? '' : 'none';
                }

                async function saveSettings() {
                    const form   = document.getElementById('media-settings-form');
                    const formData = new FormData(form);
                    formData.append('action', 'save_settings');

                    const btn    = form.querySelector('button[type="submit"]');
                    const status = document.getElementById('settings-save-status');
                    btn.disabled   = true;
                    btn.textContent = '⏳ Speichere...';
                    status.innerHTML = '';

                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const result   = await response.json();

                        if (result.success) {
                            btn.textContent  = '✅ Gespeichert';
                            status.innerHTML = '<span style="color:#10b981;font-size:0.875rem;">✓ Einstellungen wurden erfolgreich gespeichert.</span>';
                            setTimeout(() => {
                                btn.textContent  = '💾 Einstellungen speichern';
                                btn.disabled     = false;
                                status.innerHTML = '';
                            }, 2500);
                        } else {
                            btn.textContent  = '❌ Fehler';
                            status.innerHTML = '<span style="color:#ef4444;font-size:0.875rem;">Fehler: ' + (result.error ?? 'Unbekannt') + '</span>';
                            setTimeout(() => { btn.textContent = '💾 Einstellungen speichern'; btn.disabled = false; }, 3000);
                        }
                    } catch (e) {
                        btn.textContent  = '❌ Netzwerkfehler';
                        status.innerHTML = '<span style="color:#ef4444;font-size:0.875rem;">Netzwerkfehler beim Speichern.</span>';
                        setTimeout(() => { btn.textContent = '💾 Einstellungen speichern'; btn.disabled = false; }, 3000);
                    }
                }
            </script>

        <?php elseif ($viewMode === 'categories'): ?>
            <!-- CATEGORIES VIEW -->
            <div class="admin-page-header">
                <div>
                    <h2>🏷️ Dokumenten-Kategorien</h2>
                    <p class="text-muted">Organisieren Sie Dokumente und Dateien.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openCategoryModal()">Neue Kategorie</button>
                </div>
            </div>
            
            <div class="admin-card">
                <table class="media-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Anzahl Dateien</th>
                            <th style="text-align:right;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="category-list">
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px; color:#94a3b8;">Keine Kategorien vorhanden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                                    <td>-</td> <!-- Would require counting file occurrences -->
                                    <td style="text-align:right;">
                                        <button class="action-btn delete" onclick="deleteCategory('<?php echo $cat['slug']; ?>')">🗑️</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Category Modal -->
            <div class="modal" id="add-category-modal">
                <div class="modal-content">
                    <h3>Neue Kategorie</h3>
                    <div class="form-group mb-3">
                        <label>Name</label>
                        <input type="text" id="cat-name" class="form-control" onkeydown="if(event.key === 'Enter') addCategory()">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="closeModal('add-category-modal')">Abbrechen</button>
                        <button class="btn btn-primary" onclick="addCategory()">Erstellen</button>
                    </div>
                </div>
            </div>

            <script>
                function openCategoryModal() {
                    document.getElementById('cat-name').value = '';
                    document.getElementById('add-category-modal').classList.add('active');
                    setTimeout(() => document.getElementById('cat-name').focus(), 50);
                }

                async function addCategory() {
                    const name = document.getElementById('cat-name').value.trim();
                    if (!name) return;

                    const formData = new FormData();
                    formData.append('action', 'add_category');
                    formData.append('name', name);

                    const response = await fetch('', { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success) {
                        location.reload(); 
                    } else {
                        alert('Fehler: ' + result.error);
                    }
                }

                async function deleteCategory(slug) {
                    if (!confirm('Kategorie wirklich löschen? Zugeordnete Dateien werden nicht gelöscht.')) return;

                    const formData = new FormData();
                    formData.append('action', 'delete_category');
                    formData.append('slug', slug);

                    const response = await fetch('', { method: 'POST', body: formData });
                    if ((await response.json()).success) {
                        location.reload();
                    }
                }
                
                function closeModal(id) {
                    document.getElementById(id).classList.remove('active');
                }
            </script>

        <?php else: ?>
            <!-- LIBRARY VIEW -->
            <div class="admin-page-header">
                <div>
                    <h2>🖼️ Medienverwaltung</h2>
                    <p class="text-muted">Verwalten Sie alle Dateien, Bilder und Dokumente an einem Ort.</p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-outline" onclick="openCreateFolderModal()">
                        📂 Neuer Ordner
                    </button>
                    <button class="btn btn-primary" onclick="document.getElementById('file-upload').click()">
                        ☁️ Upload
                    </button>
                    <input type="file" id="file-upload" multiple style="display: none;" onchange="handleFileUpload(this)">
                </div>
            </div>

            <!-- Toolbar -->
            <div class="media-toolbar">
                <div class="media-filters">
                    <div class="breadcrumb" id="breadcrumb" style="margin-right: 20px;">
                        <span onclick="loadPath('')">Home</span>
                    </div>
                    
                    <select id="media-filter" class="form-control" style="width: 200px;" onchange="applyFilter()">
                        <option value="all">Alle Dateitypen</option>
                        <option value="folder">Nur Ordner</option>
                        <option value="image">Bilder</option>
                        <option value="video">Videos</option>
                        <option value="audio">Audio</option>
                        <option value="document">Dokumente</option>
                        <option value="archive">Archive</option>
                        <option value="plugin">Plugins</option>
                        <option value="theme">Themes</option>
                        <option value="font">Fonts</option>
                    </select>
                    
                    <select id="category-filter" class="form-control" style="width: 200px;" onchange="applyFilter()">
                        <option value="">Alle Kategorien</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="uploader-filter" class="form-control" style="width: 200px;" onchange="applyFilter()">
                        <option value="">Alle Uploader</option>
                    </select>
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Suchen..." class="form-control" id="search-input" onkeyup="applyFilterS(this.value)">
                </div>
            </div>

            <!-- Table View -->
            <div id="media-view">
                <table class="media-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Vorschau</th>
                            <th>Name</th>
                            <th>Typ</th>
                            <th>Größe</th>
                            <th>Kategorie</th>
                            <th>Hochgeladen von</th>
                            <th style="min-width: 150px; text-align: right;">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="file-list">
                        <!-- Loaded via JS -->
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px;">Lade Dateien...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Create Folder Modal -->
            <div class="modal" id="create-folder-modal">
                <div class="modal-content">
                    <h3>Neuen Ordner erstellen</h3>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Ordnername</label>
                        <input type="text" id="new-folder-name" class="form-control" placeholder="z.B. Projekte_2024" onkeydown="if(event.key === 'Enter') createFolder()">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="closeModal('create-folder-modal')">Abbrechen</button>
                        <button class="btn btn-primary" onclick="createFolder()">Erstellen</button>
                    </div>
                </div>
            </div>
            
            <!-- Rename Modal -->
            <div class="modal" id="rename-modal">
                <div class="modal-content">
                    <h3>Umbenennen</h3>
                    <input type="hidden" id="rename-old-path">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Neuer Name</label>
                        <input type="text" id="rename-new-name" class="form-control" onkeydown="if(event.key === 'Enter') renameItem()">
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="closeModal('rename-modal')">Abbrechen</button>
                        <button class="btn btn-primary" onclick="renameItem()">Speichern</button>
                    </div>
                </div>
            </div>

            <!-- Assign Category Modal -->
            <div class="modal" id="assign-modal">
                <div class="modal-content">
                    <h3>Kategorie zuweisen</h3>
                    <input type="hidden" id="assign-file-path">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Kategorie wählen</label>
                        <select id="assign-category-slug" class="form-control">
                            <option value="">-- Keine --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline" onclick="closeModal('assign-modal')">Abbrechen</button>
                        <button class="btn btn-primary" onclick="submitAssignment()">Speichern</button>
                    </div>
                </div>
            </div>

            <!-- Image Preview Modal -->
            <div class="modal" id="image-preview-modal" onclick="if(event.target === this) closeModal('image-preview-modal')">
                <div class="modal-content" style="width: auto; max-width: 90%; background: transparent; box-shadow: none; padding: 0;">
                    <img id="preview-image-full" src="" style="max-width: 100%; max-height: 85vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                    <div style="text-align: center; margin-top: 10px;">
                        <button class="btn btn-light btn-sm" onclick="closeModal('image-preview-modal')">Schließen</button>
                    </div>
                </div>
            </div>

            <script>
                // ... (Previous JS logic for Library) ...
                let currentPath = '';
                let allItems = { folders: [], files: [] };
                const allCategories = <?php echo json_encode($categories); ?>;
                // Initial filter from PHP
                const initialFilter = '<?php echo htmlspecialchars($initialFilter); ?>';

                document.addEventListener('DOMContentLoaded', () => {
                    const filterSelect = document.getElementById('media-filter');
                    if (filterSelect) {
                        filterSelect.value = initialFilter;
                    }
                    loadPath('');
                });

                async function loadPath(path) {
                    currentPath = path;
                    updateBreadcrumb(path);
                    
                    const tbody = document.getElementById('file-list');
                    if (!tbody) return;
                    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Laden...</td></tr>';

                    const formData = new FormData();
                    formData.append('action', 'list_files');
                    formData.append('path', path);

                    try {
                        const response = await fetch('', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            allItems = result.data;
                            updateUploaderFilter();
                            applyFilter();
                        } else {
                            alert('Fehler: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        tbody.innerHTML = '<tr><td colspan="6" style="color: red; text-align: center;">Ein Fehler ist aufgetreten beim Laden der Daten.</td></tr>';
                    }
                }

                function applyFilterS(val) {
                    applyFilter();
                }


                function updateUploaderFilter() {
                    const uploaderSelect = document.getElementById('uploader-filter');
                    if (!uploaderSelect) return;
                    
                    const currentVal = uploaderSelect.value;
                    const uploaders = new Map();

                    // Collect from folders
                    allItems.folders.forEach(item => {
                        if (item.uploader_id && item.uploaded_by) {
                            uploaders.set(item.uploader_id, item.uploaded_by);
                        }
                    });
                    // Collect from files
                    allItems.files.forEach(item => {
                        if (item.uploader_id && item.uploaded_by) {
                            uploaders.set(item.uploader_id, item.uploaded_by);
                        }
                    });

                    let options = '<option value="">Alle Uploader</option>';
                    uploaders.forEach((name, id) => {
                        options += `<option value="${id}">${name}</option>`;
                    });
                    
                    uploaderSelect.innerHTML = options;
                    if (currentVal && uploaders.has(parseInt(currentVal))) {
                        uploaderSelect.value = currentVal;
                    }
                }

                function applyFilter() {
                    const filterType = document.getElementById('media-filter').value;
                    const catFilter = document.getElementById('category-filter') ? document.getElementById('category-filter').value : '';
                    const uploaderFilter = document.getElementById('uploader-filter') ? document.getElementById('uploader-filter').value : '';
                    const searchTerm = document.getElementById('search-input').value.toLowerCase();
                    const tbody = document.getElementById('file-list');
                    if (!tbody) return;
                    
                    tbody.innerHTML = '';

                    let foldersToShow = allItems.folders;
                    let filesToShow = allItems.files;

                    // Filter Logic
                    if (filterType === 'folder') {
                        filesToShow = [];
                    } else if (filterType !== 'all') {
                        filesToShow = filesToShow.filter(file => getFileTypeCategory(file.type) === filterType);
                    }

                    if (catFilter) {
                         filesToShow = filesToShow.filter(file => file.category === catFilter);
                         foldersToShow = foldersToShow.filter(folder => folder.category === catFilter); 
                         // Note: Folders can now have categories too!
                    }

                    if (uploaderFilter) {
                        const uid = parseInt(uploaderFilter);
                        foldersToShow = foldersToShow.filter(f => f.uploader_id === uid);
                        filesToShow = filesToShow.filter(f => f.uploader_id === uid);
                    }

                    // Search Logic
                    if (searchTerm) {
                        foldersToShow = foldersToShow.filter(f => f.name.toLowerCase().includes(searchTerm));
                        filesToShow = filesToShow.filter(f => f.name.toLowerCase().includes(searchTerm));
                    }

                    // Render Folders
                    // Always show folders unless searching/filtering hides them
                    foldersToShow.forEach(folder => {
                        // Check if system folder
                        const isSystem = folder.is_system;
                        const deleteBtn = isSystem 
                            ? `<button class="action-btn" disabled style="opacity:0.3; cursor:not-allowed;" title="Systemordner"><span style="filter:grayscale(1)">🔒</span></button>`
                            : `<button class="action-btn delete" title="Löschen" onclick="deleteItem('${folder.path}', 'folder')">🗑️</button>`;

                        const tr = document.createElement('tr');
                        // Category Badge
                        let catBadge = '';
                        if (folder.category) {
                           const cat = allCategories.find(c => c.slug === folder.category);
                           if (cat) catBadge = `<span class="badge" style="background:#f1f5f9; color:#475569; padding:2px 6px; border-radius:4px; font-size:0.75em; border:1px solid #cbd5e1;">${cat.name}</span>`;
                        } else {
                            catBadge = `<span style="color:#cbd5e1; font-size:0.8em;">-</span>`;
                        }

                        tr.innerHTML = `
                            <td><div class="folder-icon">📂</div></td>
                            <td><a href="#" onclick="event.preventDefault(); loadPath('${folder.path}')" style="font-weight: 600; color: #1e293b; text-decoration: none;">${folder.name}</a></td>
                            <td><span class="badge" style="background:#e2e8f0; color:#475569; padding:2px 6px; border-radius:4px; font-size:0.8em;">Ordner</span></td>
                            <td>${folder.items_count !== undefined ? folder.items_count + ' Elemente' : '-'}</td>
                            <td>${catBadge}</td>
                            <td>${folder.uploaded_by || '<span style="color:#cbd5e1">-</span>'}</td>
                            <td style="text-align: right;">
                                <button class="action-btn" title="Umbenennen" onclick="openRenameModal('${folder.path}', '${folder.name}')" ${isSystem ? 'disabled style="opacity:0.5"' : ''}>✎</button>
                                ${deleteBtn}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Render Files
                    filesToShow.forEach(file => {
                        const category = getFileTypeCategory(file.type);
                        let icon = '';
                        
                        if (category === 'image') {
                            icon = `<img src="${file.url}" class="media-preview" style="cursor: zoom-in;" onclick="openImagePreview('${file.url}')" title="Vorschau">`; 
                        } else {
                            icon = `<div class="media-preview">${getFileIcon(file.type)}</div>`;
                        }

                        let catBadge = '';
                        if (file.category) {
                           const cat = allCategories.find(c => c.slug === file.category);
                           if (cat) catBadge = `<span class="badge" style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-size:0.75em; margin-right:5px; border:1px solid #bae6fd;">${cat.name}</span>`;
                        } else {
                            catBadge = `<span style="color:#cbd5e1; font-size:0.8em; margin-right:5px;">-</span>`;
                        }

                        // Check system file
                        const isSystem = file.is_system;
                        const deleteBtn = isSystem 
                            ? `<button class="action-btn" disabled style="opacity:0.3; cursor:not-allowed;" title="Systemdatei"><span style="filter:grayscale(1)">🔒</span></button>`
                            : `<button class="action-btn delete" title="Löschen" onclick="deleteItem('${file.path}', 'file')">🗑️</button>`;


                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${icon}</td>
                            <td>
                                <a href="${file.url}" target="_blank" style="color: #3b82f6; text-decoration: none;">${file.name}</a>
                            </td>
                            <td><span style="text-transform:uppercase; font-size:0.85em; font-weight:600; color:#64748b;">${file.type}</span></td>
                            <td>${formatSize(file.size)}</td>
                            <td>${catBadge}</td>
                            <td>${file.uploaded_by || '<span style="color:#cbd5e1">-</span>'}</td>
                            <td style="text-align: right;">
                                <button class="action-btn" title="Kategorie" onclick="openAssignModal('${file.path}', '${file.category || ''}')">🏷️</button>
                                <button class="action-btn" title="URL kopieren" onclick="copyToClipboard('${file.url}')">🔗</button>
                                <button class="action-btn" title="Umbenennen" onclick="openRenameModal('${file.path}', '${file.name}')">✎</button>
                                ${deleteBtn}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    if (foldersToShow.length === 0 && filesToShow.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px; font-style: italic;">Keine Einträge gefunden.</td></tr>';
                    }
                }
                
                function openAssignModal(path, currentSlug) {
                    document.getElementById('assign-file-path').value = path;
                    document.getElementById('assign-category-slug').value = currentSlug;
                    document.getElementById('assign-modal').classList.add('active');
                }

                async function submitAssignment() {
                    const filePath = document.getElementById('assign-file-path').value;
                    const slug = document.getElementById('assign-category-slug').value;
                    
                    const formData = new FormData();
                    formData.append('action', 'assign_category');
                    formData.append('file_path', filePath);
                    formData.append('slug', slug);
                    
                    const response = await fetch('', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    if (result.success) {
                        closeModal('assign-modal');
                        loadPath(currentPath); // Reload to show new assignment
                    } else {
                        alert('Fehler: ' + result.error);
                    }
                }

                function getFileTypeCategory(extension) {
                    const ext = extension.toLowerCase();
                    const categories = {
                        'image': ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
                        'video': ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'],
                        'audio': ['mp3', 'wav', 'aac', 'flac', 'm4a'],
                        'document': ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'csv'],
                        'archive': ['zip', 'rar', '7z', 'tar', 'gz'],
                        'font': ['ttf', 'otf', 'woff', 'woff2', 'eot'],
                        'plugin': ['php', 'zip'],
                        'theme': ['css', 'php']
                    };

                    for (const [cat, exts] of Object.entries(categories)) {
                        if (exts.includes(ext)) return cat;
                    }
                    return 'other';
                }

                function getFileIcon(extension) {
                    const icons = {
                        'pdf': '📄', 'doc': '📝', 'docx': '📝', 
                        'xls': '📊', 'xlsx': '📊', 
                        'ppt': '📽️', 'pptx': '📽️',
                        'zip': '📦', 'rar': '📦', '7z': '📦',
                        'mp3': '🎵', 'wav': '🎵',
                        'mp4': '🎬', 'mov': '🎬', 'avi': '🎬',
                        'txt': '📄', 'php': '🐘', 'js': '📜', 'css': '🎨', 'html': '🌐',
                        'ttf': '🔤', 'otf': '🔤', 'woff': '🔤', 'woff2': '🔤'
                    };
                    return icons[extension.toLowerCase()] || '📄';
                }

                function updateBreadcrumb(path) {
                    const container = document.getElementById('breadcrumb');
                    if (!container) return;
                    
                    if (!path) {
                        container.innerHTML = '<span onclick="loadPath(\'\')">Home</span>';
                        return;
                    }
                    
                    const parts = path.split('/').filter(p => p);
                    let html = '<span onclick="loadPath(\'\')">Home</span>';
                    
                    parts.forEach((part, index) => {
                        let clickPath = parts.slice(0, index + 1).join('/');
                        html += ' / ';
                        if (index === parts.length - 1) {
                            html += `<span>${part}</span>`;
                        } else {
                            html += `<span onclick="loadPath('${clickPath}')">${part}</span>`;
                        }
                    });
                    container.innerHTML = html;
                }

                async function createFolder() {
                    const nameInput = document.getElementById('new-folder-name');
                    const name = nameInput.value.trim();
                    if (!name) return;

                    const formData = new FormData();
                    formData.append('action', 'create_folder');
                    formData.append('name', name);
                    formData.append('path', currentPath);

                    const response = await fetch('', { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success) {
                        closeModal('create-folder-modal');
                        nameInput.value = '';
                        loadPath(currentPath);
                    } else {
                        alert('Fehler: ' + result.error);
                    }
                }

                async function handleFileUpload(input) {
                    if (input.files.length === 0) return;

                    const btn = document.querySelector('.header-actions .btn-primary');
                    const oldText = btn.innerText;
                    btn.innerText = 'Upload läuft...';
                    btn.disabled = true;

                    let errors = [];
                    for (let i = 0; i < input.files.length; i++) {
                        const file = input.files[i];
                        const formData = new FormData();
                        formData.append('action', 'upload_file');
                        formData.append('file', file);
                        formData.append('path', currentPath);

                        try {
                            const response = await fetch('', { method: 'POST', body: formData });
                            const result = await response.json();
                            if (!result.success) {
                                errors.push(file.name + ': ' + result.error);
                            }
                        } catch (e) {
                            errors.push(file.name + ': Netzwerkfehler');
                        }
                    }

                    input.value = '';
                    btn.innerText = oldText;
                    btn.disabled = false;

                    if (errors.length > 0) {
                        alert('Einige Dateien konnten nicht hochgeladen werden:\n' + errors.join('\n'));
                    }
                    loadPath(currentPath);
                }

                async function deleteItem(path, type) {
                    const conf = confirm(`Möchten Sie dieses ${type === 'folder' ? 'Ordner und dessen Inhalt' : 'Datei'} wirklich löschen?`);
                    if (!conf) return;

                    const formData = new FormData();
                    formData.append('action', 'delete_item');
                    formData.append('item_path', path);

                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const result = await response.json();
                        if (result.success) loadPath(currentPath);
                        else alert('Fehler: ' + result.error);
                    } catch (e) {
                        alert('Netzwerkfehler beim Löschen');
                    }
                }
                
                function openRenameModal(path, name) {
                    document.getElementById('rename-old-path').value = path;
                    const input = document.getElementById('rename-new-name');
                    input.value = name;
                    document.getElementById('rename-modal').classList.add('active');
                    setTimeout(() => input.focus(), 50);
                }

                async function renameItem() {
                    const oldPath = document.getElementById('rename-old-path').value;
                    const newName = document.getElementById('rename-new-name').value.trim();
                    if (!newName) return;

                    const formData = new FormData();
                    formData.append('action', 'rename_item');
                    formData.append('old_path', oldPath);
                    formData.append('new_name', newName);

                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const result = await response.json();
                        if (result.success) {
                            closeModal('rename-modal');
                            loadPath(currentPath);
                        } else {
                            alert('Fehler: ' + result.error);
                        }
                    } catch (e) {
                        alert('Netzwerkfehler');
                    }
                }

                function handleDrop(e) {
                    e.preventDefault();
                    document.querySelector('.admin-content').classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        const input = document.getElementById('file-upload');
                        input.files = e.dataTransfer.files;
                        handleFileUpload(input);
                    }
                }

                function openCreateFolderModal() {
                    document.getElementById('create-folder-modal').classList.add('active');
                    setTimeout(() => document.getElementById('new-folder-name').focus(), 50);
                }

                function closeModal(id) {
                    document.getElementById(id).classList.remove('active');
                }
                
                function openImagePreview(url) {
                    document.getElementById('preview-image-full').src = url;
                    document.getElementById('image-preview-modal').classList.add('active');
                }

                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        const btn = document.activeElement;
                        const originalText = btn.innerText;
                        btn.innerText = '✓';
                        setTimeout(() => btn.innerText = originalText, 1000);
                    });
                }

                function formatDate(timestamp) {
                    return new Date(timestamp * 1000).toLocaleString('de-DE');
                }

                function formatSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + units[i];
                }
            </script>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
