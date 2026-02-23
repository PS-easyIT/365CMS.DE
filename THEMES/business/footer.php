<?php
/**
 * Business Theme – Footer
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl   = biz_site_url();
$siteTitle = biz_site_title();
$tagline   = biz_config('footer_tagline', 'Ihr Partner für digitale Innovation.');
?>
    </main><!-- /#main-content -->

    <footer class="biz-footer" id="biz-footer" role="contentinfo">
        <div class="biz-container">
            <div class="biz-footer-grid">

                <!-- Brand -->
                <div class="biz-footer-brand">
                    <a href="<?php echo $siteUrl; ?>/" class="biz-logo" style="margin-bottom:1rem;display:inline-flex;">
                        <svg width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="margin-right:0.625rem;flex-shrink:0;">
                            <rect width="32" height="32" rx="8" fill="#6366f1"/>
                            <path d="M8 10h4v12H8V10zm6 0h4v12h-4V10zm6 4h4v8h-4v-8z" fill="white"/>
                        </svg>
                        <span class="biz-logo-text"><?php echo $siteTitle; ?></span>
                    </a>
                    <p><?php echo htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="biz-social">
                        <a href="#" aria-label="LinkedIn">in</a>
                        <a href="#" aria-label="Twitter/X">𝕏</a>
                        <a href="#" aria-label="E-Mail">✉</a>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="biz-footer-col">
                    <h4>Navigation</h4>
                    <?php biz_nav_menu('footer-nav'); ?>
                </div>

                <!-- Services -->
                <div class="biz-footer-col">
                    <h4>Leistungen</h4>
                    <ul>
                        <li><a href="<?php echo $siteUrl; ?>/#leistungen">Beratung</a></li>
                        <li><a href="<?php echo $siteUrl; ?>/#leistungen">Entwicklung</a></li>
                        <li><a href="<?php echo $siteUrl; ?>/#leistungen">Support</a></li>
                        <li><a href="<?php echo $siteUrl; ?>/#leistungen">Schulungen</a></li>
                    </ul>
                </div>

                <!-- Kontakt -->
                <div class="biz-footer-col">
                    <h4>Kontakt</h4>
                    <ul>
                        <li><a href="<?php echo $siteUrl; ?>/#kontakt">Kontaktformular</a></li>
                        <li><a href="<?php echo $siteUrl; ?>/impressum">Impressum</a></li>
                        <li><a href="<?php echo $siteUrl; ?>/datenschutz">Datenschutz</a></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Bar -->
            <div class="biz-footer-bottom">
                <span>&copy; <?php echo gmdate('Y'); ?> <?php echo $siteTitle; ?>. Alle Rechte vorbehalten.</span>
                <nav class="biz-footer-legal" aria-label="Rechtliche Links">
                    <?php biz_nav_menu('footer-legal'); ?>
                </nav>
            </div>
        </div>
    </footer>

</div><!-- /.biz-site -->

<?php \CMS\Hooks::doAction('before_footer'); ?>

</body>
</html>
