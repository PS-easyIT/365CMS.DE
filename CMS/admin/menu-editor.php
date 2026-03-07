<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menü Editor – Entry Point
 * Route: /admin/menu-editor
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/menus/MenuEditorModule.php';
$module    = new MenuEditorModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_menu_editor')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/menu-editor');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_menu') {
        $result = $module->saveMenu($_POST);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'delete_menu') {
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $result = $module->deleteMenu($menuId);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'save_items') {
        $menuId = (int)($_POST['menu_id'] ?? 0);
        $items  = $_POST['items'] ?? '[]';
        $result = $module->saveItems($menuId, $items);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    $redirect = SITE_URL . '/admin/menu-editor';
    if (!empty($_POST['menu_id'])) {
        $redirect .= '?menu=' . (int)$_POST['menu_id'];
    }
    header('Location: ' . $redirect);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken    = Security::instance()->generateToken('admin_menu_editor');
$currentMenu  = (int)($_GET['menu'] ?? 0);
$data         = $module->getData($currentMenu);
$pageTitle    = 'Menü Editor';
$activePage   = 'menu-editor';
$pageAssets   = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/menus/editor.php';
require __DIR__ . '/partials/footer.php';
