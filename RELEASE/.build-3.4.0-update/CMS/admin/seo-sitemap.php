<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'sitemap',
    'route_path' => '/admin/seo-sitemap',
    'view_file' => __DIR__ . '/views/seo/sitemap.php',
    'page_title' => 'SEO Sitemap',
    'active_page' => 'seo-sitemap',
];

require __DIR__ . '/seo-page.php';
