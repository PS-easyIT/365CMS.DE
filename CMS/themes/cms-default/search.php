<?php
/**
 * Meridian CMS Default – Suche Template
 *
 * Vom Router bereitgestellte Variablen:
 *   $results      – Treffer als Arrays mit `_type`/`_type_label` (Seite, Beitrag, Kategorie, Tag,
 *                   Plugin-Treffer mit eigener `url`)
 *   $query        – string, Suchbegriff
 *   $total        – int (optional, sonst Anzahl der Treffer)
 *   $currentPage  – int
 *   $totalPages   – int
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$query       = htmlspecialchars((string) ($_GET['q'] ?? $query ?? ''), ENT_QUOTES, 'UTF-8');
$results     = is_array($results ?? null) ? $results : [];
$total       = (int) ($total ?? count($results));
$currentPage = $currentPage ?? 1;
$totalPages  = $totalPages  ?? 1;

$searchLocale = function_exists('cms_plugin_public_language') ? cms_plugin_public_language() : 'de';
$searchResultUrl = static function (array $item) use ($searchLocale): string {
    $url = trim((string) ($item['url'] ?? ''));
    if ($url !== '') {
        return $url;
    }

    $type = (string) ($item['_type'] ?? '');
    if ($type === 'post') {
        return \CMS\Services\PermalinkService::getInstance()->buildPostUrl($item, $searchLocale);
    }

    if ($type === 'page') {
        $slug = \CMS\Services\ContentLocalizationService::getInstance()->resolveLocalizedSlug($item, $searchLocale);
        return rtrim(SITE_URL, '/') . ($searchLocale === 'en' ? '/en/' : '/') . ltrim($slug, '/');
    }

    // Kategorien, Tags und Verzeichnis-Treffer liefern ihren Pfad im Feld `slug`.
    return rtrim(SITE_URL, '/') . '/' . ltrim((string) ($item['slug'] ?? ''), '/');
};
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
        <?php foreach ($results as $result): ?>
        <?php
        $item = is_object($result) ? get_object_vars($result) : (array) $result;
        $itemType = (string) ($item['_type'] ?? '');
        $itemUrl = $searchResultUrl($item);
        $itemTitle = trim((string) ($item['title'] ?? $item['name'] ?? ''));
        $itemLabel = trim((string) ($item['_type_label'] ?? ''));
        $itemImage = in_array($itemType, ['post', 'page'], true) ? trim((string) ($item['featured_image'] ?? '')) : '';
        $itemDate = $itemType === 'post' ? (string) ($item['published_at'] ?? $item['created_at'] ?? '') : '';
        $excerpt = trim((string) ($item['excerpt'] ?? ''));
        if ($excerpt === '') {
            $excerpt = trim((string) ($item['meta_description'] ?? ''));
        }
        if ($excerpt === '') {
            $excerpt = meridian_excerpt((string) ($item['content'] ?? ''), 200);
        }
        ?>
        <article class="article-row">
            <?php if ($itemImage !== ''): ?>
            <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>" class="art-thumb">
                <img src="<?php echo htmlspecialchars($itemImage, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="<?php echo htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8'); ?>"
                     loading="lazy">
            </a>
            <?php endif; ?>
            <div class="art-body">
                <?php if ($itemLabel !== ''): ?>
                <span class="cat-tag cat-tag--sm"><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
                <h2 class="art-title">
                    <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($itemTitle, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </h2>
                <?php if ($excerpt !== ''): ?>
                <p class="art-excerpt"><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if ($itemDate !== ''): ?>
                <div class="art-meta">
                    <span class="art-date"><?php echo meridian_format_date($itemDate); ?></span>
                </div>
                <?php endif; ?>
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
        <p style="color:var(--ink-60)">Für „<?php echo $query; ?>" wurden keine Inhalte gefunden.<br>Versuche andere Suchbegriffe oder durchstöbere den Blog.</p>
        <a href="<?php echo SITE_URL; ?>/blog" class="btn-solid" style="display:inline-block;margin-top:1.25rem;">Blog durchsuchen</a>
    </div>
    <?php else: ?>
    <!-- Suchfeld ohne Query -->
    <div style="max-width:600px;margin:3rem auto;text-align:center;">
        <p style="color:var(--ink-60);margin-bottom:1.5rem;">Gib einen Suchbegriff ein, um Artikel zu finden.</p>
    </div>
    <?php endif; ?>

</div><!-- /.container -->
