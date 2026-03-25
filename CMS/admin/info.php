<?php
declare(strict_types=1);

/**
 * Info – Entry Point
 * Route: /admin/info
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'info',
    'route_path' => '/admin/info',
    'view_file' => __DIR__ . '/views/system/info.php',
    'page_title' => 'Info',
    'active_page' => 'info',
];

require __DIR__ . '/system-monitor-page.php';
