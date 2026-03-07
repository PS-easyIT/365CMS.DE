<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection = 'cache';
$performanceRoutePath = '/admin/performance-cache';
$performanceViewFile = __DIR__ . '/views/performance/cache.php';
$pageTitle = 'Performance · Cache-Verwaltung';
$activePage = 'performance-cache';

require __DIR__ . '/performance-page.php';
