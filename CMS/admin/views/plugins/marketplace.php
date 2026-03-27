<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$pluginMarketplaceConfig = [
    'searchInputId' => 'pluginSearch',
    'categoryFilterId' => 'categoryFilter',
    'statusFilterId' => 'statusFilter',
    'gridSelector' => '#pluginGrid',
    'cardSelector' => '.plugin-card',
    'emptyStateSelector' => '#pluginMarketplaceEmptyState',
];
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
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/plugins" class="btn btn-outline-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Installierte Plugins
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php
            $alertData = is_array($alert ?? null) ? $alert : [];
            require dirname(__DIR__) . '/partials/flash-alert.php';
            ?>
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
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Nur manuell / Anfrage</div>
                        </div>
                        <div class="h1 mb-0 mt-2"><?php echo (int)($data['stats']['manual_only'] ?? 0); ?></div>
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
                    <div class="col-md-4">
                        <select id="statusFilter" class="form-select">
                            <option value="">Alle Stati</option>
                            <option value="installable">Automatisch installierbar</option>
                            <option value="manual">Nur manuell / Anfrage</option>
                            <option value="installed">Bereits installiert</option>
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
                            <div class="text-secondary">Keine Plugins im Marketplace verfügbar. Hinterlege optional eine Registry-URL oder eine lokale `index.json` oberhalb von `/plugins`.</div>
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
                    $purchaseUrl = htmlspecialchars((string)($plugin['purchase_url'] ?? ''));
                    $priceAmount = htmlspecialchars((string)($plugin['price_amount'] ?? ''));
                    $priceCurrency = htmlspecialchars((string)($plugin['price_currency'] ?? 'EUR'));
                    $requiresCms = htmlspecialchars((string)($plugin['requires_cms'] ?? ''));
                    $requiresPhp = htmlspecialchars((string)($plugin['requires_php'] ?? ''));
                    $isInstalled = !empty($plugin['installed']);
                    $isPaid = !empty($plugin['is_paid']);
                    $autoInstallSupported = !empty($plugin['auto_install_supported']);
                    $installReason = htmlspecialchars((string)($plugin['install_reason'] ?? ''));
                    $hashPresent = !empty($plugin['integrity_hash_present']);
                    $compatibilityReason = htmlspecialchars((string)($plugin['compatibility_reason'] ?? ''));
                    $status = $isInstalled ? 'installed' : ($autoInstallSupported ? 'installable' : 'manual');
                ?>
                <div class="col-sm-6 col-lg-4 plugin-card" data-name="<?php echo $name; ?>" data-category="<?php echo $category; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
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
                                    <?php if ($isPaid): ?>
                                        <span class="badge bg-orange-lt">Kostenpflichtig</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="text-secondary"><?php echo $description; ?></p>
                            <?php if ($priceAmount !== ''): ?>
                                <div class="mb-2"><span class="badge bg-orange-lt"><?php echo $priceAmount . ' ' . $priceCurrency; ?></span></div>
                            <?php endif; ?>
                            <?php if ($requiresCms !== '' || $requiresPhp !== ''): ?>
                                <div class="text-muted small mb-2">
                                    <?php if ($requiresCms !== ''): ?>365CMS ab <?php echo $requiresCms; ?><?php endif; ?>
                                    <?php if ($requiresCms !== '' && $requiresPhp !== ''): ?> · <?php endif; ?>
                                    <?php if ($requiresPhp !== ''): ?>PHP ab <?php echo $requiresPhp; ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
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
                                    <form method="POST" style="display:inline;" data-confirm-message="Plugin '<?php echo $name; ?>' installieren?" data-confirm-title="Plugin installieren" data-confirm-text="Installieren" data-confirm-class="btn-primary" data-confirm-status-class="bg-primary">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="install">
                                        <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/><polyline points="7 11 12 16 17 11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                            Installieren
                                        </button>
                                    </form>
                                <?php elseif ($isPaid && $purchaseUrl !== ''): ?>
                                    <div class="text-end">
                                        <a href="<?php echo $purchaseUrl; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm mb-2">
                                            Anfragen / Kaufen
                                        </a>
                                        <?php if ($compatibilityReason !== ''): ?>
                                            <div class="text-warning small mb-2" style="max-width: 220px;"><?php echo $compatibilityReason; ?></div>
                                        <?php endif; ?>
                                        <div class="text-muted small" style="max-width: 220px;"><?php echo $installReason; ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-end">
                                        <span class="badge bg-secondary-lt mb-2">Nur manuell</span>
                                        <?php if ($compatibilityReason !== ''): ?>
                                            <div class="text-warning small mb-2" style="max-width: 220px;"><?php echo $compatibilityReason; ?></div>
                                        <?php endif; ?>
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

        <div class="card d-none mt-4" id="pluginMarketplaceEmptyState">
            <div class="card-body text-center py-5 text-secondary">
                Keine Plugins für den aktuellen Such-/Filterzustand gefunden.
            </div>
        </div>

    </div>
</div>

<script type="application/json" id="plugin-marketplace-config"><?php echo json_encode($pluginMarketplaceConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
