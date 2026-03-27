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
$view       = $data['view'] ?? 'list';
$search     = $data['search'] ?? '';
$confirmMember = (string)($_GET['confirm_member'] ?? '') === '1';
$memberFolderConfirmMessage = 'Der Member-Bereich enthält sensible Uploads. Möchten Sie den Ordner wirklich öffnen?';
$mediaLibraryConfig = [
    'memberFolderConfirmMessage' => $memberFolderConfirmMessage,
    'deleteFormId' => 'deleteMediaForm',
    'deletePathFieldId' => 'deleteMediaPath',
];

function mediaAdminUrl(array $params = []): string {
    $params = array_filter($params, static fn($value): bool => $value !== '' && $value !== null);
    $base = SITE_URL . '/admin/media';

    return $params !== [] ? $base . '?' . http_build_query($params) : $base;
}

$libraryState = [
    'path' => $path,
    'view' => $view,
    'category' => $category,
    'q' => $search,
];

if ($confirmMember) {
    $libraryState['confirm_member'] = '1';
}

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

function mediaFolderRequiresConfirmation(string $folderPath): bool {
    $folderPath = trim(str_replace('\\', '/', $folderPath), '/');
    return $folderPath === 'member' || str_starts_with($folderPath, 'member/');
}

$elFinderConnectorUrl = SITE_URL . '/api/v1/admin/media/elfinder';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Medienverwaltung</div>
                <h2 class="page-title">Medien</h2>
            </div>
            <?php if ($view !== 'finder'): ?>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
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
                <div class="w-100">
                    <div class="media-toolbar">
                        <nav aria-label="Breadcrumb">
                            <ol class="breadcrumb mb-0 media-breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars(mediaAdminUrl(['view' => $view, 'category' => $category, 'q' => $search] + ($confirmMember ? ['confirm_member' => '1'] : []))); ?>">Uploads</a></li>
                                <?php foreach ($breadcrumbs as $i => $bc): ?>
                                    <?php if ($i === count($breadcrumbs) - 1): ?>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($bc['label']); ?></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo htmlspecialchars(mediaAdminUrl([
                                                'path' => $bc['path'],
                                                'view' => $view,
                                                'category' => $category,
                                                'q' => $search,
                                            ] + ($confirmMember ? ['confirm_member' => '1'] : []))); ?>"><?php echo htmlspecialchars($bc['label']); ?></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>

                        <div class="media-toolbar-right <?php echo $view !== 'finder' ? 'media-toolbar-right--browse' : ''; ?>">
                            <?php if ($view !== 'finder'): ?>
                                <form method="get" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/media" class="media-filters">
                                    <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
                                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                                    <?php if ($confirmMember): ?>
                                        <input type="hidden" name="confirm_member" value="1">
                                    <?php endif; ?>
                                    <select class="form-select form-select-sm media-filter-category" name="category" onchange="this.form.submit()">
                                        <option value="">Alle Kategorien</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo $category === $cat['slug'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?> (<?php echo (int)$cat['count']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="input-group input-group-sm media-filter-search">
                                        <input type="search" class="form-control" name="q" placeholder="Dateien suchen …" value="<?php echo htmlspecialchars($search); ?>" maxlength="120">
                                        <button type="submit" class="btn btn-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>

                            <div class="media-view-toggle" role="group" aria-label="Ansicht umschalten">
                                <a href="<?php echo htmlspecialchars(mediaAdminUrl(array_merge($libraryState, ['view' => 'finder']))); ?>" class="btn media-view-primary <?php echo $view === 'finder' ? 'active' : ''; ?>">
                                    <span class="media-view-icon" aria-hidden="true">🗂️</span>
                                    elFinder
                                    <span class="media-view-badge">Standard</span>
                                </a>
                                <div class="media-view-secondary" aria-label="Alternative Ansichten">
                                    <span class="media-view-label">Alternativen</span>
                                    <div class="media-view-alt-group">
                                        <a href="<?php echo htmlspecialchars(mediaAdminUrl(array_merge($libraryState, ['view' => 'list']))); ?>" class="btn media-view-alt <?php echo $view === 'list' ? 'active' : ''; ?>">
                                            <span class="media-view-icon" aria-hidden="true">≣</span>
                                            Liste
                                        </a>
                                        <a href="<?php echo htmlspecialchars(mediaAdminUrl(array_merge($libraryState, ['view' => 'grid']))); ?>" class="btn media-view-alt <?php echo $view === 'grid' ? 'active' : ''; ?>">
                                            <span class="media-view-icon" aria-hidden="true">⊞</span>
                                            Grid
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <?php if ($view === 'finder'): ?>
                    <?php
                    $alertData = [
                        'type' => 'info',
                        'message' => 'Der Datei-Manager läuft im Admin-Kontext.',
                        'details' => [
                            'Änderungen werden direkt im Upload-Verzeichnis ausgeführt.',
                        ],
                    ];
                    $alertDismissible = false;
                    $alertMarginClass = 'mb-3';
                    require __DIR__ . '/../partials/flash-alert.php';
                    ?>
                    <div
                        id="cmsElfinder"
                        data-elfinder
                        data-connector-url="<?php echo htmlspecialchars($elFinderConnectorUrl, ENT_QUOTES); ?>"
                        data-csrf-token="<?php echo htmlspecialchars($mediaConnectorToken, ENT_QUOTES); ?>"
                        data-jquery-script="<?php echo htmlspecialchars(cms_asset_url('elfinder/vendor/jquery/jquery-3.7.1.min.js'), ENT_QUOTES); ?>"
                        data-jquery-ui-script="<?php echo htmlspecialchars(cms_asset_url('elfinder/vendor/jquery-ui/jquery-ui-1.13.2.min.js'), ENT_QUOTES); ?>"
                        data-elfinder-script="<?php echo htmlspecialchars(cms_asset_url('elfinder/js/elfinder.min.js'), ENT_QUOTES); ?>"
                        style="min-height: 70vh;"></div>
                <?php elseif (empty($folders) && empty($files)): ?>
                    <div class="empty">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg>
                        </div>
                        <p class="empty-title">Dieser Ordner ist leer</p>
                        <p class="empty-subtitle text-secondary">Legen Sie einen Ordner an oder laden Sie Dateien hoch.</p>
                    </div>
                <?php else: ?>
                    <?php if ($view === 'grid'): ?>
                        <div class="media-grid">
                            <?php foreach ($folders as $folder): ?>
                                <?php
                                $folderName = (string)($folder['name'] ?? '');
                                $folderPath = (string)($folder['path'] ?? trim(($path !== '' ? $path . '/' : '') . $folderName, '/'));
                                $folderUrl = mediaAdminUrl([
                                    'path' => $folderPath,
                                    'view' => $view,
                                    'category' => $category,
                                    'q' => $search,
                                ] + ($confirmMember ? ['confirm_member' => '1'] : []));
                                $confirmUrl = mediaAdminUrl([
                                    'path' => $folderPath,
                                    'view' => $view,
                                    'category' => $category,
                                    'q' => $search,
                                    'confirm_member' => '1',
                                ]);
                                $requiresConfirm = mediaFolderRequiresConfirmation($folderPath) && !$confirmMember;
                                ?>
                                <div class="media-grid-item media-grid-folder">
                                    <a href="<?php echo htmlspecialchars($folderUrl); ?>" class="text-decoration-none text-reset media-folder-link" <?php echo $requiresConfirm ? 'data-member-folder-confirm="1" data-confirm-url="' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '"' : ''; ?>>
                                        <div class="media-grid-thumb"><span class="folder-icon">📁</span></div>
                                        <div class="media-grid-label"><?php echo htmlspecialchars($folderName); ?></div>
                                        <div class="media-grid-meta"><?php echo (int)($folder['items_count'] ?? 0); ?> Einträge</div>
                                    </a>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($files as $file): ?>
                                <?php
                                $fname = (string)($file['name'] ?? '');
                                $ftype = getFileType($fname);
                                $fpath = (string)($file['path'] ?? trim(($path !== '' ? $path . '/' : '') . $fname, '/'));
                                $isImage = $ftype === 'image';
                                ?>
                                <div class="media-grid-item">
                                    <div class="media-grid-thumb">
                                        <?php if ($isImage): ?>
                                            <img src="<?php echo htmlspecialchars((string)($file['preview_url'] ?? $file['url'] ?? (UPLOAD_URL . '/' . $fpath))); ?>" alt="<?php echo htmlspecialchars($fname); ?>" loading="lazy">
                                        <?php else: ?>
                                            <?php echo mediaTypeIcon($ftype); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="media-grid-label"><?php echo htmlspecialchars($fname); ?></div>
                                    <div class="media-grid-meta"><?php echo htmlspecialchars($file['category'] ?? 'Ohne Kategorie'); ?></div>
                                    <div class="p-2 pt-0 text-center">
                                        <button type="button" class="btn btn-ghost-danger btn-icon btn-sm js-media-delete" title="Löschen" data-delete-path="<?php echo htmlspecialchars($fpath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars($fname, ENT_QUOTES); ?>" data-delete-type="Datei">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table media-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">Typ</th>
                                        <th>Name</th>
                                        <th>Kategorie</th>
                                        <th>Größe</th>
                                        <th>Geändert</th>
                                        <th class="w-1">Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($folders as $folder): ?>
                                        <?php
                                        $folderName = (string)($folder['name'] ?? '');
                                        $folderPath = (string)($folder['path'] ?? trim(($path !== '' ? $path . '/' : '') . $folderName, '/'));
                                        $folderUrl = mediaAdminUrl([
                                            'path' => $folderPath,
                                            'view' => $view,
                                            'category' => $category,
                                            'q' => $search,
                                        ] + ($confirmMember ? ['confirm_member' => '1'] : []));
                                        $confirmUrl = mediaAdminUrl([
                                            'path' => $folderPath,
                                            'view' => $view,
                                            'category' => $category,
                                            'q' => $search,
                                            'confirm_member' => '1',
                                        ]);
                                        $requiresConfirm = mediaFolderRequiresConfirmation($folderPath) && !$confirmMember;
                                        ?>
                                        <tr>
                                            <td><span class="folder-icon">📁</span></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($folderUrl); ?>" class="fw-semibold text-reset media-folder-link" <?php echo $requiresConfirm ? 'data-member-folder-confirm="1" data-confirm-url="' . htmlspecialchars($confirmUrl, ENT_QUOTES) . '"' : ''; ?>>
                                                    <?php echo htmlspecialchars($folderName); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if (!empty($folder['category'])): ?>
                                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars((string)$folder['category']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-secondary">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-secondary"><?php echo (int)($folder['items_count'] ?? 0); ?> Einträge</td>
                                            <td class="text-secondary"><?php echo !empty($folder['modified']) ? htmlspecialchars(date('d.m.Y H:i', (int)$folder['modified'])) : '—'; ?></td>
                                            <td>
                                                <?php if (empty($folder['is_system'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars($folderName, ENT_QUOTES); ?>" data-delete-type="Ordner">Löschen</button>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-lt">System</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php foreach ($files as $file): ?>
                                        <?php
                                        $fname = (string)($file['name'] ?? '');
                                        $ftype = getFileType($fname);
                                        $fpath = (string)($file['path'] ?? trim(($path !== '' ? $path . '/' : '') . $fname, '/'));
                                        $fileUrl = (string)($file['url'] ?? (UPLOAD_URL . '/' . $fpath));
                                        $previewUrl = (string)($file['preview_url'] ?? $fileUrl);
                                        $isImage = $ftype === 'image';
                                        $fsize = isset($file['size']) ? htmlspecialchars((string)($diskUsage['formatted'] ?? '')) : '—';
                                        if (isset($file['size'])) {
                                            $bytes = (int)$file['size'];
                                            if ($bytes >= 1048576) {
                                                $fsize = round($bytes / 1048576, 1) . ' MB';
                                            } elseif ($bytes >= 1024) {
                                                $fsize = round($bytes / 1024, 1) . ' KB';
                                            } else {
                                                $fsize = $bytes . ' B';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($isImage): ?>
                                                    <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars($fname); ?>" class="media-thumb" loading="lazy">
                                                <?php else: ?>
                                                    <span class="media-thumb-icon"><?php echo mediaTypeIcon($ftype); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($fileUrl); ?>" target="_blank" rel="noopener noreferrer" class="fw-semibold text-reset">
                                                    <?php echo htmlspecialchars($fname); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <form method="post" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="assign_category">
                                                    <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($fpath); ?>">
                                                    <select class="form-select form-select-sm" name="category_slug" onchange="this.form.submit()">
                                                        <option value="">Ohne Kategorie</option>
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo (($file['category'] ?? '') === $cat['slug']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($cat['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-secondary"><?php echo htmlspecialchars($fsize); ?></td>
                                            <td class="text-secondary"><?php echo !empty($file['modified']) ? htmlspecialchars(date('d.m.Y H:i', (int)$file['modified'])) : '—'; ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($fpath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars($fname, ENT_QUOTES); ?>" data-delete-type="Datei">Löschen</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                <input type="text" class="form-control" id="folderName" name="folder_name" maxlength="120" required autofocus>
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
                    <input
                        type="file"
                        class="form-control"
                        id="uploadFiles"
                        name="files[]"
                        multiple
                        required
                        data-filepond-upload
                        data-upload-url="<?php echo htmlspecialchars(SITE_URL . '/api/upload', ENT_QUOTES); ?>"
                        data-upload-path="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>"
                        data-csrf-token="<?php echo htmlspecialchars($mediaActionToken, ENT_QUOTES); ?>">
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

<script type="application/json" id="media-library-config">
<?php echo json_encode($mediaLibraryConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
