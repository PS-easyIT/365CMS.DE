<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Updates – Entry Point
 * Route: /admin/updates
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/UpdatesModule.php';
$module    = new UpdatesModule();
$alert     = null;
$allowedActions = [
    'check_updates',
    'install_core',
    'install_plugin',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_updates')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/updates');
        exit;
    }

    $action = (string)($_POST['action'] ?? '');

    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Ungültige Update-Aktion.'];
        header('Location: ' . SITE_URL . '/admin/updates');
        exit;
    }

    if ($action === 'check_updates') {
        $module->checkAllUpdates();
        $_SESSION['admin_alert'] = ['type' => 'info', 'message' => 'Update-Prüfung abgeschlossen.'];
    } elseif ($action === 'install_core') {
        $result = $module->installCoreUpdate();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? 'Core-Update konnte nicht verarbeitet werden.',
        ];
    } elseif ($action === 'install_plugin') {
        $slug   = (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($_POST['plugin_slug'] ?? '')));
        $result = $module->installPluginUpdate($slug);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? 'Plugin-Update konnte nicht verarbeitet werden.',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/updates');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_updates');
$data       = $module->getData();
$pageTitle  = 'Updates';
$activePage = 'updates';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
defined('CMS_ADMIN_SYSTEM_VIEW') || define('CMS_ADMIN_SYSTEM_VIEW', true);
require __DIR__ . '/views/system/updates.php';
require __DIR__ . '/partials/footer.php';
