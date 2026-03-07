<?php
declare(strict_types=1);

/**
 * Performance – Entry Point
 * Route: /admin/performance
 */

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection   = 'overview';
$performanceRoutePath = '/admin/performance';
$performanceViewFile  = __DIR__ . '/views/seo/performance.php';
$pageTitle            = 'Performance';
$activePage           = 'performance';

require __DIR__ . '/performance-page.php';
