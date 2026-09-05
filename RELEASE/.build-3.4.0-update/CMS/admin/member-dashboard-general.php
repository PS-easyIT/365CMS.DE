<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'general',
    'route_path' => '/admin/member-dashboard-general',
    'view_file' => __DIR__ . '/views/member/general.php',
    'page_title' => 'Member Dashboard – Allgemein',
    'active_page' => 'member-dashboard-general',
];

require __DIR__ . '/member-dashboard-page.php';
