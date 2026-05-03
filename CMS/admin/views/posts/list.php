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

$posts      = is_array($data['posts'] ?? null) ? $data['posts'] : [];
$categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
$counts     = is_array($data['counts'] ?? null) ? $data['counts'] : [];
$filter     = (string)($data['filter'] ?? '');
$catFilter  = (int)($data['catFilter'] ?? 0);
$search     = (string)($data['search'] ?? '');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Beiträge</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="/admin/posts?action=edit" class="btn btn-primary">
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
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
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
            <div class="col-sm-6 col-lg-3">
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
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-azure text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 7v5l3 3"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['scheduled'] ?? 0); ?></div>
                                <div class="text-secondary">Geplant</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
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
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-purple text-white avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 13m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M13 13m0 2a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M11 15h2"/><path d="M7 15h4"/><path d="M7 15v-6a5 5 0 0 1 10 0v6"/></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['private'] ?? 0); ?></div>
                                <div class="text-secondary">Privat</div>
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
                            <option value="scheduled" <?php if ($filter === 'scheduled') echo 'selected'; ?>>Geplant</option>
                            <option value="draft" <?php if ($filter === 'draft') echo 'selected'; ?>>Entwurf</option>
                            <option value="private" <?php if ($filter === 'private') echo 'selected'; ?>>Privat</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="categoryFilter" onchange="applyFilters()">
                            <option value="0">Alle Kategorien</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php if ($catFilter === (int)$cat['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars((string) ($cat['option_label'] ?? $cat['name'] ?? '')); ?>
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

            <div class="card-body py-2 d-none" id="bulkBarPosts">
                <form method="post" id="bulkFormPosts" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="bulk">
                    <span class="text-secondary"><strong id="selectedCountPosts">0</strong> ausgewählt</span>
                    <label for="bulkActionPosts" class="form-label mb-0 small text-secondary">Aktion</label>
                    <select name="bulk_action" id="bulkActionPosts" class="form-select form-select-sm w-auto">
                        <option value="">Aktion wählen…</option>
                        <option value="publish">Veröffentlichen</option>
                        <option value="draft">Als Entwurf setzen</option>
                        <option value="set_category">Kategorie(n) setzen</option>
                        <option value="clear_category">Kategorie entfernen</option>
                        <option value="set_author_display_name">Autoren-Anzeigenamen setzen</option>
                        <option value="clear_author_display_name">Autoren-Anzeigenamen zurücksetzen</option>
                        <option value="delete">Löschen</option>
                    </select>
                    <select name="bulk_category_ids[]" id="bulkCategoryPosts" class="form-select form-select-sm w-auto d-none" multiple size="6" aria-label="Bulk-Kategorien auswählen">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int) ($cat['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($cat['option_label'] ?? $cat['name'] ?? '')); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text"
                           name="bulk_author_display_name"
                           id="bulkAuthorDisplayNamePosts"
                           class="form-control form-control-sm w-auto d-none"
                           maxlength="150"
                           placeholder="Neuer Anzeigename für ausgewählte Beiträge">
                    <button type="submit" id="bulkSubmitPosts" class="btn btn-sm btn-primary" disabled>Anwenden</button>
                </form>
            </div>

            <div class="card-body">
                <div id="postsGrid"></div>
            </div>
        </div>

    </div>
</div>

<script>
function applyFilters() {
    var s = document.getElementById('statusFilter').value;
    var c = document.getElementById('categoryFilter').value;
    var q = document.getElementById('searchInput').value.trim();
    var url = '/admin/posts?';
    var params = [];
    if (s) params.push('status=' + encodeURIComponent(s));
    if (c && c !== '0') params.push('category=' + encodeURIComponent(c));
    if (q) params.push('q=' + encodeURIComponent(q));
    window.location.href = url + params.join('&');
}

(function() {
    var gridRoot = document.getElementById('postsGrid');
    var bulkForm = document.getElementById('bulkFormPosts');
    var bulkBar = document.getElementById('bulkBarPosts');
    var countEl = document.getElementById('selectedCountPosts');
    var bulkActionSelect = bulkForm ? bulkForm.querySelector('[name="bulk_action"]') : null;
    var bulkAuthorDisplayName = document.getElementById('bulkAuthorDisplayNamePosts');
    var bulkCategorySelect = document.getElementById('bulkCategoryPosts');
    var bulkSubmit = document.getElementById('bulkSubmitPosts');
    var selectedIds = new Set();

    if (!gridRoot || !bulkForm || !bulkBar || !countEl) {
        return;
    }

    function bulkSubmitMeta(action) {
        switch (action) {
            case 'publish':
                return { text: 'Beiträge veröffentlichen', className: 'btn btn-sm btn-primary' };
            case 'draft':
                return { text: 'Als Entwurf setzen', className: 'btn btn-sm btn-warning' };
            case 'set_category':
                return { text: 'Kategorien setzen', className: 'btn btn-sm btn-primary' };
            case 'clear_category':
                return { text: 'Kategorien entfernen', className: 'btn btn-sm btn-outline-secondary' };
            case 'set_author_display_name':
                return { text: 'Autorenname setzen', className: 'btn btn-sm btn-primary' };
            case 'clear_author_display_name':
                return { text: 'Autorenname zurücksetzen', className: 'btn btn-sm btn-outline-secondary' };
            case 'delete':
                return { text: 'Beiträge löschen', className: 'btn btn-sm btn-danger' };
            default:
                return { text: 'Anwenden', className: 'btn btn-sm btn-primary' };
        }
    }

    function updateBulkActionUi() {
        if (!bulkActionSelect || !bulkAuthorDisplayName || !bulkCategorySelect) {
            return;
        }

        var requiresName = bulkActionSelect.value === 'set_author_display_name';
        var requiresCategory = bulkActionSelect.value === 'set_category';
        bulkAuthorDisplayName.classList.toggle('d-none', !requiresName);
        bulkAuthorDisplayName.required = requiresName;
        bulkCategorySelect.classList.toggle('d-none', !requiresCategory);

        if (!requiresName) {
            bulkAuthorDisplayName.value = '';
        }
        if (!requiresCategory) {
            Array.prototype.forEach.call(bulkCategorySelect.options, function(option) {
                option.selected = false;
            });
        }

        updateBulkSubmitState();
    }

    function syncInputs() {
        getRowCheckboxes().forEach(function(checkbox) {
            checkbox.checked = selectedIds.has(String(checkbox.value));
        });

        var allCheckboxes = getRowCheckboxes();
        var selectAll = gridRoot.querySelector('.bulk-select-all');
        if (selectAll) {
            var checkedCount = allCheckboxes.filter(function(checkbox) {
                return checkbox.checked;
            }).length;

            selectAll.checked = allCheckboxes.length > 0 && checkedCount === allCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < allCheckboxes.length;

            if (allCheckboxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            }
        }
    }

    function updateBulkState() {
        countEl.textContent = String(selectedIds.size);
        bulkBar.classList.toggle('d-none', selectedIds.size === 0);
        updateBulkSubmitState();
        syncInputs();
    }

    function updateBulkSubmitState() {
        if (!bulkSubmit || !bulkActionSelect) {
            return;
        }

        var action = bulkActionSelect.value || '';
        var requiresName = action === 'set_author_display_name';
        var requiresCategory = action === 'set_category';
        var nameReady = !requiresName || (bulkAuthorDisplayName && bulkAuthorDisplayName.value.trim() !== '');
        var categoryReady = !requiresCategory || (bulkCategorySelect && bulkCategorySelect.selectedOptions.length > 0);
        var meta = bulkSubmitMeta(action);

        bulkSubmit.textContent = meta.text;
        bulkSubmit.className = meta.className;
        bulkSubmit.disabled = selectedIds.size === 0 || action === '' || !nameReady || !categoryReady;
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

        if (bulkActionSelect && !bulkActionSelect.value) {
            event.preventDefault();
            bulkActionSelect.focus();
            return;
        }

        if (bulkActionSelect && bulkActionSelect.value === 'set_author_display_name' && bulkAuthorDisplayName && !bulkAuthorDisplayName.value.trim()) {
            event.preventDefault();
            bulkAuthorDisplayName.focus();
            return;
        }

        if (bulkActionSelect && bulkActionSelect.value === 'set_category' && bulkCategorySelect && bulkCategorySelect.selectedOptions.length === 0) {
            event.preventDefault();
            bulkCategorySelect.focus();
            return;
        }

        if (bulkActionSelect && bulkActionSelect.value === 'delete') {
            var deleteMessage = selectedIds.size === 1
                ? 'Soll der ausgewählte Beitrag wirklich gelöscht werden?'
                : 'Sollen die ' + selectedIds.size + ' ausgewählten Beiträge wirklich gelöscht werden?';

            if (!window.confirm(deleteMessage)) {
                event.preventDefault();
                return;
            }
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
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', updateBulkActionUi);
    }
    if (bulkAuthorDisplayName) {
        bulkAuthorDisplayName.addEventListener('input', updateBulkSubmitState);
    }
    if (bulkCategorySelect) {
        bulkCategorySelect.addEventListener('change', updateBulkSubmitState);
    }
    updateBulkActionUi();
    updateBulkState();
})();
</script>
