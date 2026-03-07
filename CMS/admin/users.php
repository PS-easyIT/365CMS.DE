<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Benutzer – Entry Point
 * Route: /admin/users
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/UsersModule.php';
$module    = new UsersModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_users')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/users');
        exit;
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if ($result['success'] && !empty($result['id'])) {
                header('Location: ' . SITE_URL . '/admin/users?action=edit&id=' . $result['id']);
            } else {
                header('Location: ' . SITE_URL . '/admin/users');
            }
            exit;

        case 'delete':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->deleteUser($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/users');
            exit;

        case 'bulk':
            $bulkAction = $_POST['bulk_action'] ?? '';
            $ids        = array_filter(array_map('intval', $_POST['ids'] ?? []));
            $result     = $module->bulkAction($bulkAction, $ids);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/users');
            exit;
    }
}

// ─── Session-Alert ───────────────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_users');
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'edit') {
    $id         = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data       = $module->getEditData($id);
    $pageTitle  = $data['isNew'] ? 'Neuer Benutzer' : 'Benutzer bearbeiten';
    $activePage = 'users';
    $pageAssets = [];

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/users/edit.php';
    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Benutzer';
    $activePage = 'users';
    $pageAssets = [];

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/users/list.php';
    require __DIR__ . '/partials/footer.php';
}
