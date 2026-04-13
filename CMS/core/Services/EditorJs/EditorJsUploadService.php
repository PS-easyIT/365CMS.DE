<?php
/**
 * Upload-Logik für Editor.js Medien-Requests.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Services\MediaDeliveryService;
use CMS\Services\MediaService;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsUploadService
{
    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    public function uploadFile(string $fieldName, bool $imagesOnly, string $targetPath = 'editorjs', array $post = [], array $files = []): array
    {
        $file = $files[$fieldName] ?? null;
        if (!is_array($file) || $file === []) {
            return [
                'success' => 0,
                'message' => 'Keine Datei empfangen.',
            ];
        }

        $targetPath = $this->resolveRequestedTargetPath($post, $targetPath);

        return $this->storeUploadedFile($file, $imagesOnly, $targetPath);
    }

    /**
     * @param array<string,mixed> $post
     */
    public function resolveRequestedTargetPath(array $post = [], string $defaultTargetPath = 'editorjs'): string
    {
        $defaultTargetPath = trim(str_replace('\\', '/', $defaultTargetPath), '/');
        if ($defaultTargetPath === '') {
            $defaultTargetPath = 'editorjs';
        }

        $contentType = in_array((string) ($post['content_type'] ?? ''), ['post', 'page'], true)
            ? (string) $post['content_type']
            : '';

        if ($contentType === '') {
            return $defaultTargetPath;
        }

        $folderSlug = $this->sanitizeFolderSegment((string) ($post['content_slug'] ?? ''));
        if ($folderSlug === '') {
            $folderSlug = $this->sanitizeFolderSegment((string) ($post['content_slug_fallback'] ?? ''));
        }
        if ($folderSlug === '') {
            $folderSlug = $this->sanitizeFolderSegment((string) ($post['content_title'] ?? ''));
        }
        if ($folderSlug === '') {
            $folderSlug = $this->sanitizeFolderSegment((string) ($post['content_title_fallback'] ?? ''));
        }

        $baseFolder = $contentType === 'page' ? 'pages' : 'articles';
        if ($folderSlug !== '') {
            return $baseFolder . '/' . $folderSlug;
        }

        $draftKey = $this->sanitizeFolderSegment((string) ($post['draft_key'] ?? ''));
        if ($draftKey !== '') {
            return $baseFolder . '/temp/' . $draftKey;
        }

        return $defaultTargetPath;
    }

    /**
     * @param array<string,mixed> $file
     * @return array{success:int,file?:array<string,mixed>,message?:string}
     */
    public function storeUploadedFile(array $file, bool $imagesOnly, string $targetPath = 'editorjs'): array
    {
        if ($imagesOnly) {
            $file = $this->normalizeImageUploadFile($file);
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if ($imagesOnly && !$this->isAllowedImageExtension($extension)) {
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

        return [
            'success' => 1,
            'file' => $this->buildFilePayload((string) $storedFile, $targetPath),
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{success:int,file?:array<string,mixed>,message?:string,temp_path?:string,target_folder?:string,is_temp?:bool,expected_folder?:string}
     */
    public function uploadFeaturedImage(array $post = [], array $files = []): array
    {
        $file = $files['image'] ?? null;
        if (!is_array($file) || $file === []) {
            return [
                'success' => 0,
                'message' => 'Keine Bilddatei empfangen.',
            ];
        }

        $slug = trim((string) ($post['slug'] ?? ''));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $slug));
        $slug = trim($slug, '_');
        if ($slug === '') {
            $slug = 'artikelbild';
        }

        $contentType = in_array($post['content_type'] ?? '', ['post', 'page'], true)
            ? (string) $post['content_type']
            : 'post';

        $baseFolder = $contentType === 'page' ? 'pages' : 'articles';
        $isNew = !empty($post['is_new']);
        $subFolder = $isNew ? 'temp' : $slug;
        $targetPath = $baseFolder . '/' . $subFolder;

        $file = $this->normalizeImageUploadFile($file);
        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $file['name'] = $slug . ($extension !== '' ? '.' . $extension : '');

        $result = $this->storeUploadedFile($file, true, $targetPath);

        if ($result['success'] === 1 && isset($result['file']['url'])) {
            $mediaService = MediaService::getInstance();
            $mediaService->ensureCategory('PhinIT-Cover', 'phinit-cover');

            $relativePath = $this->extractRelativeMediaPath((string) $result['file']['url']);

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

    private function isAllowedImageExtension(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'], true);
    }

    /**
     * @param array<string,mixed> $file
     * @return array<string,mixed>
     */
    private function normalizeImageUploadFile(array $file): array
    {
        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            return $file;
        }

        $detectedExtension = $this->detectImageExtensionFromFile($tmpName, (string) ($file['type'] ?? ''));
        if ($detectedExtension === '') {
            return $file;
        }

        $currentName = trim((string) ($file['name'] ?? ''));
        $currentBaseName = pathinfo($currentName !== '' ? $currentName : 'clipboard-image', PATHINFO_FILENAME);
        $currentBaseName = trim((string) $currentBaseName, " \t\n\r\0\x0B.-");
        if ($currentBaseName === '') {
            $currentBaseName = 'clipboard-image';
        }

        $file['name'] = $currentBaseName . '.' . $detectedExtension;

        return $file;
    }

    private function detectImageExtensionFromFile(string $tmpName, string $reportedMime = ''): string
    {
        $mimeCandidates = [];

        $detectedImageMime = $this->detectActualImageMimeType($tmpName);
        if ($detectedImageMime !== '') {
            $mimeCandidates[] = $detectedImageMime;
        }

        $reportedMime = strtolower(trim($reportedMime));
        if ($reportedMime !== '') {
            $mimeCandidates[] = $reportedMime;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (is_string($detectedMime) && trim($detectedMime) !== '') {
                    $mimeCandidates[] = strtolower(trim($detectedMime));
                }
            }
        }

        foreach ($mimeCandidates as $mimeType) {
            $extension = match ($mimeType) {
                'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
                'image/png', 'image/x-png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'image/avif' => 'avif',
                'image/bmp', 'image/x-bmp', 'image/x-ms-bmp', 'image/ms-bmp' => 'bmp',
                default => '',
            };

            if ($extension !== '') {
                return $extension;
            }
        }

        return '';
    }

    private function detectActualImageMimeType(string $tmpName): string
    {
        if (!is_file($tmpName)) {
            return '';
        }

        if (function_exists('exif_imagetype')) {
            $imageType = @exif_imagetype($tmpName);
            if (is_int($imageType)) {
                $mimeType = image_type_to_mime_type($imageType);
                if (is_string($mimeType) && trim($mimeType) !== '') {
                    return strtolower(trim($mimeType));
                }
            }
        }

        $imageInfo = @getimagesize($tmpName);
        if (is_array($imageInfo) && isset($imageInfo['mime']) && is_string($imageInfo['mime']) && trim($imageInfo['mime']) !== '') {
            return strtolower(trim($imageInfo['mime']));
        }

        return '';
    }

    private function sanitizeFolderSegment(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
        $value = trim($value, '-');

        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, 80)
            : substr($value, 0, 80);
    }

    /**
     * @return array{url:string,name:string,size:int,extension:string}
     */
    private function buildFilePayload(string $storedFile, string $targetPath): array
    {
        $normalizedTargetPath = trim($targetPath, '/');
        $relativePath = ($normalizedTargetPath !== '' ? $normalizedTargetPath . '/' : '') . ltrim($storedFile, '/');
        $fullPath = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $mediaDelivery = MediaDeliveryService::getInstance();
        $accessUrl = $this->toRelativeMediaUrl($mediaDelivery->buildDeliveryUrl($relativePath, 'inline'));

        return [
            'url' => $accessUrl,
            'name' => basename($storedFile),
            'size' => file_exists($fullPath) ? (int) filesize($fullPath) : 0,
            'extension' => strtolower((string) pathinfo($storedFile, PATHINFO_EXTENSION)),
        ];
    }

    private function toRelativeMediaUrl(string $url): string
    {
        $trimmedUrl = trim($url);
        if ($trimmedUrl === '') {
            return '';
        }

        $parts = parse_url($trimmedUrl);
        if (!is_array($parts)) {
            return $trimmedUrl;
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '' || ($path !== '/media-file' && !str_starts_with($path, '/uploads/'))) {
            return $trimmedUrl;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }

    private function extractRelativeMediaPath(string $url): string
    {
        $trimmedUrl = trim($url);
        if ($trimmedUrl === '') {
            return '';
        }

        $parts = parse_url($trimmedUrl);
        if (!is_array($parts)) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '/media-file') {
            $query = [];
            parse_str((string) ($parts['query'] ?? ''), $query);
            $relativePath = trim(str_replace('\\', '/', (string) ($query['path'] ?? '')), '/');
            return str_contains($relativePath, '..') ? '' : $relativePath;
        }

        if (str_starts_with($path, '/uploads/')) {
            $relativePath = ltrim(substr($path, strlen('/uploads/')), '/');
            return str_contains($relativePath, '..') ? '' : trim($relativePath, '/');
        }

        $uploadUrl = rtrim((string) (defined('UPLOAD_URL') ? UPLOAD_URL : ''), '/');
        if ($uploadUrl !== '' && str_starts_with($trimmedUrl, $uploadUrl)) {
            $relativePath = ltrim(substr($trimmedUrl, strlen($uploadUrl)), '/');
            return str_contains($relativePath, '..') ? '' : trim($relativePath, '/');
        }

        return '';
    }
}