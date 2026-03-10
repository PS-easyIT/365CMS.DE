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

$runtimeSiteName = (string) (function_exists('cms_get_site_name') ? cms_get_site_name() : (defined('SITE_NAME') ? SITE_NAME : '365CMS'));
$logoUrl       = trim((string) meridian_setting('header', 'logo_url', ''));
$logoText      = trim((string) meridian_setting('header', 'logo_text', $runtimeSiteName));
$logoType      = (string) meridian_setting('header', 'logo_type', 'text');
$logoTagline   = trim((string) meridian_setting('header', 'logo_tagline', ''));
$logoHeight    = max(20, (int)meridian_setting('header', 'logo_height', 40));
$headerTitle   = trim((string) meridian_setting('header', 'header_title', ''));
$showSearch    = (bool)meridian_setting('header', 'show_search_btn', true);
$showLoginBtn  = (bool)meridian_setting('header', 'show_login_btn', true);
$showRegBtn    = (bool)meridian_setting('header', 'show_register_btn', true);
$headerBarMode = (string) meridian_setting('navigation', 'header_bar_mode', 'categories');
$showCategoryBar = (bool)meridian_setting('layout', 'show_category_bar', true);
$mobileMenuEnabled = (bool)meridian_setting('navigation', 'mobile_menu_enabled', true);
$stickyHeader  = (bool)meridian_setting('layout', 'sticky_header', true);

if ($logoText === '') {
  $logoText = $runtimeSiteName;
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isLoggedIn  = meridian_is_logged_in();
$flashMsg    = meridian_get_flash();
$memberAreaUrl = (string) meridian_member_area_url();

if (!$showCategoryBar) {
  $headerBarMode = 'none';
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Seitentitel ermitteln (Priorität: SEO → ThemeManager → SITE_NAME)
    $siteTitle = function_exists('cms_get_site_title') ? cms_get_site_title() : $runtimeSiteName;
    try {
        $seo = \CMS\Services\SEOService::getInstance();
        $seoTitle = $seo->getHomepageTitle();
        if (!empty($seoTitle)) {
            $siteTitle = $seoTitle;
        } elseif (class_exists('\CMS\ThemeManager')) {
            $tm = \CMS\ThemeManager::instance()->getSiteTitle();
            if (!empty($tm)) { $siteTitle = $tm; }
        }
    } catch (\Throwable $e) {}
    ?>
    <title><?php echo htmlspecialchars($siteTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    if (class_exists('\CMS\Hooks')) {
        \CMS\Hooks::doAction('head');
    }
    ?>
</head>
<body class="meridian-theme">

  <?php if (class_exists('\CMS\Hooks')) {
    \CMS\Hooks::doAction('body_start');
  } ?>

<?php if ($flashMsg): ?>
  <div class="alert alert-<?php echo htmlspecialchars((string) ($flashMsg['type'] ?? 'info')); ?> flash-banner" role="status">
    <?php echo htmlspecialchars((string) ($flashMsg['message'] ?? '')); ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════
     HEADER
════════════════════════════════════════ -->
<header id="site-header" class="site-header<?php echo !$stickyHeader ? ' site-header--static' : ''; ?>">
  <div class="header-inner">

    <!-- Logo + optionaler Titel daneben -->
    <div class="site-logo-group">
      <a href="<?php echo SITE_URL; ?>/" class="site-logo" aria-label="<?php echo htmlspecialchars((string) $logoText); ?> – Startseite">
        <?php if ($logoType === 'image' && $logoUrl): ?>
        <img src="<?php echo htmlspecialchars((string) $logoUrl); ?>" alt="<?php echo htmlspecialchars((string) $logoText); ?>" height="<?php echo $logoHeight; ?>" class="site-logo-image" onerror="this.style.display='none';var fallback=this.nextElementSibling;if(fallback){fallback.style.display='inline-flex';}">
        <span class="site-logo-fallback">
          <span class="logo-word"><?php echo htmlspecialchars((string) $logoText); ?></span>
          <span class="logo-dot"></span>
          <?php if ($logoTagline): ?>
          <span class="logo-tagline"><?php echo htmlspecialchars((string) $logoTagline); ?></span>
          <?php endif; ?>
        </span>
        <?php else: ?>
            <span class="logo-word"><?php echo htmlspecialchars((string) $logoText); ?></span>
            <span class="logo-dot"></span>
            <?php if ($logoTagline): ?>
            <span class="logo-tagline"><?php echo htmlspecialchars((string) $logoTagline); ?></span>
            <?php endif; ?>
        <?php endif; ?>
      </a>
      <?php if ($headerTitle): ?>
      <span class="header-site-title"><?php echo htmlspecialchars((string) $headerTitle); ?></span>
      <?php endif; ?>
    </div>

    <!-- Primary Nav -->
    <nav class="primary-nav">
        <?php
      $defaultNavItems = [
            ['label' => 'Startseite', 'href' => SITE_URL . '/'],
            ['label' => 'Blog',        'href' => SITE_URL . '/blog'],
        ];
      $navItems = $defaultNavItems;
        if (class_exists('\CMS\ThemeManager')) {
            $primaryMenu = \CMS\ThemeManager::instance()->getMenu('primary');
            if (!empty($primaryMenu)) {
                $navItems = $primaryMenu;
            }
        }

      $navItems = meridian_filter_navigation_items($navItems);
      if (empty($navItems)) {
        $navItems = meridian_filter_navigation_items($defaultNavItems);
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
          <a href="<?php echo htmlspecialchars($memberAreaUrl); ?>" class="btn-ghost">Mein Bereich</a>
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
      <?php if ($mobileMenuEnabled): ?>
      <button class="nav-toggle" id="navToggle" aria-label="Menü öffnen" aria-expanded="false" aria-controls="mobileNavPanel">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
      </button>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($showSearch): ?>
<div id="headerSearch" class="header-search" aria-hidden="true">
  <form action="<?php echo SITE_URL; ?>/search" method="GET" role="search" class="header-search-form">
    <input type="search" name="q" placeholder="Suchen…" class="form-control header-search-input" aria-label="Suche">
    <button type="submit" class="btn-submit">Suchen</button>
    <button type="button" id="searchClose" class="btn-ghost">Schließen</button>
  </form>
</div>
<?php endif; ?>

<?php if (class_exists('\CMS\Hooks')) {
    \CMS\Hooks::doAction('after_header');
} ?>

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
        $secMenu = meridian_filter_navigation_items($secMenu);
        if (!empty($secMenu)):
            foreach ($secMenu as $item):
                $mUrl   = $item['url'] ?? '#';
                $mLabel = $item['label'] ?? '';
        ?>
            <a href="<?php echo htmlspecialchars($mUrl); ?>"><?php echo htmlspecialchars($mLabel); ?></a>
        <?php endforeach; else: ?>
          <span class="category-bar-empty">Kein Menü für Position „Sekundär“ zugewiesen.</span>
        <?php endif; ?>
    <?php endif; ?>

  </div>
</div>
<?php endif; ?>

<?php if ($mobileMenuEnabled): ?>
<!-- Mobile Nav Overlay -->
<div id="mobileNavOverlay" class="mobile-nav-overlay" aria-hidden="true"></div>

<!-- Mobile Nav Panel (Slide-in von rechts) -->
<nav id="mobileNavPanel" class="mobile-nav-panel" aria-hidden="true" inert aria-label="Mobile Navigation">
  <div class="mobile-nav-header">
    <a href="<?php echo SITE_URL; ?>/" class="site-logo site-logo--mobile" aria-label="<?php echo htmlspecialchars((string) $logoText); ?> – Startseite">
      <?php if ($logoType === 'image' && $logoUrl): ?>
        <img src="<?php echo htmlspecialchars((string) $logoUrl); ?>" alt="<?php echo htmlspecialchars((string) $logoText); ?>" height="32" class="site-logo-image site-logo-image--mobile" onerror="this.style.display='none';var fallback=this.nextElementSibling;if(fallback){fallback.style.display='inline-flex';}">
        <span class="site-logo-fallback site-logo-fallback--mobile">
          <span class="logo-word logo-word--mobile"><?php echo htmlspecialchars((string) $logoText); ?></span>
          <span class="logo-dot"></span>
        </span>
      <?php else: ?>
        <span class="logo-word logo-word--mobile"><?php echo htmlspecialchars((string) $logoText); ?></span>
        <span class="logo-dot"></span>
      <?php endif; ?>
    </a>
    <button id="mobileNavClose" class="btn-icon mobile-nav-close" aria-label="Menü schließen">
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
       class="mobile-nav-link mobile-nav-link--child">
      <?php echo htmlspecialchars($cLabel); ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="mobile-nav-divider"></div>

    <?php if ($isLoggedIn): ?>
      <a href="<?php echo htmlspecialchars($memberAreaUrl); ?>" class="mobile-nav-link">👤 Mein Bereich</a>
      <a href="<?php echo SITE_URL; ?>/logout" class="mobile-nav-link">⬡ Logout</a>
    <?php else: ?>
      <?php if ($showLoginBtn): ?>
      <a href="<?php echo SITE_URL; ?>/login" class="mobile-nav-link">🔑 Anmelden</a>
      <?php endif; ?>
      <?php if ($showRegBtn): ?>
      <a href="<?php echo SITE_URL; ?>/register" class="mobile-nav-link mobile-nav-link--highlight">✨ Registrieren</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($showSearch): ?>
  <div class="mobile-nav-search">
    <form action="<?php echo SITE_URL; ?>/search" method="GET" role="search" class="mobile-nav-search-form">
      <input type="search" name="q" placeholder="Suchen…" class="form-control form-control--sm mobile-nav-search-input" aria-label="Suche">
      <button type="submit" class="btn-submit" aria-label="Suche absenden">→</button>
    </form>
  </div>
  <?php endif; ?>
</nav>
<?php endif; ?>

<!-- Main Content Wrapper startet hier -->
<main class="site-main">