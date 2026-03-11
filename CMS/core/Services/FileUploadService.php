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
use CMS\WP_Error;

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
        $auth = Auth::instance();
        $isAdmin = $auth->isAdmin();
        $isLoggedIn = $auth->isLoggedIn();

        if (!$isAdmin && !$isLoggedIn) {
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

        $targetPathRaw = (string)($_POST['target_path'] ?? $_POST['path'] ?? '');
        $targetPath = $this->sanitizePath($targetPathRaw);

        $mediaService = MediaService::getInstance();
        $validationSettings = null;

        if (!$isAdmin) {
            $currentUser = $auth->getCurrentUser();
            $userId = (int)($currentUser->id ?? 0);
            $memberRoot = 'member/user-' . $userId;
            $settings = $mediaService->getSettings();

            if ($userId <= 0 || empty($settings['member_uploads_enabled'])) {
                return [
                    'success' => false,
                    'status' => 403,
                    'data' => [
                        'error' => 'Uploads für Mitglieder sind nicht aktiviert.',
                        'new_token' => Security::instance()->generateToken('media_action'),
                    ],
                ];
            }

            if ($targetPath === '') {
                $targetPath = $memberRoot;
            }

            if ($targetPath !== $memberRoot && !str_starts_with($targetPath, $memberRoot . '/')) {
                return [
                    'success' => false,
                    'status' => 403,
                    'data' => [
                        'error' => 'Upload-Ziel liegt außerhalb deines erlaubten Bereichs.',
                        'new_token' => Security::instance()->generateToken('media_action'),
                    ],
                ];
            }

            $validationSettings = $mediaService->buildUploadValidationSettings($settings, true);
        }

        if ($validationSettings === null) {
            $validationSettings = $mediaService->buildUploadValidationSettings($mediaService->getSettings());
        }

        $validationResult = $mediaService->validateUploadFile($uploadFile, $validationSettings);
        if ($validationResult instanceof WP_Error) {
            return [
                'success' => false,
                'status' => 422,
                'data' => [
                    'error' => $validationResult->get_error_message(),
                    'new_token' => Security::instance()->generateToken('media_action'),
                ],
            ];
        }

        $uploadResult = $mediaService->uploadFile($uploadFile, $targetPath, $validationSettings);

        if ($uploadResult instanceof WP_Error) {
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

    private function sanitizePath(string $path): string
    {
        $clean = str_replace(['..', '\\'], ['', '/'], trim($path));
        return trim(preg_replace('#/+#', '/', $clean) ?? '', '/');
    }
}
