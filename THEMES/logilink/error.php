<?php if (!defined('ABSPATH')) exit; get_header(); $errorCode = $errorCode ?? 500; $errorMessage = $errorMessage ?? 'Ein Fehler ist aufgetreten.'; ?>
<main id="main" style="min-height:60vh;display:flex;align-items:center;justify-content:center;">
    <div class="ll-card" style="text-align:center;padding:3rem 2rem;max-width:500px;">
        <div style="font-size:4rem;font-weight:800;color:var(--status-delayed);font-family:var(--font-mono);line-height:1;"><?php echo (int)$errorCode; ?></div>
        <h1 style="margin:1rem 0 .5rem;">Systemfehler</h1>
        <p style="color:var(--muted-color);"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <a href="<?php echo SITE_URL; ?>" class="ll-btn ll-btn-primary" style="margin-top:1.5rem;">Zur Startseite</a>
    </div>
</main>
<?php get_footer(); ?>
