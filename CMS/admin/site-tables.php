<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site Tables – Entry Point
 * Route: /admin/site-tables
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/tables/TablesModule.php';
$module    = new TablesModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_tables')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/site-tables');
        exit;
    }

    switch ($action) {
        case 'save_settings':
            $result = $module->saveDisplaySettings($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/site-tables?action=settings');
            exit;

        case 'save':
            $result = $module->save($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if ($result['success']) {
                header('Location: ' . SITE_URL . '/admin/site-tables?action=edit&id=' . ($result['id'] ?? 0));
            } else {
                header('Location: ' . SITE_URL . '/admin/site-tables');
            }
            exit;

        case 'delete':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->delete($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/site-tables');
            exit;

        case 'duplicate':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->duplicate($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/site-tables');
            exit;
    }
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_tables');

// ─── View-Routing ────────────────────────────────────────
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'settings') {
    $data       = $module->getSettingsData();
    $pageTitle  = 'Tabellen-Einstellungen';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/settings.php';
    require __DIR__ . '/partials/footer.php';
} elseif ($viewAction === 'edit') {
    $id        = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data      = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neue Tabelle' : 'Tabelle bearbeiten';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/edit.php';
    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Tabellen';
    $activePage = 'site-tables';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/tables/list.php';
    require __DIR__ . '/partials/footer.php';
}
