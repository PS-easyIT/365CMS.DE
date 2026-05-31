<?php
/**
 * Editor.js Media- und Link-API-Service.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Json;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsMediaService
{
    private const MAX_JSON_BODY_BYTES = 65536;

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
        try {
            $this->requestGuard->ensureEditorAccess();
            $this->requestGuard->verifyMediaToken();

            $action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');
            $payload = $this->getJsonInput();
            $requestContext = array_merge($_GET, $_POST, $payload);

            switch ($action) {
                case 'list_images':
                    $filenamePrefix = trim((string) ($_GET['filename_prefix'] ?? ''));
                    $this->json($this->imageLibraryService->listImages($filenamePrefix));
                    break;

                case 'upload_image':
                    $this->json($this->uploadService->uploadFile('image', true, 'editorjs', $_POST, $_FILES));
                    break;

                case 'upload_featured':
                    $this->json($this->uploadService->uploadFeaturedImage($_POST, $_FILES));
                    break;

                case 'upload_file':
                    $this->json($this->uploadService->uploadFile('file', false, 'editorjs', $_POST, $_FILES));
                    break;

                case 'fetch_image':
                    $this->json($this->remoteMediaService->fetchImageByUrl($payload, $requestContext));
                    break;

                case 'fetch_link':
                    $this->json($this->remoteMediaService->fetchLinkMetadata($payload, $requestContext));
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

            Logger::instance()->withChannel('editorjs.media')->warning('Editor.js-Media-Anfrage fehlgeschlagen', [
                'action' => (string) ($_POST['action'] ?? $_GET['action'] ?? ''),
                'status' => $status,
                'exception' => get_class($e),
                'message' => $this->sanitizeLogMessage($e->getMessage()),
            ]);

            $this->json([
                'success' => 0,
                'message' => $status >= 500
                    ? 'Editor.js-Media-Anfrage konnte nicht verarbeitet werden. Bitte Logs prüfen.'
                    : $this->sanitizeClientMessage($e->getMessage()),
            ], $status);
        }
    }

    private function sanitizeClientMessage(string $message): string
    {
        $message = trim(strip_tags(str_replace(["\r", "\n", "\0"], ' ', $message)));
        $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return $message !== '' ? $message : 'Editor.js-Media-Anfrage ist nicht erlaubt.';
    }

    private function sanitizeLogMessage(string $message): string
    {
        $message = str_replace(["\r", "\n", "\0"], ' ', $message);
        $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return function_exists('mb_substr') ? mb_substr(trim($message), 0, 300) : substr(trim($message), 0, 300);
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_JSON_BODY_BYTES + 1);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        if (strlen($raw) > self::MAX_JSON_BODY_BYTES) {
            throw new \RuntimeException('Editor.js-Media-Payload ist zu groß.', 413);
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
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo is_string($json) ? $json : '{"success":0,"message":"JSON-Antwort konnte nicht erzeugt werden."}';
        exit;
    }
}
