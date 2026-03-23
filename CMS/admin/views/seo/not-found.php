<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$logs    = $data['logs'] ?? [];
$stats   = $data['stats'] ?? [];
$targets = $data['targets'] ?? ['pages' => [], 'posts' => [], 'hubs' => []];
$sites   = $data['sites'] ?? [];
$alertDetails = is_array($alert['details'] ?? null) ? $alert['details'] : [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">404-Monitor</h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <form
                    method="post"
                    class="d-inline"
                    data-confirm-title="404-Protokoll leeren?"
                    data-confirm-message="Alle protokollierten 404-Einträge werden entfernt. Dieser Schritt kann nicht rückgängig gemacht werden."
                    data-confirm-text="Protokoll leeren"
                    data-confirm-class="btn-danger"
                    data-confirm-status-class="bg-danger"
                >
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-danger">404-Protokoll leeren</button>
                </form>
                <a href="<?= htmlspecialchars(SITE_URL . '/admin/redirect-manager') ?>" class="btn btn-primary">Zu den Weiterleitungen</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <?php if ($alertDetails !== []): ?>
                    <ul class="mb-0 mt-2 small ps-3">
                        <?php foreach ($alertDetails as $detail): ?>
                            <li><?= htmlspecialchars((string)$detail) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">404-Pfade</div><div class="h1 mb-0 text-warning"><?= (int)($stats['not_found_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">404-Hits</div><div class="h1 mb-0"><?= (int)($stats['not_found_hits'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Weiterleitungen</div><div class="h1 mb-0"><?= (int)($stats['redirects_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Aktive Regeln</div><div class="h1 mb-0 text-success"><?= (int)($stats['redirects_active'] ?? 0) ?></div></div></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div>
                    <h3 class="card-title mb-1">Erkannte 404-Fehler</h3>
                    <div class="text-secondary small">Einträge sind hostbezogen protokolliert und können direkt in eine Weiterleitung übernommen werden.</div>
                </div>
                <label class="form-check form-switch mb-0">
                    <input type="checkbox" class="form-check-input" id="toggle-hide-resolved-404">
                    <span class="form-check-label">Bereits übernommene ausblenden</span>
                </label>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Pfad</th>
                            <th>Host / Site</th>
                            <th>Hits</th>
                            <th>Zuletzt gesehen</th>
                            <th>Referrer</th>
                            <th>Weiterleitung</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">Noch keine 404-Einträge protokolliert.</td></tr>
                        <?php else: ?>
                            <tr class="js-hidden-resolved-empty" hidden>
                                <td colspan="7" class="text-center text-secondary py-4">Alle sichtbaren Einträge sind bereits übernommen.</td>
                            </tr>
                            <?php foreach ($logs as $log): ?>
                                <?php $resolved = !empty($log['redirect_id']); ?>
                                <tr class="<?= $resolved ? 'table-success' : '' ?>" data-log-resolved="<?= $resolved ? '1' : '0' ?>">
                                    <td>
                                        <code><?= htmlspecialchars((string)($log['request_path'] ?? '')) ?></code>
                                        <?php if ($resolved): ?>
                                            <span class="badge bg-success-lt text-success ms-2">Bereits übernommen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string)($log['request_host_label'] ?? 'Hauptsite / unbekannter Host')) ?></div>
                                        <?php if (!empty($log['site_scope_suggestion'])): ?>
                                            <div class="text-secondary small">Vorschlag: <?= htmlspecialchars((string)$log['site_scope_suggestion']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($log['hit_count'] ?? 0) ?></td>
                                    <td><?= !empty($log['last_seen_at']) ? date('d.m.Y H:i', strtotime((string)$log['last_seen_at'])) : '–' ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars((string)($log['referrer_url'] ?? '–')) ?></td>
                                    <td>
                                        <?php if (!empty($log['target_url'])): ?>
                                            <span class="badge <?= (int)($log['redirect_is_active'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)($log['redirect_type'] ?? 301) ?></span>
                                            <span class="text-secondary small"><?= htmlspecialchars((string)$log['target_url']) ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary">Keine</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm <?= $resolved ? 'btn-outline-secondary' : 'btn-outline-primary' ?> js-takeover-log"
                                            data-log="<?= htmlspecialchars(json_encode($log, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>"
                                        ><?= $resolved ? 'Bearbeiten' : 'Übernehmen' ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="seo-not-found-config"><?php echo json_encode([
    'targets' => $targets,
    'sites' => $sites,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<div class="modal modal-blur fade" id="notFoundRedirectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="action" value="save_redirect">
                <input type="hidden" name="redirect_id" id="redirect-id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="redirect-modal-title">404-Weiterleitung übernehmen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">404-Pfad</label>
                            <input type="text" name="source_path" id="redirect-source" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Passende Site</label>
                            <select name="site_scope" id="redirect-site-scope" class="form-select">
                                <?php foreach ($sites as $site): ?>
                                    <option value="<?= htmlspecialchars((string)($site['value'] ?? '')) ?>"><?= htmlspecialchars((string)($site['label'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-0">
                        <div class="col-md-4">
                            <label class="form-label">Zieltyp</label>
                            <select name="target_kind" id="redirect-target-kind" class="form-select">
                                <option value="manual">Freie URL / externer Slug</option>
                                <option value="page">Seite</option>
                                <option value="post">Beitrag</option>
                                <option value="hub">Hub Site</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="redirect-target-manual-group">
                            <label class="form-label">Ziel-URL / Slug</label>
                            <input type="text" name="target_url_manual" id="redirect-target-manual" class="form-control" placeholder="/neuer-pfad oder https://..." required>
                        </div>
                        <div class="col-md-8" id="redirect-target-page-group" hidden>
                            <label class="form-label">Ziel-Seite</label>
                            <select name="target_page_id" id="redirect-target-page" class="form-select">
                                <option value="">Bitte Seite wählen</option>
                                <?php foreach (($targets['pages'] ?? []) as $pageTarget): ?>
                                    <option value="<?= (int)($pageTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($pageTarget['label'] ?? '')) ?> — /<?= htmlspecialchars((string)($pageTarget['slug'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8" id="redirect-target-post-group" hidden>
                            <label class="form-label">Ziel-Beitrag</label>
                            <select name="target_post_id" id="redirect-target-post" class="form-select">
                                <option value="">Bitte Beitrag wählen</option>
                                <?php foreach (($targets['posts'] ?? []) as $postTarget): ?>
                                    <option value="<?= (int)($postTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($postTarget['label'] ?? '')) ?> — <?= htmlspecialchars((string)($postTarget['url'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8" id="redirect-target-hub-group" hidden>
                            <label class="form-label">Ziel-Hub-Site</label>
                            <select name="target_hub_id" id="redirect-target-hub" class="form-select">
                                <option value="">Bitte Hub Site wählen</option>
                                <?php foreach (($targets['hubs'] ?? []) as $hubTarget): ?>
                                    <option value="<?= (int)($hubTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($hubTarget['label'] ?? '')) ?> — <?= htmlspecialchars((string)($hubTarget['url'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="target_url" id="redirect-target-hidden" value="">
                    <div class="row g-3 mt-0">
                        <div class="col-md-4">
                            <label class="form-label">Typ</label>
                            <select name="redirect_type" id="redirect-type" class="form-select">
                                <option value="301">301 permanent</option>
                                <option value="302">302 temporär</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <label class="form-check form-switch mb-3">
                                <input type="checkbox" name="is_active" id="redirect-active" class="form-check-input" checked>
                                <span class="form-check-label">Sofort aktivieren</span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notiz</label>
                        <textarea name="notes" id="redirect-notes" class="form-control" rows="3" placeholder="z. B. aus 404-Monitor übernommen"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Weiterleitung speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>
