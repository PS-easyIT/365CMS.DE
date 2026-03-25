<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'design',
    'route_path' => '/admin/member-dashboard-design',
    'view_file' => __DIR__ . '/views/member/design.php',
    'page_title' => 'Member Dashboard – Design & Farben',
    'active_page' => 'member-dashboard-design',
];

require __DIR__ . '/member-dashboard-page.php';
