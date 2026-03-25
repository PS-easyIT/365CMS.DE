<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'sessions',
    'route_path' => '/admin/performance-sessions',
    'view_file' => __DIR__ . '/views/performance/sessions.php',
    'page_title' => 'Performance · Session-Verwaltung',
    'active_page' => 'performance-sessions',
];

require __DIR__ . '/performance-page.php';
