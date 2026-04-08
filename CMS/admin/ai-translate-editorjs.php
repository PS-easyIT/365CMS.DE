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

if (!Auth::instance()->isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Keine Berechtigung für die Editor.js-AI-Übersetzung.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$contentType = strtolower(trim((string) ($_POST['content_type'] ?? 'editorjs')));
$hasCapability = Auth::instance()->hasCapability('manage_ai_services')
    || Auth::instance()->hasCapability('manage_settings')
    || Auth::instance()->hasCapability('use_ai_translation')
    || ($contentType === 'post' && Auth::instance()->hasCapability('edit_all_posts'))
    || ($contentType === 'page' && Auth::instance()->hasCapability('manage_pages'));

if (!$hasCapability) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Keine Berechtigung für die Editor.js-AI-Übersetzung.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST ist für die Editor.js-AI-Übersetzung erlaubt.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!Security::instance()->verifyPersistentToken((string) ($_POST['csrf_token'] ?? ''), 'admin_ai_editorjs_translation')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Sicherheitstoken für die Editor.js-AI-Übersetzung ist ungültig.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/modules/system/AiEditorJsTranslationModule.php';

$module = new AiEditorJsTranslationModule();
$userId = (int) (Auth::instance()->getCurrentUser()->id ?? 0);
$result = $module->handleRequest($_POST, $userId);

$httpStatus = !empty($result['success']) ? 200 : 422;
http_response_code($httpStatus);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;