<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Plugin Marketplace -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h2>
                <div class="text-secondary mt-1">Verfügbare Plugins durchsuchen und installieren</div>
            </div>
            <div class="col-auto ms-auto">
                <a href="plugins.php" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Installierte Plugins
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($flashMessage)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flashType); ?> alert-dismissible" role="alert">
                <div class="d-flex">
                    <div><?php echo htmlspecialchars($flashMessage); ?></div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Verfügbar</div>
                        </div>
                        <div class="h1 mb-0 mt-2"><?php echo (int)($data['stats']['available'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Installiert</div>
                        </div>
                        <div class="h1 mb-0 mt-2"><?php echo (int)($data['stats']['installed'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Installierbar</div>
                        </div>
                        <div class="h1 mb-0 mt-2"><?php echo (int)($data['stats']['installable'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                            </span>
                            <input type="text" id="pluginSearch" class="form-control" placeholder="Plugin suchen…">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="categoryFilter" class="form-select">
                            <option value="">Alle Kategorien</option>
                            <?php
                            $categories = array_unique(array_filter(array_column($data['plugins'] ?? [], 'category')));
                            sort($categories);
                            foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plugin Grid -->
        <div class="row row-deck row-cards" id="pluginGrid">
            <?php if (empty($data['plugins'])): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="text-secondary">Keine Plugins im Marketplace verfügbar.</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($data['plugins'] as $plugin):
                    $slug        = htmlspecialchars($plugin['slug'] ?? '');
                    $name        = htmlspecialchars($plugin['name'] ?? $slug);
                    $description = htmlspecialchars($plugin['description'] ?? '');
                    $version     = htmlspecialchars($plugin['version'] ?? '-');
                    $author      = htmlspecialchars($plugin['author'] ?? '-');
                    $category    = htmlspecialchars($plugin['category'] ?? '');
                    $isInstalled = !empty($plugin['installed']);
                    $autoInstallSupported = !empty($plugin['auto_install_supported']);
                    $installReason = htmlspecialchars((string)($plugin['install_reason'] ?? ''));
                    $hashPresent = !empty($plugin['integrity_hash_present']);
                ?>
                <div class="col-sm-6 col-lg-4 plugin-card" data-name="<?php echo $name; ?>" data-category="<?php echo $category; ?>">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar bg-primary-lt me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l0 10"/><path d="M20 7l0 10"/><path d="M12 3l0 18"/><path d="M3 17l4 -4l-4 -4"/><path d="M21 17l-4 -4l4 -4"/><path d="M11 7l2 -4l2 4"/></svg>
                                </div>
                                <div>
                                    <h3 class="card-title mb-0"><?php echo $name; ?></h3>
                                    <?php if ($category): ?>
                                        <span class="badge bg-azure-lt"><?php echo $category; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-secondary"><?php echo $description; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    v<?php echo $version; ?> · <?php echo $author; ?>
                                    <?php if ($hashPresent): ?>
                                        <div class="mt-1"><span class="badge bg-green-lt">SHA-256 verifiziert</span></div>
                                    <?php else: ?>
                                        <div class="mt-1"><span class="badge bg-warning-lt">Auto-Install gesperrt</span></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isInstalled): ?>
                                    <span class="badge bg-green-lt">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                        Installiert
                                    </span>
                                <?php elseif ($autoInstallSupported): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="install">
                                        <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Plugin \'<?php echo $name; ?>\' installieren?');">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                            Installieren
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="text-end">
                                        <span class="badge bg-secondary-lt mb-2">Nur manuell</span>
                                        <div class="text-muted small" style="max-width: 220px;"><?php echo $installReason; ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var search = document.getElementById('pluginSearch');
    var filter = document.getElementById('categoryFilter');
    var cards  = document.querySelectorAll('.plugin-card');

    function applyFilter() {
        var q   = (search.value || '').toLowerCase();
        var cat = filter.value;
        cards.forEach(function(card) {
            var name     = (card.dataset.name || '').toLowerCase();
            var category = card.dataset.category || '';
            var matchQ   = !q || name.indexOf(q) !== -1;
            var matchCat = !cat || category === cat;
            card.style.display = (matchQ && matchCat) ? '' : 'none';
        });
    }

    search.addEventListener('input', applyFilter);
    filter.addEventListener('change', applyFilter);
});
</script>
