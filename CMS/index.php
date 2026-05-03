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

$earlyAppConfig = __DIR__ . '/config/app.php';
if (file_exists($earlyAppConfig)) {
    require_once $earlyAppConfig;
}

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

    $normalizedHost = preg_replace('/^www\./i', '', $host) ?? '';
    if ($normalizedHost === '') {
        return '';
    }

    $siteHost = strtolower(trim((string) (parse_url((string) (defined('SITE_URL') ? SITE_URL : ''), PHP_URL_HOST) ?? '')));
    if ($siteHost !== '') {
        $siteHost = preg_replace('/^www\./i', '', $siteHost) ?? '';
        if ($siteHost === '') {
            return '';
        }

        if ($normalizedHost === $siteHost) {
            return $siteHost;
        }

        // Für alternative Hostnamen (z. B. Hub-/Alias-Domains) kein erzwungenes
        // cookie_domain setzen: Host-only Cookies sind dort stabiler und verhindern,
        // dass die Session beim Redirect auf einen anderen Host verloren geht.
        return '';
    }

    return $normalizedHost;
};

$sessionCookieDomain = $resolveSessionCookieDomain();

$resolveConfiguredSessionGcLifetime = static function (): int {
    $adminLifetime = 28_800;
    $memberLifetime = 2_592_000;

    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_CHARSET') || !defined('DB_PREFIX')) {
        return max($adminLifetime, $memberLifetime);
    }

    if (str_contains((string) DB_USER, 'YOUR_') || str_contains((string) DB_NAME, 'YOUR_')) {
        return max($adminLifetime, $memberLifetime);
    }

    $prefix = (string) DB_PREFIX;
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
        return max($adminLifetime, $memberLifetime);
    }

    try {
        $pdo = new \PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
            DB_USER,
            DB_PASS,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        $table = $prefix . 'settings';
        $stmt = $pdo->prepare("SELECT option_name, option_value FROM `{$table}` WHERE option_name IN (?, ?)");
        $stmt->execute(['perf_session_timeout_admin', 'perf_session_timeout_member']);

        foreach ($stmt->fetchAll() as $row) {
            $optionName = (string) ($row['option_name'] ?? '');
            $optionValue = (int) ($row['option_value'] ?? 0);

            if ($optionName === 'perf_session_timeout_admin') {
                $adminLifetime = max(300, min(604800, $optionValue));
                continue;
            }

            if ($optionName === 'perf_session_timeout_member') {
                $memberLifetime = max(300, min(31536000, $optionValue));
            }
        }
    } catch (\Throwable) {
        return max($adminLifetime, $memberLifetime);
    }

    return max(300, $adminLifetime, $memberLifetime);
};

$sessionGcMaxLifetime = $resolveConfiguredSessionGcLifetime();

// Start session with secure settings
// Alle ini_set MÜSSEN vor session_start() gesetzt werden (Security::startSession() prüft nur ob Session noch nicht gestartet ist)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isHttpsRequest() ? '1' : '0');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');  // Verhindert Session-Fixation (H-03-Ergänzung)
ini_set('session.cookie_samesite', 'Strict');  // CSRF-Zusatzschutz
ini_set('session.gc_maxlifetime', (string) $sessionGcMaxLifetime);
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

    $defaultTheme = defined('DEFAULT_THEME') && is_string(DEFAULT_THEME) && trim(DEFAULT_THEME) !== ''
        ? (preg_replace('/[^a-zA-Z0-9_-]/', '', trim(DEFAULT_THEME)) ?: 'cms-default')
        : 'cms-default';

    $errorTemplates = [
        __DIR__ . '/themes/' . $defaultTheme . '/error.php',
        __DIR__ . '/themes/cms-default/error.php',
        __DIR__ . '/themes/default/error.php',
    ];

    foreach ($errorTemplates as $errorTemplate) {
        if (is_file($errorTemplate)) {
            include $errorTemplate;
            exit;
        }
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>365CMS – Fehler</title></head><body><h1>Interner Fehler</h1><p>365CMS konnte die Anfrage gerade nicht verarbeiten.</p></body></html>';
    exit;
}
