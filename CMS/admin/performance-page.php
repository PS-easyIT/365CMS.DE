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

require_once __DIR__ . '/modules/seo/PerformanceModule.php';

$performanceSection   = $performanceSection ?? 'overview';
$performanceRoutePath = $performanceRoutePath ?? '/admin/performance';
$performanceViewFile  = $performanceViewFile ?? (__DIR__ . '/views/seo/performance.php');
$pageTitle            = $pageTitle ?? 'Performance';
$activePage           = $activePage ?? 'performance';
$pageAssets           = $pageAssets ?? [];
$module               = new PerformanceModule();
$alert                = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_performance')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . $performanceRoutePath);
        exit;
    }

    $result = $module->handleAction((string)$performanceSection, (string)($_POST['action'] ?? ''), $_POST);
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    header('Location: ' . SITE_URL . $performanceRoutePath);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_performance');
$data = $module->getData();

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $performanceViewFile;
require __DIR__ . '/partials/footer.php';
