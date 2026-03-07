<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$systemSection = 'email-alerts';
$systemRoutePath = '/admin/monitor-email-alerts';
$systemViewFile = __DIR__ . '/views/system/email-alerts.php';
$pageTitle = 'Monitoring · E-Mail-Benachrichtigungen';
$activePage = 'monitor-email-alerts';

require __DIR__ . '/system-monitor-page.php';
