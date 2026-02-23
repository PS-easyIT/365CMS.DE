<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteDescription(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getThemeUrl('logilink'), ENT_QUOTES, 'UTF-8'); ?>/style.css">
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="ll-body <?php echo theme_is_logged_in() ? 'is-logged-in' : ''; ?>">
<div id="page" class="ll-page-wrapper">
<a class="ll-skip-link" href="#main">Zum Inhalt springen</a>
<?php
try {
    $c = \CMS\Services\ThemeCustomizer::instance();
    $logoUrl        = $c->get('header', 'logo_url',                    '');
    $showTracking   = $c->get('header', 'show_tracking_quick_search',   true);
    $trackingPh     = $c->get('header', 'tracking_placeholder',         'Sendungsnummer eingeben …');
} catch (\Throwable $e) { $logoUrl = ''; $showTracking = true; $trackingPh = 'Sendungsnummer …'; }
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;
$safe         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<header id="masthead" class="ll-site-header" role="banner">
    <div class="ll-header-inner">
        <div class="ll-branding">
            <a href="<?php echo $safe($siteUrl); ?>" rel="home">
                <?php if (!empty($logoUrl)) : ?>
                    <img src="<?php echo $safe($logoUrl); ?>" alt="<?php echo $safe($siteTitle); ?>" width="150" height="40">
                <?php else : ?>
                    <span class="ll-logo-text"><span class="ll-logo-icon" aria-hidden="true">🚛</span><?php echo $safe($siteTitle); ?></span>
                <?php endif; ?>
            </a>
        </div>
        <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Hauptnavigation">
            <?php theme_nav_menu('primary-nav'); ?>
        </nav>
        <?php if ($showTracking) : ?>
            <form class="ll-tracking-quick" role="search" method="get" action="<?php echo $safe($siteUrl); ?>/tracking">
                <label for="ll-track-input" class="ll-visually-hidden">Sendung verfolgen</label>
                <input id="ll-track-input" type="search" name="id" placeholder="<?php echo $safe($trackingPh); ?>" autocomplete="off" aria-label="Sendungsnummer">
                <button type="submit">Verfolgen</button>
            </form>
        <?php endif; ?>
        <div class="ll-header-actions">
            <?php if ($isLoggedIn) : ?>
                <a href="<?php echo $safe($siteUrl); ?>/member" class="ll-btn ll-btn-accent">Dashboard</a>
            <?php else : ?>
                <a href="<?php echo $safe($siteUrl); ?>/login"    class="ll-btn ll-btn-outline">Anmelden</a>
                <a href="<?php echo $safe($siteUrl); ?>/register" class="ll-btn ll-btn-accent">Kostenlos starten</a>
            <?php endif; ?>
            <button id="mobileMenuToggle" class="ll-mobile-toggle" aria-label="Menü öffnen" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header><!-- #masthead -->
<div id="content" class="ll-site-content">
