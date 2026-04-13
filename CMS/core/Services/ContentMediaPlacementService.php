<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Json;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class ContentMediaPlacementService
{
    private static ?self $instance = null;

    private MediaService $mediaService;
    private MediaDeliveryService $mediaDelivery;
    private Logger $logger;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->mediaService = MediaService::getInstance();
        $this->mediaDelivery = MediaDeliveryService::getInstance();
        $this->logger = Logger::instance()->withChannel('media.content-placement');
    }

    /**
     * @param array<int,string> $contents
     * @return array<int,string>
     */
    public function relocateTemporaryContentMediaBatch(array $contents, string $contentType, string $slug): array
    {
        $baseFolder = $this->resolveBaseFolder($contentType);
        $folderSlug = $this->sanitizeFolderSegment($slug);

        if ($baseFolder === '' || $folderSlug === '' || $contents === []) {
            return $contents;
        }

        $sourcePaths = [];
        foreach ($contents as $content) {
            foreach ($this->extractTemporaryRelativePaths((string) $content, $baseFolder) as $relativePath) {
                $sourcePaths[$relativePath] = true;
            }
        }

        $pathMap = $this->moveTemporaryMediaPaths(array_keys($sourcePaths), $baseFolder, $folderSlug);
        if ($pathMap === []) {
            return $contents;
        }

        $updatedContents = [];
        foreach ($contents as $index => $content) {
            $updatedContents[$index] = $this->replaceContentReferences((string) $content, $pathMap);
        }

        return $updatedContents;
    }

    public function relocateTemporaryFeaturedImage(string $featuredImageUrl, string $featuredImageTempPath, string $contentType, string $slug): string
    {
        $baseFolder = $this->resolveBaseFolder($contentType);
        $folderSlug = $this->sanitizeFolderSegment($slug);

        if ($baseFolder === '' || $folderSlug === '') {
            return $featuredImageUrl;
        }

        $sourcePath = $this->extractRelativeMediaPath($featuredImageTempPath !== '' ? $featuredImageTempPath : $featuredImageUrl);
        if ($sourcePath === '' || !str_starts_with($sourcePath, $baseFolder . '/temp/')) {
            return $featuredImageUrl;
        }

        $pathMap = $this->moveTemporaryMediaPaths([$sourcePath], $baseFolder, $folderSlug);
        $targetPath = $pathMap[$sourcePath] ?? '';

        if ($targetPath === '') {
            return $featuredImageUrl;
        }

        return $this->mediaDelivery->buildAccessUrl($targetPath, true);
    }

    private function resolveBaseFolder(string $contentType): string
    {
        return match (strtolower(trim($contentType))) {
            'post' => 'articles',
            'page' => 'pages',
            default => '',
        };
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
     * @param array<int,string> $sourcePaths
     * @return array<string,string>
     */
    private function moveTemporaryMediaPaths(array $sourcePaths, string $baseFolder, string $folderSlug): array
    {
        $normalizedSources = [];
        foreach ($sourcePaths as $sourcePath) {
            $normalizedPath = $this->normalizeRelativeMediaPath((string) $sourcePath);
            if ($normalizedPath === '' || !str_starts_with($normalizedPath, $baseFolder . '/temp/')) {
                continue;
            }

            $normalizedSources[$normalizedPath] = true;
        }

        if ($normalizedSources === []) {
            return [];
        }

        $pathMap = [];
        foreach (array_keys($normalizedSources) as $sourcePath) {
            $targetPath = $this->resolveAvailableTargetPath(
                $baseFolder . '/' . $folderSlug . '/' . basename($sourcePath),
                $sourcePath
            );

            if ($targetPath === '') {
                continue;
            }

            $resolvedPath = $this->moveOrReusePath($sourcePath, $targetPath);
            if ($resolvedPath !== '') {
                $pathMap[$sourcePath] = $resolvedPath;
            }
        }

        return $pathMap;
    }

    private function moveOrReusePath(string $sourcePath, string $targetPath): string
    {
        $sourcePath = $this->normalizeRelativeMediaPath($sourcePath);
        $targetPath = $this->normalizeRelativeMediaPath($targetPath);

        if ($sourcePath === '' || $targetPath === '') {
            return '';
        }

        if ($sourcePath === $targetPath) {
            return $targetPath;
        }

        $sourceExists = $this->mediaService->pathExists($sourcePath);
        if (!$sourceExists) {
            return $this->mediaService->pathExists($targetPath) ? $targetPath : '';
        }

        if ($this->mediaService->pathExists($targetPath)) {
            $targetPath = $this->resolveAvailableTargetPath($targetPath, $sourcePath);
            if ($targetPath === '' || $targetPath === $sourcePath) {
                return '';
            }
        }

        $moved = $this->mediaService->moveFile($sourcePath, $targetPath);
        if ($moved instanceof \CMS\WP_Error) {
            $this->logger->warning('Temporäre Content-Mediendatei konnte nicht in den Slug-Ordner verschoben werden.', [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'error' => $moved->get_error_message(),
            ]);

            return '';
        }

        return $this->normalizeRelativeMediaPath((string) $moved);
    }

    private function resolveAvailableTargetPath(string $targetPath, string $sourcePath = ''): string
    {
        $normalizedTargetPath = $this->normalizeRelativeMediaPath($targetPath);
        $normalizedSourcePath = $this->normalizeRelativeMediaPath($sourcePath);

        if ($normalizedTargetPath === '') {
            return '';
        }

        if ($normalizedTargetPath === $normalizedSourcePath || !$this->mediaService->pathExists($normalizedTargetPath)) {
            return $normalizedTargetPath;
        }

        $directory = dirname($normalizedTargetPath);
        $directory = $directory === '.' ? '' : trim(str_replace('\\', '/', $directory), '/');
        $filename = pathinfo($normalizedTargetPath, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo($normalizedTargetPath, PATHINFO_EXTENSION));

        for ($suffix = 1; $suffix <= 200; $suffix++) {
            $candidate = ($directory !== '' ? $directory . '/' : '') . $filename . '-' . $suffix;
            if ($extension !== '') {
                $candidate .= '.' . $extension;
            }

            if ($candidate === $normalizedSourcePath || !$this->mediaService->pathExists($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return array<int,string>
     */
    private function extractTemporaryRelativePaths(string $content, string $baseFolder): array
    {
        $seen = [];
        $this->collectRelativeMediaPathsFromValue($content, $seen, $baseFolder);

        return array_keys($seen);
    }

    /**
     * @param array<string,bool> $seen
     */
    private function collectRelativeMediaPathsFromValue(mixed $value, array &$seen, string $baseFolder): void
    {
        if (is_array($value)) {
            foreach ($value as $nestedValue) {
                $this->collectRelativeMediaPathsFromValue($nestedValue, $seen, $baseFolder);
            }

            return;
        }

        if (!is_string($value)) {
            return;
        }

        $trimmedValue = trim($value);
        if ($trimmedValue === '') {
            return;
        }

        $decoded = Json::decode($trimmedValue, true, null);
        if (is_array($decoded)) {
            $this->collectRelativeMediaPathsFromValue($decoded, $seen, $baseFolder);
        }

        foreach ($this->collectPotentialCandidatesFromString($trimmedValue) as $candidate) {
            $relativePath = $this->extractRelativeMediaPath($candidate);
            if ($relativePath !== '' && str_starts_with($relativePath, $baseFolder . '/temp/')) {
                $seen[$relativePath] = true;
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private function collectPotentialCandidatesFromString(string $value): array
    {
        $decodedValue = html_entity_decode(str_replace('\/', '/', $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $candidates = [$decodedValue];

        $matches = [];
        if (preg_match_all('#https?://[^\s<>"\']+|/(?:[A-Za-z0-9._%-]+/)*media-file\?[^\s<>"\']+|/(?:[A-Za-z0-9._%-]+/)*uploads/[^\s<>"\']+#i', $decodedValue, $matches) === 1 || !empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $candidates[] = (string) $match;
            }
        }

        $urlMatches = [];
        if (preg_match_all('#url\(([^)]+)\)#i', $decodedValue, $urlMatches) === 1 || !empty($urlMatches[1])) {
            foreach ($urlMatches[1] as $match) {
                $candidates[] = trim((string) $match, " \t\n\r\0\x0B\"'");
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $candidates), static fn(string $candidate): bool => $candidate !== '')));
    }

    private function extractRelativeMediaPath(string $candidate): string
    {
        $candidate = trim(html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $candidate = trim($candidate, " \t\n\r\0\x0B\"'");

        if ($candidate === '') {
            return '';
        }

        if (preg_match('#^url\((.+)\)$#i', $candidate, $matches) === 1) {
            $candidate = trim((string) ($matches[1] ?? ''), " \t\n\r\0\x0B\"'");
        }

        if ($candidate === '') {
            return '';
        }

        if (str_starts_with($candidate, 'media-file?')) {
            $candidate = '/' . $candidate;
        }

        if (preg_match('#^(?:/)?uploads/#i', $candidate) === 1) {
            return $this->normalizeRelativeMediaPath((string) preg_replace('#^(?:/)?uploads/#i', '', $candidate));
        }

        if (preg_match('#^[A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)+$#', $candidate) === 1) {
            return $this->normalizeRelativeMediaPath($candidate);
        }

        $parsedUrl = parse_url($candidate);
        if ($parsedUrl === false) {
            return '';
        }

        $host = strtolower(trim((string) ($parsedUrl['host'] ?? '')));
        if ($host !== '' && !$this->isLocalHost($host)) {
            return '';
        }

        return $this->extractRelativeMediaPathFromUrlParts(
            (string) ($parsedUrl['path'] ?? ''),
            (string) ($parsedUrl['query'] ?? '')
        );
    }

    private function extractRelativeMediaPathFromUrlParts(string $path, string $queryString = ''): string
    {
        $normalizedPath = '/' . ltrim((string) preg_replace('#/+#', '/', str_replace('\\', '/', trim($path))), '/');
        if ($normalizedPath === '/') {
            return '';
        }

        $sitePath = trim((string) (parse_url((string) SITE_URL, PHP_URL_PATH) ?? ''), '/');
        $uploadPath = trim((string) (parse_url((string) UPLOAD_URL, PHP_URL_PATH) ?? ''), '/');

        $mediaPrefixes = array_filter([
            '/media-file',
            $sitePath !== '' ? '/' . $sitePath . '/media-file' : '',
        ]);

        foreach ($mediaPrefixes as $mediaPrefix) {
            if ($normalizedPath === $mediaPrefix) {
                parse_str($queryString, $query);
                return $this->normalizeRelativeMediaPath((string) ($query['path'] ?? ''));
            }
        }

        $uploadPrefixes = array_filter([
            '/uploads/',
            $uploadPath !== '' ? '/' . $uploadPath . '/' : '',
        ]);

        foreach ($uploadPrefixes as $uploadPrefix) {
            if (str_starts_with($normalizedPath, $uploadPrefix)) {
                return $this->normalizeRelativeMediaPath(ltrim(substr($normalizedPath, strlen($uploadPrefix)), '/'));
            }
        }

        return '';
    }

    private function isLocalHost(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return true;
        }

        $knownHosts = array_filter([
            strtolower(trim((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?? ''))),
            strtolower(trim((string) (parse_url((string) UPLOAD_URL, PHP_URL_HOST) ?? ''))),
        ]);

        return in_array($host, $knownHosts, true);
    }

    private function normalizeRelativeMediaPath(string $relativePath): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath));
        $relativePath = preg_replace('#/+#', '/', $relativePath) ?? '';
        $relativePath = trim($relativePath, '/');

        if ($relativePath === '' || str_contains($relativePath, '..') || preg_match('/[\x00-\x1F\x7F]/', $relativePath) === 1) {
            return '';
        }

        return preg_match('#^[A-Za-z0-9._\-/]+$#', $relativePath) === 1 ? $relativePath : '';
    }

    /**
     * @param array<string,string> $pathMap
     */
    private function replaceContentReferences(string $content, array $pathMap): string
    {
        $decoded = Json::decode($content, true, null);
        if (is_array($decoded)) {
            $updated = $this->replaceReferencesInValue($decoded, $pathMap);
            $encoded = json_encode($updated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (is_string($encoded) && $encoded !== '') {
                return $encoded;
            }
        }

        return $this->replaceReferencesInString($content, $pathMap);
    }

    private function replaceReferencesInValue(mixed $value, array $pathMap): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $value[$key] = $this->replaceReferencesInValue($nestedValue, $pathMap);
            }

            return $value;
        }

        if (is_string($value)) {
            return $this->replaceReferencesInString($value, $pathMap);
        }

        return $value;
    }

    /**
     * @param array<string,string> $pathMap
     */
    private function replaceReferencesInString(string $value, array $pathMap): string
    {
        $replacedValue = $value;

        foreach ($pathMap as $oldPath => $newPath) {
            if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
                continue;
            }

            $encodedOldPath = urlencode($oldPath);
            $encodedNewPath = urlencode($newPath);
            $directOldPath = $this->encodePathForDirectUrl($oldPath);
            $directNewPath = $this->encodePathForDirectUrl($newPath);

            $replacements = [
                $oldPath => $newPath,
                str_replace('/', '\/', $oldPath) => str_replace('/', '\/', $newPath),
                $encodedOldPath => $encodedNewPath,
                rawurlencode($oldPath) => rawurlencode($newPath),
                $directOldPath => $directNewPath,
                '/uploads/' . $oldPath => '/uploads/' . $newPath,
                '/uploads/' . $directOldPath => '/uploads/' . $directNewPath,
                rtrim((string) UPLOAD_URL, '/') . '/' . $directOldPath => rtrim((string) UPLOAD_URL, '/') . '/' . $directNewPath,
                rtrim((string) SITE_URL, '/') . '/media-file?path=' . $encodedOldPath => rtrim((string) SITE_URL, '/') . '/media-file?path=' . $encodedNewPath,
            ];

            foreach ($replacements as $search => $replacement) {
                if ($search === '' || $search === $replacement) {
                    continue;
                }

                $replacedValue = str_replace($search, $replacement, $replacedValue);
            }
        }

        return $replacedValue;
    }

    private function encodePathForDirectUrl(string $relativePath): string
    {
        $normalizedPath = $this->normalizeRelativeMediaPath($relativePath);
        if ($normalizedPath === '') {
            return '';
        }

        $segments = array_map(static fn(string $segment): string => rawurlencode($segment), explode('/', $normalizedPath));

        return implode('/', $segments);
    }
}