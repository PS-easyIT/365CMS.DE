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
                    'max_upload_size' => $_POST['max_upload_size'] ?? '64M',
                    'allowed_types' => $_POST['allowed_types'] ?? [],
                    'auto_webp' => isset($_POST['auto_webp']),
                    'strip_exif' => isset($_POST['strip_exif']),
                    'max_width' => (int)($_POST['max_width'] ?? 2560),
                    'max_height' => (int)($_POST['max_height'] ?? 2560),
                    'organize_month_year' => isset($_POST['organize_month_year']),
                    'member_uploads_enabled' => isset($_POST['member_uploads_enabled'])
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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .media-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            flex-wrap: wrap;
            gap: 15px;
        }
        .media-filters {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-grow: 1;
        }
        .breadcrumb {
            display: flex;
            gap: 5px;
            color: #64748b;
            font-size: 0.9em;
            margin-right: 15px;
        }
        .breadcrumb span {
            cursor: pointer;
            color: #3b82f6;
        }
        .breadcrumb span:hover {
            text-decoration: underline;
        }
        .breadcrumb span:last-child {
            color: #64748b;
            cursor: default;
            text-decoration: none;
        }
        .media-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .media-table th {
            text-align: left;
            padding: 15px;
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
        }
        .media-table td {
            padding: 15px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            vertical-align: middle;
        }
        .media-preview {
            width: 40px; 
            height: 40px; 
            object-fit: cover; 
            border-radius: 4px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            font-size: 20px;
        }
        .folder-icon {
            font-size: 24px;
            color: #fbbf24;
        }
        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #64748b;
            padding: 6px;
            border-radius: 4px;
            margin-left: 2px;
            transition: all 0.2s;
        }
        .action-btn:hover {
            background: #f1f5f9;
            color: #3b82f6;
        }
        .action-btn.delete:hover {
            color: #ef4444;
            background: #fee2e2;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            animation: modalFadeIn 0.2s ease-out;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal h3 { margin-top: 0; margin-bottom: 20px; font-size: 1.25rem; color: #1e293b; }
        .modal-footer {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .drag-over {
            border: 2px dashed #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
            position: relative;
        }
        .drag-over::after {
            content: 'Dateien hier ablegen zum Hochladen';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            color: #3b82f6;
            pointer-events: none;
            font-weight: 600;
        }
        .settings-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        .form-section {
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 25px;
        }
        .form-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .form-section h3 {
            margin-top: 0;
            color: #334155;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
    </style>
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
                    <p class="text-muted">Konfigurieren Sie Uploads, Bildgrößen und Dateitypen.</p>
                </div>
            </div>

            <div class="settings-card">
                <?php
                $isChecked = fn($key) => !empty($settings[$key]) ? 'checked' : '';
                $isTypeAllowed = fn($type) => in_array($type, $settings['allowed_types'] ?? []) ? 'checked' : '';
                $isSelected = fn($key, $val) => ($settings[$key] ?? '') === $val ? 'selected' : '';
                ?>
                <form id="media-settings-form" onsubmit="event.preventDefault(); saveSettings();">
                    
                    <!-- Upload Restrictions -->
                    <div class="form-section">
                        <h3>Datei-Uploads</h3>
                        <div class="form-group mb-3">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Maximale Dateigröße (Server-Limit: <?php echo ini_get('upload_max_filesize'); ?>)</label>
                            <select name="max_upload_size" class="form-control" style="width: 200px;">
                                <option value="2M" <?php echo $isSelected('max_upload_size', '2M'); ?>>2 MB</option>
                                <option value="5M" <?php echo $isSelected('max_upload_size', '5M'); ?>>5 MB</option>
                                <option value="10M" <?php echo $isSelected('max_upload_size', '10M'); ?>>10 MB</option>
                                <option value="32M" <?php echo $isSelected('max_upload_size', '32M'); ?>>32 MB</option>
                                <option value="64M" <?php echo $isSelected('max_upload_size', '64M'); ?>>64 MB</option>
                                <option value="128M" <?php echo $isSelected('max_upload_size', '128M'); ?>>128 MB</option>
                            </select>
                            <small class="text-muted">Begrenzt die Upload-Größe für Benutzer, unabhängig vom Server-Limit.</small>
                        </div>
                        
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Erlaubte Dateitypen</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                <label><input type="checkbox" name="allowed_types[]" value="image" <?php echo $isTypeAllowed('image'); ?>> Bilder (jpg, png, webp...)</label>
                                <label><input type="checkbox" name="allowed_types[]" value="document" <?php echo $isTypeAllowed('document'); ?>> Dokumente (pdf, docx...)</label>
                                <label><input type="checkbox" name="allowed_types[]" value="video" <?php echo $isTypeAllowed('video'); ?>> Videos (mp4, webm...)</label>
                                <label><input type="checkbox" name="allowed_types[]" value="audio" <?php echo $isTypeAllowed('audio'); ?>> Audio (mp3, wav...)</label>
                                <label><input type="checkbox" name="allowed_types[]" value="archive" <?php echo $isTypeAllowed('archive'); ?>> Archive (zip, rar...)</label>
                                <label><input type="checkbox" name="allowed_types[]" value="svg" <?php echo $isTypeAllowed('svg'); ?>> SVG (Sicherheitsrisiko beachten)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Image Processing -->
                    <div class="form-section">
                        <h3>Bildverarbeitung</h3>
                        <div class="form-group mb-3">
                            <label class="checkbox-label">
                                <input type="checkbox" name="auto_webp" <?php echo $isChecked('auto_webp'); ?>> 
                                <strong>Automatische WebP-Konvertierung</strong>
                                <p class="text-muted" style="margin:5px 0 0 25px;">Wandelt JPEGs und PNGs beim Upload automatisch in das effiziente WebP-Format um.</p>
                            </label>
                        </div>
                        <div class="form-group mb-3">
                            <label class="checkbox-label">
                                <input type="checkbox" name="strip_exif" <?php echo $isChecked('strip_exif'); ?>>
                                <strong>Metadaten entfernen (EXIF)</strong>
                                <p class="text-muted" style="margin:5px 0 0 25px;">Entfernt GPS- und Kameradaten zum Schutz der Privatsphäre.</p>
                            </label>
                        </div>
                        <div class="form-group">
                            <label style="display:block; margin-bottom:5px; font-weight:500;">Maximale Bilddimensionen</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="number" class="form-control" name="max_width" placeholder="Breite" value="<?php echo htmlspecialchars((string)($settings['max_width'] ?? 2560)); ?>" style="width:100px;">
                                <span>x</span>
                                <input type="number" class="form-control" name="max_height" placeholder="Höhe" value="<?php echo htmlspecialchars((string)($settings['max_height'] ?? 2560)); ?>" style="width:100px;">
                                <span>px</span>
                            </div>
                            <small class="text-muted">Größere Bilder werden beim Upload herunterskaliert.</small>
                        </div>
                    </div>

                    <!-- Organization -->
                    <div class="form-section">
                        <h3>Organisation</h3>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="organize_month_year" <?php echo $isChecked('organize_month_year'); ?>>
                                <strong>Uploads in Monat- und Jahr-basierten Ordnern organisieren</strong>
                                <p class="text-muted" style="margin:5px 0 0 25px;">Beispiel: <code>uploads/2026/02/datei.jpg</code></p>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Member Permissions -->
                    <div class="form-section">
                        <h3>Berechtigungen</h3>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="member_uploads_enabled" <?php echo $isChecked('member_uploads_enabled'); ?>>
                                <strong>Mitgliedern Uploads erlauben</strong>
                                <p class="text-muted" style="margin:5px 0 0 25px;">Wenn aktiviert, können Mitglieder im Dashboard Dateien in ihren eigenen Bereich hochladen (<code>uploads/member/{user}/...</code>).</p>
                            </label>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                    </div>
                </form>
            </div>

            <script>
                async function saveSettings() {
                    const form = document.getElementById('media-settings-form');
                    const formData = new FormData(form);
                    formData.append('action', 'save_settings');

                    const btn = form.querySelector('button[type="submit"]');
                    const oldText = btn.innerText;
                    btn.innerText = 'Speichere...';
                    btn.disabled = true;

                    try {
                        const response = await fetch('', { method: 'POST', body: formData });
                        const result = await response.json();
                        
                        if (result.success) {
                            alert(result.message);
                        } else {
                            alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
                        }
                    } catch (e) {
                        alert('Netzwerkfehler beim Speichern.');
                    } finally {
                        btn.innerText = oldText;
                        btn.disabled = false;
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
