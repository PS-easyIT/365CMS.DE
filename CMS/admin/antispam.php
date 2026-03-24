<?php
declare(strict_types=1);

/**
 * AntiSpam – Entry Point
 * Route: /admin/antispam
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

require_once __DIR__ . '/modules/security/AntispamModule.php';
$module      = new AntispamModule();
$alert       = null;
$redirectUrl = SITE_URL . '/admin/antispam';
$allowedActions = [
    'save_settings',
    'add_blacklist',
    'delete_blacklist',
];

$storeAlert = static function (array $result): void {
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_antispam')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    if (!in_array($action, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    switch ($action) {
        case 'save_settings':
            $result = $module->saveSettings($_POST);
            break;
        case 'add_blacklist':
            $result = $module->addBlacklist($_POST);
            break;
        case 'delete_blacklist':
            $result = $module->deleteBlacklist((int)($_POST['id'] ?? 0));
            break;
        default:
            $result = ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
            break;
    }

    $storeAlert($result);
    header('Location: ' . $redirectUrl);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_antispam');
$pageTitle  = 'AntiSpam';
$activePage = 'antispam';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/antispam.php';
require_once __DIR__ . '/partials/footer.php';
