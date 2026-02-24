<?php
/**
 * Blog-Übersicht – MedCare Pro Theme
 *
 * Erwartet optional: $posts (array), $total (int), $currentPage (int), $totalPages (int)
 *
 * @package MedCarePro
 */
if (!defined('ABSPATH')) exit;
get_header();
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$siteUrl = SITE_URL;
$page    = max(1, (int)($_GET['page'] ?? $currentPage ?? 1));

try {
    if (empty($posts)) {
        $posts = \CMS\Services\PostService::getPosts(['per_page' => 12, 'page' => $page, 'status' => 'published']);
    }
    $total      = $total ?? \CMS\Services\PostService::getCount(['status' => 'published']);
    $totalPages = $totalPages ?? (int)ceil($total / 12);
} catch (\Throwable $e) {
    $posts = []; $total = 0; $totalPages = 1;
}
?>
<main id="main" class="mc-main" role="main">
    <div class="mc-section" style="padding-top:calc(var(--spacing-2xl));">
        <div class="mc-container">

            <!-- Header -->
            <div class="mc-section-header">
                <h1 id="blog-heading" style="font-size:var(--font-3xl);">🩺 Gesundheits&shy;ratgeber</h1>
                <p>Medizinische Informationen, Praxistipps und Gesundheitsnews von zertifizierten Experten</p>
            </div>

            <?php if (!empty($posts)) : ?>
            <div class="mc-grid">
                <?php foreach ($posts as $p) :
                    $url      = $safe($p->url ?? $siteUrl . '/blog/' . ($p->slug ?? $p->id ?? ''));
                    $title    = $safe($p->title ?? '');
                    $excerpt  = $safe($p->excerpt ?? '');
                    $date     = isset($p->created_at) ? date('d.m.Y', strtotime($p->created_at)) : '';
                    $author   = $safe($p->author_name ?? '');
                    $category = $safe($p->category_name ?? '');
                    $imgUrl   = $safe($p->thumbnail_url ?? '');
                ?>
                <article class="mc-card" aria-labelledby="post-<?php echo (int)($p->id ?? 0); ?>">
                    <?php if (!empty($imgUrl)) : ?>
                    <a href="<?php echo $url; ?>" tabindex="-1" aria-hidden="true">
                        <img src="<?php echo $imgUrl; ?>" alt="<?php echo $title; ?>"
                             style="width:100%;height:180px;object-fit:cover;border-radius:var(--radius-md);margin-bottom:1rem;">
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($category)) : ?>
                    <span class="mc-specialty-badge" style="margin-bottom:.6rem;">
                        <?php echo $category; ?>
                    </span>
                    <?php endif; ?>

                    <h2 id="post-<?php echo (int)($p->id ?? 0); ?>"
                        style="font-family:var(--font-heading);font-size:var(--font-lg);margin-bottom:.5rem;">
                        <a href="<?php echo $url; ?>" style="color:var(--secondary-color);text-decoration:none;">
                            <?php echo $title; ?>
                        </a>
                    </h2>
                    <?php if (!empty($excerpt)) : ?>
                    <p style="color:var(--text-secondary);font-size:var(--font-sm);margin-bottom:.75rem;line-height:1.65;">
                        <?php echo $excerpt; ?>
                    </p>
                    <?php endif; ?>

                    <footer style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;margin-top:auto;padding-top:.75rem;border-top:1px solid var(--border-color);font-size:var(--font-xs);color:var(--muted-color);">
                        <?php if (!empty($author)) : ?><span>👤 <?php echo $author; ?></span><?php endif; ?>
                        <?php if (!empty($date)) : ?><time datetime="<?php echo $date; ?>">📅 <?php echo $date; ?></time><?php endif; ?>
                        <a href="<?php echo $url; ?>" class="mc-btn mc-btn-outline" style="padding:.35rem .85rem;font-size:var(--font-xs);">
                            Weiterlesen
                        </a>
                    </footer>
                </article>
                <?php endforeach; ?>
            </div><!-- /.mc-grid -->

            <?php else : ?>
            <div class="mc-card" style="text-align:center;padding:3rem 2rem;">
                <div style="font-size:3rem;margin-bottom:.75rem;" aria-hidden="true">📋</div>
                <h2 style="font-family:var(--font-heading);color:var(--secondary-color);margin-bottom:.5rem;">
                    Noch keine Beiträge
                </h2>
                <p style="color:var(--muted-color);">
                    Bald erscheinen hier medizinische Fachartikel und Gesundheitstipps.
                </p>
            </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1) : ?>
            <nav class="mc-pagination" aria-label="Seitennavigation" style="display:flex;gap:.5rem;justify-content:center;margin-top:2.5rem;flex-wrap:wrap;">
                <?php if ($page > 1) : ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="mc-btn mc-btn-outline mc-btn-sm" rel="prev">← Zurück</a>
                <?php endif; ?>
                <span style="padding:.5rem 1rem;font-size:var(--font-sm);color:var(--muted-color);align-self:center;">
                    Seite <?php echo $page; ?> von <?php echo $totalPages; ?>
                </span>
                <?php if ($page < $totalPages) : ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="mc-btn mc-btn-outline mc-btn-sm" rel="next">Weiter →</a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

        </div>
    </div>
</main>
<?php get_footer(); ?>
