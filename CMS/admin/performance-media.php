<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'media',
    'route_path' => '/admin/performance-media',
    'view_file' => __DIR__ . '/views/performance/media.php',
    'page_title' => 'Performance · Medien-Optimierung',
    'active_page' => 'performance-media',
];

require __DIR__ . '/performance-page.php';
