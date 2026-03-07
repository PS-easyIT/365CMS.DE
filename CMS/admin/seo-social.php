<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'social';
$seoRoutePath = '/admin/seo-social';
$seoViewFile = __DIR__ . '/views/seo/social.php';
$pageTitle = 'SEO Social';
$activePage = 'seo-social';

require __DIR__ . '/seo-page.php';
