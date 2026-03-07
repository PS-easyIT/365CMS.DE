<?php
declare(strict_types=1);

/**
 * Auskunft & Löschen – Entry Point
 * Route: /admin/data-requests
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
require_once __DIR__ . '/modules/legal/DeletionRequestsModule.php';

$privacyModule  = new PrivacyRequestsModule();
$deletionModule = new DeletionRequestsModule();
$alert          = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_data_requests')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $scope = (string)($_POST['scope'] ?? '');
        $action = (string)($_POST['action'] ?? '');
        $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];

        if ($scope === 'privacy') {
            $id = (int)($_POST['id'] ?? 0);
            $result = match ($action) {
                'process'  => $privacyModule->processRequest($id),
                'complete' => $privacyModule->completeRequest($id),
                'reject'   => $privacyModule->rejectRequest($id, (string)($_POST['reject_reason'] ?? '')),
                'delete'   => $privacyModule->deleteRequest($id),
                default    => ['success' => false, 'error' => 'Unbekannte Auskunfts-Aktion.'],
            };
        }

        if ($scope === 'deletion') {
            $id = (int)($_POST['id'] ?? 0);
            $result = match ($action) {
                'process' => $deletionModule->processRequest($id),
                'execute' => $deletionModule->executeDeletion($id),
                'reject'  => $deletionModule->rejectRequest($id, (string)($_POST['reject_reason'] ?? '')),
                'delete'  => $deletionModule->deleteRequest($id),
                default   => ['success' => false, 'error' => 'Unbekannte Lösch-Aktion.'],
            };
        }

        $_SESSION['admin_alert'] = [
            'type' => !empty($result['success']) ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
        header('Location: ' . SITE_URL . '/admin/data-requests');
        exit;
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_data_requests');
$pageTitle  = 'Auskunft & Löschen';
$activePage = 'data-requests';
$data       = [
    'privacy' => $privacyModule->getData(),
    'deletion' => $deletionModule->getData(),
];

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/data-requests.php';
require_once __DIR__ . '/partials/footer.php';
