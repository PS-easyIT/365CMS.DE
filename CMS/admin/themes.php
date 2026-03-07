<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Themes – Entry Point
 * Route: /admin/themes
 */

use CMS\Auth;
use CMS\Security;
use CMS\ThemeManager;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemesModule.php';
$module    = new ThemesModule();
$alert     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';
    if (!Security::instance()->verifyToken($postToken, 'admin_themes')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/themes');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'activate') {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['theme'] ?? '');
        $result = $module->activateTheme($slug);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    } elseif ($action === 'delete') {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['theme'] ?? '');
        $result = $module->deleteTheme($slug);
        $_SESSION['admin_alert'] = [
            'type'    => $result['success'] ? 'success' : 'danger',
            'message' => $result['message'] ?? $result['error'] ?? '',
        ];
    }

    header('Location: ' . SITE_URL . '/admin/themes');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_themes');
$data       = $module->getData();
$pageTitle  = 'Themes';
$activePage = 'themes';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/themes/list.php';
require __DIR__ . '/partials/footer.php';
