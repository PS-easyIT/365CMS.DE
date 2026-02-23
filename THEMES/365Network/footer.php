<?php
/**
 * Footer Template
 *
 * @package IT_Expert_Network_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$themeManager = \CMS\ThemeManager::instance();
$customizer   = \CMS\Services\ThemeCustomizer::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;

// Customizer Settings
$footerText        = $customizer->get('footer', 'footer_text', 'Die IT-Networking-Plattform für Experten, Unternehmen und Events. Vernetze dich mit der IT-Community.');
$showNetworkWidgets = $customizer->get('footer', 'show_network_widgets', true);
$copyrightTemplate = $customizer->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');

// Placeholders for Copyright
$copyrightText = str_replace(
    ['{year}', '{site_title}'],
    [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')],
    $copyrightTemplate
);
?>
    </div><!-- #content .site-content -->

    <footer id="colophon" class="site-footer" role="contentinfo" aria-label="Fußbereich">

        <!-- Footer Content Band -->
        <div class="footer-content-band">
            <div class="footer-container">
                <?php if ($showNetworkWidgets) : ?>
                <div class="footer-widgets">
                    <div class="footer-widget">
                        <h3 class="footer-widget-title"><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($footerText, ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                    <div class="footer-widget">
                        <h3 class="footer-widget-title">Schnellzugriff</h3>
                        <ul style="list-style:none;padding:0;margin:0;">
                            <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/it-experts">IT-Experten</a></li>
                            <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/companies">Unternehmen</a></li>
                            <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/events">Events</a></li>
                            <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/marketplace">Marketplace</a></li>
                        </ul>
                    </div>
                    <div class="footer-widget">
                        <h3 class="footer-widget-title">Mitglieder</h3>
                        <ul style="list-style:none;padding:0;margin:0;">
                            <?php if ($isLoggedIn) : ?>
                                <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/member">Mein Dashboard</a></li>
                                <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/member/profile">Mein Profil</a></li>
                                <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/logout">Abmelden</a></li>
                            <?php else : ?>
                                <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/login">Anmelden</a></li>
                                <li><a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/register">Registrieren</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>

                <div class="footer-bottom">
                    <p><?php echo $copyrightText; ?></p>

                    <nav class="footer-legal-nav" aria-label="Rechtliche Links">
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/impressum">Impressum</a>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/datenschutz">Datenschutz</a>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/agb">AGB</a>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/kontakt">Kontakt</a>
                    </nav>

                    <?php if ($isLoggedIn) : ?>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/member" class="footer-dashboard-btn">
                            Zum Dashboard
                        </a>
                    <?php else : ?>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/login" class="footer-login-btn">
                            Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

</div><!-- #page .site -->

<?php \CMS\Hooks::doAction('before_footer'); ?>

</body>
</html>
