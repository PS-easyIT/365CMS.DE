<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'analytics',
    'route_path' => '/admin/analytics',
    'view_file' => __DIR__ . '/views/seo/analytics.php',
    'page_title' => 'SEO Analytics',
    'active_page' => 'analytics',
];

require __DIR__ . '/seo-page.php';
