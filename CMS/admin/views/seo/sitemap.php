<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$sitemap = $data['sitemap'] ?? [];
$settings = $sitemap['settings'] ?? [];
$files = $sitemap['files'] ?? [];
$counts = $sitemap['counts'] ?? [];
$indexing = $sitemap['indexing'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">Sitemap &amp; Indexing</h2>
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

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Pages</div><div class="h1 mb-0"><?= (int)($counts['pages'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Posts</div><div class="h1 mb-0"><?= (int)($counts['posts'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Bild-URLs</div><div class="h1 mb-0"><?= (int)($counts['images'] ?? 0) ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">News-Kandidaten</div><div class="h1 mb-0"><?= (int)($counts['news_candidates'] ?? 0) ?></div></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Dateistatus &amp; öffentliche URLs</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Datei</th>
                                    <th>Status</th>
                                    <th>Aktualisiert</th>
                                    <th class="text-end">Größe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($files as $name => $file): ?>
                                    <tr>
                                        <td>
                                            <div><code><?= htmlspecialchars((string) $name) ?></code></div>
                                            <div class="text-secondary small">
                                                <a href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/' . $name) ?>" target="_blank" rel="noopener noreferrer">
                                                    <?= htmlspecialchars(rtrim(SITE_URL, '/') . '/' . $name) ?>
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($file['exists'])): ?>
                                                <span class="badge bg-success">vorhanden</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">fehlt</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($file['updated_at'] ?? '—')) ?></td>
                                        <td class="text-end"><?= !empty($file['exists']) ? number_format(((int) $file['size']) / 1024, 1, ',', '.') . ' KB' : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Sitemap-Defaults</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_sitemap_settings">

                            <div class="col-md-6">
                                <label class="form-label">Pages Priority</label>
                                <input class="form-control" type="text" name="pages_priority" value="<?= htmlspecialchars((string) ($settings['pages_priority'] ?? '0.8')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pages Changefreq</label>
                                <input class="form-control" type="text" name="pages_changefreq" value="<?= htmlspecialchars((string) ($settings['pages_changefreq'] ?? 'weekly')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posts Priority</label>
                                <input class="form-control" type="text" name="posts_priority" value="<?= htmlspecialchars((string) ($settings['posts_priority'] ?? '0.6')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Posts Changefreq</label>
                                <input class="form-control" type="text" name="posts_changefreq" value="<?= htmlspecialchars((string) ($settings['posts_changefreq'] ?? 'monthly')) ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">News-Publikation</label>
                                <input class="form-control" type="text" name="news_publication_name" value="<?= htmlspecialchars((string) ($settings['seo_sitemap_news_publication_name'] ?? $settings['news_publication_name'] ?? '365CMS')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">News-Sprache</label>
                                <input class="form-control" type="text" name="news_language" value="<?= htmlspecialchars((string) ($settings['seo_sitemap_news_language'] ?? $settings['news_language'] ?? 'de')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ping_google" value="1" <?= !empty($settings['ping_google']) ? 'checked' : '' ?>>
                                    <span class="form-check-label">Google pingen</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ping_bing" value="1" <?= !empty($settings['ping_bing']) ? 'checked' : '' ?>>
                                    <span class="form-check-label">Bing pingen</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="image_enabled" value="1" <?= !empty($settings['seo_sitemap_image_enabled']) ? 'checked' : '' ?>>
                                    <span class="form-check-label">`images.xml` aktivieren</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="news_enabled" value="1" <?= !empty($settings['seo_sitemap_news_enabled']) ? 'checked' : '' ?>>
                                    <span class="form-check-label">`news.xml` aktivieren</span>
                                </label>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Sitemap-Einstellungen speichern</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">IndexNow &amp; Google Submission</h3>
                        <span class="badge <?= !empty($indexing['indexnow_available']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            IndexNow-Key <?= !empty($indexing['indexnow_available']) ? 'bereit' : 'fehlt' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-secondary small mb-3">
                            <?php foreach (($indexing['notes'] ?? []) as $note): ?>
                                <div>• <?= htmlspecialchars((string) $note) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="submit_indexing_urls">
                            <div class="col-12">
                                <label class="form-label">URLs zur Übermittlung</label>
                                <textarea class="form-control" name="urls" rows="6" placeholder="<?= htmlspecialchars(SITE_URL) ?>/&#10;<?= htmlspecialchars(SITE_URL) ?>/blog/beispiel-artikel"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="submission_target[]" value="indexnow" <?= !empty($indexing['indexnow_available']) ? 'checked' : '' ?>>
                                    <span class="form-check-label">An IndexNow senden</span>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="submission_target[]" value="google">
                                    <span class="form-check-label">An Google senden</span>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Google Access-Token</label>
                                <input class="form-control" type="password" name="google_access_token" autocomplete="off" placeholder="ya29...">
                                <div class="form-hint">Nur für die aktuelle Aktion. Der Token wird nicht gespeichert.</div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">URLs jetzt übermitteln</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Google: URL aus Index entfernen</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="delete_google_url">
                            <div class="col-md-7">
                                <label class="form-label">URL</label>
                                <input class="form-control" type="url" name="google_delete_url" placeholder="<?= htmlspecialchars(SITE_URL) ?>/veraltete-seite">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Google Access-Token</label>
                                <input class="form-control" type="password" name="google_access_token" autocomplete="off" placeholder="ya29...">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-danger" type="submit">URL bei Google löschen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Bundle-Struktur</h3>
                    </div>
                    <div class="card-body">
                        <div class="timeline-simple">
                            <div class="mb-3">
                                <div class="fw-bold"><code>sitemap.xml</code></div>
                                <div class="text-secondary small">Index-Datei mit Verweisen auf alle registrierten Teil-Sitemaps.</div>
                            </div>
                            <div class="mb-3">
                                <div class="fw-bold"><code>pages.xml</code></div>
                                <div class="text-secondary small">Statische Seiten inklusive Homepage.</div>
                            </div>
                            <div class="mb-3">
                                <div class="fw-bold"><code>posts.xml</code></div>
                                <div class="text-secondary small">Beiträge mit `lastMod`, Priority und Changefreq.</div>
                            </div>
                            <div class="mb-3">
                                <div class="fw-bold"><code>images.xml</code></div>
                                <div class="text-secondary small">Bild-Sitemap für Beitrags- und Seitenbilder.</div>
                            </div>
                            <div>
                                <div class="fw-bold"><code>news.xml</code></div>
                                <div class="text-secondary small">Optionale News-Sitemap für aktuelle Artikel.</div>
                            </div>
                        </div>
                        <hr>
                        <div class="small text-secondary">
                            Aktive Endpunkte:
                            <strong><?= htmlspecialchars(implode(', ', (array) ($indexing['engines'] ?? []))) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
