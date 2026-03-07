<?php
declare(strict_types=1);

/**
 * Info – Entry Point
 * Route: /admin/info
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

require_once __DIR__ . '/modules/system/SystemInfoModule.php';
$module = new SystemInfoModule();
$alert = null;

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_system_info');
$data       = $module->getInfoData();
$pageTitle  = 'Info';
$activePage = 'info';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/system/info.php';
require __DIR__ . '/partials/footer.php';
