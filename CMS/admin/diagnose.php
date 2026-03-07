<?php
declare(strict_types=1);

/**
 * Diagnose – Entry Point
 * Route: /admin/diagnose
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'diagnose';
$systemRoutePath = '/admin/diagnose';
$systemViewFile = __DIR__ . '/views/system/diagnose.php';
$pageTitle = 'Diagnose';
$activePage = 'diagnose';

require __DIR__ . '/system-monitor-page.php';
