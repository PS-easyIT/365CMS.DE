<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$currentMemberPage = $activePage ?? 'member-dashboard';
$sectionNavGroups = [[
    'items' => [
        ['slug' => 'member-dashboard', 'label' => 'Übersicht', 'url' => '/admin/member-dashboard'],
        ['slug' => 'member-dashboard-general', 'label' => 'Allgemein', 'url' => '/admin/member-dashboard-general'],
        ['slug' => 'member-dashboard-design', 'label' => 'Design & Farben', 'url' => '/admin/member-dashboard-design'],
        ['slug' => 'member-dashboard-frontend-modules', 'label' => 'Frontend-Module', 'url' => '/admin/member-dashboard-frontend-modules'],
        ['slug' => 'member-dashboard-widgets', 'label' => 'Dashboard Widgets', 'url' => '/admin/member-dashboard-widgets'],
        ['slug' => 'member-dashboard-plugin-widgets', 'label' => 'Plugin-Widgets', 'url' => '/admin/member-dashboard-plugin-widgets'],
        ['slug' => 'member-dashboard-profile-fields', 'label' => 'Profil-Felder', 'url' => '/admin/member-dashboard-profile-fields'],
        ['slug' => 'member-dashboard-notifications', 'label' => 'Benachrichtigungen', 'url' => '/admin/member-dashboard-notifications'],
        ['slug' => 'member-dashboard-onboarding', 'label' => 'Onboarding', 'url' => '/admin/member-dashboard-onboarding'],
    ],
]];
$currentSectionPage = (string)$currentMemberPage;

require dirname(__DIR__) . '/partials/section-subnav.php';
