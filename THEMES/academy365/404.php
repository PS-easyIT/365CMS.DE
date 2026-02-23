<?php
http_response_code(404);
get_header();
$siteUrl = SITE_URL;
$safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
try {
    $c = \CMS\Services\ThemeCustomizer::instance();
    $notFoundTitle    = $c->get('error_pages', 'not_found_title',   'Seite nicht gefunden');
    $notFoundMessage  = $c->get('error_pages', 'not_found_message', 'Diese Seite existiert leider nicht. Entdecke stattdessen unsere Kurse!');
    $notFoundCta      = $c->get('error_pages', 'not_found_cta',     'Kurse entdecken');
} catch (\Throwable $e) {
    $notFoundTitle = 'Seite nicht gefunden'; $notFoundMessage = 'Diese Seite existiert leider nicht.'; $notFoundCta = 'Kurse entdecken';
}
?>
<main id="main" class="ac-main-content" role="main">
    <div class="ac-container ac-error-page">
        <div class="ac-error-code" aria-hidden="true">404</div>
        <span style="font-size:4rem;" aria-hidden="true">🎓</span>
        <h1><?php echo $safe($notFoundTitle); ?></h1>
        <p class="ac-muted"><?php echo $safe($notFoundMessage); ?></p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem;">
            <a href="<?php echo $safe($siteUrl . '/courses'); ?>" class="ac-btn ac-btn-primary"><?php echo $safe($notFoundCta); ?></a>
            <a href="<?php echo $safe($siteUrl); ?>" class="ac-btn ac-btn-ghost">Zur Startseite</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>
