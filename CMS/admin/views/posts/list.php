<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Posts – Listenansicht
 *
 * Erwartet: $data (aus PostsModule::getListData())
 *           $alert (Session-Alert)
 */

$posts      = $data['posts'] ?? [];
$categories = $data['categories'] ?? [];
$counts     = $data['counts'] ?? [];
$filter     = $data['filter'] ?? '';
$catFilter  = $data['catFilter'] ?? 0;
$search     = $data['search'] ?? '';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Beiträge</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neuer Beitrag
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible mb-3" role="alert">
                <div class="d-flex">
                    <div><?php echo htmlspecialchars($alert['message']); ?></div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6l0 13"/><path d="M12 6l0 13"/><path d="M21 6l0 13"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['total'] ?? 0); ?> Beiträge</div>
                                <div class="text-secondary">Gesamt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['published'] ?? 0); ?></div>
                                <div class="text-secondary">Veröffentlicht</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-warning text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M12 10l0 6"/><path d="M9 13l6 0"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['drafts'] ?? 0); ?></div>
                                <div class="text-secondary">Entwürfe</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="statusFilter" onchange="applyFilters()">
                            <option value="">Alle Status</option>
                            <option value="published" <?php if ($filter === 'published') echo 'selected'; ?>>Veröffentlicht</option>
                            <option value="draft" <?php if ($filter === 'draft') echo 'selected'; ?>>Entwurf</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="categoryFilter" onchange="applyFilters()">
                            <option value="0">Alle Kategorien</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php if ($catFilter === (int)$cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><path d="M21 21l-6 -6"/></svg>
                            </span>
                            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Suchen…"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   onkeydown="if(event.key==='Enter')applyFilters()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk-Aktionen -->
            <div class="card-body py-2 d-none" id="bulkBar">
                <form method="post" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="bulk">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary"><strong id="selectedCount">0</strong> ausgewählt</span>
                        <select name="bulk_action" class="form-select form-select-sm w-auto">
                            <option value="">Aktion wählen…</option>
                            <option value="publish">Veröffentlichen</option>
                            <option value="draft">Als Entwurf</option>
                            <option value="delete">Löschen</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Anwenden</button>
                    </div>
                </form>
            </div>

            <!-- Tabelle -->
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                            </th>
                            <th>Titel</th>
                            <th>Kategorie</th>
                            <th>Status</th>
                            <th>Autor</th>
                            <th>Datum</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg mb-2" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6l0 13"/><path d="M12 6l0 13"/><path d="M21 6l0 13"/></svg>
                                <p class="mt-1 mb-0">Keine Beiträge gefunden.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td>
                                    <input class="form-check-input row-check" type="checkbox" name="ids[]" value="<?php echo (int)$p['id']; ?>" form="bulkForm">
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts?action=edit&id=<?php echo (int)$p['id']; ?>" class="text-reset">
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </a>
                                    <div class="text-secondary small">/blog/<?php echo htmlspecialchars($p['slug']); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($p['category_name'])): ?>
                                        <span class="badge bg-azure-lt"><?php echo htmlspecialchars($p['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['status'] === 'published'): ?>
                                        <span class="badge bg-success-lt">Veröffentlicht</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-lt">Entwurf</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars($p['author'] ?? '–'); ?></td>
                                <td class="text-secondary"><?php echo date('d.m.Y', strtotime($p['updated_at'] ?? $p['created_at'])); ?></td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts?action=edit&id=<?php echo (int)$p['id']; ?>" class="btn btn-ghost-primary btn-icon btn-sm" title="Bearbeiten">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                                        </a>
                                        <form method="post" class="d-inline mb-0"
                                              onsubmit="return confirm('Beitrag &bdquo;<?php echo htmlspecialchars($p['title'], ENT_QUOTES); ?>&ldquo; wirklich löschen?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                            <button type="submit" class="btn btn-ghost-danger btn-icon btn-sm" title="Löschen">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            </button>
                                        </form>
                                    </div>
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

<script>
function applyFilters() {
    var s = document.getElementById('statusFilter').value;
    var c = document.getElementById('categoryFilter').value;
    var q = document.getElementById('searchInput').value.trim();
    var url = '<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts?';
    var params = [];
    if (s) params.push('status=' + encodeURIComponent(s));
    if (c && c !== '0') params.push('category=' + encodeURIComponent(c));
    if (q) params.push('q=' + encodeURIComponent(q));
    window.location.href = url + params.join('&');
}

// Select-All & Bulk
(function() {
    var selectAll = document.getElementById('selectAll');
    var bulkBar   = document.getElementById('bulkBar');
    var countEl   = document.getElementById('selectedCount');

    function updateBulk() {
        var checked = document.querySelectorAll('.row-check:checked').length;
        countEl.textContent = checked;
        bulkBar.classList.toggle('d-none', checked === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulk();
        });
    }
    document.querySelectorAll('.row-check').forEach(function(cb) {
        cb.addEventListener('change', updateBulk);
    });

})();
</script>
