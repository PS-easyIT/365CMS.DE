<?php if (!defined('ABSPATH')) exit; get_header(); $page = \CMS\Services\PageService::getCurrent(); $safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); ?>
<main id="main" class="mc-main" role="main" style="padding:3rem 0;">
    <div class="mc-container" style="max-width:860px;">
        <?php if (!empty($page)) : ?><article class="mc-card" style="padding:2rem;">
            <?php if (!empty($page->title)) : ?><h1 style="font-family:var(--font-heading);margin-bottom:1.5rem;"><?php echo $safe($page->title); ?></h1><?php endif; ?>
            <?php if (!empty($page->content)) : ?><div class="prose" style="font-family:var(--font-serif);"><?php echo \CMS\Helpers\ContentHelper::processContent($page->content); ?></div><?php endif; ?>
        </article>
        <?php else : ?><div class="mc-card" style="text-align:center;padding:3rem;"><p>Seite nicht gefunden.</p><a href="<?php echo SITE_URL; ?>" class="mc-btn mc-btn-primary" style="margin-top:1rem;">Zur Startseite</a></div><?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
