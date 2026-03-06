<?php
/**
 * Admin Dashboard (modular)
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config.php';
require_once CORE_PATH . 'autoload.php';
require_once ABSPATH . 'includes/functions.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\DashboardService;
use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$user = $auth->getCurrentUser();
$dashboardService = DashboardService::getInstance();
$stats = $dashboardService->getAllStats();
$activityFeed = $dashboardService->getActivityFeed(8);
$recentOrders = $dashboardService->getRecentOrders(5);
$attentionItems = $dashboardService->getAttentionItems();

$csrfToken = Security::instance()->generateToken('admin_dashboard');

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

require_once dirname(__DIR__, 2) . '/partials/admin-menu.php';
?>
<?php renderAdminLayoutStart('Admin Dashboard', 'dashboard'); ?>

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

<?php renderAdminAlerts(); ?>

<div class="data-grid">
    <div class="data-box">
        <div class="data-label">Heute aktiv</div>
        <div class="data-value"><?php echo number_format((int)($stats['users']['active_today'] ?? 0)); ?></div>
    </div>
    <div class="data-box">
        <div class="data-label">Neue Benutzer heute</div>
        <div class="data-value"><?php echo number_format((int)($stats['users']['new_today'] ?? 0)); ?></div>
    </div>
    <div class="data-box">
        <div class="data-label">Offene Bestellungen</div>
        <div class="data-value"><?php echo number_format((int)($stats['orders']['pending'] ?? 0)); ?></div>
    </div>
    <div class="data-box">
        <div class="data-label">Umsatz 30 Tage</div>
        <div class="data-value" style="font-size:1.25rem;"><?php echo htmlspecialchars((string)($stats['orders']['month_revenue_formatted'] ?? '0,00 EUR')); ?></div>
    </div>
</div>

<div class="row row-deck row-cards mb-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="subheader">Benutzer</div>
            <div class="h1 mb-1"><?php echo number_format($stats['users']['total']); ?></div>
            <div class="text-secondary"><?php echo number_format($stats['users']['active_today']); ?> heute aktiv</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="subheader">Seiten</div>
            <div class="h1 mb-1"><?php echo number_format($stats['pages']['total']); ?></div>
            <div class="text-secondary"><?php echo number_format($stats['pages']['published']); ?> veröffentlicht</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="subheader">Medien</div>
            <div class="h1 mb-1"><?php echo number_format($stats['media']['total']); ?></div>
            <div class="text-secondary"><?php echo $stats['media']['total_size_mb']; ?> MB gesamt</div>
        </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card"><div class="card-body">
            <div class="subheader">Sessions</div>
            <div class="h1 mb-1"><?php echo number_format($stats['sessions']['active']); ?></div>
            <div class="text-secondary"><?php echo number_format($stats['sessions']['total']); ?> gesamt</div>
        </div></div>
    </div>
</div>

<div class="row row-deck row-cards mb-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">⚡ Schnellzugriff</h3></div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action" href="<?php echo SITE_URL; ?>/admin/posts">📝 Neuen Beitrag erstellen</a>
                    <a class="list-group-item list-group-item-action" href="<?php echo SITE_URL; ?>/admin/media">🖼️ Medien verwalten</a>
                    <a class="list-group-item list-group-item-action" href="<?php echo SITE_URL; ?>/admin/users">👤 Benutzer verwalten</a>
                    <a class="list-group-item list-group-item-action" href="<?php echo SITE_URL; ?>/admin/system">🧰 System & Diagnose</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">🛎️ Aufmerksamkeit erforderlich</h3></div>
            <div class="card-body">
                <?php if (!empty($attentionItems)): ?>
                    <div class="status-list">
                        <?php foreach ($attentionItems as $item): ?>
                            <div class="info-list-item">
                                <div>
                                    <strong><?php echo htmlspecialchars((string)$item['icon']); ?> <?php echo htmlspecialchars((string)$item['label']); ?></strong><br>
                                    <span class="text-secondary"><?php echo htmlspecialchars((string)$item['hint']); ?></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <span class="badge bg-<?php echo htmlspecialchars((string)$item['type']); ?>-lt"><?php echo htmlspecialchars((string)$item['value']); ?></span>
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars((string)$item['url']); ?>">Öffnen</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding:1rem 0;">
                        <span class="empty-state-icon">✅</span>
                        <p style="margin:0;color:#64748b;">Aktuell gibt es keine dringenden Aufgaben.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">💻 System-Status</h3></div>
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

<div class="row row-deck row-cards mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">🕒 Letzte Aktivitäten</h3></div>
            <div class="card-body">
                <div class="activity-log">
                    <?php if (!empty($activityFeed)): ?>
                        <?php foreach ($activityFeed as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">📌</div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars((string)($activity->action ?? 'Aktivität')); ?></strong>
                                    <?php if (!empty($activity->username)): ?>
                                        <div class="text-secondary"><?php echo htmlspecialchars((string)$activity->username); ?></div>
                                    <?php endif; ?>
                                    <div class="activity-time" title="<?php echo !empty($activity->created_at) ? date('d.m.Y H:i', strtotime((string)$activity->created_at)) : ''; ?>">
                                        <?php echo !empty($activity->created_at) ? htmlspecialchars(time_ago((string)$activity->created_at)) : 'unbekannt'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding:1rem 0;">
                            <span class="empty-state-icon">📭</span>
                            <p style="margin:0;color:#64748b;">Noch keine Aktivitätsdaten vorhanden.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">🧾 Letzte Bestellungen</h3></div>
            <div class="card-body">
                <?php if (!empty($recentOrders)): ?>
                    <div class="status-list">
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="info-list-item">
                                <div>
                                    <strong>#<?php echo htmlspecialchars((string)($order->order_number ?? '—')); ?></strong><br>
                                    <span class="text-secondary"><?php echo htmlspecialchars((string)($order->customer_name ?? 'Gast')); ?></span>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-weight:600;color:#1e293b;"><?php echo number_format((float)($order->total_amount ?? 0), 2, ',', '.'); ?> <?php echo htmlspecialchars((string)($order->currency ?? 'EUR')); ?></div>
                                    <div class="activity-time" title="<?php echo !empty($order->created_at) ? date('d.m.Y H:i', strtotime((string)$order->created_at)) : ''; ?>">
                                        <?php echo !empty($order->created_at) ? htmlspecialchars(time_ago((string)$order->created_at)) : 'unbekannt'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo SITE_URL; ?>/admin/orders">Alle Bestellungen</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="padding:1rem 0;">
                        <span class="empty-state-icon">🧾</span>
                        <p style="margin:0;color:#64748b;">Noch keine Bestellungen vorhanden.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php Hooks::doAction('admin_dashboard_widgets'); ?>

<?php renderAdminLayoutEnd(); ?>
