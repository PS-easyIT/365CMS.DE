<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'notifications',
    'route_path' => '/admin/member-dashboard-notifications',
    'view_file' => __DIR__ . '/views/member/notifications.php',
    'page_title' => 'Member Dashboard – Benachrichtigungen',
    'active_page' => 'member-dashboard-notifications',
];

require __DIR__ . '/member-dashboard-page.php';
