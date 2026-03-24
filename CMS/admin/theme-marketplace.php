<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Marketplace – Entry Point
 * Route: /admin/theme-marketplace
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemeMarketplaceModule.php';
$module    = new ThemeMarketplaceModule();
$alert     = null;
$redirectUrl = SITE_URL . '/admin/theme-marketplace';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    if (!Security::instance()->verifyToken($postToken, 'admin_theme_marketplace')) {
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

    $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['theme'] ?? ''));
    $result = $module->installTheme($slug);
    $_SESSION['admin_alert'] = [
        'type'    => !empty($result['success']) ? 'success' : 'danger',
        'message' => $result['message'] ?? $result['error'] ?? 'Unbekannte Antwort.',
    ];

    header('Location: ' . $redirectUrl);
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_theme_marketplace');
$data       = $module->getData();
$pageTitle  = 'Theme Marketplace';
$activePage = 'theme-marketplace';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/marketplace.php';
require __DIR__ . '/partials/footer.php';
