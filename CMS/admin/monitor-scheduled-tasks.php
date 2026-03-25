<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'scheduled-tasks',
    'route_path' => '/admin/monitor-scheduled-tasks',
    'view_file' => __DIR__ . '/views/system/scheduled-tasks.php',
    'page_title' => 'Monitoring · Scheduled Tasks',
    'active_page' => 'monitor-scheduled-tasks',
];

require __DIR__ . '/system-monitor-page.php';
