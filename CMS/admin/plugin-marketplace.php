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
$redirectUrl = SITE_URL . '/admin/plugin-marketplace';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_plugin_mp')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action !== 'install') {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $result = $module->installPlugin((string)($_POST['slug'] ?? ''));
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
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
