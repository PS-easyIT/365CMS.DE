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
    var url = '<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts?';
    var params = [];
    if (s) params.push('status=' + encodeURIComponent(s));
    if (c && c !== '0') params.push('category=' + encodeURIComponent(c));
    if (q) params.push('q=' + encodeURIComponent(q));
    window.location.href = url + params.join('&');
}

</script>
