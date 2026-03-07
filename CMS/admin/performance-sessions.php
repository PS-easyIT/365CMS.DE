<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$performanceSection = 'sessions';
$performanceRoutePath = '/admin/performance-sessions';
$performanceViewFile = __DIR__ . '/views/performance/sessions.php';
$pageTitle = 'Performance · Session-Verwaltung';
$activePage = 'performance-sessions';

require __DIR__ . '/performance-page.php';
