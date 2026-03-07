<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'dashboard';
$seoRoutePath = '/admin/seo-dashboard';
$seoViewFile = __DIR__ . '/views/seo/dashboard.php';
$pageTitle = 'SEO Dashboard';
$activePage = 'seo-dashboard';

require __DIR__ . '/seo-page.php';
