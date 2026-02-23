<?php
/**
 * Blog – Listen-Cards (4 Beiträge, newspaper-style)
 *
 * Erwartete Variable:
 *   $listPosts – array of stdObjects, max. 4 Einträge
 *
 * @package CMSv2\Themes\CmsDefault\Partials
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($listPosts)) {
    return;
}
?>
<div class="section-label"><h3>Aktuelle Artikel</h3></div>

<div class="article-list">
    <?php foreach ($listPosts as $item): ?>
    <?php
        $iTitle   = htmlspecialchars($item->title ?? '');
        $iSlug    = htmlspecialchars($item->slug ?? '');
        $iCat     = htmlspecialchars($item->category_name ?? '');
        $iCatSlug = htmlspecialchars($item->category_slug ?? '');
        $iExcerpt = htmlspecialchars(
            !empty($item->excerpt) ? $item->excerpt : meridian_excerpt($item->content ?? '', 120)
        );
        $iDate    = !empty($item->published_at ?? $item->created_at ?? '')
                    ? meridian_format_date($item->published_at ?? $item->created_at, true)
                    : '';
        $iRead    = !empty($item->read_time) ? $item->read_time . ' Min.' : '5 Min.';
        $iImage   = $item->featured_image ?? '';
    ?>
    <article class="article-row">
        <?php if (!empty($iImage)): ?>
        <a href="<?php echo SITE_URL; ?>/blog/<?php echo $iSlug; ?>" class="art-thumb">
            <img src="<?php echo htmlspecialchars($iImage); ?>"
                 alt="<?php echo $iTitle; ?>"
                 loading="lazy">
        </a>
        <?php else: ?>
        <a href="<?php echo SITE_URL; ?>/blog/<?php echo $iSlug; ?>" class="art-thumb">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </a>
        <?php endif; ?>

        <div class="art-body">
            <?php if ($iCat): ?>
            <div class="art-cat">
                <a href="<?php echo SITE_URL; ?>/blog?category=<?php echo urlencode($iCatSlug ?: $iCat); ?>">
                    <?php echo $iCat; ?>
                </a>
            </div>
            <?php endif; ?>
            <div class="art-title">
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo $iSlug; ?>"><?php echo $iTitle; ?></a>
            </div>
            <?php if ($iExcerpt): ?>
            <div class="art-excerpt"><?php echo $iExcerpt; ?></div>
            <?php endif; ?>
            <div class="art-meta">
                <?php if ($iDate): ?>
                <time><?php echo $iDate; ?></time>
                <span class="dot"></span>
                <?php endif; ?>
                <span class="read-t"><?php echo $iRead; ?></span>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
