<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$redirects = $data['redirects'] ?? [];
$logs      = $data['logs'] ?? [];
$stats     = $data['stats'] ?? [];
$targets   = $data['targets'] ?? ['pages' => [], 'posts' => [], 'hubs' => []];
$resolvedLogsCount = count(array_filter($logs, static fn(array $log): bool => !empty($log['redirect_id'])));
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">SEO</div>
                <h2 class="page-title">404-Errors &amp; Weiterleitung</h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="action" value="clear_logs">
                    <button type="submit" class="btn btn-outline-danger">404-Protokoll leeren</button>
                </form>
                <button type="button" class="btn btn-primary js-create-redirect">Weiterleitung anlegen</button>
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
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Weiterleitungen</div><div class="h1 mb-0"><?= (int)($stats['redirects_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">Aktiv</div><div class="h1 mb-0 text-success"><?= (int)($stats['redirects_active'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">404-Pfade</div><div class="h1 mb-0 text-warning"><?= (int)($stats['not_found_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card"><div class="card-body"><div class="subheader">404-Hits</div><div class="h1 mb-0"><?= (int)($stats['not_found_hits'] ?? 0) ?></div></div></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Weiterleitungen per Slug löschen</h3>
            </div>
            <div class="card-body">
                <form method="post" class="row g-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="action" value="delete_redirects_by_slug">
                    <div class="col-md-8 col-lg-6">
                        <label class="form-label" for="redirect-slug-filter">Slug</label>
                        <input
                            type="text"
                            class="form-control"
                            id="redirect-slug-filter"
                            name="slug_filter"
                            placeholder="z. B. kontakt oder blog"
                            required
                        >
                        <div class="form-hint">Löscht Weiterleitungen, deren Quellpfad den Slug als eigenes Segment enthält – z. B. <code>*/kontakt/*</code>, <code>/kontakt</code> oder <code>/de/kontakt/team</code>.</div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <button
                            type="submit"
                            class="btn btn-outline-danger w-100"
                            onclick="return confirm('Passende Weiterleitungen für diesen Slug wirklich löschen?')"
                        >Slug-Regeln löschen</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Weiterleitungen</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Quelle</th>
                            <th>Ziel</th>
                            <th>Typ</th>
                            <th>Hits</th>
                            <th>Status</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($redirects)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Noch keine Weiterleitungen angelegt.</td></tr>
                        <?php else: ?>
                            <?php foreach ($redirects as $redirect): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($redirect['source_path']) ?></code></td>
                                    <td class="text-secondary"><?= htmlspecialchars($redirect['target_url']) ?></td>
                                    <td><span class="badge <?= (int)$redirect['redirect_type'] === 301 ? 'bg-green' : 'bg-yellow' ?>"><?= (int)$redirect['redirect_type'] ?></span></td>
                                    <td><?= (int)($redirect['hits'] ?? 0) ?></td>
                                    <td><?= (int)$redirect['is_active'] === 1 ? '<span class="badge bg-success">Aktiv</span>' : '<span class="badge bg-secondary">Inaktiv</span>' ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <button
                                                    type="button"
                                                    class="dropdown-item js-edit-redirect"
                                                    data-redirect="<?= htmlspecialchars(json_encode($redirect, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>"
                                                >Bearbeiten</button>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                                    <input type="hidden" name="action" value="toggle_redirect">
                                                    <input type="hidden" name="id" value="<?= (int)$redirect['id'] ?>">
                                                    <button type="submit" class="dropdown-item"><?= (int)$redirect['is_active'] === 1 ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                                    <input type="hidden" name="action" value="delete_redirect">
                                                    <input type="hidden" name="id" value="<?= (int)$redirect['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Weiterleitung wirklich löschen?')">Löschen</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title mb-1">Erkannte 404-Fehler</h3>
                    <div class="text-secondary small">Bei Bedarf direkt als 301/302 übernehmbar.</div>
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
                            <th>Hits</th>
                            <th>Zuletzt gesehen</th>
                            <th>Referrer</th>
                            <th>Weiterleitung</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">Noch keine 404-Einträge protokolliert.</td></tr>
                        <?php else: ?>
                            <tr class="js-hidden-resolved-empty" hidden>
                                <td colspan="6" class="text-center text-secondary py-4">
                                    Alle aktuell sichtbaren 404-Einträge sind bereits übernommen.
                                    <span class="d-block small mt-1">Deaktiviere den Filter, um bearbeitete Einträge wieder einzublenden.</span>
                                </td>
                            </tr>
                            <?php foreach ($logs as $log): ?>
                                <?php $isResolvedLog = !empty($log['redirect_id']); ?>
                                <tr class="<?= $isResolvedLog ? 'table-success' : '' ?>" data-log-resolved="<?= $isResolvedLog ? '1' : '0' ?>">
                                    <td>
                                        <code><?= htmlspecialchars($log['request_path']) ?></code>
                                        <?php if ($isResolvedLog): ?>
                                            <span class="badge bg-success-lt text-success ms-2">404 bereits übernommen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)($log['hit_count'] ?? 0) ?></td>
                                    <td><?= !empty($log['last_seen_at']) ? date('d.m.Y H:i', strtotime((string)$log['last_seen_at'])) : '–' ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars($log['referrer_url'] ?? '–') ?></td>
                                    <td>
                                        <?php if (!empty($log['target_url'])): ?>
                                            <span class="badge <?= (int)($log['redirect_is_active'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)($log['redirect_type'] ?? 301) ?></span>
                                            <span class="text-secondary small"><?= htmlspecialchars($log['target_url']) ?></span>
                                            <?php if ($isResolvedLog): ?>
                                                <div class="text-success small mt-1">Schon mit vorhandener Weiterleitung verknüpft.</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-secondary">Keine</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm <?= !empty($log['redirect_id']) ? 'btn-outline-secondary' : 'btn-outline-primary' ?> js-takeover-log"
                                            data-log="<?= htmlspecialchars(json_encode($log, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>"
                                        ><?= !empty($log['redirect_id']) ? 'Bearbeiten' : 'Übernehmen' ?></button>
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

<div class="modal modal-blur fade" id="redirectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="action" value="save_redirect">
                <input type="hidden" name="redirect_id" id="redirect-id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="redirect-modal-title">Weiterleitung anlegen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Quellpfad</label>
                        <input type="text" name="source_path" id="redirect-source" class="form-control" placeholder="/alter-pfad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zieltyp</label>
                        <select name="target_kind" id="redirect-target-kind" class="form-select">
                            <option value="manual">Freie URL / externer Slug</option>
                            <option value="page">Seite</option>
                            <option value="post">Beitrag</option>
                            <option value="hub">Hub Site</option>
                        </select>
                    </div>
                    <div class="mb-3" id="redirect-target-page-group" hidden>
                        <label class="form-label">Ziel-Seite</label>
                        <select name="target_page_id" id="redirect-target-page" class="form-select">
                            <option value="">Bitte Seite wählen</option>
                            <?php foreach (($targets['pages'] ?? []) as $pageTarget): ?>
                                <option value="<?= (int)($pageTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($pageTarget['label'] ?? '')) ?> — /<?= htmlspecialchars((string)($pageTarget['slug'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="redirect-target-post-group" hidden>
                        <label class="form-label">Ziel-Beitrag</label>
                        <select name="target_post_id" id="redirect-target-post" class="form-select">
                            <option value="">Bitte Beitrag wählen</option>
                            <?php foreach (($targets['posts'] ?? []) as $postTarget): ?>
                                <option value="<?= (int)($postTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($postTarget['label'] ?? '')) ?> — /blog/<?= htmlspecialchars((string)($postTarget['slug'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="redirect-target-hub-group" hidden>
                        <label class="form-label">Ziel-Hub-Site</label>
                        <select name="target_hub_id" id="redirect-target-hub" class="form-select">
                            <option value="">Bitte Hub Site wählen</option>
                            <?php foreach (($targets['hubs'] ?? []) as $hubTarget): ?>
                                <option value="<?= (int)($hubTarget['id'] ?? 0) ?>"><?= htmlspecialchars((string)($hubTarget['label'] ?? '')) ?> — /<?= htmlspecialchars((string)($hubTarget['slug'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="redirect-target-manual-group">
                        <label class="form-label">Ziel-URL / Slug</label>
                        <input type="text" name="target_url_manual" id="redirect-target-manual" class="form-control" placeholder="/neuer-pfad oder https://..." required>
                        <div class="form-hint">Für interne Ziele reicht auch ein Slug wie <code>kontakt</code> oder ein Pfad wie <code>/kontakt</code>.</div>
                    </div>
                    <input type="hidden" name="target_url" id="redirect-target-hidden" value="">
                    <div class="row g-3">
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
                        <textarea name="notes" id="redirect-notes" class="form-control" rows="3" placeholder="z. B. alte Kampagnen-URL, Tippfehler, Umstrukturierung"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modalElement = document.getElementById('redirectModal');
    if (!modalElement) {
        return;
    }

    const titleElement = document.getElementById('redirect-modal-title');
    const idField = document.getElementById('redirect-id');
    const sourceField = document.getElementById('redirect-source');
    const targetKindField = document.getElementById('redirect-target-kind');
    const targetPageField = document.getElementById('redirect-target-page');
    const targetPostField = document.getElementById('redirect-target-post');
    const targetHubField = document.getElementById('redirect-target-hub');
    const targetManualField = document.getElementById('redirect-target-manual');
    const targetHiddenField = document.getElementById('redirect-target-hidden');
    const targetPageGroup = document.getElementById('redirect-target-page-group');
    const targetPostGroup = document.getElementById('redirect-target-post-group');
    const targetHubGroup = document.getElementById('redirect-target-hub-group');
    const targetManualGroup = document.getElementById('redirect-target-manual-group');
    const typeField = document.getElementById('redirect-type');
    const notesField = document.getElementById('redirect-notes');
    const activeField = document.getElementById('redirect-active');
    const form = modalElement.querySelector('form');
    const hideResolvedToggle = document.getElementById('toggle-hide-resolved-404');
    const resolvedLogRows = Array.from(document.querySelectorAll('tr[data-log-resolved]'));
    const hiddenResolvedEmptyState = document.querySelector('.js-hidden-resolved-empty');
    const targetCatalog = <?= json_encode($targets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const resolvedLogsCount = <?= (int)$resolvedLogsCount ?>;
    const hideResolvedStorageKey = 'cms-admin-hide-resolved-404';

    function normalizeTargetUrl(value) {
        const trimmed = String(value || '').trim();

        if (!trimmed) {
            return '';
        }

        if (/^https?:\/\//i.test(trimmed)) {
            try {
                const absoluteUrl = new URL(trimmed, window.location.origin);
                if (absoluteUrl.origin === window.location.origin) {
                    return normalizeTargetUrl((absoluteUrl.pathname || '/') + (absoluteUrl.search || '') + (absoluteUrl.hash || ''));
                }
            } catch (error) {
                return trimmed;
            }

            return trimmed;
        }

        let normalized = trimmed;
        if (!normalized.startsWith('/')) {
            normalized = '/' + normalized;
        }

        if (normalized.length > 1) {
            normalized = normalized.replace(/\/+$/, '');
        }

        return normalized || '/';
    }

    function findTargetByUrl(url) {
        const normalized = normalizeTargetUrl(url);
        const targetMaps = [
            ['page', targetCatalog.pages || []],
            ['post', targetCatalog.posts || []],
            ['hub', targetCatalog.hubs || []]
        ];

        for (const [kind, items] of targetMaps) {
            const match = items.find(function (item) {
                return normalizeTargetUrl(item.url) === normalized;
            });

            if (match) {
                return { kind: kind, item: match };
            }
        }

        return null;
    }

    function updateTargetFieldVisibility() {
        const kind = targetKindField.value || 'manual';

        targetPageGroup.hidden = kind !== 'page';
        targetPostGroup.hidden = kind !== 'post';
        targetHubGroup.hidden = kind !== 'hub';
        targetManualGroup.hidden = kind !== 'manual';

        targetManualField.required = kind === 'manual';
        targetPageField.required = kind === 'page';
        targetPostField.required = kind === 'post';
        targetHubField.required = kind === 'hub';
    }

    function setTargetKind(kind) {
        targetKindField.value = kind;
        updateTargetFieldVisibility();
    }

    function resetTargetSelectors() {
        targetPageField.value = '';
        targetPostField.value = '';
        targetHubField.value = '';
        targetManualField.value = '';
        targetHiddenField.value = '';
    }

    function applyTargetValue(url) {
        resetTargetSelectors();

        const matchedTarget = findTargetByUrl(url);
        if (matchedTarget) {
            setTargetKind(matchedTarget.kind);

            if (matchedTarget.kind === 'page') {
                targetPageField.value = String(matchedTarget.item.id || '');
            } else if (matchedTarget.kind === 'post') {
                targetPostField.value = String(matchedTarget.item.id || '');
            } else if (matchedTarget.kind === 'hub') {
                targetHubField.value = String(matchedTarget.item.id || '');
            }

            targetHiddenField.value = matchedTarget.item.url || '';
            return;
        }

        setTargetKind('manual');
        targetManualField.value = url || '';
        targetHiddenField.value = url || '';
    }

    function syncHiddenTargetValue() {
        const kind = targetKindField.value || 'manual';

        if (kind === 'page') {
            const item = (targetCatalog.pages || []).find(function (entry) {
                return String(entry.id) === String(targetPageField.value || '');
            });
            targetHiddenField.value = item ? (item.url || '') : '';
            return;
        }

        if (kind === 'post') {
            const item = (targetCatalog.posts || []).find(function (entry) {
                return String(entry.id) === String(targetPostField.value || '');
            });
            targetHiddenField.value = item ? (item.url || '') : '';
            return;
        }

        if (kind === 'hub') {
            const item = (targetCatalog.hubs || []).find(function (entry) {
                return String(entry.id) === String(targetHubField.value || '');
            });
            targetHiddenField.value = item ? (item.url || '') : '';
            return;
        }

        targetHiddenField.value = targetManualField.value.trim();
    }

    function getModalInstance() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            console.error('Bootstrap Modal ist nicht verfügbar.');
            return null;
        }

        return bootstrap.Modal.getOrCreateInstance(modalElement);
    }

    function resetRedirectForm() {
        titleElement.textContent = 'Weiterleitung anlegen';
        idField.value = '0';
        sourceField.value = '';
        setTargetKind('manual');
        resetTargetSelectors();
        typeField.value = '301';
        notesField.value = '';
        activeField.checked = true;
    }

    function applyResolvedFilter(hideResolved) {
        if (!resolvedLogRows.length) {
            if (hiddenResolvedEmptyState) {
                hiddenResolvedEmptyState.hidden = true;
            }
            return;
        }

        let visibleRows = 0;

        resolvedLogRows.forEach(function (row) {
            const isResolved = row.dataset.logResolved === '1';
            const shouldHide = hideResolved && isResolved;
            row.hidden = shouldHide;

            if (!shouldHide) {
                visibleRows += 1;
            }
        });

        if (hiddenResolvedEmptyState) {
            hiddenResolvedEmptyState.hidden = !(hideResolved && visibleRows === 0 && resolvedLogsCount > 0);
        }
    }

    function openCreateModal() {
        const modal = getModalInstance();
        if (!modal) {
            return;
        }

        resetRedirectForm();
        modal.show();
        window.setTimeout(function () {
            sourceField.focus();
        }, 120);
    }

    function openEditModal(redirect) {
        const modal = getModalInstance();
        if (!modal) {
            return;
        }

        resetRedirectForm();
        titleElement.textContent = 'Weiterleitung bearbeiten';
        idField.value = redirect.id || 0;
        sourceField.value = redirect.source_path || '';
        applyTargetValue(redirect.target_url || '');
        typeField.value = String(redirect.redirect_type || 301);
        notesField.value = redirect.notes || '';
        activeField.checked = String(redirect.is_active || '0') === '1' || redirect.is_active === 1;
        modal.show();
        window.setTimeout(function () {
            if ((targetKindField.value || 'manual') === 'manual') {
                targetManualField.focus();
                return;
            }

            typeField.focus();
        }, 120);
    }

    function openLogTakeoverModal(log) {
        const modal = getModalInstance();
        if (!modal) {
            return;
        }

        resetRedirectForm();
        titleElement.textContent = log.redirect_id ? '404-Weiterleitung bearbeiten' : '404-Fehler übernehmen';
        idField.value = log.redirect_id || 0;
        sourceField.value = log.request_path || '';
        applyTargetValue(log.target_url || '');
        if (log.redirect_type) {
            typeField.value = String(log.redirect_type);
        }
        const noteParts = [];
        if (log.redirect_notes) {
            noteParts.push(String(log.redirect_notes));
        }
        if (log.referrer_url) {
            noteParts.push('Referrer: ' + log.referrer_url);
        }
        if (log.hit_count) {
            noteParts.push('404-Hits: ' + log.hit_count);
        }
        notesField.value = noteParts.join(' | ');
        modal.show();
        window.setTimeout(function () {
            if ((targetKindField.value || 'manual') === 'manual') {
                targetManualField.focus();
                return;
            }

            typeField.focus();
        }, 120);
    }

    targetKindField.addEventListener('change', function () {
        resetTargetSelectors();
        updateTargetFieldVisibility();
    });

    [targetPageField, targetPostField, targetHubField].forEach(function (field) {
        field.addEventListener('change', syncHiddenTargetValue);
    });

    targetManualField.addEventListener('input', syncHiddenTargetValue);
    targetManualField.addEventListener('change', syncHiddenTargetValue);

    if (hideResolvedToggle) {
        hideResolvedToggle.checked = window.localStorage.getItem(hideResolvedStorageKey) === '1';
        applyResolvedFilter(hideResolvedToggle.checked);

        hideResolvedToggle.addEventListener('change', function () {
            const hideResolved = hideResolvedToggle.checked;
            window.localStorage.setItem(hideResolvedStorageKey, hideResolved ? '1' : '0');
            applyResolvedFilter(hideResolved);
        });
    } else {
        applyResolvedFilter(false);
    }

    if (form) {
        form.addEventListener('submit', function () {
            syncHiddenTargetValue();
        });
    }

    updateTargetFieldVisibility();

    document.querySelectorAll('.js-create-redirect').forEach(function (button) {
        button.addEventListener('click', openCreateModal);
    });

    document.querySelectorAll('.js-edit-redirect').forEach(function (button) {
        button.addEventListener('click', function () {
            try {
                const redirect = JSON.parse(button.dataset.redirect || '{}');
                openEditModal(redirect);
            } catch (error) {
                console.error('Weiterleitung konnte nicht geladen werden.', error);
            }
        });
    });

    document.querySelectorAll('.js-takeover-log').forEach(function (button) {
        button.addEventListener('click', function () {
            try {
                const log = JSON.parse(button.dataset.log || '{}');
                openLogTakeoverModal(log);
            } catch (error) {
                console.error('404-Eintrag konnte nicht übernommen werden.', error);
            }
        });
    });
})();
</script>
