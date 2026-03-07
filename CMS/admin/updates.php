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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_updates')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/updates');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'check_updates') {
        $result = $module->checkAllUpdates();
        $_SESSION['admin_alert'] = ['type' => 'info', 'message' => 'Update-Prüfung abgeschlossen.'];
    } elseif ($action === 'install_core') {
        $result = $module->installCoreUpdate();
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'install_plugin') {
        $slug   = preg_replace('/[^a-z0-9_-]/', '', $_POST['plugin_slug'] ?? '');
        $result = $module->installPluginUpdate($slug);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
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
require __DIR__ . '/views/system/updates.php';
require __DIR__ . '/partials/footer.php';
