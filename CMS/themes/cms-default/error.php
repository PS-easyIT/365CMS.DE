<?php
/**
 * Meridian CMS Default – Allgemeines Fehler-Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$errorCode    = $errorCode    ?? 500;
$errorTitle   = $errorTitle   ?? 'Serverfehler';
$errorMessage = $errorMessage ?? 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuche es später erneut.';

http_response_code((int)$errorCode);
?>

<div class="container">
    <div class="error-page">
        <div class="error-page-inner">
            <div class="error-code"><?php echo (int)$errorCode; ?></div>
            <h1 class="error-title"><?php echo htmlspecialchars($errorTitle); ?></h1>
            <p class="error-desc"><?php echo htmlspecialchars($errorMessage); ?></p>
            <div class="error-actions">
                <a href="<?php echo SITE_URL; ?>/" class="btn-solid">Zur Startseite</a>
                <a href="javascript:history.back()" class="btn-ghost">Zurück</a>
            </div>
        </div>
    </div>
</div><!-- /.container -->
