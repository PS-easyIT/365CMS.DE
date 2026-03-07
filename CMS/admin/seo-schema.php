<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'schema';
$seoRoutePath = '/admin/seo-schema';
$seoViewFile = __DIR__ . '/views/seo/schema.php';
$pageTitle = 'SEO Strukturierte Daten';
$activePage = 'seo-schema';

require __DIR__ . '/seo-page.php';
