<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme-unabhängige Einstellungen – Entry Point
 * Route: /admin/theme-settings
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

header('Location: ' . SITE_URL . '/admin/settings');
exit;

require_once __DIR__ . '/modules/settings/SettingsModule.php';
$module       = new SettingsModule();
$alert        = null;
$currentTab   = 'general';
$hideSettingsTabs = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_theme_settings')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/theme-settings');
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

    header('Location: ' . SITE_URL . '/admin/theme-settings');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_theme_settings');
$data       = $module->getData();
$pageTitle  = 'Einstellungen';
$activePage = 'theme-settings';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/settings/general.php';
require __DIR__ . '/partials/footer.php';
