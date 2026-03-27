<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$dashboard = $data['dashboard'] ?? [];
$overview = $data['overview'] ?? [];
$content = $overview['content'] ?? [];
$scores = $overview['scores'] ?? [];
$total = $overview['total'] ?? 0;
$status = $dashboard['status'] ?? [];
$topIssues = $dashboard['top_issues'] ?? [];
$contentBuckets = $dashboard['content_buckets'] ?? [];
$recentCritical = $dashboard['recent_critical'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">SEO Dashboard</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php
            $alertData = is_array($alert ?? null) ? $alert : [];
            require dirname(__DIR__) . '/partials/flash-alert.php';
            ?>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Inhalte geprüft</div>
                        <div class="h1 mb-0"><?= (int)$total ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">SEO Gut</div>
                        <div class="h1 mb-0 text-success"><?= (int)($scores['good'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Warnungen</div>
                        <div class="h1 mb-0 text-warning"><?= (int)($scores['warning'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Kritisch</div>
                        <div class="h1 mb-0 text-danger"><?= (int)($scores['bad'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge <?= !empty($status['sitemap_exists']) ? 'bg-success' : 'bg-danger' ?> badge-pill p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5"/><path d="M9 4v13"/><path d="M15 7v5.5"/><path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.001 -.836 1.709 -1.462 2.121 -1.879z"/><path d="M19 18v.01"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold">Sitemap</div>
                            <div class="text-secondary small">
                                <?= !empty($status['sitemap_exists']) ? 'Vorhanden · ' . htmlspecialchars((string)($status['sitemap_date'] ?? '')) : 'Nicht vorhanden' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge <?= !empty($status['robots_exists']) ? 'bg-success' : 'bg-warning' ?> badge-pill p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18"/><path d="M13 8l2 0"/><path d="M13 12l2 0"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold">robots.txt</div>
                            <div class="text-secondary small"><?= !empty($status['robots_exists']) ? 'Vorhanden · ' . htmlspecialchars((string)($status['robots_date'] ?? '')) : 'Nicht vorhanden' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards mb-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Content-Status</h3></div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between"><span>Veröffentlichbar</span><strong class="text-success"><?= (int)($contentBuckets['publish_ready'] ?? 0) ?></strong></div>
                            <div class="list-group-item d-flex justify-content-between"><span>Meta-Daten fehlen</span><strong class="text-warning"><?= (int)($contentBuckets['needs_meta'] ?? 0) ?></strong></div>
                            <div class="list-group-item d-flex justify-content-between"><span>Interne Links fehlen</span><strong><?= (int)($contentBuckets['needs_links'] ?? 0) ?></strong></div>
                            <div class="list-group-item d-flex justify-content-between"><span>Lesbarkeit optimieren</span><strong class="text-danger"><?= (int)($contentBuckets['needs_readability'] ?? 0) ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Häufigste SEO-Probleme</h3></div>
                    <div class="card-body">
                        <?php if (empty($topIssues)): ?>
                            <div class="text-success">✓ Keine häufigen Probleme erkannt.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topIssues as $issue => $count): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars((string)$issue) ?></span>
                                        <span class="badge bg-warning-lt text-warning"><?= (int)$count ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Kritische Inhalte</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead><tr><th>Inhalt</th><th>Score</th><th>Offene Punkte</th></tr></thead>
                            <tbody>
                                <?php if (empty($recentCritical)): ?>
                                    <tr><td colspan="3" class="text-center text-secondary py-4">Keine kritischen Inhalte – schöne Seltenheit.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentCritical as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)($item['title'] ?? '')) ?><div class="text-secondary small"><?= htmlspecialchars((string)($item['type'] ?? '')) ?> · <?= htmlspecialchars((string)($item['slug'] ?? '')) ?></div></td>
                                            <td><span class="badge bg-danger"><?= (int)($item['seo_score_value'] ?? 0) ?></span></td>
                                            <td>
                                                <?php foreach (array_slice((array)($item['seo_issues'] ?? []), 0, 3) as $issue): ?>
                                                    <div class="small text-warning"><?= htmlspecialchars((string)($issue['msg'] ?? '')) ?></div>
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Schnellzugriffe</h3></div>
                    <div class="list-group list-group-flush">
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-audit">SEO Audit & Bulk-Editor</a>
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-meta">Meta-Templates & Variablen</a>
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-social">OG, X/Twitter & Pinterest</a>
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-schema">Schema.org & Typ-Verteilung</a>
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-sitemap">Sitemaps & robots.txt</a>
                        <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars(SITE_URL) ?>/admin/seo-technical">Broken Links, hreflang & Bild-SEO</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
