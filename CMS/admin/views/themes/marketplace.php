<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Theme Marketplace
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$catalog = $data['catalog'] ?? [];
$total   = (int) ($data['total'] ?? 0);
$stats = is_array($data['stats'] ?? null) ? $data['stats'] : [];
$filters = is_array($data['filters'] ?? null) ? $data['filters'] : [];
$statusOptions = is_array($filters['statuses'] ?? null) ? $filters['statuses'] : [];
$source = is_array($data['source'] ?? null) ? $data['source'] : [];
$constraints = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$allowedHosts = is_array($constraints['allowed_marketplace_hosts'] ?? null) ? $constraints['allowed_marketplace_hosts'] : [];
$allowedArchiveExtensions = is_array($constraints['allowed_archive_extensions'] ?? null) ? $constraints['allowed_archive_extensions'] : [];
$themeMarketplaceConfig = [
    'searchInputId' => 'themeMarketplaceSearch',
    'statusFilterId' => 'themeMarketplaceStatusFilter',
    'cardSelector' => '.theme-marketplace-card',
    'emptyStateSelector' => '#themeMarketplaceEmptyState',
    'installFormSelector' => 'form[data-theme-marketplace-install-form]',
];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$normalizeSearchName = static function (mixed $value): string {
    $string = (string) $value;

    if (function_exists('mb_strtolower')) {
        return (string) mb_strtolower($string, 'UTF-8');
    }

    return strtolower($string);
};
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Theme Marketplace</h2>
            <div class="text-muted mt-1"><?php echo $total; ?> Theme<?php echo $total !== 1 ? 's' : ''; ?> verfügbar</div>
        </div>
        <div class="ms-auto">
            <a href="/admin/themes" class="btn btn-outline-primary">
                Zur Theme-Verwaltung
            </a>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>

    <?php if (!empty($source['message'])): ?>
        <?php
        $alertData = [
            'type' => ($source['status'] ?? 'info') === 'success' ? 'success' : (($source['status'] ?? 'info') === 'warning' ? 'warning' : 'info'),
            'message' => (string) ($source['message'] ?? ''),
            'details' => array_values(array_filter(array_merge(
                !empty($source['url']) ? ['Quelle: ' . (string) $source['url']] : [],
                is_array($source['details'] ?? null) ? array_map(static fn (mixed $detail): string => (string) $detail, $source['details']) : []
            ))),
        ];
        $alertMarginClass = 'mb-3';
        require dirname(__DIR__) . '/partials/flash-alert.php';
        ?>
    <?php endif; ?>

    <?php if (empty($catalog)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-packages mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M2 13.5v5.5l5 3"/><path d="M7 16.5l5 -3"/><path d="M12 19v5.5"/><path d="M17 16.5l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M12 13.5v5.5l5 3"/><path d="M17 16.5l5 -3"/><path d="M12 8l-5 -3l5 -3l5 3v5.5l-5 3z"/><path d="M7 5v5.5l5 3"/><path d="M12 8l5 -3"/></svg>
                <h3>Kein Theme-Katalog verfügbar</h3>
                <p class="text-muted">Die index.json wurde nicht gefunden oder enthält keine Themes.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Verfügbar</div>
                        <div class="h1 mb-0 mt-2"><?php echo $total; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Automatisch installierbar</div>
                        <div class="h1 mb-0 mt-2"><?php echo (int) ($stats['installable'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Nur manuell / Anfrage</div>
                        <div class="h1 mb-0 mt-2"><?php echo (int) ($stats['manual_only'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Aktiv</div>
                        <div class="h1 mb-0 mt-2"><?php echo (int) ($stats['active'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <?php if (!empty($constraints['package_size_limit_label']) || !empty($constraints['catalog_cache_ttl'])): ?>
                    <div class="small text-muted mb-3">
                        <?php if (!empty($constraints['package_size_limit_label'])): ?>
                            Auto-Install bis <?php echo $escape((string) ($constraints['package_size_limit_label'] ?? '')); ?> Paketgröße
                        <?php endif; ?>
                        <?php if (!empty($constraints['package_size_limit_label']) && !empty($constraints['catalog_cache_ttl'])): ?>
                            ·
                        <?php endif; ?>
                        <?php if (!empty($constraints['catalog_cache_ttl'])): ?>
                            Katalog-Cache: <?php echo (int) ($constraints['catalog_cache_ttl'] ?? 0); ?> Sekunden TTL
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($allowedHosts !== [] || !empty($constraints['catalog_max_bytes_label']) || !empty($constraints['archive_uncompressed_limit_label'])): ?>
                    <div class="small text-muted mb-3">
                        <?php if ($allowedHosts !== []): ?>
                            Erlaubte Hosts: <?php echo $escape(implode(', ', $allowedHosts)); ?>
                        <?php endif; ?>
                        <?php if ($allowedHosts !== [] && (!empty($constraints['catalog_max_bytes_label']) || !empty($constraints['archive_uncompressed_limit_label']))): ?>
                            <br>
                        <?php endif; ?>
                        <?php if (!empty($constraints['catalog_max_bytes_label'])): ?>
                            Katalog bis <?php echo $escape((string) ($constraints['catalog_max_bytes_label'] ?? '')); ?>
                        <?php endif; ?>
                        <?php if (!empty($constraints['catalog_max_bytes_label']) && !empty($constraints['manifest_max_bytes_label'])): ?>
                            ·
                        <?php endif; ?>
                        <?php if (!empty($constraints['manifest_max_bytes_label'])): ?>
                            Manifest bis <?php echo $escape((string) ($constraints['manifest_max_bytes_label'] ?? '')); ?>
                        <?php endif; ?>
                        <?php if ((!empty($constraints['catalog_max_bytes_label']) || !empty($constraints['manifest_max_bytes_label'])) && (!empty($constraints['archive_uncompressed_limit_label']) || !empty($constraints['archive_entries_limit']))): ?>
                            ·
                        <?php endif; ?>
                        <?php if (!empty($constraints['archive_uncompressed_limit_label'])): ?>
                            Archiv entpackt bis <?php echo $escape((string) ($constraints['archive_uncompressed_limit_label'] ?? '')); ?>
                        <?php endif; ?>
                        <?php if (!empty($constraints['archive_uncompressed_limit_label']) && !empty($constraints['archive_entries_limit'])): ?>
                            ·
                        <?php endif; ?>
                        <?php if (!empty($constraints['archive_entries_limit'])): ?>
                            max. <?php echo (int) ($constraints['archive_entries_limit'] ?? 0); ?> Archiveinträge
                        <?php endif; ?>
                        <?php if ($allowedArchiveExtensions !== []): ?>
                            <br>Erlaubte Archiv-Endungen: <?php echo $escape(implode(', ', $allowedArchiveExtensions)); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="10" cy="10" r="7"/><line x1="21" y1="21" x2="15" y2="15"/></svg>
                            </span>
                            <input type="text" id="themeMarketplaceSearch" class="form-control" placeholder="Theme suchen…">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select id="themeMarketplaceStatusFilter" class="form-select">
                            <option value="">Alle Stati</option>
                            <?php foreach ($statusOptions as $statusOption): ?>
                                <option value="<?php echo $escape($statusOption['value'] ?? ''); ?>"><?php echo $escape($statusOption['label'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-deck row-cards">
            <?php foreach ($catalog as $theme):
                $slug = $theme['slug'] ?? '';
                $name = $theme['name'] ?? $slug;
                $author = (string) ($theme['author'] ?? '');
                $purchaseUrl = (string) ($theme['purchase_url'] ?? '');
                $isPaid = !empty($theme['is_paid']);
                $hashPresent = !empty($theme['integrity_hash_present']);
                $hashShort = (string) ($theme['integrity_hash_short'] ?? '');
                $packageSizeLabel = (string) ($theme['package_size_label'] ?? '');
                $downloadHost = (string) ($theme['download_host'] ?? '');
                $downloadHostAllowed = !empty($theme['download_host_allowed']);
                $downloadExtensionAllowed = !empty($theme['download_extension_allowed']);
                $packageSizeAllowed = !empty($theme['package_size_allowed']);
                $compatibilityReason = (string) ($theme['compatibility_reason'] ?? '');
                $searchText = trim($normalizeSearchName(implode(' ', array_filter([$name, $slug, $author]))));
                $status = !empty($theme['active'])
                    ? 'active'
                    : (!empty($theme['installed'])
                        ? 'installed'
                        : (!empty($theme['install_supported']) ? 'installable' : 'manual'));
            ?>
                <div class="col-sm-6 col-lg-4 theme-marketplace-card"
                     data-name="<?php echo $escape($searchText); ?>"
                     data-status="<?php echo $escape($status); ?>">
                    <div class="card">
                        <div class="card-img-top" style="height: 180px; background: var(--tblr-bg-surface-secondary); display: flex; align-items: center; justify-content: center;">
                            <?php if (!empty($theme['screenshot'])): ?>
                                <img src="<?php echo $escape($theme['screenshot']); ?>" alt="<?php echo $escape($name); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-palette" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25"/><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <h3 class="card-title mb-0"><?php echo $escape($name); ?></h3>
                                <?php if (!empty($theme['active'])): ?>
                                    <span class="badge bg-primary ms-2">Aktiv</span>
                                <?php elseif (!empty($theme['installed'])): ?>
                                    <span class="badge bg-green ms-2">Installiert</span>
                                <?php endif; ?>
                                <?php if (!empty($theme['updateAvailable'])): ?>
                                    <span class="badge bg-warning ms-2">Update</span>
                                <?php endif; ?>
                                <?php if ($isPaid): ?>
                                    <span class="badge bg-orange-lt ms-2">Kostenpflichtig</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($theme['description'])): ?>
                                <p class="text-muted small mb-2"><?php echo $escape($theme['description']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($theme['price_amount'])): ?>
                                <div class="mb-2"><span class="badge bg-orange-lt"><?php echo $escape((string) $theme['price_amount']); ?> <?php echo $escape((string) ($theme['price_currency'] ?? 'EUR')); ?></span></div>
                            <?php endif; ?>
                            <div class="text-muted small">
                                <?php if (!empty($theme['version'])): ?>
                                    <span class="me-2">v<?php echo $escape($theme['version']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($theme['author'])): ?>
                                    <span><?php echo $escape($theme['author']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($packageSizeLabel !== '' || $downloadHost !== ''): ?>
                                <div class="text-muted small mt-2">
                                    <?php if ($packageSizeLabel !== ''): ?>Paket: <?php echo $escape($packageSizeLabel); ?><?php endif; ?>
                                    <?php if ($packageSizeLabel !== '' && $downloadHost !== ''): ?> · <?php endif; ?>
                                    <?php if ($downloadHost !== ''): ?>Host: <?php echo $escape($downloadHost); ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($hashPresent): ?>
                                <div class="mt-2"><span class="badge bg-green-lt text-success">SHA-256 <?php echo $escape($hashShort); ?></span></div>
                            <?php else: ?>
                                <div class="mt-2"><span class="badge bg-warning-lt text-warning">Auto-Install gesperrt</span></div>
                            <?php endif; ?>
                            <?php if (!$packageSizeAllowed): ?>
                                <div class="mt-2"><span class="badge bg-warning-lt text-warning">Paket zu groß für Auto-Install</span></div>
                            <?php endif; ?>
                            <?php if ($downloadHost !== '' && !$downloadHostAllowed): ?>
                                <div class="mt-2"><span class="badge bg-warning-lt text-warning">Host nicht freigegeben</span></div>
                            <?php endif; ?>
                            <?php if ($downloadHost !== '' && !$downloadExtensionAllowed): ?>
                                <div class="mt-2"><span class="badge bg-warning-lt text-warning">Archiv-Endung nicht erlaubt</span></div>
                            <?php endif; ?>
                            <?php if (!empty($theme['requires_cms']) || !empty($theme['requires_php'])): ?>
                                <div class="text-muted small mt-2">
                                    <?php if (!empty($theme['requires_cms'])): ?>365CMS ab <?php echo $escape((string) $theme['requires_cms']); ?><?php endif; ?>
                                    <?php if (!empty($theme['requires_cms']) && !empty($theme['requires_php'])): ?> · <?php endif; ?>
                                    <?php if (!empty($theme['requires_php'])): ?>PHP ab <?php echo $escape((string) $theme['requires_php']); ?><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <?php if (empty($theme['installed'])): ?>
                                <?php if (!empty($theme['install_supported'])): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-green-lt text-success">SHA-256 verifiziert</span>
                                        <form method="post" class="d-inline" data-theme-marketplace-install-form="1" data-confirm-message="Theme '<?php echo $escape($name); ?>' installieren?" data-confirm-title="Theme installieren" data-confirm-text="Installieren" data-confirm-class="btn-primary" data-confirm-status-class="bg-primary">
                                            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                            <input type="hidden" name="action" value="install">
                                            <input type="hidden" name="theme" value="<?php echo $escape($slug); ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" data-theme-marketplace-submit-button data-submitting-text="Installiere…">Installieren</button>
                                        </form>
                                    </div>
                                <?php elseif ($isPaid && $purchaseUrl !== ''): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-orange-lt text-orange">Anfrage erforderlich</span>
                                        <a href="<?php echo $escape($purchaseUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">Anfragen / Kaufen</a>
                                        <?php if ($compatibilityReason !== ''): ?>
                                            <span class="text-warning small"><?php echo $escape($compatibilityReason); ?></span>
                                        <?php endif; ?>
                                        <span class="text-muted small"><?php echo $escape((string)($theme['install_reason'] ?? 'Dieses Theme wird über den Marketplace auf Anfrage bereitgestellt.')); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="badge bg-secondary-lt text-secondary">Nur manuell</span>
                                        <?php if ($compatibilityReason !== ''): ?>
                                            <span class="text-warning small"><?php echo $escape($compatibilityReason); ?></span>
                                        <?php endif; ?>
                                        <span class="text-muted small"><?php echo $escape((string)($theme['install_reason'] ?? 'Für dieses Theme ist aktuell kein Installationspaket im Marketplace hinterlegt.')); ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php elseif (!empty($theme['active'])): ?>
                                <span class="text-muted small">Aktives Theme</span>
                            <?php else: ?>
                                <span class="text-muted small">Bereits installiert</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card d-none mt-4" id="themeMarketplaceEmptyState">
            <div class="card-body text-center py-5 text-secondary">
                Keine Themes für den aktuellen Such-/Statusfilter gefunden.
            </div>
        </div>
    <?php endif; ?>
</div>
<script type="application/json" id="theme-marketplace-config"><?php echo json_encode($themeMarketplaceConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
