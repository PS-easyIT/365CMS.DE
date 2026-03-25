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

function cms_admin_member_dashboard_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

$legacySection = (string)($_GET['section'] ?? 'overview');

if ($legacySection !== '' && $legacySection !== 'overview') {
    $legacyRoutes = [
        'general'        => '/admin/member-dashboard-general',
        'design'         => '/admin/member-dashboard-design',
        'frontend-modules' => '/admin/member-dashboard-frontend-modules',
        'widgets'        => '/admin/member-dashboard-widgets',
        'plugin-widgets' => '/admin/member-dashboard-plugin-widgets',
        'profile-fields' => '/admin/member-dashboard-profile-fields',
        'notifications'  => '/admin/member-dashboard-notifications',
        'onboarding'     => '/admin/member-dashboard-onboarding',
    ];

    if (isset($legacyRoutes[$legacySection])) {
        cms_admin_member_dashboard_redirect(SITE_URL . $legacyRoutes[$legacySection]);
    }
}

if (!Auth::instance()->isAdmin()) {
    cms_admin_member_dashboard_redirect(SITE_URL);
}

$memberSection = 'overview';
$memberRoutePath = '/admin/member-dashboard';
$memberViewFile = __DIR__ . '/views/member/dashboard.php';
$pageTitle  = 'Member Dashboard';
$activePage = 'member-dashboard';

require __DIR__ . '/member-dashboard-page.php';
