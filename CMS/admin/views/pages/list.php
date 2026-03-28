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

$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$counts  = $listData['counts'];
$pages   = $listData['pages'];
$categories = $listData['categories'] ?? [];
$filter  = $listData['filter'];
$catFilter = (int)($listData['catFilter'] ?? 0);
$search  = $listData['search'];
$pagesGridConfig = is_array($pagesGridConfig ?? null) ? $pagesGridConfig : [];

$statusLabels = [
    'published' => ['Veröffentlicht', 'bg-green'],
    'draft'     => ['Entwurf',        'bg-yellow'],
    'private'   => ['Privat',         'bg-purple'],
];
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Seiten & Beiträge</div>
                <h2 class="page-title">Seiten</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="<?= $siteUrl ?>/admin/pages?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neue Seite
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">

        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        $alertMarginClass = 'mb-3';
        include __DIR__ . '/../partials/flash-alert.php';
        ?>

        <!-- KPI-Karten -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-6 col-sm-3">
                <div class="card">
                    <div class="card-body p-3 text-center">
                        <div class="text-secondary mb-1">Gesamt</div>
                        <div class="h1 mb-0"><?= $counts['total'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card">
                    <div class="card-body p-3 text-center">
                        <div class="text-secondary mb-1">Veröffentlicht</div>
                        <div class="h1 mb-0 text-green"><?= $counts['published'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card">
                    <div class="card-body p-3 text-center">
                        <div class="text-secondary mb-1">Entwürfe</div>
                        <div class="h1 mb-0 text-yellow"><?= $counts['drafts'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-sm-3">
                <div class="card">
                    <div class="card-body p-3 text-center">
                        <div class="text-secondary mb-1">Privat</div>
                        <div class="h1 mb-0 text-purple"><?= $counts['private'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar: Filter + Suche + Bulk -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alle Seiten</h3>
                <div class="card-actions">
                    <form method="get" action="<?= $siteUrl ?>/admin/pages" class="d-flex gap-2 js-pages-filter-form">
                        <select name="status" class="form-select form-select-sm js-pages-filter-submit" style="width: auto;">
                            <option value="">Alle Status</option>
                            <option value="published"<?= $filter === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
                            <option value="draft"<?= $filter === 'draft' ? ' selected' : '' ?>>Entwürfe</option>
                            <option value="private"<?= $filter === 'private' ? ' selected' : '' ?>>Privat</option>
                        </select>
                        <select name="category" class="form-select form-select-sm js-pages-filter-submit" style="width: auto;">
                            <option value="0">Alle Kategorien</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int)($category['id'] ?? 0) ?>"<?= $catFilter === (int)($category['id'] ?? 0) ? ' selected' : '' ?>><?= htmlspecialchars((string)($category['option_label'] ?? $category['name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group input-group-sm" style="width: 220px;">
                            <input type="text" name="q" class="form-control" placeholder="Suchen…"
                                   value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-icon btn-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body border-bottom py-2" id="bulkBarPages">
                <form method="post" id="bulkFormPages" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="bulk">
                    <span class="text-secondary">Markierte Seiten bearbeiten</span>
                    <select name="bulk_action" class="form-select form-select-sm w-auto">
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
                    <button type="submit" class="btn btn-sm btn-primary">Anwenden</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">
                                <input class="form-check-input" type="checkbox" id="pagesSelectAll" aria-label="Alle Seiten auswählen">
                            </th>
                            <th>Titel</th>
                            <th>Slug</th>
                            <th>Kategorie</th>
                            <th>Status</th>
                            <th>Autor</th>
                            <th>Aktualisiert</th>
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
                            $pageTitle = trim((string)($page->title ?? ''));
                            $pageSlug = trim((string)($page->slug ?? ''));
                            $pageStatus = (string)($page->status ?? 'draft');
                            [$pageStatusLabel, $pageStatusClass] = $statusLabels[$pageStatus] ?? [$pageStatus, 'bg-secondary'];
                            $pageCategory = trim((string)($page->category_name ?? ''));
                            $pageAuthor = trim((string)($page->author ?? ''));
                            $pageUpdatedAt = trim((string)($page->updated_at ?? $page->created_at ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <input class="form-check-input" type="checkbox" name="ids[]" value="<?= $pageId ?>" form="bulkFormPages">
                                </td>
                                <td>
                                    <a href="<?= $siteUrl ?>/admin/pages?action=edit&id=<?= $pageId ?>" class="text-reset fw-medium">
                                        <?= htmlspecialchars($pageTitle !== '' ? $pageTitle : 'Ohne Titel') ?>
                                    </a>
                                </td>
                                <td class="text-secondary">/<?= htmlspecialchars($pageSlug) ?></td>
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
                                <td class="text-secondary"><?= htmlspecialchars($pageUpdatedAt !== '' ? $pageUpdatedAt : '–') ?></td>
                                <td>
                                    <a href="<?= $siteUrl ?>/admin/pages?action=edit&id=<?= $pageId ?>" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
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
    var bulkAction = document.querySelector('#bulkFormPages [name="bulk_action"]');
    var bulkCategory = document.getElementById('bulkCategoryPages');

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
    }

    if (!selectAll) {
        syncBulkCategoryVisibility();
        return;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('input[name="ids[]"][form="bulkFormPages"]').forEach(function (checkbox) {
            checkbox.checked = selectAll.checked;
        });
    });

    if (bulkAction) {
        bulkAction.addEventListener('change', syncBulkCategoryVisibility);
    }

    syncBulkCategoryVisibility();
});
</script>

