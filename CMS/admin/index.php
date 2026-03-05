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
<?php renderAdminLayoutStart('Admin Dashboard', 'dashboard'); ?>
        
        <!-- Page Header -->
        <div class="page-header d-print-none mb-3">
            <div class="container-xl">
                <div class="page-pretitle">Übersicht</div>
                <h2 class="page-title">
                    🛠️ Dashboard
                    <span class="badge bg-primary ms-2" style="font-size:0.65rem; vertical-align:middle;">v<?php echo htmlspecialchars(CMS_VERSION); ?></span>
                </h2>
                <div class="text-secondary mt-1">Willkommen zurück, <strong><?php echo htmlspecialchars($user->username); ?></strong>.</div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>✅ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <div class="d-flex">
                    <div>❌ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                </div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>
        
        <!-- Dashboard Statistics -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Benutzer</div>
                        <div class="h1 mb-1"><?php echo number_format($stats['users']['total']); ?></div>
                        <div class="text-secondary"><?php echo number_format($stats['users']['active_today']); ?> heute aktiv</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Seiten</div>
                        <div class="h1 mb-1"><?php echo number_format($stats['pages']['total']); ?></div>
                        <div class="text-secondary"><?php echo number_format($stats['pages']['published']); ?> veröffentlicht</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Medien</div>
                        <div class="h1 mb-1"><?php echo number_format($stats['media']['total']); ?></div>
                        <div class="text-secondary"><?php echo $stats['media']['total_size_mb']; ?> MB gesamt</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Sessions</div>
                        <div class="h1 mb-1"><?php echo number_format($stats['sessions']['active']); ?></div>
                        <div class="text-secondary"><?php echo number_format($stats['sessions']['total']); ?> gesamt</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">💻 System-Status</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h4 class="subheader">Server</h4>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item"><strong>PHP Version:</strong> <?php echo $stats['system']['php_version']; ?></div>
                            <div class="list-group-item"><strong>Server:</strong> <?php echo htmlspecialchars($stats['system']['server_software']); ?></div>
                            <div class="list-group-item"><strong>OS:</strong> <?php echo htmlspecialchars($stats['system']['os']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="subheader">Performance</h4>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item"><strong>RAM Nutzung:</strong> <?php echo $stats['performance']['memory_usage_formatted']; ?></div>
                            <div class="list-group-item"><strong>RAM Limit:</strong> <?php echo $stats['performance']['memory_limit']; ?></div>
                            <div class="list-group-item"><strong>Peak:</strong> <?php echo $stats['performance']['memory_peak_formatted']; ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h4 class="subheader">Sicherheit</h4>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item"><strong>Failed Logins (24h):</strong> <?php echo number_format($stats['security']['failed_logins_24h']); ?></div>
                            <div class="list-group-item"><strong>Blocked IPs:</strong> <?php echo number_format($stats['security']['blocked_ips']); ?></div>
                            <div class="list-group-item"><strong>HTTPS:</strong> <?php echo $stats['security']['https_enabled'] ? '✅ Aktiv' : '❌ Inaktiv'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Links -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔗 Links &amp; Ressourcen</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item">
                        <strong>GitHub Repository:</strong>
                        <a href="https://github.com/PS-easyIT/365CMS.DE" target="_blank" rel="noopener noreferrer">github.com/PS-easyIT/365CMS.DE</a>
                    </div>
                    <div class="list-group-item">
                        <strong>365CMS Website:</strong>
                        <a href="https://365cms.de" target="_blank" rel="noopener noreferrer">365cms.de</a>
                    </div>
                    <div class="list-group-item">
                        <strong>PHiNiT Website:</strong>
                        <a href="https://phinit.de" target="_blank" rel="noopener noreferrer">phinit.de</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php Hooks::doAction('admin_dashboard_widgets'); ?>
        
<?php renderAdminLayoutEnd(); ?>
