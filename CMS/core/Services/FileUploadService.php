<?php
/**
 * File Upload Service
 *
 * FilePond-kompatibler Upload-Endpunkt-Service.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Auth;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

final class FileUploadService
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * Verarbeitet einen FilePond-/Upload-Request und gibt einen standardisierten Response zurück.
     *
     * @return array{success:bool,status:int,data:array<string,mixed>}
     */
    public function handleUploadRequest(): array
    {
        if (!Auth::instance()->isAdmin()) {
            return [
                'success' => false,
                'status' => 403,
                'data' => ['error' => 'Nicht autorisiert.'],
            ];
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::instance()->verifyToken($csrfToken, 'media_action')) {
            return [
                'success' => false,
                'status' => 403,
                'data' => [
                    'error' => 'Sicherheitsüberprüfung fehlgeschlagen.',
                    'new_token' => Security::instance()->generateToken('media_action'),
                ],
            ];
        }

        $uploadFile = $_FILES['filepond'] ?? $_FILES['file'] ?? null;
        if (!is_array($uploadFile)) {
            return [
                'success' => false,
                'status' => 400,
                'data' => [
                    'error' => 'Keine Datei im Request gefunden (erwartet: filepond oder file).',
                    'new_token' => Security::instance()->generateToken('media_action'),
                ],
            ];
        }

        $targetPathRaw = (string)($_POST['path'] ?? '');
        $targetPath = $this->sanitizePath($targetPathRaw);

        $mediaService = new MediaService();
        $uploadResult = $mediaService->uploadFile($uploadFile, $targetPath);

        if (is_wp_error($uploadResult)) {
            return [
                'success' => false,
                'status' => 422,
                'data' => [
                    'error' => $uploadResult->get_error_message(),
                    'new_token' => Security::instance()->generateToken('media_action'),
                ],
            ];
        }

        $filename = (string)$uploadResult;
        $relativePath = trim($targetPath . '/' . $filename, '/');
        $url = rtrim(UPLOAD_URL, '/') . '/' . str_replace('\\', '/', $relativePath);

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'id' => $relativePath,
                'filename' => $filename,
                'path' => $relativePath,
                'url' => $url,
                'new_token' => Security::instance()->generateToken('media_action'),
            ],
        ];
    }

    private function sanitizePath(string $path): string
    {
        $clean = str_replace(['..', '\\'], ['', '/'], trim($path));
        return trim(preg_replace('#/+#', '/', $clean) ?? '', '/');
    }
}
