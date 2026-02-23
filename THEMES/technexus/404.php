<?php
/**
 * TechNexus Theme – 404 Fehlerseite
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main" class="site-main error-page" role="main">
    <div class="container" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
        <div class="tech-card" style="text-align:center;padding:3rem 2rem;max-width:520px;">
            <div style="font-size:5rem;font-family:var(--font-mono);color:var(--primary-color);line-height:1;">404</div>
            <h1 style="font-size:var(--font-2xl);margin:1rem 0 0.5rem;">Seite nicht gefunden</h1>
            <p style="color:var(--muted-color);">
                Die gesuchte Seite existiert nicht oder wurde verschoben.
            </p>
            <div style="margin-top:1.5rem;display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Zurück zur Startseite</a>
                <a href="<?php echo SITE_URL; ?>/it-experts" class="btn btn-outline">Experten suchen</a>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
