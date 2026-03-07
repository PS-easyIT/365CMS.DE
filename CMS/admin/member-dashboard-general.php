<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'general';
$memberRoutePath = '/admin/member-dashboard-general';
$memberViewFile = __DIR__ . '/views/member/general.php';
$pageTitle = 'Member Dashboard – Allgemein';
$activePage = 'member-dashboard-general';

require __DIR__ . '/member-dashboard-page.php';
