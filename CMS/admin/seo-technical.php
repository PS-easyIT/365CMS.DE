<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'technical';
$seoRoutePath = '/admin/seo-technical';
$seoViewFile = __DIR__ . '/views/seo/technical.php';
$pageTitle = 'Technisches SEO';
$activePage = 'seo-technical';

require __DIR__ . '/seo-page.php';
