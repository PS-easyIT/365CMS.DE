<?php
declare(strict_types=1);

/**
 * Firewall – Entry Point
 * Route: /admin/firewall
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

require_once __DIR__ . '/modules/security/FirewallModule.php';
$module = new FirewallModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if (!$module->isSupportedAction($action)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Firewall-Aktion.'];
    } elseif (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_firewall')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $result = ['success' => false, 'error' => 'Firewall-Aktion konnte nicht verarbeitet werden.'];

        switch ($action) {
            case 'save_settings':
                $result = $module->saveSettings($_POST);
                break;
            case 'add_rule':
                $result = $module->addRule($_POST);
                break;
            case 'delete_rule':
                $result = $module->deleteRule((int)($_POST['id'] ?? 0));
                break;
            case 'toggle_rule':
                $result = $module->toggleRule((int)($_POST['id'] ?? 0));
                break;
        }

        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
    }

    header('Location: ' . SITE_URL . '/admin/firewall');
    exit;
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_firewall');
$pageTitle  = 'Firewall';
$activePage = 'firewall';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/security/firewall.php';
require_once __DIR__ . '/partials/footer.php';
