<?php
declare(strict_types=1);

/**
 * Performance – Entry Point
 * Route: /admin/performance
 */

if (!defined('ABSPATH')) {
    exit;
}

$performancePageConfig = ['section' => 'overview'];

require __DIR__ . '/performance-page.php';
