<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteDescription(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getThemeUrl('buildbase'), ENT_QUOTES, 'UTF-8'); ?>/style.css">
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="bb-body <?php echo theme_is_logged_in() ? 'is-logged-in' : ''; ?>">
<div id="page" class="bb-page-wrapper">

    <a class="bb-skip-link" href="#main">Zum Inhalt springen</a>

    <?php
    try {
        $c = \CMS\Services\ThemeCustomizer::instance();
        $showEmBanner  = $c->get('header', 'show_emergency_banner', false);
        $emergencyPhone = $c->get('header', 'emergency_phone', '+49 800 000 0000');
        $emergencyLabel = $c->get('header', 'emergency_label', '🔧 Notfall-Service:');
        $logoUrl        = $c->get('header', 'logo_url', '');
        $showSearch     = $c->get('header', 'show_search', true);
    } catch (\Throwable $e) {
        $showEmBanner  = false;
        $emergencyPhone = '';
        $emergencyLabel = '';
        $logoUrl        = '';
        $showSearch     = true;
    }
    $themeManager = \CMS\ThemeManager::instance();
    $siteTitle    = $themeManager->getSiteTitle();
    $isLoggedIn   = theme_is_logged_in();
    $siteUrl      = SITE_URL;
    $safe         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    if ($showEmBanner && !empty($emergencyPhone)) :
        echo '<div class="bb-emergency-banner" role="alert">';
        echo $safe($emergencyLabel) . ' <a href="tel:' . preg_replace('/[^0-9+]/', '', $emergencyPhone) . '" style="color:#fff;text-decoration:underline;">' . $safe($emergencyPhone) . '</a>';
        echo '</div>';
        echo '<script>document.body.classList.add("has-emergency-banner")</script>';
    endif;
    ?>

    <header id="masthead" class="bb-site-header" role="banner">
        <div class="bb-header-inner">

            <!-- Logo -->
            <div class="bb-branding">
                <a href="<?php echo $safe($siteUrl); ?>" class="bb-site-logo" rel="home">
                    <?php if (!empty($logoUrl)) : ?>
                        <img src="<?php echo $safe($logoUrl); ?>" alt="<?php echo $safe($siteTitle); ?>" width="160" height="44">
                    <?php else : ?>
                        <span class="bb-logo-text">
                            <span class="bb-logo-icon" aria-hidden="true">🔨</span>
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
            <div class="bb-header-actions">
                <?php if ($showSearch) : ?>
                    <button id="searchToggle" class="bb-icon-btn" aria-label="Suche öffnen" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </button>
                <?php endif; ?>
                <?php if ($isLoggedIn) : ?>
                    <a href="<?php echo $safe($siteUrl); ?>/member" class="bb-btn bb-btn-primary">Dashboard</a>
                <?php else : ?>
                    <a href="<?php echo $safe($siteUrl); ?>/login"    class="bb-btn bb-btn-ghost">Anmelden</a>
                    <a href="<?php echo $safe($siteUrl); ?>/register" class="bb-btn bb-btn-primary">Profil anlegen</a>
                <?php endif; ?>
                <button id="mobileMenuToggle" class="bb-mobile-toggle" aria-label="Menü öffnen" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>

        <?php if ($showSearch) : ?>
        <div id="searchPanel" class="bb-search-panel" hidden style="background:var(--bg-secondary);border-top:1px solid var(--border-color);padding:.75rem 0;">
            <div class="bb-header-inner">
                <form role="search" method="get" action="<?php echo $safe($siteUrl); ?>/search" style="display:flex;gap:.5rem;flex:1;">
                    <label for="bb-search" class="bb-visually-hidden">Suche</label>
                    <input id="bb-search" type="search" name="q" placeholder="Handwerker, Baufirmen, Projekte suchen …" autocomplete="off"
                           style="flex:1;padding:.6rem .9rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);font-family:var(--font-body);">
                    <button type="submit" class="bb-btn bb-btn-primary" aria-label="Suchen">Suchen</button>
                </form>
                <button id="searchClose" class="bb-btn bb-btn-ghost" aria-label="Suche schließen">✕</button>
            </div>
        </div>
        <?php endif; ?>
    </header><!-- #masthead -->

<div id="content" class="bb-site-content">
