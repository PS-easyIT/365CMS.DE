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

function cms_admin_menu_editor_redirect(int $menuId = 0): never
{
    $redirect = SITE_URL . '/admin/menu-editor';
    if ($menuId > 0) {
        $redirect .= '?menu=' . $menuId;
    }

    header('Location: ' . $redirect);
    exit;
}

function cms_admin_menu_editor_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_menu_editor_flash_result(array $result): void
{
    cms_admin_menu_editor_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_menu_editor_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_menu_editor_action_handlers(MenuEditorModule $module): array
{
    return [
        'save_menu' => static fn (array $post): array => $module->saveMenu($post),
        'delete_menu' => static fn (array $post): array => $module->deleteMenu((int) ($post['menu_id'] ?? 0)),
        'save_items' => static fn (array $post): array => $module->saveItems(
            (int) ($post['menu_id'] ?? 0),
            (string) ($post['items'] ?? '[]')
        ),
    ];
}

$module = new MenuEditorModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_menu_editor')) {
        cms_admin_menu_editor_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_menu_editor_redirect((int) ($_POST['menu_id'] ?? 0));
    }

    $action = (string) ($_POST['action'] ?? '');
    $handlers = cms_admin_menu_editor_action_handlers($module);

    if (!isset($handlers[$action])) {
        cms_admin_menu_editor_flash(['type' => 'danger', 'message' => 'Unbekannte Aktion.']);
        cms_admin_menu_editor_redirect((int) ($_POST['menu_id'] ?? 0));
    }

    $result = $handlers[$action]($_POST);
    cms_admin_menu_editor_flash_result($result);
    cms_admin_menu_editor_redirect((int) ($_POST['menu_id'] ?? 0));
}

$alert = cms_admin_menu_editor_pull_alert();

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
