<?php
/**
 * Index / Fallback Template
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<main id="main" class="site-main" role="main">
    <div class="container">
        <div class="content-area" style="padding:var(--spacing-lg) 0;">
            <div style="text-align:center;padding:var(--spacing-xl) 0;color:#666;">
                <svg style="width:64px;height:64px;opacity:0.3;margin:0 auto var(--spacing-md);display:block;"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>Keine Inhalte gefunden.</p>
                <a href="<?php echo htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8'); ?>/" class="btn btn-primary" style="margin-top:var(--spacing-md);">
                    Zur Startseite
                </a>
            </div>
        </div>
    </div>
</main>
