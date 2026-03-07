<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'analytics';
$seoRoutePath = '/admin/analytics';
$seoViewFile = __DIR__ . '/views/seo/analytics.php';
$pageTitle = 'SEO Analytics';
$activePage = 'analytics';

require __DIR__ . '/seo-page.php';
