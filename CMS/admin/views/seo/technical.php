<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$technical = $data['technical'] ?? [];
$settings = $technical['settings'] ?? [];
$brokenLinks = $technical['broken_links'] ?? [];
$missingAltRows = $technical['missing_alt_rows'] ?? [];
$noindexCandidates = $technical['noindex_candidates'] ?? [];
$hreflangGroups = $technical['hreflang_groups'] ?? [];
$redirectStats = $technical['redirect_stats'] ?? [];
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">SEO</div><h2 class="page-title">Technisches SEO</h2></div></div></div></div>
<div class="page-body"><div class="container-xl">
    <?php if (!empty($alert)): ?>
        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>
    <?php require __DIR__ . '/subnav.php'; ?>
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-3"><div class="card"><div class="card-body"><div class="subheader">Broken Links</div><div class="h1 mb-0 text-danger"><?= count($brokenLinks) ?></div></div></div></div>
        <div class="col-sm-3"><div class="card"><div class="card-body"><div class="subheader">Bilder ohne Alt</div><div class="h1 mb-0 text-warning"><?= count($missingAltRows) ?></div></div></div></div>
        <div class="col-sm-3"><div class="card"><div class="card-body"><div class="subheader">noindex-Kandidaten</div><div class="h1 mb-0"><?= count($noindexCandidates) ?></div></div></div></div>
        <div class="col-sm-3"><div class="card"><div class="card-body"><div class="subheader">404-Hits</div><div class="h1 mb-0"><?= (int)($redirectStats['not_found_hits'] ?? 0) ?></div></div></div></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h3 class="card-title">Technische Standards</h3></div><div class="card-body"><form method="post" class="row g-3"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="action" value="save_technical_settings"><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="auto_redirect_slug" value="1" <?= !empty($settings['seo_technical_auto_redirect_slug']) ? 'checked' : '' ?>><span class="form-check-label">Weiterleitung bei Slug-Änderung automatisch</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="hreflang_enabled" value="1" <?= !empty($settings['seo_technical_hreflang_enabled']) ? 'checked' : '' ?>><span class="form-check-label">hreflang-Gruppen pflegen</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="breadcrumbs_enabled" value="1" <?= !empty($settings['seo_technical_breadcrumbs_enabled']) ? 'checked' : '' ?>><span class="form-check-label">Breadcrumbs aktiv</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="image_alt_required" value="1" <?= !empty($settings['seo_technical_image_alt_required']) ? 'checked' : '' ?>><span class="form-check-label">Bild-Alt-Texte verpflichtend</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="noindex_archives" value="1" <?= !empty($settings['seo_technical_noindex_archives']) ? 'checked' : '' ?>><span class="form-check-label">Archive noindex</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="noindex_tags" value="1" <?= !empty($settings['seo_technical_noindex_tags']) ? 'checked' : '' ?>><span class="form-check-label">Tags noindex</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="pagination_rel" value="1" <?= !empty($settings['seo_technical_pagination_rel']) ? 'checked' : '' ?>><span class="form-check-label">Pagination-SEO aktiv</span></label></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="broken_link_scan" value="1" <?= !empty($settings['seo_technical_broken_link_scan']) ? 'checked' : '' ?>><span class="form-check-label">Broken-Link-Prüfung aktiv</span></label></div><div class="col-12"><button class="btn btn-primary" type="submit">Technische SEO speichern</button></div></form></div></div></div>
        <div class="col-lg-7"><div class="card mb-4"><div class="card-header"><h3 class="card-title">Broken Links</h3></div><div class="table-responsive"><table class="table card-table table-vcenter"><thead><tr><th>Quelle</th><th>Ziel</th></tr></thead><tbody><?php if (empty($brokenLinks)): ?><tr><td colspan="2" class="text-center text-secondary py-4">Keine internen Broken Links erkannt.</td></tr><?php else: ?><?php foreach ($brokenLinks as $row): ?><tr><td><?= htmlspecialchars((string)$row['source_title']) ?><div class="text-secondary small"><?= htmlspecialchars((string)$row['source_type']) ?> · <?= htmlspecialchars((string)$row['source_slug']) ?></div></td><td><code><?= htmlspecialchars((string)$row['target_path']) ?></code></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div><div class="card"><div class="card-header"><h3 class="card-title">hreflang & Bild-SEO</h3></div><div class="card-body"><div class="mb-3"><strong>hreflang-Gruppen:</strong> <?= count($hreflangGroups) ?></div><div class="mb-3"><strong>404-Weiterleitungen:</strong> <?= (int)($redirectStats['redirects_total'] ?? 0) ?> Regeln, <?= (int)($redirectStats['redirects_active'] ?? 0) ?> aktiv</div><div class="text-secondary small">Inhalte mit fehlenden Bild-Alt-Texten: <?= count($missingAltRows) ?> · Inhalte mit noindex: <?= count($noindexCandidates) ?></div></div></div></div>
    </div>
</div></div>
