<?php get_header(); ?>
<main id="main" class="ac-main-content ac-page-content" role="main">
    <div class="ac-container">
        <?php if (\CMS\CMS::instance()->hasContent()) : ?>
            <?php while (\CMS\CMS::instance()->loop()) : \CMS\CMS::instance()->theContent(); ?>
                <article class="ac-page-article">
                    <header class="ac-page-header">
                        <h1 class="ac-page-title"><?php \CMS\CMS::instance()->theTitle(); ?></h1>
                    </header>
                    <div class="ac-page-body" style="font-family:var(--font-body);">
                        <?php \CMS\CMS::instance()->theContent(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
