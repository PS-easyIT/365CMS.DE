<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member Dashboard Konfiguration – Entry Point
 * Route: /admin/member-dashboard
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/member/MemberDashboardModule.php';
$module    = new MemberDashboardModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_member_dashboard')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/member-dashboard');
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $result = $module->saveSettings($_POST);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/member-dashboard');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_member_dashboard');
$data       = $module->getData();
$pageTitle  = 'Member Dashboard';
$activePage = 'member-dashboard';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/member/dashboard.php';
require __DIR__ . '/partials/footer.php';
