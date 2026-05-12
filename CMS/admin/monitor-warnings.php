<?php
declare(strict_types=1);

/**
 * Warnzentrale – Entry Point
 * Route: /admin/monitor-warnings
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = ['section' => 'warnings'];

require __DIR__ . '/system-monitor-page.php';