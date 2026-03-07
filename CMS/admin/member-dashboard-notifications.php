<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'notifications';
$memberRoutePath = '/admin/member-dashboard-notifications';
$memberViewFile = __DIR__ . '/views/member/notifications.php';
$pageTitle = 'Member Dashboard – Benachrichtigungen';
$activePage = 'member-dashboard-notifications';

require __DIR__ . '/member-dashboard-page.php';
