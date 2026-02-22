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
$db = \CMS\Database::instance();
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
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260222b">
    <?php renderAdminSidebarStyles(); ?>
    <?php renderAdminSidebarStyles(); ?>
</head>
<body class="admin-body">
    <?php renderAdminSidebar($currentPage); ?>
    
    <div class="admin-content">
        <div class="admin-page-header">
            <?php 
                $pageTitle = '📊 Analytics & Monitoring';
                $pageSub = 'Besucher-Statistiken und Traffic-Analyse';
                
                if ($activeTab === '404-monitor') {
                    $pageTitle = '🚫 404 Monitor';
                    $pageSub = 'Fehlerhafte Aufrufe und fehlende Seiten';
                } elseif ($activeTab === 'seo-analyzer') {
                    $pageTitle = '📑 SEO Analyse';
                    $pageSub = 'Technische Prüfung und Optimierung';
                }
            ?>
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p><?php echo htmlspecialchars($pageSub); ?></p>
        </div>
        
        <?php if ($activeTab === 'overview' || $activeTab === 'visitors' || $activeTab === 'pages' || $activeTab === 'sources'): ?>
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
                        <div class="metric-change" style="color:#10b981;">● Online</div>
                    </div>
                   
                    <div class="metric-card">
                        <div class="metric-icon">📈</div>
                        <div class="metric-value"><?php echo number_format((float)($visitorStats['bounce_rate'] ?? 0), 1); ?>%</div>
                        <div class="metric-label">Absprungrate</div>
                    </div>
                </div>

                <!-- CSS Only Chart -->
                <div class="chart-container">
                    <h3 style="margin-bottom: 1.5rem;">📈 Besucher-Verlauf (Letzte 30 Tage)</h3>
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 300px; gap: 4px; padding-bottom: 2rem;">
                        <?php 
                        $maxViews = 1;
                        foreach ($pageViews as $views) {
                            if ($views > $maxViews) $maxViews = $views;
                        }
                        
                        foreach ($pageViews as $date => $views): 
                            $height = max(4, round(($views / $maxViews) * 100));
                            $day = date('d', strtotime($date));
                        ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; group: date-col;">
                            <div style="width: 100%; text-align: center; font-size: 0.7rem; color: #64748b; margin-bottom: 4px; opacity: 0;" title="<?php echo $views; ?> Aufrufe"><?php echo $views; ?></div>
                            <div style="width: 100%; border-radius: 4px 4px 0 0; background: #3b82f6; height: <?php echo $height; ?>%; transition: height 0.3s;" title="<?php echo $date; ?>: <?php echo $views; ?> Aufrufe"></div>
                            <div style="margin-top: 8px; font-size: 0.7rem; color: #94a3b8;"><?php echo $day; ?></div>
                        </div>
                        <?php endforeach; ?>
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
                    <h3 style="margin-bottom: 1.5rem;">📈 Besucher-Trend (Letzte 30 Tage)</h3>
                    <!-- Reusing chart logic -->
                    <div style="display: flex; align-items: flex-end; justify-content: space-between; height: 300px; gap: 4px; padding-bottom: 2rem;">
                         <?php 
                        $maxViews = 1;
                        if (!empty($pageViews)) {
                            foreach ($pageViews as $views) {
                                if ($views > $maxViews) $maxViews = $views;
                            }
                        }
                        
                        foreach ($pageViews as $date => $views): 
                            $height = max(4, round(($views / $maxViews) * 100));
                            $day = date('d', strtotime($date));
                        ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; group: date-col;">
                            <div style="width: 100%; border-radius: 4px 4px 0 0; background: #6366f1; height: <?php echo $height; ?>%; transition: height 0.3s;" title="<?php echo $date; ?>: <?php echo $views; ?> Aufrufe"></div>
                            <div style="margin-top: 8px; font-size: 0.7rem; color: #94a3b8;"><?php echo $day; ?></div>
                        </div>
                        <?php endforeach; ?>
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
                            <p style="font-size: 0.875rem; margin-top: 0.5rem; color: #94a3b8;">Tracking beginnt automatisch</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($activeTab === 'sources'): ?>
                <!-- Traffic Sources Tab -->
                <h3 style="margin-bottom: 1rem;">🔗 Traffic Quellen (Top 10)</h3>
                <div class="page-list">
                    <?php 
                    // Direct DB Query for Referrers until added to Service
                    $db = \CMS\Database::instance();
                    $stmt = $db->prepare("
                        SELECT referrer, COUNT(*) as count 
                        FROM {$db->getPrefix()}page_views 
                        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                        AND referrer != '' 
                        AND referrer NOT LIKE ?
                        GROUP BY referrer 
                        ORDER BY count DESC 
                        LIMIT 10
                    ");
                    $stmt->execute(['%' . parse_url(SITE_URL, PHP_URL_HOST) . '%']);
                    $referrers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($referrers)): ?>
                        <?php 
                        $maxRef = $referrers[0]['count']; 
                        foreach ($referrers as $ref): 
                            $percent = round(($ref['count'] / $maxRef) * 100);
                        ?>
                            <div class="page-item">
                                <div style="flex: 1;">
                                    <strong><?php echo htmlspecialchars(parse_url($ref['referrer'], PHP_URL_HOST) ?? $ref['referrer']); ?></strong>
                                    <div style="color: #64748b; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px;">
                                        <?php echo htmlspecialchars($ref['referrer']); ?>
                                    </div>
                                    <div style="margin-top: 5px; background: #f1f5f9; height: 6px; border-radius: 3px; width: 100%;">
                                        <div style="background: #3b82f6; height: 100%; border-radius: 3px; width: <?php echo $percent; ?>%;"></div>
                                    </div>
                                </div>
                                <div style="text-align: right; min-width: 80px;">
                                    <div style="font-weight: 600; color: #3b82f6;">
                                        <?php echo number_format($ref['count']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">Besuche</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🔗</div>
                            <p>Keine externen Traffic-Quellen gefunden</p>
                            <p style="font-size: 0.875rem; margin-top: 0.5rem; color: #94a3b8;">
                                Traffic wird überwiegend direkt oder intern generiert
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            
            <?php elseif ($activeTab === '404-monitor'): ?>
                <!-- 404 Monitor Tab -->
                <h3 style="margin-bottom: 1rem;">🚫 404 Fehler Protokoll</h3>
                <div class="page-list">
                    <?php 
                    $pageViews404 = [];
                    // Check if page_title '404' exists in tracking
                    $stmt = $db->prepare("
                        SELECT page_slug, COUNT(*) as count, MAX(visited_at) as last_seen 
                        FROM {$db->getPrefix()}page_views 
                        WHERE page_title LIKE '%404%' OR page_title LIKE '%Not Found%'
                        GROUP BY page_slug 
                        ORDER BY count DESC 
                        LIMIT 50
                    ");
                    $stmt->execute();
                    $pageViews404 = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($pageViews404)): ?>
                        <?php foreach ($pageViews404 as $p404): ?>
                            <div class="page-item">
                                <div>
                                    <strong style="color: #ef4444;">/<?php echo htmlspecialchars($p404['page_slug']); ?></strong>
                                    <div style="color: #64748b; font-size: 0.875rem;">
                                        Zuletzt: <?php echo date('d.m.Y H:i', strtotime($p404['last_seen'])); ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: #ef4444;">
                                        <?php echo number_format($p404['count']); ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #64748b;">Fehler</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">✅</div>
                            <p>Keine 404-Fehler gefunden</p>
                            <p style="font-size: 0.875rem; margin-top: 0.5rem; color: #94a3b8;">
                                Großartig! Alle Links scheinen zu funktionieren.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($activeTab === 'seo-analyzer'): ?>
                <!-- SEO Analyzer Tab -->
                <?php
                // Basic SEO Checks
                $score = 0;
                $checks = [];
                
                // 1. Site Title & Desc
                $siteDesc = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'setting_site_description'");
                if (!empty($siteDesc['option_value'])) {
                    $score += 20;
                    $checks[] = ['label' => 'Meta Description gesetzt', 'status' => true];
                } else {
                    $checks[] = ['label' => 'Meta Description fehlt', 'status' => false];
                }

                // 2. Permalinks
                $permalinks = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'setting_permalink_structure'");
                if ($permalinks && strpos($permalinks['option_value'], '%') !== false) {
                     $score += 20;
                     $checks[] = ['label' => 'Sprechende Permalinks aktiv', 'status' => true];
                } else {
                     $checks[] = ['label' => 'Standard-Permalinks in Verwendung', 'status' => false];
                }

                // 3. Sitemap
                $sitemap = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'seo_sitemap_enabled'");
                if ($sitemap && $sitemap['option_value'] == '1') {
                     $score += 20;
                     $checks[] = ['label' => 'XML Sitemap aktiviert', 'status' => true];
                } else {
                     $checks[] = ['label' => 'XML Sitemap deaktiviert', 'status' => false];
                }

                // 4. Content Check (Pages with Meta Desc)
                $pagesCount = $db->fetchOne("SELECT COUNT(*) as c FROM {$db->getPrefix()}posts WHERE status = 'published'");
                $pagesWithMeta = $db->fetchOne("SELECT COUNT(*) as c FROM {$db->getPrefix()}posts WHERE status = 'published' AND meta_description != '' AND meta_description IS NOT NULL");
                
                $ratio = ($pagesCount['c'] > 0) ? ($pagesWithMeta['c'] / $pagesCount['c']) : 1;
                $score += round($ratio * 20);
                $checks[] = ['label' => "{$pagesWithMeta['c']} von {$pagesCount['c']} Seiten haben Meta-Descriptions", 'status' => ($ratio > 0.5)];

                // 5. SSL (Simple check)
                $isSSL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
                if ($isSSL) {
                    $score += 20;
                    $checks[] = ['label' => 'SSL/HTTPS aktiv', 'status' => true];
                } else {
                    $checks[] = ['label' => 'Kein SSL erkannt', 'status' => false];
                }
                ?>
                
                <div class="metrics-grid">
                    <div class="metric-card" style="border-left-color: <?php echo $score >= 80 ? '#10b981' : ($score >= 50 ? '#f59e0b' : '#ef4444'); ?>;">
                        <div class="metric-icon">🎯</div>
                        <div class="metric-value"><?php echo $score; ?>/100</div>
                        <div class="metric-label">SEO Gesamt-Score</div>
                    </div>
                </div>

                <div class="admin-card">
                    <h3>Prüfbericht</h3>
                    <div class="page-list">
                        <?php foreach ($checks as $check): ?>
                            <div class="page-item">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 1.2rem;"><?php echo $check['status'] ? '✅' : '⚠️'; ?></span>
                                    <span style="<?php echo $check['status'] ? '' : 'color: #ea580c; font-weight: 500;'; ?>">
                                        <?php echo htmlspecialchars($check['label']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                     <div style="margin-top: 20px; text-align: right;">
                        <a href="/admin/seo" class="btn btn-primary">SEO Einstellungen öffnen</a>
                    </div>
                </div>

            <?php endif; ?>
    </div>
</body>
</html>
