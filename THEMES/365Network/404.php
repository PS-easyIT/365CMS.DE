<?php
/**
 * 404 Not Found Template
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl = SITE_URL;
?>

<main id="main" class="site-main" role="main">
    <div class="container">
        <div class="error-page" style="padding:var(--spacing-xl) 0;">
            <div class="error-page-code">404</div>
            <h1>Seite nicht gefunden</h1>
            <p>Die gesuchte Seite existiert nicht oder wurde verschoben.</p>
            <div class="error-page-actions">
                <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/"
                   class="btn btn-primary">
                    Zur Startseite
                </a>
                <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/search"
                   class="btn btn-secondary">
                    Suche öffnen
                </a>
                <button onclick="history.back()" class="btn btn-outline" type="button">
                    ← Zurück
                </button>
            </div>
        </div>
    </div>
</main>
