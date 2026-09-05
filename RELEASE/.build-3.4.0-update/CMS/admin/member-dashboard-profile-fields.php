<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberDashboardPageConfig = [
    'section' => 'profile-fields',
    'route_path' => '/admin/member-dashboard-profile-fields',
    'view_file' => __DIR__ . '/views/member/profile-fields.php',
    'page_title' => 'Member Dashboard – Profil-Felder',
    'active_page' => 'member-dashboard-profile-fields',
];

require __DIR__ . '/member-dashboard-page.php';
