<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$seoSection = 'audit';
$seoRoutePath = '/admin/seo-audit';
$seoViewFile = __DIR__ . '/views/seo/audit.php';
$pageTitle = 'SEO Audit';
$activePage = 'seo-audit';

require __DIR__ . '/seo-page.php';
