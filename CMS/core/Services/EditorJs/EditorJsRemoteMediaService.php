<?php
/**
 * Remote-Fetch- und Link-Metadatenlogik für Editor.js.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Http\Client as HttpClient;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsRemoteMediaService
{
    public function __construct(
        private readonly EditorJsUploadService $uploadService
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $post
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    public function fetchImageByUrl(array $payload = [], array $post = []): array
    {
        $url = trim((string) ($payload['url'] ?? $post['url'] ?? ''));

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

        try {
            return $this->uploadService->storeUploadedFile($uploadPayload, true, 'editorjs');
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $post
     * @return array{success:int,link?:string,meta?:array<string,mixed>,message?:string}
     */
    public function fetchLinkMetadata(array $payload = [], array $post = []): array
    {
        $url = trim((string) ($payload['url'] ?? $payload['link'] ?? $post['url'] ?? ''));

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
            'site_name' => strip_tags((string) $siteName),
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
}