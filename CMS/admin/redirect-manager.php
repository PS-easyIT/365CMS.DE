<?php
declare(strict_types=1);

/**
 * 404-Errors & Weiterleitung – Entry Point
 * Route: /admin/redirect-manager
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

require_once __DIR__ . '/modules/seo/RedirectManagerModule.php';
$module = new RedirectManagerModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_redirect_manager')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'save_redirect':
                $result = $module->saveRedirect($_POST);
                break;
            case 'delete_redirect':
                $result = $module->deleteRedirect((int)($_POST['id'] ?? 0));
                break;
            case 'toggle_redirect':
                $result = $module->toggleRedirect((int)($_POST['id'] ?? 0));
                break;
            case 'clear_logs':
                $result = $module->clearLogs();
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        $_SESSION['admin_alert'] = [
            'type' => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
        header('Location: ' . SITE_URL . '/admin/redirect-manager');
        exit;
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_redirect_manager');
$pageTitle = '404-Errors & Weiterleitung';
$activePage = 'redirect-manager';
$data = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/seo/redirects.php';
require_once __DIR__ . '/partials/footer.php';
