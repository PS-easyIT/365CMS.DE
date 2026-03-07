<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'widgets';
$memberRoutePath = '/admin/member-dashboard-widgets';
$memberViewFile = __DIR__ . '/views/member/widgets.php';
$pageTitle = 'Member Dashboard – Widgets';
$activePage = 'member-dashboard-widgets';

require __DIR__ . '/member-dashboard-page.php';
