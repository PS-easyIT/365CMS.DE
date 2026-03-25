<?php
declare(strict_types=1);

/**
 * Abo-Pakete – Entry Point
 * Route: /admin/packages
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/subscriptions/PackagesModule.php';
$module          = new PackagesModule();
require_once __DIR__ . '/modules/subscriptions/SubscriptionSettingsModule.php';
$settingsModule  = new SubscriptionSettingsModule();
$alert     = null;
$redirectBase = SITE_URL . '/admin/packages';
$allowedActions = [
    'save',
    'seed_defaults',
    'delete',
    'toggle',
    'save_package_settings',
];

// ─── POST-Verarbeitung ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_packages')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . $redirectBase);
        exit;
    } else {
        $action = trim((string)($_POST['action'] ?? ''));
        if (!in_array($action, $allowedActions, true)) {
            $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Aktion.'];
            header('Location: ' . $redirectBase);
            exit;
        }

        switch ($action) {
            case 'save':
                $result = $module->save($_POST);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . $redirectBase);
                exit;

            case 'seed_defaults':
                $result = $module->seedDefaults();
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . $redirectBase);
                exit;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . $redirectBase);
                exit;

            case 'toggle':
                $id = (int)($_POST['id'] ?? 0);
                $result = $module->toggleStatus($id);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . $redirectBase);
                exit;

            case 'save_package_settings':
                $result = $settingsModule->savePackageSettings($_POST);
                $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
                header('Location: ' . $redirectBase);
                exit;
        }
    }
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_packages');
$pageTitle  = 'Pakete & Abo-Einstellungen';
$activePage = 'packages';
$data       = array_merge($module->getData(), $settingsModule->getPackageData());

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/packages.php';
require_once __DIR__ . '/partials/footer.php';
