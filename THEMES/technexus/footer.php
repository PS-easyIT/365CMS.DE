<?php
/**
 * TechNexus Theme – Footer Template
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;

try {
    $c = \CMS\Services\ThemeCustomizer::instance();
    $footerText        = $c->get('footer', 'footer_text', 'Die führende Plattform für IT-Experten, Tech-Teams und Softwarehäuser.');
    $showTechLinks     = $c->get('footer', 'show_tech_links', true);
    $copyrightTemplate = $c->get('footer', 'copyright_text', '&copy; {year} {site_title}. Alle Rechte vorbehalten.');
} catch (\Throwable $e) {
    $footerText        = 'IT-Expert-Netzwerk';
    $showTechLinks     = true;
    $copyrightTemplate = '&copy; {year} {site_title}. Alle Rechte vorbehalten.';
}

$copyrightText = str_replace(
    ['{year}', '{site_title}'],
    [gmdate('Y'), htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8')],
    $copyrightTemplate
);

$safe = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
    </div><!-- #content .site-content -->

    <footer id="colophon" class="site-footer" role="contentinfo" aria-label="Fußbereich">

        <div class="footer-content-band">
            <div class="footer-container">
                <div class="footer-widgets">

                    <!-- Branding -->
                    <div class="footer-widget">
                        <h3 class="footer-widget-title"><?php echo $safe($siteTitle); ?></h3>
                        <p><?php echo nl2br($safe($footerText)); ?></p>
                    </div>

                    <?php if ($showTechLinks) : ?>
                    <!-- Tech-Kategorien -->
                    <div class="footer-widget">
                        <h3 class="footer-widget-title">Netzwerk</h3>
                        <ul>
                            <li><a href="<?php echo $safe($siteUrl); ?>/it-experts">IT-Experten</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/companies">Tech-Unternehmen</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/events">Tech Events</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/jobs">IT Jobs</a></li>
                            <li><a href="<?php echo $safe($siteUrl); ?>/marketplace">Marketplace</a></li>
                        </ul>
                    </div>

                    <!-- Mitglieder -->
                    <div class="footer-widget">
                        <h3 class="footer-widget-title">Mitglieder</h3>
                        <ul>
                            <?php if ($isLoggedIn) : ?>
                                <li><a href="<?php echo $safe($siteUrl); ?>/member">Mein Dashboard</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/member/profile">Mein Profil</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/member/projects">Meine Projekte</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/logout">Abmelden</a></li>
                            <?php else : ?>
                                <li><a href="<?php echo $safe($siteUrl); ?>/login">Anmelden</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/register">Registrieren (kostenlos)</a></li>
                                <li><a href="<?php echo $safe($siteUrl); ?>/register?role=expert">Experten-Profil anlegen</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php else : ?>
                        <div class="footer-widget">
                            <?php theme_nav_menu('footer-nav'); ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-container">
                <?php echo $copyrightText; ?>
                <?php
                // Rechtliche Links
                $legalMenu = \CMS\ThemeManager::instance()->getMenu('footer-legal');
                if (!empty($legalMenu)) : ?>
                    <span aria-hidden="true"> · </span>
                    <?php theme_nav_menu('footer-legal'); ?>
                <?php endif; ?>
            </div>
        </div>

    </footer><!-- #colophon -->

</div><!-- #page -->

<?php \CMS\Hooks::doAction('before_footer'); ?>
<script>
// TechNexus – Theme Toggle & Header Scroll
(function() {
    const html    = document.documentElement;
    const toggle  = document.getElementById('themeToggle');
    const header  = document.getElementById('masthead');
    const mMenu   = document.getElementById('mobileMenuToggle');
    const nav     = document.querySelector('.main-navigation');
    const srch    = document.getElementById('searchToggle');
    const srchPnl = document.getElementById('searchPanel');
    const srchCls = document.getElementById('searchClose');

    // Dark mode
    const saved = localStorage.getItem('tn-color-scheme');
    if (saved) html.setAttribute('data-theme', saved);

    toggle?.addEventListener('click', () => {
        const current = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', current);
        localStorage.setItem('tn-color-scheme', current);
    });

    // Sticky header blur
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 20);
        }, { passive: true });
    }

    // Mobile menu
    mMenu?.addEventListener('click', () => {
        const expanded = mMenu.getAttribute('aria-expanded') === 'true';
        mMenu.setAttribute('aria-expanded', String(!expanded));
        nav?.classList.toggle('open', !expanded);
    });

    // Search panel
    srch?.addEventListener('click', () => {
        srchPnl?.removeAttribute('hidden');
        srchPnl?.querySelector('input')?.focus();
        srch.setAttribute('aria-expanded', 'true');
    });
    srchCls?.addEventListener('click', () => {
        srchPnl?.setAttribute('hidden', '');
        srch?.setAttribute('aria-expanded', 'false');
    });
})();
</script>
</body>
</html>
