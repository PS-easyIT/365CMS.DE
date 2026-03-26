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

const CMS_ADMIN_MEMBER_DASHBOARD_READ_CAPABILITIES = ['manage_settings', 'manage_users'];
const CMS_ADMIN_MEMBER_DASHBOARD_LEGACY_ROUTES = [
    'general' => '/admin/member-dashboard-general',
    'design' => '/admin/member-dashboard-design',
    'frontend-modules' => '/admin/member-dashboard-frontend-modules',
    'widgets' => '/admin/member-dashboard-widgets',
    'plugin-widgets' => '/admin/member-dashboard-plugin-widgets',
    'profile-fields' => '/admin/member-dashboard-profile-fields',
    'notifications' => '/admin/member-dashboard-notifications',
    'onboarding' => '/admin/member-dashboard-onboarding',
];

function cms_admin_member_dashboard_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

function cms_admin_member_dashboard_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_member_dashboard_can_access_overview(): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_member_dashboard_has_any_capability(CMS_ADMIN_MEMBER_DASHBOARD_READ_CAPABILITIES);
}

function cms_admin_member_dashboard_normalize_legacy_section(mixed $section): string
{
    $section = strtolower(trim((string) $section));

    if ($section === '' || $section === 'overview') {
        return 'overview';
    }

    return isset(CMS_ADMIN_MEMBER_DASHBOARD_LEGACY_ROUTES[$section]) ? $section : 'overview';
}

$legacySection = cms_admin_member_dashboard_normalize_legacy_section($_GET['section'] ?? 'overview');

if ($legacySection !== '' && $legacySection !== 'overview') {
    if (isset(CMS_ADMIN_MEMBER_DASHBOARD_LEGACY_ROUTES[$legacySection])) {
        cms_admin_member_dashboard_redirect(SITE_URL . CMS_ADMIN_MEMBER_DASHBOARD_LEGACY_ROUTES[$legacySection]);
    }
}

if (!cms_admin_member_dashboard_can_access_overview()) {
    cms_admin_member_dashboard_redirect(SITE_URL);
}

$memberSection = 'overview';
$memberRoutePath = '/admin/member-dashboard';
$memberViewFile = __DIR__ . '/views/member/dashboard.php';
$pageTitle  = 'Member Dashboard';
$activePage = 'member-dashboard';

require __DIR__ . '/member-dashboard-page.php';
