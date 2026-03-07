<?php
declare(strict_types=1);

/**
 * Analytics Übersicht – Entry Point
 * Route: /admin/analytics
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

require_once __DIR__ . '/modules/seo/AnalyticsModule.php';
$module    = new AnalyticsModule();
$csrfToken = Security::instance()->generateToken('admin_analytics');
$alert     = null;

if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$pageTitle  = 'Analytics Übersicht';
$activePage = 'analytics';
$data       = $module->getData();

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/seo/analytics.php';
require_once __DIR__ . '/partials/footer.php';
