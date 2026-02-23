<?php
$safe = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
try { $c = \CMS\Services\ThemeCustomizer::instance(); } catch (\Throwable $e) { $c = null; }
$copyrightText = $c ? $c->get('footer', 'copyright_text', '') : '';
$siteTitle     = \CMS\ThemeManager::instance()->getSiteTitle();
$siteUrl       = SITE_URL;
$year          = date('Y');
?>
</div><!-- #content .ac-site-content -->
<footer id="colophon" class="ac-site-footer" role="contentinfo">
    <div class="ac-footer-inner ac-container">
        <div class="ac-footer-brand">
            <a href="<?php echo $safe($siteUrl); ?>" class="ac-footer-logo">
                <span aria-hidden="true">🎓</span><?php echo $safe($siteTitle); ?>
            </a>
            <p class="ac-footer-tagline">Lernen ohne Grenzen. Starte noch heute.</p>
        </div>
        <nav class="ac-footer-nav" aria-label="Fußzeilen-Navigation">
            <div class="ac-footer-col">
                <h4>Für Lernende</h4>
                <ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/courses">Alle Kurse</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/categories">Kategorien</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/certificates">Zertifikate</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/pro">Academy365 Pro</a></li>
                </ul>
            </div>
            <div class="ac-footer-col">
                <h4>Für Tutoren</h4>
                <ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/tutor-register">Tutor werden</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/tutor-guide">Tutor-Leitfaden</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/earnings">Verdienst-Rechner</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/tutor-faq">FAQ Tutoren</a></li>
                </ul>
            </div>
            <div class="ac-footer-col">
                <h4>Unternehmen</h4>
                <ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/business">Business-Plan</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/enterprise">Enterprise</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/partner">Partner werden</a></li>
                </ul>
            </div>
            <div class="ac-footer-col">
                <h4>Plattform</h4>
                <ul>
                    <li><a href="<?php echo $safe($siteUrl); ?>/about">Über uns</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/blog">Blog</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/contact">Kontakt</a></li>
                    <li><a href="<?php echo $safe($siteUrl); ?>/help">Hilfe</a></li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="ac-footer-bottom">
        <div class="ac-container">
            <p>
                <?php if ($copyrightText && trim($copyrightText) !== '') : ?>
                    <?php echo $safe($copyrightText); ?>
                <?php else : ?>
                    &copy; <?php echo $year; ?> <?php echo $safe($siteTitle); ?>. Alle Rechte vorbehalten.
                <?php endif; ?>
            </p>
            <?php theme_nav_menu('footer-legal'); ?>
        </div>
    </div>
</footer><!-- #colophon -->
</div><!-- #page .ac-page-wrapper -->
<?php \CMS\Hooks::doAction('before_footer'); ?>
</body>
</html>
