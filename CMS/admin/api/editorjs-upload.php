<?php
/**
 * Editor.js Upload/Fetch Endpoint (modular path)
 *
 * Unterstützt:
 * - action=upload_image (multipart)
 * - action=upload_file  (multipart)
 * - action=fetch_image  (byUrl)
 * - action=fetch_link   (LinkTool)
 *
 * @package CMSv2\Admin\API
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Services\MediaService;

if (!defined('ABSPATH')) {
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Auth::instance()->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => 0, 'error' => 'Nicht angemeldet']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => 0, 'error' => 'Methode nicht erlaubt']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!Security::instance()->verifyToken($csrfToken, 'editorjs_upload')) {
    http_response_code(403);
    echo json_encode(['success' => 0, 'error' => 'CSRF-Token ungültig']);
    exit;
}

$action = (string)($_GET['action'] ?? 'upload_image');

$allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
$allowedFileExt  = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'txt', 'csv'];
$maxSize = 10 * 1024 * 1024; // 10 MB

if ($action === 'fetch_link') {
    $url = trim((string)($_POST['url'] ?? ''));
    $validUrl = filter_var($url, FILTER_VALIDATE_URL);

    if ($validUrl === false) {
        http_response_code(400);
        echo json_encode(['success' => 0, 'error' => 'Ungültige URL']);
        exit;
    }

    echo json_encode([
        'success' => 1,
        'meta' => [
            'title' => (string)$validUrl,
            'description' => '',
            'image' => ['url' => ''],
        ],
        'link' => (string)$validUrl,
    ]);
    exit;
}

if ($action === 'fetch_image') {
    $url = trim((string)($_POST['url'] ?? ''));
    $validUrl = filter_var($url, FILTER_VALIDATE_URL);

    if ($validUrl === false) {
        http_response_code(400);
        echo json_encode(['success' => 0, 'error' => 'Ungültige Bild-URL']);
        exit;
    }

    $ext = strtolower((string)pathinfo(parse_url((string)$validUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
    if ($ext !== '' && !in_array($ext, $allowedImageExt, true)) {
        http_response_code(415);
        echo json_encode(['success' => 0, 'error' => 'Bildtyp nicht erlaubt']);
        exit;
    }

    echo json_encode([
        'success' => 1,
        'file' => [
            'url' => (string)$validUrl,
        ],
    ]);
    exit;
}

$fileKey = 'image';
if (!empty($_FILES['file'])) {
    $fileKey = 'file';
}

if (empty($_FILES[$fileKey]) || (int)$_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => 0, 'error' => 'Keine Datei empfangen']);
    exit;
}

$file = $_FILES[$fileKey];
$size = (int)($file['size'] ?? 0);
if ($size > $maxSize) {
    http_response_code(413);
    echo json_encode(['success' => 0, 'error' => 'Datei zu groß (max. 10 MB)']);
    exit;
}

$ext = strtolower((string)pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
$allowed = ($action === 'upload_file') ? array_merge($allowedImageExt, $allowedFileExt) : $allowedImageExt;
if (!in_array($ext, $allowed, true)) {
    http_response_code(415);
    echo json_encode(['success' => 0, 'error' => 'Dateityp nicht erlaubt: ' . $ext]);
    exit;
}

$year = date('Y');
$month = date('m');
$targetPath = "editor/{$year}/{$month}";

try {
    $mediaService = MediaService::getInstance();
    $result = $mediaService->uploadFile($file, $targetPath);

    if (is_wp_error($result)) {
        http_response_code(500);
        echo json_encode(['success' => 0, 'error' => $result->get_error_message()]);
        exit;
    }

    $fileUrl = SITE_URL . '/uploads/' . $targetPath . '/' . $result;

    echo json_encode([
        'success' => 1,
        'file' => [
            'url' => $fileUrl,
            'name' => (string)$result,
            'size' => $size,
        ],
    ]);
} catch (\Throwable) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'error' => 'Upload fehlgeschlagen']);
}
