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
$security = Security::instance();
$mediaService = new MediaService();

// Handle AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    // CSRF-Schutz reaktiviert (Fix C-13) – Token-Verifikation VOR generateToken()
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'media_action')) {
        // Token ungültig – neuen Token generieren, damit Client es erneut versuchen kann
        $newToken = $security->generateToken('media_action');
        echo json_encode(['success' => false, 'error' => 'Sicherheitsüberprüfung fehlgeschlagen', 'new_token' => $newToken]);
        exit;
    }
    // Nach erfolgreicher Verifikation: neuen Token generieren (One-Time-Use)
    $newCsrfToken = $security->generateToken('media_action');
    $action = $_POST['action'];
    $currentPath = $_POST['path'] ?? '';

    try {
        switch ($action) {
            case 'list_files':
                $result = $mediaService->getItems($currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'data' => $result, 'new_token' => $newCsrfToken]);
                }
                break;

            case 'create_folder':
                $folderName = $_POST['name'] ?? '';
                if (empty($folderName)) {
                    echo json_encode(['success' => false, 'error' => 'Ordnername ist erforderlich', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->createFolder($folderName, $currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
                }
                break;

            case 'upload_file':
                if (!isset($_FILES['file'])) {
                    echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->uploadFile($_FILES['file'], $currentPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'filename' => $result, 'new_token' => $newCsrfToken]);
                }
                break;

            case 'delete_item':
                $itemPath = $_POST['item_path'] ?? '';
                if (empty($itemPath)) {
                    echo json_encode(['success' => false, 'error' => 'Item path is required', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->deleteItem($itemPath);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
                }
                break;
            
            case 'rename_item':
                $oldPath = $_POST['old_path'] ?? '';
                $newName = $_POST['new_name'] ?? '';
                if (empty($oldPath) || empty($newName)) {
                    echo json_encode(['success' => false, 'error' => 'Fehlende Parameter', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->renameItem($oldPath, $newName);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
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
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Einstellungen erfolgreich gespeichert.', 'new_token' => $newCsrfToken]);
                }
                break;

            case 'get_categories':
                echo json_encode(['success' => true, 'data' => $mediaService->getCategories(), 'new_token' => $newCsrfToken]);
                break;

            case 'add_category':
                $name = $_POST['name'] ?? '';
                if (empty($name)) {
                    echo json_encode(['success' => false, 'error' => 'Name ist erforderlich', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->addCategory($name);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
                }
                break;

            case 'delete_category':
                $slug = $_POST['slug'] ?? '';
                if (empty($slug)) {
                    echo json_encode(['success' => false, 'error' => 'Slug ist erforderlich', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->deleteCategory($slug);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
                }
                break;

            case 'assign_category':
                $filePath = $_POST['file_path'] ?? '';
                $slug = $_POST['slug'] ?? '';
                if (empty($filePath)) {
                    echo json_encode(['success' => false, 'error' => 'Datei ist erforderlich', 'new_token' => $newCsrfToken]);
                    break;
                }
                $result = $mediaService->assignCategory($filePath, $slug);
                if (is_wp_error($result)) {
                    echo json_encode(['success' => false, 'error' => $result->get_error_message(), 'new_token' => $newCsrfToken]);
                } else {
                    echo json_encode(['success' => true, 'new_token' => $newCsrfToken]);
                }
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Ungültige Aktion', 'new_token' => $newCsrfToken]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'new_token' => $newCsrfToken]);
    }
    exit;
}

// CSRF-Token für die Seite generieren (GET-Anfragen / Seitenaufruf)
$mediaCsrfToken = $security->generateToken('media_action');

// Extract active tab to determine initial filter
$activeTab = $_GET['tab'] ?? 'all';
$initialFilter = 'all';

switch ($activeTab) {
    case 'settings':
    case 'categories':
    case 'elfinder':
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
$diskUsage = ($viewMode === 'files') ? $mediaService->getDiskUsage() : null;

$mediaActiveSlug = 'media-' . ($activeTab === 'all' ? 'library' : $activeTab);
renderAdminLayoutStart('Medienverwaltung', $mediaActiveSlug);
?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/filepond/filepond.min.css?v=20260305">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/elfinder/css/elfinder.min.css?v=20260305">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/elfinder/css/theme.css?v=20260305">
    <script src="<?php echo SITE_URL; ?>/assets/filepond/filepond.min.js" defer></script>
    <script src="<?php echo SITE_URL; ?>/assets/elfinder/js/elfinder.min.js" defer></script>
    <script>
        let CMS_MEDIA_NONCE = '<?php echo htmlspecialchars($mediaCsrfToken, ENT_QUOTES, 'UTF-8'); ?>';
        async function cmsPost(formData) {
            formData.append('csrf_token', CMS_MEDIA_NONCE);
            const response = await fetch('', { method: 'POST', body: formData });
            const cloned = response.clone();
            try {
                const json = await cloned.json();
                if (json.new_token) {
                    CMS_MEDIA_NONCE = json.new_token;
                }
                if (!json.success && json.new_token && json.error && json.error.indexOf('Sicherheits') !== -1) {
                    const retryData = new FormData();
                    for (const [key, value] of formData.entries()) {
                        if (key !== 'csrf_token') retryData.append(key, value);
                    }
                    retryData.append('csrf_token', CMS_MEDIA_NONCE);
                    const retryResp = await fetch('', { method: 'POST', body: retryData });
                    const retryClone = retryResp.clone();
                    try {
                        const retryJson = await retryClone.json();
                        if (retryJson.new_token) CMS_MEDIA_NONCE = retryJson.new_token;
                    } catch (e2) { /* ignore */ }
                    return retryResp;
                }
            } catch (e) { /* ignore */ }
            return response;
        }
    </script>

    <!-- Main Content -->
    <div class="media-content-area" <?php if($viewMode === 'files') echo 'ondragover="event.preventDefault(); this.classList.add(\'drag-over\');" ondragleave="this.classList.remove(\'drag-over\');" ondrop="handleDrop(event)"'; ?>>
        
        <?php if ($viewMode === 'settings'): ?>
            <!-- SETTINGS VIEW -->
            <div class="page-header d-print-none mb-3">
                <div class="container-xl">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Administration</div>
                            <h2 class="page-title">⚙️ Medieneinstellungen</h2>
                            <p class="text-secondary mt-1">Uploads, Bildverarbeitung, Organisation, Berechtigungen &amp; Sicherheit konfigurieren.</p>
                        </div>
                    </div>
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
                                <select name="max_upload_size" class="form-select form-select-sm">
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
                                    <select name="member_max_upload_size" class="form-select form-select-sm">
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
                        const response = await cmsPost(formData);
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
            <div class="page-header d-print-none mb-3">
                <div class="container-xl">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Administration</div>
                            <h2 class="page-title">🏷️ Dokumenten-Kategorien</h2>
                            <p class="text-secondary mt-1">Organisieren Sie Dokumente und Dateien.</p>
                        </div>
                        <div class="col-auto ms-auto">
                            <button class="btn btn-primary" onclick="openCategoryModal()">Neue Kategorie</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="category-status-notice" aria-live="polite"></div>
            
            <div class="card">
                <div class="table-responsive">
                <table class="table table-vcenter card-table">
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
                                    <td><?php echo (int)($cat['count'] ?? 0); ?></td>
                                    <td style="text-align:right;">
                                        <?php if (!empty($cat['is_system'])): ?>
                                            <span class="badge bg-secondary-lt">System</span>
                                            <button class="action-btn" disabled style="opacity:.35;cursor:not-allowed;" title="System-Kategorie">🔒</button>
                                        <?php else: ?>
                                            <button class="action-btn delete" onclick="deleteCategory('<?php echo htmlspecialchars($cat['slug'], ENT_QUOTES); ?>')">🗑️</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Add Category Modal -->
            <div class="modal modal-blur fade" id="add-category-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Neue Kategorie</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" id="cat-name" class="form-control" onkeydown="if(event.key === 'Enter') addCategory()">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button class="btn btn-primary" onclick="addCategory()">Erstellen</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function showCategoryNotice(message, type = 'success') {
                    const container = document.getElementById('category-status-notice');
                    if (!container) return;
                    container.innerHTML = '<div class="notice notice-' + (type === 'error' ? 'error' : type === 'info' ? 'info' : 'success') + ' mb-3">' + String(message).replace(/[&<>"']/g, function(char) {
                        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[char];
                    }) + '</div>';
                }

                function openCategoryModal() {
                    document.getElementById('cat-name').value = '';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('add-category-modal')).show();
                    setTimeout(() => document.getElementById('cat-name').focus(), 150);
                }

                async function addCategory() {
                    const name = document.getElementById('cat-name').value.trim();
                    if (!name) return;

                    const formData = new FormData();
                    formData.append('action', 'add_category');
                    formData.append('name', name);

                    const response = await cmsPost(formData);
                    const result = await response.json();

                    if (result.success) {
                        showCategoryNotice('Kategorie erfolgreich erstellt.', 'success');
                        window.setTimeout(() => location.reload(), 350);
                    } else {
                        showCategoryNotice('Fehler: ' + result.error, 'error');
                    }
                }

                async function deleteCategory(slug) {
                    _pendingDeleteCategorySlug = slug;
                    const delModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaDeleteModal'));
                    delModal.show();
                    document.getElementById('mediaDeleteMsg').textContent = 'Kategorie wirklich löschen? Zugeordnete Dateien werden nicht gelöscht.';
                    document.getElementById('mediaDeleteConfirmBtn').onclick = async function() {
                        delModal.hide();
                        const formData = new FormData();
                        formData.append('action', 'delete_category');
                        formData.append('slug', _pendingDeleteCategorySlug);
                        const response = await cmsPost(formData);
                        const result = await response.json();
                        if (result.success) {
                            showCategoryNotice('Kategorie gelöscht.', 'success');
                            window.setTimeout(() => location.reload(), 350);
                        } else {
                            showCategoryNotice('Fehler: ' + (result.error ?? 'Kategorie konnte nicht gelöscht werden.'), 'error');
                        }
                    };
                }
                let _pendingDeleteCategorySlug = '';
                
                function closeModal(id) {
                    const el = document.getElementById(id);
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) inst.hide();
                }
            </script>

        <?php elseif ($viewMode === 'elfinder'): ?>
            <!-- ELFINDER VIEW -->
            <div class="page-header d-print-none mb-3">
                <div class="container-xl">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Administration</div>
                            <h2 class="page-title">🗂️ elFinder Dateimanager</h2>
                            <p class="text-secondary mt-1">Connector aktiv: <code>/admin/includes/elfinder-connector.php</code> (nur Admin, Upload-Verzeichnis auf <code>CMS/uploads/</code> beschränkt).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning">
                        ⚠️ Der Connector ist aktiv. Für die vollständige Browser-UI benötigt elFinder zusätzlich jQuery + jQuery UI als lokale Runtime-Abhängigkeit.
                    </div>
                    <p style="margin:0;color:#475569;">Endpoint-Test:</p>
                    <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;margin-top:.75rem;">GET <?php echo SITE_URL; ?>/admin/includes/elfinder-connector.php?cmd=open&target=l1_Lw</pre>
                </div>
            </div>

        <?php else: ?>
            <!-- LIBRARY VIEW -->
            <div class="page-header d-print-none mb-3">
                <div class="container-xl">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="page-pretitle">Administration</div>
                            <h2 class="page-title">🖼️ Medienverwaltung</h2>
                            <p class="text-secondary mt-1">Verwalten Sie alle Dateien, Bilder und Dokumente an einem Ort.</p>
                        </div>
                        <div class="col-auto ms-auto d-flex gap-2 align-items-center">
                            <?php if ($diskUsage): ?>
                            <span class="media-disk-usage" title="Speicherbelegung: <?php echo (int)$diskUsage['count']; ?> Dateien">
                                💾 <?php echo htmlspecialchars($diskUsage['formatted']); ?>
                                <small>(<?php echo (int)$diskUsage['count']; ?> Dateien)</small>
                            </span>
                            <?php endif; ?>
                            <button class="btn btn-outline" onclick="openCreateFolderModal()">
                                📂 Neuer Ordner
                            </button>
                            <button class="btn btn-primary" onclick="triggerUploadBrowse()">
                                ☁️ Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="media-toolbar">
                <div class="media-filters">
                    <div class="media-breadcrumb" id="breadcrumb" style="margin-right: 20px;">
                        <span onclick="loadPath('')">Home</span>
                    </div>
                    
                    <select id="media-filter" class="form-select form-select-sm" style="width: 170px;" onchange="applyFilter()">
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
                    
                    <select id="category-filter" class="form-select form-select-sm" style="width: 170px;" onchange="applyFilter()">
                        <option value="">Alle Kategorien</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="uploader-filter" class="form-select form-select-sm" style="width: 170px;" onchange="applyFilter()">
                        <option value="">Alle Uploader</option>
                    </select>
                </div>
                <div class="media-toolbar-right">
                    <input type="text" placeholder="Suchen..." class="form-control form-control-sm" id="search-input" style="width:200px;" onkeyup="applyFilterS(this.value)">
                    <div class="media-view-toggle btn-group">
                        <button class="btn btn-sm btn-icon" id="view-list-btn" onclick="setViewMode('list')" title="Listenansicht">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        </button>
                        <button class="btn btn-sm btn-icon" id="view-grid-btn" onclick="setViewMode('grid')" title="Kachelansicht">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="section-header">
                        <h3>☁️ Schnell-Upload</h3>
                        <p>Dateien per Drag & Drop oder über den Dateidialog direkt in den aktuell geöffneten Ordner hochladen.</p>
                    </div>
                    <input type="file" id="file-upload" class="js-filepond" multiple onchange="if (typeof window.FilePond === 'undefined') handleFileUpload(this)">
                    <div id="media-status-notice" aria-live="polite"></div>
                </div>
            </div>

            <!-- Media Content Layout (File List + Detail Panel) -->
            <div class="media-layout">
                <!-- File Area -->
                <div class="media-file-area">
                    <!-- Table View -->
                    <div id="media-list-view">
                        <div class="card">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table media-table">
                                    <thead>
                                        <tr>
                                            <th style="width:44px;">
                                                <input type="checkbox" class="form-check-input" id="select-all" onchange="toggleSelectAll(this.checked)">
                                            </th>
                                            <th style="width: 60px;">Vorschau</th>
                                            <th>Name</th>
                                            <th>Typ</th>
                                            <th>Größe</th>
                                            <th>Kategorie</th>
                                            <th style="min-width: 120px; text-align: right;">Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody id="file-list">
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 30px;">Lade Dateien...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Grid View -->
                    <div id="media-grid-view" style="display:none;">
                        <div class="media-grid" id="file-grid">
                            <!-- Loaded via JS -->
                        </div>
                    </div>

                    <!-- Bulk Actions Bar -->
                    <div class="media-bulk-bar" id="bulk-bar" style="display:none;">
                        <span id="bulk-count">0 ausgewählt</span>
                        <button class="btn btn-sm btn-danger" onclick="bulkDelete()">🗑️ Auswahl löschen</button>
                    </div>
                </div>

                <!-- Detail Panel -->
                <div class="media-detail-panel" id="detail-panel" style="display:none;">
                    <div class="media-detail-header">
                        <strong>Dateidetails</strong>
                        <button class="btn-close" onclick="closeDetailPanel()">&times;</button>
                    </div>
                    <div class="media-detail-body">
                        <div class="media-detail-preview" id="detail-preview"></div>
                        <div class="media-detail-info">
                            <table class="media-detail-table">
                                <tr><td>Dateiname</td><td id="detail-name">-</td></tr>
                                <tr><td>Typ</td><td id="detail-type">-</td></tr>
                                <tr><td>Größe</td><td id="detail-size">-</td></tr>
                                <tr><td>Abmessungen</td><td id="detail-dimensions">-</td></tr>
                                <tr><td>Geändert</td><td id="detail-modified">-</td></tr>
                                <tr><td>Hochgeladen von</td><td id="detail-uploader">-</td></tr>
                                <tr><td>Kategorie</td><td id="detail-category">-</td></tr>
                            </table>
                        </div>
                        <div class="media-detail-url">
                            <label class="form-label" style="font-size:.8rem;font-weight:600;">Datei-URL</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="detail-url" readonly>
                                <button class="btn btn-outline" onclick="copyToClipboard(document.getElementById('detail-url').value)">📋</button>
                            </div>
                        </div>
                        <div class="media-detail-actions">
                            <button class="btn btn-sm btn-outline w-100 mb-1" id="detail-rename-btn" onclick="openRenameFromDetail()">✎ Umbenennen</button>
                            <button class="btn btn-sm btn-outline w-100 mb-1" id="detail-category-btn" onclick="openCategoryFromDetail()">🏷️ Kategorie</button>
                            <a class="btn btn-sm btn-outline w-100 mb-1" id="detail-download-btn" href="#" download>⬇️ Herunterladen</a>
                            <button class="btn btn-sm btn-danger w-100" id="detail-delete-btn" onclick="deleteFromDetail()">🗑️ Löschen</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Create Folder Modal -->
            <div class="modal modal-blur fade" id="create-folder-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Neuen Ordner erstellen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Ordnername</label>
                                <input type="text" id="new-folder-name" class="form-control" placeholder="z.B. Projekte_2024" onkeydown="if(event.key === 'Enter') createFolder()">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button class="btn btn-primary" onclick="createFolder()">Erstellen</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rename Modal -->
            <div class="modal modal-blur fade" id="rename-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Umbenennen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="rename-old-path">
                            <div class="mb-3">
                                <label class="form-label">Neuer Name</label>
                                <input type="text" id="rename-new-name" class="form-control" onkeydown="if(event.key === 'Enter') renameItem()">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button class="btn btn-primary" onclick="renameItem()">Speichern</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assign Category Modal -->
            <div class="modal modal-blur fade" id="assign-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Kategorie zuweisen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="assign-file-path">
                            <div class="mb-3">
                                <label class="form-label">Kategorie wählen</label>
                                <select id="assign-category-slug" class="form-select">
                                    <option value="">-- Keine --</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button class="btn btn-primary" onclick="submitAssignment()">Speichern</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Preview Modal -->
            <div class="modal modal-blur fade" id="image-preview-modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                    <div class="modal-content" style="background: transparent; border: none; box-shadow: none;">
                        <div class="modal-body p-0 text-center">
                            <img id="preview-image-full" src="" style="max-width: 100%; max-height: 85vh; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                        </div>
                        <div class="modal-footer border-0 justify-content-center" style="background: transparent;">
                            <button class="btn btn-light btn-sm" data-bs-dismiss="modal">Schließen</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div class="modal modal-blur fade" id="mediaDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content">
                        <div class="modal-status bg-danger"></div>
                        <div class="modal-header">
                            <h5 class="modal-title">Löschen bestätigen</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                        </div>
                        <div class="modal-body">
                            <p id="mediaDeleteMsg">Element wirklich löschen?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="button" class="btn btn-danger" id="mediaDeleteConfirmBtn">🗑️ Löschen</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // State
                let currentPath = '';
                let allItems = { folders: [], files: [] };
                let selectedItems = new Set();
                let currentDetailFile = null;
                let currentViewMode = localStorage.getItem('media_view') || 'list';
                let mediaPond = null;
                const allCategories = <?php echo json_encode($categories); ?>;
                const initialFilter = '<?php echo htmlspecialchars($initialFilter); ?>';

                document.addEventListener('DOMContentLoaded', () => {
                    const filterSelect = document.getElementById('media-filter');
                    if (filterSelect) filterSelect.value = initialFilter;
                    setViewMode(currentViewMode);
                    initFilePond();
                    loadPath('');
                });

                /* ── View Mode Toggle ── */
                function setViewMode(mode) {
                    currentViewMode = mode;
                    localStorage.setItem('media_view', mode);
                    const listView = document.getElementById('media-list-view');
                    const gridView = document.getElementById('media-grid-view');
                    const listBtn = document.getElementById('view-list-btn');
                    const gridBtn = document.getElementById('view-grid-btn');
                    if (mode === 'grid') {
                        listView.style.display = 'none';
                        gridView.style.display = '';
                        listBtn.classList.remove('active');
                        gridBtn.classList.add('active');
                    } else {
                        listView.style.display = '';
                        gridView.style.display = 'none';
                        listBtn.classList.add('active');
                        gridBtn.classList.remove('active');
                    }
                    applyFilter();
                }

                /* ── Selection ── */
                function toggleSelectAll(checked) {
                    selectedItems.clear();
                    document.querySelectorAll('.media-row-check').forEach(cb => {
                        cb.checked = checked;
                        if (checked) selectedItems.add(cb.dataset.path);
                    });
                    updateBulkBar();
                }
                function toggleSelectItem(path, checked) {
                    if (checked) selectedItems.add(path);
                    else selectedItems.delete(path);
                    updateBulkBar();
                }
                function updateBulkBar() {
                    const bar = document.getElementById('bulk-bar');
                    if (selectedItems.size > 0) {
                        bar.style.display = 'flex';
                        document.getElementById('bulk-count').textContent = selectedItems.size + ' ausgewählt';
                    } else {
                        bar.style.display = 'none';
                    }
                }

                function showMediaNotice(message, type = 'success') {
                    const container = document.getElementById('media-status-notice');
                    if (!container) return;
                    container.innerHTML = '<div class="notice notice-' + escAttr(type) + ' mt-3">' + escHtml(message) + '</div>';
                    window.clearTimeout(showMediaNotice._timer);
                    showMediaNotice._timer = window.setTimeout(() => {
                        container.innerHTML = '';
                    }, 4500);
                }

                function triggerUploadBrowse() {
                    if (mediaPond && typeof mediaPond.browse === 'function') {
                        mediaPond.browse();
                        return;
                    }

                    const input = document.getElementById('file-upload');
                    if (input) {
                        input.click();
                    }
                }

                async function bulkDelete() {
                    if (selectedItems.size === 0) return;
                    const delModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaDeleteModal'));
                    delModal.show();
                    document.getElementById('mediaDeleteMsg').textContent = selectedItems.size + ' Elemente wirklich löschen?';
                    document.getElementById('mediaDeleteConfirmBtn').onclick = async function() {
                        delModal.hide();
                        for (const path of selectedItems) {
                            const fd = new FormData();
                            fd.append('action', 'delete_item');
                            fd.append('item_path', path);
                            await cmsPost(fd);
                        }
                        selectedItems.clear();
                        updateBulkBar();
                        loadPath(currentPath);
                    };
                }

                /* ── Detail Panel ── */
                function showDetailPanel(file) {
                    currentDetailFile = file;
                    const panel = document.getElementById('detail-panel');
                    panel.style.display = '';

                    const previewEl = document.getElementById('detail-preview');
                    const isImage = ['jpg','jpeg','png','gif','webp','svg','bmp','ico'].includes((file.type||'').toLowerCase());
                    if (isImage) {
                        previewEl.innerHTML = '<img src="' + escAttr(file.url) + '" alt="" style="max-width:100%;max-height:240px;border-radius:6px;cursor:pointer;" onclick="openImagePreview(\'' + escAttr(file.url) + '\')">';
                        // Try to get dimensions
                        const img = new Image();
                        img.onload = () => {
                            document.getElementById('detail-dimensions').textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
                        };
                        img.src = file.url;
                    } else {
                        previewEl.innerHTML = '<div style="font-size:3rem;text-align:center;padding:1.5rem;">' + getFileIcon(file.type) + '</div>';
                        document.getElementById('detail-dimensions').textContent = '-';
                    }

                    document.getElementById('detail-name').textContent = file.name;
                    document.getElementById('detail-type').textContent = (file.type || '').toUpperCase();
                    document.getElementById('detail-size').textContent = formatSize(file.size || 0);
                    document.getElementById('detail-modified').textContent = file.modified ? formatDate(file.modified) : '-';
                    document.getElementById('detail-uploader').textContent = file.uploaded_by || '-';
                    
                    const catEl = document.getElementById('detail-category');
                    if (file.category) {
                        const cat = allCategories.find(c => c.slug === file.category);
                        catEl.textContent = cat ? cat.name : file.category;
                    } else {
                        catEl.textContent = '-';
                    }

                    document.getElementById('detail-url').value = file.url || '';
                    document.getElementById('detail-download-btn').href = file.url || '#';
                    
                    const isSystem = file.is_system;
                    document.getElementById('detail-delete-btn').disabled = isSystem;
                    document.getElementById('detail-rename-btn').disabled = isSystem;
                }
                function closeDetailPanel() {
                    document.getElementById('detail-panel').style.display = 'none';
                    currentDetailFile = null;
                }
                function openRenameFromDetail() {
                    if (!currentDetailFile) return;
                    openRenameModal(currentDetailFile.path, currentDetailFile.name);
                }
                function openCategoryFromDetail() {
                    if (!currentDetailFile) return;
                    openAssignModal(currentDetailFile.path, currentDetailFile.category || '');
                }
                function deleteFromDetail() {
                    if (!currentDetailFile) return;
                    deleteItem(currentDetailFile.path, 'file');
                }

                /* ── FilePond ── */
                function initFilePond() {
                    const input = document.getElementById('file-upload');
                    if (!input || typeof window.FilePond === 'undefined') return;

                    window.FilePond.registerPlugin();
                    mediaPond = window.FilePond.create(input, {
                        allowMultiple: true,
                        instantUpload: true,
                        credits: false,
                        labelIdle: 'Dateien hier ablegen oder <span class="filepond--label-action">durchsuchen</span>',
                        server: {
                            process: {
                                url: '/api/upload',
                                method: 'POST',
                                ondata: (formData) => {
                                    formData.append('csrf_token', CMS_MEDIA_NONCE);
                                    formData.append('path', currentPath || '');
                                    return formData;
                                },
                                onload: (responseText) => {
                                    try {
                                        const payload = JSON.parse(responseText);
                                        if (payload.new_token) CMS_MEDIA_NONCE = payload.new_token;
                                        return payload.id || payload.path || '';
                                    } catch (e) { return ''; }
                                },
                                onerror: (responseText) => {
                                    try {
                                        const payload = JSON.parse(responseText);
                                        if (payload.new_token) CMS_MEDIA_NONCE = payload.new_token;
                                        return payload.error || 'Upload fehlgeschlagen';
                                    } catch (e) { return 'Upload fehlgeschlagen'; }
                                }
                            }
                        }
                    });
                    mediaPond.on('processfile', (error) => {
                        if (!error) {
                            showMediaNotice('Upload erfolgreich abgeschlossen.', 'success');
                            loadPath(currentPath);
                        }
                    });
                    mediaPond.on('processfileabort', () => showMediaNotice('Upload wurde abgebrochen.', 'info'));
                }

                /* ── Load Files ── */
                async function loadPath(path) {
                    currentPath = path;
                    selectedItems.clear();
                    updateBulkBar();
                    updateBreadcrumb(path);
                    
                    const tbody = document.getElementById('file-list');
                    if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:1.5rem;color:#94a3b8;">Laden...</td></tr>';

                    const formData = new FormData();
                    formData.append('action', 'list_files');
                    formData.append('path', path);

                    try {
                        const response = await cmsPost(formData);
                        const result = await response.json();
                        if (result.success) {
                            allItems = result.data;
                            updateUploaderFilter();
                            applyFilter();
                        } else {
                            showMediaNotice(result.error || 'Dateiliste konnte nicht geladen werden.', 'error');
                            if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="color:#ef4444;text-align:center;">Fehler: ' + escHtml(result.error) + '</td></tr>';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showMediaNotice('Netzwerkfehler beim Laden der Dateien.', 'error');
                        if (tbody) tbody.innerHTML = '<tr><td colspan="7" style="color:#ef4444;text-align:center;">Netzwerkfehler</td></tr>';
                    }
                }

                function applyFilterS(val) { applyFilter(); }

                function updateUploaderFilter() {
                    const uploaderSelect = document.getElementById('uploader-filter');
                    if (!uploaderSelect) return;
                    const currentVal = uploaderSelect.value;
                    const uploaders = new Map();
                    allItems.folders.forEach(i => { if (i.uploader_id && i.uploaded_by) uploaders.set(i.uploader_id, i.uploaded_by); });
                    allItems.files.forEach(i => { if (i.uploader_id && i.uploaded_by) uploaders.set(i.uploader_id, i.uploaded_by); });
                    let options = '<option value="">Alle Uploader</option>';
                    uploaders.forEach((name, id) => { options += '<option value="' + id + '">' + escHtml(name) + '</option>'; });
                    uploaderSelect.innerHTML = options;
                    if (currentVal && uploaders.has(parseInt(currentVal))) uploaderSelect.value = currentVal;
                }

                /* ── Filter & Render ── */
                function applyFilter() {
                    const filterType = document.getElementById('media-filter').value;
                    const catFilter = document.getElementById('category-filter') ? document.getElementById('category-filter').value : '';
                    const uploaderFilter = document.getElementById('uploader-filter') ? document.getElementById('uploader-filter').value : '';
                    const searchTerm = document.getElementById('search-input').value.toLowerCase();

                    let foldersToShow = [...allItems.folders];
                    let filesToShow = [...allItems.files];

                    if (filterType === 'folder') { filesToShow = []; }
                    else if (filterType !== 'all') { filesToShow = filesToShow.filter(f => getFileTypeCategory(f.type) === filterType); }
                    if (catFilter) {
                        filesToShow = filesToShow.filter(f => f.category === catFilter);
                        foldersToShow = foldersToShow.filter(f => f.category === catFilter);
                    }
                    if (uploaderFilter) {
                        const uid = parseInt(uploaderFilter);
                        foldersToShow = foldersToShow.filter(f => f.uploader_id === uid);
                        filesToShow = filesToShow.filter(f => f.uploader_id === uid);
                    }
                    if (searchTerm) {
                        foldersToShow = foldersToShow.filter(f => f.name.toLowerCase().includes(searchTerm));
                        filesToShow = filesToShow.filter(f => f.name.toLowerCase().includes(searchTerm));
                    }

                    if (currentViewMode === 'grid') {
                        renderGrid(foldersToShow, filesToShow);
                    } else {
                        renderTable(foldersToShow, filesToShow);
                    }
                }

                /* ── Table Rendering ── */
                function renderTable(folders, files) {
                    const tbody = document.getElementById('file-list');
                    if (!tbody) return;
                    tbody.innerHTML = '';

                    folders.forEach(folder => {
                        const isSystem = folder.is_system;
                        const deleteBtn = isSystem
                            ? '<button class="action-btn" disabled style="opacity:0.3;cursor:not-allowed;" title="Systemordner">🔒</button>'
                            : '<button class="action-btn delete" title="Löschen" onclick="deleteItem(\'' + escAttr(folder.path) + '\', \'folder\')">🗑️</button>';
                        const catBadge = getCatBadge(folder.category);

                        const tr = document.createElement('tr');
                        tr.innerHTML = '<td><input type="checkbox" class="form-check-input media-row-check" data-path="' + escAttr(folder.path) + '" onchange="toggleSelectItem(\'' + escAttr(folder.path) + '\', this.checked)"></td>' +
                            '<td><div class="folder-icon">📂</div></td>' +
                            '<td><a href="#" onclick="event.preventDefault(); loadPath(\'' + escAttr(folder.path) + '\')" style="font-weight:600;color:#1e293b;text-decoration:none;">' + escHtml(folder.name) + '</a></td>' +
                            '<td><span class="badge bg-secondary-lt">Ordner</span></td>' +
                            '<td>' + (folder.items_count !== undefined ? parseInt(folder.items_count) + ' Elemente' : '-') + '</td>' +
                            '<td>' + catBadge + '</td>' +
                            '<td style="text-align:right;">' +
                                '<button class="action-btn" title="Umbenennen" onclick="openRenameModal(\'' + escAttr(folder.path) + '\', \'' + escAttr(folder.name) + '\')" ' + (isSystem ? 'disabled style="opacity:0.5"' : '') + '>✎</button> ' +
                                deleteBtn +
                            '</td>';
                        tbody.appendChild(tr);
                    });

                    files.forEach(file => {
                        const category = getFileTypeCategory(file.type);
                        const isImage = category === 'image';
                        const icon = isImage
                            ? '<img src="' + escAttr(file.url) + '" class="media-thumb" style="cursor:pointer;" onclick="showDetailPanel(window._mf_' + file._idx + ')" title="Details">'
                            : '<div class="media-thumb-icon">' + getFileIcon(file.type) + '</div>';
                        const isSystem = file.is_system;
                        const deleteBtn = isSystem
                            ? '<button class="action-btn" disabled style="opacity:0.3;cursor:not-allowed;">🔒</button>'
                            : '<button class="action-btn delete" title="Löschen" onclick="deleteItem(\'' + escAttr(file.path) + '\', \'file\')">🗑️</button>';
                        const catBadge = getCatBadge(file.category);

                        const tr = document.createElement('tr');
                        tr.className = currentDetailFile && currentDetailFile.path === file.path ? 'table-active' : '';
                        tr.innerHTML = '<td><input type="checkbox" class="form-check-input media-row-check" data-path="' + escAttr(file.path) + '" onchange="toggleSelectItem(\'' + escAttr(file.path) + '\', this.checked)"></td>' +
                            '<td>' + icon + '</td>' +
                            '<td><a href="#" onclick="event.preventDefault(); showDetailPanel(window._mf_' + file._idx + ')" style="color:#3b82f6;text-decoration:none;">' + escHtml(file.name) + '</a></td>' +
                            '<td><span style="text-transform:uppercase;font-size:.8em;font-weight:600;color:#64748b;">' + escHtml(file.type) + '</span></td>' +
                            '<td>' + formatSize(file.size) + '</td>' +
                            '<td>' + catBadge + '</td>' +
                            '<td style="text-align:right;">' +
                                '<button class="action-btn" title="URL kopieren" onclick="copyToClipboard(\'' + escAttr(file.url) + '\')">🔗</button> ' +
                                '<button class="action-btn" title="Umbenennen" onclick="openRenameModal(\'' + escAttr(file.path) + '\', \'' + escAttr(file.name) + '\')">✎</button> ' +
                                deleteBtn +
                            '</td>';
                        tbody.appendChild(tr);

                        // Store file reference for detail panel
                        window['_mf_' + file._idx] = file;
                    });

                    if (folders.length === 0 && files.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:40px;font-style:italic;">Keine Einträge gefunden.</td></tr>';
                    }
                }

                /* ── Grid Rendering ── */
                function renderGrid(folders, files) {
                    const grid = document.getElementById('file-grid');
                    if (!grid) return;
                    grid.innerHTML = '';

                    folders.forEach(folder => {
                        const div = document.createElement('div');
                        div.className = 'media-grid-item media-grid-folder';
                        div.onclick = () => loadPath(folder.path);
                        div.innerHTML = '<div class="media-grid-thumb"><span style="font-size:2.5rem;">📂</span></div>' +
                            '<div class="media-grid-label" title="' + escAttr(folder.name) + '">' + escHtml(folder.name) + '</div>' +
                            '<div class="media-grid-meta">' + (folder.items_count !== undefined ? parseInt(folder.items_count) + ' Elemente' : 'Ordner') + '</div>';
                        grid.appendChild(div);
                    });

                    files.forEach(file => {
                        const isImage = ['jpg','jpeg','png','gif','webp','svg','bmp','ico'].includes((file.type||'').toLowerCase());
                        const div = document.createElement('div');
                        div.className = 'media-grid-item' + (currentDetailFile && currentDetailFile.path === file.path ? ' active' : '');
                        div.onclick = () => showDetailPanel(file);

                        if (isImage) {
                            div.innerHTML = '<div class="media-grid-thumb"><img src="' + escAttr(file.url) + '" alt="" loading="lazy"></div>';
                        } else {
                            div.innerHTML = '<div class="media-grid-thumb"><span style="font-size:2rem;">' + getFileIcon(file.type) + '</span></div>';
                        }
                        div.innerHTML += '<div class="media-grid-label" title="' + escAttr(file.name) + '">' + escHtml(file.name) + '</div>' +
                            '<div class="media-grid-meta">' + formatSize(file.size) + '</div>';
                        grid.appendChild(div);
                    });

                    if (folders.length === 0 && files.length === 0) {
                        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:40px;font-style:italic;">Keine Einträge gefunden.</div>';
                    }
                }

                /* ── Category Badge Helper ── */
                function getCatBadge(category) {
                    if (!category) return '<span style="color:#cbd5e1;font-size:.8em;">-</span>';
                    const cat = allCategories.find(c => c.slug === category);
                    if (cat) return '<span class="badge bg-azure-lt">' + escHtml(cat.name) + '</span>';
                    return '<span class="badge bg-secondary-lt">' + escHtml(category) + '</span>';
                }

                /* ── Assign file indices for detail panel references ── */
                const _origApplyFilter = applyFilter;
                // Wrap to assign _idx before rendering
                applyFilter = function() {
                    allItems.files.forEach((f, i) => { f._idx = i; window['_mf_' + i] = f; });
                    _origApplyFilter();
                };

                /* ── Modals & Actions ── */
                function openAssignModal(path, currentSlug) {
                    document.getElementById('assign-file-path').value = path;
                    document.getElementById('assign-category-slug').value = currentSlug;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('assign-modal')).show();
                }
                async function submitAssignment() {
                    const filePath = document.getElementById('assign-file-path').value;
                    const slug = document.getElementById('assign-category-slug').value;
                    const fd = new FormData();
                    fd.append('action', 'assign_category');
                    fd.append('file_path', filePath);
                    fd.append('slug', slug);
                    const response = await cmsPost(fd);
                    const result = await response.json();
                    if (result.success) {
                        closeModal('assign-modal');
                        showMediaNotice('Kategorie aktualisiert.', 'success');
                        loadPath(currentPath);
                    } else {
                        showMediaNotice('Fehler: ' + result.error, 'error');
                    }
                }

                // XSS-Schutz
                function escHtml(str) {
                    if (str == null) return '';
                    const div = document.createElement('div');
                    div.appendChild(document.createTextNode(String(str)));
                    return div.innerHTML;
                }
                function escAttr(str) {
                    return escHtml(str).replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                }

                function getFileTypeCategory(extension) {
                    const ext = (extension||'').toLowerCase();
                    const cats = {
                        'image': ['jpg','jpeg','png','gif','webp','svg','bmp','ico'],
                        'video': ['mp4','webm','ogg','mov','avi','mkv'],
                        'audio': ['mp3','wav','aac','flac','m4a'],
                        'document': ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','rtf','csv'],
                        'archive': ['zip','rar','7z','tar','gz'],
                        'font': ['ttf','otf','woff','woff2','eot']
                    };
                    for (const [cat, exts] of Object.entries(cats)) { if (exts.includes(ext)) return cat; }
                    return 'other';
                }
                function getFileIcon(extension) {
                    const icons = {
                        'pdf':'📄','doc':'📝','docx':'📝','xls':'📊','xlsx':'📊','ppt':'📽️','pptx':'📽️',
                        'zip':'📦','rar':'📦','7z':'📦','mp3':'🎵','wav':'🎵','mp4':'🎬','mov':'🎬','avi':'🎬',
                        'txt':'📄','php':'🐘','js':'📜','css':'🎨','html':'🌐',
                        'ttf':'🔤','otf':'🔤','woff':'🔤','woff2':'🔤'
                    };
                    return icons[(extension||'').toLowerCase()] || '📄';
                }

                function updateBreadcrumb(path) {
                    const container = document.getElementById('breadcrumb');
                    if (!container) return;
                    if (!path) { container.innerHTML = '<span onclick="loadPath(\'\')">Home</span>'; return; }
                    const parts = path.split('/').filter(p => p);
                    let html = '<span onclick="loadPath(\'\')">Home</span>';
                    parts.forEach((part, index) => {
                        let clickPath = parts.slice(0, index + 1).join('/');
                        html += ' / ';
                        html += index === parts.length - 1
                            ? '<span>' + escHtml(part) + '</span>'
                            : '<span onclick="loadPath(\'' + escAttr(clickPath) + '\')">' + escHtml(part) + '</span>';
                    });
                    container.innerHTML = html;
                }

                async function createFolder() {
                    const nameInput = document.getElementById('new-folder-name');
                    const name = nameInput.value.trim();
                    if (!name) return;
                    const fd = new FormData();
                    fd.append('action', 'create_folder');
                    fd.append('name', name);
                    fd.append('path', currentPath);
                    const response = await cmsPost(fd);
                    const result = await response.json();
                    if (result.success) {
                        closeModal('create-folder-modal');
                        nameInput.value = '';
                        showMediaNotice('Ordner erfolgreich erstellt.', 'success');
                        loadPath(currentPath);
                    } else {
                        showMediaNotice('Fehler: ' + result.error, 'error');
                    }
                }

                async function handleFileUpload(input) {
                    if (input.files.length === 0) return;
                    const btn = document.querySelector('.col-auto .btn-primary');
                    const oldText = btn ? btn.innerText : '';
                    if (btn) { btn.innerText = 'Upload läuft...'; btn.disabled = true; }

                    let errors = [];
                    for (let i = 0; i < input.files.length; i++) {
                        const fd = new FormData();
                        fd.append('action', 'upload_file');
                        fd.append('file', input.files[i]);
                        fd.append('path', currentPath);
                        try {
                            const response = await cmsPost(fd);
                            const result = await response.json();
                            if (!result.success) errors.push(input.files[i].name + ': ' + result.error);
                        } catch (e) { errors.push(input.files[i].name + ': Netzwerkfehler'); }
                    }
                    input.value = '';
                    if (btn) { btn.innerText = oldText; btn.disabled = false; }
                    if (errors.length > 0) {
                        showMediaNotice('Einige Uploads sind fehlgeschlagen: ' + errors.join(' | '), 'error');
                    } else {
                        showMediaNotice('Upload erfolgreich abgeschlossen.', 'success');
                    }
                    loadPath(currentPath);
                }

                async function deleteItem(path, type) {
                    const delModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('mediaDeleteModal'));
                    delModal.show();
                    document.getElementById('mediaDeleteMsg').textContent =
                        'Möchten Sie ' + (type === 'folder' ? 'diesen Ordner und dessen Inhalt' : 'diese Datei') + ' wirklich löschen?';
                    document.getElementById('mediaDeleteConfirmBtn').onclick = async function() {
                        delModal.hide();
                        const fd = new FormData();
                        fd.append('action', 'delete_item');
                        fd.append('item_path', path);
                        try {
                            const response = await cmsPost(fd);
                            const result = await response.json();
                            if (result.success) {
                                closeDetailPanel();
                                showMediaNotice('Element erfolgreich gelöscht.', 'success');
                                loadPath(currentPath);
                            } else {
                                showMediaNotice('Fehler: ' + result.error, 'error');
                            }
                        } catch (e) {
                            showMediaNotice('Netzwerkfehler beim Löschen.', 'error');
                        }
                    };
                }

                function openRenameModal(path, name) {
                    document.getElementById('rename-old-path').value = path;
                    const input = document.getElementById('rename-new-name');
                    input.value = name;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('rename-modal')).show();
                    setTimeout(() => input.focus(), 150);
                }
                async function renameItem() {
                    const oldPath = document.getElementById('rename-old-path').value;
                    const newName = document.getElementById('rename-new-name').value.trim();
                    if (!newName) return;
                    const fd = new FormData();
                    fd.append('action', 'rename_item');
                    fd.append('old_path', oldPath);
                    fd.append('new_name', newName);
                    try {
                        const response = await cmsPost(fd);
                        const result = await response.json();
                        if (result.success) {
                            closeModal('rename-modal');
                            showMediaNotice('Element erfolgreich umbenannt.', 'success');
                            loadPath(currentPath);
                        } else {
                            showMediaNotice('Fehler: ' + result.error, 'error');
                        }
                    } catch (e) {
                        showMediaNotice('Netzwerkfehler.', 'error');
                    }
                }

                function handleDrop(e) {
                    e.preventDefault();
                    document.querySelector('.media-content-area').classList.remove('drag-over');
                    if (e.dataTransfer.files.length > 0) {
                        const input = document.getElementById('file-upload');
                        input.files = e.dataTransfer.files;
                        handleFileUpload(input);
                    }
                }

                function openCreateFolderModal() {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('create-folder-modal')).show();
                    setTimeout(() => document.getElementById('new-folder-name').focus(), 150);
                }
                function closeModal(id) {
                    const el = document.getElementById(id);
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) inst.hide();
                }
                function openImagePreview(url) {
                    document.getElementById('preview-image-full').src = url;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('image-preview-modal')).show();
                }
                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        const btn = document.activeElement;
                        if (btn) { const old = btn.innerText; btn.innerText = '✓'; setTimeout(() => btn.innerText = old, 1000); }
                        showMediaNotice('URL in die Zwischenablage kopiert.', 'info');
                    });
                }
                function formatDate(timestamp) { return new Date(timestamp * 1000).toLocaleString('de-DE'); }
                function formatSize(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + units[i];
                }
            </script>
        <?php endif; ?>
    </div><!-- /.media-content-area -->
<?php renderAdminLayoutEnd(); ?>
