<?php
declare(strict_types=1);

/**
 * Löschanträge (DSGVO Art. 17) – Entry Point
 * Route: /admin/deletion-requests
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

require_once __DIR__ . '/modules/legal/DeletionRequestsModule.php';
$module    = new DeletionRequestsModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_deletion')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'process':
                $result = $module->processRequest((int)($_POST['id'] ?? 0));
                break;
            case 'execute':
                $result = $module->executeDeletion((int)($_POST['id'] ?? 0));
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
        header('Location: ' . SITE_URL . '/admin/deletion-requests');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_deletion');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_deletion');
$pageTitle  = 'Löschanträge';
$activePage = 'deletion-requests';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/deletion-requests.php';
require_once __DIR__ . '/partials/footer.php';
