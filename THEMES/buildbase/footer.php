<?php
if (!defined('ABSPATH')) exit;
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;
try {
    $c            = \CMS\Services\ThemeCustomizer::instance();
    $footerText   = $c->get('footer', 'footer_text', 'Ihr starkes Netzwerk für Bauprojekte und Handwerk.');
    $showLinks    = $c->get('footer', 'show_trade_links', true);
    $copyrightTpl = $c->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');
} catch (\Throwable $e) {
    $footerText   = '';
    $showLinks    = true;
    $copyrightTpl = '&copy; {year} {site_title}.';
}
$copyright = str_replace(['{year}','{site_title}'], [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')], $copyrightTpl);
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
    </div><!-- #content -->

    <footer id="bb-footer" class="bb-footer" role="contentinfo">
        <div class="bb-container">
            <div class="bb-footer-widgets">
                <div>
                    <h3><?php echo $safe($siteTitle); ?></h3>
                    <p><?php echo nl2br($safe($footerText)); ?></p>
                </div>
                <?php if ($showLinks) : ?>
                    <div>
                        <h3>Handwerk</h3>
                        <ul>
                            <li><a href="<?php echo $safe($siteUrl); ?>/handwerker">Handwerker finden</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/baufirmen">Baufirmen</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/angebot">Angebot anfragen</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/projekte">Referenzprojekte</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3>Für Fachbetriebe</h3>
                        <ul>
                            <li><a href="<?php echo $safe($siteUrl); ?>/register">Profil anlegen</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/pricing">Pakete & Preise</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bb-footer-bottom">
            <div class="bb-container"><?php echo $copyright; ?></div>
        </div>
    </footer>

</div><!-- #page -->
<?php \CMS\Hooks::doAction('before_footer'); ?>
</body>
</html>
