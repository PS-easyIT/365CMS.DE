<?php
/**
 * Header Template
 *
 * @package IT_Expert_Network_Theme
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
    $_headerLogoUrl = \CMS\Services\ThemeCustomizer::instance()->get('header', 'logo_url', '');
} catch (\Throwable $_e) {
    $_headerLogoUrl = '';
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

    <header id="masthead" class="site-header" role="banner">
        <div class="header-container">
            <div class="header-inner">

                <!-- Branding -->
                <div class="site-branding">
                    <div class="site-logo">
                        <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/" aria-label="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($_headerLogoUrl)) : ?>
                                <img src="<?php echo htmlspecialchars($_headerLogoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="<?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                     style="max-height:var(--logo-max-height,48px);height:auto;display:block;">
                            <?php else : ?>
                                <svg class="network-icon" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <circle cx="30" cy="30" r="6" fill="currentColor"/>
                                    <circle cx="15" cy="15" r="5" fill="currentColor"/>
                                    <circle cx="45" cy="15" r="5" fill="currentColor"/>
                                    <circle cx="15" cy="45" r="5" fill="currentColor"/>
                                    <circle cx="45" cy="45" r="5" fill="currentColor"/>
                                    <line x1="30" y1="30" x2="15" y2="15" stroke="currentColor" stroke-width="2"/>
                                    <line x1="30" y1="30" x2="45" y2="15" stroke="currentColor" stroke-width="2"/>
                                    <line x1="30" y1="30" x2="15" y2="45" stroke="currentColor" stroke-width="2"/>
                                    <line x1="30" y1="30" x2="45" y2="45" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="site-identity">
                        <h1 class="site-title">
                            <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/" rel="home">
                                <?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </h1>
                        <?php if ($siteDesc && trim($siteDesc) !== '') : ?>
                            <p class="site-description"><?php echo htmlspecialchars($siteDesc, ENT_QUOTES, 'UTF-8'); ?></p>
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
                        <span class="theme-toggle-icon dark-icon">🌙</span>
                        <span class="theme-toggle-icon light-icon">☀️</span>
                    </button>

                    <!-- Search Toggle -->
                    <button class="search-toggle" id="searchToggle" aria-label="Suche öffnen" type="button" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>

                    <!-- Toggle Buttons removed -->

                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Menü öffnen"
                            aria-expanded="false" aria-controls="mobileMenuDrawer" type="button">
                        <span class="hamburger" aria-hidden="true">
                            <span class="line"></span>
                            <span class="line"></span>
                            <span class="line"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" role="presentation"></div>

    <!-- Mobile Menu Drawer -->
    <nav id="mobileMenuDrawer" class="mobile-menu-drawer" aria-label="Mobile Navigation" aria-hidden="true">
        <?php theme_nav_menu('mobile'); ?>
    </nav>

    <!-- Search Overlay -->
    <div class="search-overlay" id="searchOverlay" role="dialog" aria-label="Schnellsuche" aria-hidden="true">
        <button class="search-overlay-close" id="searchOverlayClose" aria-label="Suche schließen" type="button">×</button>
        <div class="search-overlay-inner">
            <form class="search-overlay-form" action="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/search" method="GET">
                <input class="search-overlay-input" type="search" name="q" placeholder="Suche…"
                       autocomplete="off" spellcheck="false" aria-label="Suchbegriff eingeben">
                <button type="submit" class="search-overlay-submit">Suchen</button>
            </form>
        </div>
    </div>

    <div id="content" class="site-content">
