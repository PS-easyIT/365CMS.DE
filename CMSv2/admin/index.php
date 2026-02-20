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
    <title>Admin Dashboard - <?php echo htmlspecialchars(SITE_NAME); ?></title>
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
    
    <?php renderAdminSidebar('dashboard'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>Dashboard</h2>
            <div class="admin-user">
                <span>Willkommen, <strong><?php echo htmlspecialchars($user->username); ?></strong></span>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
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
            
            <!-- Users Stats -->
            <div class="stat-card">
                <h3>Benutzer</h3>
                <div class="stat-number"><?php echo number_format($stats['users']['total']); ?></div>
                <p class="stat-label">
                    <?php echo number_format($stats['users']['active_today']); ?> heute aktiv
                </p>
            </div>
            
            <!-- Pages Stats -->
            <div class="stat-card">
                <h3>Seiten</h3>
                <div class="stat-number"><?php echo number_format($stats['pages']['total']); ?></div>
                <p class="stat-label">
                    <?php echo number_format($stats['pages']['published']); ?> veröffentlicht
                </p>
            </div>
            
            <!-- Media Stats -->
            <div class="stat-card">
                <h3>Medien</h3>
                <div class="stat-number"><?php echo number_format($stats['media']['total']); ?></div>
                <p class="stat-label">
                    <?php echo $stats['media']['total_size_mb']; ?> MB gesamt
                </p>
            </div>
            
            <!-- Sessions Stats -->
            <div class="stat-card">
                <h3>Aktive Sessions</h3>
                <div class="stat-number"><?php echo number_format($stats['sessions']['active']); ?></div>
                <p class="stat-label">
                    <?php echo number_format($stats['sessions']['total']); ?> gesamt
                </p>
            </div>
            
        </div>
        
        <!-- System Information -->
        <div class="admin-section">
            <h3>System-Informationen</h3>
            
            <div class="info-grid">
                <div class="info-card">
                    <h4>🖥️ Server</h4>
                    <ul class="info-list">
                        <li><strong>PHP Version:</strong> <?php echo $stats['system']['php_version']; ?></li>
                        <li><strong>Server Software:</strong> <?php echo htmlspecialchars($stats['system']['server_software']); ?></li>
                        <li><strong>Betriebssystem:</strong> <?php echo htmlspecialchars($stats['system']['os']); ?></li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h4>📊 Performance</h4>
                    <ul class="info-list">
                        <li><strong>Memory Usage:</strong> <?php echo $stats['performance']['memory_usage_formatted']; ?></li>
                        <li><strong>Memory Limit:</strong> <?php echo $stats['performance']['memory_limit']; ?></li>
                        <li><strong>Peak Memory:</strong> <?php echo $stats['performance']['memory_peak_formatted']; ?></li>
                    </ul>
                </div>
                
                <div class="info-card">
                    <h4>🔒 Sicherheit</h4>
                    <ul class="info-list">
                        <li><strong>Failed Logins (24h):</strong> <?php echo number_format($stats['security']['failed_logins_24h']); ?></li>
                        <li><strong>Blocked IPs:</strong> <?php echo number_format($stats['security']['blocked_ips']); ?></li>
                        <li><strong>HTTPS:</strong> <?php echo $stats['security']['https_enabled'] ? '✅ Aktiv' : '❌ Inaktiv'; ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3>Schnellzugriff</h3>
            <div class="action-buttons">
                <a href="/admin/pages" class="btn btn-primary">
                    📄 Neue Seite erstellen
                </a>
                <a href="/admin/users" class="btn btn-secondary">
                    👥 Benutzer verwalten
                </a>
                <a href="/admin/settings" class="btn btn-secondary">
                    ⚙️ Einstellungen
                </a>
            </div>
        </div>
        
        <!-- Plugin Hooks for Dashboard Widgets -->
        <?php Hooks::doAction('admin_dashboard_widgets'); ?>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    
</body>
</html>
