<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Support & Dokumentation – Entry Point
 * Route: /admin/support
 */

use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/system/SupportModule.php';
$module = new SupportModule();

$data       = $module->getData();
$pageTitle  = 'Support & Dokumentation';
$activePage = 'support';
$pageAssets = [];

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/system/support.php';
require __DIR__ . '/partials/footer.php';
