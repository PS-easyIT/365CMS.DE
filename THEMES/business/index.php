<?php
/**
 * Business Theme – Index / Fallback
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="biz-page-hero">
    <div class="biz-container">
        <h1>Seite nicht gefunden</h1>
        <p>Die gesuchte Seite existiert nicht oder wurde verschoben.</p>
    </div>
</section>

<div class="biz-page-content">
    <div class="biz-container" style="text-align:center;padding:4rem 0;">
        <p style="color:#64748b;margin-bottom:2rem;">Bitte nutzen Sie die Navigation oder kehren Sie zur Startseite zurück.</p>
        <a href="<?php echo biz_site_url(); ?>/" class="btn-biz btn-biz-primary">Zur Startseite</a>
    </div>
</div>
