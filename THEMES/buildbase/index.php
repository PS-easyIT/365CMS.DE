<?php if (!defined('ABSPATH')) exit; get_header(); $posts = \CMS\Services\PostService::getPosts(['per_page' => 12]); $safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<main id="main" class="bb-main" role="main" style="padding:3rem 0;">
    <div class="bb-container"><h1 style="font-family:var(--font-heading);margin-bottom:2rem;">Beiträge</h1>
        <?php if (!empty($posts)) : ?><div class="bb-grid">
            <?php foreach ($posts as $post) : ?>
                <article class="bb-card">
                    <h2 style="font-size:1.1rem;font-family:var(--font-heading);"><a href="<?php echo $safe($post->url ?? SITE_URL); ?>" style="color:var(--text-primary);text-decoration:none;"><?php echo $safe($post->title ?? ''); ?></a></h2>
                    <?php if (!empty($post->excerpt)) : ?><p style="color:var(--muted-color);font-size:.9rem;margin-top:.5rem;"><?php echo $safe($post->excerpt); ?></p><?php endif; ?>
                </article>
            <?php endforeach; ?></div>
        <?php else : ?><p style="color:var(--muted-color);">Noch keine Beiträge.</p><?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
