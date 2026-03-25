<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoPageConfig = [
    'section' => 'audit',
    'route_path' => '/admin/seo-audit',
    'view_file' => __DIR__ . '/views/seo/audit.php',
    'page_title' => 'SEO Audit',
    'active_page' => 'seo-audit',
];

require __DIR__ . '/seo-page.php';
