<?php
/**
 * Admin Menu – Grouped Navigation
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

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
                    'url'    => '/admin/groups?tab=roles',
                    'active' => _adminNavIsActive('/admin/groups?tab=roles') || $currentPage === 'roles',
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
                    'label'  => 'Pluginverwaltung',
                    'icon'   => '🔌',
                    'url'    => '/admin/design-dashboard-widgets?tab=plugins',
                    'active' => _adminNavIsActive('/admin/design-dashboard-widgets?tab=plugins') || $currentPage === 'dashboard-plugins',
                ],
                [
                    'slug'   => 'dashboard-widgets',
                    'label'  => 'Info Widgets',
                    'icon'   => '📌',
                    'url'    => '/admin/design-dashboard-widgets?tab=widgets',
                    'active' => (_adminNavIsActive('/admin/design-dashboard-widgets') && empty($_GET['tab'])) || _adminNavIsActive('/admin/design-dashboard-widgets?tab=widgets') || $currentPage === 'design-dashboard-widgets',
                ],
                [
                    'slug'   => 'dashboard-layout',
                    'label'  => 'Layout',
                    'icon'   => '🗂️',
                    'url'    => '/admin/design-dashboard-widgets?tab=layout',
                    'active' => _adminNavIsActive('/admin/design-dashboard-widgets?tab=layout') || $currentPage === 'dashboard-layout',
                ],
                [
                    'slug'   => 'dashboard-design',
                    'label'  => 'Design (Farben)',
                    'icon'   => '🎨',
                    'url'    => '/admin/design-dashboard-widgets?tab=design',
                    'active' => _adminNavIsActive('/admin/design-dashboard-widgets?tab=design') || $currentPage === 'dashboard-design',
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
                    'slug'   => 'analytics',
                    'label'  => 'Analytics',
                    'icon'   => '📊',
                    'url'    => '/admin/analytics',
                    'active' => _adminNavIsActive('/admin/analytics') || $currentPage === 'analytics',
                ],
                [
                    'slug'   => 'seo',
                    'label'  => 'SEO',
                    'icon'   => '🔍',
                    'url'    => '/admin/seo',
                    'active' => _adminNavIsActive('/admin/seo') || $currentPage === 'seo',
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

        // ── Recht & Sicherheit ──────────────────────────────────────────────
        [
            'type'     => 'group',
            'label'    => 'Recht & Sicherheit',
            'icon'     => '⚖️',
            'children' => [
                [
                    'slug'   => 'cookies',
                    'label'  => 'Cookie Managed',
                    'icon'   => '🍪',
                    'url'    => '/admin/cookies', // Neu
                    'active' => _adminNavIsActive('/admin/cookies') || $currentPage === 'cookies',
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
                    'url'    => '/admin/data-access', // Neu
                    'active' => _adminNavIsActive('/admin/data-access') || $currentPage === 'data-access',
                ],
                [
                    'slug'   => 'data-deletion',
                    'label'  => 'Löschanträge',
                    'icon'   => '🗑️',
                    'url'    => '/admin/data-deletion', // Neu
                    'active' => _adminNavIsActive('/admin/data-deletion') || $currentPage === 'data-deletion',
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
                    'slug'   => 'plugin-updates',
                    'label'  => 'Installieren & Updates',
                    'icon'   => '🔄',
                    'url'    => '/admin/updates',
                    'active' => _adminNavIsActive('/admin/updates') || $currentPage === 'updates',
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
    <style>
        body.admin-body {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            background: #f1f5f9;
            margin: 0;
        }

        /* ── Sidebar Shell ── */
        .admin-sidebar {
            width: 220px;
            background: #0f172a;
            color: #cbd5e1;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            left: 0;
            top: 0;
            z-index: 1000;
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: #334155 transparent;
        }
        .admin-sidebar::-webkit-scrollbar { width: 4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }

        /* ── Sidebar Header ── */
        .admin-sidebar-header {
            padding: 1.25rem 1rem 1rem;
            border-bottom: 1px solid #1e293b;
        }
        .admin-sidebar-logo {
            font-size: 1rem;
            font-weight: 700;
            color: #f1f5f9;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: .5rem;
            letter-spacing: -.01em;
        }

        /* ── Content Area ── */
        .admin-content {
            margin-left: 220px;
            padding: 2rem;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .admin-content h2,
        .admin-content h3,
        .admin-content h4,
        .admin-content p,
        .admin-content label,
        .admin-content input,
        .admin-content textarea,
        .admin-content select,
        .admin-content button {
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        }

        /* ── Nav Container ── */
        .admin-nav {
            padding: .75rem 0;
        }

        /* ── Standalone Item ── */
        .nav-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: .8125rem;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            transition: background .15s, color .15s;
            border-radius: 0;
        }
        .nav-item:hover { background: #1e293b; color: #f1f5f9; }
        .nav-item.active { background: #1d4ed8; color: #fff; font-weight: 600; }
        .nav-item .nav-icon { font-size: .9rem; flex-shrink: 0; }

        /* ── Group (details/summary) ── */
        .nav-group {
            border: none;
        }
        .nav-group-header {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem 1rem;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #475569;
            cursor: pointer;
            list-style: none;
            user-select: none;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            transition: color .15s;
        }
        .nav-group-header:hover { color: #94a3b8; }
        .nav-group-header::marker,
        .nav-group-header::-webkit-details-marker { display: none; }
        .nav-group-arrow {
            margin-left: auto;
            font-size: .65rem;
            opacity: .5;
            transition: transform .2s;
        }
        details.nav-group[open] .nav-group-arrow { transform: rotate(90deg); }

        /* ── Group Children ── */
        .nav-group-children {
            padding: 0 0 .25rem 0;
        }
        .nav-subitem {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .4rem 1rem .4rem 2rem;
            color: #64748b;
            text-decoration: none;
            font-size: .8rem;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            transition: background .15s, color .15s;
        }
        .nav-subitem:hover { background: #1e293b; color: #e2e8f0; }
        .nav-subitem.active {
            background: #172554;
            color: #93c5fd;
            font-weight: 600;
            border-left: 2px solid #3b82f6;
            padding-left: calc(2rem - 2px);
        }
        .nav-subitem .nav-icon { font-size: .8rem; flex-shrink: 0; }

        /* ── Section Label (Trennüberschrift innerhalb Gruppen-Kinder) ── */
        .nav-section-label {
            display: flex;
            align-items: center;
            gap: .35rem;
            padding: .7rem 1rem .2rem 1rem;
            font-size: .63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #2d3f55;
            margin-top: .15rem;
            border-top: 1px solid #1e293b;
        }
        .nav-section-label:first-child { border-top: none; padding-top: .4rem; }
        .nav-section-label .nav-icon { font-size: .72rem; opacity: .7; }

        /* ── Divider ── */
        .nav-divider {
            height: 1px;
            background: #1e293b;
            margin: .5rem 0;
        }

        /* ── Footer Links ── */
        .admin-sidebar-footer {
            padding: .75rem 0;
            border-top: 1px solid #1e293b;
            margin-top: .5rem;
        }

        @media (max-width: 768px) {
            .admin-sidebar { width: 100%; position: relative; height: auto; }
            .admin-content { margin-left: 0; }
        }
    </style>
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
                <span>🛠️</span>
                <?= htmlspecialchars(SITE_NAME) ?>
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
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
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
