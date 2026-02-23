<?php
/**
 * Business Theme – 404 Fehlerseite
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

http_response_code(404);
?>

<section class="biz-page-hero">
    <div class="biz-container">
        <p style="font-size:0.875rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;">Fehler 404</p>
        <h1>Seite nicht gefunden</h1>
        <p>Die angeforderte Seite existiert nicht oder wurde verschoben.</p>
    </div>
</section>

<div class="biz-page-content">
    <div class="biz-container" style="text-align:center;padding:5rem 0;">
        <div style="font-size:5rem;margin-bottom:1.5rem;">🔍</div>
        <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:1rem;">Ups – hier gibt es nichts zu sehen.</h2>
        <p style="color:#64748b;margin-bottom:2.5rem;max-width:440px;margin-left:auto;margin-right:auto;">
            Möglicherweise wurde die Seite umbenannt oder gelöscht. Nutzen Sie die Navigation oder kehren Sie zur Startseite zurück.
        </p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo biz_site_url(); ?>/" class="btn-biz btn-biz-primary btn-biz-lg">🏠 Zur Startseite</a>
            <a href="<?php echo biz_site_url(); ?>/#kontakt" class="btn-biz btn-biz-ghost btn-biz-lg">Kontakt aufnehmen</a>
        </div>
    </div>
</div>
