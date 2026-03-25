<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'cache',
    'route_path' => '/admin/performance-cache',
    'view_file' => __DIR__ . '/views/performance/cache.php',
    'page_title' => 'Performance · Cache-Verwaltung',
    'active_page' => 'performance-cache',
];

require __DIR__ . '/performance-page.php';
