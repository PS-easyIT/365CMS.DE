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
        ['slug' => 'member-dashboard', 'label' => 'Übersicht', 'url' => SITE_URL . '/admin/member-dashboard'],
        ['slug' => 'member-dashboard-general', 'label' => 'Allgemein', 'url' => SITE_URL . '/admin/member-dashboard-general'],
        ['slug' => 'member-dashboard-design', 'label' => 'Design & Farben', 'url' => SITE_URL . '/admin/member-dashboard-design'],
        ['slug' => 'member-dashboard-frontend-modules', 'label' => 'Frontend-Module', 'url' => SITE_URL . '/admin/member-dashboard-frontend-modules'],
        ['slug' => 'member-dashboard-widgets', 'label' => 'Dashboard Widgets', 'url' => SITE_URL . '/admin/member-dashboard-widgets'],
        ['slug' => 'member-dashboard-plugin-widgets', 'label' => 'Plugin-Widgets', 'url' => SITE_URL . '/admin/member-dashboard-plugin-widgets'],
        ['slug' => 'member-dashboard-profile-fields', 'label' => 'Profil-Felder', 'url' => SITE_URL . '/admin/member-dashboard-profile-fields'],
        ['slug' => 'member-dashboard-notifications', 'label' => 'Benachrichtigungen', 'url' => SITE_URL . '/admin/member-dashboard-notifications'],
        ['slug' => 'member-dashboard-onboarding', 'label' => 'Onboarding', 'url' => SITE_URL . '/admin/member-dashboard-onboarding'],
    ],
]];
$currentSectionPage = (string)$currentMemberPage;

require dirname(__DIR__) . '/partials/section-subnav.php';
