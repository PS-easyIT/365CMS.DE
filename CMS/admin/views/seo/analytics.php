<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl      = defined('SITE_URL') ? SITE_URL : '';
$stats        = $data['stats'] ?? [];
$topPages     = $data['top_pages'] ?? [];
$daily        = $data['daily'] ?? [];
$referrers    = $data['referrers'] ?? [];
$contentStats = $data['content_stats'] ?? [];
$hasTable     = $data['has_table'] ?? false;
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO & Performance</div>
                <h2 class="page-title">Analytics Übersicht</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <?php if (!$hasTable): ?>
            <div class="alert alert-info">
                <div class="d-flex">
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>
                    </div>
                    <div>
                        <h4 class="alert-title">Page-View-Tracking nicht aktiv</h4>
                        <div class="text-secondary">Die Tabelle <code>page_views</code> existiert noch nicht. Aktivieren Sie das interne Tracking, um Besucherstatistiken zu erfassen.</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Traffic KPIs -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Heute</div>
                        <div class="h1 mb-0"><?= number_format($stats['today']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Gestern</div>
                        <div class="h1 mb-0"><?= number_format($stats['yesterday']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Letzte 7 Tage</div>
                        <div class="h1 mb-0"><?= number_format($stats['week']) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Letzte 30 Tage</div>
                        <div class="h1 mb-0"><?= number_format($stats['month']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Top-Seiten -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Top-Seiten (30 Tage)</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr><th>URL</th><th class="text-end">Aufrufe</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topPages)): ?>
                                    <tr><td colspan="2" class="text-center text-secondary py-3">Keine Daten vorhanden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topPages as $page): ?>
                                        <tr>
                                            <td><code class="small"><?= htmlspecialchars($page['page_slug']) ?></code></td>
                                            <td class="text-end"><strong><?= number_format((int)$page['views']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inhalts-Statistiken & Referrer -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Inhalte</h3></div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">Seiten</div><div class="datagrid-content"><?= $contentStats['pages'] ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Beiträge</div><div class="datagrid-content"><?= $contentStats['posts'] ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Kommentare</div><div class="datagrid-content"><?= $contentStats['comments'] ?></div></div>
                            <div class="datagrid-item"><div class="datagrid-title">Benutzer</div><div class="datagrid-content"><?= $contentStats['users'] ?></div></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Top-Referrer (30 Tage)</h3></div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($referrers)): ?>
                            <div class="list-group-item text-secondary text-center small">Keine Daten</div>
                        <?php else: ?>
                            <?php foreach ($referrers as $ref): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-truncate small"><?= htmlspecialchars($ref['referrer']) ?></span>
                                    <span class="badge bg-blue-lt"><?= (int)$ref['cnt'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($data['ga_id'])): ?>
            <div class="card mt-4">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="badge bg-green badge-pill p-2">GA</span>
                    <div>
                        <div class="fw-bold">Google Analytics aktiv</div>
                        <div class="text-secondary small">Tracking-ID: <?= htmlspecialchars($data['ga_id']) ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
