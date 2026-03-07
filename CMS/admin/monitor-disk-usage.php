<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'disk';
$systemRoutePath = '/admin/monitor-disk-usage';
$systemViewFile = __DIR__ . '/views/system/disk-usage.php';
$pageTitle = 'Monitoring · Disk Usage';
$activePage = 'monitor-disk-usage';

require __DIR__ . '/system-monitor-page.php';
