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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_hub_sites')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/hub-sites');
        exit;
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST);
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if (!empty($result['success'])) {
                if (!empty($_POST['open_public_after_save']) && !empty($result['slug'])) {
                    header('Location: ' . SITE_URL . '/' . ltrim((string)$result['slug'], '/'));
                    exit;
                }

                header('Location: ' . SITE_URL . '/admin/hub-sites?action=edit&id=' . (int)($result['id'] ?? 0));
            } else {
                header('Location: ' . SITE_URL . '/admin/hub-sites');
            }
            exit;

        case 'save-template':
            $result = $module->saveTemplate($_POST);
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if (!empty($result['success'])) {
                header('Location: ' . SITE_URL . '/admin/hub-sites?action=template-edit&key=' . rawurlencode((string)($result['key'] ?? '')));
            } else {
                header('Location: ' . SITE_URL . '/admin/hub-sites?action=templates');
            }
            exit;

        case 'duplicate-template':
            $result = $module->duplicateTemplate((string)($_POST['key'] ?? ''));
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if (!empty($result['success'])) {
                header('Location: ' . SITE_URL . '/admin/hub-sites?action=template-edit&key=' . rawurlencode((string)($result['key'] ?? '')));
            } else {
                header('Location: ' . SITE_URL . '/admin/hub-sites?action=templates');
            }
            exit;

        case 'delete-template':
            $result = $module->deleteTemplate((string)($_POST['key'] ?? ''));
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/hub-sites?action=templates');
            exit;

        case 'delete':
            $result = $module->delete((int)($_POST['id'] ?? 0));
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/hub-sites');
            exit;

        case 'duplicate':
            $result = $module->duplicate((int)($_POST['id'] ?? 0));
            $_SESSION['admin_alert'] = [
                'type' => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/hub-sites');
            exit;
    }
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_hub_sites');
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'edit') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neue Hub-Site' : 'Hub-Site bearbeiten';
    $activePage = 'hub-sites';

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
