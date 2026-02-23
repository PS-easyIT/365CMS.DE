<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="main" class="mc-main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="mc-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:5rem;font-weight:800;color:var(--primary-color);line-height:1;">404</div>
        <h1 style="font-family:var(--font-heading);margin:1rem 0 .5rem;">Seite nicht gefunden</h1>
        <p style="color:var(--muted-color);">Die gesuchte Seite existiert nicht.</p>
        <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>" class="mc-btn mc-btn-primary">Zur Startseite</a>
            <a href="<?php echo SITE_URL; ?>/aerzte" class="mc-btn mc-btn-outline">Arzt suchen</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>
