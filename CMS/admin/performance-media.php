<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection = 'media';
$performanceRoutePath = '/admin/performance-media';
$performanceViewFile = __DIR__ . '/views/performance/media.php';
$pageTitle = 'Performance · Medien-Optimierung';
$activePage = 'performance-media';

require __DIR__ . '/performance-page.php';
