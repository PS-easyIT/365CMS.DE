<?php if (!defined('ABSPATH')) exit; get_header(); $page = \CMS\Services\PageService::getCurrent(); $safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<main id="main" class="bb-main" role="main" style="padding:3rem 0;">
    <div class="bb-container" style="max-width:860px;">
        <?php if (!empty($page)) : ?>
            <article class="bb-card" style="padding:2rem;">
                <?php if (!empty($page->title)) : ?><h1 style="margin-bottom:1.5rem;"><?php echo $safe($page->title); ?></h1><?php endif; ?>
                <?php if (!empty($page->content)) : ?><div class="prose"><?php echo \CMS\Helpers\ContentHelper::processContent($page->content); ?></div><?php endif; ?>
            </article>
        <?php else : ?><div class="bb-card" style="text-align:center;padding:3rem;"><p>Seite nicht gefunden.</p><a href="<?php echo SITE_URL; ?>" class="bb-btn bb-btn-primary" style="margin-top:1rem;">Zur Startseite</a></div><?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
