<?php
/**
 * Meridian CMS Default – Header Template
 *
 * @package CMSv2\Themes\CmsDefault
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$logoUrl       = meridian_setting('header', 'logo_url', '');
$logoText      = meridian_setting('header', 'logo_text', defined('SITE_NAME') ? SITE_NAME : '365CMS');
$logoType      = meridian_setting('header', 'logo_type', 'text');
$logoTagline   = meridian_setting('header', 'logo_tagline', '');
$logoHeight    = max(20, (int)meridian_setting('header', 'logo_height', 40));
$headerTitle   = meridian_setting('header', 'header_title', '');
$showSearch    = (bool)meridian_setting('header', 'show_search_btn', true);
$showLoginBtn  = (bool)meridian_setting('header', 'show_login_btn', true);
$showRegBtn    = (bool)meridian_setting('header', 'show_register_btn', true);
$headerBarMode = meridian_setting('navigation', 'header_bar_mode', 'categories');
$stickyHeader  = (bool)meridian_setting('layout', 'sticky_header', true);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isLoggedIn  = meridian_is_logged_in();
$flashMsg    = meridian_get_flash();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Seitentitel ermitteln
    $siteTitle = defined('SITE_NAME') ? SITE_NAME : '365CMS';
    try {
        if (class_exists('\CMS\ThemeManager')) {
            $tm = \CMS\ThemeManager::instance()->getSiteTitle();
            if (!empty($tm)) { $siteTitle = $tm; }
        }
    } catch (\Throwable $e) {}
    ?>
    <title><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // Preconnect + Fonts: Local Fonts werden automatisch priorisiert wenn aktiviert
    if (function_exists('meridian_output_fonts')) {
        meridian_output_fonts();
    }
    ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/themes/cms-default/style.css?v=<?php echo defined('MERIDIAN_THEME_VERSION') ? MERIDIAN_THEME_VERSION : '1.0.0'; ?>">
    <?php
    // Theme-Customizer CSS-Variablen direkt nach style.css ausgeben
    if (function_exists('meridian_output_custom_styles')) {
        meridian_output_custom_styles();
    }
    // SEO meta tags + Custom Head Code via Hooks
    if (class_exists('\CMS\Hooks')) {
        \CMS\Hooks::doAction('head');
    }
    ?>
</head>
<body class="meridian-theme">

<?php if ($flashMsg): ?>
<div class="alert alert-<?php echo htmlspecialchars($flashMsg['type'] ?? 'info'); ?>" style="border-radius:0;margin:0;">
    <?php echo htmlspecialchars($flashMsg['message'] ?? ''); ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════
     HEADER
════════════════════════════════════════ -->
<header class="site-header<?php echo !$stickyHeader ? ' site-header--static' : ''; ?>">
  <div class="header-inner">

    <!-- Logo + optionaler Titel daneben -->
    <div class="site-logo-group">
      <a href="<?php echo SITE_URL; ?>/" class="site-logo" aria-label="<?php echo htmlspecialchars($logoText); ?> – Startseite">
        <?php if ($logoType === 'image' && $logoUrl): ?>
            <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($logoText); ?>" height="<?php echo $logoHeight; ?>" style="max-height:<?php echo $logoHeight; ?>px;display:block;width:auto;">
        <?php else: ?>
            <span class="logo-word"><?php echo htmlspecialchars($logoText); ?></span>
            <span class="logo-dot"></span>
            <?php if ($logoTagline): ?>
            <span class="logo-tagline"><?php echo htmlspecialchars($logoTagline); ?></span>
            <?php endif; ?>
        <?php endif; ?>
      </a>
      <?php if ($headerTitle): ?>
      <span class="header-site-title"><?php echo htmlspecialchars($headerTitle); ?></span>
      <?php endif; ?>
    </div>

    <!-- Primary Nav -->
    <nav class="primary-nav">
        <?php
        $navItems = [
            ['label' => 'Startseite', 'href' => SITE_URL . '/'],
            ['label' => 'Blog',        'href' => SITE_URL . '/blog'],
        ];
        if (class_exists('\CMS\ThemeManager')) {
            $primaryMenu = \CMS\ThemeManager::instance()->getMenu('primary');
            if (!empty($primaryMenu)) {
                $navItems = $primaryMenu;
            }
        }

        foreach ($navItems as $item):
            $href    = is_array($item) ? ($item['href'] ?? $item['url'] ?? '#') : '#';
            $label   = is_array($item) ? ($item['label'] ?? $item['title'] ?? '') : $item;
            $isActive = rtrim($currentPath, '/') === rtrim(parse_url($href, PHP_URL_PATH) ?? '/', '/');
            $children = is_array($item) ? ($item['children'] ?? []) : [];

            if ($children):
        ?>
            <div class="nav-group">
                <a href="<?php echo htmlspecialchars($href); ?>">
                    <?php echo htmlspecialchars($label); ?>
                    <svg viewBox="0 0 10 10"><polyline points="2,3 5,7 8,3"/></svg>
                </a>
                <div class="nav-dropdown">
                    <?php foreach ($children as $child):
                        $childHref  = is_array($child) ? ($child['href'] ?? $child['url'] ?? '#') : '#';
                        $childLabel = is_array($child) ? ($child['label'] ?? $child['title'] ?? '') : $child;
                    ?>
                    <a href="<?php echo htmlspecialchars($childHref); ?>"><?php echo htmlspecialchars($childLabel); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($href); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($label); ?>
            </a>
        <?php endif; endforeach; ?>

        <div class="nav-spacer"></div>
    </nav>

    <!-- Header Actions -->
    <div class="header-actions">
      <?php if ($showSearch): ?>
      <button class="btn-icon" id="searchToggle" aria-label="Suche">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
      <?php endif; ?>

      <?php if ($isLoggedIn): ?>
          <a href="<?php echo SITE_URL; ?>/member/profile" class="btn-ghost">Mein Bereich</a>
          <a href="<?php echo SITE_URL; ?>/logout" class="btn-ghost">Logout</a>
      <?php else: ?>
          <?php if ($showLoginBtn): ?>
          <a href="<?php echo SITE_URL; ?>/login" class="btn-ghost">Anmelden</a>
          <?php endif; ?>
          <?php if ($showRegBtn): ?>
          <a href="<?php echo SITE_URL; ?>/register" class="btn-solid">Registrieren</a>
          <?php endif; ?>
      <?php endif; ?>

      <!-- Hamburger (nur mobil sichtbar via CSS) -->
      <button class="nav-toggle" id="navToggle" aria-label="Menü öffnen" aria-expanded="false" aria-controls="mobileNavPanel">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
      </button>
    </div>
  </div>
</header>

<!-- Kategorie/Menü-Leiste -->
<?php if ($headerBarMode !== 'none'): ?>
<div class="category-bar">
  <div class="category-bar-inner">
    
    <?php if ($headerBarMode === 'categories'): ?>
        <span class="cat-label">Kategorien</span>
        <a href="<?php echo SITE_URL; ?>/blog" class="<?php echo ($currentPath === '/blog' && !isset($_GET['category'])) ? 'active' : ''; ?>">Alle</a>
        <?php
        $cats = function_exists('meridian_get_categories') ? meridian_get_categories(8) : [];
        if (!empty($cats)):
            foreach ($cats as $cat):
                $catSlug = $cat['slug'] ?? '';
                $catName = $cat['name'] ?? '';
                $isActive = (isset($_GET['category']) && $_GET['category'] === $catSlug);
        ?>
        <a href="<?php echo SITE_URL; ?>/blog?category=<?php echo urlencode($catSlug); ?>" class="<?php echo $isActive ? 'active' : ''; ?>"><?php echo htmlspecialchars($catName); ?></a>
        <?php endforeach; endif; ?>

    <?php elseif ($headerBarMode === 'menu'): ?>
        <?php
        $secMenu = [];
        try {
            $secMenu = \CMS\ThemeManager::instance()->getMenu('secondary') ?? [];
        } catch (\Throwable $e) {}
        if (!empty($secMenu)):
            foreach ($secMenu as $item):
                $mUrl   = $item['url'] ?? '#';
                $mLabel = $item['label'] ?? '';
        ?>
            <a href="<?php echo htmlspecialchars($mUrl); ?>"><?php echo htmlspecialchars($mLabel); ?></a>
        <?php endforeach; else: ?>
            <span style="font-size:0.8rem;color:var(--ink-muted);">Kein Menü für Position "Sekundär" zugewiesen.</span>
        <?php endif; ?>
    <?php endif; ?>

  </div>
</div>
<?php endif; ?>

<!-- Mobile Nav Overlay -->
<div id="mobileNavOverlay" class="mobile-nav-overlay" aria-hidden="true"></div>

<!-- Mobile Nav Panel (Slide-in von rechts) -->
<nav id="mobileNavPanel" class="mobile-nav-panel" aria-hidden="true" inert aria-label="Mobile Navigation">
  <div class="mobile-nav-header">
    <a href="<?php echo SITE_URL; ?>/" class="site-logo" aria-label="<?php echo htmlspecialchars($logoText); ?> – Startseite" style="text-decoration:none;">
      <?php if ($logoType === 'image' && $logoUrl): ?>
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($logoText); ?>" height="32" style="max-height:32px;width:auto;">
      <?php else: ?>
        <span class="logo-word" style="font-size:1rem;"><?php echo htmlspecialchars($logoText); ?></span>
        <span class="logo-dot"></span>
      <?php endif; ?>
    </a>
    <button id="mobileNavClose" class="btn-icon" aria-label="Menü schließen" style="margin-left:auto;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div class="mobile-nav-body">
    <?php foreach ($navItems as $item):
        $mHref    = is_array($item) ? ($item['href'] ?? $item['url'] ?? '#') : '#';
        $mLabel   = is_array($item) ? ($item['label'] ?? $item['title'] ?? '') : $item;
        $mActive  = rtrim($currentPath, '/') === rtrim(parse_url($mHref, PHP_URL_PATH) ?? '/', '/');
        $mChildren = is_array($item) ? ($item['children'] ?? []) : [];
    ?>
    <a href="<?php echo htmlspecialchars($mHref); ?>"
       class="mobile-nav-link<?php echo $mActive ? ' active' : ''; ?>">
      <?php echo htmlspecialchars($mLabel); ?>
    </a>
    <?php foreach ($mChildren as $child):
        $cHref  = is_array($child) ? ($child['href'] ?? $child['url'] ?? '#') : '#';
        $cLabel = is_array($child) ? ($child['label'] ?? $child['title'] ?? '') : $child;
    ?>
    <a href="<?php echo htmlspecialchars($cHref); ?>"
       class="mobile-nav-link"
       style="padding-left:2.5rem;font-size:.83rem;opacity:.8;">
      <?php echo htmlspecialchars($cLabel); ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>

    <div style="height:1px;background:var(--rule);margin:.5rem 1.25rem;"></div>

    <?php if ($isLoggedIn): ?>
      <a href="<?php echo SITE_URL; ?>/member/profile" class="mobile-nav-link">👤 Mein Bereich</a>
      <a href="<?php echo SITE_URL; ?>/logout" class="mobile-nav-link">⬡ Logout</a>
    <?php else: ?>
      <?php if ($showLoginBtn): ?>
      <a href="<?php echo SITE_URL; ?>/login" class="mobile-nav-link">🔑 Anmelden</a>
      <?php endif; ?>
      <?php if ($showRegBtn): ?>
      <a href="<?php echo SITE_URL; ?>/register" class="mobile-nav-link" style="color:var(--accent);font-weight:600;">✨ Registrieren</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($showSearch): ?>
  <div class="mobile-nav-search">
    <form action="<?php echo SITE_URL; ?>/search" method="GET" role="search" style="display:flex;gap:.5rem;">
      <input type="search" name="q" placeholder="Suchen…" class="form-control form-control--sm" aria-label="Suche" style="flex:1;">
      <button type="submit" class="btn-submit" aria-label="Suche absenden" style="padding:.35rem .85rem;font-size:.82rem;">→</button>
    </form>
  </div>
  <?php endif; ?>
</nav>

<!-- Main Content Wrapper startet hier -->
<main class="site-main">