<?php
/**
 * Remote-Fetch- und Link-Metadatenlogik für Editor.js.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Http\Client as HttpClient;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsRemoteMediaService
{
    private const TMP_FILE_PREFIX = 'cms_ejs_';
    private const MAX_REMOTE_URL_LENGTH = 2048;
    private const MAX_METADATA_HTML_BYTES = 1048576;
    private const MAX_METADATA_TITLE_LENGTH = 180;
    private const MAX_METADATA_DESCRIPTION_LENGTH = 400;
    private const MAX_METADATA_SITE_NAME_LENGTH = 120;

    private Logger $logger;

    public function __construct(
        private readonly EditorJsUploadService $uploadService
    ) {
        $this->logger = Logger::instance()->withChannel('editorjs.remote-media');
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $post
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    public function fetchImageByUrl(array $payload = [], array $post = []): array
    {
        $url = $this->normalizeRemoteUrl((string) ($payload['url'] ?? $post['url'] ?? ''));

        if ($url === '') {
            return [
                'success' => 0,
                'message' => 'Ungültige oder unsichere Bild-URL.',
            ];
        }

        $download = $this->downloadRemoteFile($url, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp']);
        if ($download['success'] !== true) {
            return [
                'success' => 0,
                'message' => 'Bild konnte nicht geladen werden.',
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
            $this->cleanupTemporaryFile($tmpFile);
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $post
     * @return array{success:int,link?:string,meta?:array<string,mixed>,message?:string}
     */
    public function fetchLinkMetadata(array $payload = [], array $post = []): array
    {
        $url = $this->normalizeRemoteUrl((string) ($payload['url'] ?? $payload['link'] ?? $post['url'] ?? ''));

        if ($url === '') {
            return [
                'success' => 0,
                'message' => 'Ungültige oder unsichere Link-URL.',
            ];
        }

        $htmlResult = $this->downloadRemoteHtml($url);
        if ($htmlResult['success'] !== true) {
            return [
                'success' => 0,
                'message' => 'Metadaten konnten nicht geladen werden.',
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
            $this->logger->warning('Remote-Bildabruf fehlgeschlagen', [
                'url' => $this->maskUrlForLogs($url),
                'error' => $this->sanitizeLogMessage((string) ($response['error'] ?? 'Remote-Datei konnte nicht geladen werden.')),
                'status' => (int) ($response['status'] ?? 0),
            ]);

            return [
                'success' => false,
                'message' => 'Remote-Datei konnte nicht geladen werden.',
            ];
        }

        $content = (string) ($response['body'] ?? '');
        $contentType = (string) ($response['contentType'] ?? '');

        if ($content === '' || !$this->isAllowedContentType($contentType, $allowedMimePrefixes)) {
            return [
                'success' => false,
                'message' => 'Remote-Datei hat keinen erlaubten Typ.',
            ];
        }

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

        $tmpFile = $this->createTemporaryFile($content);
        if ($tmpFile === null) {
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
            'maxBytes' => self::MAX_METADATA_HTML_BYTES,
            'allowedContentTypes' => ['text/html', 'application/xhtml+xml'],
        ]);

        $html = (string) ($response['body'] ?? '');
        if (!$response['success'] || trim($html) === '') {
            $this->logger->warning('Remote-Link-Metadaten konnten nicht geladen werden', [
                'url' => $this->maskUrlForLogs($url),
                'error' => $this->sanitizeLogMessage((string) ($response['error'] ?? 'Remote-HTML konnte nicht geladen werden.')),
                'status' => (int) ($response['status'] ?? 0),
            ]);

            return [
                'success' => false,
                'message' => 'Remote-HTML konnte nicht geladen werden.',
            ];
        }

        return [
            'success' => true,
            'html' => mb_substr($html, 0, self::MAX_METADATA_HTML_BYTES),
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
        $siteName = (string) (parse_url($sourceUrl, PHP_URL_HOST) ?: '');

        $doc = new \DOMDocument();
        $previousState = libxml_use_internal_errors(true);
        @$doc->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

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
                $image = $this->normalizeRemoteUrl($this->absolutizeUrl($content, $sourceUrl));
            }

            if ($siteName === '' && $property === 'og:site_name') {
                $siteName = $content;
            }
        }

        if ($title === '') {
            $title = $sourceUrl;
        }

        return [
            'title' => $this->sanitizeMetadataText($title, self::MAX_METADATA_TITLE_LENGTH),
            'description' => $this->sanitizeMetadataText($description, self::MAX_METADATA_DESCRIPTION_LENGTH),
            'site_name' => $this->sanitizeMetadataText((string) $siteName, self::MAX_METADATA_SITE_NAME_LENGTH),
            'image' => [
                'url' => $image,
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

    private function normalizeRemoteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > self::MAX_REMOTE_URL_LENGTH) {
            return '';
        }

        if (str_contains($url, "\r") || str_contains($url, "\n")) {
            return '';
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host
            . (isset($parts['port']) ? ':' . (int) $parts['port'] : '')
            . $path
            . $query;
    }

    private function isAllowedContentType(string $contentType, array $allowedMimePrefixes): bool
    {
        foreach ($allowedMimePrefixes as $allowedType) {
            if ($allowedType !== '' && stripos($contentType, $allowedType) === 0) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeMetadataText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_substr($value, 0, $maxLength);
    }

    private function sanitizeLogMessage(string $message): string
    {
        $message = str_replace(["\r", "\n", "\0"], ' ', $message);
        $message = preg_replace('/\s+/u', ' ', $message) ?? $message;

        return mb_substr(trim($message), 0, 300);
    }

    private function maskUrlForLogs(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $host = (string) ($parts['host'] ?? '');
        $path = (string) ($parts['path'] ?? '');
        $maskedPath = $path !== '' ? '/' . ltrim(basename($path), '/') : '';

        return (($parts['scheme'] ?? 'https')) . '://' . $host . $maskedPath;
    }

    private function createTemporaryFile(string $content): ?string
    {
        $tempDir = realpath(sys_get_temp_dir());
        if ($tempDir === false || !is_dir($tempDir)) {
            return null;
        }

        $tmpFile = tempnam($tempDir, self::TMP_FILE_PREFIX);
        if ($tmpFile === false) {
            return null;
        }

        $resolvedTmpFile = realpath($tmpFile);
        if ($resolvedTmpFile === false || is_link($resolvedTmpFile) || !str_starts_with($resolvedTmpFile, rtrim($tempDir, '\\/') . DIRECTORY_SEPARATOR)) {
            if (is_file($tmpFile)) {
                @unlink($tmpFile);
            }

            return null;
        }

        if (file_put_contents($resolvedTmpFile, $content, LOCK_EX) === false) {
            @unlink($resolvedTmpFile);
            return null;
        }

        return $resolvedTmpFile;
    }

    private function cleanupTemporaryFile(string $tmpFile): void
    {
        if ($tmpFile === '' || !is_file($tmpFile)) {
            return;
        }

        $tempDir = realpath(sys_get_temp_dir());
        $resolvedTmpFile = realpath($tmpFile);
        if ($tempDir === false || $resolvedTmpFile === false || is_link($resolvedTmpFile)) {
            return;
        }

        if (!str_starts_with($resolvedTmpFile, rtrim($tempDir, '\\/') . DIRECTORY_SEPARATOR)) {
            return;
        }

        if (!str_starts_with(basename($resolvedTmpFile), self::TMP_FILE_PREFIX)) {
            return;
        }

        @unlink($resolvedTmpFile);
    }
}