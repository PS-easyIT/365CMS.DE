<?php
/**
 * Admin Sidebar (Legacy – Inline-Rendering)
 * 
 * @deprecated Verwende stattdessen admin-menu.php mit renderAdminSidebar()!
 *             Diese Datei wird nicht mehr aktiv eingebunden. Sie existiert
 *             nur noch als Fallback für ältere Integrationen.
 * 
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Build menu items if not already defined
if (!isset($menuItems)) {
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    
    $menuItems = [
        [
            'slug' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => '📊',
            'url' => SITE_URL . '/admin',
            'active' => ($current_page === 'index')
        ],
        [
            'slug' => 'pages',
            'label' => 'Seiten',
            'icon' => '📄',
            'url' => SITE_URL . '/admin/pages',
            'active' => ($current_page === 'pages')
        ],
        [
            'slug' => 'users',
            'label' => 'Benutzer',
            'icon' => '👥',
            'url' => SITE_URL . '/admin/users',
            'active' => ($current_page === 'users')
        ],
        [
            'slug' => 'subscriptions',
            'label' => 'Abos',
            'icon' => '💳',
            'url' => SITE_URL . '/admin/subscriptions',
            'active' => ($current_page === 'subscriptions')
        ],
        [
            'slug' => 'plugins',
            'label' => 'Plugins',
            'icon' => '🔌',
            'url' => SITE_URL . '/admin/plugins',
            'active' => ($current_page === 'plugins')
        ],
        [
            'slug' => 'themes',
            'label' => 'Themeverwaltung',
            'icon' => '🖼️',
            'url' => SITE_URL . '/admin/themes',
            'active' => ($current_page === 'themes')
        ],
        [
            'slug' => 'theme-editor',
            'label' => 'Themedesign',
            'icon' => '🎨',
            'url' => SITE_URL . '/admin/theme-editor',
            'active' => ($current_page === 'theme-editor')
        ],
        [
            'slug' => 'menus',
            'label' => 'Menü Verwaltung',
            'icon' => '🗂️',
            'url' => SITE_URL . '/admin/menus',
            'active' => ($current_page === 'menus')
        ],
        [
            'slug' => 'seo',
            'label' => 'SEO',
            'icon' => '🔍',
            'url' => SITE_URL . '/admin/seo',
            'active' => ($current_page === 'seo')
        ],
        [
            'slug' => 'performance',
            'label' => 'Performance',
            'icon' => '⚡',
            'url' => SITE_URL . '/admin/performance',
            'active' => ($current_page === 'performance')
        ],
        [
            'slug' => 'analytics',
            'label' => 'Analytics',
            'icon' => '📈',
            'url' => SITE_URL . '/admin/analytics',
            'active' => ($current_page === 'analytics')
        ],
        [
            'slug' => 'backup',
            'label' => 'Backups',
            'icon' => '💾',
            'url' => SITE_URL . '/admin/backup',
            'active' => ($current_page === 'backup')
        ],
        [
            'slug' => 'groups',
            'label' => 'Gruppen',
            'icon' => '👨‍👩‍👧‍👦',
            'url' => SITE_URL . '/admin/groups',
            'active' => ($current_page === 'groups')
        ],
        [
            'slug' => 'settings',
            'label' => 'Einstellungen',
            'icon' => '⚙️',
            'url' => SITE_URL . '/admin/settings',
            'active' => ($current_page === 'settings'),
            'children' => [
                [
                    'slug'   => 'updates',
                    'label'  => 'Updates',
                    'icon'   => '🔄',
                    'url'    => SITE_URL . '/admin/updates',
                    'active' => ($current_page === 'updates')
                ]
            ]
        ],
        [
            'slug' => 'system',
            'label' => 'System & Diagnose',
            'icon' => '🔧',
            'url' => SITE_URL . '/admin/system',
            'active' => ($current_page === 'system')
        ]
    ];
    
    // Allow plugins to add menu items
    if (class_exists('CMS\Hooks')) {
        $menuItems = \CMS\Hooks::applyFilters('admin_menu_items', $menuItems);
    }
}
?>

<div class="admin-sidebar">
    <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
    
    <nav class="admin-nav">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" 
               class="nav-item <?php echo $item['active'] ? 'active' : ''; ?>">
                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
            
            <?php if (!empty($item['children'])): ?>
                <div class="nav-submenu">
                    <?php foreach ($item['children'] as $child): ?>
                        <a href="<?php echo htmlspecialchars($child['url']); ?>" 
                           class="nav-subitem <?php echo $child['active'] ? 'active' : ''; ?>">
                            <span class="nav-icon"><?php echo $child['icon']; ?></span>
                            <?php echo htmlspecialchars($child['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <hr>
        
        <a href="<?php echo SITE_URL; ?>" class="nav-item">
            <span class="nav-icon">🏠</span>
            Zur Website
        </a>
        
        <a href="<?php echo SITE_URL; ?>/logout" class="nav-item">
            <span class="nav-icon">🚪</span>
            Abmelden
        </a>
    </nav>
</div>
