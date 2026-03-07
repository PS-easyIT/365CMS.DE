<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$memberSection = 'profile-fields';
$memberRoutePath = '/admin/member-dashboard-profile-fields';
$memberViewFile = __DIR__ . '/views/member/profile-fields.php';
$pageTitle = 'Member Dashboard – Profil-Felder';
$activePage = 'member-dashboard-profile-fields';

require __DIR__ . '/member-dashboard-page.php';
