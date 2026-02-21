<?php
/**
 * Security Audit Admin Page
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) exit;
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$auth = Auth::instance();
$db = Database::instance();
$security = Security::instance();

// --- AUDIT LOGIC ---
$auditResults = [];

// 1. SSL Check
$isSSL = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$auditResults[] = [
    'category' => 'Infrastructure',
    'title' => 'SSL Encryption',
    'status' => $isSSL ? 'pass' : 'fail',
    'message' => $isSSL ? 'Active (HTTPS)' : 'Inactive (HTTP is insecure)'
];

// 2. PHP Version
$phpVer = phpversion();
$auditResults[] = [
    'category' => 'Infrastructure', 
    'title' => 'PHP Version', 
    'status' => version_compare($phpVer, '8.0.0', '>=') ? 'pass' : 'fail',
    'message' => 'Current: ' . $phpVer . ' (Recommended: 8.0+)'
];

// 3. Debug Mode
$debug = defined('WP_DEBUG') && WP_DEBUG; // Or CMS constant
$displayErrors = ini_get('display_errors');
$auditResults[] = [
    'category' => 'Configuration',
    'title' => 'Debug Display', 
    'status' => ($displayErrors == 0 || strtolower($displayErrors) === 'off') ? 'pass' : 'warning',
    'message' => 'display_errors is ' . ($displayErrors ? 'ON' : 'OFF')
];

// 4. Admin Account Check
$admins = $db->execute("SELECT username FROM {$db->getPrefix()}users WHERE role = 'admin'")->fetchAll();
$hasDefaultAdmin = false;
foreach ($admins as $a) {
    if (strtolower($a->username) === 'admin' || strtolower($a->username) === 'administrator') {
        $hasDefaultAdmin = true;
    }
}
$auditResults[] = [
    'category' => 'Users',
    'title' => 'Admin Username',
    'status' => $hasDefaultAdmin ? 'fail' : 'pass',
    'message' => $hasDefaultAdmin ? 'Default "admin" user exists (High Risk)' : 'No default admin user found'
];

// 5. Database Prefix
$prefix = $db->getPrefix();
$isDefaultWP = ($prefix === 'wp_');
$isDefaultCMS = ($prefix === 'cms_');

$auditResults[] = [
    'category' => 'Database',
    'title' => 'Table Prefix',
    'status' => $isDefaultWP ? 'fail' : ($isDefaultCMS ? 'info' : 'pass'), 
    'message' => "Current prefix: {$prefix}" . ($isDefaultWP ? ' (High Risk: "wp_" is targeted by bots)' : ($isDefaultCMS ? ' (Standard prefix, acceptable but custom is better)' : ' (Custom prefix, good)'))
];

// 6. Uploads Directory Protection
$uploadsDir = UPLOAD_PATH;
$isWritable = is_writable($uploadsDir);
// Just checking write permissions isn't enough, we want to know if execution is prevented, but we can't test that easily.
$auditResults[] = [
    'category' => 'Filesystem',
    'title' => 'Uploads Writable',
    'status' => $isWritable ? 'pass' : 'warning', // Actually it MUST be writable for CMS to work, but good to know
    'message' => $isWritable ? 'Writable (Correct)' : 'Not Writable (Uploads will fail)'
];

// 7. Check for install.php existence
$installFile = dirname(__DIR__) . '/install.php';
$auditResults[] = [
    'category' => 'Filesystem',
    'title' => 'Installation File',
    'status' => file_exists($installFile) ? 'fail' : 'pass',
    'message' => file_exists($installFile) ? 'install.php exists! Delete it immediately.' : 'install.php removed'
];


require_once __DIR__ . '/partials/admin-menu.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Security Audit - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        .audit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .audit-card { background: white; border-radius: 8px; border: 1px solid #e2e8f0; padding: 1.25rem; display: flex; align-items: flex-start; gap: 1rem; }
        .audit-icon { font-size: 1.5rem; flex-shrink: 0; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        
        .status-pass { background: #dcfce7; color: #166534; }
        .status-fail { background: #fee2e2; color: #991b1b; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-info { background: #dbeafe; color: #1e40af; }
        
        .score-card { background: #1e293b; color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; }
        .score-circle { width: 80px; height: 80px; border-radius: 50%; border: 4px solid #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; }
    </style>
</head>
<body class="admin-body">
    <?php renderAdminSidebar('security-audit'); ?>
    <div class="admin-content">
        <div class="admin-page-header">
            <h2>🛡️ Security Audit</h2>
            <p>Systemweite Sicherheitsüberprüfung.</p>
        </div>

        <?php
        $total = count($auditResults);
        $passed = count(array_filter($auditResults, fn($r) => $r['status'] === 'pass'));
        $score = round(($passed / $total) * 100);
        $scoreColor = $score > 80 ? '#22c55e' : ($score > 50 ? '#eab308' : '#ef4444');
        ?>

        <div class="score-card">
            <div>
                <h3 style="margin:0; font-size:1.5rem;">Sicherheits-Status</h3>
                <p style="margin:0.5rem 0 0 0; color:#94a3b8;">
                    <?php echo $passed; ?> von <?php echo $total; ?> Checks bestanden.
                </p>
                <?php if($passed < $total): ?>
                    <p style="margin-top:0.5rem; color:#fba;">Handlungsbedarf erkannt!</p>
                <?php else: ?>
                    <p style="margin-top:0.5rem; color:#86efac;">Hervorragend!</p>
                <?php endif; ?>
            </div>
            <div class="score-circle" style="border-color: <?php echo $scoreColor; ?>;">
                <?php echo $score; ?>%
            </div>
        </div>

        <div class="audit-grid">
            <?php foreach($auditResults as $check): ?>
            <div class="audit-card" style="border-left: 4px solid <?php 
                echo match($check['status']) { 
                    'pass' => '#22c55e', 
                    'fail' => '#ef4444', 
                    'warning' => '#eab308',
                    'info' => '#3b82f6',
                    default => '#94a3b8'
                }; 
            ?>;">
                <div class="audit-icon <?php echo 'status-' . $check['status']; ?>">
                    <?php echo match($check['status']) { 
                        'pass' => '✓', 
                        'fail' => '✕', 
                        'warning' => '!', 
                        'info' => 'i',
                        default => '?'
                    }; ?>
                </div>
                <div>
                    <span style="font-size:0.75rem; text-transform:uppercase; color:#64748b; font-weight:600; letter-spacing:0.5px;">
                        <?php echo htmlspecialchars($check['category']); ?>
                    </span>
                    <h4 style="margin:0.25rem 0; font-size:1.1rem;"><?php echo htmlspecialchars($check['title']); ?></h4>
                    <p style="margin:0; font-size:0.9rem; color:#475569;">
                        <?php echo htmlspecialchars($check['message']); ?>
                    </p>
                    <?php if($check['status'] === 'fail' && $check['title'] === 'Installation File'): ?>
                         <div style="margin-top:0.5rem;">
                             <code style="background:#fee2e2; padding:0.25rem;">Delete /install.php</code>
                         </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
    </div>
    
    <style>
        .settings-form { max-width: 100%; }
        .settings-card {
            background: white; border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden; display: flex; flex-direction: column;
        }
        .card-header {
            background: #f8fafc; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0;
        }
        .card-header h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }
        .card-header p { margin: 0.25rem 0 0 0; font-size: 0.85rem; color: #64748b; }
        .card-body { padding: 1.5rem; flex: 1; }
        .toggle-group { display: flex; align-items: flex-start; gap: 1rem; }
        .toggle-switch { position: relative; width: 48px; height: 26px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #3b82f6; }
        input:checked + .slider:before { transform: translateX(22px); }
        .toggle-label strong { display: block; color: #334155; }
        .toggle-label p { margin: 0; font-size: 0.85rem; color: #64748b; }
        .form-control { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; }
        .sticky-footer {
            margin-top: 2rem; background: white; padding: 1rem; border-radius: 8px; text-align: right;
            border: 1px solid #e2e8f0; position: sticky; bottom: 20px; z-index: 10;
        }
        .btn-large { padding: 0.75rem 2rem; background: #3b82f6; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-large:hover { background: #2563eb; }
    </style>
</body>
</html>
