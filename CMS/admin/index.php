<?php
/**
 * Admin Dashboard
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\DashboardService;
use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$user = $auth->getCurrentUser();
$dashboardService = DashboardService::getInstance();
$stats = $dashboardService->getAllStats();

// Generate CSRF token
$csrfToken = Security::instance()->generateToken('admin_dashboard');

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php 
    if (class_exists('CMS\Hooks')) {
        CMS\Hooks::doAction('head');
        CMS\Hooks::doAction('admin_head');
    }
    renderAdminSidebarStyles(); 
    ?>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('dashboard'); ?>
    
    <div class="admin-content">
        
        <div class="admin-page-header">
            <div>
                <h2>🚀 Dashboard <span style="display:inline-block; margin-left:0.5rem; padding:0.2rem 0.6rem; background:var(--admin-primary); color:#fff; border-radius:99px; font-size:0.7rem; vertical-align:middle; font-weight:700;">v<?php echo htmlspecialchars(CMS_VERSION); ?></span></h2>
                <p>Willkommen zurück, <strong><?php echo htmlspecialchars($user->username); ?></strong>.</p>
            </div>
            <div class="header-actions">
                <a href="pages.php?action=new" class="btn btn-primary">➕ Neue Seite</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['success']); 
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Statistics -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>Benutzer</h3>
                <div class="stat-number"><?php echo number_format($stats['users']['total']); ?></div>
                <div class="stat-label"><?php echo number_format($stats['users']['active_today']); ?> heute aktiv</div>
            </div>
            
            <div class="stat-card">
                <h3>Seiten</h3>
                <div class="stat-number"><?php echo number_format($stats['pages']['total']); ?></div>
                <div class="stat-label"><?php echo number_format($stats['pages']['published']); ?> veröffentlicht</div>
            </div>
            
            <div class="stat-card">
                <h3>Medien</h3>
                <div class="stat-number"><?php echo number_format($stats['media']['total']); ?></div>
                <div class="stat-label"><?php echo $stats['media']['total_size_mb']; ?> MB gesamt</div>
            </div>
            
            <div class="stat-card">
                <h3>Sessions</h3>
                <div class="stat-number"><?php echo number_format($stats['sessions']['active']); ?></div>
                <div class="stat-label"><?php echo number_format($stats['sessions']['total']); ?> gesamt</div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="admin-card" style="margin-top:2rem;">
            <h3>💻 System-Status</h3>
            
            <div class="info-grid">
                <div class="info-card">
                    <h4>Server</h4>
                    <ul class="info-list">
                        <li><strong>PHP Version:</strong> <?php echo $stats['system']['php_version']; ?></li>
                        <li><strong>Server:</strong> <?php echo htmlspecialchars($stats['system']['server_software']); ?></li>
                        <li><strong>OS:</strong> <?php echo htmlspecialchars($stats['system']['os']); ?></li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h4>Performance</h4>
                    <ul class="info-list">
                        <li><strong>RAM Nutzung:</strong> <?php echo $stats['performance']['memory_usage_formatted']; ?></li>
                        <li><strong>RAM Limit:</strong> <?php echo $stats['performance']['memory_limit']; ?></li>
                        <li><strong>Peak:</strong> <?php echo $stats['performance']['memory_peak_formatted']; ?></li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h4>Sicherheit</h4>
                    <ul class="info-list">
                        <li><strong>Failed Logins (24h):</strong> <?php echo number_format($stats['security']['failed_logins_24h']); ?></li>
                        <li><strong>Blocked IPs:</strong> <?php echo number_format($stats['security']['blocked_ips']); ?></li>
                        <li><strong>HTTPS:</strong> <?php echo $stats['security']['https_enabled'] ? '✅ Aktiv' : '❌ Inaktiv'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-card">
            <h3>⚡ Schnellzugriff</h3>
            <div class="form-actions" style="margin-top:1rem;">
                <a href="users.php" class="btn btn-secondary">
                    👥 Benutzer verwalten
                </a>
                <a href="settings.php" class="btn btn-secondary">
                    ⚙️ Einstellungen
                </a>
                <a href="media.php" class="btn btn-secondary">
                    📁 Medien
                </a>
                <a href="updates.php" class="btn btn-secondary">
                    🔄 Updates prüfen
                </a>
            </div>
        </div>
        
        <?php Hooks::doAction('admin_dashboard_widgets'); ?>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>
