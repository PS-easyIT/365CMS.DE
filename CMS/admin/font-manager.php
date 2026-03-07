<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Font Manager – Entry Point
 * Route: /admin/font-manager
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/FontManagerModule.php';
$module    = new FontManagerModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_font_manager')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/font-manager');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $result = $module->saveSettings($_POST);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'scan_theme_fonts') {
        $result = $module->scanThemeFonts();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'delete_font') {
        $fontId = (int)($_POST['font_id'] ?? 0);
        $result = $module->deleteCustomFont($fontId);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'download_google_font') {
        $fontFamily = trim($_POST['google_font_family'] ?? '');
        $result = $module->downloadGoogleFont($fontFamily);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/font-manager');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_font_manager');
$data       = $module->getData();
$pageTitle  = 'Font Manager';
$activePage = 'font-manager';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/fonts.php';
require __DIR__ . '/partials/footer.php';
