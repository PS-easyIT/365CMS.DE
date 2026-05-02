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
    /**
     * @return array{success:int,items?:array<int,array<string,mixed>>,message?:string}
     */
    public function listImages(string $filenamePrefix = ''): array
    {
        $items = [];
        $rootPath = rtrim((string) UPLOAD_PATH, '/\\');
        $mediaDelivery = MediaDeliveryService::getInstance();
        $filenamePrefix = $this->normalizeFilenamePrefix($filenamePrefix);
        if ($filenamePrefix === null) {
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

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg', 'ico'];

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

            if ($filenamePrefix !== '' && !str_starts_with($file->getFilename(), $filenamePrefix)) {
                continue;
            }

            $items[] = [
                'name' => $file->getFilename(),
                'path' => $relativePath,
                'url' => $this->toRelativeMediaUrl($mediaDelivery->buildDeliveryUrl($relativePath, 'inline')),
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

    private function normalizeFilenamePrefix(string $filenamePrefix): ?string
    {
        $filenamePrefix = trim($filenamePrefix);
        if ($filenamePrefix === '') {
            return '';
        }

        if (strlen($filenamePrefix) > 120 || preg_match('/^[A-Za-z0-9._-]+$/', $filenamePrefix) !== 1) {
            return null;
        }

        return $filenamePrefix;
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
        if ($path === '' || ($path !== '/media-file' && !str_starts_with($path, '/uploads/'))) {
            return $trimmedUrl;
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }
}