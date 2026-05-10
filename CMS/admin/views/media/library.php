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
$usageFilter = $data['usage_filter'] ?? 'all';
$fileTypeFilter = (string)($data['file_type_filter'] ?? 'all');
$extensionFilter = (string)($data['extension_filter'] ?? '');
$sizeFilter = (string)($data['size_filter'] ?? 'all');
$modifiedFilter = (string)($data['modified_filter'] ?? 'all');
$confirmMember = !empty($data['confirm_member']);
$memberFolderConfirmMessage = (string)($data['member_folder_confirm_message'] ?? 'Der Member-Bereich enthält sensible Uploads. Möchten Sie den Ordner wirklich öffnen?');
$breadcrumbs = is_array($data['breadcrumbs'] ?? null) ? $data['breadcrumbs'] : [];
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$categoryOptions = is_array($data['category_options'] ?? null) ? $data['category_options'] : [];
$usageFilterOptions = is_array($data['usage_filter_options'] ?? null) ? $data['usage_filter_options'] : [];
$fileTypeFilterOptions = is_array($data['file_type_filter_options'] ?? null) ? $data['file_type_filter_options'] : [];
$extensionFilterOptions = is_array($data['extension_filter_options'] ?? null) ? $data['extension_filter_options'] : [];
$sizeFilterOptions = is_array($data['size_filter_options'] ?? null) ? $data['size_filter_options'] : [];
$modifiedFilterOptions = is_array($data['modified_filter_options'] ?? null) ? $data['modified_filter_options'] : [];
$filterState = is_array($data['filter_state'] ?? null) ? $data['filter_state'] : [];
$baseUrl = (string)($data['base_url'] ?? '/admin/media');
$listUrl = (string)($data['list_url'] ?? $baseUrl);
$gridUrl = (string)($data['grid_url'] ?? $baseUrl);
$rootUrl = (string)($data['root_url'] ?? $baseUrl);
$resetFilterUrl = (string)($data['reset_filter_url'] ?? $baseUrl);
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
$hasAdvancedMediaFilters = $fileTypeFilter !== 'all' || $extensionFilter !== '' || $sizeFilter !== 'all' || $modifiedFilter !== 'all';

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

function renderMediaUsageBadges(array $usageSummary): string {
    $badges = [];
    $postCount = (int)($usageSummary['post_count'] ?? 0);
    $pageCount = (int)($usageSummary['page_count'] ?? 0);
    $featuredCount = (int)($usageSummary['featured_count'] ?? 0);
    $contentCount = (int)($usageSummary['content_count'] ?? 0);
    $contentEnCount = (int)($usageSummary['content_en_count'] ?? 0);

    if ($postCount > 0) {
        $badges[] = '<span class="badge bg-blue-lt">' . $postCount . ' Beitrag' . ($postCount === 1 ? '' : 'e') . '</span>';
    }
    if ($pageCount > 0) {
        $badges[] = '<span class="badge bg-indigo-lt">' . $pageCount . ' Seite' . ($pageCount === 1 ? '' : 'n') . '</span>';
    }
    if ($featuredCount > 0) {
        $badges[] = '<span class="badge bg-green-lt">' . $featuredCount . ' Bildreferenz' . ($featuredCount === 1 ? '' : 'en') . '</span>';
    }
    if (($contentCount + $contentEnCount) > 0) {
        $badges[] = '<span class="badge bg-purple-lt">' . ($contentCount + $contentEnCount) . ' Inhalt' . (($contentCount + $contentEnCount) === 1 ? '' : 'e') . '</span>';
    }

    return $badges !== [] ? '<div class="media-usage-badges">' . implode('', $badges) . '</div>' : '';
}

function normalizeMediaUsageEditUrl(string $editUrl): string {
    $editUrl = trim($editUrl);

    if ($editUrl === '' || preg_match('/[\x00-\x1F\x7F]/', $editUrl) === 1) {
        return '';
    }

    return preg_match('#^/admin/(?:posts|pages)\?action=edit&id=\d+$#', $editUrl) === 1 ? $editUrl : '';
}

function renderMediaUsageLink(array $usageItem): string {
    $editUrl = normalizeMediaUsageEditUrl((string)($usageItem['edit_url'] ?? ''));
    $typeLabel = (string)($usageItem['content_type_label'] ?? 'Inhalt');
    $title = (string)($usageItem['title'] ?? 'Ohne Titel');
    $fieldLabel = (string)($usageItem['field_label'] ?? 'Verwendung');

    $inner = '<span class="badge bg-blue-lt">' . htmlspecialchars($typeLabel) . '</span>';
    $inner .= '<span class="fw-medium">' . htmlspecialchars($title) . '</span>';
    $inner .= '<span class="text-secondary">(' . htmlspecialchars($fieldLabel) . ')</span>';

    if ($editUrl === '') {
        return '<span class="media-usage-link">' . $inner . '</span>';
    }

    return '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES) . '" class="media-usage-link text-reset text-decoration-none">' . $inner . '</a>';
}

function renderMediaUsageList(array $usageItems, array $usageSummary = [], int $maxVisible = 3): string {
    if ($usageItems === []) {
        return '<span class="text-secondary">Nicht eingebunden</span>';
    }

    $html = '<div class="media-usage-list">';
    $html .= renderMediaUsageBadges($usageSummary);
    $visibleItems = array_slice($usageItems, 0, $maxVisible);

    foreach ($visibleItems as $usageItem) {
        if (!is_array($usageItem)) {
            continue;
        }

        $html .= renderMediaUsageLink($usageItem);
    }

    $remainingItems = array_slice($usageItems, $maxVisible);
    if ($remainingItems !== []) {
        $remaining = count($remainingItems);
        $html .= '<details class="media-usage-more">';
        $html .= '<summary>+ ' . $remaining . ' weitere Referenz' . ($remaining === 1 ? '' : 'en') . '</summary>';
        $html .= '<div class="media-usage-more-list">';
        foreach ($remainingItems as $usageItem) {
            if (is_array($usageItem)) {
                $html .= renderMediaUsageLink($usageItem);
            }
        }
        $html .= '</div></details>';
    }

    $html .= '</div>';

    return $html;
}

function renderMediaUsageSummary(array $usageItems, int $usageCount): string {
    if ($usageCount <= 0 || $usageItems === []) {
        return '<span class="text-secondary">Nicht eingebunden</span>';
    }

    $firstUsage = is_array($usageItems[0] ?? null) ? $usageItems[0] : [];
    $summary = $usageCount === 1 ? '1 Verwendung' : $usageCount . ' Verwendungen';
    $label = trim((string)($firstUsage['content_type_label'] ?? '') . ' ' . (string)($firstUsage['title'] ?? ''));

    $html = '<span class="text-success">' . htmlspecialchars($summary) . '</span>';
    if ($label !== '') {
        $html .= '<span class="text-secondary"> · ' . htmlspecialchars($label) . '</span>';
    }

    return $html;
}

function renderMediaUsageSummaryBlock(array $file): string {
    $usageItems = (array)($file['usage_items'] ?? []);
    $usageCount = (int)($file['usage_count'] ?? 0);
    $usageSummary = is_array($file['usage_summary'] ?? null) ? $file['usage_summary'] : [];

    $html = '<div class="media-usage-summary-block">';
    $html .= renderMediaUsageSummary($usageItems, $usageCount);
    $html .= renderMediaUsageBadges($usageSummary);
    $html .= '</div>';

    return $html;
}

function renderMediaDuplicateSummary(array $file, bool $compact = false): string {
    $duplicateCount = (int)($file['duplicate_count'] ?? 0);
    $duplicatePaths = is_array($file['duplicate_paths'] ?? null) ? $file['duplicate_paths'] : [];

    if ($duplicateCount < 2 || $duplicatePaths === []) {
        return '';
    }

    $shortHash = (string)($file['duplicate_short_hash'] ?? '');
    $label = $duplicateCount === 2 ? '1 Duplikat' : ($duplicateCount - 1) . ' Duplikate';
    $html = '<div class="small mt-1">';
    $html .= '<span class="badge bg-warning-lt text-warning">' . htmlspecialchars($label) . '</span>';

    if ($shortHash !== '') {
        $html .= '<span class="text-secondary ms-1">Hash ' . htmlspecialchars($shortHash) . '</span>';
    }

    if (!$compact) {
        $visiblePaths = array_slice(array_map('strval', $duplicatePaths), 0, 3);
        $html .= '<div class="text-secondary mt-1">Auch vorhanden: ' . htmlspecialchars(implode(', ', $visiblePaths));
        $remaining = count($duplicatePaths) - count($visiblePaths);
        if ($remaining > 0) {
            $html .= ' +' . $remaining . ' weitere';
        }
        $html .= '</div>';
    }

    $html .= '</div>';

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
            <div class="col-sm-6 col-lg">
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
            <div class="col-sm-6 col-lg">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-green text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 14a3.5 3.5 0 0 0 5 0l4 -4a3.5 3.5 0 0 0 -5 -5l-.5 .5"/><path d="M14 10a3.5 3.5 0 0 0 -5 0l-4 4a3.5 3.5 0 0 0 5 5l.5 -.5"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($stats['used_file_count'] ?? 0); ?> / <?php echo (int)($stats['visible_file_count'] ?? count($files)); ?></div>
                                <div class="text-secondary">sichtbar eingebunden</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg">
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
            <div class="col-sm-6 col-lg">
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
            <div class="col-sm-6 col-lg">
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
                                <select class="form-select form-select-sm media-filter-category" name="category" data-media-auto-submit-select="1">
                                    <option value="">Alle Kategorien</option>
                                    <?php foreach ($categoryOptions as $cat): ?>
                                        <option value="<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>" <?php echo $category === ($cat['slug'] ?? '') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($cat['name'] ?? '')); ?> (<?php echo (int)($cat['count'] ?? 0); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm media-filter-category" name="usage_filter" data-media-auto-submit-select="1">
                                    <?php foreach ($usageFilterOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars((string)($option['value'] ?? 'all')); ?>" <?php echo $usageFilter === ($option['value'] ?? 'all') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($option['label'] ?? 'Alle Medien')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm media-filter-category" name="file_type" data-media-auto-submit-select="1" aria-label="Dateityp filtern">
                                    <?php foreach ($fileTypeFilterOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars((string)($option['value'] ?? 'all')); ?>" <?php echo $fileTypeFilter === ($option['value'] ?? 'all') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($option['label'] ?? 'Alle Dateitypen')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm media-filter-category" name="extension" data-media-auto-submit-select="1" aria-label="Dateiendung filtern">
                                    <option value="">Alle Endungen</option>
                                    <?php foreach ($extensionFilterOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars((string)($option['value'] ?? ''), ENT_QUOTES); ?>" <?php echo $extensionFilter === ($option['value'] ?? '') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($option['label'] ?? '')); ?> (<?php echo (int)($option['count'] ?? 0); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm media-filter-category" name="size_filter" data-media-auto-submit-select="1" aria-label="Dateigröße filtern">
                                    <?php foreach ($sizeFilterOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars((string)($option['value'] ?? 'all')); ?>" <?php echo $sizeFilter === ($option['value'] ?? 'all') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($option['label'] ?? 'Alle Größen')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select class="form-select form-select-sm media-filter-category" name="modified_filter" data-media-auto-submit-select="1" aria-label="Änderungszeitraum filtern">
                                    <?php foreach ($modifiedFilterOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars((string)($option['value'] ?? 'all')); ?>" <?php echo $modifiedFilter === ($option['value'] ?? 'all') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)($option['label'] ?? 'Alle Änderungsdaten')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="input-group input-group-sm media-filter-search">
                                    <input type="search" class="form-control" name="q" placeholder="Dateien suchen …" value="<?php echo htmlspecialchars($search); ?>" maxlength="<?php echo (int)($constraints['search_max_length'] ?? 120); ?>">
                                    <button type="submit" class="btn btn-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                                    </button>
                                </div>
                                <?php if ($hasAdvancedMediaFilters || $category !== '' || $usageFilter !== 'all' || $search !== ''): ?>
                                    <a href="<?php echo htmlspecialchars($resetFilterUrl, ENT_QUOTES); ?>" class="btn btn-sm btn-outline-secondary">Filter zurücksetzen</a>
                                <?php endif; ?>
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
                    <?php if ($hasAdvancedMediaFilters): ?>
                        <div class="alert alert-info mb-0 mt-3" role="status">
                            <strong>Erweiterte Filter aktiv:</strong>
                            Dateityp, Endung, Größe und Änderungszeitraum werden serverseitig per Allowlist geprüft. Ordner bleiben sichtbar, damit die Navigation auch bei leeren Trefferlisten erhalten bleibt.
                        </div>
                    <?php endif; ?>
                    <?php if ((int)($stats['duplicate_file_count'] ?? 0) > 0): ?>
                        <div class="alert alert-warning mb-0 mt-3" role="status">
                            <strong>Duplikat-Erkennung:</strong>
                            <?php echo (int)($stats['duplicate_file_count'] ?? 0); ?> sichtbare Datei(en)
                            gehören zu <?php echo (int)($stats['duplicate_group_count'] ?? 0); ?> identischen Hash-Gruppe(n).
                            Die Erkennung ist read-only; Löschen oder Verschieben bleibt eine bewusste Admin-Aktion.
                        </div>
                    <?php endif; ?>
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
                                    <button type="submit" class="btn btn-primary" aria-disabled="true" disabled>Diesen Medien-Batch ausführen</button>
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
                                    <div class="media-grid-meta small"><?php echo renderMediaUsageSummaryBlock($file); ?></div>
                                    <?php echo renderMediaDuplicateSummary($file, true); ?>
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
                                        <th>Eingebunden in</th>
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
                                            <td><span class="text-secondary">—</span></td>
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
                                                <?php echo renderMediaDuplicateSummary($file); ?>
                                            </td>
                                            <td>
                                                <form method="post" class="d-flex gap-2 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="assign_category">
                                                    <input type="hidden" name="file_path" value="<?php echo htmlspecialchars((string)($file['path'] ?? '')); ?>">
                                                    <select class="form-select form-select-sm" name="category_slug" data-media-auto-submit-select="1">
                                                        <option value="">Ohne Kategorie</option>
                                                        <?php foreach ($categoryOptions as $cat): ?>
                                                            <option value="<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>" <?php echo (($file['category'] ?? '') === ($cat['slug'] ?? '')) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars((string)($cat['name'] ?? '')); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td><?php echo renderMediaUsageList((array)($file['usage_items'] ?? []), is_array($file['usage_summary'] ?? null) ? $file['usage_summary'] : []); ?></td>
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
                <div class="alert alert-info mt-3 mb-0" role="status" aria-live="polite" data-upload-status hidden></div>
                <div class="list-group list-group-flush mt-3" aria-live="polite" data-upload-results hidden></div>
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
