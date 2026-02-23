<?php
/**
 * TechNexus Theme – Einzelseite (page.php)
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$page = \CMS\Services\PageService::getCurrent();
?>

<main id="main" class="site-main" role="main" style="padding:var(--spacing-2xl) 0;">
    <div class="container" style="max-width:860px;">
        <?php if (!empty($page)) : ?>
            <article class="page-content tech-card" style="padding:var(--spacing-xl);">
                <?php if (!empty($page->title ?? '')) : ?>
                    <h1 class="page-title" style="margin-bottom:var(--spacing-md);font-size:var(--font-3xl);">
                        <?php echo htmlspecialchars($page->title, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                <?php endif; ?>

                <?php if (!empty($page->content ?? '')) : ?>
                    <div class="page-body prose">
                        <?php echo \CMS\Helpers\ContentHelper::processContent($page->content); ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php else : ?>
            <div class="tech-card" style="text-align:center;padding:3rem;">
                <p>Diese Seite wurde nicht gefunden.</p>
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Zur Startseite</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
