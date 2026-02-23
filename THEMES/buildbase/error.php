<?php if (!defined('ABSPATH')) exit; get_header(); $errorCode = $errorCode ?? 500; $errorMessage = $errorMessage ?? 'Ein Fehler ist aufgetreten.'; ?>
<main id="main" class="bb-main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="bb-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:4rem;font-family:var(--font-heading);color:var(--accent-color);line-height:1;"><?php echo (int)$errorCode; ?></div>
        <h1 style="font-family:var(--font-heading);margin:1rem 0 .5rem;">Systemfehler</h1>
        <p style="color:var(--muted-color);"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="<?php echo SITE_URL; ?>" class="bb-btn bb-btn-primary" style="margin-top:1.5rem;">Zur Startseite</a>
    </div>
</main>
<?php get_footer(); ?>
