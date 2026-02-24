<?php
if (!defined('ABSPATH')) exit;
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$siteUrl      = SITE_URL;
try {
    $c            = \CMS\Services\ThemeCustomizer::instance();
    $footerText   = $c->get('footer', 'footer_text', 'Die Gesundheitsplattform für Ärzte und Patienten.');
    $imprintTitle = $c->get('dsgvo_medical', 'imprint_doctor_title', 'Medizinischer Betreiber: Dr. med. ...');
    $copyrightTpl = $c->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');
    $disclaimer   = $c->get('footer', 'footer_disclaimer', '');
} catch (\Throwable $e) {
    $footerText = ''; $imprintTitle = ''; $copyrightTpl = '&copy; {year} {site_title}.'; $disclaimer = '';
}
$copyright = str_replace(['{year}','{site_title}'], [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')], $copyrightTpl);
$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
    </div><!-- #content -->
    <footer id="mc-footer" class="mc-footer" role="contentinfo">
        <div class="mc-container">
            <div class="mc-footer-widgets">
                <div>
                    <h3><?php echo $safe($siteTitle); ?></h3>
                    <p><?php echo nl2br($safe($footerText)); ?></p>
                    <?php if (!empty($imprintTitle)) : ?>
                        <p style="margin-top:.75rem;font-size:var(--font-xs);opacity:.6;"><?php echo $safe($imprintTitle); ?></p>
                    <?php endif; ?>
                </div>
                <div><h3>Für Patienten</h3><ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/aerzte">Arzt suchen</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/termin">Termin buchen</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/fachgebiete">Fachgebiete</a></li>
                </ul></div>
                <div><h3>Für Ärzte</h3><ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/register">Profil anlegen</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/pricing">Pakete &amp; Preise</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/login">Anmelden</a></li>
                </ul></div>
                <div><h3>Rechtliches</h3><ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/datenschutz">Datenschutz</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/impressum">Impressum</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/agb">AGB</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/cookie-richtlinie">Cookie-Richtlinie</a></li>
                </ul></div>
            </div>
            <?php if (!empty(trim($disclaimer))) : ?>
            <div class="mc-footer-disclaimer" role="note">
                <p><?php echo nl2br($safe($disclaimer)); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <div class="mc-footer-bottom">
            <div class="mc-container">
                <span><?php echo $copyright; ?></span>
                <nav class="mc-footer-legal" aria-label="Rechtliche Links">
                    <a href="<?php echo $safe($siteUrl); ?>/datenschutz">Datenschutz</a>
                    <a href="<?php echo $safe($siteUrl); ?>/impressum">Impressum</a>
                    <a href="<?php echo $safe($siteUrl); ?>/agb">AGB</a>
                </nav>
            </div>
        </div>
    </footer>
</div><!-- #page -->
<?php \CMS\Hooks::doAction('before_footer'); ?>
</body>
</html>
