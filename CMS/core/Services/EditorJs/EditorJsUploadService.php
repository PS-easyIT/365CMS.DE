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

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $file['name'] = $slug . ($extension !== '' ? '.' . $extension : '');

        $result = $this->storeUploadedFile($file, true, $targetPath);

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

    private function isAllowedImageExtension(string $extension): bool
    {
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'], true);
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

        return [
            'url' => $mediaDelivery->buildAccessUrl($relativePath, true),
            'name' => basename($storedFile),
            'size' => file_exists($fullPath) ? (int) filesize($fullPath) : 0,
            'extension' => strtolower((string) pathinfo($storedFile, PATHINFO_EXTENSION)),
        ];
    }
}