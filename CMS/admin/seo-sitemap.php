<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'sitemap';
$seoRoutePath = '/admin/seo-sitemap';
$seoViewFile = __DIR__ . '/views/seo/sitemap.php';
$pageTitle = 'SEO Sitemap';
$activePage = 'seo-sitemap';

require __DIR__ . '/seo-page.php';
