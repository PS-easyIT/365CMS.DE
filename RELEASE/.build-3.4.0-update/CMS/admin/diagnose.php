<?php
declare(strict_types=1);

/**
 * Diagnose – Entry Point
 * Route: /admin/diagnose
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemMonitorPageConfig = ['section' => 'diagnose'];

require __DIR__ . '/system-monitor-page.php';
