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

if (!function_exists('cmsNormalizeSidebarActivePage')) {
    function cmsNormalizeSidebarActivePage(string $activePage): string {
        return match (trim($activePage)) {
            'theme-settings' => 'settings',
            'system-info' => 'info',
            'support' => 'documentation',
            'privacy-requests', 'deletion-requests' => 'data-requests',
            default => trim($activePage),
        };
    }
}

if (!function_exists('buildSidebarPluginIcon')) {
    function buildSidebarPluginIcon(string $icon, string $fallback): string {
        $icon = trim($icon);

        if ($icon === '') {
            return $fallback;
        }

        if (str_starts_with($icon, '<')) {
            return $icon;
        }

        if (preg_match('~^(https?://|/)~i', $icon) === 1) {
            return '<img src="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" alt="" class="icon" style="width:24px;height:24px;object-fit:contain;">';
        }

        return '<span class="icon d-inline-flex align-items-center justify-content-center" aria-hidden="true">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('sidebarTopLevelIcon')) {
    function sidebarTopLevelIcon(string $key): string {
        $iconMap = [
            'dashboard' => 'layout-dashboard',
            'ai-services' => 'sparkles',
            'pages-posts' => 'file-text',
            'media' => 'photo',
            'users' => 'users',
            'member-dashboard' => 'id-badge-2',
            'subscriptions' => 'credit-card',
            'themes' => 'palette',
            'seo' => 'chart-line',
            'performance' => 'gauge',
            'legal' => 'scale',
            'security' => 'shield',
            'plugins' => 'plug',
            'system' => 'settings',
            'diagnose' => 'stethoscope',
            'plugin-item' => 'plug',
        ];

        $icon = $iconMap[$key] ?? 'point';
        return '<i class="ti ti-' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
    }
}

$activePage = cmsNormalizeSidebarActivePage((string) ($activePage ?? ''));
$siteUrl    = defined('SITE_URL') ? SITE_URL : '';
$sidebarLogoUrl = cms_asset_url('images/LOGO_365CMS-75px.png', false);
$sidebarLogoFallbackUrl = $sidebarLogoUrl;
$defaultPluginIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h10v10h-10z"/><path d="M14 7v-3a1 1 0 0 0 -1 -1h-2a1 1 0 0 0 -1 1v3"/><path d="M7 14h-3a1 1 0 0 1 -1 -1v-2a1 1 0 0 1 1 -1h3"/><path d="M17 14h3a1 1 0 0 0 1 -1v-2a1 1 0 0 0 -1 -1h-3"/><path d="M14 17v3a1 1 0 0 1 -1 1h-2a1 1 0 0 1 -1 -1v-3"/></svg>';

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
$pluginMenuGroups = [];
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

$coreModuleService = null;
try {
    if (class_exists('\CMS\Services\CoreModuleService')) {
        $coreModuleService = \CMS\Services\CoreModuleService::getInstance();
    }
} catch (\Throwable) {
    $coreModuleService = null;
}

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
    $menuUrl = $siteUrl . '/admin/plugins/' . rawurlencode($menuSlug) . '/' . rawurlencode($menuSlug);
    $menuIcon = sidebarTopLevelIcon('plugin-item');
    $children = [];
    $groupSlugs = [$menuSlug];

    foreach ((array)($menu['children'] ?? []) as $child) {
        if (!is_array($child) || empty($child['menu_slug'])) {
            continue;
        }

        $childSlug = (string)$child['menu_slug'];
        $children[] = [
            'label' => (string)($child['menu_title'] ?? $childSlug),
            'slug'  => $childSlug,
            'url'   => $siteUrl . '/admin/plugins/' . rawurlencode($menuSlug) . '/' . rawurlencode($childSlug),
        ];
        $groupSlugs[] = $childSlug;
    }

    if ($children !== []) {
        $hasOverviewChild = false;
        foreach ($children as $child) {
            if (($child['slug'] ?? '') === $menuSlug) {
                $hasOverviewChild = true;
                break;
            }
        }

        if (!$hasOverviewChild) {
            array_unshift($children, [
                'label' => 'Übersicht',
                'slug'  => $menuSlug,
                'url'   => $menuUrl,
            ]);
        }

        $pluginMenuGroups[] = [
            'type'     => 'group',
            'label'    => $menuTitle,
            'icon'     => sidebarTopLevelIcon('plugin-item'),
            'slugs'    => array_values(array_unique($groupSlugs)),
            'children' => $children,
        ];
        continue;
    }

    $pluginMenuGroups[] = [
        'type'  => 'item',
        'label' => $menuTitle,
        'slug'  => $menuSlug,
        'url'   => $menuUrl,
        'icon'  => sidebarTopLevelIcon('plugin-item'),
    ];
}

$pluginSidebarChildren = array_values(array_unique($pluginSidebarChildren, SORT_REGULAR));
$pluginSidebarSlugs = array_values(array_unique($pluginSidebarSlugs));

$subscriptionSidebarChildren = [
    ['label' => 'Pakete & Abo-Einstellungen', 'slug' => 'packages', 'url' => $siteUrl . '/admin/packages'],
    ['label' => 'Bestellungen & Zuweisung', 'slug' => 'orders', 'url' => $siteUrl . '/admin/orders'],
    ['label' => 'Einstellungen', 'slug' => 'subscription-settings', 'url' => $siteUrl . '/admin/subscription-settings'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $subscriptionSidebarChildren = $coreModuleService->filterSidebarChildren('subscriptions', $subscriptionSidebarChildren);
}

$subscriptionSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $subscriptionSidebarChildren
));

$themeSidebarChildren = [
    ['label' => 'Theme - Verwaltung', 'slug' => 'themes', 'url' => $siteUrl . '/admin/themes'],
    ['label' => 'Theme - Editor', 'slug' => 'theme-editor', 'url' => $siteUrl . '/admin/theme-editor'],
    ['label' => 'Theme - Explorer', 'slug' => 'theme-explorer', 'url' => $siteUrl . '/admin/theme-explorer'],
    ['label' => 'Theme - Menü', 'slug' => 'menu-editor', 'url' => $siteUrl . '/admin/menu-editor'],
    ['label' => 'Landing Page', 'slug' => 'landing-page', 'url' => $siteUrl . '/admin/landing-page'],
    ['label' => 'Font Manager', 'slug' => 'font-manager', 'url' => $siteUrl . '/admin/font-manager'],
    ['label' => 'CMS Loginpage', 'slug' => 'cms-loginpage', 'url' => $siteUrl . '/admin/cms-loginpage'],
];

if ($_marketplaceEnabled) {
    $themeSidebarChildren[] = ['label' => 'Theme - Marketplace', 'slug' => 'theme-marketplace', 'url' => $siteUrl . '/admin/theme-marketplace'];
}

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $themeSidebarChildren = $coreModuleService->filterSidebarChildren('themes', $themeSidebarChildren);
}

$themeSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $themeSidebarChildren
));

$memberDashboardSidebarChildren = [
    ['label' => 'Übersicht', 'slug' => 'member-dashboard', 'url' => $siteUrl . '/admin/member-dashboard'],
    ['label' => 'Allgemein', 'slug' => 'member-dashboard-general', 'url' => $siteUrl . '/admin/member-dashboard-general'],
    ['label' => 'Design & Farben', 'slug' => 'member-dashboard-design', 'url' => $siteUrl . '/admin/member-dashboard-design'],
    ['label' => 'Frontend-Module', 'slug' => 'member-dashboard-frontend-modules', 'url' => $siteUrl . '/admin/member-dashboard-frontend-modules'],
    ['label' => 'Dashboard Widgets', 'slug' => 'member-dashboard-widgets', 'url' => $siteUrl . '/admin/member-dashboard-widgets'],
    ['label' => 'Plugin-Widgets', 'slug' => 'member-dashboard-plugin-widgets', 'url' => $siteUrl . '/admin/member-dashboard-plugin-widgets'],
    ['label' => 'Profil-Felder', 'slug' => 'member-dashboard-profile-fields', 'url' => $siteUrl . '/admin/member-dashboard-profile-fields'],
    ['label' => 'Benachrichtigungen', 'slug' => 'member-dashboard-notifications', 'url' => $siteUrl . '/admin/member-dashboard-notifications'],
    ['label' => 'Mitglieder-Onboarding', 'slug' => 'member-dashboard-onboarding', 'url' => $siteUrl . '/admin/member-dashboard-onboarding'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $memberDashboardSidebarChildren = $coreModuleService->filterSidebarChildren('member_dashboard', $memberDashboardSidebarChildren);
}

$memberDashboardSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $memberDashboardSidebarChildren
));

$aiSidebarChildren = [
    ['label' => 'AI Dashboard', 'slug' => 'ai-services', 'url' => $siteUrl . '/admin/ai-services'],
    ['label' => 'Übersetzung', 'slug' => 'ai-translation', 'url' => $siteUrl . '/admin/ai-translation'],
    ['label' => 'Content Creator', 'slug' => 'ai-content-creator', 'url' => $siteUrl . '/admin/ai-content-creator'],
    ['label' => 'SEO Creator', 'slug' => 'ai-seo-creator', 'url' => $siteUrl . '/admin/ai-seo-creator'],
    ['label' => 'Einstellungen', 'slug' => 'ai-settings', 'url' => $siteUrl . '/admin/ai-settings'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $aiSidebarChildren = $coreModuleService->filterSidebarChildren('ai_services', $aiSidebarChildren);
}

$aiSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $aiSidebarChildren
));

$seoSidebarChildren = [
    ['label' => 'SEO Dashboard', 'slug' => 'seo-dashboard', 'url' => $siteUrl . '/admin/seo-dashboard'],
    ['label' => 'Analytics', 'slug' => 'analytics', 'url' => $siteUrl . '/admin/analytics'],
    ['label' => 'SEO Audit', 'slug' => 'seo-audit', 'url' => $siteUrl . '/admin/seo-audit'],
    ['label' => 'Meta-Daten', 'slug' => 'seo-meta', 'url' => $siteUrl . '/admin/seo-meta'],
    ['label' => 'Social Media', 'slug' => 'seo-social', 'url' => $siteUrl . '/admin/seo-social'],
    ['label' => 'Strukturierte Daten', 'slug' => 'seo-schema', 'url' => $siteUrl . '/admin/seo-schema'],
    ['label' => 'Sitemap & robots.txt', 'slug' => 'seo-sitemap', 'url' => $siteUrl . '/admin/seo-sitemap'],
    ['label' => 'Technisches SEO', 'slug' => 'seo-technical', 'url' => $siteUrl . '/admin/seo-technical'],
    ['label' => 'Weiterleitungen', 'slug' => 'redirect-manager', 'url' => $siteUrl . '/admin/redirect-manager'],
    ['label' => '404-Monitor', 'slug' => 'not-found-monitor', 'url' => $siteUrl . '/admin/not-found-monitor'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $seoSidebarChildren = $coreModuleService->filterSidebarChildren('seo', $seoSidebarChildren);
}

$seoSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $seoSidebarChildren
));

$performanceSidebarChildren = [
    ['label' => 'Übersicht', 'slug' => 'performance', 'url' => $siteUrl . '/admin/performance'],
    ['label' => 'Cache-Verwaltung', 'slug' => 'performance-cache', 'url' => $siteUrl . '/admin/performance-cache'],
    ['label' => 'Medien-Optimierung', 'slug' => 'performance-media', 'url' => $siteUrl . '/admin/performance-media'],
    ['label' => 'Datenbank-Wartung', 'slug' => 'performance-database', 'url' => $siteUrl . '/admin/performance-database'],
    ['label' => 'Performance-Einstellungen', 'slug' => 'performance-settings', 'url' => $siteUrl . '/admin/performance-settings'],
    ['label' => 'Session-Verwaltung', 'slug' => 'performance-sessions', 'url' => $siteUrl . '/admin/performance-sessions'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $performanceSidebarChildren = $coreModuleService->filterSidebarChildren('performance', $performanceSidebarChildren);
}

$performanceSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $performanceSidebarChildren
));

$legalSidebarChildren = [
    ['label' => 'Legal Sites', 'slug' => 'legal-sites', 'url' => $siteUrl . '/admin/legal-sites'],
    ['label' => 'Cookie-Manager', 'slug' => 'cookie-manager', 'url' => $siteUrl . '/admin/cookie-manager'],
    ['label' => 'Auskunft & Löschen', 'slug' => 'data-requests', 'url' => $siteUrl . '/admin/data-requests'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $legalSidebarChildren = $coreModuleService->filterSidebarChildren('legal', $legalSidebarChildren);
}

$legalSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $legalSidebarChildren
));

$securitySidebarChildren = [
    ['label' => 'AntiSpam', 'slug' => 'antispam', 'url' => $siteUrl . '/admin/antispam'],
    ['label' => 'Firewall', 'slug' => 'firewall', 'url' => $siteUrl . '/admin/firewall'],
    ['label' => 'Audit', 'slug' => 'security-audit', 'url' => $siteUrl . '/admin/security-audit'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $securitySidebarChildren = $coreModuleService->filterSidebarChildren('security', $securitySidebarChildren);
}

$securitySidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $securitySidebarChildren
));

$systemSidebarChildren = [
    ['label' => 'Einstellungen', 'slug' => 'settings', 'url' => $siteUrl . '/admin/settings'],
    ['label' => 'Mail & Azure OAuth2', 'slug' => 'mail-settings', 'url' => $siteUrl . '/admin/mail-settings'],
    ['label' => 'Module', 'slug' => 'modules', 'url' => $siteUrl . '/admin/modules'],
    ['label' => 'Backup & Restore', 'slug' => 'backups', 'url' => $siteUrl . '/admin/backups'],
    ['label' => 'Updates', 'slug' => 'updates', 'url' => $siteUrl . '/admin/updates'],
    ['label' => 'Dokumentation', 'slug' => 'documentation', 'url' => $siteUrl . '/admin/documentation'],
];

if ($coreModuleService instanceof \CMS\Services\CoreModuleService) {
    $systemSidebarChildren = $coreModuleService->filterSidebarChildren('system', $systemSidebarChildren);
}

$systemSidebarSlugs = array_values(array_map(
    static fn (array $item): string => (string) ($item['slug'] ?? ''),
    $systemSidebarChildren
));


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
        'icon'  => sidebarTopLevelIcon('dashboard'),
    ],

    // ─── AI Services ────────────────
    [
        'type'     => 'group',
        'label'    => 'AI Services',
        'icon'     => sidebarTopLevelIcon('ai-services'),
        'slugs'    => $aiSidebarSlugs,
        'children' => $aiSidebarChildren,
    ],

    // ─── Seiten & Beiträge ─────────────
    [
        'type'     => 'group',
        'label'    => 'Seiten & Beiträge',
        'icon'     => sidebarTopLevelIcon('pages-posts'),
        'slugs'    => ['pages', 'posts', 'post-categories', 'post-tags', 'comments', 'table-of-contents', 'hub-sites', 'site-tables', 'content-settings'],
        'children' => [
            ['label' => 'Seiten',               'slug' => 'pages',              'url' => $siteUrl . '/admin/pages'],
            ['label' => 'Beiträge',             'slug' => 'posts',              'url' => $siteUrl . '/admin/posts'],
            ['label' => 'Kategorien',           'slug' => 'post-categories',    'url' => $siteUrl . '/admin/post-categories'],
            ['label' => 'Tags',                 'slug' => 'post-tags',          'url' => $siteUrl . '/admin/post-tags'],
            ['label' => 'Kommentare',           'slug' => 'comments',           'url' => $siteUrl . '/admin/comments'],
            ['label' => 'Inhaltsverzeichnis',   'slug' => 'table-of-contents',  'url' => $siteUrl . '/admin/table-of-contents'],
            ['label' => 'Hub-Sites',            'slug' => 'hub-sites',          'url' => $siteUrl . '/admin/hub-sites'],
            ['label' => 'Tabellen',             'slug' => 'site-tables',        'url' => $siteUrl . '/admin/site-tables'],
            ['label' => 'Einstellungen',        'slug' => 'content-settings',   'url' => $siteUrl . '/admin/settings?tab=content'],
        ],
    ],

    // ─── Medienverwaltung ──────────────
    [
        'type'     => 'group',
        'label'    => 'Medienverwaltung',
        'icon'     => sidebarTopLevelIcon('media'),
        'slugs'    => ['media', 'media-featured', 'media-check', 'media-categories', 'media-settings'],
        'children' => [
            ['label' => 'Medien',         'slug' => 'media',              'url' => $siteUrl . '/admin/media'],
            ['label' => 'Beitrags & Site Medien', 'slug' => 'media-featured', 'url' => $siteUrl . '/admin/media?tab=featured'],
            ['label' => 'Medien Check',   'slug' => 'media-check',        'url' => $siteUrl . '/admin/media?tab=check'],
            ['label' => 'Kategorien',     'slug' => 'media-categories',   'url' => $siteUrl . '/admin/media?tab=categories'],
            ['label' => 'Einstellungen',  'slug' => 'media-settings',     'url' => $siteUrl . '/admin/media?tab=settings'],
        ],
    ],

    // ─── Benutzer & Gruppen ────────────
    [
        'type'     => 'group',
        'label'    => 'Benutzer & Gruppen',
        'icon'     => sidebarTopLevelIcon('users'),
        'slugs'    => ['users', 'groups', 'roles', 'user-settings'],
        'children' => [
            ['label' => 'Benutzer',        'slug' => 'users',   'url' => $siteUrl . '/admin/users'],
            ['label' => 'Gruppen',         'slug' => 'groups',  'url' => $siteUrl . '/admin/groups'],
            ['label' => 'Rollen & Rechte', 'slug' => 'roles',   'url' => $siteUrl . '/admin/roles'],
            ['label' => 'Einstellungen',   'slug' => 'user-settings', 'url' => $siteUrl . '/admin/user-settings'],
        ],
    ],

    // ─── Member Dashboard ─────────────
    [
        'type'     => 'group',
        'label'    => 'Member Dashboard',
        'icon'     => sidebarTopLevelIcon('member-dashboard'),
        'slugs'    => $memberDashboardSidebarSlugs,
        'children' => $memberDashboardSidebarChildren,
    ],

    // ─── Aboverwaltung ────────────────
    [
        'type'     => 'group',
        'label'    => 'Aboverwaltung',
        'icon'     => sidebarTopLevelIcon('subscriptions'),
        'slugs'    => $subscriptionSidebarSlugs,
        'children' => $subscriptionSidebarChildren,
    ],

    // ─── Themes & Design ──────────────
    [
        'type'     => 'group',
        'label'    => 'Themes & Design',
        'icon'     => sidebarTopLevelIcon('themes'),
        'slugs'    => $themeSidebarSlugs,
        'children' => $themeSidebarChildren,
    ],

    // ─── SEO ─────────────────────────
    [
        'type'     => 'group',
        'label'    => 'SEO',
        'icon'     => sidebarTopLevelIcon('seo'),
        'slugs'    => $seoSidebarSlugs,
        'children' => $seoSidebarChildren,
    ],

    // ─── Performance ─────────────────
    [
        'type'     => 'group',
        'label'    => 'Performance',
        'icon'     => sidebarTopLevelIcon('performance'),
        'slugs'    => $performanceSidebarSlugs,
        'children' => $performanceSidebarChildren,
    ],

    // ─── Recht ───────────────────────
    [
        'type'     => 'group',
        'label'    => 'Recht',
        'icon'     => sidebarTopLevelIcon('legal'),
        'slugs'    => $legalSidebarSlugs,
        'children' => $legalSidebarChildren,
    ],

    // ─── Sicherheit ──────────────────
    [
        'type'     => 'group',
        'label'    => 'Sicherheit',
        'icon'     => sidebarTopLevelIcon('security'),
        'slugs'    => $securitySidebarSlugs,
        'children' => $securitySidebarChildren,
    ],

    // ─── Plugins ──────────────────────
    [
        'type'     => 'group',
        'label'    => 'Plugins',
        'icon'     => sidebarTopLevelIcon('plugins'),
        'slugs'    => $pluginSidebarSlugs,
        'children' => $pluginSidebarChildren,
    ],

    // ─── System & Einstellungen ───────
    [
        'type'     => 'group',
        'label'    => 'System & Doku',
        'icon'     => sidebarTopLevelIcon('system'),
        'slugs'    => $systemSidebarSlugs,
        'children' => $systemSidebarChildren,
    ],

    // ─── Diagnose ────────────────────
    [
        'type'     => 'group',
        'label'    => 'Diagnose',
        'icon'     => sidebarTopLevelIcon('diagnose'),
        'slugs'    => ['info', 'diagnose', 'monitor-assets', 'monitor-response-time', 'monitor-cron-status', 'monitor-disk-usage', 'monitor-scheduled-tasks', 'monitor-health-check', 'monitor-email-alerts', 'cms-logs'],
        'children' => [
            ['label' => 'Übersicht', 'slug' => 'info', 'url' => $siteUrl . '/admin/info'],
            ['label' => 'Datenbank', 'slug' => 'diagnose', 'url' => $siteUrl . '/admin/diagnose'],
            ['label' => 'Assets', 'slug' => 'monitor-assets', 'url' => $siteUrl . '/admin/monitor-assets'],
            ['label' => 'Response-Time Monitoring', 'slug' => 'monitor-response-time', 'url' => $siteUrl . '/admin/monitor-response-time'],
            ['label' => 'Cron-Job Status', 'slug' => 'monitor-cron-status', 'url' => $siteUrl . '/admin/monitor-cron-status'],
            ['label' => 'Disk-Usage', 'slug' => 'monitor-disk-usage', 'url' => $siteUrl . '/admin/monitor-disk-usage'],
            ['label' => 'Scheduled Tasks', 'slug' => 'monitor-scheduled-tasks', 'url' => $siteUrl . '/admin/monitor-scheduled-tasks'],
            ['label' => 'Health-Check', 'slug' => 'monitor-health-check', 'url' => $siteUrl . '/admin/monitor-health-check'],
            ['label' => 'E-Mail-Benachrichtigungen', 'slug' => 'monitor-email-alerts', 'url' => $siteUrl . '/admin/monitor-email-alerts'],
            ['label' => 'Logs & Protokolle', 'slug' => 'cms-logs', 'url' => $siteUrl . '/admin/cms-logs'],
        ],
    ],
];

if ($pluginMenuGroups !== []) {
    foreach ($menuGroups as $index => $item) {
        if (($item['type'] ?? '') === 'group' && in_array('plugins', (array)($item['slugs'] ?? []), true)) {
            $pluginCount = count($pluginMenuGroups);
            array_splice($menuGroups, $index + 1, 0, array_merge([
                [
                    'type'  => 'spacer',
                    'class' => 'nav-spacer--plugins-start',
                ],
            ], array_map(static function (array $pluginGroup, int $pluginIndex) use ($pluginCount): array {
                $classes = ['nav-item--plugin'];

                if ($pluginIndex === 0) {
                    $classes[] = 'nav-item--plugin-first';
                }

                if ($pluginIndex === $pluginCount - 1) {
                    $classes[] = 'nav-item--plugin-last';
                }

                $pluginGroup['class'] = trim((string)($pluginGroup['class'] ?? '') . ' ' . implode(' ', $classes));
                return $pluginGroup;
            }, $pluginMenuGroups, array_keys($pluginMenuGroups)), [
                [
                    'type'  => 'spacer',
                    'class' => 'nav-spacer--plugins-end',
                ],
            ]));
            break;
        }
    }
}

$menuGroups = array_values(array_filter($menuGroups, static function (array $item): bool {
    if (($item['type'] ?? '') !== 'group') {
        return true;
    }

    return !empty($item['children']);
}));

/**
 * Prüft ob ein Slug aktiv ist
 */
if (!function_exists('isSlugActive')) {
    function isSlugActive(string $slug, string $activePage): bool {
        return $slug === $activePage;
    }
}

/**
 * Prüft ob eine Gruppe aktiv ist (irgendeins der Children)
 */
if (!function_exists('isGroupActive')) {
    function isGroupActive(array $slugs, string $activePage): bool {
        return in_array($activePage, $slugs, true);
    }
}

if (!function_exists('sidebarChildIcon')) {
    function sidebarChildIcon(string $slug): string {
        $iconMap = [
            'pages' => 'file-text',
            'posts' => 'article',
            'comments' => 'message-circle',
            'media' => 'photo',
            'users' => 'users',
            'groups' => 'users',
            'roles' => 'shield-check',
            'orders' => 'shopping-cart',
            'packages' => 'package',
            'analytics' => 'chart-bar',
            'seo-dashboard' => 'chart-pie',
            'documentation' => 'book',
            'settings' => 'settings',
            'mail-settings' => 'mail',
            'updates' => 'refresh',
            'backups' => 'database',
            'cms-logs' => 'file-report',
            'modules' => 'apps',
            'themes' => 'palette',
            'theme-editor' => 'code',
            'theme-explorer' => 'folder',
            'menu-editor' => 'list',
            'font-manager' => 'typography',
        ];

        $icon = $iconMap[$slug] ?? 'chevron-right';
        return '<i class="ti ti-' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>';
    }
}

?>
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">

        <!-- Brand / Logo -->
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="<?= htmlspecialchars((string) $siteUrl) ?>/admin">
                <img src="<?= htmlspecialchars((string) $sidebarLogoUrl) ?>"
                     alt="<?= htmlspecialchars((string) (defined('SITE_NAME') ? SITE_NAME : '365CMS')) ?>"
                     height="75"
                     style="height:75px;width:auto;max-width:100%;"
                     onerror="this.onerror=null;this.src='<?= htmlspecialchars((string) $sidebarLogoFallbackUrl) ?>';this.style.height='75px';this.style.width='auto';">
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

                    <?php if ($item['type'] === 'divider'): ?>
                        <li class="nav-divider <?php echo htmlspecialchars((string)($item['class'] ?? '')); ?>" aria-hidden="true"></li>

                    <?php elseif ($item['type'] === 'spacer'): ?>
                        <li class="<?php echo htmlspecialchars((string)($item['class'] ?? 'nav-spacer')); ?>" aria-hidden="true"></li>

                    <?php elseif ($item['type'] === 'section-label'): ?>
                        <li class="nav-section-label <?php echo htmlspecialchars((string)($item['class'] ?? '')); ?>">
                            <?= htmlspecialchars((string)($item['label'] ?? '')) ?>
                        </li>

                    <?php elseif ($item['type'] === 'item'): ?>
                        <!-- Einzelner Menüpunkt -->
                        <li class="nav-item<?= isSlugActive((string) ($item['slug'] ?? ''), $activePage) ? ' active' : '' ?><?= !empty($item['class']) ? ' ' . htmlspecialchars((string)$item['class']) : '' ?>">
                            <a class="nav-link" href="<?= htmlspecialchars((string) ($item['url'] ?? '#')) ?>">
                                <span class="nav-link-icon"><?= $item['icon'] ?></span>
                                <span class="nav-link-title"><?= htmlspecialchars((string) ($item['label'] ?? '')) ?></span>
                            </a>
                        </li>

                    <?php elseif ($item['type'] === 'group'): ?>
                        <?php $groupActive = isGroupActive($item['slugs'], $activePage); ?>
                        <!-- Gruppe mit Untermenü -->
                        <li class="nav-item dropdown<?= $groupActive ? ' active' : '' ?><?= !empty($item['class']) ? ' ' . htmlspecialchars((string)$item['class']) : '' ?>">
                            <a class="nav-link dropdown-toggle<?= $groupActive ? ' show' : '' ?>"
                               href="#sidebar-<?= htmlspecialchars((string) ($item['slugs'][0] ?? 'group')) ?>"
                               data-bs-toggle="dropdown" data-bs-auto-close="false"
                               role="button" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>">
                                <span class="nav-link-icon"><?= $item['icon'] ?></span>
                                <span class="nav-link-title"><?= htmlspecialchars((string) ($item['label'] ?? '')) ?></span>
                            </a>
                            <div class="dropdown-menu<?= $groupActive ? ' show' : '' ?>">
                                <?php foreach ($item['children'] as $child): ?>
                                    <a class="dropdown-item<?= isSlugActive((string) ($child['slug'] ?? ''), $activePage) ? ' active' : '' ?>"
                                       href="<?= htmlspecialchars((string) ($child['url'] ?? '#')) ?>">
                                        <span class="dropdown-item-icon" aria-hidden="true"><?= sidebarChildIcon((string) ($child['slug'] ?? '')) ?></span>
                                        <span class="dropdown-item-title"><?= htmlspecialchars((string) ($child['label'] ?? '')) ?></span>
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

<script>
(function () {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }

    const RECENT_LINKS_STORAGE_KEY = 'cms365-admin-recent-links';
    const RECENT_LINKS_STORAGE_LIMIT = 8;
    const SIDEBAR_COLLAPSE_BREAKPOINT = 900;

    function syncSidebarCollapsedState() {
        if (typeof window.matchMedia !== 'function') {
            return;
        }
        const media = window.matchMedia('(max-width: ' + (SIDEBAR_COLLAPSE_BREAKPOINT - 0.02) + 'px)');
        const update = () => {
            document.body.classList.toggle('sidebar-collapsed', media.matches);
        };

        update();
        if (typeof media.addEventListener === 'function') {
            media.addEventListener('change', update);
        } else if (typeof media.addListener === 'function') {
            media.addListener(update);
        }
    }

    function storageAvailable(type) {
        let storage;
        try {
            storage = window[type];
            const testKey = '__storage_test__';
            storage.setItem(testKey, testKey);
            storage.removeItem(testKey);
            return true;
        } catch (error) {
            return !!(
                error instanceof DOMException
                && error.name === 'QuotaExceededError'
                && storage
                && storage.length !== 0
            );
        }
    }

    function safeGetLocalStorageItem(key) {
        try {
            return window.localStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function safeSetLocalStorageItem(key, value) {
        try {
            window.localStorage.setItem(key, value);
            return true;
        } catch (error) {
            return false;
        }
    }

    function isValidAdminUrl(url) {
        return typeof url === 'string'
            && /^\/admin(?:\/|$)/.test(url)
            && /[\x00-\x1F\x7F]/.test(url) === false;
    }

    function normalizeRecentEntry(entry) {
        if (!entry || typeof entry !== 'object') {
            return null;
        }

        const rawUrl = typeof entry.url === 'string' ? entry.url.trim() : '';
        const rawLabel = typeof entry.label === 'string' ? entry.label.trim() : '';

        if (!isValidAdminUrl(rawUrl) || rawLabel === '') {
            return null;
        }

        const normalized = {
            url: rawUrl.slice(0, 512),
            label: rawLabel.slice(0, 160),
        };

        if (typeof entry.ts === 'string') {
            const timestamp = new Date(entry.ts);
            if (!Number.isNaN(timestamp.getTime())) {
                normalized.ts = timestamp.toISOString();
            }
        }

        return normalized;
    }

    function sanitizeRecentEntries(entries, limit) {
        const normalizedEntries = [];
        const seenUrls = new Set();

        if (!Array.isArray(entries)) {
            return normalizedEntries;
        }

        entries.forEach((entry) => {
            const normalized = normalizeRecentEntry(entry);
            if (!normalized || seenUrls.has(normalized.url)) {
                return;
            }

            seenUrls.add(normalized.url);
            normalizedEntries.push(normalized);
        });

        return normalizedEntries.slice(0, limit);
    }

    if (!storageAvailable('localStorage')) {
        return;
    }

    const currentUrl = new URL(window.location.href);
    if (!/^\/admin(?:\/|$)/.test(currentUrl.pathname)) {
        return;
    }

    ['csrf_token', 'token', 'success', 'error', 'message', 'flash'].forEach((key) => currentUrl.searchParams.delete(key));

    const normalizedUrl = currentUrl.pathname + (currentUrl.searchParams.toString() !== '' ? '?' + currentUrl.searchParams.toString() : '');
    if (!isValidAdminUrl(normalizedUrl)) {
        return;
    }

    const activeChild = document.querySelector('#sidebar-menu .dropdown-item.active');
    const activeTopLevel = document.querySelector('#sidebar-menu .nav-item.active > .nav-link .nav-link-title');
    const activeGroup = document.querySelector('#sidebar-menu .nav-item.dropdown.active > .nav-link .nav-link-title');
    let label = '';

    if (activeChild) {
        const childLabel = activeChild.textContent ? activeChild.textContent.trim() : '';
        const groupLabel = activeGroup && activeGroup.textContent ? activeGroup.textContent.trim() : '';
        label = groupLabel !== '' && groupLabel !== childLabel ? groupLabel + ' · ' + childLabel : childLabel;
    } else if (activeTopLevel && activeTopLevel.textContent) {
        label = activeTopLevel.textContent.trim();
    }

    if (label === '') {
        label = (document.title || 'Dashboard').replace(/\s*[·|-]\s*365CMS.*$/i, '').trim();
    }

    if (label === '') {
        return;
    }

    let entries = [];

    try {
        entries = JSON.parse(safeGetLocalStorageItem(RECENT_LINKS_STORAGE_KEY) || '[]');
    } catch (error) {
        entries = [];
    }

    entries = sanitizeRecentEntries(entries, RECENT_LINKS_STORAGE_LIMIT).filter((entry) => entry.url !== normalizedUrl);

    entries.unshift({
        url: normalizedUrl.slice(0, 512),
        label: label.slice(0, 160),
        ts: new Date().toISOString(),
    });

    safeSetLocalStorageItem(RECENT_LINKS_STORAGE_KEY, JSON.stringify(sanitizeRecentEntries(entries, RECENT_LINKS_STORAGE_LIMIT)));
    syncSidebarCollapsedState();
})();
</script>

<div class="page-wrapper">
