<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'technical',
    'route_path' => '/admin/seo-technical',
    'view_file' => __DIR__ . '/views/seo/technical.php',
    'page_title' => 'Technisches SEO',
    'active_page' => 'seo-technical',
];

require __DIR__ . '/seo-page.php';
