<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SEO_VIEW')) {
    exit;
}

$seoActionToken = (string) ($csrfToken ?? '');
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
    ['slug' => 'redirect-manager', 'label' => 'Weiterleitungen', 'url' => SITE_URL . '/admin/redirect-manager'],
    ['slug' => 'not-found-monitor', 'label' => '404-Monitor', 'url' => SITE_URL . '/admin/not-found-monitor'],
];

$sectionNavGroups = [[
    'items' => $seoSubnavItems,
]];

$currentSectionPage = (string) ($activePage ?? 'seo-dashboard');
?>
<?php require dirname(__DIR__) . '/partials/section-subnav.php'; ?>

<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
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
