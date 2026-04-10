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

const CMS_ADMIN_ROLES_WRITE_CAPABILITY = 'manage_users';

const CMS_ADMIN_ROLES_ALLOWED_ACTIONS = [
    'save_permissions',
    'add_role',
    'update_role',
    'delete_role',
    'add_capability',
    'update_capability',
    'delete_capability',
];

function cms_admin_roles_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_ROLES_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

/**
 * @return array{action:string,post:array<string,mixed>}
 */
function cms_admin_roles_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_roles_normalize_action($post['action'] ?? ''),
        'post' => $post,
    ];
}

function cms_admin_roles_handle_action(RolesModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save_permissions' => $module->savePermissions($payload['post']),
        'add_role' => $module->addRole($payload['post']),
        'update_role' => $module->updateRole($payload['post']),
        'delete_role' => $module->deleteRole($payload['post']),
        'add_capability' => $module->addCapability($payload['post']),
        'update_capability' => $module->updateCapability($payload['post']),
        'delete_capability' => $module->deleteCapability($payload['post']),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };
}

if (!Auth::instance()->isAdmin() || !Auth::instance()->hasCapability(CMS_ADMIN_ROLES_WRITE_CAPABILITY)) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/modules/users/RolesModule.php';
$module    = new RolesModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_roles')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: /admin/roles');
        exit;
    }

    $payload = cms_admin_roles_normalize_payload($_POST);
    $result = cms_admin_roles_handle_action($module, $payload);

    $_SESSION['admin_alert'] = [
        'type'    => $result['success'] ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? '',
    ];

    header('Location: /admin/roles');
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
$pageAssets = [
    'js' => [
        cms_asset_url('js/admin-users.js'),
    ],
];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/users/roles.php';
require __DIR__ . '/partials/footer.php';
