<?php
declare(strict_types=1);

/**
 * Diagnose Assets – Entry Point
 * Route: /admin/monitor-assets
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = ['section' => 'assets'];

require __DIR__ . '/system-monitor-page.php';