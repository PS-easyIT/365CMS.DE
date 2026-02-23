<?php if (!defined('ABSPATH')) exit; get_header(); $posts = \CMS\Services\PostService::getPosts(['per_page' => 12]); $safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<main id="main" class="ll-main" role="main" style="padding:3rem 0;">
    <div class="ll-container"><h1 style="margin-bottom:2rem;">Beiträge</h1>
        <?php if (!empty($posts)) : ?><div class="ll-grid">
            <?php foreach ($posts as $p) : ?><article class="ll-card">
                <h2 style="font-size:1.05rem;"><a href="<?php echo $safe($p->url ?? SITE_URL); ?>" style="color:var(--text-primary);text-decoration:none;"><?php echo $safe($p->title ?? ''); ?></a></h2>
                <?php if (!empty($p->excerpt)) : ?><p style="color:var(--muted-color);font-size:.9rem;margin-top:.5rem;"><?php echo $safe($p->excerpt); ?></p><?php endif; ?>
            </article><?php endforeach; ?>
        </div><?php else : ?><p style="color:var(--muted-color);">Noch keine Beiträge.</p><?php endif; ?>
    </div>
</main><?php get_footer(); ?>
