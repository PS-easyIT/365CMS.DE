<?php
/**
 * Member Menu Items
 * 
 * Zentrale Definition aller Member-Menüpunkte mit Plugin-Hook-Support
 * 
 * @package CMSv2\Member
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Ensure autoloader if accessed directly via include without context (safety fallback)
if (!class_exists('\CMS\Database') && defined('CORE_PATH')) {
    require_once CORE_PATH . 'autoload.php';
}

/**
 * Get member menu items
 * 
 * @param string $currentPage Current page slug
 * @return array Menu items
 */
function getMemberMenuItems(string $currentPage = ''): array
{
    $menuItems = [
        // HAUPTMENÜ
        [
            'slug' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => '🏠',
            'url' => '/member',
            'active' => $currentPage === 'dashboard',
            'category' => 'main'
        ],
        
        // NETZWERK
        [
            'slug' => 'media',
            'label' => 'Meine Files',
            'icon' => '📂',
            'url' => '/member/media',
            'active' => $currentPage === 'media',
            'category' => 'network'
        ],
        [
            'slug' => 'messages',
            'label' => 'Nachrichten',
            'icon' => '💬',
            'url' => '/member/messages',
            'active' => $currentPage === 'messages',
            'category' => 'network'
        ],
        [
            'slug' => 'favorites',
            'label' => 'Favoriten',
            'icon' => '⭐',
            'url' => '/member/favorites',
            'active' => $currentPage === 'favorites',
            'category' => 'network'
        ],

        // EINSTELLUNGEN
        [
            'slug' => 'profile',
            'label' => 'Mein Profil',
            'icon' => '👤',
            'url' => '/member/profile',
            'active' => $currentPage === 'profile',
            'category' => 'settings'
        ],
        [
            'slug' => 'notifications',
            'label' => 'Benachrichtigungen',
            'icon' => '🔔',
            'url' => '/member/notifications',
            'active' => $currentPage === 'notifications',
            'category' => 'settings'
        ],
        [
            'slug' => 'privacy',
            'label' => 'Datenschutz',
            'icon' => '🛡️',
            'url' => '/member/privacy',
            'active' => $currentPage === 'privacy',
            'category' => 'settings'
        ],
        [
            'slug' => 'security',
            'label' => 'Sicherheit',
            'icon' => '🔒',
            'url' => '/member/security',
            'active' => $currentPage === 'security',
            'category' => 'settings'
        ],
        // Abo (optional)
        [
            'slug' => 'subscription',
            'label' => 'Mein Abo',
            'icon' => '💎',
            'url' => '/member/subscription',
            'active' => $currentPage === 'subscription',
            'category' => 'settings',
            'visible' => isMemberSubscriptionVisible()
        ],
    ];
    
    // Allow plugins to add menu items (Hook-System für Plugins!)
    // Plugins sollten 'category' => 'plugins' verwenden oder was anderes
    if (class_exists('\\CMS\\Hooks')) {
        $menuItems = \CMS\Hooks::applyFilters('member_menu_items', $menuItems);
    }
    
    // Filter nicht sichtbare Items
    $menuItems = array_filter($menuItems, function($item) {
        return !isset($item['visible']) || $item['visible'] === true;
    });
    
    return $menuItems;
}

/**
 * Check if subscription status is visible for members
 * 
 * @return bool
 */
function isMemberSubscriptionVisible(): bool
{
    $db = \CMS\Database::instance();
    // Default to strict check, ensure table exists or option exists
    // Simplified for this context:
    $setting = $db->get_var(
        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_subscription_visible'"
    );
    return $setting === '1' || $setting === 'yes';
}

/**
 * Check permission for member media
 */
function isMemberMediaVisible(): bool {
    // Media perm check
    return true; // Activated by default in new layout
}

/**
 * Get menu items grouped by category
 * 
 * @param string $currentPage Current page slug
 * @return array Grouped menu items
 */
function getMemberMenuGrouped(string $currentPage = ''): array
{
    $allItems = getMemberMenuItems($currentPage);
    
    // Define exact order of categories
    $grouped = [
        'main'     => [],
        'network'  => [],
        'plugins'  => [],
        'settings' => []
    ];
    
    foreach ($allItems as $item) {
        $category = $item['category'] ?? 'plugins';
        if (!isset($grouped[$category])) {
            // Falls Plugins eigene Kategorien nutzen, dynamisch anlegen
            // Wir packen unbekannte Sachen aber besser zu 'plugins'
            $category = 'plugins'; 
        }
        $grouped[$category][] = $item;
    }
    
    return $grouped;
}

/**
 * Render member sidebar
 * 
 * @param string $currentPage Current active page
 * @return void
 */
function renderMemberSidebar(string $currentPage = ''): void
{
    $auth = \CMS\Auth::instance();
    $user = $auth->getCurrentUser(); // Returns object with ->username, ->email, ->role, ->status
    $menuGroups = getMemberMenuGrouped($currentPage);
    
    // Helper to fetch colors (fallback to defaults if not set)
    // Wir nutzen hier CSS Variablen, die im Dashboard View gesetzt wurden,
    // oder Fallbacks für Seiten, die das nicht haben.
    
    $isAdmin = $auth->isAdmin();
    
    $labels = [
        'main'     => 'HAUPTMENÜ',
        'network'  => 'NETZWERK',
        'plugins'  => 'PLUGIN BEREICH',
        'settings' => 'EINSTELLUNGEN'
    ];
    
    ?>
    <aside class="member-sidebar">
        <!-- Sidebar Header: Branding & User -->
        <div class="sidebar-header">
            <div class="sidebar-brand">
                <span class="brand-logo">🔹</span>
                <span class="brand-name"><?php echo htmlspecialchars(SITE_NAME); ?></span>
            </div>
            
            <div class="sidebar-user">
                <div class="user-avatar-circle">
                    <?php echo strtoupper(substr($user->username, 0, 2)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name-row">
                        <span class="user-name"><?php echo htmlspecialchars($user->username); ?></span>
                        <span class="status-badge status-<?php echo $user->status; ?>" title="Status: <?php echo ucfirst($user->status); ?>">
                            <?php echo $user->status === 'active' ? '✓' : '•'; ?>
                        </span>
                    </div>
                    <span class="user-role-label"><?php echo ucfirst($user->role); ?></span>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-menu-scroll">
            <nav class="sidebar-nav">
                <?php foreach ($labels as $catKey => $catLabel): 
                    if (empty($menuGroups[$catKey])) continue;
                ?>
                <div class="nav-section">
                    <h4 class="nav-section-title"><?php echo $catLabel; ?></h4>
                    <ul class="nav-list">
                        <?php foreach ($menuGroups[$catKey] as $item): ?>
                        <li>
                            <a href="<?php echo $item['url']; ?>" class="nav-link<?php echo $item['active'] ? ' active' : ''; ?>">
                                <span class="nav-icon"><?php echo $item['icon']; ?></span>
                                <span class="nav-text"><?php echo $item['label']; ?></span>
                                <?php if ($item['active']): ?><span class="active-dot"></span><?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Footer / Logout -->
        <div class="sidebar-footer">
            <?php if ($isAdmin): ?>
                <a href="<?php echo SITE_URL; ?>/admin" class="footer-link admin-link">
                    🛠️ Admin
                </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/logout" class="footer-link logout-link">
                🚪 Abmelden
            </a>
        </div>
    </aside>
    <?php
}

/**
 * Render member sidebar styles
 * New Flat / Admin-like Design
 */
function renderMemberSidebarStyles(): void
{
    ?>
    <style>
        /* Sidebar Variables (Default Fallbacks) */
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;     /* Slate 800 */
            --sidebar-text: #94a3b8;   /* Slate 400 */
            --sidebar-text-hover: #f1f5f9; /* Slate 100 */
            --sidebar-active-bg: #334155; /* Slate 700 */
            --sidebar-active-accent: #6366f1; /* Indigo 500 */
            --sidebar-header-bg: #0f172a; /* Slate 900 */
        }

        body.member-body {
            display: flex; /* Sidebar layout */
            margin: 0;
            background-color: #f1f5f9;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .member-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255,255,255,0.05);
            z-index: 50;
        }

        .member-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
        }
        
        /* Mobile adjustment handled via media queries usually, keeping simple here */

        /* Header */
        .sidebar-header {
            background-color: var(--sidebar-header-bg);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-brand {
            font-size: 1.125rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 1.5rem;
            display: flex; 
            align-items: center; 
            gap: 0.5rem;
        }
        
        /* User Profile in Sidebar */
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .user-avatar-circle {
            width: 40px; height: 40px;
            background: var(--sidebar-active-accent);
            color: #fff;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        .user-details {
            flex: 1;
            min-width: 0; /* Text truncation fix */
        }
        .user-name-row {
            display: flex; align-items: center; gap: 0.5rem;
        }
        .user-name {
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .status-badge {
            font-size: 0.65rem;
            padding: 0.1rem 0.3rem;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
            color: var(--sidebar-text);
        }
        .status-badge.status-active { background: rgba(16, 185, 129, 0.2); color: #34d399; } /* Greenish */
        
        .user-role-label {
            display: block;
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.1rem;
        }

        /* Menu Scroll Area */
        .sidebar-menu-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 0;
        }
        
        /* Nav Sections */
        .nav-section { margin-bottom: 1.5rem; }
        .nav-section-title {
            padding: 0 1.5rem;
            margin: 0 0 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b; /* Muted Slate */
            font-weight: 700;
        }
        .nav-list { list-style: none; padding: 0; margin: 0; }
        .nav-list li { margin-bottom: 2px; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1.5rem;
            text-decoration: none;
            color: var(--sidebar-text);
            font-size: 0.9rem;
            transition: all 0.15s;
            position: relative;
        }
        .nav-link:hover {
            color: var(--sidebar-text-hover);
            background: rgba(255,255,255,0.03);
        }
        .nav-link.active {
            background: var(--sidebar-active-bg);
            color: #fff;
            font-weight: 500;
            border-right: 3px solid var(--sidebar-active-accent);
        }
        .nav-icon {
            font-size: 1.1rem;
            opacity: 0.8;
            width: 24px; text-align: center;
        }
        .nav-link.active .nav-icon { opacity: 1; color: var(--sidebar-active-accent); }

        /* Footer */
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            background: var(--sidebar-header-bg);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .footer-link {
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background 0.15s;
        }
        .footer-link:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .admin-link { color: var(--sidebar-active-accent); }

        .member-user-email {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .member-user-role {
            font-size: 0.75rem;
            color: #667eea;
            background: rgba(102, 126, 234, 0.2);
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            display: inline-block;
            width: fit-content;
        }
        
        .member-nav {
            padding: 1rem 0;
        }
        
        .member-nav-section {
            margin-bottom: 1.5rem;
        }
        
        .member-nav-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.5);
            padding: 0.5rem 1.5rem;
            margin: 0 0 0.5rem;
            font-weight: 600;
        }
        
        .member-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
            gap: 0.75rem;
        }
        
        .member-nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .member-nav-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.3) 0%, transparent 100%);
            color: white;
            border-left: 3px solid #667eea;
        }
        
        .member-nav-icon {
            font-size: 1.25rem;
        }
        
        .member-nav-text {
            font-size: 0.9375rem;
            font-weight: 500;
        }
        
        .member-nav-indicator {
            position: absolute;
            right: 1rem;
            width: 6px;
            height: 6px;
            background: #667eea;
            border-radius: 50%;
        }
        
        .member-sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .member-btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .member-btn-logout:hover {
            background: rgba(239, 68, 68, 0.3);
            color: white;
        }
        /* Admin-Badge in der Sidebar */
        .member-admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: rgba(251,191,36,0.2);
            border: 1px solid rgba(251,191,36,0.5);
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #fbbf24;
            text-decoration: none;
            transition: background 0.15s;
        }
        .member-admin-badge:hover {
            background: rgba(251,191,36,0.35);
            color: #fde68a;
        }
        
        .member-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
            background: #f7fafc;
        }
        
        @media (max-width: 768px) {
            .member-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .member-content {
                margin-left: 0;
            }
            
            .member-sidebar-footer {
                position: relative;
            }
        }
    </style>
    <?php
    // SunEditor auf allen Member-Seiten laden
    $__sunUrl = defined('SITE_URL') ? SITE_URL : '';
    echo '<link rel="stylesheet" href="' . htmlspecialchars($__sunUrl) . '/assets/suneditor/css/suneditor.min.css">' . "\n";
    echo '<script src="' . htmlspecialchars($__sunUrl) . '/assets/suneditor/suneditor.min.js"></script>' . "\n";
    echo '<script src="' . htmlspecialchars($__sunUrl) . '/assets/suneditor/lang/de.js"></script>' . "\n";
}
