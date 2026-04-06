<?php
/**
 * 365CMS - Main Bootstrap File
 * 
 * Minimal entry point - all logic handled by core classes
 * 
 * @package 365CMS
 * @since 2026-02-17
 */

declare(strict_types=1);

$isHttpsRequest = static function (): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if (in_array($forwardedProto, ['https', 'wss'], true)) {
        return true;
    }

    $forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    if (in_array($forwardedSsl, ['on', '1', 'true'], true)) {
        return true;
    }

    $frontEndHttps = strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? ''));
    return in_array($frontEndHttps, ['on', '1'], true);
};

$resolveSessionCookieDomain = static function (): string {
    $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($host === '') {
        return '';
    }

    if (str_contains($host, ':')) {
        $host = explode(':', $host, 2)[0];
    }

    if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }

    return preg_replace('/^www\./i', '', $host) ?? '';
};

$sessionCookieDomain = $resolveSessionCookieDomain();

// Start session with secure settings
// Alle ini_set MÜSSEN vor session_start() gesetzt werden (Security::startSession() prüft nur ob Session noch nicht gestartet ist)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttpsRequest() ? '1' : '0');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');  // Verhindert Session-Fixation (H-03-Ergänzung)
ini_set('session.cookie_samesite', 'Strict');  // CSRF-Zusatzschutz
if ($sessionCookieDomain !== '') {
    ini_set('session.cookie_domain', $sessionCookieDomain);
}
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
        require_once $file;
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

    if (class_exists('CMS\\CacheManager')) {
        CMS\CacheManager::instance()->sendResponseHeaders('private');
    }
    
    http_response_code(500);
    include __DIR__ . '/themes/default/error.php';
}
