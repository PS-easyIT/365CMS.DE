<?php
declare(strict_types=1);

/**
 * Info – Entry Point
 * Route: /admin/info
 */

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'info';
$systemRoutePath = '/admin/info';
$systemViewFile = __DIR__ . '/views/system/info.php';
$pageTitle = 'Info';
$activePage = 'info';

require __DIR__ . '/system-monitor-page.php';
