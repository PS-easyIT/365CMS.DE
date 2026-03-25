<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'cron',
    'route_path' => '/admin/monitor-cron-status',
    'view_file' => __DIR__ . '/views/system/cron-status.php',
    'page_title' => 'Monitoring · Cron-Status',
    'active_page' => 'monitor-cron-status',
];

require __DIR__ . '/system-monitor-page.php';
