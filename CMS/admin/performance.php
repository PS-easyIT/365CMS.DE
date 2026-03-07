<?php
declare(strict_types=1);

/**
 * Performance – Entry Point
 * Route: /admin/performance
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/seo/PerformanceModule.php';
$module    = new PerformanceModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_performance')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'clear_cache':
                $result = $module->clearCache();
                break;
            case 'clear_expired_sessions':
                $result = $module->clearExpiredSessions();
                break;
            case 'optimize_images':
                $result = $module->reportImageOptimization();
                break;
            case 'save_settings':
                $result = $module->saveSettings($_POST);
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/performance');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_performance');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_performance');
$pageTitle  = 'Performance';
$activePage = 'performance';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/seo/performance.php';
require_once __DIR__ . '/partials/footer.php';
