<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection = 'settings';
$performanceRoutePath = '/admin/performance-settings';
$performanceViewFile = __DIR__ . '/views/performance/settings.php';
$pageTitle = 'Performance · Einstellungen';
$activePage = 'performance-settings';

require __DIR__ . '/performance-page.php';
