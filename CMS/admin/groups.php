<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gruppen – Entry Point
 * Route: /admin/groups
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/GroupsModule.php';
$module    = new GroupsModule();
$alert     = null;
$redirectUrl = SITE_URL . '/admin/groups';

function cms_admin_groups_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_groups_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ];
}

function cms_admin_groups_handle_action(GroupsModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save':
            return $module->save($post);

        case 'delete':
            return $module->delete((int) ($post['id'] ?? 0));
    }

    return ['success' => false, 'error' => 'Unbekannte Aktion.'];
}

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_groups')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_groups_redirect($redirectUrl);
    }

    $result = cms_admin_groups_handle_action($module, (string) $action, $_POST);
    cms_admin_groups_flash($result);
    cms_admin_groups_redirect($redirectUrl);
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_groups');
$data       = $module->getData();
$pageTitle  = 'Gruppen';
$activePage = 'groups';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/users/groups.php';
require __DIR__ . '/partials/footer.php';
