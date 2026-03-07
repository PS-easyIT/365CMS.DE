<?php
declare(strict_types=1);

/**
 * Plugin Marketplace – Entry Point
 * Route: /admin/plugin-marketplace
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

require_once __DIR__ . '/modules/plugins/PluginMarketplaceModule.php';
$module    = new PluginMarketplaceModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_plugin_mp')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $action = $_POST['action'] ?? '';
        switch ($action) {
            case 'install':
                $result = $module->installPlugin($_POST['slug'] ?? '');
                break;
            default:
                $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/plugin-marketplace');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_plugin_mp');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_plugin_mp');
$pageTitle  = 'Plugin Marketplace';
$activePage = 'plugin-marketplace';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/plugins/marketplace.php';
require_once __DIR__ . '/partials/footer.php';
