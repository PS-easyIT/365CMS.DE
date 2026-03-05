<?php
/**
 * Editor.js Upload Endpoint
 *
 * Nimmt Bild- und Datei-Uploads von Editor.js entgegen.
 * Gibt JSON im Editor.js-Format zurück:
 *   { success: 1, file: { url: "..." } }
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

// Nur eingeloggte Admins dürfen hochladen
if (!Auth::instance()->isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => 0, 'error' => 'Nicht angemeldet']);
    exit;
}

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => 0, 'error' => 'Methode nicht erlaubt']);
    exit;
}

// CSRF prüfen
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Security::instance()->verifyToken($csrfToken, 'editorjs_upload')) {
    http_response_code(403);
    echo json_encode(['success' => 0, 'error' => 'CSRF-Token ungültig']);
    exit;
}

// Datei prüfen
$fileKey = 'image'; // Editor.js Image-Plugin sendet als "image"
if (empty($_FILES[$fileKey])) {
    $fileKey = 'file'; // Attaches-Plugin sendet als "file"
}
if (empty($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => 0, 'error' => 'Keine Datei empfangen']);
    exit;
}

$file = $_FILES[$fileKey];

// Maximale Dateigröße: 10 MB
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(413);
    echo json_encode(['success' => 0, 'error' => 'Datei zu groß (max. 10 MB)']);
    exit;
}

// Erlaubte Dateitypen
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedImages  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
$allowedFiles   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'txt', 'csv'];
$allowedAll     = array_merge($allowedImages, $allowedFiles);

if (!in_array($ext, $allowedAll, true)) {
    http_response_code(415);
    echo json_encode(['success' => 0, 'error' => 'Dateityp nicht erlaubt: ' . $ext]);
    exit;
}

// Upload-Zielverzeichnis: uploads/editor/YYYY/MM/
$year  = date('Y');
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

    // Dateiname zurückbekommen → URL zusammenbauen
    $fileUrl = SITE_URL . '/uploads/' . $targetPath . '/' . $result;

    echo json_encode([
        'success' => 1,
        'file' => [
            'url'  => $fileUrl,
            'name' => $result,
            'size' => $file['size'],
        ]
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'error' => 'Upload fehlgeschlagen']);
}
