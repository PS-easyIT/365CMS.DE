<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;
use CMS\Services\CoreModuleService;

\CMS\CacheManager::instance()->sendResponseHeaders('private');

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST ist für die AI-SEO-Generierung erlaubt.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!Auth::instance()->isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Keine Berechtigung für die Editor.js-AI-SEO-Generierung.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!CoreModuleService::getInstance()->isModuleEnabled('ai_services')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'AI Services ist derzeit als Core-Modul deaktiviert.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$contentType = strtolower(trim((string) ($_POST['content_type'] ?? '')));
$hasCapability = Auth::instance()->hasCapability('manage_ai_services')
    || Auth::instance()->hasCapability('manage_settings')
    || Auth::instance()->hasCapability('use_ai_seo_meta')
    || ($contentType === 'post' && Auth::instance()->hasCapability('edit_all_posts'))
    || ($contentType === 'page' && Auth::instance()->hasCapability('manage_pages'));

if (!$hasCapability) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Keine Berechtigung für die Editor.js-AI-SEO-Generierung.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!Security::instance()->verifyPersistentToken((string) ($_POST['csrf_token'] ?? ''), 'admin_ai_seo_metadata')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Sicherheitstoken für die AI-SEO-Generierung ist ungültig.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (function_exists('set_time_limit')) {
    set_time_limit(300);
}

require_once __DIR__ . '/modules/system/AiEditorJsSeoMetadataModule.php';

$module = new AiEditorJsSeoMetadataModule();
$userId = (int) (Auth::instance()->getCurrentUser()->id ?? 0);
$result = $module->handleRequest($_POST, $userId);

http_response_code(!empty($result['success']) ? 200 : 422);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;
