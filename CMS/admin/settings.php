<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Allgemeine Einstellungen – Entry Point
 * Route: /admin/settings
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/settings/SettingsModule.php';
$module    = new SettingsModule();
$alert     = null;
$currentTab = ($_GET['tab'] ?? 'general') === 'content' ? 'content' : 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_settings')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        $redirectTab = ($_POST['tab'] ?? 'general') === 'content' ? 'content' : 'general';
        header('Location: ' . SITE_URL . '/admin/settings?tab=' . $redirectTab);
        exit;
    }

    $action = $_POST['action'] ?? '';
    $currentTab = ($_POST['tab'] ?? 'general') === 'content' ? 'content' : 'general';
    if ($action === 'save') {
        $result = $module->saveSettings($_POST);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/settings?tab=' . $currentTab);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_settings');
$data       = $module->getData();
$pageTitle  = $currentTab === 'content' ? 'Beiträge & Sites' : 'Allgemeine Einstellungen';
$activePage = $currentTab === 'content' ? 'content-settings' : 'settings';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/settings/general.php';
require __DIR__ . '/partials/footer.php';
