<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'onboarding';
$memberRoutePath = '/admin/member-dashboard-onboarding';
$memberViewFile = __DIR__ . '/views/member/onboarding.php';
$pageTitle = 'Member Dashboard – Onboarding';
$activePage = 'member-dashboard-onboarding';

require __DIR__ . '/member-dashboard-page.php';
