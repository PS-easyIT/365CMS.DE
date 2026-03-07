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

require_once __DIR__ . '/modules/seo/SeoSuiteModule.php';

$seoSection = $seoSection ?? 'dashboard';
$seoRoutePath = $seoRoutePath ?? '/admin/seo-dashboard';
$seoViewFile = $seoViewFile ?? (__DIR__ . '/views/seo/dashboard.php');
$pageTitle = $pageTitle ?? 'SEO';
$activePage = $activePage ?? 'seo-dashboard';
$module = new SeoSuiteModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_seo_suite')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . $seoRoutePath);
        exit;
    }

    $result = $module->handleAction((string)$seoSection, (string)($_POST['action'] ?? ''), $_POST);
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    header('Location: ' . SITE_URL . $seoRoutePath);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_seo_suite');
$data = $module->getData((string)$seoSection);

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require $seoViewFile;
require __DIR__ . '/partials/footer.php';
