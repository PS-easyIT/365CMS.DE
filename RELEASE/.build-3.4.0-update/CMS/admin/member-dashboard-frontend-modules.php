<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'frontend-modules',
    'route_path' => '/admin/member-dashboard-frontend-modules',
    'view_file' => __DIR__ . '/views/member/frontend-modules.php',
    'page_title' => 'Member Dashboard – Frontend-Module',
    'active_page' => 'member-dashboard-frontend-modules',
];

require __DIR__ . '/member-dashboard-page.php';
