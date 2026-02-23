<?php
if (!defined('ABSPATH')) exit;
get_header();
?>
<main id="main" class="pf-main error-page" role="main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="pf-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:5rem;color:var(--primary-color);font-weight:800;line-height:1;">404</div>
        <h1 style="margin:1rem 0 .5rem;">Seite nicht gefunden</h1>
        <p style="color:var(--muted-color);">Die gesuchte Seite existiert nicht oder wurde verschoben.</p>
        <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo SITE_URL; ?>" class="pf-btn pf-btn-primary">Zur Startseite</a>
            <a href="<?php echo SITE_URL; ?>/jobs" class="pf-btn pf-btn-ghost">Offene Stellen</a>
        </div>
    </div>
</main>
<?php get_footer(); ?>
