<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'plugin-widgets';
$memberRoutePath = '/admin/member-dashboard-plugin-widgets';
$memberViewFile = __DIR__ . '/views/member/plugin-widgets.php';
$pageTitle = 'Member Dashboard – Plugin-Widgets';
$activePage = 'member-dashboard-plugin-widgets';

require __DIR__ . '/member-dashboard-page.php';
