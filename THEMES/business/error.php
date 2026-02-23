<?php
/**
 * Business Theme – Fehlerseite (generisch)
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$errorCode    = $GLOBALS['error_code']    ?? 500;
$errorMessage = $GLOBALS['error_message'] ?? 'Ein unerwarteter Fehler ist aufgetreten.';
http_response_code((int)$errorCode);
?>

<section class="biz-page-hero">
    <div class="biz-container">
        <p style="font-size:0.875rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;">Fehler <?php echo (int)$errorCode; ?></p>
        <h1>Etwas ist schiefgelaufen.</h1>
    </div>
</section>

<div class="biz-page-content">
    <div class="biz-container" style="text-align:center;padding:5rem 0;">
        <p style="color:#64748b;margin-bottom:2.5rem;">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <a href="<?php echo biz_site_url(); ?>/" class="btn-biz btn-biz-primary btn-biz-lg">🏠 Zur Startseite</a>
    </div>
</div>
