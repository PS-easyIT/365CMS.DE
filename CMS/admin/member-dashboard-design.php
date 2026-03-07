<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'design';
$memberRoutePath = '/admin/member-dashboard-design';
$memberViewFile = __DIR__ . '/views/member/design.php';
$pageTitle = 'Member Dashboard – Design & Farben';
$activePage = 'member-dashboard-design';

require __DIR__ . '/member-dashboard-page.php';
