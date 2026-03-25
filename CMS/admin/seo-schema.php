<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'schema',
    'route_path' => '/admin/seo-schema',
    'view_file' => __DIR__ . '/views/seo/schema.php',
    'page_title' => 'SEO Strukturierte Daten',
    'active_page' => 'seo-schema',
];

require __DIR__ . '/seo-page.php';
