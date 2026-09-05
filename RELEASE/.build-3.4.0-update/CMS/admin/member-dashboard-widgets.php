<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'widgets',
    'route_path' => '/admin/member-dashboard-widgets',
    'view_file' => __DIR__ . '/views/member/widgets.php',
    'page_title' => 'Member Dashboard – Widgets',
    'active_page' => 'member-dashboard-widgets',
];

require __DIR__ . '/member-dashboard-page.php';
