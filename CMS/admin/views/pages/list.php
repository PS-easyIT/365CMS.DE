<?php
declare(strict_types=1);

/**
 * Pages List View – Seitenübersicht
 *
 * Erwartet:
 *   $listData['pages']   – array  Seitenliste
 *   $listData['counts']  – array  Status-Counts
 *   $listData['filter']  – string Aktiver Filter
 *   $listData['search']  – string Suchbegriff
 *   $csrfToken           – string CSRF-Token
 *   $alert               – array|null  Erfolgs-/Fehlermeldung
 *
 * @package CMSv2\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

$pagesAdminBaseUrl = '/admin/pages';
$counts  = is_array($listData['counts'] ?? null) ? $listData['counts'] : [];
$pages   = is_array($listData['pages'] ?? null) ? $listData['pages'] : [];
$categories = $listData['categories'] ?? [];
$filter  = (string)($listData['filter'] ?? '');
$catFilter = (int)($listData['catFilter'] ?? 0);
$search  = (string)($listData['search'] ?? '');

$statusLabels = [
    'published' => ['Veröffentlicht', 'bg-green-lt text-green'],
    'draft'     => ['Entwurf',        'bg-yellow-lt text-yellow'],
    'private'   => ['Privat',         'bg-purple-lt text-purple'],
];

$pagesHasActiveFilters = ($filter !== '') || ($catFilter > 0) || ($search !== '');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="content-listing-header">
            <div>
                <div class="page-pretitle">Seiten & Beiträge</div>
                <h2 class="page-title mb-1">Seiten</h2>
                <div class="content-listing-header__meta">
                    <span><?php echo (int)($counts['total'] ?? 0); ?> Einträge</span>
                    <span>Veröffentlicht: <?php echo (int)($counts['published'] ?? 0); ?></span>
                    <span>Entwurf: <?php echo (int)($counts['drafts'] ?? 0); ?></span>
                    <span>Privat: <?php echo (int)($counts['private'] ?? 0); ?></span>
                </div>
            </div>
            <a href="<?= $pagesAdminBaseUrl ?>?action=edit" class="btn btn-primary d-print-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                Neue Seite
            </a>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        $alertMarginClass = 'mb-3';
        include __DIR__ . '/../partials/flash-alert.php';
        ?>

        <div class="card content-listing-card">
            <div class="card-header content-listing-toolbar">
                <div class="content-listing-toolbar__label">Filter &amp; Suche</div>
                <div class="content-listing-filters">
                    <div class="content-listing-filters__group">
                        <label for="pagesStatusFilter" class="form-label mb-0 small text-secondary">Status</label>
                        <select id="pagesStatusFilter" name="status" form="pagesFilterForm" class="form-select form-select-sm js-pages-filter-submit">
                            <option value="">Alle Status</option>
                            <option value="published"<?= $filter === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
                            <option value="draft"<?= $filter === 'draft' ? ' selected' : '' ?>>Entwurf</option>
                            <option value="private"<?= $filter === 'private' ? ' selected' : '' ?>>Privat</option>
                        </select>
                    </div>
                    <div class="content-listing-filters__group">
                        <label for="pagesCategoryFilter" class="form-label mb-0 small text-secondary">Kategorie</label>
                        <select id="pagesCategoryFilter" name="category" form="pagesFilterForm" class="form-select form-select-sm js-pages-filter-submit">
                            <option value="0">Alle Kategorien</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)($category['id'] ?? 0) ?>"<?= $catFilter === (int)($category['id'] ?? 0) ? ' selected' : '' ?>><?= htmlspecialchars((string)($category['option_label'] ?? $category['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="content-listing-filters__search">
                        <label for="pagesSearchInput" class="form-label mb-0 small text-secondary">Suche</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/></svg>
                            </span>
                            <input id="pagesSearchInput" type="text" name="q" form="pagesFilterForm" class="form-control form-control-sm" placeholder="Titel, Slug oder Autor" value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" form="pagesFilterForm" class="btn btn-outline-secondary">Suchen</button>
                        </div>
                    </div>
                    <div class="content-listing-filters__actions">
                        <?php if ($pagesHasActiveFilters): ?>
                            <a href="<?= $pagesAdminBaseUrl ?>" class="btn btn-sm btn-outline-secondary">Filter zurücksetzen</a>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="get" action="<?= $pagesAdminBaseUrl ?>" id="pagesFilterForm" class="js-pages-filter-form"></form>
            </div>

            <div class="card-body border-bottom py-2 d-none content-listing-bulkbar" id="bulkBarPages">
                <form method="post" id="bulkFormPages" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="bulk">
                    <span class="text-secondary"><strong id="selectedCountPages">0</strong> ausgewählt</span>
                    <label for="bulkActionPages" class="form-label mb-0 small text-secondary">Aktion</label>
                    <select name="bulk_action" id="bulkActionPages" class="form-select form-select-sm w-auto">
                        <option value="">Aktion wählen…</option>
                        <option value="publish">Veröffentlichen</option>
                        <option value="draft">Als Entwurf setzen</option>
                        <option value="set_category">Kategorie setzen</option>
                        <option value="clear_category">Kategorie entfernen</option>
                        <option value="delete">Löschen</option>
                    </select>
                    <select name="bulk_category_id" id="bulkCategoryPages" class="form-select form-select-sm w-auto d-none">
                        <option value="0">Kategorie wählen…</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)($category['id'] ?? 0) ?>"><?= htmlspecialchars((string)($category['option_label'] ?? $category['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" id="bulkSubmitPages" class="btn btn-sm btn-primary" disabled>Anwenden</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table content-listing-table">
                    <thead>
                        <tr>
                            <th class="w-1">
                                <input class="form-check-input" type="checkbox" id="pagesSelectAll" aria-label="Alle Seiten auswählen">
                            </th>
                            <th class="content-listing-table__title-col">Titel</th>
                            <th>Slug</th>
                            <th class="text-nowrap">Kategorie</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-nowrap">Autor</th>
                            <th class="text-nowrap">Aktualisiert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pages)): ?>
                        <?php
                        $emptyStateColspan = 8;
                        $emptyStateMessage = 'Keine Seiten gefunden.';
                        $emptyStateSubtitle = 'Prüfen Sie Filter oder Suche – die serverseitige Liste liefert aktuell keine Einträge.';
                        $emptyStateIcon = 'file-text';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($pages as $page): ?>
                            <?php
                            $pageId = (int)($page->id ?? 0);
                            $pageTitle = trim((string)($page->display_title ?? $page->title ?? ''));
                            $pageSlug = trim((string)($page->display_slug ?? $page->slug ?? ''));
                            $pageSlugEn = trim((string)($page->slug_en ?? ''));
                            $pageStatus = (string)($page->status ?? 'draft');
                            [$pageStatusLabel, $pageStatusClass] = $statusLabels[$pageStatus] ?? [$pageStatus, 'bg-secondary-lt text-secondary'];
                            $pageCategory = trim((string)($page->category_name ?? ''));
                            $pageAuthor = trim((string)($page->author ?? ''));
                            $pageUpdatedAt = trim((string)($page->updated_at ?? $page->created_at ?? ''));
                            $pageUpdatedAtLabel = $pageUpdatedAt;
                            if ($pageUpdatedAt !== '') {
                                $pageUpdatedTimestamp = strtotime($pageUpdatedAt);
                                if ($pageUpdatedTimestamp !== false) {
                                    $pageUpdatedAtLabel = date('d.m.Y H:i', $pageUpdatedTimestamp);
                                }
                            }
                            $pageHasEnglishVariant = !empty($page->has_english_variant);
                            $pageIsEnglishOnly = !empty($page->is_english_only);
                            ?>
                            <tr class="content-listing-table__row">
                                <td>
                                    <input class="form-check-input" type="checkbox" name="ids[]" value="<?= $pageId ?>" form="bulkFormPages">
                                </td>
                                <td class="content-listing-table__title-cell">
                                    <a href="<?= $pagesAdminBaseUrl ?>?action=edit&id=<?= $pageId ?>" class="text-reset fw-medium">
                                        <?= htmlspecialchars($pageTitle !== '' ? $pageTitle : 'Ohne Titel') ?>
                                    </a>
                                    <?php if ($pageIsEnglishOnly): ?>
                                        <span class="badge bg-blue-lt ms-2">EN only</span>
                                    <?php elseif ($pageHasEnglishVariant): ?>
                                        <span class="badge bg-secondary-lt ms-2">EN</span>
                                    <?php endif; ?>
                                    <div class="text-secondary small mt-1">ID: <?= $pageId ?></div>
                                </td>
                                <td class="text-secondary content-listing-table__slug-cell">
                                    /<?= htmlspecialchars($pageSlug) ?>
                                    <?php if ($pageSlugEn !== '' && $pageSlugEn !== $pageSlug): ?>
                                        <div class="small mt-1">/en/<?= htmlspecialchars($pageSlugEn) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($pageCategory !== ''): ?>
                                        <span class="badge bg-azure-lt"><?= htmlspecialchars($pageCategory) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= htmlspecialchars($pageStatusClass) ?>"><?= htmlspecialchars($pageStatusLabel) ?></span>
                                </td>
                                <td><?= htmlspecialchars($pageAuthor !== '' ? $pageAuthor : '–') ?></td>
                                <td class="text-secondary"><?= htmlspecialchars($pageUpdatedAtLabel !== '' ? $pageUpdatedAtLabel : '–') ?></td>
                                <td class="table-actions content-listing-table__actions-cell">
                                    <div class="table-row-actions table-row-actions--icons">
                                        <a href="<?= $pagesAdminBaseUrl ?>?action=edit&id=<?= $pageId ?>" class="btn btn-outline-primary btn-sm btn-icon" aria-label="Seite bearbeiten" title="Bearbeiten">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v10H7z" opacity="0"/><path d="M16.474 5.408a2.077 2.077 0 1 1 2.937 2.937l-9.19 9.19a6 6 0 0 1 -2.52 1.51l-2.093 .698l.698 -2.093a6 6 0 0 1 1.51 -2.52z"/><path d="M14.474 7.408l2.118 2.118"/></svg>
                                        </a>
                                        <form method="post" action="<?= $pagesAdminBaseUrl ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $pageId ?>">
                                            <button type="button" class="btn btn-ghost-danger btn-sm btn-icon" aria-label="Seite löschen" title="Löschen" onclick="cmsConfirm({title:'Seite löschen?',message:'Diese Seite wird unwiderruflich gelöscht.',confirmText:'Löschen',confirmClass:'btn-danger',onConfirm:()=>cmsSubmitFormSafely(this.closest('form'))})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7l1 -3h4l1 3"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('pagesSelectAll');
    var bulkForm = document.getElementById('bulkFormPages');
    var bulkBar = document.getElementById('bulkBarPages');
    var bulkAction = document.querySelector('#bulkFormPages [name="bulk_action"]');
    var bulkCategory = document.getElementById('bulkCategoryPages');
    var selectedCount = document.getElementById('selectedCountPages');
    var bulkSubmit = document.getElementById('bulkSubmitPages');
    var filterForm = document.getElementById('pagesFilterForm');
    var filterInputs = Array.prototype.slice.call(document.querySelectorAll('.js-pages-filter-submit'));

    filterInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            if (filterForm) {
                cmsSubmitFormSafely(filterForm);
            }
        });
    });

    function bulkSubmitMeta(action) {
        switch (action) {
            case 'publish':
                return { text: 'Seiten veröffentlichen', className: 'btn btn-sm btn-primary' };
            case 'draft':
                return { text: 'Als Entwurf setzen', className: 'btn btn-sm btn-warning' };
            case 'set_category':
                return { text: 'Kategorie setzen', className: 'btn btn-sm btn-primary' };
            case 'clear_category':
                return { text: 'Kategorie entfernen', className: 'btn btn-sm btn-outline-secondary' };
            case 'delete':
                return { text: 'Seiten löschen', className: 'btn btn-sm btn-danger' };
            default:
                return { text: 'Anwenden', className: 'btn btn-sm btn-primary' };
        }
    }

    function getRowCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="ids[]"][form="bulkFormPages"]'));
    }

    function updateSelectionState() {
        var rowCheckboxes = getRowCheckboxes();
        var checkedCount = rowCheckboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        if (selectedCount) {
            selectedCount.textContent = String(checkedCount);
        }

        if (bulkBar) {
            bulkBar.classList.toggle('d-none', checkedCount === 0);
        }

        syncBulkSubmitState(checkedCount);

        if (!selectAll) {
            return;
        }

        selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }

    function syncBulkCategoryVisibility() {
        if (!bulkAction || !bulkCategory) {
            return;
        }

        var requiresCategory = bulkAction.value === 'set_category';
        bulkCategory.classList.toggle('d-none', !requiresCategory);
        bulkCategory.required = requiresCategory;

        if (!requiresCategory) {
            bulkCategory.value = '0';
        }

        syncBulkSubmitState();
    }

    function syncBulkSubmitState(selectedCountOverride) {
        if (!bulkSubmit || !bulkAction) {
            return;
        }

        var selectedItems = typeof selectedCountOverride === 'number'
            ? selectedCountOverride
            : getRowCheckboxes().filter(function (checkbox) { return checkbox.checked; }).length;
        var action = bulkAction.value || '';
        var requiresCategory = action === 'set_category';
        var categoryReady = !requiresCategory || !bulkCategory || bulkCategory.value !== '0';
        var meta = bulkSubmitMeta(action);

        bulkSubmit.textContent = meta.text;
        bulkSubmit.className = meta.className;
        bulkSubmit.disabled = selectedItems === 0 || action === '' || !categoryReady;
    }

    if (!selectAll) {
        updateSelectionState();
        syncBulkCategoryVisibility();
        return;
    }

    selectAll.addEventListener('change', function () {
        getRowCheckboxes().forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
        updateSelectionState();
    });

    getRowCheckboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', updateSelectionState);
    });

    if (bulkAction) {
        bulkAction.addEventListener('change', syncBulkCategoryVisibility);
    }

    if (bulkCategory) {
        bulkCategory.addEventListener('change', function () {
            syncBulkSubmitState();
        });
    }

    if (bulkForm) {
        bulkForm.addEventListener('submit', function (event) {
            var selectedCheckboxes = getRowCheckboxes().filter(function (checkbox) {
                return checkbox.checked;
            });

            if (!bulkAction || bulkAction.value === '') {
                event.preventDefault();
                if (bulkAction) {
                    bulkAction.focus();
                }
                return;
            }

            if (selectedCheckboxes.length === 0) {
                event.preventDefault();
                return;
            }

            if (bulkAction.value === 'set_category' && bulkCategory && bulkCategory.value === '0') {
                event.preventDefault();
                bulkCategory.focus();
                return;
            }

            if (bulkAction.value === 'delete') {
                var deleteMessage = selectedCheckboxes.length === 1
                    ? 'Soll die ausgewählte Seite wirklich gelöscht werden?'
                    : 'Sollen die ' + selectedCheckboxes.length + ' ausgewählten Seiten wirklich gelöscht werden?';

                if (!window.confirm(deleteMessage)) {
                    event.preventDefault();
                }
            }
        });
    }

    syncBulkCategoryVisibility();
    updateSelectionState();
});
</script>

