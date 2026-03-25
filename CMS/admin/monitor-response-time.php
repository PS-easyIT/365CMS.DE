<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'response-time',
    'route_path' => '/admin/monitor-response-time',
    'view_file' => __DIR__ . '/views/system/response-time.php',
    'page_title' => 'Monitoring · Response Time',
    'active_page' => 'monitor-response-time',
];

require __DIR__ . '/system-monitor-page.php';
