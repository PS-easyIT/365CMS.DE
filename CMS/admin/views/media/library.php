<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media – Bibliothek View
 *
 * Erwartet: $data (aus MediaModule::getLibraryData())
 *           $alert, $csrfToken
 */

$folders    = $data['folders'] ?? [];
$files      = $data['files'] ?? [];
$categories = $data['categories'] ?? [];
$diskUsage  = $data['diskUsage'] ?? [];
$path       = $data['path'] ?? '';
$category   = $data['category'] ?? '';

// Breadcrumb-Pfad aufbauen
$breadcrumbs = [];
if ($path !== '') {
    $parts = explode('/', trim($path, '/'));
    $cumulative = '';
    foreach ($parts as $part) {
        $cumulative .= ($cumulative ? '/' : '') . $part;
        $breadcrumbs[] = ['label' => $part, 'path' => $cumulative];
    }
}

// Dateityp-Icon Helper
function mediaTypeIcon(string $type): string {
    $icons = [
        'image' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M6 18l3.5 -4a4 4 0 0 1 5 -.5l5.5 4.5"/></svg>',
        'video' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-red" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>',
        'audio' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-purple" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M16 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M9 17v-13h10v13"/><path d="M9 8h10"/></svg>',
    ];
    return $icons[$type] ?? '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>';
}

function getFileType(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'svg'])) return 'image';
    if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv'])) return 'video';
    if (in_array($ext, ['mp3', 'wav', 'aac', 'flac', 'm4a'])) return 'audio';
    return 'document';
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Medienverwaltung</div>
                <h2 class="page-title">Medien</h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newFolderModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3"/><path d="M16 19h6"/><path d="M19 16v6"/></svg>
                    Neuer Ordner
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                    Hochladen
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible mb-3" role="alert">
                <div class="d-flex"><div><?php echo htmlspecialchars($alert['message']); ?></div></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-primary text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($diskUsage['count'] ?? 0); ?></div>
                                <div class="text-secondary">Dateien</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-azure text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 6h3.5l1.5 -1.5h2l1.5 1.5h3.5v12h-12z"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo htmlspecialchars($diskUsage['formatted'] ?? '0 B'); ?></div>
                                <div class="text-secondary">Speicher</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-yellow text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo count($folders); ?></div>
                                <div class="text-secondary">Ordner</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-teal text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><rect x="9" y="3" width="12" height="12" rx="2"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo count($categories); ?></div>
                                <div class="text-secondary">Kategorien</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breadcrumb & Filter -->
        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col">
                        <nav aria-label="Breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/media">Uploads</a>
                                </li>
                                <?php foreach ($breadcrumbs as $i => $bc): ?>
                                    <?php if ($i === count($breadcrumbs) - 1): ?>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($bc['label']); ?></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/media?path=<?php echo urlencode($bc['path']); ?>"><?php echo htmlspecialchars($bc['label']); ?></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" onchange="window.location.href='<?php echo htmlspecialchars(SITE_URL); ?>/admin/media?path=<?php echo urlencode($path); ?>&category='+this.value">
                            <option value="">Alle Kategorien</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?> (<?php echo (int)$cat['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Datei-Grid -->
            <div class="card-body">
                <?php if (empty($folders) && empty($files)): ?>
                    <div class="empty">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg>
                        </div>
                        <p class="empty-title">Dieser Ordner ist leer</p>
                        <p class="empty-subtitle text-secondary">Legen Sie einen Ordner an oder laden Sie Dateien hoch.</p>
                    </div>
                <?php else: ?>
                    <div class="row row-cards">
                        <!-- Ordner -->
                        <?php foreach ($folders as $folder): ?>
                            <?php $folderName = $folder['name'] ?? $folder; ?>
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/media?path=<?php echo urlencode(($path ? $path . '/' : '') . $folderName); ?>" class="card card-sm text-center text-decoration-none">
                                    <div class="card-body py-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-yellow mb-1" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg>
                                        <div class="text-truncate small font-weight-medium"><?php echo htmlspecialchars($folderName); ?></div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>

                        <!-- Dateien -->
                        <?php foreach ($files as $file): ?>
                            <?php
                            $fname = $file['name'] ?? '';
                            $ftype = getFileType($fname);
                            $fsize = '';
                            if (isset($file['size'])) {
                                $bytes = (int)$file['size'];
                                if ($bytes >= 1048576) { $fsize = round($bytes / 1048576, 1) . ' MB'; }
                                elseif ($bytes >= 1024) { $fsize = round($bytes / 1024, 1) . ' KB'; }
                                else { $fsize = $bytes . ' B'; }
                            }
                            $fpath = ($path ? $path . '/' : '') . $fname;
                            $isImage = $ftype === 'image';
                            ?>
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <div class="card card-sm text-center">
                                    <div class="card-body py-3">
                                        <?php if ($isImage): ?>
                                            <img src="<?php echo htmlspecialchars(UPLOAD_PATH . '/' . $fpath); ?>" alt="<?php echo htmlspecialchars($fname); ?>" class="rounded mb-1" style="max-height:60px;max-width:100%;object-fit:cover;" loading="lazy">
                                        <?php else: ?>
                                            <div class="mb-1"><?php echo mediaTypeIcon($ftype); ?></div>
                                        <?php endif; ?>
                                        <div class="text-truncate small font-weight-medium" title="<?php echo htmlspecialchars($fname); ?>"><?php echo htmlspecialchars($fname); ?></div>
                                        <div class="text-secondary" style="font-size:0.7rem;"><?php echo $fsize; ?></div>
                                    </div>
                                    <div class="card-footer p-1">
                                        <div class="btn-list justify-content-center">
                                            <button class="btn btn-ghost-danger btn-icon btn-sm" title="Löschen" onclick="deleteMedia('<?php echo htmlspecialchars(addslashes($fpath), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($fname), ENT_QUOTES); ?>')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Ordner-erstellen Modal -->
<div class="modal modal-blur fade" id="newFolderModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="parent_path" value="<?php echo htmlspecialchars($path); ?>">
            <div class="modal-header">
                <h5 class="modal-title">Neuer Ordner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="folderName">Ordnername</label>
                <input type="text" class="form-control" id="folderName" name="folder_name" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal modal-blur fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="target_path" value="<?php echo htmlspecialchars($path); ?>">
            <div class="modal-header">
                <h5 class="modal-title">Dateien hochladen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="uploadFiles">Dateien auswählen</label>
                    <input type="file" class="form-control" id="uploadFiles" name="files[]" multiple required>
                </div>
                <div class="text-secondary small">Mehrfachauswahl möglich. Maximale Dateigröße laut Einstellungen.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Hochladen</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete-Formular -->
<form id="deleteMediaForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete_item">
    <input type="hidden" name="item_path" id="deleteMediaPath">
</form>

<script>
function deleteMedia(path, name) {
    cmsConfirm({
        title: 'Datei löschen',
        message: '<strong>' + name + '</strong> wirklich löschen?',
        confirmText: 'Löschen',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            document.getElementById('deleteMediaPath').value = path;
            document.getElementById('deleteMediaForm').submit();
        }
    });
}
</script>
