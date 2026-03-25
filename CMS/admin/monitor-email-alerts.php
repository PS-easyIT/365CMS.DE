<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = [
    'section' => 'email-alerts',
    'route_path' => '/admin/monitor-email-alerts',
    'view_file' => __DIR__ . '/views/system/email-alerts.php',
    'page_title' => 'Monitoring · E-Mail-Benachrichtigungen',
    'active_page' => 'monitor-email-alerts',
];

require __DIR__ . '/system-monitor-page.php';
