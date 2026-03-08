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
$filter  = $listData['filter'];
$search  = $listData['search'];

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

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

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
                    <form method="get" action="<?= $siteUrl ?>/admin/pages" class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <option value="">Alle Status</option>
                            <option value="published"<?= $filter === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
                            <option value="draft"<?= $filter === 'draft' ? ' selected' : '' ?>>Entwürfe</option>
                            <option value="private"<?= $filter === 'private' ? ' selected' : '' ?>>Privat</option>
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

            <div class="card-body">
                <div id="pagesGrid"></div>
            </div>
        </div>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

