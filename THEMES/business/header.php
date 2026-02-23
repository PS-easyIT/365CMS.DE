<?php
/**
 * Business Theme – Header
 *
 * @package IT_Business_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

$tm       = \CMS\ThemeManager::instance();
$title    = biz_site_title();
$siteUrl  = biz_site_url();

try {
    $_logoUrl = \CMS\Services\ThemeCustomizer::instance()->get('header', 'logo_url', '');
} catch (\Throwable $_e) {
    $_logoUrl = '';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>

<body>
<a class="skip-link" href="#main-content">Zum Inhalt springen</a>

<div class="biz-site">

    <header class="biz-header" id="biz-masthead">
        <div class="biz-container">
            <div class="biz-header-inner">

                <!-- Logo -->
                <a href="<?php echo $siteUrl; ?>/" class="biz-logo" aria-label="<?php echo $title; ?>">
                    <?php if (!empty($_logoUrl)) : ?>
                        <img src="<?php echo htmlspecialchars($_logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                             alt="<?php echo $title; ?>" class="biz-logo-img">
                    <?php else : ?>
                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect width="32" height="32" rx="8" fill="#6366f1"/>
                            <path d="M8 10h4v12H8V10zm6 0h4v12h-4V10zm6 4h4v8h-4v-8z" fill="white"/>
                        </svg>
                        <span class="biz-logo-text"><?php echo $title; ?></span>
                    <?php endif; ?>
                </a>

                <!-- Desktop Navigation -->
                <nav class="biz-nav" id="biz-main-nav" aria-label="Hauptnavigation">
                    <?php biz_nav_menu('primary'); ?>
                </nav>

                <!-- Header CTA & Mobile Toggle -->
                <div class="biz-header-cta">
                    <a href="<?php echo $siteUrl; ?>/#kontakt" class="btn-biz btn-biz-primary">
                        Kontakt
                    </a>
                </div>

                <!-- Mobile Toggle -->
                <button class="biz-mobile-toggle" id="bizMobileToggle"
                        aria-label="Menü öffnen" aria-expanded="false" aria-controls="bizMobileDrawer"
                        type="button">
                    <span class="line"></span>
                    <span class="line"></span>
                    <span class="line"></span>
                </button>

            </div>
        </div>
    </header>

    <!-- Mobile Overlay & Drawer -->
    <div class="biz-mobile-overlay" id="bizMobileOverlay" role="presentation"></div>
    <nav id="bizMobileDrawer" class="biz-mobile-drawer" aria-label="Mobile Navigation" aria-hidden="true">
        <?php biz_nav_menu('primary'); ?>
        <div class="biz-drawer-cta">
            <a href="<?php echo $siteUrl; ?>/#kontakt"
               class="btn-biz btn-biz-primary"
               style="width:100%;justify-content:center;display:flex;">
                Kontakt aufnehmen
            </a>
        </div>
    </nav>

    <main id="main-content" class="biz-content">
