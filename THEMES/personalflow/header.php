<!DOCTYPE html>
<html lang="de" id="pf-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteDescription(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getThemeUrl('personalflow'), ENT_QUOTES, 'UTF-8'); ?>/style.css">
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="pf-body <?php echo theme_is_logged_in() ? 'is-logged-in' : ''; ?>">
<div id="page" class="pf-page-wrapper">

    <a class="pf-skip-link" href="#main">Zum Inhalt springen</a>

    <header id="masthead" class="pf-site-header" role="banner">
        <?php
        try {
            $c           = \CMS\Services\ThemeCustomizer::instance();
            $logoUrl     = $c->get('header', 'logo_url', '');
            $headerStyle = $c->get('header', 'header_style', 'white');
            $showSearch  = $c->get('header', 'show_search', true);
        } catch (\Throwable $e) {
            $logoUrl     = '';
            $headerStyle = 'white';
            $showSearch  = true;
        }
        $themeManager = \CMS\ThemeManager::instance();
        $siteTitle    = $themeManager->getSiteTitle();
        $isLoggedIn   = theme_is_logged_in();
        $siteUrl      = SITE_URL;
        $safe         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="pf-header-inner">

            <!-- Logo -->
            <div class="pf-site-branding">
                <a href="<?php echo $safe($siteUrl); ?>" class="pf-site-logo" rel="home" aria-label="<?php echo $safe($siteTitle); ?> – Startseite">
                    <?php if (!empty($logoUrl)) : ?>
                        <img src="<?php echo $safe($logoUrl); ?>" alt="<?php echo $safe($siteTitle); ?>" width="160" height="44" loading="eager">
                    <?php else : ?>
                        <span class="pf-logo-text">
                            <span class="pf-logo-icon" aria-hidden="true">👥</span>
                            <?php echo $safe($siteTitle); ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Navigation -->
            <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Hauptnavigation">
                <?php theme_nav_menu('primary-nav'); ?>
            </nav>

            <!-- Aktionen -->
            <div class="pf-header-actions">
                <?php if ($showSearch) : ?>
                    <button id="searchToggle" class="pf-icon-btn" aria-label="Suche öffnen" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </button>
                <?php endif; ?>

                <?php if ($isLoggedIn) : ?>
                    <a href="<?php echo $safe($siteUrl); ?>/member" class="pf-btn pf-btn-primary" aria-label="Zum Dashboard">
                        Dashboard
                    </a>
                <?php else : ?>
                    <a href="<?php echo $safe($siteUrl); ?>/login"    class="pf-btn pf-btn-ghost">Anmelden</a>
                    <a href="<?php echo $safe($siteUrl); ?>/register" class="pf-btn pf-btn-primary">Kostenlos starten</a>
                <?php endif; ?>

                <!-- Mobile Menu Toggle -->
                <button id="mobileMenuToggle" class="pf-mobile-toggle" aria-label="Menü öffnen" aria-expanded="false" aria-controls="site-navigation">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <?php if ($showSearch) : ?>
        <div id="searchPanel" class="pf-search-panel" hidden>
            <div class="pf-header-inner">
                <form role="search" method="get" action="<?php echo $safe($siteUrl); ?>/search" class="pf-search-form">
                    <label for="pf-search" class="pf-visually-hidden">Suche</label>
                    <input id="pf-search" type="search" name="q" placeholder="Kandidaten, Jobs & Unternehmen suchen …" autocomplete="off">
                    <button type="submit" aria-label="Suchen">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </button>
                </form>
                <button id="searchClose" class="pf-icon-btn" aria-label="Suche schließen">✕</button>
            </div>
        </div>
        <?php endif; ?>

    </header><!-- #masthead -->

<div id="content" class="pf-site-content">
