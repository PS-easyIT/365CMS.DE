<?php
/**
 * Editor.js Media- und Link-API-Service.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Http\Client as HttpClient;
use CMS\Json;
use CMS\Services\MediaService;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsMediaService
{
    public function handleMediaApiRequest(): void
    {
        $this->ensureAdminAccess();
        $this->verifyMediaToken();

        $action = (string) ($_REQUEST['action'] ?? '');

        try {
            switch ($action) {
                case 'list_images':
                    $this->json($this->handleImageLibraryList());
                    break;

                case 'upload_image':
                    $this->json($this->handleFileUpload('image', true));
                    break;

                case 'upload_featured':
                    $this->json($this->handleFeaturedImageUpload());
                    break;

                case 'upload_file':
                    $this->json($this->handleFileUpload('file', false));
                    break;

                case 'fetch_image':
                    $this->json($this->handleImageFetchByUrl());
                    break;

                case 'fetch_link':
                    $this->json($this->handleLinkMetadataFetch());
                    break;

                default:
                    $this->json([
                        'success' => 0,
                        'message' => 'Unbekannte Editor.js-Media-Aktion.',
                    ], 400);
            }
        } catch (\Throwable $e) {
            $this->json([
                'success' => 0,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function ensureAdminAccess(): void
    {
        if (!class_exists(\CMS\Auth::class) || !\CMS\Auth::instance()->isAdmin()) {
            $this->json([
                'success' => 0,
                'message' => 'Nicht autorisiert.',
            ], 403);
        }
    }

    private function verifyMediaToken(): void
    {
        $token = '';

        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = (string) $_POST['csrf_token'];
        }

        if ($token === '' || !class_exists(\CMS\Security::class) || !\CMS\Security::instance()->verifyPersistentToken($token, 'editorjs_media')) {
            $this->json([
                'success' => 0,
                'message' => 'Ungültiges Sicherheitstoken für Editor.js-Uploads.',
            ], 403);
        }
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleFileUpload(string $fieldName, bool $imagesOnly, string $targetPath = 'editorjs'): array
    {
        if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
            return [
                'success' => 0,
                'message' => 'Keine Datei empfangen.',
            ];
        }

        $file = $_FILES[$fieldName];
        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ($imagesOnly && !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'], true)) {
            return [
                'success' => 0,
                'message' => 'Nur Bilddateien sind erlaubt.',
            ];
        }

        $storedFile = MediaService::getInstance()->uploadFile($file, trim($targetPath, '/'));

        if ($storedFile instanceof \CMS\WP_Error) {
            return [
                'success' => 0,
                'message' => $storedFile->get_error_message(),
            ];
        }

        $normalizedTargetPath = trim($targetPath, '/');
        $relativePath = ($normalizedTargetPath !== '' ? $normalizedTargetPath . '/' : '') . ltrim((string) $storedFile, '/');
        $fullPath = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $publicUrl = rtrim((string) UPLOAD_URL, '/') . '/' . $relativePath;

        return [
            'success' => 1,
            'file' => [
                'url' => $publicUrl,
                'name' => basename((string) $storedFile),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'extension' => strtolower((string) pathinfo((string) $storedFile, PATHINFO_EXTENSION)),
            ],
        ];
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleImageFetchByUrl(): array
    {
        $payload = $this->getJsonInput();
        $url = trim((string) ($payload['url'] ?? $_POST['url'] ?? ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => 0,
                'message' => 'Ungültige Bild-URL.',
            ];
        }

        $download = $this->downloadRemoteFile($url, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp']);
        if ($download['success'] !== true) {
            return [
                'success' => 0,
                'message' => (string) ($download['message'] ?? 'Bild konnte nicht geladen werden.'),
            ];
        }

        $tmpFile = (string) $download['tmpFile'];
        $tmpName = basename((string) parse_url($url, PHP_URL_PATH)) ?: ('remote-image.' . ($download['extension'] ?? 'jpg'));

        $uploadPayload = [
            'name' => $tmpName,
            'type' => (string) ($download['contentType'] ?? 'application/octet-stream'),
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile) ?: 0,
        ];

        $result = MediaService::getInstance()->uploadFile($uploadPayload, 'editorjs');

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($result instanceof \CMS\WP_Error) {
            return [
                'success' => 0,
                'message' => $result->get_error_message(),
            ];
        }

        $relativePath = 'editorjs/' . ltrim((string) $result, '/');
        $fullPath = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return [
            'success' => 1,
            'file' => [
                'url' => rtrim((string) UPLOAD_URL, '/') . '/' . $relativePath,
                'name' => basename((string) $result),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0,
                'extension' => strtolower((string) pathinfo((string) $result, PATHINFO_EXTENSION)),
            ],
        ];
    }

    /**
     * @return array{success:int,items?:array<int,array<string,mixed>>,message?:string}
     */
    private function handleImageLibraryList(): array
    {
        $items = [];
        $rootPath = rtrim((string) UPLOAD_PATH, '/\\');
        $rootUrl = rtrim((string) UPLOAD_URL, '/');

        if (!is_dir($rootPath)) {
            return [
                'success' => 1,
                'items' => [],
            ];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($rootPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = ltrim(str_replace('\\', '/', substr($absolutePath, strlen($rootPath))), '/');

            if ($relativePath === 'member' || str_starts_with($relativePath, 'member/')) {
                continue;
            }

            $items[] = [
                'name' => $file->getFilename(),
                'path' => $relativePath,
                'url' => $rootUrl . '/' . $relativePath,
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return (int) ($right['modified'] ?? 0) <=> (int) ($left['modified'] ?? 0);
        });

        return [
            'success' => 1,
            'items' => array_slice($items, 0, 250),
        ];
    }

    /**
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    private function handleFeaturedImageUpload(): array
    {
        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            return [
                'success' => 0,
                'message' => 'Keine Bilddatei empfangen.',
            ];
        }

        $slug = trim((string) ($_POST['slug'] ?? ''));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $slug));
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'artikelbild';
        }

        $contentType = in_array($_POST['content_type'] ?? '', ['post', 'page'], true)
            ? $_POST['content_type']
            : 'post';

        $baseFolder = $contentType === 'page' ? 'pages' : 'articles';
        $isNew = !empty($_POST['is_new']);
        $subFolder = $isNew ? 'temp' : $slug;
        $targetPath = $baseFolder . '/' . $subFolder;

        $file = $_FILES['image'];
        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $file['name'] = $slug . ($extension !== '' ? '.' . $extension : '');
        $_FILES['image'] = $file;

        $result = $this->handleFileUpload('image', true, $targetPath);

        if ($result['success'] === 1 && isset($result['file']['url'])) {
            $mediaService = MediaService::getInstance();
            $mediaService->ensureCategory('PhinIT-Cover', 'phinit-cover');
            $uploadUrl = rtrim((string) (defined('UPLOAD_URL') ? UPLOAD_URL : ''), '/');
            $fileUrl = rtrim((string) $result['file']['url'], '/');
            $relativePath = $uploadUrl !== '' && str_starts_with($fileUrl, $uploadUrl)
                ? ltrim(substr($fileUrl, strlen($uploadUrl)), '/')
                : '';
            if ($relativePath !== '') {
                $mediaService->assignCategory($relativePath, 'phinit-cover');
            }

            $result['temp_path'] = $relativePath;
            $result['target_folder'] = $targetPath;
            $result['is_temp'] = $isNew;
            $result['expected_folder'] = $baseFolder . '/' . $slug;
        }

        return $result;
    }

    /**
     * @return array{success:int,link?:string,meta?:array<string,mixed>,message?:string}
     */
    private function handleLinkMetadataFetch(): array
    {
        $payload = $this->getJsonInput();
        $url = trim((string) ($payload['url'] ?? $payload['link'] ?? $_POST['url'] ?? ''));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => 0,
                'message' => 'Ungültige Link-URL.',
            ];
        }

        $htmlResult = $this->downloadRemoteHtml($url);
        if ($htmlResult['success'] !== true) {
            return [
                'success' => 0,
                'message' => (string) ($htmlResult['message'] ?? 'Metadaten konnten nicht geladen werden.'),
            ];
        }

        return [
            'success' => 1,
            'link' => $url,
            'meta' => $this->extractLinkMetadata($url, (string) $htmlResult['html']),
        ];
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
     * @param string[] $allowedMimePrefixes
     * @return array{success:bool,tmpFile?:string,contentType?:string,extension?:string,message?:string}
     */
    private function downloadRemoteFile(string $url, array $allowedMimePrefixes): array
    {
        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS EditorJS/2.1',
            'timeout' => 12,
            'connectTimeout' => 5,
            'maxBytes' => 10 * 1024 * 1024,
            'allowedContentTypes' => $allowedMimePrefixes,
        ]);

        if (!$response['success']) {
            return [
                'success' => false,
                'message' => (string) ($response['error'] ?? 'Remote-Datei konnte nicht geladen werden.'),
            ];
        }

        $content = (string) ($response['body'] ?? '');
        $contentType = (string) ($response['contentType'] ?? '');

        $extension = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = match ($contentType) {
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/avif' => 'avif',
                'image/bmp' => 'bmp',
                default => 'jpg',
            };
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'cms_ejs_');
        if ($tmpFile === false || file_put_contents($tmpFile, $content) === false) {
            return [
                'success' => false,
                'message' => 'Temporäre Datei konnte nicht geschrieben werden.',
            ];
        }

        return [
            'success' => true,
            'tmpFile' => $tmpFile,
            'contentType' => $contentType,
            'extension' => $extension,
        ];
    }

    /**
     * @return array{success:bool,html?:string,message?:string}
     */
    private function downloadRemoteHtml(string $url): array
    {
        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS LinkTool/2.1',
            'timeout' => 10,
            'connectTimeout' => 5,
            'maxBytes' => 1024 * 1024,
            'allowedContentTypes' => ['text/html', 'application/xhtml+xml'],
        ]);

        $html = (string) ($response['body'] ?? '');
        if (!$response['success'] || trim($html) === '') {
            return [
                'success' => false,
                'message' => (string) ($response['error'] ?? 'Remote-HTML konnte nicht geladen werden.'),
            ];
        }

        return [
            'success' => true,
            'html' => $html,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function extractLinkMetadata(string $sourceUrl, string $html): array
    {
        $title = '';
        $description = '';
        $image = '';
        $siteName = parse_url($sourceUrl, PHP_URL_HOST) ?: '';

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$doc->loadHTML($html);
        libxml_clear_errors();

        $titleNodes = $doc->getElementsByTagName('title');
        if ($titleNodes->length > 0) {
            $title = trim((string) $titleNodes->item(0)?->textContent);
        }

        foreach ($doc->getElementsByTagName('meta') as $metaTag) {
            $property = strtolower((string) $metaTag->getAttribute('property'));
            $name = strtolower((string) $metaTag->getAttribute('name'));
            $content = trim((string) $metaTag->getAttribute('content'));

            if ($content === '') {
                continue;
            }

            if ($title === '' && in_array($property, ['og:title', 'twitter:title'], true)) {
                $title = $content;
            }

            if ($description === '' && in_array($name, ['description', 'twitter:description'], true)) {
                $description = $content;
            }

            if ($description === '' && $property === 'og:description') {
                $description = $content;
            }

            if ($image === '' && in_array($property, ['og:image', 'twitter:image'], true)) {
                $image = $this->absolutizeUrl($content, $sourceUrl);
            }

            if ($siteName === '' && $property === 'og:site_name') {
                $siteName = $content;
            }
        }

        if ($title === '') {
            $title = $sourceUrl;
        }

        return [
            'title' => strip_tags($title),
            'description' => strip_tags($description),
            'site_name' => strip_tags($siteName),
            'image' => [
                'url' => filter_var($image, FILTER_VALIDATE_URL) ?: '',
            ],
        ];
    }

    private function absolutizeUrl(string $url, string $baseUrl): string
    {
        if ($url === '') {
            return '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return $url;
        }

        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $port . $url;
        }

        $path = (string) ($baseParts['path'] ?? '/');
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $scheme . '://' . $host . $port . ($dir !== '' ? $dir : '') . '/' . ltrim($url, '/');
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
