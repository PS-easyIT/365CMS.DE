<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/member/MemberDashboardModule.php';

$memberSection   = $memberSection ?? 'overview';
$memberRoutePath = $memberRoutePath ?? '/admin/member-dashboard';
$memberViewFile  = $memberViewFile ?? (__DIR__ . '/views/member/dashboard.php');
$pageTitle       = $pageTitle ?? 'Member Dashboard';
$activePage      = $activePage ?? 'member-dashboard';
$pageAssets      = $pageAssets ?? [];
$module          = new MemberDashboardModule();
$alert           = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_member_dashboard')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . $memberRoutePath);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    $result = $action === 'save'
        ? $module->saveSection((string)$memberSection, $_POST)
        : ['success' => false, 'error' => 'Unbekannte Aktion.'];

    $_SESSION['admin_alert'] = [
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: ' . SITE_URL . $memberRoutePath);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_member_dashboard');
$data      = $module->getData();

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $memberViewFile;
require __DIR__ . '/partials/footer.php';
