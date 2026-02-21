<?php
/**
 * 365CMS - Main Bootstrap File
 * 
 * Minimal entry point - all logic handled by core classes
 * 
 * @package 365CMS
 * @version 2.0.0
 * @since 2026-02-17
 */

declare(strict_types=1);

// Start session with secure settings
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.use_only_cookies', '1');
session_start();

// Load configuration
require_once __DIR__ . '/config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'CMS\\';
    $baseDir = __DIR__ . '/core/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize CMS
try {
    $app = CMS\Bootstrap::instance();
    $app->run();
} catch (Throwable $e) {
    // Log error and show friendly message
    error_log('CMS Fatal Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    if (CMS_DEBUG) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>CMS Error</title>';
        echo '<style>body{font-family:monospace;padding:2rem;background:#1e293b;color:#e2e8f0;}';
        echo 'h1{color:#ef4444;margin-bottom:1rem;}pre{background:#0f172a;padding:1.5rem;border-radius:8px;overflow:auto;white-space:pre-wrap;}';
        echo '.trace{color:#94a3b8;font-size:0.875rem;}</style></head><body>';
        echo '<h1>⚠️ CMS Fatal Error</h1>';
        echo '<pre><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
        echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n";
        echo '<strong>Stack Trace:</strong></pre>';
        echo '<pre class="trace">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</body></html>';
    } else {
        http_response_code(500);
        include __DIR__ . '/themes/default/error.php';
    }
}
