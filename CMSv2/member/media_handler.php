<?php
/**
 * Standalone Media Handler
 * bypasses routing complications.
 */
define('CMS_AJAX_REQUEST', true);

// Try to find config
$possible_paths = [
    dirname(__DIR__) . '/config.php',
    dirname(dirname(__DIR__)) . '/config.php'
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        // Start Session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        require_once $path;
        break;
    }
}

if (!defined('ABSPATH')) {
    // Fallback constants
    define('ABSPATH', dirname(__DIR__) . '/');
    define('CORE_PATH', ABSPATH . 'core/');
    define('UPLOAD_PATH', ABSPATH . 'uploads/');
}

// Load Autoloader
if (file_exists(CORE_PATH . 'autoload.php')) {
    require_once CORE_PATH . 'autoload.php';
}

// Delegate to the actual logic
require_once __DIR__ . '/media-ajax.php';
