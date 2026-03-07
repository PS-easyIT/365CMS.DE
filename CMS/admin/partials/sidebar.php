<?php
declare(strict_types=1);

/**
 * Admin Partial: Sidebar Navigation (Tabler navbar-vertical)
 *
 * Erwartet vor dem include:
 *   $activePage – string  Aktuelle Seite (z. B. 'dashboard', 'pages', 'media')
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$activePage = $activePage ?? '';
$siteUrl    = defined('SITE_URL') ? SITE_URL : '';
$assetsUrl  = defined('ASSETS_URL') ? ASSETS_URL : $siteUrl . '/assets';

// Marketplace ein-/ausblenden (DB-Setting, Default: aktiviert)
$_marketplaceEnabled = true;
try {
    $_mpRow = \CMS\Database::instance()->get_row(
        "SELECT option_value FROM " . \CMS\Database::instance()->getPrefix() . "settings WHERE option_name = 'marketplace_enabled'"
    );
    if ($_mpRow !== null) {
        $_marketplaceEnabled = $_mpRow->option_value !== '0';
    }
} catch (\Throwable) {}

// Plugin-Menüs laden und für die Sidebar verfügbar machen
$registeredPluginMenus = [];
try {
    \CMS\Hooks::doAction('cms_admin_menu');
    if (function_exists('get_registered_admin_menus')) {
        $registeredPluginMenus = get_registered_admin_menus();
    }
} catch (\Throwable) {
    $registeredPluginMenus = [];
}

$pluginSidebarChildren = [
    ['label' => 'Plugins verwalten', 'slug' => 'plugins', 'url' => $siteUrl . '/admin/plugins'],
];

if ($_marketplaceEnabled) {
    $pluginSidebarChildren[] = ['label' => 'Marketplace', 'slug' => 'plugin-marketplace', 'url' => $siteUrl . '/admin/plugin-marketplace'];
}

$pluginSidebarSlugs = ['plugins'];
if ($_marketplaceEnabled) {
    $pluginSidebarSlugs[] = 'plugin-marketplace';
}

foreach ($registeredPluginMenus as $menu) {
    if (!is_array($menu) || !empty($menu['hidden']) || empty($menu['menu_slug'])) {
        continue;
    }

    $menuSlug  = (string)$menu['menu_slug'];
    $menuTitle = (string)($menu['menu_title'] ?? $menuSlug);
    $pluginSidebarChildren[] = [
        'label' => $menuTitle,
        'slug'  => $menuSlug,
        'url'   => $siteUrl . '/admin/plugins/' . rawurlencode($menuSlug) . '/' . rawurlencode($menuSlug),
    ];
    $pluginSidebarSlugs[] = $menuSlug;

    foreach ((array)($menu['children'] ?? []) as $child) {
        if (!is_array($child) || empty($child['menu_slug'])) {
            continue;
        }

        $childSlug = (string)$child['menu_slug'];
        $pluginSidebarChildren[] = [
            'label' => '— ' . (string)($child['menu_title'] ?? $childSlug),
            'slug'  => $childSlug,
            'url'   => $siteUrl . '/admin/plugins/' . rawurlencode($menuSlug) . '/' . rawurlencode($childSlug),
        ];
        $pluginSidebarSlugs[] = $childSlug;
    }
}

$pluginSidebarChildren = array_values(array_unique($pluginSidebarChildren, SORT_REGULAR));
$pluginSidebarSlugs = array_values(array_unique($pluginSidebarSlugs));


/**
 * Menü-Struktur: Hauptpunkte mit Icons, Unterpunkte ohne Icons
 */
$menuGroups = [
    // ─── Dashboard ─────────────────────
    [
        'type'  => 'item',
        'label' => 'Dashboard',
        'slug'  => 'dashboard',
        'url'   => $siteUrl . '/admin',
        'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/></svg>',
    ],

    // ─── Seiten & Beiträge ─────────────
    [
        'type'     => 'group',
        'label'    => 'Seiten & Beiträge',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M9 9l1 0"/><path d="M9 13l6 0"/><path d="M9 17l6 0"/></svg>',
        'slugs'    => ['pages', 'posts', 'comments', 'table-of-contents', 'site-tables', 'content-settings'],
        'children' => [
            ['label' => 'Seiten',               'slug' => 'pages',              'url' => $siteUrl . '/admin/pages'],
            ['label' => 'Beiträge',             'slug' => 'posts',              'url' => $siteUrl . '/admin/posts'],
            ['label' => 'Kommentare',           'slug' => 'comments',           'url' => $siteUrl . '/admin/comments'],
            ['label' => 'Inhaltsverzeichnis',   'slug' => 'table-of-contents',  'url' => $siteUrl . '/admin/table-of-contents'],
            ['label' => 'Tabellen',             'slug' => 'site-tables',        'url' => $siteUrl . '/admin/site-tables'],
            ['label' => 'Einstellungen',        'slug' => 'content-settings',   'url' => $siteUrl . '/admin/settings?tab=content'],
        ],
    ],

    // ─── Medienverwaltung ──────────────
    [
        'type'     => 'group',
        'label'    => 'Medienverwaltung',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/></svg>',
        'slugs'    => ['media', 'media-categories', 'media-settings'],
        'children' => [
            ['label' => 'Medien',         'slug' => 'media',              'url' => $siteUrl . '/admin/media'],
            ['label' => 'Kategorien',     'slug' => 'media-categories',   'url' => $siteUrl . '/admin/media?tab=categories'],
            ['label' => 'Einstellungen',  'slug' => 'media-settings',     'url' => $siteUrl . '/admin/media?tab=settings'],
        ],
    ],

    // ─── Benutzer & Gruppen ────────────
    [
        'type'     => 'group',
        'label'    => 'Benutzer & Gruppen',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>',
        'slugs'    => ['users', 'groups', 'roles'],
        'children' => [
            ['label' => 'Benutzer',        'slug' => 'users',   'url' => $siteUrl . '/admin/users'],
            ['label' => 'Gruppen',         'slug' => 'groups',  'url' => $siteUrl . '/admin/groups'],
            ['label' => 'Rollen & Rechte', 'slug' => 'roles',   'url' => $siteUrl . '/admin/roles'],
        ],
    ],

    // ─── Themes & Design ──────────────
    [
        'type'     => 'group',
        'label'    => 'Themes & Design',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25"/><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>',
        'slugs'    => ['themes', 'theme-editor', 'theme-explorer', 'menu-editor', 'landing-page', 'font-manager'],
        'children' => [
            ['label' => 'Theme - Verwaltung', 'slug' => 'themes',         'url' => $siteUrl . '/admin/themes'],
            ['label' => 'Theme - Editor',     'slug' => 'theme-editor',   'url' => $siteUrl . '/admin/theme-editor'],
            ['label' => 'Theme - Explorer',   'slug' => 'theme-explorer', 'url' => $siteUrl . '/admin/theme-explorer'],
            ['label' => 'Theme - Menü',       'slug' => 'menu-editor',    'url' => $siteUrl . '/admin/menu-editor'],
            ['label' => 'Landing Page',       'slug' => 'landing-page',   'url' => $siteUrl . '/admin/landing-page'],
            ['label' => 'Font Manager',       'slug' => 'font-manager',   'url' => $siteUrl . '/admin/font-manager'],
        ],
    ],

    // ─── Member Dashboard ─────────────
    [
        'type'     => 'group',
        'label'    => 'Member Dashboard',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14l-4 -2l-4 2l-4 -2l-4 2z"/><path d="M8 7h8"/><path d="M8 11h8"/></svg>',
        'slugs'    => ['member-dashboard'],
        'children' => [
            ['label' => 'Einstellungen',       'slug' => 'member-dashboard',              'url' => $siteUrl . '/admin/member-dashboard'],
            ['label' => 'Dashboard',           'slug' => 'member-dashboard-link',         'url' => $siteUrl . '/member'],
            ['label' => 'Meine Files',         'slug' => 'member-dashboard-media-link',   'url' => $siteUrl . '/member/media'],
            ['label' => 'Mein Profil',         'slug' => 'member-dashboard-profile-link', 'url' => $siteUrl . '/member/profile'],
            ['label' => 'Benachrichtigungen',  'slug' => 'member-dashboard-notify-link',  'url' => $siteUrl . '/member/notifications'],
            ['label' => 'Datenschutz',         'slug' => 'member-dashboard-privacy-link', 'url' => $siteUrl . '/member/privacy'],
            ['label' => 'Sicherheit',          'slug' => 'member-dashboard-security-link','url' => $siteUrl . '/member/security'],
            ['label' => 'Mein Abo',            'slug' => 'member-dashboard-sub-link',     'url' => $siteUrl . '/member/subscription'],
        ],
    ],

    // ─── Aboverwaltung ────────────────
    [
        'type'     => 'group',
        'label'    => 'Aboverwaltung',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l2 0"/></svg>',
        'slugs'    => ['packages', 'subscription-settings', 'orders'],
        'children' => [
            ['label' => 'Pakete & Abo-Einstellungen', 'slug' => 'packages',              'url' => $siteUrl . '/admin/packages'],
            ['label' => 'Bestellungen & Zuweisung',   'slug' => 'orders',                'url' => $siteUrl . '/admin/orders'],
            ['label' => 'Einstellungen',              'slug' => 'subscription-settings', 'url' => $siteUrl . '/admin/subscription-settings'],
        ],
    ],

    // ─── SEO & Performance ────────────
    [
        'type'     => 'group',
        'label'    => 'SEO & Performance',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M3.6 9h16.8"/><path d="M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18"/><path d="M12.5 3a17 17 0 0 1 0 18"/></svg>',
        'slugs'    => ['seo-dashboard', 'analytics', 'performance', 'redirect-manager'],
        'children' => [
            ['label' => 'SEO Dashboard',       'slug' => 'seo-dashboard', 'url' => $siteUrl . '/admin/seo-dashboard'],
            ['label' => 'Analytics',            'slug' => 'analytics',     'url' => $siteUrl . '/admin/analytics'],
            ['label' => 'Performance',          'slug' => 'performance',   'url' => $siteUrl . '/admin/performance'],
            ['label' => '404-Errors & Weiterleitung', 'slug' => 'redirect-manager', 'url' => $siteUrl . '/admin/redirect-manager'],
        ],
    ],

    // ─── Recht ───────────────────────
    [
        'type'     => 'group',
        'label'    => 'Recht',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 21h8"/><path d="M12 3l7 4v5c0 5 -3.5 7.5 -7 9c-3.5 -1.5 -7 -4 -7 -9v-5l7 -4"/></svg>',
        'slugs'    => ['legal-sites', 'cookie-manager', 'data-requests', 'privacy-requests', 'deletion-requests'],
        'children' => [
            ['label' => 'Legal Sites',          'slug' => 'legal-sites',    'url' => $siteUrl . '/admin/legal-sites'],
            ['label' => 'Cookie-Manager',       'slug' => 'cookie-manager', 'url' => $siteUrl . '/admin/cookie-manager'],
            ['label' => 'Auskunft & Löschen',   'slug' => 'data-requests',  'url' => $siteUrl . '/admin/data-requests'],
        ],
    ],

    // ─── Sicherheit ──────────────────
    [
        'type'     => 'group',
        'label'    => 'Sicherheit',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/><path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 12l0 2.5"/></svg>',
        'slugs'    => ['antispam', 'firewall', 'security-audit'],
        'children' => [
            ['label' => 'AntiSpam',             'slug' => 'antispam',          'url' => $siteUrl . '/admin/antispam'],
            ['label' => 'Firewall',             'slug' => 'firewall',          'url' => $siteUrl . '/admin/firewall'],
            ['label' => 'Audit',                'slug' => 'security-audit',    'url' => $siteUrl . '/admin/security-audit'],
        ],
    ],

    // ─── Plugins ──────────────────────
    [
        'type'     => 'group',
        'label'    => 'Plugins',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l0 10"/><path d="M20 7l0 10"/><path d="M12 3l0 18"/><path d="M3 17l4 -4l-4 -4"/><path d="M21 17l-4 -4l4 -4"/><path d="M11 7l2 -4l2 4"/></svg>',
        'slugs'    => $pluginSidebarSlugs,
        'children' => $pluginSidebarChildren,
    ],

    // ─── System & Einstellungen ───────
    [
        'type'     => 'group',
        'label'    => 'System',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.066 2.573c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.573 1.066c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.066 -2.573c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/></svg>',
        'slugs'    => ['settings', 'theme-settings', 'backups', 'updates'],
        'children' => [
            ['label' => 'Einstellungen',      'slug' => 'settings', 'url' => $siteUrl . '/admin/settings'],
            ['label' => 'Backup & Restore',   'slug' => 'backups',  'url' => $siteUrl . '/admin/backups'],
            ['label' => 'Updates',            'slug' => 'updates',  'url' => $siteUrl . '/admin/updates'],
        ],
    ],

    // ─── Info & Diagnose ─────────────
    [
        'type'     => 'group',
        'label'    => 'Info & Diagnose',
        'icon'     => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 9h.01"/><path d="M11 12h1v4h1"/></svg>',
        'slugs'    => ['info', 'diagnose', 'documentation', 'system-info'],
        'children' => [
            ['label' => 'Info',              'slug' => 'info',       'url' => $siteUrl . '/admin/info'],
            ['label' => 'Diagnose',          'slug' => 'diagnose',   'url' => $siteUrl . '/admin/diagnose'],
            ['label' => 'Dokumentation',     'slug' => 'documentation', 'url' => $siteUrl . '/admin/documentation'],
        ],
    ],
];

/**
 * Prüft ob ein Slug aktiv ist
 */
function isSlugActive(string $slug, string $activePage): bool {
    return $slug === $activePage;
}

/**
 * Prüft ob eine Gruppe aktiv ist (irgendeins der Children)
 */
function isGroupActive(array $slugs, string $activePage): bool {
    return in_array($activePage, $slugs, true);
}

?>
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">

        <!-- Brand / Logo -->
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="<?= htmlspecialchars($siteUrl) ?>/admin">
                <img src="<?= $assetsUrl ?>/images/logo-white.svg"
                     alt="<?= htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : '365CMS') ?>"
                     height="32"
                     onerror="this.onerror=null;this.src='<?= $assetsUrl ?>/images/logo.svg';">
            </a>
        </h1>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="Navigation umschalten">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation -->
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">

                <?php foreach ($menuGroups as $item): ?>

                    <?php if ($item['type'] === 'item'): ?>
                        <!-- Einzelner Menüpunkt -->
                        <li class="nav-item<?= isSlugActive($item['slug'], $activePage) ? ' active' : '' ?>">
                            <a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>">
                                <span class="nav-link-icon"><?= $item['icon'] ?></span>
                                <span class="nav-link-title"><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>

                    <?php elseif ($item['type'] === 'group'): ?>
                        <?php $groupActive = isGroupActive($item['slugs'], $activePage); ?>
                        <!-- Gruppe mit Untermenü -->
                        <li class="nav-item dropdown<?= $groupActive ? ' active' : '' ?>">
                            <a class="nav-link dropdown-toggle<?= $groupActive ? ' show' : '' ?>"
                               href="#sidebar-<?= htmlspecialchars($item['slugs'][0] ?? 'group') ?>"
                               data-bs-toggle="dropdown" data-bs-auto-close="false"
                               role="button" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>">
                                <span class="nav-link-icon"><?= $item['icon'] ?></span>
                                <span class="nav-link-title"><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                            <div class="dropdown-menu<?= $groupActive ? ' show' : '' ?>">
                                <?php foreach ($item['children'] as $child): ?>
                                    <a class="dropdown-item<?= isSlugActive($child['slug'], $activePage) ? ' active' : '' ?>"
                                       href="<?= htmlspecialchars($child['url']) ?>">
                                        <?= htmlspecialchars($child['label']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </li>

                    <?php endif; ?>

                <?php endforeach; ?>

            </ul>
        </div>

        <!-- Sidebar Footer -->
        <div class="navbar-nav flex-row d-none d-lg-flex mt-auto pb-3 px-2">
            <div class="d-flex flex-column w-100 gap-1">
                <a class="nav-link text-reset px-2 py-1" href="<?= $siteUrl ?>/" target="_blank" rel="noopener">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6"/><path d="M11 13l9 -9"/><path d="M15 4h5v5"/></svg>
                    <span class="nav-link-title">Website ansehen</span>
                </a>
                <a class="nav-link text-reset px-2 py-1" href="<?= $siteUrl ?>/logout">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/></svg>
                    <span class="nav-link-title text-danger">Abmelden</span>
                </a>
            </div>
        </div>

    </div>
</aside>

<div class="page-wrapper">
