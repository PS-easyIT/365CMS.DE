<?php
declare(strict_types=1);

$truncateForLog = static function (string $value, int $limit = 400): string {
    $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');
    if ($value === '') {
        return '';
    }

    return mb_substr($value, 0, $limit);
};

$normalizeCronTask = static function (mixed $task): string {
    $task = strtolower(trim((string) $task));
    if ($task === '' || $task === 'default') {
        return 'all';
    }

    if (str_starts_with($task, 'hook:')) {
        $task = substr($task, 5);
    }

    $task = preg_replace('/[^a-z0-9_-]+/', '', $task) ?? '';
    return $task !== '' ? $task : 'all';
};

$normalizeCronLimit = static function (mixed $limit): ?int {
    if ($limit === null || $limit === '') {
        return null;
    }

    $normalized = filter_var($limit, FILTER_VALIDATE_INT);
    if ($normalized === false) {
        return null;
    }

    return min(100, max(1, (int) $normalized));
};

$getWebCronToken = static function (): string {
    $headerToken = trim((string) ($_SERVER['HTTP_X_CMS_CRON_TOKEN'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
    if ($headerToken !== '') {
        return $headerToken;
    }

    return trim((string) ($_GET['token'] ?? ''));
};

$isHttpsRequest = static function (): bool {
    if (PHP_SAPI === 'cli') {
        return false;
    }

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

$normalizeOutputMode = static function (mixed $mode, bool $isCli): string {
    $mode = strtolower(trim((string) $mode));

    return match ($mode) {
        'json', 'application/json' => 'json',
        'text', 'plain', 'txt' => 'text',
        'quiet', 'silent', 'none' => 'quiet',
        default => $isCli ? 'quiet' : 'quiet',
    };
};

ob_start();

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

$outputMode = 'quiet';

$respond = static function (array $payload, int $statusCode = 200) use (&$outputMode): void {
    $capturedOutput = trim((string) ob_get_clean());

    if ($capturedOutput !== '' && $outputMode !== 'quiet') {
        $payload['captured_output'] = $capturedOutput;
    }

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code($outputMode === 'quiet' && $statusCode >= 200 && $statusCode < 300 ? 204 : $statusCode);
        header('X-Robots-Tag: noindex, nofollow, noarchive');
    }

    if (PHP_SAPI !== 'cli' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
        exit($statusCode === 200 ? 0 : 1);
    }

    if ($outputMode === 'quiet') {
        exit($statusCode === 200 ? 0 : 1);
    }

    if ($outputMode === 'text') {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('Content-Type: text/plain; charset=UTF-8');
        }

        $lines = [
            'success=' . (!empty($payload['success']) ? '1' : '0'),
            'task=' . (string) ($payload['task'] ?? ''),
            'mode=' . (string) ($payload['mode'] ?? (PHP_SAPI === 'cli' ? 'cli' : 'web')),
        ];

        if (!empty($payload['error'])) {
            $lines[] = 'error=' . (string) $payload['error'];
        }

        if (!empty($payload['captured_output'])) {
            $lines[] = 'captured_output=' . (string) $payload['captured_output'];
        }

        echo implode(PHP_EOL, $lines) . PHP_EOL;
        exit($statusCode === 200 ? 0 : 1);
    }

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if (!is_string($json)) {
        $json = '{"success":false,"error":"JSON-Ausgabe fehlgeschlagen."}';
    }

    echo $json . PHP_EOL;
    exit($statusCode === 200 ? 0 : 1);
};

try {
    $task = 'all';
    $limit = null;
    $force = false;
    $token = '';
    $outputMode = $normalizeOutputMode(null, PHP_SAPI === 'cli');

    if (PHP_SAPI === 'cli') {
        foreach (array_slice($_SERVER['argv'] ?? [], 1) as $argument) {
            if (str_starts_with($argument, '--task=')) {
                $task = $normalizeCronTask(substr($argument, 7));
                continue;
            }
            if (str_starts_with($argument, '--limit=')) {
                $limit = $normalizeCronLimit(substr($argument, 8));
                continue;
            }
            if ($argument === '--force=1' || $argument === '--force' || $argument === '--force=true') {
                $force = true;
                continue;
            }
            if (in_array($argument, ['--json', '--verbose'], true)) {
                $outputMode = 'json';
                continue;
            }
            if ($argument === '--text') {
                $outputMode = 'text';
                continue;
            }
            if ($argument === '--quiet' || $argument === '--silent') {
                $outputMode = 'quiet';
            }
        }
    } else {
        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($requestMethod, ['GET', 'HEAD'], true)) {
            $respond([
                'success' => false,
                'error' => 'HTTP-Methode für Cron nicht erlaubt.',
            ], 405);
        }

        $task = $normalizeCronTask($_GET['task'] ?? 'all');
        $limit = $normalizeCronLimit($_GET['limit'] ?? null);
        $force = !empty($_GET['force']);
        $token = $getWebCronToken();

        if (!empty($_GET['verbose'])) {
            $outputMode = 'json';
        } elseif (!empty($_GET['text'])) {
            $outputMode = 'text';
        } elseif (!empty($_GET['quiet'])) {
            $outputMode = 'quiet';
        } else {
            $outputMode = $normalizeOutputMode($_GET['format'] ?? null, false);
        }
    }
    if (PHP_SAPI !== 'cli') {
        $config = CMS\Services\MailQueueService::getInstance()->getConfiguration();
        $expectedToken = (string) ($config['cron_token'] ?? '');
        if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
            $respond([
                'success' => false,
                'error' => 'Ungültiger oder fehlender Cron-Token.',
            ], 403);
        }
    }

    $runnerResult = CMS\Services\CronRunnerService::getInstance()->run([
        'task' => $task,
        'limit' => $limit,
        'force' => $force,
        'mode' => PHP_SAPI === 'cli' ? 'cli' : 'web',
        'source' => 'cron.php',
    ]);

    $statusCode = match ((string) ($runnerResult['error_code'] ?? '')) {
        'invalid_task' => 400,
        'lock_active' => 429,
        default => !empty($runnerResult['success']) ? 200 : 500,
    };

    $respond($runnerResult, $statusCode);
} catch (Throwable $e) {
    $respond([
        'success' => false,
        'error' => 'Cron-Lauf fehlgeschlagen. Details wurden intern protokolliert.',
    ], 500);
}
