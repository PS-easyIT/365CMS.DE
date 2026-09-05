<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'meta',
    'route_path' => '/admin/seo-meta',
    'view_file' => __DIR__ . '/views/seo/meta.php',
    'page_title' => 'SEO Meta-Daten',
    'active_page' => 'seo-meta',
];

require __DIR__ . '/seo-page.php';
