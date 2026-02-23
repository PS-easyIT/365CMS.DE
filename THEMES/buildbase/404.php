<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="main" class="bb-main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="bb-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:5rem;font-family:var(--font-heading);color:var(--primary-color);line-height:1;">404</div>
        <h1 style="font-family:var(--font-heading);margin:1rem 0 .5rem;">Seite nicht gefunden</h1>
        <p style="color:var(--muted-color);">Die gesuchte Seite existiert nicht.</p>
        <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;">
            <a href="<?php echo SITE_URL; ?>" class="bb-btn bb-btn-primary">Zur Startseite</a>
            <a href="<?php echo SITE_URL; ?>/handwerker" class="bb-btn bb-btn-outline">Handwerker finden</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>
