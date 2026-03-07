<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$redirects = $data['redirects'] ?? [];
$logs      = $data['logs'] ?? [];
$stats     = $data['stats'] ?? [];
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
                <h3 class="card-title">Erkannte 404-Fehler</h3>
                <div class="text-secondary small">Bei Bedarf direkt als 301/302 übernehmbar.</div>
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
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($log['request_path']) ?></code></td>
                                    <td><?= (int)($log['hit_count'] ?? 0) ?></td>
                                    <td><?= !empty($log['last_seen_at']) ? date('d.m.Y H:i', strtotime((string)$log['last_seen_at'])) : '–' ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars($log['referrer_url'] ?? '–') ?></td>
                                    <td>
                                        <?php if (!empty($log['target_url'])): ?>
                                            <span class="badge <?= (int)($log['redirect_is_active'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int)($log['redirect_type'] ?? 301) ?></span>
                                            <span class="text-secondary small"><?= htmlspecialchars($log['target_url']) ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary">Keine</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary js-takeover-log"
                                            data-log="<?= htmlspecialchars(json_encode($log, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>"
                                        >Übernehmen</button>
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
                        <label class="form-label">Ziel-URL</label>
                        <input type="text" name="target_url" id="redirect-target" class="form-control" placeholder="/neuer-pfad oder https://..." required>
                    </div>
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
    const targetField = document.getElementById('redirect-target');
    const typeField = document.getElementById('redirect-type');
    const notesField = document.getElementById('redirect-notes');
    const activeField = document.getElementById('redirect-active');

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
        targetField.value = '';
        typeField.value = '301';
        notesField.value = '';
        activeField.checked = true;
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
        targetField.value = redirect.target_url || '';
        typeField.value = String(redirect.redirect_type || 301);
        notesField.value = redirect.notes || '';
        activeField.checked = String(redirect.is_active || '0') === '1' || redirect.is_active === 1;
        modal.show();
        window.setTimeout(function () {
            targetField.focus();
        }, 120);
    }

    function openLogTakeoverModal(log) {
        const modal = getModalInstance();
        if (!modal) {
            return;
        }

        resetRedirectForm();
        titleElement.textContent = '404-Fehler übernehmen';
        sourceField.value = log.request_path || '';
        if (log.target_url) {
            targetField.value = log.target_url;
        }
        if (log.redirect_type) {
            typeField.value = String(log.redirect_type);
        }
        const noteParts = [];
        if (log.referrer_url) {
            noteParts.push('Referrer: ' + log.referrer_url);
        }
        if (log.hit_count) {
            noteParts.push('404-Hits: ' + log.hit_count);
        }
        notesField.value = noteParts.join(' | ');
        modal.show();
        window.setTimeout(function () {
            targetField.focus();
        }, 120);
    }

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
