<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

$currentSystemPage = $activePage ?? 'info';
$sectionNavGroups = [
    [
        'label' => 'Info',
        'items' => [
            ['slug' => 'info', 'label' => 'Info CMS', 'url' => SITE_URL . '/admin/info'],
            ['slug' => 'documentation', 'label' => 'Dokumentation', 'url' => SITE_URL . '/admin/documentation'],
        ],
    ],
    [
        'label' => 'Diagnose',
        'items' => [
            ['slug' => 'diagnose', 'label' => 'Diagnose Datenbank', 'url' => SITE_URL . '/admin/diagnose'],
            ['slug' => 'cms-logs', 'label' => 'CMS Logs', 'url' => SITE_URL . '/admin/cms-logs'],
            ['slug' => 'monitor-response-time', 'label' => 'Response-Time', 'url' => SITE_URL . '/admin/monitor-response-time'],
            ['slug' => 'monitor-cron-status', 'label' => 'Cron-Status', 'url' => SITE_URL . '/admin/monitor-cron-status'],
            ['slug' => 'monitor-disk-usage', 'label' => 'Disk-Usage', 'url' => SITE_URL . '/admin/monitor-disk-usage'],
            ['slug' => 'monitor-scheduled-tasks', 'label' => 'Scheduled Tasks', 'url' => SITE_URL . '/admin/monitor-scheduled-tasks'],
            ['slug' => 'monitor-health-check', 'label' => 'Health-Check', 'url' => SITE_URL . '/admin/monitor-health-check'],
            ['slug' => 'monitor-email-alerts', 'label' => 'E-Mail-Benachrichtigungen', 'url' => SITE_URL . '/admin/monitor-email-alerts'],
        ],
    ],
];
$currentSectionPage = (string)$currentSystemPage;

require dirname(__DIR__) . '/partials/section-subnav.php';
