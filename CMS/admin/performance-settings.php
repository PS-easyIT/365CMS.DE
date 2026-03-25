<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'settings',
    'route_path' => '/admin/performance-settings',
    'view_file' => __DIR__ . '/views/performance/settings.php',
    'page_title' => 'Performance · Einstellungen',
    'active_page' => 'performance-settings',
];

require __DIR__ . '/performance-page.php';
