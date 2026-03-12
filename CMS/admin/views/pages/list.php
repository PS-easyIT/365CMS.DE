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

            <div class="card-body py-2 d-none" id="bulkBarPages">
                <form method="post" id="bulkFormPages" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="bulk">
                    <span class="text-secondary"><strong id="selectedCountPages">0</strong> ausgewählt</span>
                    <select name="bulk_action" class="form-select form-select-sm w-auto">
                        <option value="">Aktion wählen…</option>
                        <option value="publish">Veröffentlichen</option>
                        <option value="draft">Als Entwurf setzen</option>
                        <option value="delete">Löschen</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Anwenden</button>
                </form>
            </div>

            <div class="card-body">
                <div id="pagesGrid"></div>
            </div>
        </div>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

<script>
(function() {
    var gridRoot = document.getElementById('pagesGrid');
    var bulkForm = document.getElementById('bulkFormPages');
    var bulkBar = document.getElementById('bulkBarPages');
    var countEl = document.getElementById('selectedCountPages');
    var selectedIds = new Set();

    if (!gridRoot || !bulkForm || !bulkBar || !countEl) {
        return;
    }

    function syncInputs() {
        gridRoot.querySelectorAll('.bulk-row-check').forEach(function(checkbox) {
            checkbox.checked = selectedIds.has(String(checkbox.value));
        });

        var allCheckboxes = Array.prototype.slice.call(gridRoot.querySelectorAll('.bulk-row-check'));
        var selectAll = gridRoot.querySelector('.bulk-select-all');
        if (selectAll) {
            selectAll.checked = allCheckboxes.length > 0 && allCheckboxes.every(function(checkbox) {
                return checkbox.checked;
            });
        }
    }

    function updateBulkState() {
        countEl.textContent = String(selectedIds.size);
        bulkBar.classList.toggle('d-none', selectedIds.size === 0);
        syncInputs();
    }

    gridRoot.addEventListener('change', function(event) {
        var target = event.target;

        if (target.classList.contains('bulk-row-check')) {
            if (target.checked) {
                selectedIds.add(String(target.value));
            } else {
                selectedIds.delete(String(target.value));
            }
            updateBulkState();
            return;
        }

        if (target.classList.contains('bulk-select-all')) {
            gridRoot.querySelectorAll('.bulk-row-check').forEach(function(checkbox) {
                checkbox.checked = target.checked;
                if (target.checked) {
                    selectedIds.add(String(checkbox.value));
                } else {
                    selectedIds.delete(String(checkbox.value));
                }
            });
            updateBulkState();
        }
    });

    bulkForm.addEventListener('submit', function(event) {
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function(input) {
            input.remove();
        });

        if (selectedIds.size === 0) {
            event.preventDefault();
            return;
        }

        selectedIds.forEach(function(id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            bulkForm.appendChild(input);
        });
    });

    var observer = new MutationObserver(function() {
        syncInputs();
    });

    observer.observe(gridRoot, { childList: true, subtree: true });
    updateBulkState();
})();
</script>

