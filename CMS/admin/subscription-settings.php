<?php
declare(strict_types=1);

/**
 * Abo-Einstellungen – Entry Point
 * Route: /admin/subscription-settings
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

require_once __DIR__ . '/modules/subscriptions/SubscriptionSettingsModule.php';
$module    = new SubscriptionSettingsModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_sub_settings')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
    } else {
        $result = $module->saveSettings($_POST);
        $_SESSION['admin_alert'] = ['type' => $result['success'] ? 'success' : 'danger', 'message' => $result['message'] ?? $result['error'] ?? ''];
        header('Location: ' . SITE_URL . '/admin/subscription-settings');
        exit;
    }
    $csrfToken = Security::instance()->generateToken('admin_sub_settings');
}

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_sub_settings');
$pageTitle  = 'Abo-Einstellungen';
$activePage = 'subscription-settings';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/subscriptions/settings.php';
require_once __DIR__ . '/partials/footer.php';
