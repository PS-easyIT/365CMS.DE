<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * System-Info & Diagnose – Entry Point
 * Route: /admin/system-info
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/SystemInfoModule.php';
$module    = new SystemInfoModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_system_info')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/system-info');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'clear_cache') {
        $result = $module->clearCache();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'optimize_db') {
        $result = $module->optimizeDatabase();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'clear_logs') {
        $result = $module->clearLogs();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'create_tables') {
        $result = $module->createMissingTables();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'repair_tables') {
        $result = $module->repairTables();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/system-info');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_system_info');
$data       = $module->getData();
$pageTitle  = 'System-Info & Diagnose';
$activePage = 'system-info';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/system/info.php';
require __DIR__ . '/partials/footer.php';
