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
$statusLabels = [
    'published' => ['Veröffentlicht', 'bg-green-lt text-green'],
    'scheduled' => ['Geplant', 'bg-azure-lt text-azure'],
    'draft'     => ['Entwurf', 'bg-yellow-lt text-yellow'],
    'private'   => ['Privat', 'bg-purple-lt text-purple'],
];
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

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1">
                                <input class="form-check-input" type="checkbox" id="postsSelectAll" aria-label="Alle Beiträge auswählen">
                            </th>
                            <th>Titel</th>
                            <th>Slug</th>
                            <th>Kategorie</th>
                            <th>Status</th>
                            <th>Autor</th>
                            <th>Aktualisiert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($posts)): ?>
                        <?php
                        $emptyStateColspan = 8;
                        $emptyStateMessage = 'Keine Beiträge gefunden.';
                        $emptyStateSubtitle = 'Prüfen Sie Filter oder Suche – die serverseitige Liste liefert aktuell keine Einträge.';
                        $emptyStateIcon = 'file-text';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <?php
                            $postId = (int)($post['id'] ?? 0);
                            $postTitle = trim((string)($post['title'] ?? $post['title_en'] ?? ''));
                            $postSlug = trim((string)($post['slug'] ?? $post['slug_en'] ?? ''));
                            $postSlugEn = trim((string)($post['slug_en'] ?? ''));
                            $postStatus = (string)($post['status'] ?? 'draft');
                            $publishedAt = trim((string)($post['published_at'] ?? ''));
                            if ($postStatus === 'published' && $publishedAt !== '') {
                                $publishedTimestamp = strtotime($publishedAt);
                                if ($publishedTimestamp !== false && $publishedTimestamp > time()) {
                                    $postStatus = 'scheduled';
                                }
                            }
                            [$postStatusLabel, $postStatusClass] = $statusLabels[$postStatus] ?? [$postStatus, 'bg-secondary-lt text-secondary'];
                            $postCategory = trim((string)($post['category_name'] ?? ''));
                            $postAuthor = trim((string)($post['author'] ?? ''));
                            $postUpdatedAt = trim((string)($post['updated_at'] ?? $post['created_at'] ?? ''));
                            $postUpdatedAtLabel = $postUpdatedAt;
                            if ($postUpdatedAt !== '') {
                                $postUpdatedTimestamp = strtotime($postUpdatedAt);
                                if ($postUpdatedTimestamp !== false) {
                                    $postUpdatedAtLabel = date('d.m.Y H:i', $postUpdatedTimestamp);
                                }
                            }
                            $postHasEnglishVariant = $postSlugEn !== '' && $postSlugEn !== $postSlug;
                            ?>
                            <tr>
                                <td>
                                    <input class="form-check-input" type="checkbox" name="ids[]" value="<?php echo $postId; ?>" form="bulkFormPosts">
                                </td>
                                <td>
                                    <a href="/admin/posts?action=edit&id=<?php echo $postId; ?>" class="text-reset fw-medium">
                                        <?php echo htmlspecialchars($postTitle !== '' ? $postTitle : 'Ohne Titel', ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <?php if ($postHasEnglishVariant): ?>
                                        <span class="badge bg-secondary-lt ms-2">EN</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary">
                                    /blog/<?php echo htmlspecialchars($postSlug, ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($postHasEnglishVariant): ?>
                                        <div class="small mt-1">/en/blog/<?php echo htmlspecialchars($postSlugEn, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($postCategory !== ''): ?>
                                        <span class="badge bg-azure-lt"><?php echo htmlspecialchars($postCategory, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?php echo htmlspecialchars($postStatusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($postStatusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($postAuthor !== '' ? $postAuthor : '–', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($postUpdatedAtLabel !== '' ? $postUpdatedAtLabel : '–', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="table-actions">
                                    <div class="table-row-actions table-row-actions--icons">
                                        <a href="/admin/posts?action=edit&id=<?php echo $postId; ?>" class="btn btn-outline-primary btn-sm btn-icon" aria-label="Beitrag bearbeiten" title="Bearbeiten">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v10H7z" opacity="0"/><path d="M16.474 5.408a2.077 2.077 0 1 1 2.937 2.937l-9.19 9.19a6 6 0 0 1 -2.52 1.51l-2.093 .698l.698 -2.093a6 6 0 0 1 1.51 -2.52z"/><path d="M14.474 7.408l2.118 2.118"/></svg>
                                        </a>
                                        <form method="post" action="/admin/posts" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $postId; ?>">
                                            <button type="button" class="btn btn-ghost-danger btn-sm btn-icon" aria-label="Beitrag löschen" title="Löschen" onclick="cmsConfirm({title:'Beitrag löschen?',message:'Dieser Beitrag wird unwiderruflich gelöscht.',confirmText:'Löschen',confirmClass:'btn-danger',onConfirm:()=>cmsSubmitFormSafely(this.closest('form'))})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7l1 -3h4l1 3"/></svg>
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
    var url = '/admin/posts?';
    var params = [];
    if (s) params.push('status=' + encodeURIComponent(s));
    if (c && c !== '0') params.push('category=' + encodeURIComponent(c));
    if (q) params.push('q=' + encodeURIComponent(q));
    window.location.href = url + params.join('&');
}

(function() {
    var bulkForm = document.getElementById('bulkFormPosts');
    var bulkBar = document.getElementById('bulkBarPosts');
    var countEl = document.getElementById('selectedCountPosts');
    var bulkActionSelect = bulkForm ? bulkForm.querySelector('[name="bulk_action"]') : null;
    var bulkAuthorDisplayName = document.getElementById('bulkAuthorDisplayNamePosts');
    var bulkCategorySelect = document.getElementById('bulkCategoryPosts');
    var bulkSubmit = document.getElementById('bulkSubmitPosts');
    var selectAll = document.getElementById('postsSelectAll');

    if (!bulkForm || !bulkBar || !countEl) {
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

    function getRowCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('input[name="ids[]"][form="bulkFormPosts"]'));
    }

    function updateBulkState() {
        var rowCheckboxes = getRowCheckboxes();
        var checkedCount = rowCheckboxes.filter(function(checkbox) { return checkbox.checked; }).length;

        countEl.textContent = String(checkedCount);
        bulkBar.classList.toggle('d-none', checkedCount === 0);
        updateBulkSubmitState();

        if (selectAll) {
            selectAll.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        }
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
        var selectedCount = getRowCheckboxes().filter(function(checkbox) { return checkbox.checked; }).length;
        bulkSubmit.disabled = selectedCount === 0 || action === '' || !nameReady || !categoryReady;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            getRowCheckboxes().forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    getRowCheckboxes().forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkState);
    });

    bulkForm.addEventListener('submit', function(event) {
        var checkedBoxes = getRowCheckboxes().filter(function(checkbox) { return checkbox.checked; });

        if (checkedBoxes.length === 0) {
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
            var deleteMessage = checkedBoxes.length === 1
                ? 'Soll der ausgewählte Beitrag wirklich gelöscht werden?'
                : 'Sollen die ' + checkedBoxes.length + ' ausgewählten Beiträge wirklich gelöscht werden?';

            if (!window.confirm(deleteMessage)) {
                event.preventDefault();
                return;
            }
        }
    });

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
