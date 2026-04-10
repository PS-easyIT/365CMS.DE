<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_PERFORMANCE_VIEW')) {
    exit;
}

$currentPerformancePage = $activePage ?? 'performance';
$sectionNavGroups = [[
    'items' => [
        ['slug' => 'performance', 'label' => 'Übersicht', 'url' => '/admin/performance'],
        ['slug' => 'performance-cache', 'label' => 'Cache-Verwaltung', 'url' => '/admin/performance-cache'],
        ['slug' => 'performance-media', 'label' => 'Medien-Optimierung', 'url' => '/admin/performance-media'],
        ['slug' => 'performance-database', 'label' => 'Datenbank-Wartung', 'url' => '/admin/performance-database'],
        ['slug' => 'performance-settings', 'label' => 'Performance-Einstellungen', 'url' => '/admin/performance-settings'],
        ['slug' => 'performance-sessions', 'label' => 'Session-Verwaltung', 'url' => '/admin/performance-sessions'],
    ],
]];
$currentSectionPage = (string)$currentPerformancePage;

require dirname(__DIR__) . '/partials/section-subnav.php';
