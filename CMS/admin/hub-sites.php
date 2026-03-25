<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hub / Routing Sites – Entry Point
 * Route: /admin/hub-sites
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/hub/HubSitesModule.php';
$module = new HubSitesModule();
$alert = null;
$redirectBase = SITE_URL . '/admin/hub-sites';
$allowedActions = ['save', 'save-template', 'duplicate-template', 'delete-template', 'delete', 'duplicate'];
$allowedViews = ['list', 'edit', 'template-edit', 'templates'];

function cms_admin_hub_sites_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_hub_sites_flash(array $result, string $fallbackMessage): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? $fallbackMessage),
    ];
}

function cms_admin_hub_sites_post_redirect(string $redirectBase, string $action, array $result, array $post): string
{
    if ($action === 'save') {
        if (!empty($result['success'])) {
            if (!empty($post['open_public_after_save']) && !empty($result['slug'])) {
                return SITE_URL . '/' . ltrim((string) $result['slug'], '/');
            }

            return $redirectBase . '?action=edit&id=' . (int) ($result['id'] ?? 0);
        }

        return $redirectBase;
    }

    if ($action === 'save-template' || $action === 'duplicate-template') {
        if (!empty($result['success'])) {
            return $redirectBase . '?action=template-edit&key=' . rawurlencode((string) ($result['key'] ?? ''));
        }

        return $redirectBase . '?action=templates';
    }

    if ($action === 'delete-template') {
        return $redirectBase . '?action=templates';
    }

    return $redirectBase;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_hub_sites')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        cms_admin_hub_sites_redirect($redirectBase);
    }

    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Hub-Sites-Aktion.'];
        cms_admin_hub_sites_redirect($redirectBase);
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST);
            cms_admin_hub_sites_flash($result, 'Hub-Site konnte nicht gespeichert werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));

        case 'save-template':
            $result = $module->saveTemplate($_POST);
            cms_admin_hub_sites_flash($result, 'Hub-Template konnte nicht gespeichert werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));

        case 'duplicate-template':
            $result = $module->duplicateTemplate((string)($_POST['key'] ?? ''));
            cms_admin_hub_sites_flash($result, 'Hub-Template konnte nicht dupliziert werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));

        case 'delete-template':
            $result = $module->deleteTemplate((string)($_POST['key'] ?? ''));
            cms_admin_hub_sites_flash($result, 'Hub-Template konnte nicht gelöscht werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));

        case 'delete':
            $result = $module->delete((int)($_POST['id'] ?? 0));
            cms_admin_hub_sites_flash($result, 'Hub-Site konnte nicht gelöscht werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));

        case 'duplicate':
            $result = $module->duplicate((int)($_POST['id'] ?? 0));
            cms_admin_hub_sites_flash($result, 'Hub-Site konnte nicht dupliziert werden.');
            cms_admin_hub_sites_redirect(cms_admin_hub_sites_post_redirect($redirectBase, $action, $result, $_POST));
    }
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_hub_sites');
$viewAction = (string)($_GET['action'] ?? 'list');
if (!in_array($viewAction, $allowedViews, true)) {
    $viewAction = 'list';
}

if ($viewAction === 'edit') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neue Hub-Site' : 'Hub-Site bearbeiten';
    $activePage = 'hub-sites';
    $pageAssets = [
        'css' => [
            cms_asset_url('suneditor/css/suneditor.min.css'),
            cms_asset_url('css/admin-hub-site-edit.css'),
        ],
        'js' => [
            cms_asset_url('suneditor/suneditor.min.js'),
            cms_asset_url('suneditor/lang/de.js'),
            cms_asset_url('js/admin-hub-site-edit.js'),
        ],
    ];

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/hub/edit.php';
    require __DIR__ . '/partials/footer.php';
} elseif ($viewAction === 'template-edit') {
    $key = isset($_GET['key']) ? (string)$_GET['key'] : null;
    $data = $module->getTemplateEditData($key);
    $pageTitle = $data['isNew'] ? 'Neues Hub-Template' : 'Hub-Template bearbeiten';
    $activePage = 'hub-sites';
    $pageAssets = ['css' => [], 'js' => []];

    $hubTemplateCssPath = rtrim((string)ASSETS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-hub-template-editor.css';
    if (is_file($hubTemplateCssPath)) {
        $pageAssets['css'][] = cms_asset_url('css/admin-hub-template-editor.css');
    }

    $hubTemplateJsPath = rtrim((string)ASSETS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'admin-hub-template-editor.js';
    if (is_file($hubTemplateJsPath)) {
        $pageAssets['js'][] = cms_asset_url('js/admin-hub-template-editor.js');
    }

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/hub/template-edit.php';
    require __DIR__ . '/partials/footer.php';
} elseif ($viewAction === 'templates') {
    $data = $module->getTemplateListData();
    $pageTitle = 'Hub-Site Templates';
    $activePage = 'hub-sites';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/hub/templates.php';
    require __DIR__ . '/partials/footer.php';
} else {
    $data = $module->getListData();
    $pageTitle = 'Hub-Sites';
    $activePage = 'hub-sites';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/hub/list.php';
    require __DIR__ . '/partials/footer.php';
}
