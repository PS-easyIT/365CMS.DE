<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'disk',
    'route_path' => '/admin/monitor-disk-usage',
    'view_file' => __DIR__ . '/views/system/disk-usage.php',
    'page_title' => 'Monitoring · Disk Usage',
    'active_page' => 'monitor-disk-usage',
];

require __DIR__ . '/system-monitor-page.php';
