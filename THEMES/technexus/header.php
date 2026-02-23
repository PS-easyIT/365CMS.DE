<?php
/**
 * TechNexus Theme – Header Template
 *
 * @package TechNexus_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$siteDesc     = $themeManager->getSiteDescription();
$themeUrl     = $themeManager->getThemeUrl();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;

try {
    $c              = \CMS\Services\ThemeCustomizer::instance();
    $_logoUrl       = $c->get('header', 'logo_url', '');
    $_showStatusDot = $c->get('header', 'show_status_dot', true);
    $_blurHeader    = $c->get('header', 'enable_blur_header', true);
} catch (\Throwable $_e) {
    $_logoUrl       = '';
    $_showStatusDot = true;
    $_blurHeader    = true;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>

<body>
<div id="page" class="site">
    <a class="skip-link" href="#content">Zum Inhalt springen</a>

    <header id="masthead" class="site-header<?php echo $_blurHeader ? ' blur-enabled' : ''; ?>" role="banner">
        <div class="header-container">
            <div class="header-inner">

                <!-- Branding -->
                <div class="site-branding">
                    <div class="site-logo">
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/"
                           aria-label="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($_logoUrl)) : ?>
                                <img src="<?php echo htmlspecialchars($_logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                     style="max-height:var(--logo-max-height,40px);height:auto;display:block;">
                            <?php else : ?>
                                <!-- Tech Network Default Icon -->
                                <svg style="height:40px;width:40px;color:var(--primary-color);" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <rect x="2" y="2" width="56" height="56" rx="12" fill="currentColor" opacity="0.1"/>
                                    <circle cx="30" cy="30" r="4" fill="currentColor"/>
                                    <circle cx="14" cy="14" r="3.5" fill="currentColor"/>
                                    <circle cx="46" cy="14" r="3.5" fill="currentColor"/>
                                    <circle cx="14" cy="46" r="3.5" fill="currentColor"/>
                                    <circle cx="46" cy="46" r="3.5" fill="currentColor"/>
                                    <line x1="30" y1="30" x2="14" y2="14" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
                                    <line x1="30" y1="30" x2="46" y2="14" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
                                    <line x1="30" y1="30" x2="14" y2="46" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
                                    <line x1="30" y1="30" x2="46" y2="46" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
                                </svg>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="site-identity">
                        <span class="site-title">
                            <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/" rel="home">
                                <?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </span>
                        <?php if ($_showStatusDot && $isLoggedIn) : ?>
                            <span class="status-indicator" title="Online" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Hauptmenü (Desktop) -->
                <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Hauptmenü">
                    <?php theme_nav_menu('primary'); ?>
                </nav>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Dark Mode Toggle -->
                    <button class="theme-toggle" id="themeToggle" aria-label="Dark Mode umschalten" type="button">
                        <span class="theme-toggle-icon dark-icon" aria-hidden="true">🌙</span>
                        <span class="theme-toggle-icon light-icon" aria-hidden="true">☀️</span>
                    </button>

                    <!-- Search Toggle -->
                    <button class="search-toggle" id="searchToggle" aria-label="Suche öffnen" type="button" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>

                    <!-- User Actions -->
                    <?php if ($isLoggedIn) : ?>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/member"
                           class="btn btn-outline" style="font-size:0.875rem;padding:0.375rem 0.875rem;">
                            Dashboard
                        </a>
                    <?php else : ?>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/login"
                           class="btn btn-outline" style="font-size:0.875rem;padding:0.375rem 0.875rem;">
                            Anmelden
                        </a>
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/register"
                           class="btn btn-primary" style="font-size:0.875rem;padding:0.375rem 0.875rem;">
                            Registrieren
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle"
                            aria-label="Menü öffnen" aria-expanded="false" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                </div>

            </div><!-- .header-inner -->
        </div><!-- .header-container -->
    </header><!-- #masthead -->

    <!-- Search Panel (hidden) -->
    <div id="searchPanel" class="search-panel" hidden role="search" aria-label="Suche">
        <div class="search-panel-inner">
            <form action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/search" method="get" class="search-form">
                <input type="search" name="q" placeholder="IT-Experten, Tech-Skills, Firmen suchen…"
                       aria-label="Suche" class="search-input" autocomplete="off">
                <button type="submit" class="btn btn-primary" aria-label="Suchen">Suchen</button>
            </form>
            <button class="search-close" id="searchClose" aria-label="Suche schließen" type="button">✕</button>
        </div>
    </div>

    <div id="content" class="site-content">
