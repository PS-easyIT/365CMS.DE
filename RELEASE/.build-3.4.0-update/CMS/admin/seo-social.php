<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'social',
    'route_path' => '/admin/seo-social',
    'view_file' => __DIR__ . '/views/seo/social.php',
    'page_title' => 'SEO Social',
    'active_page' => 'seo-social',
];

require __DIR__ . '/seo-page.php';
