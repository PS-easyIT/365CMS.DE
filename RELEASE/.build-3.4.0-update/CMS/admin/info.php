<?php
declare(strict_types=1);

/**
 * Info – Entry Point
 * Route: /admin/info
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = ['section' => 'info'];

require __DIR__ . '/system-monitor-page.php';
