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
$confirmMember = !empty($data['confirm_member']);
$memberFolderConfirmMessage = (string)($data['member_folder_confirm_message'] ?? 'Der Member-Bereich enthält sensible Uploads. Möchten Sie den Ordner wirklich öffnen?');
$breadcrumbs = is_array($data['breadcrumbs'] ?? null) ? $data['breadcrumbs'] : [];
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$categoryOptions = is_array($data['category_options'] ?? null) ? $data['category_options'] : [];
$filterState = is_array($data['filter_state'] ?? null) ? $data['filter_state'] : [];
$baseUrl = (string)($data['base_url'] ?? '/admin/media');
$listUrl = (string)($data['list_url'] ?? $baseUrl);
$gridUrl = (string)($data['grid_url'] ?? $baseUrl);
$rootUrl = (string)($data['root_url'] ?? $baseUrl);
$emptyState = is_array($data['empty_state'] ?? null) ? $data['empty_state'] : ['title' => 'Dieser Ordner ist leer', 'subtitle' => 'Legen Sie einen Ordner an oder laden Sie Dateien hoch.'];
$constraints = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$moveTargets = is_array($data['move_targets'] ?? null) ? $data['move_targets'] : [];
$bulkActions = is_array($data['bulk_actions'] ?? null) ? $data['bulk_actions'] : [];
$mediaLibraryConfig = [
    'memberFolderConfirmMessage' => $memberFolderConfirmMessage,
    'currentPath' => $path,
    'deleteFormId' => 'deleteMediaForm',
    'deletePathFieldId' => 'deleteMediaPath',
    'renameModalId' => 'mediaRenameModal',
    'renamePathFieldId' => 'mediaRenamePath',
    'renameNameFieldId' => 'mediaRenameName',
    'renameLabelId' => 'mediaRenameItemLabel',
    'moveModalId' => 'mediaMoveModal',
    'movePathFieldId' => 'mediaMovePath',
    'moveTargetFieldId' => 'mediaMoveTarget',
    'moveLabelId' => 'mediaMoveItemLabel',
    'bulkRootSelector' => '[data-media-library-root]',
    'bulkFormId' => 'mediaBulkForm',
    'bulkCountId' => 'mediaBulkSelectedCount',
    'bulkActionFieldId' => 'mediaBulkAction',
    'bulkMoveWrapId' => 'mediaBulkMoveWrap',
    'bulkMoveTargetFieldId' => 'mediaBulkTarget',
];

// Dateityp-Icon Helper
function mediaTypeIcon(string $type): string {
    $icons = [
        'image' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-green" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M6 18l3.5 -4a4 4 0 0 1 5 -.5l5.5 4.5"/></svg>',
        'video' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-red" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"/><rect x="3" y="6" width="12" height="12" rx="2"/></svg>',
        'audio' => '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-purple" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M16 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M9 17v-13h10v13"/><path d="M9 8h10"/></svg>',
    ];
    return $icons[$type] ?? '<svg xmlns="http://www.w3.org/2000/svg" class="icon text-secondary" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>';
}

function renderMoveTargetOptions(array $targets, string $selectedPath = ''): string {
    $html = '';

    foreach ($targets as $target) {
        $targetPath = (string)($target['path'] ?? '');
        $targetLabel = (string)($target['label'] ?? ($targetPath !== '' ? $targetPath : 'Uploads'));
        $html .= '<option value="' . htmlspecialchars($targetPath, ENT_QUOTES) . '"' . ($targetPath === $selectedPath ? ' selected' : '') . '>'
            . htmlspecialchars($targetLabel)
            . '</option>';
    }

    return $html;
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
                                <div class="font-weight-medium"><?php echo (int)($stats['file_count'] ?? ($diskUsage['count'] ?? 0)); ?></div>
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
                                <div class="font-weight-medium"><?php echo htmlspecialchars((string)($stats['storage_label'] ?? ($diskUsage['formatted'] ?? '0 B'))); ?></div>
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
                                <div class="font-weight-medium"><?php echo (int)($stats['folder_count'] ?? count($folders)); ?></div>
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
                                <div class="font-weight-medium"><?php echo (int)($stats['category_count'] ?? count($categories)); ?></div>
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
                        <div class="alert alert-info mb-3" role="alert">
                        <div class="d-flex">
                            <div>
                                    <strong>Bibliotheks-Grenzen:</strong>
                                maximal <?php echo (int)($constraints['max_upload_files'] ?? 0); ?> Dateien pro Upload,
                                Gesamtpaket bis <?php echo htmlspecialchars((string)($constraints['max_upload_batch_label'] ?? '—')); ?>,
                                Suchbegriff bis <?php echo (int)($constraints['search_max_length'] ?? 120); ?> Zeichen
                                und Ordnernamen bis <?php echo (int)($constraints['folder_name_max_length'] ?? 120); ?> Zeichen.
                            </div>
                        </div>
                    </div>
                    <div class="media-toolbar">
                        <nav aria-label="Breadcrumb">
                            <ol class="breadcrumb mb-0 media-breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($rootUrl); ?>">Uploads</a></li>
                                <?php foreach ($breadcrumbs as $i => $bc): ?>
                                    <?php if ($i === count($breadcrumbs) - 1): ?>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars((string)($bc['label'] ?? '')); ?></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item">
                                            <a href="<?php echo htmlspecialchars((string)($bc['url'] ?? $rootUrl)); ?>"><?php echo htmlspecialchars((string)($bc['label'] ?? '')); ?></a>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ol>
                        </nav>

                        <div class="media-toolbar-right media-toolbar-right--browse">
                            <form method="get" action="<?php echo htmlspecialchars($baseUrl); ?>" class="media-filters">
                                <input type="hidden" name="path" value="<?php echo htmlspecialchars($path); ?>">
                                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                                <?php if ($confirmMember): ?>
                                    <input type="hidden" name="confirm_member" value="1">
                                <?php endif; ?>
                                <select class="form-select form-select-sm media-filter-category" name="category" onchange="this.form.submit()">
                                    <option value="">Alle Kategorien</option>
                                    <?php foreach ($categoryOptions as $cat): ?>
                                        <option value="<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>" <?php echo $category === ($cat['slug'] ?? '') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($cat['name'] ?? '')); ?> (<?php echo (int)($cat['count'] ?? 0); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group input-group-sm media-filter-search">
                                    <input type="search" class="form-control" name="q" placeholder="Dateien suchen …" value="<?php echo htmlspecialchars($search); ?>" maxlength="<?php echo (int)($constraints['search_max_length'] ?? 120); ?>">
                                    <button type="submit" class="btn btn-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                    </button>
                                </div>
                            </form>

                            <div class="btn-group" role="group" aria-label="Ansicht umschalten">
                                <a href="<?php echo htmlspecialchars($listUrl); ?>" class="btn btn-outline-primary <?php echo $view === 'list' ? 'active' : ''; ?>">
                                    <span class="media-view-icon" aria-hidden="true">≣</span>
                                    Liste
                                </a>
                                <a href="<?php echo htmlspecialchars($gridUrl); ?>" class="btn btn-outline-primary <?php echo $view === 'grid' ? 'active' : ''; ?>">
                                    <span class="media-view-icon" aria-hidden="true">⊞</span>
                                    Grid
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <?php if (empty($folders) && empty($files)): ?>
                    <div class="empty">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg>
                        </div>
                        <p class="empty-title"><?php echo htmlspecialchars((string)($emptyState['title'] ?? 'Dieser Ordner ist leer')); ?></p>
                        <p class="empty-subtitle text-secondary"><?php echo htmlspecialchars((string)($emptyState['subtitle'] ?? 'Legen Sie einen Ordner an oder laden Sie Dateien hoch.')); ?></p>
                    </div>
                <?php else: ?>
                    <div data-media-library-root>
                        <div class="card card-sm mb-3" id="mediaBulkFormWrap">
                            <form id="mediaBulkForm" method="post" class="card-body d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                <input type="hidden" name="action" value="bulk_items">
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <label class="form-check mb-0">
                                        <input type="checkbox" class="form-check-input bulk-select-all" aria-label="Alle sichtbaren Medien auswählen">
                                        <span class="form-check-label">Alle sichtbaren auswählen</span>
                                    </label>
                                    <span class="badge bg-blue-lt"><span id="mediaBulkSelectedCount">0</span> ausgewählt</span>
                                </div>
                                <div class="d-flex flex-column flex-md-row gap-2 align-items-md-center ms-lg-auto w-100 justify-content-lg-end">
                                    <select class="form-select" id="mediaBulkAction" name="bulk_action" style="max-width: 15rem;">
                                        <option value="">Bulk-Aktion wählen …</option>
                                        <?php foreach ($bulkActions as $bulkAction): ?>
                                            <option value="<?php echo htmlspecialchars((string)($bulkAction['value'] ?? ''), ENT_QUOTES); ?>"><?php echo htmlspecialchars((string)($bulkAction['label'] ?? '')); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="d-none" id="mediaBulkMoveWrap">
                                        <select class="form-select" id="mediaBulkTarget" name="target_parent_path" style="min-width: 18rem; max-width: 24rem;">
                                            <?php echo renderMoveTargetOptions($moveTargets, $path); ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Auf Auswahl anwenden</button>
                                </div>
                            </form>
                        </div>
                    <?php if ($view === 'grid'): ?>
                        <div class="media-grid">
                            <?php foreach ($folders as $folder): ?>
                                <?php $folderPath = (string)($folder['path'] ?? ''); ?>
                                <div class="media-grid-item media-grid-folder">
                                    <?php if (empty($folder['is_system'])): ?>
                                        <div class="p-2 pb-0 d-flex justify-content-between align-items-start gap-2">
                                            <label class="form-check m-0">
                                                <input class="form-check-input bulk-row-check" type="checkbox" name="item_paths[]" form="mediaBulkForm" value="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" aria-label="Ordner <?php echo htmlspecialchars((string)($folder['name'] ?? 'Ordner'), ENT_QUOTES); ?> auswählen">
                                            </label>
                                            <div class="dropdown">
                                                <button class="btn btn-icon btn-ghost-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-label="Ordneraktionen">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <button type="button" class="dropdown-item js-media-open-rename" data-bs-toggle="modal" data-bs-target="#mediaRenameModal" data-media-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($folder['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Ordner">Umbenennen</button>
                                                    <button type="button" class="dropdown-item js-media-open-move" data-bs-toggle="modal" data-bs-target="#mediaMoveModal" data-media-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($folder['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Ordner" data-media-target="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>">Verschieben</button>
                                                    <button type="button" class="dropdown-item text-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars((string)($folder['name'] ?? 'Ordner'), ENT_QUOTES); ?>" data-delete-type="Ordner">Löschen</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars((string)($folder['url'] ?? $rootUrl)); ?>" class="text-decoration-none text-reset media-folder-link" <?php echo !empty($folder['requires_confirmation']) ? 'data-member-folder-confirm="1" data-confirm-url="' . htmlspecialchars((string)($folder['confirm_url'] ?? ''), ENT_QUOTES) . '"' : ''; ?>>
                                        <div class="media-grid-thumb"><span class="folder-icon">📁</span></div>
                                        <div class="media-grid-label"><?php echo htmlspecialchars((string)($folder['name'] ?? '')); ?></div>
                                        <div class="media-grid-meta"><?php echo (int)($folder['items_count'] ?? 0); ?> Einträge</div>
                                    </a>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($files as $file): ?>
                                <?php $filePath = (string)($file['path'] ?? ''); ?>
                                <div class="media-grid-item">
                                    <div class="p-2 pb-0 d-flex justify-content-between align-items-start gap-2">
                                        <label class="form-check m-0">
                                            <input class="form-check-input bulk-row-check" type="checkbox" name="item_paths[]" form="mediaBulkForm" value="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" aria-label="Datei <?php echo htmlspecialchars((string)($file['name'] ?? 'Datei'), ENT_QUOTES); ?> auswählen">
                                        </label>
                                        <div class="dropdown">
                                            <button class="btn btn-icon btn-ghost-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-label="Dateiaktionen">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <button type="button" class="dropdown-item js-media-open-rename" data-bs-toggle="modal" data-bs-target="#mediaRenameModal" data-media-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($file['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Datei">Umbenennen</button>
                                                <button type="button" class="dropdown-item js-media-open-move" data-bs-toggle="modal" data-bs-target="#mediaMoveModal" data-media-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($file['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Datei" data-media-target="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>">Verschieben</button>
                                                <button type="button" class="dropdown-item text-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars((string)($file['name'] ?? 'Datei'), ENT_QUOTES); ?>" data-delete-type="Datei">Löschen</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="media-grid-thumb">
                                        <?php if (!empty($file['is_image'])): ?>
                                            <img src="<?php echo htmlspecialchars((string)($file['preview_url'] ?? '')); ?>" alt="<?php echo htmlspecialchars((string)($file['name'] ?? '')); ?>" loading="lazy">
                                        <?php else: ?>
                                            <?php echo mediaTypeIcon((string)($file['file_type'] ?? 'document')); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="media-grid-label"><?php echo htmlspecialchars((string)($file['name'] ?? '')); ?></div>
                                    <div class="media-grid-meta"><?php echo htmlspecialchars((string)($file['category_label'] ?? 'Ohne Kategorie')); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table media-table">
                                <thead>
                                    <tr>
                                        <th class="w-1">
                                            <label class="form-check m-0">
                                                <input type="checkbox" class="form-check-input bulk-select-all" aria-label="Alle sichtbaren Medien auswählen">
                                            </label>
                                        </th>
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
                                        <?php $folderPath = (string)($folder['path'] ?? ''); ?>
                                        <tr>
                                            <td>
                                                <?php if (empty($folder['is_system'])): ?>
                                                    <label class="form-check m-0">
                                                        <input class="form-check-input bulk-row-check" type="checkbox" name="item_paths[]" form="mediaBulkForm" value="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" aria-label="Ordner <?php echo htmlspecialchars((string)($folder['name'] ?? 'Ordner'), ENT_QUOTES); ?> auswählen">
                                                    </label>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="folder-icon">📁</span></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars((string)($folder['url'] ?? $rootUrl)); ?>" class="fw-semibold text-reset media-folder-link" <?php echo !empty($folder['requires_confirmation']) ? 'data-member-folder-confirm="1" data-confirm-url="' . htmlspecialchars((string)($folder['confirm_url'] ?? ''), ENT_QUOTES) . '"' : ''; ?>>
                                                    <?php echo htmlspecialchars((string)($folder['name'] ?? '')); ?>
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
                                            <td class="text-secondary"><?php echo htmlspecialchars((string)($folder['modified_label'] ?? '—')); ?></td>
                                            <td>
                                                <?php if (empty($folder['is_system'])): ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Aktionen</button>
                                                        <div class="dropdown-menu dropdown-menu-end">
                                                            <button type="button" class="dropdown-item js-media-open-rename" data-bs-toggle="modal" data-bs-target="#mediaRenameModal" data-media-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($folder['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Ordner">Umbenennen</button>
                                                            <button type="button" class="dropdown-item js-media-open-move" data-bs-toggle="modal" data-bs-target="#mediaMoveModal" data-media-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($folder['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Ordner" data-media-target="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>">Verschieben</button>
                                                            <button type="button" class="dropdown-item text-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($folderPath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars((string)($folder['name'] ?? 'Ordner'), ENT_QUOTES); ?>" data-delete-type="Ordner">Löschen</button>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-lt">System</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php foreach ($files as $file): ?>
                                        <?php $filePath = (string)($file['path'] ?? ''); ?>
                                        <tr>
                                            <td>
                                                <label class="form-check m-0">
                                                    <input class="form-check-input bulk-row-check" type="checkbox" name="item_paths[]" form="mediaBulkForm" value="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" aria-label="Datei <?php echo htmlspecialchars((string)($file['name'] ?? 'Datei'), ENT_QUOTES); ?> auswählen">
                                                </label>
                                            </td>
                                            <td>
                                                <?php if (!empty($file['is_image'])): ?>
                                                    <img src="<?php echo htmlspecialchars((string)($file['preview_url'] ?? '')); ?>" alt="<?php echo htmlspecialchars((string)($file['name'] ?? '')); ?>" class="media-thumb" loading="lazy">
                                                <?php else: ?>
                                                    <span class="media-thumb-icon"><?php echo mediaTypeIcon((string)($file['file_type'] ?? 'document')); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars((string)($file['url'] ?? '')); ?>" target="_blank" rel="noopener noreferrer" class="fw-semibold text-reset">
                                                    <?php echo htmlspecialchars((string)($file['name'] ?? '')); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <form method="post" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="assign_category">
                                                    <input type="hidden" name="file_path" value="<?php echo htmlspecialchars((string)($file['path'] ?? '')); ?>">
                                                    <select class="form-select form-select-sm" name="category_slug" onchange="this.form.submit()">
                                                        <option value="">Ohne Kategorie</option>
                                                        <?php foreach ($categoryOptions as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>" <?php echo (($file['category'] ?? '') === ($cat['slug'] ?? '')) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars((string)($cat['name'] ?? '')); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="text-secondary"><?php echo htmlspecialchars((string)($file['formatted_size'] ?? '—')); ?></td>
                                            <td class="text-secondary"><?php echo htmlspecialchars((string)($file['modified_label'] ?? '—')); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Aktionen</button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <button type="button" class="dropdown-item js-media-open-rename" data-bs-toggle="modal" data-bs-target="#mediaRenameModal" data-media-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($file['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Datei">Umbenennen</button>
                                                        <button type="button" class="dropdown-item js-media-open-move" data-bs-toggle="modal" data-bs-target="#mediaMoveModal" data-media-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-media-name="<?php echo htmlspecialchars((string)($file['name'] ?? ''), ENT_QUOTES); ?>" data-media-kind="Datei" data-media-target="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>">Verschieben</button>
                                                        <button type="button" class="dropdown-item text-danger js-media-delete" data-delete-path="<?php echo htmlspecialchars($filePath, ENT_QUOTES); ?>" data-delete-name="<?php echo htmlspecialchars((string)($file['name'] ?? 'Datei'), ENT_QUOTES); ?>" data-delete-type="Datei">Löschen</button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                <input type="text" class="form-control" id="folderName" name="folder_name" maxlength="<?php echo (int)($constraints['folder_name_max_length'] ?? 120); ?>" required autofocus>
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
                        data-upload-url="<?php echo htmlspecialchars('/api/upload', ENT_QUOTES); ?>"
                        data-upload-path="<?php echo htmlspecialchars($path, ENT_QUOTES); ?>"
                        data-csrf-token="<?php echo htmlspecialchars($mediaActionToken, ENT_QUOTES); ?>">
                </div>
                <div class="text-secondary small">
                    Mehrfachauswahl möglich. Pro Upload sind maximal <?php echo (int)($constraints['max_upload_files'] ?? 0); ?> Dateien
                    mit zusammen höchstens <?php echo htmlspecialchars((string)($constraints['max_upload_batch_label'] ?? '—')); ?> erlaubt.
                </div>
                <div class="alert alert-info mt-3 mb-0" role="status" data-upload-status hidden></div>
                <div class="list-group list-group-flush mt-3" data-upload-results hidden></div>
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

<div class="modal modal-blur fade" id="mediaRenameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
            <input type="hidden" name="action" value="rename_item">
            <input type="hidden" name="old_path" id="mediaRenamePath">
            <div class="modal-header">
                <h5 class="modal-title">Element umbenennen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-2">Neuer Name für <span class="fw-semibold" id="mediaRenameItemLabel">Element</span></div>
                <label class="form-label" for="mediaRenameName">Name</label>
                <input class="form-control" id="mediaRenameName" name="new_name" type="text" maxlength="120" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Umbenennen</button>
            </div>
        </form>
    </div>
</div>

<div class="modal modal-blur fade" id="mediaMoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
            <input type="hidden" name="action" value="move_item">
            <input type="hidden" name="old_path" id="mediaMovePath">
            <div class="modal-header">
                <h5 class="modal-title">Element verschieben</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div class="text-secondary small mb-2">Zielordner für <span class="fw-semibold" id="mediaMoveItemLabel">Element</span></div>
                <label class="form-label" for="mediaMoveTarget">Zielordner</label>
                <select class="form-select" id="mediaMoveTarget" name="target_parent_path">
                    <?php echo renderMoveTargetOptions($moveTargets, $path); ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Verschieben</button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="media-library-config">
<?php echo json_encode($mediaLibraryConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
