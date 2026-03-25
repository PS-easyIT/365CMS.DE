<?php
declare(strict_types=1);

/**
 * Performance – Entry Point
 * Route: /admin/performance
 */

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'overview',
    'route_path' => '/admin/performance',
    'view_file' => __DIR__ . '/views/seo/performance.php',
    'page_title' => 'Performance',
    'active_page' => 'performance',
];

require __DIR__ . '/performance-page.php';
