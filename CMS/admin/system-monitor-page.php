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

require_once __DIR__ . '/modules/system/SystemInfoModule.php';

$systemSection = $systemSection ?? 'info';
$systemRoutePath = $systemRoutePath ?? '/admin/info';
$systemViewFile = $systemViewFile ?? (__DIR__ . '/views/system/info.php');
$pageTitle = $pageTitle ?? 'Info & Diagnose';
$activePage = $activePage ?? 'info';
$pageAssets = $pageAssets ?? [];
$module = new SystemInfoModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_system_info')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . $systemRoutePath);
        exit;
    }

    $result = $module->handleAction((string)$systemSection, (string)($_POST['action'] ?? ''), $_POST);
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    header('Location: ' . SITE_URL . $systemRoutePath);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_system_info');
$data = $module->getData();

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $systemViewFile;
require __DIR__ . '/partials/footer.php';
