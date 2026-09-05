<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'plugin-widgets',
    'route_path' => '/admin/member-dashboard-plugin-widgets',
    'view_file' => __DIR__ . '/views/member/plugin-widgets.php',
    'page_title' => 'Member Dashboard – Plugin-Widgets',
    'active_page' => 'member-dashboard-plugin-widgets',
];

require __DIR__ . '/member-dashboard-page.php';
