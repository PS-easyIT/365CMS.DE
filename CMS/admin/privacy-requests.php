<?php
declare(strict_types=1);

/**
 * Datenschutz-Auskunft (DSGVO Art. 15) – Entry Point
 * Route: /admin/privacy-requests
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

require_once __DIR__ . '/modules/legal/PrivacyRequestsModule.php';
$module    = new PrivacyRequestsModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_privacy')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'process':
                $result = $module->processRequest((int)($_POST['id'] ?? 0));
                break;
            case 'complete':
                $result = $module->completeRequest((int)($_POST['id'] ?? 0));
                break;
            case 'reject':
                $result = $module->rejectRequest((int)($_POST['id'] ?? 0), $_POST['reject_reason'] ?? '');
                break;
            case 'delete':
                $result = $module->deleteRequest((int)($_POST['id'] ?? 0));
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/privacy-requests');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_privacy');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_privacy');
$pageTitle  = 'Datenschutz-Auskunft';
$activePage = 'privacy-requests';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/privacy-requests.php';
require_once __DIR__ . '/partials/footer.php';
