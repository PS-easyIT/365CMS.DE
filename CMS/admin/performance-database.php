<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection = 'database';
$performanceRoutePath = '/admin/performance-database';
$performanceViewFile = __DIR__ . '/views/performance/database.php';
$pageTitle = 'Performance · Datenbank-Wartung';
$activePage = 'performance-database';

require __DIR__ . '/performance-page.php';
