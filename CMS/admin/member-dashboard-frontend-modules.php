<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'frontend-modules';
$memberRoutePath = '/admin/member-dashboard-frontend-modules';
$memberViewFile = __DIR__ . '/views/member/frontend-modules.php';
$pageTitle = 'Member Dashboard – Frontend-Module';
$activePage = 'member-dashboard-frontend-modules';

require __DIR__ . '/member-dashboard-page.php';
