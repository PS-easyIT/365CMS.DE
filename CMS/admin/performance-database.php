<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = [
    'section' => 'database',
    'route_path' => '/admin/performance-database',
    'view_file' => __DIR__ . '/views/performance/database.php',
    'page_title' => 'Performance · Datenbank-Wartung',
    'active_page' => 'performance-database',
];

require __DIR__ . '/performance-page.php';
