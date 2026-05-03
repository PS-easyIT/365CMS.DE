<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Auth;
use CMS\CacheManager;
use CMS\Database;
use CMS\Logger;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class MediaDeliveryService
{
    private const INLINE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'avif'];
    private const ELFINDER_TMB_PREFIX = '.elfinder/.tmb/';
    private const STREAM_CHUNK_BYTES = 8192;

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function buildAccessUrl(string $relativePath, bool $preferInline = false): string
    {
        $normalizedPath = $this->normalizeRelativePath($relativePath);
        if ($normalizedPath === '') {
            return rtrim((string) UPLOAD_URL, '/');
        }

        if ($this->isPrivateMemberPath($normalizedPath)) {
            return $this->buildDeliveryUrl($normalizedPath, $preferInline ? 'inline' : 'attachment');
        }

        if ($this->containsHiddenSegment($normalizedPath)) {
            return $this->buildDeliveryUrl($normalizedPath, 'inline');
        }

        return $this->buildDirectUploadUrl($normalizedPath);
    }

    public function buildPreviewUrl(string $relativePath): string
    {
        $normalizedPath = $this->normalizeRelativePath($relativePath);
        if ($normalizedPath === '') {
            return $this->buildAccessUrl($relativePath, true);
        }

        if ($this->isInlineSafePath($normalizedPath)) {
            return $this->buildDeliveryUrl($normalizedPath, 'inline');
        }

        return $this->buildAccessUrl($normalizedPath, true);
    }

    public function buildDeliveryUrl(string $relativePath, string $disposition = 'attachment'): string
    {
        $normalizedPath = $this->normalizeRelativePath($relativePath);
        $query = http_build_query([
            'path' => $normalizedPath,
            'disposition' => $disposition === 'inline' ? 'inline' : 'attachment',
        ]);

        return rtrim((string) SITE_URL, '/') . '/media-file?' . $query;
    }

    public function normalizeUrl(string $url, bool $preferInline = false): string
    {
        $trimmedUrl = trim($url);
        if ($trimmedUrl === '') {
            return '';
        }

        $deliveryPrefix = rtrim((string) SITE_URL, '/') . '/media-file';
        if (str_starts_with($trimmedUrl, $deliveryPrefix)) {
            $queryString = (string) (parse_url($trimmedUrl, PHP_URL_QUERY) ?? '');
            parse_str($queryString, $query);
            $relativePath = $this->normalizeRelativePath((string) ($query['path'] ?? ''));
            $disposition = strtolower(trim((string) ($query['disposition'] ?? 'attachment')));

            if ($relativePath === '') {
                return $trimmedUrl;
            }

            if (
                !$preferInline
                && $disposition === 'attachment'
                && !$this->isInlineSafePath($relativePath)
            ) {
                return $trimmedUrl;
            }

            if (
                $this->isPrivateMemberPath($relativePath)
                || ($this->containsHiddenSegment($relativePath) && !$this->isAllowedHiddenPath($relativePath))
            ) {
                return $trimmedUrl;
            }

            return $this->buildAccessUrl($relativePath, $preferInline);
        }

        $uploadPrefix = rtrim((string) UPLOAD_URL, '/');
        if (!str_starts_with($trimmedUrl, $uploadPrefix)) {
            return $trimmedUrl;
        }

        $relativePath = ltrim(substr($trimmedUrl, strlen($uploadPrefix)), '/');
        $relativePath = implode('/', array_map('rawurldecode', explode('/', $relativePath)));

        return $this->buildAccessUrl($relativePath, $preferInline);
    }

    public function normalizeAdminVisibleUrl(string $url): string
    {
        $trimmedUrl = trim($url);
        if ($trimmedUrl === '') {
            return '';
        }

        $deliveryPrefix = rtrim((string) SITE_URL, '/') . '/media-file';
        if (!str_starts_with($trimmedUrl, $deliveryPrefix)) {
            return $this->normalizeUrl($trimmedUrl, false);
        }

        $queryString = (string) (parse_url($trimmedUrl, PHP_URL_QUERY) ?? '');
        parse_str($queryString, $query);
        $relativePath = $this->normalizeRelativePath((string) ($query['path'] ?? ''));

        if (
            $relativePath === ''
            || $this->isPrivateMemberPath($relativePath)
            || ($this->containsHiddenSegment($relativePath) && !$this->isAllowedHiddenPath($relativePath))
        ) {
            return $trimmedUrl;
        }

        return $this->buildDirectUploadUrl($relativePath);
    }

    public function handleRequest(): void
    {
        $path = (string) ($_GET['path'] ?? '');
        $disposition = strtolower(trim((string) ($_GET['disposition'] ?? 'attachment')));
        $requestedInline = $disposition === 'inline';

        if (trim($path) === '') {
            $this->deny(404, 'Die angeforderte Datei wurde nicht gefunden.', false);
        }

        $relativePath = $this->normalizeRelativePath($path);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            $this->deny(400, 'Ungültiger Medienpfad.');
        }

        if ($this->containsHiddenSegment($relativePath) && !$this->isAllowedHiddenPath($relativePath)) {
            $this->deny(403, 'Der angeforderte Medienpfad ist nicht freigegeben.');
        }

        $absolutePath = $this->resolveAbsolutePath($relativePath);
        if ($absolutePath instanceof WP_Error) {
            $this->deny(403, $absolutePath->get_error_message());
        }

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            $this->deny(404, 'Die angeforderte Datei wurde nicht gefunden.');
        }

        if ($this->isPrivateMemberPath($relativePath) && !$this->canAccessPrivateMemberPath($relativePath)) {
            $this->deny(403, 'Kein Zugriff auf diese Datei.');
        }

        $inline = $requestedInline && $this->isInlineSafePath($relativePath);
        $cacheProfile = $this->isPrivateMemberPath($relativePath) ? 'private' : $this->resolvePublicCacheProfile();
        $cacheManager = CacheManager::instance();
        $isPublicCacheProfile = in_array($cacheProfile, ['public', 'public_no_cache'], true);
        $cacheTtl = $cacheProfile === 'public'
            ? $this->resolvePublicCacheTtl($inline)
            : 300;
        $cacheManager->sendResponseHeaders($cacheProfile, $cacheTtl);

        $mimeType = $this->detectMimeType($absolutePath);
        $filename = basename($absolutePath);
        $filesize = (int) filesize($absolutePath);
        $lastModified = filemtime($absolutePath) ?: time();

        if ($isPublicCacheProfile && !headers_sent()) {
            header_remove('Vary');
            header('Vary: Accept-Encoding');

            if (!$cacheManager->sendConditionalHeaders('media:' . $relativePath . ':' . ($inline ? 'inline' : 'attachment'), $lastModified)) {
                exit;
            }
        }

        $range = $this->parseRangeHeader((string) ($_SERVER['HTTP_RANGE'] ?? ''), $filesize);

        if ($range instanceof WP_Error) {
            if (!headers_sent()) {
                http_response_code(416);
                header('Content-Range: bytes */' . $filesize);
            }

            $this->deny(416, $range->get_error_message());
        }

        $rangeStart = $range['start'] ?? 0;
        $rangeEnd = $range['end'] ?? max(0, $filesize - 1);
        $contentLength = $rangeEnd >= $rangeStart ? ($rangeEnd - $rangeStart + 1) : 0;
        $isPartial = $filesize > 0 && ($rangeStart > 0 || $rangeEnd < ($filesize - 1));

        if (!headers_sent()) {
            if ($isPartial) {
                http_response_code(206);
            }

            header('Content-Type: ' . $mimeType);
            header('X-Content-Type-Options: nosniff');
            header('Accept-Ranges: bytes');
            header('Content-Length: ' . $contentLength);
            if (!$isPublicCacheProfile) {
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            }
            header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $this->buildSafeFilename($filename) . '"');

            if ($isPartial) {
                header('Content-Range: bytes ' . $rangeStart . '-' . $rangeEnd . '/' . $filesize);
            }
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
            exit;
        }

        $this->streamFile($absolutePath, $rangeStart, $rangeEnd);
        exit;
    }

    private function buildDirectUploadUrl(string $relativePath): string
    {
        $segments = array_map(static fn(string $segment): string => rawurlencode($segment), explode('/', $relativePath));
        return rtrim((string) UPLOAD_URL, '/') . '/' . implode('/', $segments);
    }

    private function normalizeRelativePath(string $relativePath): string
    {
        $clean = trim(str_replace('\\', '/', $relativePath), '/');
        $clean = preg_replace('#/+#', '/', $clean) ?? '';
        return trim($clean, '/');
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

    private function isAllowedHiddenPath(string $relativePath): bool
    {
        if (!str_starts_with($relativePath, self::ELFINDER_TMB_PREFIX)) {
            return false;
        }

        if (!$this->isInlineSafePath($relativePath)) {
            return false;
        }

        if (Auth::instance()->isAdmin()) {
            return true;
        }

        $sourcePath = $this->resolveElfinderThumbnailSourcePath($relativePath);
        if ($sourcePath === null || $sourcePath === '') {
            return false;
        }

        if ($this->containsHiddenSegment($sourcePath)) {
            return false;
        }

        if ($this->isPrivateMemberPath($sourcePath)) {
            return $this->canAccessPrivateMemberPath($sourcePath);
        }

        return true;
    }

    private function resolveElfinderThumbnailSourcePath(string $relativePath): ?string
    {
        if (!str_starts_with($relativePath, self::ELFINDER_TMB_PREFIX)) {
            return null;
        }

        $thumbName = basename($relativePath);
        if ($thumbName === '' || !str_ends_with(strtolower($thumbName), '.png')) {
            return null;
        }

        $thumbId = substr($thumbName, 0, -4);
        if (!is_string($thumbId) || $thumbId === '') {
            return null;
        }

        foreach ($this->buildElfinderHashCandidates($thumbId) as $candidate) {
            $decodedPath = $this->decodeElfinderHash($candidate);
            if ($decodedPath !== null) {
                return $decodedPath;
            }
        }

        return null;
    }

    /**
     * @return array<int,string>
     */
    private function buildElfinderHashCandidates(string $thumbId): array
    {
        $candidates = [$thumbId];

        if (preg_match('/^(.*?)(\d{9,13})$/', $thumbId, $matches) === 1 && !empty($matches[1])) {
            $candidates[] = (string) $matches[1];
        }

        return array_values(array_unique(array_filter($candidates, static fn (string $value): bool => $value !== '')));
    }

    private function decodeElfinderHash(string $hash): ?string
    {
        $separatorPos = strpos($hash, '_');
        if ($separatorPos === false || $separatorPos >= strlen($hash) - 1) {
            return null;
        }

        $encodedPath = substr($hash, $separatorPos + 1);
        if (!is_string($encodedPath) || $encodedPath === '') {
            return null;
        }

        $encodedPath = strtr($encodedPath, '-_.', '+/=');
        $padding = strlen($encodedPath) % 4;
        if ($padding !== 0) {
            $encodedPath .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($encodedPath, true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        if (function_exists('mb_check_encoding') && !\mb_check_encoding($decoded, 'UTF-8')) {
            return null;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
            return null;
        }

        $normalized = $this->normalizeRelativePath($decoded);
        return $normalized !== '' ? $normalized : null;
    }

    private function resolveAbsolutePath(string $relativePath): string|WP_Error
    {
        $absolutePath = rtrim((string) UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $uploadRoot = realpath(rtrim((string) UPLOAD_PATH, '/\\'));
        $parent = realpath(dirname($absolutePath));

        if ($uploadRoot === false || $parent === false || !str_starts_with($parent, $uploadRoot)) {
            return new WP_Error('invalid_media_path', 'Der Medienpfad liegt außerhalb des Upload-Verzeichnisses.');
        }

        return $absolutePath;
    }

    private function isPrivateMemberPath(string $relativePath): bool
    {
        return $relativePath === 'member' || str_starts_with($relativePath, 'member/');
    }

    private function canAccessPrivateMemberPath(string $relativePath): bool
    {
        $auth = Auth::instance();
        if (!$auth->isLoggedIn()) {
            return false;
        }

        if ($auth->isAdmin()) {
            return true;
        }

        $currentUser = Auth::getCurrentUser();
        $userId = (int) ($currentUser->id ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $memberPrefix = 'member/user-' . $userId;
        return $relativePath === $memberPrefix || str_starts_with($relativePath, $memberPrefix . '/');
    }

    private function isInlineSafePath(string $relativePath): bool
    {
        $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));
        return in_array($extension, self::INLINE_EXTENSIONS, true);
    }

    private function resolvePublicCacheProfile(): string
    {
        try {
            $db = Database::instance();
            $value = $db->get_var(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'perf_browser_cache' LIMIT 1"
            );

            return (string) $value === '0' ? 'public_no_cache' : 'public';
        } catch (\Throwable) {
            return 'public';
        }
    }

    private function resolvePublicCacheTtl(bool $inline): int
    {
        $ttl = $inline ? 3600 : 300;

        try {
            $db = Database::instance();
            $rows = $db->get_results(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name IN ('perf_browser_cache', 'perf_browser_cache_ttl')"
            ) ?: [];

            $configuredTtl = $ttl;

            foreach ($rows as $row) {
                $name = (string) ($row->option_name ?? '');
                $value = (string) ($row->option_value ?? '');

                if ($name === 'perf_browser_cache') {
                    continue;
                }

                if ($name === 'perf_browser_cache_ttl') {
                    $configuredTtl = (int) $value;
                }
            }

            if ($configuredTtl > 0) {
                $ttl = $configuredTtl;
            }
        } catch (\Throwable) {
            return $ttl;
        }

        return max(300, min(31536000, $ttl));
    }

    private function detectMimeType(string $absolutePath): string
    {
        $mimeType = $this->detectActualImageMimeType($absolutePath);

        if ($mimeType === '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $absolutePath);
                finfo_close($finfo);
                $mimeType = is_string($detected) ? $detected : '';
            }
        }

        if ($mimeType === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($absolutePath);
            $mimeType = is_string($detected) ? $detected : '';
        }

        return $mimeType !== '' ? $mimeType : 'application/octet-stream';
    }

    private function detectActualImageMimeType(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return '';
        }

        if (function_exists('exif_imagetype')) {
            $imageType = @exif_imagetype($absolutePath);
            if (is_int($imageType)) {
                $mimeType = image_type_to_mime_type($imageType);
                if (is_string($mimeType) && trim($mimeType) !== '') {
                    return strtolower(trim($mimeType));
                }
            }
        }

        $imageInfo = @getimagesize($absolutePath);
        if (is_array($imageInfo) && isset($imageInfo['mime']) && is_string($imageInfo['mime']) && trim($imageInfo['mime']) !== '') {
            return strtolower(trim($imageInfo['mime']));
        }

        return '';
    }

    /**
     * @return array{start:int,end:int}|WP_Error
     */
    private function parseRangeHeader(string $rangeHeader, int $filesize): array|WP_Error
    {
        if ($filesize < 0) {
            $filesize = 0;
        }

        $defaultRange = ['start' => 0, 'end' => max(0, $filesize - 1)];
        $rangeHeader = trim($rangeHeader);
        if ($rangeHeader === '') {
            return $defaultRange;
        }

        if (!str_starts_with(strtolower($rangeHeader), 'bytes=')) {
            return new WP_Error('invalid_range_header', 'Ungültiger Range-Header.');
        }

        $rangeSpec = trim(substr($rangeHeader, 6));
        if ($rangeSpec === '' || str_contains($rangeSpec, ',')) {
            return new WP_Error('invalid_range_header', 'Mehrere Byte-Bereiche werden nicht unterstützt.');
        }

        if (!preg_match('/^(\d*)-(\d*)$/', $rangeSpec, $matches)) {
            return new WP_Error('invalid_range_header', 'Ungültiger Range-Header.');
        }

        $startRaw = $matches[1] ?? '';
        $endRaw = $matches[2] ?? '';

        if ($startRaw === '' && $endRaw === '') {
            return new WP_Error('invalid_range_header', 'Ungültiger Range-Header.');
        }

        if ($filesize === 0) {
            return new WP_Error('range_not_satisfiable', 'Die angeforderte Byte-Range ist für diese Datei nicht verfügbar.');
        }

        if ($startRaw === '') {
            $suffixLength = (int) $endRaw;
            if ($suffixLength <= 0) {
                return new WP_Error('range_not_satisfiable', 'Die angeforderte Byte-Range ist für diese Datei nicht verfügbar.');
            }

            $suffixLength = min($suffixLength, $filesize);
            return [
                'start' => max(0, $filesize - $suffixLength),
                'end' => $filesize - 1,
            ];
        }

        $start = (int) $startRaw;
        $end = $endRaw === '' ? ($filesize - 1) : (int) $endRaw;

        if ($start < 0 || $start >= $filesize || $end < $start) {
            return new WP_Error('range_not_satisfiable', 'Die angeforderte Byte-Range ist für diese Datei nicht verfügbar.');
        }

        return [
            'start' => $start,
            'end' => min($end, $filesize - 1),
        ];
    }

    private function streamFile(string $absolutePath, int $start, int $end): void
    {
        $handle = fopen($absolutePath, 'rb');
        if (!is_resource($handle)) {
            $this->deny(500, 'Die Datei konnte nicht gelesen werden.');
        }

        $output = fopen('php://output', 'wb');
        if (!is_resource($output)) {
            fclose($handle);
            $this->deny(500, 'Die Datei konnte nicht gelesen werden.');
        }

        try {
            if ($start > 0 && fseek($handle, $start) !== 0) {
                $this->deny(500, 'Die Datei konnte nicht gelesen werden.');
            }

            $bytesRemaining = max(0, $end - $start + 1);
            while ($bytesRemaining > 0 && !feof($handle)) {
                $chunkSize = min(self::STREAM_CHUNK_BYTES, $bytesRemaining);
                $buffer = fread($handle, $chunkSize);
                if ($buffer === false) {
                    $this->deny(500, 'Die Datei konnte nicht gelesen werden.');
                }

                if ($buffer === '') {
                    break;
                }

                if (fwrite($output, $buffer) === false) {
                    $this->deny(500, 'Die Datei konnte nicht gelesen werden.');
                }

                flush();
                $bytesRemaining -= strlen($buffer);
            }
        } finally {
            fclose($output);
            fclose($handle);
        }
    }

    private function buildSafeFilename(string $filename): string
    {
        $safe = preg_replace('/[\r\n"]+/', '_', $filename) ?? 'download';
        return $safe !== '' ? $safe : 'download';
    }

    private function deny(int $status, string $message, bool $logRequest = true): void
    {
        if ($logRequest) {
            Logger::instance()->withChannel('media.delivery')->warning('Medienauslieferung abgelehnt.', [
                'status' => $status,
                'message' => $message,
                'path' => (string) ($_GET['path'] ?? ''),
            ]);
        }

        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->buildPublicErrorMessage($status);
        exit;
    }

    private function buildPublicErrorMessage(int $status): string
    {
        return match ($status) {
            400 => 'Ungültige Medienanfrage.',
            403 => 'Kein Zugriff auf diese Datei.',
            404 => 'Die angeforderte Datei wurde nicht gefunden.',
            416 => 'Die angeforderte Byte-Range ist nicht verfügbar.',
            default => 'Die Medienanfrage konnte nicht verarbeitet werden.',
        };
    }
}