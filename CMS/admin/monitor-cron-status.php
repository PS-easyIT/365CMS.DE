<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'cron';
$systemRoutePath = '/admin/monitor-cron-status';
$systemViewFile = __DIR__ . '/views/system/cron-status.php';
$pageTitle = 'Monitoring · Cron-Status';
$activePage = 'monitor-cron-status';

require __DIR__ . '/system-monitor-page.php';
