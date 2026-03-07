<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$analytics = $data['analytics'] ?? [];
$stats = $analytics['visitor_stats'] ?? [];
$topPages = $analytics['top_pages'] ?? [];
$daily = $analytics['daily_traffic'] ?? [];
$referrers = $analytics['referrers'] ?? [];
$backlinks = $analytics['backlinks'] ?? [];
$internalLinkSuggestions = $analytics['internal_link_suggestions'] ?? [];
$coreWebVitals = $analytics['core_web_vitals'] ?? [];
$trackingSettings = $analytics['tracking_settings'] ?? [];
$hasTable = $analytics['has_page_views'] ?? false;
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
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

        <?php require __DIR__ . '/subnav.php'; ?>

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

        <div class="row row-deck row-cards mb-4">
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Seitenaufrufe 30 Tage</div>
                        <div class="h1 mb-0"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Unique Visitors</div>
                        <div class="h1 mb-0"><?= number_format((int)($stats['unique'] ?? 0)) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Absprungrate</div>
                        <div class="h1 mb-0"><?= htmlspecialchars((string)($stats['bounce_rate'] ?? '0%')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Ø Sessiondauer</div>
                        <div class="h1 mb-0"><?= htmlspecialchars((string)($stats['avg_session_duration'] ?? '0s')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Ranking-/Traffic-Verlauf (30 Tage)</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Tag</th><th class="text-end">Views</th></tr></thead>
                            <tbody>
                                <?php if (empty($daily)): ?>
                                    <tr><td colspan="2" class="text-center text-secondary py-3">Keine Trenddaten vorhanden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($daily as $row): ?>
                                        <tr><td><?= htmlspecialchars((string)($row['day'] ?? '')) ?></td><td class="text-end"><strong><?= number_format((int)($row['views'] ?? 0)) ?></strong></td></tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card mt-4">
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
                                                <td><code class="small"><?= htmlspecialchars((string)$page['page_slug']) ?></code></td>
                                                <td class="text-end"><strong><?= number_format((int)$page['views']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Core Web Vitals (Schätzung)</h3></div>
                    <div class="card-body">
                        <div class="datagrid">
                            <div class="datagrid-item"><div class="datagrid-title">TTFB</div><div class="datagrid-content"><?= (int)($coreWebVitals['ttfb_ms'] ?? 0) ?> ms</div></div>
                            <div class="datagrid-item"><div class="datagrid-title">LCP*</div><div class="datagrid-content"><?= htmlspecialchars((string)($coreWebVitals['lcp_estimate'] ?? '—')) ?> s</div></div>
                            <div class="datagrid-item"><div class="datagrid-title">INP*</div><div class="datagrid-content"><?= htmlspecialchars((string)($coreWebVitals['inp_estimate'] ?? '—')) ?> ms</div></div>
                            <div class="datagrid-item"><div class="datagrid-title">CLS*</div><div class="datagrid-content"><?= htmlspecialchars((string)($coreWebVitals['cls_estimate'] ?? '—')) ?></div></div>
                        </div>
                        <div class="form-hint mt-2"><?= htmlspecialchars((string)($coreWebVitals['note'] ?? '')) ?></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Backlink-/Referrer-Monitoring</h3></div>
                    <div class="list-group list-group-flush">
                        <?php if (empty($backlinks)): ?>
                            <div class="list-group-item text-secondary text-center small">Keine Daten</div>
                        <?php else: ?>
                            <?php foreach ($backlinks as $ref): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-truncate small"><?= htmlspecialchars((string)$ref['domain']) ?></span>
                                    <span class="badge bg-blue-lt"><?= (int)$ref['hits'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Tracking & Suchmaschinen</h3></div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_analytics_settings">
                            <div class="col-12"><label class="form-label">Google Search Console Property</label><input class="form-control" type="text" name="gsc_property" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_gsc_property'] ?? '')) ?>" placeholder="sc-domain:example.de"></div>
                            <div class="col-md-6"><label class="form-label">GA4 ID</label><input class="form-control" type="text" name="ga4_id" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_ga4_id'] ?? '')) ?>" placeholder="G-XXXXXXX"></div>
                            <div class="col-md-6"><label class="form-label">GTM ID</label><input class="form-control" type="text" name="gtm_id" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_gtm_id'] ?? '')) ?>" placeholder="GTM-XXXX"></div>
                            <div class="col-md-8"><label class="form-label">Matomo URL</label><input class="form-control" type="text" name="matomo_url" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_matomo_url'] ?? '')) ?>"></div>
                            <div class="col-md-4"><label class="form-label">Matomo Site ID</label><input class="form-control" type="text" name="matomo_site_id" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_matomo_site_id'] ?? '1')) ?>"></div>
                            <div class="col-md-6"><label class="form-label">Meta Pixel</label><input class="form-control" type="text" name="fb_pixel_id" value="<?= htmlspecialchars((string)($trackingSettings['seo_analytics_fb_pixel_id'] ?? '')) ?>"></div>
                            <div class="col-md-6 d-flex flex-column gap-2 justify-content-end">
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="exclude_admins" value="1" <?= !empty($trackingSettings['seo_analytics_exclude_admins']) ? 'checked' : '' ?>><span class="form-check-label">Admins ausschließen</span></label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="respect_dnt" value="1" <?= !empty($trackingSettings['seo_analytics_respect_dnt']) ? 'checked' : '' ?>><span class="form-check-label">Do Not Track respektieren</span></label>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="anonymize_ip" value="1" <?= !empty($trackingSettings['seo_analytics_anonymize_ip']) ? 'checked' : '' ?>><span class="form-check-label">IP anonymisieren</span></label>
                            </div>
                            <div class="col-12"><button class="btn btn-primary" type="submit">Analytics speichern</button></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Internal-Linking-Ideen</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Inhalt</th><th>Typ</th><th>Score</th></tr></thead>
                            <tbody>
                                <?php if (empty($internalLinkSuggestions)): ?>
                                    <tr><td colspan="3" class="text-center text-secondary py-4">Keine offensichtlichen Lücken gefunden.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($internalLinkSuggestions as $suggestion): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)$suggestion['title']) ?><div class="text-secondary small"><?= htmlspecialchars((string)$suggestion['slug']) ?></div></td>
                                            <td><?= htmlspecialchars((string)$suggestion['type']) ?></td>
                                            <td><span class="badge bg-warning-lt text-warning"><?= (int)$suggestion['score'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
