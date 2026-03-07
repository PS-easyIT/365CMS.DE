<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'response-time';
$systemRoutePath = '/admin/monitor-response-time';
$systemViewFile = __DIR__ . '/views/system/response-time.php';
$pageTitle = 'Monitoring · Response Time';
$activePage = 'monitor-response-time';

require __DIR__ . '/system-monitor-page.php';
