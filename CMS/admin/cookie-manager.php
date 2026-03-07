<?php
declare(strict_types=1);

/**
 * Cookie Manager – Entry Point
 * Route: /admin/cookie-manager
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

require_once __DIR__ . '/modules/legal/CookieManagerModule.php';
$module    = new CookieManagerModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_cookies')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save_settings':
                $result = $module->saveSettings($_POST);
                break;
            case 'save_category':
                $result = $module->saveCategory($_POST);
                break;
            case 'delete_category':
                $result = $module->deleteCategory((int)($_POST['id'] ?? 0));
                break;
            case 'save_service':
                $result = $module->saveService($_POST);
                break;
            case 'delete_service':
                $result = $module->deleteService((int)($_POST['id'] ?? 0));
                break;
            case 'import_curated_service':
                $result = $module->importCuratedService(
                    (string)($_POST['service_slug'] ?? ''),
                    isset($_POST['self_hosted']) && $_POST['self_hosted'] === '1'
                );
                break;
            case 'run_scan':
                $result = $module->runScanner();
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/cookie-manager');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_cookies');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_cookies');
$pageTitle  = 'Cookie Manager';
$activePage = 'cookie-manager';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/cookies.php';
require_once __DIR__ . '/partials/footer.php';
