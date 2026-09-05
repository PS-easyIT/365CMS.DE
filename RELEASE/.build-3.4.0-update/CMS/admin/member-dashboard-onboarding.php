<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'onboarding',
    'route_path' => '/admin/member-dashboard-onboarding',
    'view_file' => __DIR__ . '/views/member/onboarding.php',
    'page_title' => 'Member Dashboard – Onboarding',
    'active_page' => 'member-dashboard-onboarding',
];

require __DIR__ . '/member-dashboard-page.php';
