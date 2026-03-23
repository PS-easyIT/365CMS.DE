<?php
/**
 * Editor.js Media- und Link-API-Service.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsMediaService
{
    private readonly EditorJsRequestGuard $requestGuard;
    private readonly EditorJsUploadService $uploadService;
    private readonly EditorJsRemoteMediaService $remoteMediaService;
    private readonly EditorJsImageLibraryService $imageLibraryService;

    public function __construct()
    {
        $this->requestGuard = new EditorJsRequestGuard();
        $this->uploadService = new EditorJsUploadService();
        $this->remoteMediaService = new EditorJsRemoteMediaService($this->uploadService);
        $this->imageLibraryService = new EditorJsImageLibraryService();
    }

    public function handleMediaApiRequest(): void
    {
        $this->requestGuard->ensureEditorAccess();
        $this->requestGuard->verifyMediaToken();

        $action = (string) ($_REQUEST['action'] ?? '');
        $payload = $this->getJsonInput();

        try {
            switch ($action) {
                case 'list_images':
                    $this->json($this->imageLibraryService->listImages());
                    break;

                case 'upload_image':
                    $this->json($this->uploadService->uploadFile('image', true, 'editorjs', $_FILES));
                    break;

                case 'upload_featured':
                    $this->json($this->uploadService->uploadFeaturedImage($_POST, $_FILES));
                    break;

                case 'upload_file':
                    $this->json($this->uploadService->uploadFile('file', false, 'editorjs', $_FILES));
                    break;

                case 'fetch_image':
                    $this->json($this->remoteMediaService->fetchImageByUrl($payload, $_POST));
                    break;

                case 'fetch_link':
                    $this->json($this->remoteMediaService->fetchLinkMetadata($payload, $_POST));
                    break;

                default:
                    $this->json([
                        'success' => 0,
                        'message' => 'Unbekannte Editor.js-Media-Aktion.',
                    ], 400);
            }
        } catch (\Throwable $e) {
            $status = $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = 500;
            }

            $this->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        return Json::decodeArray($raw, []);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
