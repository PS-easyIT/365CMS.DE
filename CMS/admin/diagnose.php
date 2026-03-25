<?php
declare(strict_types=1);

/**
 * Diagnose – Entry Point
 * Route: /admin/diagnose
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'diagnose',
    'route_path' => '/admin/diagnose',
    'view_file' => __DIR__ . '/views/system/diagnose.php',
    'page_title' => 'Diagnose',
    'active_page' => 'diagnose',
];

require __DIR__ . '/system-monitor-page.php';
