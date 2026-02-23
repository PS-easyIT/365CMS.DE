<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteDescription(), ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getSiteTitle(), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars(\CMS\ThemeManager::instance()->getThemeUrl('medcarepro'), ENT_QUOTES, 'UTF-8'); ?>/style.css">
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="mc-body <?php echo theme_is_logged_in() ? 'is-logged-in' : ''; ?>">
<div id="page" class="mc-page-wrapper">
<a class="mc-skip-link" href="#main">Zum Inhalt springen</a>
<?php
try {
    $c = \CMS\Services\ThemeCustomizer::instance();
    $logoUrl      = $c->get('header', 'logo_url', '');
    $showSearch   = $c->get('header', 'show_search', true);
    $showFontSize = $c->get('accessibility', 'show_font_size_toggle', true);
    $showContrast = $c->get('accessibility', 'show_contrast_toggle', true);
} catch (\Throwable $e) { $logoUrl = ''; $showSearch = true; $showFontSize = false; $showContrast = false; }
$themeManager = \CMS\ThemeManager::instance();
$siteTitle    = $themeManager->getSiteTitle();
$isLoggedIn   = theme_is_logged_in();
$siteUrl      = SITE_URL;
$safe         = fn(string $v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<header id="masthead" class="mc-site-header" role="banner">
    <div class="mc-header-inner">
        <div class="mc-branding">
            <a href="<?php echo $safe($siteUrl); ?>" rel="home">
                <?php if (!empty($logoUrl)) : ?>
                    <img src="<?php echo $safe($logoUrl); ?>" alt="<?php echo $safe($siteTitle); ?>" width="150" height="44">
                <?php else : ?>
                    <span class="mc-logo-text"><span class="mc-logo-cross" aria-hidden="true">✚</span><?php echo $safe($siteTitle); ?></span>
                <?php endif; ?>
            </a>
        </div>
        <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Hauptnavigation">
            <?php theme_nav_menu('primary-nav'); ?>
        </nav>
        <div class="mc-header-actions">
            <?php if ($showFontSize) : ?>
                <button id="fontSizeToggle" class="mc-btn mc-btn-ghost mc-font-size-toggle" aria-label="Schriftgröße ändern" title="Schriftgröße">A±</button>
            <?php endif; ?>
            <?php if ($showContrast) : ?>
                <button id="contrastToggle" class="mc-btn mc-btn-ghost mc-font-size-toggle" aria-label="Kontrast wechseln" title="Hoher Kontrast">◑</button>
            <?php endif; ?>
            <?php if ($showSearch) : ?>
                <button id="searchToggle" class="mc-btn mc-btn-ghost" style="padding:.5rem;" aria-label="Suche öffnen" aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </button>
            <?php endif; ?>
            <?php if ($isLoggedIn) : ?>
                <a href="<?php echo $safe($siteUrl); ?>/member" class="mc-btn mc-btn-primary">Mein Bereich</a>
            <?php else : ?>
                <a href="<?php echo $safe($siteUrl); ?>/login"    class="mc-btn mc-btn-ghost">Anmelden</a>
                <a href="<?php echo $safe($siteUrl); ?>/register" class="mc-btn mc-btn-primary">Arzt registrieren</a>
            <?php endif; ?>
            <button id="mobileMenuToggle" class="mc-mobile-toggle" aria-label="Menü öffnen" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
    <?php if ($showSearch) : ?>
    <div id="searchPanel" class="mc-search-panel" hidden style="background:var(--bg-secondary);border-top:1px solid var(--border-color);padding:.75rem 0;">
        <div class="mc-header-inner">
            <form role="search" method="get" action="<?php echo $safe($siteUrl); ?>/search" style="display:flex;gap:.5rem;flex:1;">
                <label for="mc-search" class="mc-visually-hidden">Arzt/Fachgebiet suchen</label>
                <input id="mc-search" type="search" name="q" placeholder="Arzt, Fachgebiet, PLZ suchen …" autocomplete="off" style="flex:1;padding:.6rem .9rem;border:1px solid var(--border-color);border-radius:var(--radius-pill);font-family:var(--font-body);">
                <button type="submit" class="mc-btn mc-btn-primary" aria-label="Suchen">Suchen</button>
            </form>
            <button id="searchClose" class="mc-btn mc-btn-ghost" aria-label="Schließen">✕</button>
        </div>
    </div>
    <?php endif; ?>
</header><!-- #masthead -->
<div id="content" class="mc-site-content">
