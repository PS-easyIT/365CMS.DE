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

            <!-- Bulk-Leiste -->
            <div class="card-body border-bottom py-2 d-none" id="bulkBar">
                <form method="post" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="bulk">
                    <input type="hidden" name="bulk_ids" id="bulkIds" value="">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary"><strong id="bulkCount">0</strong> ausgewählt</span>
                        <select name="bulk_action" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Aktion wählen…</option>
                            <option value="publish">Veröffentlichen</option>
                            <option value="draft">Als Entwurf</option>
                            <option value="delete">Löschen</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Ausführen</button>
                    </div>
                </form>
            </div>

            <!-- Tabelle -->
            <?php if (!empty($pages)): ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">
                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                </th>
                                <th>Titel</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Autor</th>
                                <th>Aktualisiert</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-check"
                                               value="<?= (int)$page->id ?>">
                                    </td>
                                    <td>
                                        <a href="<?= $siteUrl ?>/admin/pages?action=edit&id=<?= (int)$page->id ?>"
                                           class="text-reset fw-medium">
                                            <?= htmlspecialchars($page->title) ?>
                                        </a>
                                    </td>
                                    <td class="text-secondary">/<?= htmlspecialchars($page->slug ?? '') ?></td>
                                    <td>
                                        <?php
                                        $s = $statusLabels[$page->status] ?? ['Unbekannt', 'bg-secondary'];
                                        ?>
                                        <span class="badge <?= $s[1] ?>"><?= $s[0] ?></span>
                                    </td>
                                    <td class="text-secondary"><?= htmlspecialchars($page->author ?? '–') ?></td>
                                    <td class="text-secondary"><?= htmlspecialchars($page->updated_at ?? $page->created_at ?? '') ?></td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <a href="<?= $siteUrl ?>/admin/pages?action=edit&id=<?= (int)$page->id ?>"
                                               class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                            <form method="post" class="d-inline mb-0"
                                                  onsubmit="return confirm('Seite &bdquo;<?= htmlspecialchars($page->title, ENT_QUOTES) ?>&ldquo; wirklich löschen?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$page->id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Löschen
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="card-body">
                    <div class="empty">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/></svg>
                        </div>
                        <p class="empty-title">Keine Seiten vorhanden</p>
                        <p class="empty-subtitle text-secondary">Erstellen Sie Ihre erste Seite.</p>
                        <div class="empty-action">
                            <a href="<?= $siteUrl ?>/admin/pages?action=edit" class="btn btn-primary">
                                Neue Seite erstellen
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select All
    const selectAll = document.getElementById('selectAll');
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    const bulkIds   = document.getElementById('bulkIds');

    function updateBulk() {
        const checked = document.querySelectorAll('.row-check:checked');
        const count = checked.length;
        bulkBar.classList.toggle('d-none', count === 0);
        bulkCount.textContent = count;
        bulkIds.value = Array.from(checked).map(c => c.value).join(',');
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(cb => { cb.checked = this.checked; });
            updateBulk();
        });
    }

    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', updateBulk);
    });

    // Bulk-Form Submit
    const bulkForm = document.getElementById('bulkForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const action = bulkForm.querySelector('[name="bulk_action"]').value;
            if (!action) { e.preventDefault(); return; }
            if (action === 'delete') {
                e.preventDefault();
                cmsConfirm({
                    title: 'Seiten löschen?',
                    message: bulkCount.textContent + ' Seite(n) endgültig löschen?',
                    confirmText: 'Löschen',
                    onConfirm: function() { bulkForm.submit(); }
                });
            }
        });
    }
});
</script>
