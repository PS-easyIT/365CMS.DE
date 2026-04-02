<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

\CMS\CacheManager::instance()->sendResponseHeaders('private');

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');

if (!Auth::instance()->isAdmin() || !Auth::instance()->hasCapability('manage_settings')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Keine Berechtigung für den Cron-Runner.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST ist für den Cron-Runner erlaubt.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_system_cron_runner')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Sicherheitstoken für den Cron-Runner ist ungültig.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/modules/system/SystemInfoModule.php';

$module = new SystemInfoModule();
$result = $module->handleCronRunnerRequest($_POST);
$httpStatus = max(200, min(599, (int) ($result['http_status'] ?? (!empty($result['success']) ? 200 : 500))));
unset($result['http_status']);

http_response_code($httpStatus);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;