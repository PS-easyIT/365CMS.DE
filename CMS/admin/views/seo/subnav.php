<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoActionToken = (string) ($csrfToken ?? '');
if ($seoActionToken === '') {
    $seoActionToken = \CMS\Security::instance()->generateToken('admin_seo_suite');
}
$seoActionEndpoint = SITE_URL . '/admin/seo-sitemap';
$seoReturnTo = (string) ($_SERVER['REQUEST_URI'] ?? '/admin/seo-sitemap');

$seoSubnavItems = [
    ['slug' => 'seo-dashboard', 'label' => 'Dashboard', 'url' => SITE_URL . '/admin/seo-dashboard'],
    ['slug' => 'analytics', 'label' => 'Analytics', 'url' => SITE_URL . '/admin/analytics'],
    ['slug' => 'seo-audit', 'label' => 'Audit', 'url' => SITE_URL . '/admin/seo-audit'],
    ['slug' => 'seo-meta', 'label' => 'Meta-Daten', 'url' => SITE_URL . '/admin/seo-meta'],
    ['slug' => 'seo-social', 'label' => 'Social', 'url' => SITE_URL . '/admin/seo-social'],
    ['slug' => 'seo-schema', 'label' => 'Strukturierte Daten', 'url' => SITE_URL . '/admin/seo-schema'],
    ['slug' => 'seo-sitemap', 'label' => 'Sitemap', 'url' => SITE_URL . '/admin/seo-sitemap'],
    ['slug' => 'seo-technical', 'label' => 'Technisches SEO', 'url' => SITE_URL . '/admin/seo-technical'],
    ['slug' => 'redirect-manager', 'label' => '404 & Weiterleitungen', 'url' => SITE_URL . '/admin/redirect-manager'],
];
?>
<div class="card mb-4">
    <div class="card-body p-2">
        <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center justify-content-between">
            <div class="nav nav-pills nav-pills-sm flex-wrap gap-2">
                <?php foreach ($seoSubnavItems as $item): ?>
                    <a class="nav-link <?= ($activePage ?? '') === $item['slug'] ? 'active' : '' ?>" href="<?= htmlspecialchars($item['url']) ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <form method="post" action="<?= htmlspecialchars($seoActionEndpoint) ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($seoActionToken) ?>">
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($seoReturnTo) ?>">
                    <input type="hidden" name="action" value="regenerate_sitemap_bundle">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        Sitemaps generieren
                    </button>
                </form>

                <form method="post" action="<?= htmlspecialchars($seoActionEndpoint) ?>" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($seoActionToken) ?>">
                    <input type="hidden" name="return_to" value="<?= htmlspecialchars($seoReturnTo) ?>">
                    <input type="hidden" name="action" value="save_robots">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        robots.txt schreiben
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
