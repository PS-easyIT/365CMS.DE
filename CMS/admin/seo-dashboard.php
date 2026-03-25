<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'dashboard',
    'route_path' => '/admin/seo-dashboard',
    'view_file' => __DIR__ . '/views/seo/dashboard.php',
    'page_title' => 'SEO Dashboard',
    'active_page' => 'seo-dashboard',
];

require __DIR__ . '/seo-page.php';
