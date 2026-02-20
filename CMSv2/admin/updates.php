<?php
/**
 * Updates Admin Page
 * 
 * System-, Plugin- und Theme-Updates
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
use CMS\Services\UpdateService;

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
$updates = UpdateService::getInstance();

// Get current tab
$activeTab = $_GET['tab'] ?? 'core';

// Get update data
$coreUpdate = $updates->checkCoreUpdates();
$pluginUpdates = $updates->checkPluginUpdates();
$themeUpdate = $updates->checkThemeUpdates();
$systemReqs = $updates->getSystemRequirements();
$updateHistory = $updates->getUpdateHistory(20);

// Generate CSRF token
$csrfToken = $security->generateToken('updates');

// Determine current page for menu
$currentPage = 'updates';

// Load admin menu (VOR DOCTYPE!)
require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Updates - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .update-tabs {
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
        
        .update-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .update-card.update-available {
            border-left: 4px solid #3b82f6;
        }
        
        .update-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .update-badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #15803d;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .changelog-list {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .changelog-list li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        
        .changelog-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .system-req-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .req-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid #cbd5e1;
        }
        
        .req-item.met {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .req-item.not-met {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
    </style>
</head>
<body>
    
    <?php renderAdminSidebar($currentPage); ?>
    
    <div class="admin-content">
        <div class="page-header">
            <h1>🔄 System-Updates</h1>
            <p style="color: #64748b;">CMS, Plugins und Themes aktuell halten</p>
        </div>
        
        <!-- Tabs -->
        <div class="update-tabs">
            <a href="?tab=core" class="tab-button <?php echo $activeTab === 'core' ? 'active' : ''; ?>">
                💻 CMS Updates
            </a>
            <a href="?tab=plugins" class="tab-button <?php echo $activeTab === 'plugins' ? 'active' : ''; ?>">
                🔌 Plugin Updates
            </a>
            <a href="?tab=themes" class="tab-button <?php echo $activeTab === 'themes' ? 'active' : ''; ?>">
                🎨 Theme Updates
            </a>
            <a href="?tab=system" class="tab-button <?php echo $activeTab === 'system' ? 'active' : ''; ?>">
                ⚙️ Systemanforderungen
            </a>
            <a href="?tab=history" class="tab-button <?php echo $activeTab === 'history' ? 'active' : ''; ?>">
                📜 Verlauf
            </a>
        </div>
        
        <!-- Tab Content -->
        <?php if ($activeTab === 'core'): ?>
            <!-- Core Updates Tab -->
            <div class="update-card <?php echo $coreUpdate['update_available'] ? 'update-available' : ''; ?>">
                <div class="update-header">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">365 CMS</h3>
                        <p style="color: #64748b; margin: 0;">
                            Version <?php echo htmlspecialchars($coreUpdate['current_version']); ?>
                            <?php if ($coreUpdate['update_available']): ?>
                                → <?php echo htmlspecialchars($coreUpdate['latest_version']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($coreUpdate['update_available']): ?>
                        <span class="update-badge badge-info">Update verfügbar</span>
                    <?php else: ?>
                        <span class="update-badge badge-success">✓ Aktuell</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($coreUpdate['update_available']): ?>
                    <div style="margin: 1.5rem 0;">
                        <strong>Neu in Version <?php echo htmlspecialchars($coreUpdate['latest_version']); ?>:</strong>
                        <?php if (!empty($coreUpdate['changelog'])): ?>
                            <ul class="changelog-list">
                                <?php foreach ($coreUpdate['changelog'] as $change): ?>
                                    <li><?php echo htmlspecialchars($change); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        
                        <?php if (!empty($coreUpdate['release_notes'])): ?>
                            <details style="margin-top: 1rem;">
                                <summary style="cursor: pointer; color: #3b82f6;">Vollständige Release Notes anzeigen</summary>
                                <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px; white-space: pre-wrap;">
                                    <?php echo htmlspecialchars($coreUpdate['release_notes']); ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button class="btn-save" style="margin: 0;">
                            ⬇ Auf Version <?php echo htmlspecialchars($coreUpdate['latest_version']); ?> aktualisieren
                        </button>
                        <?php if (!empty($coreUpdate['download_url'])): ?>
                            <a href="<?php echo htmlspecialchars($coreUpdate['download_url']); ?>" target="_blank" style="color: #64748b; text-decoration: none;">
                                📦 Download auf GitHub
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #10b981; margin-top: 1rem;">
                        ✓ Sie verwenden die neueste Version des CMS
                    </p>
                <?php endif; ?>
            </div>
            
        <?php elseif ($activeTab === 'plugins'): ?>
            <!-- Plugin Updates Tab -->
            <h3 style="margin-bottom: 1rem;">🔌 Plugin Updates</h3>
            
            <?php if (!empty($pluginUpdates)): ?>
                <?php foreach ($pluginUpdates as $folder => $update): ?>
                    <div class="update-card update-available">
                        <div class="update-header">
                            <div>
                                <h4 style="margin: 0 0 0.25rem 0;"><?php echo htmlspecialchars($update['name']); ?></h4>
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    Version <?php echo htmlspecialchars($update['current_version']); ?>
                                    → <?php echo htmlspecialchars($update['new_version']); ?>
                                </div>
                            </div>
                            <button class="btn-save">⬇ Aktualisieren</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="update-card">
                    <p style="color: #10b981; text-align: center; padding: 2rem;">
                        ✓ Alle Plugins sind aktuell
                    </p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($activeTab === 'themes'): ?>
            <!-- Theme Updates Tab -->
            <h3 style="margin-bottom: 1rem;">🎨 Theme Updates</h3>
            
            <?php if (!empty($themeUpdate)): ?>
                <div class="update-card <?php echo ($themeUpdate['update_available'] ?? false) ? 'update-available' : ''; ?>">
                    <div class="update-header">
                        <div>
                            <h4 style="margin: 0 0 0.25rem 0;">Default Theme</h4>
                            <div style="color: #64748b; font-size: 0.875rem;">
                                Version <?php echo htmlspecialchars($themeUpdate['current_version'] ?? 'Unknown'); ?>
                                <?php if ($themeUpdate['update_available'] ?? false): ?>
                                    → <?php echo htmlspecialchars($themeUpdate['latest_version'] ?? ''); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($themeUpdate['update_available'] ?? false): ?>
                            <span class="update-badge badge-info">Update verfügbar</span>
                        <?php else: ?>
                            <span class="update-badge badge-success">✓ Aktuell</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($themeUpdate['update_available'] ?? false): ?>
                        <?php if (!empty($themeUpdate['changelog'])): ?>
                            <ul class="changelog-list">
                                <?php foreach ($themeUpdate['changelog'] as $change): ?>
                                    <li><?php echo htmlspecialchars($change); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <button class="btn-save" style="margin-top: 1rem;">
                            ⬇ Auf Version <?php echo htmlspecialchars($themeUpdate['latest_version'] ?? ''); ?> aktualisieren
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="update-card">
                    <p style="color: #94a3b8; text-align: center; padding: 2rem;">Theme-Informationen konnten nicht geladen werden</p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($activeTab === 'system'): ?>
            <!-- System Requirements Tab -->
            <h3 style="margin-bottom: 1rem;">⚙️ Systemanforderungen</h3>
            
            <div class="update-card">
                <h4>PHP Version</h4>
                <div class="system-req-grid">
                    <div class="req-item <?php echo $systemReqs['php_version']['met'] ? 'met' : 'not-met'; ?>">
                        <strong>Erforderlich:</strong> <?php echo $systemReqs['php_version']['required']; ?><br>
                        <strong>Installiert:</strong> <?php echo $systemReqs['php_version']['current']; ?>
                    </div>
                </div>
            </div>
            
            <div class="update-card">
                <h4>MySQL Version</h4>
                <div class="system-req-grid">
                    <div class="req-item <?php echo $systemReqs['mysql_version']['met'] ? 'met' : 'not-met'; ?>">
                        <strong>Erforderlich:</strong> <?php echo $systemReqs['mysql_version']['required']; ?><br>
                        <strong>Installiert:</strong> <?php echo $systemReqs['mysql_version']['current']; ?>
                    </div>
                </div>
            </div>
            
            <div class="update-card">
                <h4>PHP Extensions</h4>
                <div class="system-req-grid">
                    <?php foreach ($systemReqs['extensions'] as $ext => $loaded): ?>
                        <div class="req-item <?php echo $loaded ? 'met' : 'not-met'; ?>">
                            <strong><?php echo strtoupper($ext); ?></strong><br>
                            <?php echo $loaded ? '✓ Installiert' : '✗ Fehlt'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (isset($systemReqs['permissions'])): ?>
                <div class="update-card">
                    <h4>Verzeichnis-Berechtigungen</h4>
                    <div class="system-req-grid">
                        <?php foreach ($systemReqs['permissions'] as $dir => $writable): ?>
                            <div class="req-item <?php echo $writable ? 'met' : 'not-met'; ?>">
                                <strong><?php echo str_replace('_', '/', ucfirst($dir)); ?></strong><br>
                                <?php echo $writable ? '✓ Beschreibbar' : '✗ Nicht beschreibbar'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php elseif ($activeTab === 'history'): ?>
            <!-- Update History Tab -->
            <h3 style="margin-bottom: 1rem;">📜 Update-Verlauf</h3>
            
            <?php if (!empty($updateHistory)): ?>
                <?php foreach ($updateHistory as $entry): ?>
                    <div class="update-card">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div>
                                <strong><?php echo htmlspecialchars($entry['name'] ?? 'Unknown'); ?></strong>
                                <span style="color: #64748b; margin-left: 1rem;">
                                    Version <?php echo htmlspecialchars($entry['version'] ?? ''); ?>
                                </span>
                                <div style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($entry['type'] ?? ''); ?>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="color: #64748b; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($entry['timestamp'] ?? ''); ?>
                                </div>
                                <div style="color: #94a3b8; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($entry['user'] ?? 'System'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="update-card">
                    <p style="color: #94a3b8; text-align: center; padding: 2rem;">
                        Noch keine Updates durchgeführt
                    </p>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
    </div>
    
</body>
</html>
