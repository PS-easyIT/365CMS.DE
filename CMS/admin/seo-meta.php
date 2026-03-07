<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'meta';
$seoRoutePath = '/admin/seo-meta';
$seoViewFile = __DIR__ . '/views/seo/meta.php';
$pageTitle = 'SEO Meta-Daten';
$activePage = 'seo-meta';

require __DIR__ . '/seo-page.php';
