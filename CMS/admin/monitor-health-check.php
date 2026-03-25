<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'health-check',
    'route_path' => '/admin/monitor-health-check',
    'view_file' => __DIR__ . '/views/system/health-check.php',
    'page_title' => 'Monitoring · Health Check',
    'active_page' => 'monitor-health-check',
];

require __DIR__ . '/system-monitor-page.php';
