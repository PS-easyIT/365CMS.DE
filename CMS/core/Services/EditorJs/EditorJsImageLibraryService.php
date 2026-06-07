<?php
/**
 * Listing-Service für die Editor.js Bildbibliothek.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Services\MediaDeliveryService;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsImageLibraryService
{
    private const MAX_UNFILTERED_ITEMS = 250;
    private const MAX_FILTERED_ITEMS = 1000;

    /**
     * @return array{success:int,items?:array<int,array<string,mixed>>,message?:string}
     */
    public function listImages(string $filenamePrefix = '', string $pathPrefix = ''): array
    {
        $items = [];
        $rootPath = rtrim((string) UPLOAD_PATH, '/\\');
        $mediaDelivery = MediaDeliveryService::getInstance();
        $filenamePrefix = $this->normalizeFilenamePrefix($filenamePrefix);
        $pathPrefix = $this->normalizePathPrefix($pathPrefix);
        if ($filenamePrefix === null) {
            return [
                'success' => 1,
                'items' => [],
            ];
        }

        if ($pathPrefix === null) {
            return [
                'success' => 1,
                'items' => [],
            ];
        }

        if (!is_dir($rootPath)) {
            return [
                'success' => 1,
                'items' => [],
            ];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico'];

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

            if ($relativePath === '' || $this->containsHiddenSegment($relativePath)) {
                continue;
            }

            if ($relativePath === 'member' || str_starts_with($relativePath, 'member/')) {
                continue;
            }

            if ($pathPrefix !== '' && $relativePath !== $pathPrefix && !str_starts_with($relativePath, $pathPrefix . '/')) {
                continue;
            }

            if ($filenamePrefix !== '' && !str_starts_with($file->getFilename(), $filenamePrefix)) {
                continue;
            }

            $items[] = [
                'name' => $file->getFilename(),
                'path' => $relativePath,
                'url' => $this->toRelativeMediaUrl($mediaDelivery->buildAccessUrl($relativePath, true)),
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return (int) ($right['modified'] ?? 0) <=> (int) ($left['modified'] ?? 0);
        });

        return [
            'success' => 1,
            'items' => ($filenamePrefix !== '' || $pathPrefix !== '')
                ? array_slice($items, 0, self::MAX_FILTERED_ITEMS)
                : array_slice($items, 0, self::MAX_UNFILTERED_ITEMS),
        ];
    }

    private function normalizeFilenamePrefix(string $filenamePrefix): ?string
    {
        $filenamePrefix = trim($filenamePrefix);
        if ($filenamePrefix === '') {
            return '';
        }

        $filenamePrefix = rtrim($filenamePrefix, '*');
        if ($filenamePrefix === '') {
            return '';
        }

        if (strlen($filenamePrefix) > 120 || preg_match('/^[A-Za-z0-9._-]+$/', $filenamePrefix) !== 1) {
            return null;
        }

        return $filenamePrefix;
    }

    private function normalizePathPrefix(string $pathPrefix): ?string
    {
        $pathPrefix = trim(str_replace('\\', '/', $pathPrefix), '/');
        if ($pathPrefix === '') {
            return '';
        }

        $pathPrefix = preg_replace('#/+#', '/', $pathPrefix) ?? '';
        if ($pathPrefix === '' || str_contains($pathPrefix, '..') || preg_match('/[\x00-\x1F\x7F]/', $pathPrefix) === 1) {
            return null;
        }

        if (preg_match('#^[A-Za-z0-9._\-/]+$#', $pathPrefix) !== 1) {
            return null;
        }

        foreach (explode('/', $pathPrefix) as $segment) {
            if ($segment === '' || str_starts_with($segment, '.')) {
                return null;
            }
        }

        return $pathPrefix;
    }

    private function containsHiddenSegment(string $relativePath): bool
    {
        foreach (explode('/', $relativePath) as $segment) {
            if ($segment !== '' && str_starts_with($segment, '.')) {
                return true;
            }
        }

        return false;
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
        $uploadPath = '/' . trim((string) (parse_url((string) UPLOAD_URL, PHP_URL_PATH) ?? 'uploads'), '/');
        if ($path === '' || ($path !== '/media-file' && !str_starts_with($path, '/uploads/') && !str_starts_with($path, $uploadPath . '/'))) {
            return $trimmedUrl;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }
}