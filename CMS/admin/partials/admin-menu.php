<?php
/**
 * Admin Menu – Grouped Navigation
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ermittelt, ob eine Nav-URL die aktuelle Seite ist.
 * Unterstützt exakte Pfad + Query-String-Vergleich.
 */
function _adminNavIsActive(string $navUrl): bool
{
    $currentPath  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $currentQuery = $_GET;

    $parsed = parse_url($navUrl);
    $navPath = rtrim($parsed['path'] ?? $navUrl, '/');

    if (rtrim($currentPath, '/') !== $navPath) {
        return false;
    }
    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $navParams);
        foreach ($navParams as $k => $v) {
            if (($currentQuery[$k] ?? null) !== $v) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Liefert die vollständige Menü-Struktur.
 *
 * Typen:
 *   'item'  – einzelner Link (standalone)
 *   'group' – aufklappbare Gruppe mit children
 *
 * @param string $currentPage  Slug des aktiven Menüpunkts (Legacy-Kompatibilität; URL-Erkennung hat Vorrang)
 */
function getAdminMenuItems(string $currentPage = ''): array
{
    // Fire hook to let plugins register menus
    if (class_exists('CMS\Hooks')) {
        global $cms_admin_menu;
        if (empty($cms_admin_menu)) {
            \CMS\Hooks::doAction('cms_admin_menu');
        }
    }

    $menuItems = [

        // ── Dashboard ────────────────────────────────────────────────────────
        [
            'type'   => 'item',
            'slug'   => 'dashboard',
            'label'  => 'Dashboard',
            'icon'   => '📊',
            'url'    => '/admin',
            'active' => _adminNavIsActive('/admin') || $currentPage === 'dashboard',
        ],

        // ── Plugins (Dynamic) ────────────────────────────────────────────────


        // ── Landing Page ─────────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Landing Page',
            'icon'     => '🏠',
            'children' => [
                [
                    'slug'   => 'landing-header',
                    'label'  => 'Header',
                    'icon'   => '🔝',
                    'url'    => '/admin/landing-page?section=header',
                    'active' => _adminNavIsActive('/admin/landing-page?section=header')
                              || (_adminNavIsActive('/admin/landing-page') && empty($_GET['section'])),
                ],
                [
                    'slug'   => 'landing-content',
                    'label'  => 'Content',
                    'icon'   => '📋',
                    'url'    => '/admin/landing-page?section=content',
                    'active' => _adminNavIsActive('/admin/landing-page?section=content'),
                ],
                [
                    'slug'   => 'landing-footer',
                    'label'  => 'Footer',
                    'icon'   => '🔚',
                    'url'    => '/admin/landing-page?section=footer',
                    'active' => _adminNavIsActive('/admin/landing-page?section=footer'),
                ],
                [
                    'slug'   => 'landing-design',
                    'label'  => 'Design',
                    'icon'   => '🎨',
                    'url'    => '/admin/landing-page?section=design',
                    'active' => _adminNavIsActive('/admin/landing-page?section=design'),
                ],
                [
                    'slug'   => 'landing-plugins',
                    'label'  => 'Plugins',
                    'icon'   => '🔌',
                    'url'    => '/admin/landing-page?section=plugins',
                    'active' => _adminNavIsActive('/admin/landing-page?section=plugins'),
                ],
                [
                    'slug'   => 'landing-settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/landing-page?section=settings',
                    'active' => _adminNavIsActive('/admin/landing-page?section=settings'),
                ],
            ],
        ],

        // ── Seiten & Beiträge ────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Seiten & Beiträge',
            'icon'     => '📄',
            'children' => [
                [
                    'slug'   => 'pages',
                    'label'  => 'Seiten',
                    'icon'   => '📄',
                    'url'    => '/admin/pages',
                    'active' => _adminNavIsActive('/admin/pages') || $currentPage === 'pages',
                ],
                [
                    'slug'   => 'posts',
                    'label'  => 'Beiträge',
                    'icon'   => '✏️',
                    'url'    => '/admin/posts',
                    'active' => _adminNavIsActive('/admin/posts') || $currentPage === 'posts',
                ],
            ],
        ],

        // ── Medienverwaltung ─────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Medienverwaltung',
            'icon'     => '📷',
            'children' => [
                [
                    'slug'   => 'media-library',
                    'label'  => 'Medien',
                    'icon'   => '🖼️',
                    'url'    => '/admin/media',
                    'active' => _adminNavIsActive('/admin/media') && empty($_GET['tab']),
                ],
                [
                    'slug'   => 'media-categories',
                    'label'  => 'Kategorien',
                    'icon'   => '🏷️',
                    'url'    => '/admin/media?tab=categories',
                    'active' => _adminNavIsActive('/admin/media?tab=categories'),
                ],
                [
                    'slug'   => 'site-tables',
                    'label'  => 'Site Tables',
                    'icon'   => '📊',
                    'url'    => '/admin/site-tables',
                    'active' => _adminNavIsActive('/admin/site-tables'),
                ],
                [
                    'slug'   => 'media-settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/media?tab=settings',
                    'active' => _adminNavIsActive('/admin/media?tab=settings'),
                ],
            ],
        ],

        // ── Benutzer & Gruppen ───────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Benutzer & Gruppen',
            'icon'     => '👥',
            'children' => [
                [
                    'slug'   => 'users',
                    'label'  => 'Benutzer',
                    'icon'   => '👤',
                    'url'    => '/admin/users',
                    'active' => _adminNavIsActive('/admin/users') || $currentPage === 'users',
                ],
                [
                    'slug'   => 'groups',
                    'label'  => 'Gruppen',
                    'icon'   => '🫂',
                    'url'    => '/admin/groups',
                    'active' => (_adminNavIsActive('/admin/groups') && ($_GET['tab'] ?? '') !== 'roles') || $currentPage === 'groups',
                ],
                [
                    'slug'   => 'roles',
                    'label'  => 'Rollen & Rechte',
                    'icon'   => '🔑',
                    'url'    => '/admin/rbac',
                    'active' => _adminNavIsActive('/admin/rbac') || $currentPage === 'rbac',
                ],
            ],
        ],

        // ── Aboverwaltung ────────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Aboverwaltung',
            'icon'     => '💳',
            'children' => [
                [
                    'slug'   => 'orders',
                    'label'  => 'Bestellungen',
                    'icon'   => '🛒',
                    'url'    => '/admin/orders',
                    'active' => _adminNavIsActive('/admin/orders') || ($currentPage === 'orders'),
                ],
                [
                    'slug'   => 'subscriptions',
                    'label'  => 'Pakete',
                    'icon'   => '📦',
                    'url'    => '/admin/subscriptions',
                    'active' => (_adminNavIsActive('/admin/subscriptions') && empty($_GET['tab'])) || $currentPage === 'subscriptions',
                ],
                [
                    'slug'   => 'subscriptions-assignments',
                    'label'  => 'Zuweisungen',
                    'icon'   => '🔗',
                    'url'    => '/admin/subscriptions?tab=assignments',
                    'active' => (rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') === '/admin/subscriptions'
                                 && in_array($_GET['tab'] ?? '', ['assignments', 'group-assignments'])) || $currentPage === 'subscriptions-assignments',
                ],
                [
                    'slug'   => 'subscriptions-settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/subscriptions?tab=settings',
                    'active' => (rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/') === '/admin/subscriptions'
                                 && in_array($_GET['tab'] ?? '', ['settings', 'payments'])) || $currentPage === 'subscriptions-settings',
                ],
            ],
        ],

        // ── Themes & Design ───────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Themes & Design',
            'icon'     => '🎨',
            'children' => [
                [
                    'slug'   => 'themes',
                    'label'  => 'Themes',
                    'icon'   => '🖼️',
                    'url'    => '/admin/themes',
                    'active' => (_adminNavIsActive('/admin/themes') && empty($_GET['tab'])) || $currentPage === 'themes',
                ],
                [
                    'slug'   => 'theme-customizer',
                    'label'  => 'Design Editor',
                    'icon'   => '🎨',
                    'url'    => '/admin/theme-customizer',
                    'active' => _adminNavIsActive('/admin/theme-customizer') || $currentPage === 'theme-customizer',
                ],
                [
                    'slug'   => 'menus',
                    'label'  => 'Menü Editor',
                    'icon'   => '🗂️',
                    'url'    => '/admin/menus',
                    'active' => _adminNavIsActive('/admin/menus') || $currentPage === 'menus',
                ],
                [
                    'slug'   => 'table-of-contents',
                    'label'  => 'Table of Contents',
                    'icon'   => '📑',
                    'url'    => '/admin/table-of-contents',
                    'active' => _adminNavIsActive('/admin/table-of-contents') || $currentPage === 'table-of-contents',
                ],
                [
                    'slug'   => 'theme-marketplace',
                    'label'  => 'Marketplace',
                    'icon'   => '🏪',
                    'url'    => '/admin/theme-marketplace',
                    'active' => _adminNavIsActive('/admin/theme-marketplace') || $currentPage === 'theme-marketplace',
                ],
                [
                    'slug'   => 'theme-settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/theme-settings',
                    'active' => _adminNavIsActive('/admin/theme-settings') || $currentPage === 'theme-settings',
                ],
            ],
        ],

        // ── Member Dashboard ──────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Member Dashboard',
            'icon'     => '🧩',
            // Plugins können via cms_member_dashboard_items ihre Menüpunkte hier einhängen
            'children' => \CMS\Hooks::applyFilters('cms_member_dashboard_items', [
                [
                    'slug'   => 'dashboard-plugins',
                    'label'  => 'Plugins',
                    'icon'   => '🔌',
                    'url'    => '/admin/member-dashboard?tab=plugins',
                    'active' => _adminNavIsActive('/admin/member-dashboard?tab=plugins') || $currentPage === 'dashboard-plugins',
                ],
                [
                    'slug'   => 'dashboard-widgets',
                    'label'  => 'Widgets',
                    'icon'   => '📌',
                    'url'    => '/admin/member-dashboard?tab=widgets',
                    'active' => (_adminNavIsActive('/admin/member-dashboard') && empty($_GET['tab'])) || _adminNavIsActive('/admin/member-dashboard?tab=widgets') || $currentPage === 'member-dashboard',
                ],
                [
                    'slug'   => 'dashboard-design',
                    'label'  => 'Design & Layout',
                    'icon'   => '🎨',
                    'url'    => '/admin/member-dashboard?tab=design',
                    'active' => _adminNavIsActive('/admin/member-dashboard?tab=design') || _adminNavIsActive('/admin/member-dashboard?tab=layout') || $currentPage === 'dashboard-design',
                ],
                [
                    'slug'   => 'dashboard-settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/member-dashboard?tab=settings',
                    'active' => _adminNavIsActive('/admin/member-dashboard?tab=settings') || $currentPage === 'dashboard-settings',
                ],
            ]),
        ],

        // ── SEO & Performance ────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'SEO & Performance',
            'icon'     => '📈',
            'children' => [
                [
                    'slug'   => 'seo',
                    'label'  => 'SEO Dashboard',
                    'icon'   => '🔍',
                    'url'    => '/admin/seo',
                    'active' => (_adminNavIsActive('/admin/seo') && empty($_GET['tab'])) || $currentPage === 'seo',
                ],
                [
                    'slug'   => 'seo-permalinks',
                    'label'  => 'Permalinks',
                    'icon'   => '🔗',
                    'url'    => '/admin/seo?tab=permalinks',
                    'active' => _adminNavIsActive('/admin/seo?tab=permalinks'),
                ],
                [
                    'slug'   => 'seo-indexing',
                    'label'  => 'Indexierung',
                    'icon'   => '📡',
                    'url'    => '/admin/seo?tab=indexing',
                    'active' => _adminNavIsActive('/admin/seo?tab=indexing'),
                ],
                [
                    'slug'   => 'performance',
                    'label'  => 'Performance',
                    'icon'   => '⚡',
                    'url'    => '/admin/performance',
                    'active' => _adminNavIsActive('/admin/performance') || $currentPage === 'performance',
                ],
            ],
        ],
        
        // ── Analytics ────────────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Analytics',
            'icon'     => '📊',
            'children' => [
                [
                    'slug'   => 'analytics',
                    'label'  => 'Übersicht',
                    'icon'   => '📉',
                    'url'    => '/admin/analytics',
                    'active' => (_adminNavIsActive('/admin/analytics') && empty($_GET['tab'])) || $currentPage === 'analytics',
                ],
                 [
                    'slug'   => 'analytics-404',
                    'label'  => '404 Monitor',
                    'icon'   => '🚫',
                    'url'    => '/admin/analytics?tab=404-monitor',
                    'active' => _adminNavIsActive('/admin/analytics?tab=404-monitor'),
                ],
                [
                    'slug'   => 'analytics-seo',
                    'label'  => 'SEO Analyse',
                    'icon'   => '📑',
                    'url'    => '/admin/analytics?tab=seo-analyzer',
                    'active' => _adminNavIsActive('/admin/analytics?tab=seo-analyzer'),
                ],
            ],
        ],

        // ── Recht & Sicherheit ──────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Recht & Sicherheit',
            'icon'     => '⚖️',
            'children' => [
                [
                    'slug'   => 'legal-sites',
                    'label'  => 'Legal Sites',
                    'icon'   => '§',
                    'url'    => '/admin/legal-sites', 
                    'active' => _adminNavIsActive('/admin/legal-sites') || $currentPage === 'legal-sites',
                ],
                [
                    'slug'   => 'cookies',
                    'label'  => 'Cookie Managed',
                    'icon'   => '🍪',
                    'url'    => '/admin/cookies',
                    'active' => _adminNavIsActive('/admin/cookies') || $currentPage === 'cookies',
                ],
                [
                    'slug'   => 'antispam',
                    'label'  => 'AntiSpam',
                    'icon'   => '🛡️',
                    'url'    => '/admin/antispam',
                    'active' => _adminNavIsActive('/admin/antispam') || $currentPage === 'antispam',
                ],
                [
                    'slug'   => 'fonts-local',
                    'label'  => 'Font Manager',
                    'icon'   => '🔤',
                    'url'    => '/admin/fonts-local',
                    'active' => _adminNavIsActive('/admin/fonts-local') || $currentPage === 'fonts-local',
                ],
                [
                    'slug'   => 'data-access',
                    'label'  => 'Recht auf Auskunft',
                    'icon'   => '👤',
                    'url'    => '/admin/data-access',
                    'active' => _adminNavIsActive('/admin/data-access') || $currentPage === 'data-access',
                ],
                [
                    'slug'   => 'data-deletion',
                    'label'  => 'Löschanträge',
                    'icon'   => '🗑️',
                    'url'    => '/admin/data-deletion', 
                    'active' => _adminNavIsActive('/admin/data-deletion') || $currentPage === 'data-deletion',
                ],
                [
                    'slug'   => 'security-audit',
                    'label'  => 'Security Audit',
                    'icon'   => '🛡️',
                    'url'    => '/admin/security-audit',
                    'active' => (_adminNavIsActive('/admin/security-audit') && empty($_GET['tab'])) || $currentPage === 'security-audit',
                ],
            ],
        ],

        // ── Plugins ──────────────────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Plugins',
            'icon'     => '🔌',
            'children' => [
                [
                    'slug'   => 'plugins',
                    'label'  => 'Verwalten',
                    'icon'   => '🔌',
                    'url'    => '/admin/plugins',
                    'active' => _adminNavIsActive('/admin/plugins') || $currentPage === 'plugins',
                ],
                [
                    'slug'   => 'plugin-marketplace',
                    'label'  => 'Marketplace',
                    'icon'   => '🏪',
                    'url'    => '/admin/plugin-marketplace',
                    'active' => _adminNavIsActive('/admin/plugin-marketplace') || $currentPage === 'plugin-marketplace',
                ],
            ],
        ],

        // ── System & Einstellungen ────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'System & Einstellungen',
            'icon'     => '⚙️',
            'children' => [
                [
                    'slug'   => 'settings',
                    'label'  => 'Einstellungen',
                    'icon'   => '⚙️',
                    'url'    => '/admin/settings',
                    'active' => _adminNavIsActive('/admin/settings') || $currentPage === 'settings',
                ],
                [
                    'slug'   => 'updates',
                    'label'  => 'Updates',
                    'icon'   => '🔄',
                    'url'    => '/admin/updates',
                    'active' => _adminNavIsActive('/admin/updates') || $currentPage === 'updates',
                ],
                [
                    'slug'   => 'backup',
                    'label'  => 'Backup',
                    'icon'   => '💾',
                    'url'    => '/admin/backup',
                    'active' => _adminNavIsActive('/admin/backup') || $currentPage === 'backup',
                ],
                [
                    'slug'   => 'system',
                    'label'  => 'Info & Diagnose',
                    'icon'   => '🩺',
                    'url'    => '/admin/system',
                    'active' => _adminNavIsActive('/admin/system') || $currentPage === 'system',
                ],
                [
                    'slug'   => 'support',
                    'label'  => 'Support & Docs',
                    'icon'   => '📖',
                    'url'    => '/admin/support',
                    'active' => _adminNavIsActive('/admin/support') || $currentPage === 'support',
                ],
            ],
        ],

    ];

    // ── Load Dynamic Menus from add_menu_page() ──
    if (function_exists('get_registered_admin_menus')) {
        $dynamicMenus = get_registered_admin_menus();
        
        foreach ($dynamicMenus as $menu) {
            // Plugins mit hidden=true erscheinen im Member Dashboard, nicht als eigene Gruppe
            if (!empty($menu['hidden'])) continue;
            // Convert 'plugin_page' to Template format
            if (isset($menu['type']) && ($menu['type'] === 'plugin_page')) {
                // Determine if active
                // Case 1: Active via Router plugin URL
                // /admin/plugins/{parent}/{page}
                // Case 2: Active via legacy currentPage slug
                
                $menuSlug = $menu['menu_slug'];
                
                // Prepare Children
                $children = [];
                $hasActiveChild = false;
                
                if (!empty($menu['children'])) {
                    foreach ($menu['children'] as $child) {
                        $childSlug = $child['menu_slug'];
                        $childUrl  = !empty($child['url']) ? $child['url'] : ('/admin/plugins/' . $menuSlug . '/' . $childSlug);
                        
                        // Check if child is active
                        // If exact match OR if current page is the child slug
                        // Note: Allow "jobads" parent to open if "jobads-new" is active
                        $parsedUrl = parse_url($childUrl);
                        $childPath = $parsedUrl['path'] ?? $childUrl;
                        
                        $isChildActive = _adminNavIsActive($childPath) || ($currentPage === $childSlug);
                        if ($isChildActive) $hasActiveChild = true;

                        $children[] = [
                            'slug'   => $childSlug,
                            'label'  => $child['menu_title'],
                            'icon'   => '🔹',
                            'url'    => $childUrl,
                            'active' => $isChildActive,
                        ];
                    }
                }

                // If children exist, render as Group
                if (!empty($children)) {
                    // Determine Icon
                    $icon = '🧩';
                    if (!empty($menu['icon_url'])) {
                        if (strpos($menu['icon_url'], 'dashicons') !== false) {
                            $icon = '<span class="dashicons ' . htmlspecialchars($menu['icon_url']) . '"></span>';
                        } else {
                             $icon = $menu['icon_url']; // Emoji or Image URL
                        }
                    }

                    $menuItems[] = [
                        'type'     => 'group',
                        'label'    => $menu['menu_title'],
                        'icon'     => $icon, 
                        'children' => $children,
                        // Add 'active' property to group so it stays open
                        'active'   => $hasActiveChild 
                    ];
                } else {
                    // Standalone Item
                    $url = '/admin/plugins/' . $menuSlug . '/' . $menuSlug;
                    
                    // Determine Icon
                    $icon = '🧩';
                    if (!empty($menu['icon_url'])) {
                        if (strpos($menu['icon_url'], 'dashicons') !== false) {
                            $icon = '<span class="dashicons ' . htmlspecialchars($menu['icon_url']) . '"></span>';
                        } else {
                             $icon = $menu['icon_url']; // Emoji or Image URL
                        }
                    }

                    $menuItems[] = [
                        'type'   => 'item',
                        'slug'   => $menuSlug,
                        'label'  => $menu['menu_title'],
                        'icon'   => $icon, 
                        'url'    => $url,
                        'active' => _adminNavIsActive($url) || ($currentPage === $menuSlug),
                    ];
                }
            }
        }
    }

    // Plugins können Menüpunkte ergänzen (Legacy-Hook)
    $menuItems = \CMS\Hooks::applyFilters('admin_menu_items', $menuItems);

    return $menuItems;
}

/**
 * Render admin sidebar styles
 */
function renderAdminSidebarStyles(): void
{
    ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-sidebar.css?v=20260222">
    <?php
    if (class_exists('\CMS\Services\EditorService')) {
        \CMS\Services\EditorService::getInstance()->enqueueEditorAssets();
    }
}

/**
 * Render admin sidebar
 *
 * @param string $currentPage  Active slug (Legacy; URL detection takes precedence)
 */
function renderAdminSidebar(string $currentPage = ''): void
{
    $menuItems = getAdminMenuItems($currentPage);
    ?>
    <div class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="<?= SITE_URL ?>/admin" class="admin-sidebar-logo">
                <img src="<?= SITE_URL ?>/assets/images/365CMS-DASHBOARD-Admin-100px.png"
                     alt="<?= htmlspecialchars(SITE_NAME) ?> Admin"
                     class="sidebar-logo-img">
            </a>
        </div>

        <nav class="admin-nav">
            <?php foreach ($menuItems as $item):
                $type = $item['type'] ?? 'item';
                
                // Atom Plugin Support (Simplified)
                if ($type === 'atom_plugin') {
                   // Check if plugin is active - always show for now or assume activated
                   // if (!in_array($item['plugin'], get_active_plugins() ?? [])) continue;
                   
                   // Unwrap data
                   $item = $item['data'];
                   $type = $item['type'];
                }

                if ($type === 'item'): ?>

                    <a href="<?= htmlspecialchars($item['url']) ?>"
                       class="nav-item <?= !empty($item['active']) ? 'active' : '' ?>">
                        <span class="nav-icon"><?= $item['icon'] ?></span>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>

                <?php elseif ($type === 'group'):
                    // Gruppe ist offen wenn ein Kind aktiv ist
                    $groupOpen = !empty($item['active']); 
                    if (!$groupOpen) {
                        foreach ($item['children'] as $child) {
                            if (!empty($child['active'])) { $groupOpen = true; break; }
                        }
                    }
                    ?>

                    <div class="nav-divider"></div>
                    <details class="nav-group" <?= $groupOpen ? 'open' : '' ?>>
                        <summary class="nav-group-header">
                            <span><?= $item['icon'] ?></span>
                            <?= htmlspecialchars($item['label']) ?>
                            <span class="nav-group-arrow">▶</span>
                        </summary>
                        <div class="nav-group-children">
                            <?php foreach ($item['children'] as $child): ?>
                                <?php if (($child['type'] ?? 'link') === 'section'): ?>
                                    <div class="nav-section-label">
                                        <?php if (!empty($child['icon'])): ?><span class="nav-icon"><?= $child['icon'] ?></span><?php endif; ?>
                                        <?= htmlspecialchars($child['label']) ?>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($child['url']) ?>"
                                       class="nav-subitem <?= !empty($child['active']) ? 'active' : '' ?>">
                                        <span class="nav-icon"><?= $child['icon'] ?></span>
                                        <?= htmlspecialchars($child['label']) ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </details>

                <?php endif; ?>
            <?php endforeach; ?>

            <div class="admin-sidebar-footer">
                <a href="<?= SITE_URL ?>" class="nav-item">
                    <span class="nav-icon">🏠</span>Zur Website
                </a>
                <a href="<?= SITE_URL ?>/logout" class="nav-item">
                    <span class="nav-icon">🚪</span>Abmelden
                </a>
            </div>
        </nav>
    </div>
    <?php
}

/**
 * Öffnet das Admin-Layout (Head + Sidebar).
 * Muss mit renderAdminLayoutEnd() geschlossen werden.
 *
 * @param string $title     Seitentitel (wird im <title>-Tag verwendet)
 * @param string $activeSlug Aktiver Menü-Punkt (z.B. 'experts')
 */
function renderAdminLayoutStart(string $title, string $activeSlug = ''): void
{
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> – <?php echo htmlspecialchars(SITE_NAME); ?></title>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-sidebar.css?v=20260222c">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222c">
        <?php 
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::doAction('head');
            CMS\Hooks::doAction('admin_head');
        }
        renderAdminSidebarStyles(); 
        ?>
    </head>
    <body class="admin-body">
        <?php renderAdminSidebar($activeSlug); ?>
        <div class="admin-content">
    <?php
}

/**
 * Schließt das Admin-Layout (schließt .admin-content, body, html).
 */
function renderAdminLayoutEnd(): void
{
    ?>
        </div><!-- /.admin-content -->
    </body>
    </html>
    <?php
}
