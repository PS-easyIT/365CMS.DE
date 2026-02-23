<?php
if (!defined('ABSPATH')) exit;
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$siteUrl      = SITE_URL;
try {
    $c            = \CMS\Services\ThemeCustomizer::instance();
    $footerText   = $c->get('footer', 'footer_text', 'Die digitale Plattform für Logistik und Transport.');
    $copyrightTpl = $c->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');
} catch (\Throwable $e) { $footerText = ''; $copyrightTpl = '&copy; {year} {site_title}.'; }
$copyright = str_replace(['{year}','{site_title}'], [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')], $copyrightTpl);
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
    </div><!-- #content -->
    <footer id="ll-footer" class="ll-footer" role="contentinfo">
        <div class="ll-container">
            <div class="ll-footer-widgets">
                <div><h3><?php echo $safe($siteTitle); ?></h3><p><?php echo nl2br($safe($footerText)); ?></p></div>
                <div><h3>Services</h3><ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/tracking">Sendung verfolgen</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/partners">Spediteure</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/routes">Routen</a></li>
                </ul></div>
                <div><h3>Konto</h3><ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/register">Registrieren</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/login">Anmelden</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/member">Dashboard</a></li>
                </ul></div>
            </div>
        </div>
        <div class="ll-footer-bottom"><div class="ll-container"><?php echo $copyright; ?></div></div>
    </footer>
</div><!-- #page -->
<?php \CMS\Hooks::doAction('before_footer'); ?>
</body>
</html>
