<?php
/**
 * TechNexus Theme – Index / Archiv
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$posts = \CMS\Services\PostService::getPosts(['per_page' => 12]);
?>

<main id="main" class="site-main" role="main" style="padding:var(--spacing-2xl) 0;">
    <div class="container">
        <h1 style="margin-bottom:var(--spacing-xl);">Beiträge</h1>

        <?php if (!empty($posts)) : ?>
            <div class="tech-grid">
                <?php foreach ($posts as $post) :
                    $pTitle   = htmlspecialchars($post->title   ?? '', ENT_QUOTES, 'UTF-8');
                    $pExcerpt = htmlspecialchars($post->excerpt ?? '', ENT_QUOTES, 'UTF-8');
                    $pUrl     = htmlspecialchars($post->url     ?? SITE_URL, ENT_QUOTES, 'UTF-8');
                    $pDate    = !empty($post->created_at) ? gmdate('d.m.Y', strtotime($post->created_at)) : '';
                ?>
                    <article class="tech-card">
                        <header>
                            <?php if ($pDate) : ?>
                                <time datetime="<?php echo esc_attr($pDate); ?>" style="font-size:var(--font-xs);color:var(--muted-color);">
                                    <?php echo $pDate; ?>
                                </time>
                            <?php endif; ?>
                            <h2 style="margin-top:0.5rem;font-size:var(--font-lg);">
                                <a href="<?php echo $pUrl; ?>" style="color:var(--text-primary);text-decoration:none;">
                                    <?php echo $pTitle; ?>
                                </a>
                            </h2>
                        </header>
                        <?php if ($pExcerpt) : ?>
                            <p style="color:var(--muted-color);margin-top:0.5rem;"><?php echo $pExcerpt; ?></p>
                        <?php endif; ?>
                        <a href="<?php echo $pUrl; ?>" class="tech-badge" style="margin-top:1rem;display:inline-block;">
                            Weiterlesen →
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="tech-card" style="text-align:center;padding:3rem;">
                <p>Noch keine Beiträge vorhanden.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
