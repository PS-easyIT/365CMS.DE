<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member Dashboard Konfiguration – Entry Point
 * Route: /admin/member-dashboard
 */

use CMS\Auth;
use CMS\Security;

$legacySection = (string)($_GET['section'] ?? 'overview');

if ($legacySection !== '' && $legacySection !== 'overview') {
    $legacyRoutes = [
        'general'        => '/admin/member-dashboard-general',
        'widgets'        => '/admin/member-dashboard-widgets',
        'profile-fields' => '/admin/member-dashboard-profile-fields',
    ];

    if (isset($legacyRoutes[$legacySection])) {
        header('Location: ' . SITE_URL . $legacyRoutes[$legacySection]);
        exit;
    }
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$memberSection = 'overview';
$memberRoutePath = '/admin/member-dashboard';
$memberViewFile = __DIR__ . '/views/member/dashboard.php';
$pageTitle  = 'Member Dashboard';
$activePage = 'member-dashboard';

require __DIR__ . '/member-dashboard-page.php';
