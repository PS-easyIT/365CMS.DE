<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'health-check';
$systemRoutePath = '/admin/monitor-health-check';
$systemViewFile = __DIR__ . '/views/system/health-check.php';
$pageTitle = 'Monitoring · Health Check';
$activePage = 'monitor-health-check';

require __DIR__ . '/system-monitor-page.php';
