<?php
declare(strict_types=1);

/**
 * Admin Dashboard – Entry Point
 *
 * Route: /admin
 * Loads: DashboardModule → views/dashboard/index.php
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

$sectionPageConfig = [
    'section' => 'overview',
    'route_path' => '/admin',
    'view_file' => __DIR__ . '/views/dashboard/index.php',
    'page_title' => 'Dashboard',
    'active_page' => 'dashboard',
    'page_assets' => [
        'css' => [],
        'js' => [],
    ],
    'csrf_action' => 'admin_dashboard',
    'guard_constant' => '',
    'module_file' => __DIR__ . '/modules/dashboard/DashboardModule.php',
    'module_factory' => static function (): DashboardModule {
        return new DashboardModule();
    },
    'data_loader' => static function ($module): array {
        return $module instanceof DashboardModule ? $module->getData() : [];
    },
    'access_checker' => static function (): bool {
        return Auth::instance()->isAdmin();
    },
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte Dashboard-Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
