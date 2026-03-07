<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$content  = $data['content'] ?? [];
$scores   = $data['scores'] ?? [];
$total    = $data['total'] ?? 0;
$scoreColors = ['good' => 'success', 'warning' => 'warning', 'bad' => 'danger'];
$scoreLabels = ['good' => 'Gut', 'warning' => 'Warnung', 'bad' => 'Kritisch'];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO & Performance</div>
                <h2 class="page-title">SEO Dashboard</h2>
            </div>
            <div class="col-auto ms-auto">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="regenerate_sitemap">
                    <button type="submit" class="btn btn-outline-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
                        Sitemap generieren
                    </button>
                </form>
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

        <!-- Score Cards -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Inhalte geprüft</div>
                        <div class="h1 mb-0"><?= $total ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">SEO Gut</div>
                        <div class="h1 mb-0 text-success"><?= (int)$scores['good'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Warnungen</div>
                        <div class="h1 mb-0 text-warning"><?= (int)$scores['warning'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Kritisch</div>
                        <div class="h1 mb-0 text-danger"><?= (int)$scores['bad'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge <?= $data['sitemap_exists'] ? 'bg-success' : 'bg-danger' ?> badge-pill p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5"/><path d="M9 4v13"/><path d="M15 7v5.5"/><path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.001 -.836 1.709 -1.462 2.121 -1.879z"/><path d="M19 18v.01"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold">Sitemap</div>
                            <div class="text-secondary small">
                                <?= $data['sitemap_exists'] ? 'Vorhanden · ' . htmlspecialchars($data['sitemap_date'] ?? '') : 'Nicht vorhanden' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="badge <?= $data['robots_exists'] ? 'bg-success' : 'bg-warning' ?> badge-pill p-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h11a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-11a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1m3 0v18"/><path d="M13 8l2 0"/><path d="M13 12l2 0"/></svg>
                        </span>
                        <div>
                            <div class="fw-bold">robots.txt</div>
                            <div class="text-secondary small"><?= $data['robots_exists'] ? 'Vorhanden' : 'Nicht vorhanden' ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Audit Table -->
        <div class="card">
            <div class="card-header"><h3 class="card-title">SEO-Audit: Alle Inhalte</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Typ</th>
                            <th>Titel</th>
                            <th>Meta-Titel</th>
                            <th>Meta-Beschreibung</th>
                            <th>Status</th>
                            <th>Probleme</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content as $item): ?>
                            <tr>
                                <td><span class="badge bg-azure-lt"><?= htmlspecialchars($item['type']) ?></span></td>
                                <td>
                                    <a href="<?= $siteUrl ?>/admin/<?= $item['type'] === 'Seite' ? 'pages' : 'posts' ?>?action=edit&id=<?= (int)$item['id'] ?>">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php $mt = $item['meta_title'] ?? ''; ?>
                                    <?php if ($mt): ?>
                                        <span class="text-secondary small"><?= mb_strlen($mt) ?>/60</span>
                                    <?php else: ?>
                                        <span class="text-danger small">fehlt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $md = $item['meta_description'] ?? ''; ?>
                                    <?php if ($md): ?>
                                        <span class="text-secondary small"><?= mb_strlen($md) ?>/160</span>
                                    <?php else: ?>
                                        <span class="text-danger small">fehlt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $scoreColors[$item['seo_score']] ?? 'secondary' ?>">
                                        <?= $scoreLabels[$item['seo_score']] ?? '?' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php foreach ($item['seo_issues'] as $issue): ?>
                                        <div class="text-<?= $issue['type'] === 'bad' ? 'danger' : 'warning' ?> small"><?= htmlspecialchars($issue['msg']) ?></div>
                                    <?php endforeach; ?>
                                    <?php if (empty($item['seo_issues'])): ?>
                                        <span class="text-success small">✓ Alles gut</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
