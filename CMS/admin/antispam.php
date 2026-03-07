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
$module    = new AntispamModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_antispam')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
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
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/antispam');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_antispam');
}

if (isset($_SESSION['admin_alert'])) {
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
