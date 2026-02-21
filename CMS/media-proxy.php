<?php
// ULTRA-SIMPLE PROXY REPLACEMENT
// This file replaces the previous version entirely to fix syntax errors and simplify.

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Do not output errors as HTML

$log_file = __DIR__ . '/media_debug.log';

function debug_log($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

debug_log("PROXY START");

try {
    // 1. Core definitions
    if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
    if (!defined('CORE_PATH')) define('CORE_PATH', ABSPATH . 'core/');
    if (!defined('CMS_AJAX_REQUEST')) define('CMS_AJAX_REQUEST', true);

    // 2. Load Config
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
    } else {
        throw new Exception("Config file not found");
    }

    // 3. Fallback constants if config missed them
    if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', ABSPATH . 'uploads/');
    if (!defined('UPLOAD_URL')) define('UPLOAD_URL', '/uploads/');

    // 4. Session Handling
    if (session_status() === PHP_SESSION_NONE) {
        // Try to respect settings, but prioritize starting success
        ini_set('session.cookie_httponly', '1');
        @session_start();
        debug_log("Session started: " . session_id());
    }

    // 5. Autoloader
    if (file_exists(CORE_PATH . 'autoload.php')) {
        require_once CORE_PATH . 'autoload.php';
    } else {
        throw new Exception("Autoloader not found at " . CORE_PATH);
    }

    // 6. Include Logic
    $ajax_handler = __DIR__ . '/member/media-ajax.php';
    if (file_exists($ajax_handler)) {
        debug_log("Delegating to $ajax_handler");
        require_once $ajax_handler;
    } else {
        throw new Exception("Handler file missing: $ajax_handler");
    }

} catch (Throwable $e) {
    debug_log("FATAL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
