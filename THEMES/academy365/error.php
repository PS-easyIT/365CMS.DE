<?php
$statusCode = isset($errorCode) ? (int) $errorCode : 500;
http_response_code($statusCode);
get_header();
$safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$siteUrl = SITE_URL;
?>
<main id="main" class="ac-main-content" role="main">
    <div class="ac-container ac-error-page">
        <div class="ac-error-code" aria-hidden="true"><?php echo $statusCode; ?></div>
        <span style="font-size:4rem;" aria-hidden="true">⚠️</span>
        <h1>Ein Fehler ist aufgetreten</h1>
        <p class="ac-muted">
            <?php if (!empty($errorMessage)) : ?>
                <?php echo $safe($errorMessage); ?>
            <?php else : ?>
                Es ist ein technischer Fehler aufgetreten. Bitte versuche es später erneut.
            <?php endif; ?>
        </p>
        <a href="<?php echo $safe($siteUrl); ?>" class="ac-btn ac-btn-primary" style="margin-top:1.5rem;">Zurück zur Startseite</a>
    </div>
</main>
<?php get_footer(); ?>
