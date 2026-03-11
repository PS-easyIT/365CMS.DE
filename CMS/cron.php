<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

require_once __DIR__ . '/config.php';

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

if (PHP_SAPI !== 'cli') {
    \CMS\CacheManager::instance()->sendResponseHeaders('private');
}

$respond = static function (array $payload, int $statusCode = 200): void {
    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code($statusCode);
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        $json = '{"success":false,"error":"JSON-Ausgabe fehlgeschlagen."}';
    }

    echo $json . PHP_EOL;
    exit($statusCode === 200 ? 0 : 1);
};

try {
    $task = 'mail-queue';
    $limit = null;
    $force = false;
    $token = '';

    if (PHP_SAPI === 'cli') {
        foreach (array_slice($_SERVER['argv'] ?? [], 1) as $argument) {
            if (str_starts_with($argument, '--task=')) {
                $task = (string) substr($argument, 7);
                continue;
            }
            if (str_starts_with($argument, '--limit=')) {
                $limit = (int) substr($argument, 8);
                continue;
            }
            if ($argument === '--force=1' || $argument === '--force' || $argument === '--force=true') {
                $force = true;
            }
        }
    } else {
        $task = (string) ($_GET['task'] ?? 'mail-queue');
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : null;
        $force = !empty($_GET['force']);
        $token = trim((string) ($_GET['token'] ?? ''));
    }

    if ($task !== 'mail-queue') {
        $respond([
            'success' => false,
            'error' => 'Unbekannte Cron-Task. Unterstützt wird aktuell nur "mail-queue".',
        ], 400);
    }

    $app = CMS\Bootstrap::instance();
    $queue = CMS\Services\MailQueueService::getInstance();

    if (PHP_SAPI !== 'cli') {
        $config = $queue->getConfiguration();
        $expectedToken = (string) ($config['cron_token'] ?? '');
        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            $respond([
                'success' => false,
                'error' => 'Ungültiger oder fehlender Cron-Token.',
            ], 403);
        }
    }

    $result = $queue->handleCronHook([
        'limit' => $limit,
        'force' => $force,
    ]);

    $respond([
        'success' => !empty($result['success']),
        'task' => 'mail-queue',
        'mode' => PHP_SAPI === 'cli' ? 'cli' : 'web',
        'result' => $result,
    ], !empty($result['success']) ? 200 : 500);
} catch (Throwable $e) {
    error_log('CMS Cron Error: ' . $e->getMessage());
    $respond([
        'success' => false,
        'error' => $e->getMessage(),
    ], 500);
}
