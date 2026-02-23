<?php
/**
 * TechNexus Theme – Allgemeine Fehlerseite
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$errorCode    = $errorCode ?? 500;
$errorMessage = $errorMessage ?? 'Ein unerwarteter Fehler ist aufgetreten.';
?>

<main id="main" class="site-main error-page" role="main">
    <div class="container" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
        <div class="tech-card" style="text-align:center;padding:3rem 2rem;max-width:520px;">
            <div style="font-size:4rem;font-family:var(--font-mono);color:var(--accent-color);line-height:1;">
                <?php echo (int)$errorCode; ?>
            </div>
            <h1 style="font-size:var(--font-2xl);margin:1rem 0 0.5rem;">Systemfehler</h1>
            <p style="color:var(--muted-color);">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div style="margin-top:1.5rem;">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Zur Startseite</a>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
