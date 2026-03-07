<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'scheduled-tasks';
$systemRoutePath = '/admin/monitor-scheduled-tasks';
$systemViewFile = __DIR__ . '/views/system/scheduled-tasks.php';
$pageTitle = 'Monitoring · Scheduled Tasks';
$activePage = 'monitor-scheduled-tasks';

require __DIR__ . '/system-monitor-page.php';
