<?php
/**
 * Analytics Admin Page
 * 
 * Besucher-Statistiken und Traffic-Analyse (OHNE Updates - die sind jetzt unter /admin/updates)
 * 
 * @package CMSv2\Admin
 * @version 2.0.1
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\AnalyticsService;
use CMS\Services\TrackingService;

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
$security = Security::instance();

// Initialize services
$analytics = AnalyticsService::getInstance();
$tracking = TrackingService::getInstance();

// Get current tab
$activeTab = $_GET['tab'] ?? 'overview';

// Get analytics data with error handling
try {
    $visitorStats = $analytics->getVisitorStats(30) ?? [
        'total' => 0,
        'unique' => 0,
        'active_now' => 0,
        'bounce_rate' => 0,
        'avg_duration' => 0
    ];
    
    $topPages = $tracking->getTopPages(30, 10) ?? [];
    $pageViews = $tracking->getPageViewsByDate(30) ?? [];
    $recentActivity = $analytics->getRecentActivity(20) ?? [];
    
} catch (Exception $e) {
    error_log("Analytics Error: " . $e->getMessage());
    $visitorStats = [
        'total' => 0,
        'unique' => 0,
        'active_now' => 0,
        'bounce_rate' => 0,
        'avg_duration' => 0
    ];
    $topPages = [];
    $pageViews = [];
    $recentActivity = [];
}

// Generate CSRF token
$csrfToken = $security->generateToken('analytics');

// Determine current page for menu
$currentPage = 'analytics';

// Load admin menu functions
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .analytics-tabs {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 2rem;
        }
        
        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #64748b;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .tab-button:hover {
            color: #3b82f6;
        }
        
        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3b82f6;
        }
        
        .metric-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .metric-change {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .metric-change.positive {
            color: #10b981;
        }
        
        .metric-change.negative {
            color: #ef4444;
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .page-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .page-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .page-item:last-child {
            border-bottom: none;
        }
        
        .activity-log {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-time {
            color: #94a3b8;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar($currentPage); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <h1>📊 Analytics & Monitoring</h1>
            <p>Besucher-Statistiken und Traffic-Analyse</p>
        </div>
        
        <!-- Tabs -->
        <div class="analytics-tabs">
            <a href="?tab=overview" class="tab-button <?php echo $activeTab === 'overview' ? 'active' : ''; ?>">
                📊 Übersicht
            </a>
            <a href="?tab=visitors" class="tab-button <?php echo $activeTab === 'visitors' ? 'active' : ''; ?>">
                👥 Besucher
            </a>
            <a href="?tab=pages" class="tab-button <?php echo $activeTab === 'pages' ? 'active' : ''; ?>">
                📄 Seiten
            </a>
            <a href="?tab=sources" class="tab-button <?php echo $activeTab === 'sources' ? 'active' : ''; ?>">
                🔗 Traffic-Quellen
            </a>
        </div>
        
        <?php if ($activeTab === 'overview'): ?>
            <!-- Overview Tab -->
            <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">👥</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['total'] ?? 0)); ?></div>
                        <div class="metric-label">Seitenaufrufe (30 Tage)</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">👤</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['unique'] ?? 0)); ?></div>
                        <div class="metric-label">Eindeutige Besucher</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">⚡</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['active_now'] ?? 0)); ?></div>
                        <div class="metric-label">Aktive Besucher</div>
                        <div class="metric-change">Aktuell online</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">📈</div>
                        <div class="metric-value"><?php echo number_format((float)($visitorStats['bounce_rate'] ?? 0), 1); ?>%</div>
                        <div class="metric-label">Absprungrate</div>
                    </div>
                </div>
                
                <!-- Chart Placeholder -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 1rem;">📈 Besucher-Verlauf (30 Tage)</h3>
                    <div style="height: 300px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 8px;">
                        <p style="color: #94a3b8;">Chart wird hier angezeigt (Chart.js Integration)</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <!-- Top Pages -->
                    <div>
                        <h3 style="margin-bottom: 1rem;">📄 Beliebteste Seiten</h3>
                        <div class="page-list">
                            <?php if (!empty($topPages)): ?>
                                <?php foreach ($topPages as $page): ?>
                                    <div class="page-item">
                                        <div>
                                            <strong><?php echo htmlspecialchars($page['page_title'] ?? 'Unbekannt'); ?></strong>
                                            <div style="color: #64748b; font-size: 0.875rem;">
                                                /<?php echo htmlspecialchars($page['page_slug'] ?? ''); ?>
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-weight: 600; color: #3b82f6;">
                                                <?php echo number_format((int)($page['views'] ?? 0)); ?> Aufrufe
                                            </div>
                                            <div style="color: #64748b; font-size: 0.875rem;">
                                                <?php echo number_format((int)($page['unique_visitors'] ?? 0)); ?> eindeutig
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📄</div>
                                    <p>Noch keine Seitenaufrufe vorhanden</p>
                                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">Tracking beginnt automatisch mit dem ersten Besuch</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div>
                        <h3 style="margin-bottom: 1rem;">📊 Letzte Aktivitäten</h3>
                        <div class="activity-log">
                            <?php if (!empty($recentActivity)): ?>
                                <?php foreach ($recentActivity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php 
                                            $icons = [
                                                'login' => '🔐',
                                                'logout' => '🚪',
                                                'page_view' => '👁️',
                                                'edit' => '✏️',
                                                'create' => '➕',
                                                'delete' => '🗑️'
                                            ];
                                            echo $icons[$activity['action'] ?? 'page_view'] ?? '📝';
                                            ?>
                                        </div>
                                        <div class="activity-content">
                                            <div style="font-weight: 500;">
                                                <?php echo htmlspecialchars($activity['description'] ?? $activity['action'] ?? 'Aktivität'); ?>
                                            </div>
                                            <div style="color: #64748b; font-size: 0.875rem;">
                                                <?php echo htmlspecialchars($activity['username'] ?? 'Unbekannt'); ?>
                                            </div>
                                        </div>
                                        <div class="activity-time">
                                            <?php 
                                            $time = strtotime($activity['created_at'] ?? 'now');
                                            $diff = time() - $time;
                                            
                                            if ($diff < 60) {
                                                echo 'Gerade eben';
                                            } elseif ($diff < 3600) {
                                                echo floor($diff / 60) . ' Min. her';
                                            } elseif ($diff < 86400) {
                                                echo floor($diff / 3600) . ' Std. her';
                                            } else {
                                                echo date('d.m.Y H:i', $time);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">📊</div>
                                    <p>Noch keine Aktivitäten vorhanden</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($activeTab === 'visitors'): ?>
                <!-- Visitors Tab -->
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-icon">👥</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['total'] ?? 0)); ?></div>
                        <div class="metric-label">Gesamt-Aufrufe</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">👤</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['unique'] ?? 0)); ?></div>
                        <div class="metric-label">Eindeutige Besucher</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">⏱️</div>
                        <div class="metric-value"><?php echo number_format((int)($visitorStats['avg_duration'] ?? 0)); ?>s</div>
                        <div class="metric-label">Durchschnittliche Sitzungsdauer</div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="metric-icon">📉</div>
                        <div class="metric-value"><?php echo number_format((float)($visitorStats['bounce_rate'] ?? 0), 1); ?>%</div>
                        <div class="metric-label">Absprungrate</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h3 style="margin-bottom: 1rem;">📈 Besucher-Trend</h3>
                    <div style="height: 400px; display: flex; align-items: center; justify-content: center; background: #f8fafc; border-radius: 8px;">
                        <p style="color: #94a3b8;">Detaillierter Trend-Chart (Chart.js)</p>
                    </div>
                </div>
                
            <?php elseif ($activeTab === 'pages'): ?>
                <!-- Pages Tab -->
                <h3 style="margin-bottom: 1rem;">📄 Seiten-Performance</h3>
                <div class="page-list">
                    <?php if (!empty($topPages)): ?>
                        <?php foreach ($topPages as $index => $page): ?>
                            <div class="page-item">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #cbd5e1; min-width: 40px;">
                                        #<?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($page['page_title'] ?? 'Unbekannt'); ?></strong>
                                        <div style="color: #64748b; font-size: 0.875rem;">
                                            /<?php echo htmlspecialchars($page['page_slug'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; text-align: right;">
                                    <div>
                                        <div style="font-weight: 600; color: #3b82f6;">
                                            <?php echo number_format((int)($page['views'] ?? 0)); ?>
                                        </div>
                                        <div style="color: #64748b; font-size: 0.875rem;">Aufrufe</div>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #10b981;">
                                            <?php echo number_format((int)($page['unique_visitors'] ?? 0)); ?>
                                        </div>
                                        <div style="color: #64748b; font-size: 0.875rem;">Eindeutig</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📄</div>
                            <p>Noch keine Seitenaufrufe vorhanden</p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($activeTab === 'sources'): ?>
                <!-- Traffic Sources Tab -->
                <div class="empty-state" style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <div class="empty-state-icon">🔗</div>
                    <h3>Traffic-Quellen Analyse</h3>
                    <p style="margin-top: 1rem;">Diese Funktion zeigt woher Ihre Besucher kommen</p>
                    <p style="font-size: 0.875rem; color: #94a3b8; margin-top: 0.5rem;">
                        Referrer-Tracking ist aktiviert und sammelt Daten
                    </p>
                </div>
                
            <?php endif; ?>
    </div>
</body>
</html>
