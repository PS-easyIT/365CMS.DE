<?php
/**
 * Meridian CMS Default – Suche Template
 *
 * Vom Router bereitgestellte Variablen:
 *   $results      – array of objects
 *   $query        – string, Suchbegriff
 *   $total        – int
 *   $currentPage  – int
 *   $totalPages   – int
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$query       = htmlspecialchars($_GET['q'] ?? $query ?? '');
$results     = $results     ?? [];
$total       = $total       ?? 0;
$currentPage = $currentPage ?? 1;
$totalPages  = $totalPages  ?? 1;
?>

<div class="search-header">
    <div class="container search-header-inner">
        <h1 class="search-heading">
            <?php if ($query): ?>
                Suchergebnisse für: <em><?php echo $query; ?></em>
            <?php else: ?>
                Suche
            <?php endif; ?>
        </h1>
        <p class="search-sub">
            <?php if ($total > 0): ?>
                <?php echo number_format($total); ?> Treffer gefunden
                <?php if ($totalPages > 1): ?>
                    &ensp;·&ensp; Seite <?php echo $currentPage; ?> von <?php echo $totalPages; ?>
                <?php endif; ?>
            <?php elseif ($query): ?>
                Keine Ergebnisse für „<?php echo $query; ?>"
            <?php endif; ?>
        </p>
        <!-- Suche verfeinern -->
        <form class="search-form" action="<?php echo SITE_URL; ?>/search" method="GET" role="search">
            <input type="search" name="q" class="form-control"
                   value="<?php echo $query; ?>"
                   placeholder="Erneut suchen …"
                   aria-label="Suche verfeinern"
                   autocomplete="off">
            <button type="submit" class="btn-solid">Suchen</button>
        </form>
    </div>
</div>

<div class="container">

    <?php if (!empty($results)): ?>
    <div class="article-list search-results" style="max-width:760px;margin:0 auto;">
        <?php foreach ($results as $post): ?>
        <article class="article-row">
            <?php if (!empty($post->featured_image)): ?>
            <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>" class="art-thumb">
                <img src="<?php echo htmlspecialchars($post->featured_image); ?>"
                     alt="<?php echo htmlspecialchars($post->title); ?>"
                     loading="lazy">
            </a>
            <?php endif; ?>
            <div class="art-body">
                <?php if (!empty($post->category_name)): ?>
                <a class="cat-tag cat-tag--sm"
                   href="<?php echo SITE_URL . '/blog?category=' . urlencode($post->category_slug ?? $post->category_name); ?>">
                    <?php echo htmlspecialchars($post->category_name); ?>
                </a>
                <?php endif; ?>
                <h2 class="art-title">
                    <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($post->slug); ?>">
                        <?php echo htmlspecialchars($post->title); ?>
                    </a>
                </h2>
                <?php $excerpt = !empty($post->excerpt) ? $post->excerpt : meridian_excerpt($post->content ?? '', 200); ?>
                <?php if ($excerpt): ?>
                <p class="art-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                <?php endif; ?>
                <div class="art-meta">
                    <?php if (!empty($post->author_name)): ?>
                    <span class="art-author"><?php echo htmlspecialchars($post->author_name); ?></span>
                    <?php endif; ?>
                    <span class="art-date"><?php echo meridian_format_date($post->published_at ?? $post->created_at ?? ''); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Paginierung -->
    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Seitennavigation">
        <?php
        $qBase = '?q=' . urlencode($query) . '&';
        if ($currentPage > 1): ?>
        <a class="pagination-item pagination-item--prev"
           href="<?php echo SITE_URL . '/search' . $qBase . 'page=' . ($currentPage - 1); ?>"
           aria-label="Vorherige Seite">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </a>
        <?php endif;
        for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
        <a class="pagination-item<?php echo $p === $currentPage ? ' pagination-item--active' : ''; ?>"
           href="<?php echo SITE_URL . '/search' . $qBase . 'page=' . $p; ?>"
           <?php echo $p === $currentPage ? 'aria-current="page"' : ''; ?>><?php echo $p; ?></a>
        <?php endfor;
        if ($currentPage < $totalPages): ?>
        <a class="pagination-item pagination-item--next"
           href="<?php echo SITE_URL . '/search' . $qBase . 'page=' . ($currentPage + 1); ?>"
           aria-label="Nächste Seite">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php elseif ($query): ?>
    <!-- Keine Ergebnisse -->
    <div class="empty-state" style="text-align:center;padding:4rem 2rem;max-width:600px;margin:0 auto;">
        <p style="font-size:3rem;margin:0">🔍</p>
        <h2 style="margin:.75rem 0 .5rem;">Nichts gefunden</h2>
        <p style="color:var(--ink-60)">Für „<?php echo $query; ?>" wurden keine Artikel gefunden.<br>Versuche andere Suchbegriffe oder durchstöbere den Blog.</p>
        <a href="<?php echo SITE_URL; ?>/blog" class="btn-solid" style="display:inline-block;margin-top:1.25rem;">Blog durchsuchen</a>
    </div>
    <?php else: ?>
    <!-- Suchfeld ohne Query -->
    <div style="max-width:600px;margin:3rem auto;text-align:center;">
        <p style="color:var(--ink-60);margin-bottom:1.5rem;">Gib einen Suchbegriff ein, um Artikel zu finden.</p>
    </div>
    <?php endif; ?>

</div><!-- /.container -->
