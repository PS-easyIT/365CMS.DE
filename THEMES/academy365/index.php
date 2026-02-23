<?php get_header(); ?>
<main id="main" class="ac-main-content ac-archive-content" role="main">
    <div class="ac-container">
        <header class="ac-archive-header">
            <h1 class="ac-archive-title"><?php \CMS\CMS::instance()->theArchiveTitle(); ?></h1>
        </header>
        <?php if (\CMS\CMS::instance()->hasContent()) : ?>
            <div class="ac-courses-grid">
                <?php while (\CMS\CMS::instance()->loop()) : \CMS\CMS::instance()->theContent(); ?>
                    <article class="ac-card" <?php \CMS\CMS::instance()->theClass(); ?>>
                        <div class="ac-card-thumb"></div>
                        <div class="ac-card-body">
                            <h2 class="ac-card-title">
                                <a href="<?php \CMS\CMS::instance()->thePermalink(); ?>"><?php \CMS\CMS::instance()->theTitle(); ?></a>
                            </h2>
                            <div class="ac-card-excerpt"><?php \CMS\CMS::instance()->theExcerpt(); ?></div>
                            <a href="<?php \CMS\CMS::instance()->thePermalink(); ?>" class="ac-btn ac-btn-secondary ac-btn-sm">Zum Kurs</a>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
            <div class="ac-pagination"><?php \CMS\CMS::instance()->thePagination(); ?></div>
        <?php else : ?>
            <div class="ac-empty-state">
                <span aria-hidden="true" style="font-size:3rem;">📚</span>
                <p>Noch keine Kurse vorhanden.</p>
                <a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>" class="ac-btn ac-btn-primary">Zurück zur Startseite</a>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php get_footer(); ?>
