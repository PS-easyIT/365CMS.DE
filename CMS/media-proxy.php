<?php
declare(strict_types=1);

/**
 * Legacy-Medien-Proxy.
 *
 * H-07: Der frühere Sonderpfad mit eigener Session-/Debug-Logik wird nur noch
 * als Kompatibilitätsadapter betrieben und leitet in die reguläre
 * Bootstrap-/Router-Pipeline um.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

if (!defined('CMS_AJAX_REQUEST') && strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    define('CMS_AJAX_REQUEST', true);
}

require_once __DIR__ . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Bootstrap;
use CMS\Logger;

try {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $targetUri = $method === 'POST' ? '/api/upload' : '/member/media';

    $_SERVER['REQUEST_URI'] = $targetUri;
    $_SERVER['PHP_SELF'] = $targetUri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';

    Logger::instance()->warning('Legacy media-proxy endpoint used; forwarding to router pipeline.', [
        'method' => $method,
        'target_uri' => $targetUri,
        'remote_addr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    ]);

    Bootstrap::instance()->run();
} catch (\Throwable $e) {
    if (class_exists(Logger::class)) {
        Logger::instance()->error('Legacy media-proxy forwarding failed.', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Der Legacy-Medien-Proxy konnte nicht an die zentrale Pipeline übergeben werden.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(302);
    header('Location: ' . rtrim((string)SITE_URL, '/') . '/member/media');
    exit;
}
