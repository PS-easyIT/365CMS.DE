<?php
/**
 * Member Dashboard View
 *
 * Variables provided by index.php controller:
 * - $dashboardData : array  – Dashboard stats, activities, subscription info
 * - $user          : object – Current user (injected by MemberController::render)
 *
 * @package CMSv2\Member\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

// ── Hilfsfunktionen ────────────────────────────────────────────────────────────

/** Lädt die Design-Einstellungen aus der Datenbank */
function getDashboardDesignSettings(): array
{
    $db = \CMS\Database::instance();
    // Fallback Farben angelehnt an Admin-Design (Slate/Indigo)
    return [
        'primary' => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_primary'") ?: '#6366f1', // Indigo-500
        'accent'  => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_accent'") ?: '#8b5cf6', // Violet-500
        'bg'      => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_bg'") ?: '#f1f5f9', // Slate-100
        'card_bg' => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_card_bg'") ?: '#ffffff',
        'text'    => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_text'") ?: '#1e293b', // Slate-800
        'border'  => $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_color_border'") ?: '#e2e8f0', // Slate-200
    ];
}

/** Lädt bis zu 4 benutzerdefinierte Admin-Widgets aus den CMS-Einstellungen */
function getDashboardCustomWidgets(): array
{
    $db = \CMS\Database::instance();
    $widgets = [];
    for ($i = 1; $i <= 4; $i++) {
        $title   = $db->get_var(
            "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
            ["member_widget_{$i}_title"]
        );
        $content = $db->get_var(
            "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
            ["member_widget_{$i}_content"]
        );
        $icon    = $db->get_var(
            "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ?",
            ["member_widget_{$i}_icon"]
        );
        if (!empty($title) || !empty($content)) {
            $widgets[] = ['title' => $title ?? '', 'content' => $content ?? '', 'icon' => $icon ?: '📌'];
        }
    }
    return $widgets;
}

/** Gibt die verfügbaren Feature-Widgets zurück – gefiltert nach aktiven Plugins + Rechten */
function getDashboardFeatureWidgets(object $user): array
{
    $isAdmin = \CMS\Auth::instance()->isAdmin();
    $widgets = [];

    // Experten-Plugin
    if (class_exists('CMS_Experts_Database')) {
        $widgets[] = [
            'icon'       => '🧑‍💼',
            'title'      => 'Experten-Profile',
            'text'       => 'IT-Experten suchen, Profile anlegen und verwalten.',
            'link'       => '/experts',
            'link_label' => 'Zur Übersicht',
            'admin_link' => $isAdmin ? '/admin/experts' : null,
            'admin_label'=> $isAdmin ? '⚙️ Verwalten' : null,
            'color'      => '#4f46e5',
        ];
    }

    // Companies-Plugin
    if (class_exists('CMS_Companies_Database')) {
        $widgets[] = [
            'icon'       => '🏢',
            'title'      => 'Unternehmen',
            'text'       => 'Firmen im Netzwerk entdecken und Experten zuordnen.',
            'link'       => '/companies',
            'link_label' => 'Zur Übersicht',
            'admin_link' => $isAdmin ? '/admin/companies' : null,
            'admin_label'=> $isAdmin ? '⚙️ Verwalten' : null,
            'color'      => '#0891b2',
        ];
    }

    // Plugin-Hook: externe Widgets einbinden (von anderen Plugins)
    $hook_widgets = \CMS\Hooks::applyFilters('member_dashboard_widgets', []);
    foreach ($hook_widgets as $hw) {
        $widgets[] = [
            'icon'       => $hw['icon'] ?? '🔌',
            'title'      => $hw['title'] ?? 'Plugin-Widget',
            'text'       => $hw['description'] ?? '',
            'link'       => $hw['url'] ?? null,
            'link_label' => $hw['link_label'] ?? 'Öffnen',
            'color'      => $hw['color'] ?? '#7c3aed',
            'callback'   => $hw['callback'] ?? null,
        ];
    }

    return $widgets;
}

/**
 * Liefert Plugin-Bereich-Widgets aus der PluginDashboardRegistry.
 * Diese enthalten optionale Statistiken und direkten Navigationslink.
 */
function getDashboardRegistryWidgets(object $user): array
{
    if (!class_exists('\CMS\Member\PluginDashboardRegistry')) {
        return [];
    }
    $registry = \CMS\Member\PluginDashboardRegistry::instance();
    $registry->init();
    return $registry->getDashboardWidgets($user);
}

$isAdmin             = \CMS\Auth::instance()->isAdmin();
$featureWidgets      = getDashboardFeatureWidgets($user);
$registryPluginWidgets = getDashboardRegistryWidgets($user);
$customWidgets       = getDashboardCustomWidgets();
$designColors        = getDashboardDesignSettings();
$firstName      = htmlspecialchars(explode(' ', $user->username)[0]);
$hour           = (int) date('H');
$greeting       = $hour < 12 ? 'Guten Morgen' : ($hour < 18 ? 'Guten Tag' : 'Guten Abend');

// Neue Settings laden (Layout & Logo)
$db = \CMS\Database::instance();
$layoutCols  = (int) ($db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_columns'") ?: 3);
$layoutOrder = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_section_order'") ?: 'stats,widgets,plugins';
$memberLogo  = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_logo'") ?: '';

// Spaltenbreite berechnen (Grid based on 12)
// 1 Col -> span 12
// 2 Col -> span 6
// 3 Col -> span 4
// 4 Col -> span 3
$colSpan = match($layoutCols) {
    1 => 12,
    2 => 6,
    4 => 3,
    default => 4 // 3 Columns
};

// Begrüßung & Willkommenstext laden
$greetingTpl = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_greeting'") ?: 'Guten Tag, {name}!';
$welcomeText = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_welcome_text'") ?: '';
$showWelcome = $db->get_var("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'member_dashboard_show_welcome'") ?: '1';

// Platzhalter ersetzen
$greeting = str_replace('{name}', $firstName, $greetingTpl);

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/member.css">
    <?php renderMemberSidebarStyles(); ?>
    <style>
        /* ── Dynamic Design Settings ── */
        :root {
            --member-primary: <?php echo $designColors['primary']; ?>;
            --member-secondary: <?php echo $designColors['accent']; ?>;
            --member-bg: <?php echo $designColors['bg']; ?>;
            --member-surface: <?php echo $designColors['card_bg']; ?>;
            --member-border: <?php echo $designColors['border']; ?>;
            --member-text: <?php echo $designColors['text']; ?>;
            
            --member-gradient-primary: linear-gradient(135deg, <?php echo $designColors['primary']; ?> 0%, <?php echo $designColors['accent']; ?> 100%);
        }
        
        /* ── Modern Flat Design (Admin-Style) ── */
        .member-content {
            background-color: var(--member-bg);
            min-height: 100vh;
        }

        .member-page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--member-border);
        }
        .member-hello h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--member-text);
            margin: 0 0 0.5rem 0;
            letter-spacing: -0.025em;
        }
        .member-hello p {
            color: #64748b;
            margin: 0;
            font-size: 1rem;
        }
        .member-meta-info {
            font-size: 0.875rem;
            color: #94a3b8;
            text-align: right;
        }
        
        /* Welcome Banner */
        .member-welcome-banner {
            background: var(--member-surface);
            border: 1px solid var(--member-border);
            border-left: 4px solid var(--member-primary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .member-welcome-banner p { margin: 0; color: var(--member-text); line-height: 1.6; }

        /* ── 4-Col Grid for Top Stats ── */
        .member-stats-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
            margin-bottom: 4.5rem; /* Increased spacing to next section */
        }
        
        .stat-card {
            background: var(--member-surface);
            border: 1px solid var(--member-border);
            border-radius: 8px; /* Slightly squarer like admin */
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); /* Subtle shadow */
            transition: all 0.2s ease;
            height: 100%; /* Ensure full height */
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-color: var(--member-primary);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        
        .stat-icon {
            font-size: 1.25rem;
            opacity: 0.8;
            color: var(--member-primary);
        }

        .stat-body {
            flex-grow: 1; /* Pushes footer down */
        }
        
        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--member-text);
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }
        
        .stat-meta {
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        /* Grid Spans */
        .stat-col { grid-column: span 12; }
        @media (min-width: 768px) { .stat-col { grid-column: span 6; } }
        /* Force 4 columns on large screens for top row */
        @media (min-width: 1200px) { .stat-col { grid-column: span 3; } }

        /* ── Section Titles ── */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            margin-top: 3rem;
            border-left: 4px solid var(--member-primary);
            padding-left: 1rem;
        }
        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--member-text);
        }

        /* ── Dynamic Layout Columns ── */
        .grid-item-col { grid-column: span 12; display: flex; }
        
        @media (min-width: 768px) {
            .grid-item-col { grid-column: span <?php echo max(6, $colSpan * 2); ?>; }
        }
        @media (min-width: 1200px) {
            .grid-item-col { grid-column: span <?php echo $colSpan; ?>; }
        }

        /* ── Plugin Grid (4xX) - Deprecated specific classes, use generic grid-item-col */
        .plugin-grid, .info-widgets-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
            margin-bottom: 3.5rem;
        }

        .plugin-card {
            background: var(--member-surface);
            border: 1px solid var(--member-border);
            border-radius: 8px;
            width: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .plugin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .plugin-card-header {
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .plugin-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .plugin-title {
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            font-size: 1rem;
        }
        
        .plugin-card-body {
            padding: 1.25rem;
            flex-grow: 1;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .plugin-card-footer {
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        .btn-primary-ghost {
            color: var(--member-primary);
            background: transparent;
            border: 1px solid transparent;
        }
        .btn-primary-ghost:hover {
            background: rgba(99, 102, 241, 0.1);
        }
        .btn-admin {
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .btn-admin:hover { color: #64748b; }
        
        /* Badges */
        .member-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.375rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: inherit;
        }
        .member-badge-active { background-color: #dcfce7; color: #166534; }
        .member-badge-pending { background-color: #fef9c3; color: #854d0e; }
        .member-badge-blocked { background-color: #fee2e2; color: #991b1b; }
        .member-badge-admin { background-color: #e0e7ff; color: #3730a3; }
        .member-badge-member { background-color: #f1f5f9; color: #475569; }

        /* ── Quick Start Bar ── */
        .quick-start-bar {
            background-color: var(--member-surface);
            border: 1px solid var(--member-border);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 2.5rem; /* Increased spacing to cards */
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .quick-start-title {
            font-weight: 600;
            color: var(--member-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .quick-actions-list {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .btn-quick {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-quick:hover {
            background-color: #fff;
            border-color: var(--member-primary);
            color: var(--member-primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transform: translateY(-1px);
        }

    </style>
</head>
<body class="member-body">
    
    <?php renderMemberSidebar('dashboard'); ?>
    
    <!-- Main Content -->
    <div class="member-content">
        
        <!-- Header Section -->
        <div class="member-page-header">
            <div class="member-hello" style="display:flex; align-items:center; gap:1.25rem;">
                <?php if (!empty($memberLogo)): ?>
                <img src="<?php echo htmlspecialchars($memberLogo); ?>" 
                     alt="Dashboard Logo" 
                     style="height:56px; width:auto; border-radius:6px; display:block;">
                <?php endif; ?>
                <div>
                    <h1>Willkommen, <?php echo htmlspecialchars($firstName); ?>!</h1>
                    <p>Dashboard &Uuml;bersicht</p>
                </div>
            </div>
            <div class="member-meta-info">
                <?php if ($isAdmin): ?>
                    <a href="<?php echo SITE_URL; ?>/admin" style="display:inline-block; margin-bottom:0.25rem; font-weight:600; text-decoration:none; color:var(--member-primary);">
                        &rarr; Zum Admin-Bereich
                    </a><br>
                <?php endif; ?>
                Letzter Login: <?php echo $dashboardData['last_login_formatted']; ?>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="member-alert member-alert-success">
                <span class="alert-icon">✓</span>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="member-alert member-alert-error">
                <span class="alert-icon">✕</span>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- ── Dynamic Section Order ── -->
        <?php 
        $sections = explode(',', $layoutOrder);
        foreach ($sections as $sectionKey):
            $sectionKey = trim($sectionKey);
            
            // ── QUICK START ──
            if ($sectionKey === 'quick_start'): ?>
                <div class="quick-start-bar">
                    <div class="quick-start-title">
                        <span style="font-size:1.25rem;">🚀</span> 
                        <span>Schnellstart</span>
                    </div>
                    <div class="quick-actions-list">
                        <a href="/member/profile" class="btn-quick">👤 Profil bearbeiten</a>
                        <a href="/member/security" class="btn-quick">🔒 Passwort & 2FA</a>
                        <?php if (class_exists('CMS_Experts_Database')): ?>
                            <a href="/experts" class="btn-quick">🔍 Experten suchen</a>
                        <?php endif; ?>
                        <a href="/member/messages" class="btn-quick">💬 Nachrichten</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php 
            // ── STATS ──
            if ($sectionKey === 'stats'): ?>
                <!-- Stats Grid - Uses dynamic column span -->
                <div class="member-stats-grid" style="grid-template-columns: repeat(12, 1fr);">
                    <!-- Card 1: Account / Status -->
                    <div class="grid-item-col">
                        <div class="stat-card" style="width:100%;">
                            <div class="stat-header">
                                <span class="stat-title">Mein Status</span>
                                <span class="member-badge member-badge-<?php echo $user->status; ?>">
                                    <?php echo ucfirst($user->status); ?>
                                </span>
                            </div>
                            <div class="stat-body">
                                <div class="stat-value"><?php echo ucfirst($user->role); ?></div>
                                <div class="stat-meta">Seit <?php echo date('d.m.Y', strtotime($user->created_at)); ?> dabei</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 2: Subscription -->
                    <div class="grid-item-col">
                        <div class="stat-card" style="width:100%;">
                            <div class="stat-header">
                                <span class="stat-title">Aktuelles Abo</span>
                                <span class="stat-icon">⭐</span>
                            </div>
                            <div class="stat-body">
                                <?php if ($dashboardData['subscription']): ?>
                                    <div class="stat-value"><?php echo htmlspecialchars($dashboardData['subscription']->package_name); ?></div>
                                    <div class="stat-meta"><a href="/member/subscription" style="text-decoration:none; color:inherit;">Details ansehen &rarr;</a></div>
                                <?php else: ?>
                                    <div class="stat-value" style="font-size:1.5rem; color:#94a3b8;">Kein Abo</div>
                                    <div class="stat-meta"><a href="/member/subscription" style="text-decoration:none; color:var(--member-primary);">Jetzt upgraden &rarr;</a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 3: Activity Stats -->
                    <div class="grid-item-col">
                        <div class="stat-card" style="width:100%;">
                            <div class="stat-header">
                                <span class="stat-title">Aktivität (30d)</span>
                                <span class="stat-icon">📊</span>
                            </div>
                            <div class="stat-body">
                                <div class="stat-value"><?php echo number_format((int)($dashboardData['login_count_30d'] ?? 0)); ?></div>
                                <div class="stat-meta">Logins</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card 4: Security -->
                    <div class="grid-item-col">
                        <div class="stat-card" style="width:100%;">
                            <div class="stat-header">
                                <span class="stat-title">Sicherheit</span>
                                <span class="stat-icon">🔒</span>
                            </div>
                            <div class="stat-body">
                                <?php if ($dashboardData['two_factor_enabled'] ?? false): ?>
                                    <div class="stat-value" style="color:var(--member-success, #16a34a);">Geschützt</div>
                                    <div class="stat-meta">2FA Aktiv</div>
                                <?php else: ?>
                                    <div class="stat-value" style="color:var(--member-warning, #ca8a04);">Warnung</div>
                                    <div class="stat-meta"><a href="/member/security" style="color:inherit;">2FA jetzt aktivieren</a></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php 
            // ── INFO WIDGETS ──
            if ($sectionKey === 'widgets' && !empty($customWidgets)): ?>
                <div class="section-header">
                    <h2>Informationen</h2>
                </div>
                <!-- Reuse plugin-grid class for layout consistency -->
                <div class="plugin-grid">
                    <?php foreach ($customWidgets as $cw): ?>
                    <div class="grid-item-col">
                        <div class="plugin-card">
                            <div class="plugin-card-header">
                                <div class="plugin-icon-box" style="background:var(--member-secondary); color:#fff; font-size:1.2rem;">
                                    <?php echo htmlspecialchars($cw['icon']); ?>
                                </div>
                                <h3 class="plugin-title"><?php echo htmlspecialchars($cw['title']); ?></h3>
                            </div>
                            <div class="plugin-card-body">
                                <div class="info-widget-content">
                                    <?php echo nl2br(strip_tags(html_entity_decode($cw['content']), '<a><strong><em><br>')); ?>
                                </div>
                            </div>
                            <?php if (!empty($cw['link']) && !empty($cw['btntext'])): ?>
                            <div class="plugin-card-footer">
                                <a href="<?php echo htmlspecialchars($cw['link']); ?>" class="btn-sm btn-primary-ghost">
                                    <?php echo htmlspecialchars($cw['btntext']); ?> &rarr;
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php 
            // ── PLUGINS ──
            if ($sectionKey === 'plugins'): 
                $allPluginWidgets = array_merge($featureWidgets, $registryPluginWidgets);
                // Also add static quick links to this array or render them after
                ?>
                <div class="section-header">
                    <h2>Bereiche & Tools</h2>
                </div>
                <div class="plugin-grid">
                    <?php foreach ($allPluginWidgets as $pw): 
                        $bg = $pw['color'] ?? '#4f46e5';
                        $icon = $pw['icon'] ?? '🧩';
                        $adminLink = $pw['admin_link'] ?? null;
                        $adminLabel= $pw['admin_label'] ?? 'Admin';
                    ?>
                    <div class="grid-item-col">
                        <div class="plugin-card">
                            <div class="plugin-card-header">
                                <div class="plugin-icon-box" style="background:<?php echo $bg; ?>15; color:<?php echo $bg; ?>;">
                                    <?php echo $icon; ?>
                                </div>
                                <h3 class="plugin-title"><?php echo htmlspecialchars($pw['title']); ?></h3>
                            </div>
                            <div class="plugin-card-body">
                                <?php if (!empty($pw['text']) || !empty($pw['description'])): ?>
                                    <p><?php echo htmlspecialchars(mb_strimwidth(strip_tags($pw['text'] ?? $pw['description']), 0, 120, '...')); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($pw['stats'])): ?>
                                <div style="margin-top:1rem; padding-top:1rem; border-top:1px dashed #e2e8f0; font-size:0.8rem;">
                                    <strong style="font-size:1.1rem; color:#1e293b;"><?php echo (int)$pw['stats']['count']; ?></strong> 
                                    <?php echo htmlspecialchars($pw['stats']['label']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="plugin-card-footer">
                                <?php if (!empty($pw['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($pw['link']); ?>" class="btn-sm btn-primary-ghost">Öffnen &rarr;</a>
                                <?php else: ?>
                                    <span></span>
                                <?php endif; ?>
                                <?php if (!empty($adminLink)): ?>
                                    <a href="<?php echo htmlspecialchars($adminLink); ?>" class="btn-sm btn-admin" title="<?php echo htmlspecialchars($adminLabel); ?>">⚙️</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Static Quick Link: Notifications -->
                    <div class="grid-item-col">
                        <div class="plugin-card">
                            <div class="plugin-card-header">
                                <div class="plugin-icon-box" style="background:#f59e0b15; color:#f59e0b;">🔔</div>
                                <h3 class="plugin-title">Benachrichtigungen</h3>
                            </div>
                            <div class="plugin-card-body">Einstellungen und Historie deiner System-Meldungen.</div>
                            <div class="plugin-card-footer">
                                <a href="/member/notifications" class="btn-sm btn-primary-ghost">Öffnen &rarr;</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Static Quick Link: Settings -->
                    <div class="grid-item-col">
                        <div class="plugin-card">
                            <div class="plugin-card-header">
                                <div class="plugin-icon-box" style="background:#64748b15; color:#64748b;">⚙️</div>
                                <h3 class="plugin-title">Einstellungen</h3>
                            </div>
                            <div class="plugin-card-body">Privatsphäre, Passwort und Profileinstellungen.</div>
                            <div class="plugin-card-footer">
                                <a href="/member/profile" class="btn-sm btn-primary-ghost">Bearbeiten &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>

    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.member-alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
    
</body>
</html>
