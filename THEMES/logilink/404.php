<?php if (!defined('ABSPATH')) exit; get_header(); ?>
<main id="main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="ll-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:5rem;font-weight:800;color:var(--accent-color);font-family:var(--font-mono);line-height:1;">404</div>
        <h1 style="margin:1rem 0 .5rem;">Seite nicht gefunden</h1>
        <p style="color:var(--muted-color);">Die gesuchte Seite existiert nicht oder wurde verschoben.</p>
        <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>" class="ll-btn ll-btn-primary">Zur Startseite</a>
            <a href="<?php echo SITE_URL; ?>/tracking" class="ll-btn ll-btn-accent">Sendung verfolgen</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>
