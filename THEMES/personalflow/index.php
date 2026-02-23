<?php
if (!defined('ABSPATH')) exit;
get_header();
$posts = \CMS\Services\PostService::getPosts(['per_page' => 12]);
$safe  = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<main id="main" class="pf-main" role="main" style="padding:3rem 0;">
    <div class="pf-container">
        <h1>Beiträge</h1>
        <?php if (!empty($posts)) : ?>
            <div class="pf-grid" style="margin-top:2rem;">
                <?php foreach ($posts as $post) : ?>
                    <article class="pf-card">
                        <?php if (!empty($post->created_at)) : ?>
                            <time style="font-size:.8rem;color:var(--muted-color);"><?php echo $safe(gmdate('d.m.Y', strtotime($post->created_at))); ?></time>
                        <?php endif; ?>
                        <h2 style="margin:.5rem 0;font-size:1.1rem;">
                            <a href="<?php echo $safe($post->url ?? SITE_URL); ?>" style="color:var(--text-primary);text-decoration:none;"><?php echo $safe($post->title ?? ''); ?></a>
                        </h2>
                        <?php if (!empty($post->excerpt)) : ?>
                            <p style="color:var(--muted-color);font-size:.9rem;"><?php echo $safe($post->excerpt); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p style="color:var(--muted-color);margin-top:2rem;">Noch keine Beiträge vorhanden.</p>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
