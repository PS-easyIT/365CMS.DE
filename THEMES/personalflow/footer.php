<?php
/**
 * PersonalFlow Theme – Footer Template
 *
 * @package PersonalFlow_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;

try {
    $c             = \CMS\Services\ThemeCustomizer::instance();
    $footerText    = $c->get('footer', 'footer_text', 'Die smarte HR-Plattform für Talente und Unternehmen.');
    $showHrLinks   = $c->get('footer', 'show_hr_links', true);
    $copyrightTpl  = $c->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');
} catch (\Throwable $e) {
    $footerText   = 'PersonalFlow';
    $showHrLinks  = true;
    $copyrightTpl = '&copy; {year} {site_title}. Alle Rechte vorbehalten.';
}

$copyright = str_replace(
    ['{year}', '{site_title}'],
    [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')],
    $copyrightTpl
);
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
    </div><!-- #content .pf-site-content -->

    <footer id="pf-footer" class="pf-footer" role="contentinfo" aria-label="Fußbereich">
        <div class="pf-footer-content">
            <div class="pf-footer-container">
                <div class="pf-footer-widgets">

                    <div class="pf-footer-widget">
                        <h3><?php echo $safe($siteTitle); ?></h3>
                        <p><?php echo nl2br($safe($footerText)); ?></p>
                    </div>

                    <?php if ($showHrLinks) : ?>
                        <div class="pf-footer-widget">
                            <h3>Für Kandidaten</h3>
                            <ul>
                                <li><a href="<?php echo $safe($siteUrl); ?>/jobs">Offene Stellen</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/candidates/register">Profil anlegen</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/career-tips">Karrieretipps</a></li>
                            </ul>
                        </div>
                        <div class="pf-footer-widget">
                            <h3>Für Unternehmen</h3>
                            <ul>
                                <li><a href="<?php echo $safe($siteUrl); ?>/candidates">Kandidaten suchen</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/employers/register">Als Arbeitgeber registrieren</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/pricing">Preise & Pakete</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
        <div class="pf-footer-bottom">
            <div class="pf-footer-container">
                <?php echo $copyright; ?>
            </div>
        </div>
    </footer><!-- #pf-footer -->

</div><!-- #page -->

<?php \CMS\Hooks::doAction('before_footer'); ?>
</body>
</html>
