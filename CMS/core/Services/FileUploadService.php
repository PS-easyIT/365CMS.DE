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

use CMS\AuditLogger;
use CMS\Auth;
use CMS\Logger;
use CMS\Security;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class FileUploadService
{
    private static ?self $instance = null;
    private const MAX_PATH_LENGTH = 240;
    private const MAX_SEGMENTS = 12;
    private const PATH_SEGMENT_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]{0,79}$/';

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
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return $this->errorResponse(405, 'Ungültige Upload-Anfrage.', 'upload.invalid_method');
        }

        $auth = Auth::instance();
        $isAdmin = $auth->isAdmin();
        $isLoggedIn = $auth->isLoggedIn();

        if (!$isAdmin && !$isLoggedIn) {
            return $this->errorResponse(403, 'Nicht autorisiert.', 'upload.unauthorized');
        }

        $csrfToken = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::instance()->verifyToken($csrfToken, 'media_action')) {
            return $this->errorResponse(403, 'Sicherheitsüberprüfung fehlgeschlagen.', 'upload.invalid_csrf');
        }

        $uploadFile = $_FILES['filepond'] ?? $_FILES['file'] ?? null;
        if (!is_array($uploadFile)) {
            return $this->errorResponse(400, 'Keine Datei im Upload-Request gefunden.', 'upload.missing_file');
        }

        $uploadFile = $this->normalizeUploadArray($uploadFile);
        if ($uploadFile === null) {
            return $this->errorResponse(400, 'Die Upload-Datei ist ungültig.', 'upload.invalid_file_payload');
        }

        if (!$this->isUploadedFilePayload($uploadFile)) {
            return $this->errorResponse(400, 'Die Upload-Datei ist unvollständig oder ungültig.', 'upload.invalid_file');
        }

        $targetPathRaw = (string)($_POST['target_path'] ?? $_POST['path'] ?? '');
        $targetPath = $this->sanitizePath($targetPathRaw);
        if ($targetPath === null) {
            return $this->errorResponse(400, 'Ungültiger Upload-Pfad.', 'upload.invalid_target_path', ['target_path' => $targetPathRaw]);
        }

        $mediaService = MediaService::getInstance();
        $validationSettings = null;

        if (!$isAdmin) {
            $currentUser = $auth->getCurrentUser();
            $userId = (int)($currentUser->id ?? 0);
            $memberRoot = 'member/user-' . $userId;
            $settings = $mediaService->getSettings();

            if ($userId <= 0 || empty($settings['member_uploads_enabled'])) {
                return $this->errorResponse(403, 'Uploads für Mitglieder sind nicht aktiviert.', 'upload.member_disabled', ['user_id' => $userId]);
            }

            if ($targetPath === '') {
                $targetPath = $memberRoot;
            }

            if ($targetPath !== $memberRoot && !str_starts_with($targetPath, $memberRoot . '/')) {
                return $this->errorResponse(403, 'Upload-Ziel liegt außerhalb deines erlaubten Bereichs.', 'upload.member_path_denied', ['user_id' => $userId, 'target_path' => $targetPath]);
            }

            $validationSettings = $mediaService->buildUploadValidationSettings($settings, true);
        }

        if ($validationSettings === null) {
            $validationSettings = $mediaService->buildUploadValidationSettings($mediaService->getSettings());
        }

        $validationResult = $mediaService->validateUploadFile($uploadFile, $validationSettings);
        if ($validationResult instanceof WP_Error) {
            return $this->errorResponse(
                422,
                'Die Upload-Datei erfüllt die Sicherheits- oder Formatregeln nicht.',
                'upload.validation_failed',
                [
                    'error_code' => $validationResult->get_error_code(),
                    'error_message' => $validationResult->get_error_message(),
                    'target_path' => $targetPath,
                ]
            );
        }

        $uploadResult = $mediaService->uploadFile($uploadFile, $targetPath, $validationSettings);

        if ($uploadResult instanceof WP_Error) {
            return $this->errorResponse(
                422,
                'Datei konnte nicht gespeichert werden.',
                'upload.persist_failed',
                [
                    'error_code' => $uploadResult->get_error_code(),
                    'error_message' => $uploadResult->get_error_message(),
                    'target_path' => $targetPath,
                ]
            );
        }

        $filename = (string)$uploadResult;
        $relativePath = trim($targetPath . '/' . $filename, '/');
        $mediaDelivery = MediaDeliveryService::getInstance();
        $url = $mediaDelivery->buildAccessUrl($relativePath, true);
        $downloadUrl = $mediaDelivery->buildDeliveryUrl($relativePath, 'attachment');

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'id' => $relativePath,
                'filename' => $filename,
                'path' => $relativePath,
                'url' => $url,
                'preview_url' => $mediaDelivery->buildPreviewUrl($relativePath),
                'download_url' => $downloadUrl,
                'new_token' => Security::instance()->generateToken('media_action'),
            ],
        ];
    }

    private function sanitizePath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('/[\x00-\x1F\x7F]+/u', '', $path) ?? '';
        $path = trim($path, '/');

        if ($path === '' || strlen($path) > self::MAX_PATH_LENGTH) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        if ($segments === [] || count($segments) > self::MAX_SEGMENTS) {
            return null;
        }

        $normalized = [];
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || str_starts_with($segment, '.')) {
                return null;
            }

            if (!preg_match(self::PATH_SEGMENT_PATTERN, $segment)) {
                return null;
            }

            $normalized[] = $segment;
        }

        return implode('/', $normalized);
    }

    private function normalizeUploadArray(array $uploadFile): ?array
    {
        $name = $uploadFile['name'] ?? null;
        $tmpName = $uploadFile['tmp_name'] ?? null;
        $error = $uploadFile['error'] ?? null;
        $size = $uploadFile['size'] ?? null;

        if (is_array($name) || is_array($tmpName) || is_array($error) || is_array($size)) {
            return null;
        }

        return [
            'name' => (string) $name,
            'tmp_name' => (string) $tmpName,
            'error' => is_numeric($error) ? (int) $error : UPLOAD_ERR_NO_FILE,
            'size' => is_numeric($size) ? (int) $size : 0,
            'type' => isset($uploadFile['type']) && !is_array($uploadFile['type']) ? (string) $uploadFile['type'] : '',
        ];
    }

    private function isUploadedFilePayload(array $uploadFile): bool
    {
        $tmpName = (string) ($uploadFile['tmp_name'] ?? '');
        $size = (int) ($uploadFile['size'] ?? 0);
        $fileName = trim((string) ($uploadFile['name'] ?? ''));

        if ($fileName === '' || $tmpName === '' || $size <= 0) {
            return false;
        }

        if (!is_uploaded_file($tmpName) && !is_file($tmpName)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $context
     * @return array{success:bool,status:int,data:array<string,mixed>}
     */
    private function errorResponse(int $status, string $message, string $action, array $context = []): array
    {
        $this->logFailure($action, $message, $context);

        return [
            'success' => false,
            'status' => $status,
            'data' => [
                'error' => $message,
                'new_token' => Security::instance()->generateToken('media_action'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    private function logFailure(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('upload.service')->warning($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_CONTENT,
            $action,
            $message,
            'file-upload',
            null,
            $context,
            'warning'
        );
    }
}
