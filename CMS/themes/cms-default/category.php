<?php
/**
 * Meridian CMS Default – Kategorie-Archiv Template
 *
 * Vom Router bereitgestellte Variablen:
 *   $posts        – array of stdObjects
 *   $total        – int, Gesamtanzahl
 *   $currentPage  – int, aktuelle Seite
 *   $totalPages   – int
 *   $category     – stdObject|array|null  (id, name, slug, description)
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$posts       = $posts       ?? [];
$total       = $total       ?? 0;
$currentPage = $currentPage ?? 1;
$totalPages  = $totalPages  ?? 1;

// Kategorie-Daten normalisieren
$cat         = (array)($category ?? []);
$catName     = htmlspecialchars($cat['name'] ?? ($_GET['category'] ?? 'Kategorie'), ENT_QUOTES, 'UTF-8');
$catSlug     = $cat['slug'] ?? urlencode($_GET['category'] ?? '');
$catDesc     = htmlspecialchars($cat['description'] ?? '', ENT_QUOTES, 'UTF-8');

$showSidebar   = (bool) meridian_setting('layout', 'show_sidebar', true);
$recentSidebar = meridian_get_recent_posts(5);
$sidebarCats   = meridian_get_categories(8);
$tagCloud      = [];
$rawTagData    = meridian_get_tags(20);
foreach ($rawTagData as $t) {
    $tagCloud[] = $t['name'];
}
?>

<!-- Kategorie-Header -->
<div class="archive-header" style="background:var(--surface-tint);border-bottom:1px solid var(--rule);padding:2.5rem 0 2rem;">
    <div class="container" style="max-width:var(--max);margin:0 auto;padding:0 1.5rem;">
        <nav class="breadcrumb" aria-label="Breadcrumb" style="font-size:.8rem;color:var(--ink-muted);margin-bottom:.75rem;">
            <a href="<?php echo SITE_URL; ?>/" style="color:var(--ink-muted);text-decoration:none;">Startseite</a>
            <span style="margin:0 .4rem;">›</span>
            <a href="<?php echo SITE_URL; ?>/blog" style="color:var(--ink-muted);text-decoration:none;">Blog</a>
            <span style="margin:0 .4rem;">›</span>
            <span style="color:var(--ink);"><?php echo $catName; ?></span>
        </nav>
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;">
            <span style="font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--accent);">Kategorie</span>
        </div>
        <h1 style="font-family:var(--font-serif);font-size:clamp(1.6rem,4vw,2.4rem);font-weight:700;margin:0 0 .5rem;color:var(--ink);"><?php echo $catName; ?></h1>
        <?php if ($catDesc): ?>
        <p style="font-size:.95rem;color:var(--ink-muted);margin:0 0 .75rem;max-width:580px;"><?php echo $catDesc; ?></p>
        <?php endif; ?>
        <p style="font-size:.82rem;color:var(--ink-ghost);">
            <?php echo number_format($total); ?> <?php echo $total === 1 ? 'Artikel' : 'Artikel'; ?> gefunden
        </p>
    </div>
</div>

<!-- Inhalt -->
<div class="container" style="max-width:var(--max);margin:0 auto;padding:2rem 1.5rem;">
    <div class="page-wrap<?php echo $showSidebar ? ' page-wrap--sidebar' : ''; ?>">

        <main id="main-content">
            <?php if (!empty($posts)): ?>

            <?php
            $listPosts = array_slice($posts, 0, 4);
            $gridPosts = array_slice($posts, 4);
            require __DIR__ . '/partials/blog-list-cards.php';
            if (!empty($gridPosts)) {
                require __DIR__ . '/partials/blog-grid-cards.php';
            }
            ?>

            <!-- Paginierung -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Seitennavigation">
                <?php
                $baseUrl = SITE_URL . '/blog?category=' . urlencode($catSlug);
                $qSep    = '&';
                if ($currentPage > 1): ?>
                <a class="pagination-item pagination-item--prev"
                   href="<?php echo $baseUrl . $qSep . 'page=' . ($currentPage - 1); ?>"
                   aria-label="Vorherige Seite">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <?php endif;
                $start = max(1, $currentPage - 2);
                $end   = min($totalPages, $currentPage + 2);
                if ($start > 1): ?>
                    <a class="pagination-item" href="<?php echo $baseUrl . $qSep . 'page=1'; ?>">1</a>
                    <?php if ($start > 2): ?><span class="pagination-gap">…</span><?php endif; ?>
                <?php endif;
                for ($p = $start; $p <= $end; $p++): ?>
                <a class="pagination-item<?php echo $p === $currentPage ? ' pagination-item--active' : ''; ?>"
                   href="<?php echo $baseUrl . $qSep . 'page=' . $p; ?>"
                   <?php echo $p === $currentPage ? 'aria-current="page"' : ''; ?>><?php echo $p; ?></a>
                <?php endfor;
                if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><span class="pagination-gap">…</span><?php endif; ?>
                    <a class="pagination-item" href="<?php echo $baseUrl . $qSep . 'page=' . $totalPages; ?>"><?php echo $totalPages; ?></a>
                <?php endif;
                if ($currentPage < $totalPages): ?>
                <a class="pagination-item pagination-item--next"
                   href="<?php echo $baseUrl . $qSep . 'page=' . ($currentPage + 1); ?>"
                   aria-label="Nächste Seite">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <div class="empty-state" style="text-align:center;padding:4rem 2rem;">
                <p style="font-size:3rem;margin:0;">📭</p>
                <h2 style="margin:.75rem 0 .5rem;">Keine Artikel in dieser Kategorie</h2>
                <p style="color:var(--ink-muted);">In der Kategorie <strong><?php echo $catName; ?></strong> wurden noch keine Beiträge veröffentlicht.</p>
                <a href="<?php echo SITE_URL; ?>/blog" class="btn-solid" style="display:inline-block;margin-top:1rem;">Alle Artikel anzeigen</a>
            </div>
            <?php endif; ?>
        </main>

        <?php if ($showSidebar): ?>
        <?php require __DIR__ . '/partials/sidebar.php'; ?>
        <?php endif; ?>

    </div>
</div>
