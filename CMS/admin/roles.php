<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rollen & Rechte – Entry Point
 * Route: /admin/roles
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/RolesModule.php';
$module    = new RolesModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_roles')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/roles');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $result = match ($action) {
        'save_permissions' => $module->savePermissions($_POST),
        'add_role' => $module->addRole($_POST),
        'update_role' => $module->updateRole($_POST),
        'delete_role' => $module->deleteRole($_POST),
        'add_capability' => $module->addCapability($_POST),
        'update_capability' => $module->updateCapability($_POST),
        'delete_capability' => $module->deleteCapability($_POST),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };

    $_SESSION['admin_alert'] = [
        'type'    => $result['success'] ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: ' . SITE_URL . '/admin/roles');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_roles');
$data       = $module->getData();
$pageTitle  = 'Rollen & Rechte';
$activePage = 'roles';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/users/roles.php';
require __DIR__ . '/partials/footer.php';
