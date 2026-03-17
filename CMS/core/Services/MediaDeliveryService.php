<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Auth;
use CMS\CacheManager;
use CMS\Logger;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class MediaDeliveryService
{
    private const INLINE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'ico', 'avif'];
    private const ELFINDER_TMB_PREFIX = '.elfinder/.tmb/';

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

        if ($preferInline && $this->isInlineSafePath($normalizedPath)) {
            return $this->buildDeliveryUrl($normalizedPath, 'inline');
        }

        return $this->buildDirectUploadUrl($normalizedPath);
    }

    public function buildPreviewUrl(string $relativePath): string
    {
        return $this->buildAccessUrl($relativePath, true);
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
            return $trimmedUrl;
        }

        $uploadPrefix = rtrim((string) UPLOAD_URL, '/');
        if (!str_starts_with($trimmedUrl, $uploadPrefix)) {
            return $trimmedUrl;
        }

        $relativePath = ltrim(substr($trimmedUrl, strlen($uploadPrefix)), '/');
        $relativePath = implode('/', array_map('rawurldecode', explode('/', $relativePath)));

        return $this->buildAccessUrl($relativePath, $preferInline);
    }

    public function handleRequest(): void
    {
        $path = (string) ($_GET['path'] ?? '');
        $disposition = strtolower(trim((string) ($_GET['disposition'] ?? 'attachment')));
        $requestedInline = $disposition === 'inline';

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
        $cacheProfile = $this->isPrivateMemberPath($relativePath) ? 'private' : 'public';
        $cacheTtl = $inline ? 3600 : 300;
        CacheManager::instance()->sendResponseHeaders($cacheProfile, $cacheTtl);

        $mimeType = $this->detectMimeType($absolutePath);
        $filename = basename($absolutePath);
        $filesize = (int) filesize($absolutePath);
        $lastModified = filemtime($absolutePath) ?: time();

        if (!headers_sent()) {
            header('Content-Type: ' . $mimeType);
            header('X-Content-Type-Options: nosniff');
            header('Content-Length: ' . $filesize);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $this->buildSafeFilename($filename) . '"');
        }

        readfile($absolutePath);
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

    private function detectMimeType(string $absolutePath): string
    {
        $mimeType = '';

        if (function_exists('finfo_open')) {
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

    private function buildSafeFilename(string $filename): string
    {
        $safe = preg_replace('/[\r\n"]+/', '_', $filename) ?? 'download';
        return $safe !== '' ? $safe : 'download';
    }

    private function deny(int $status, string $message): void
    {
        Logger::instance()->withChannel('media.delivery')->warning('Medienauslieferung abgelehnt.', [
            'status' => $status,
            'message' => $message,
            'path' => (string) ($_GET['path'] ?? ''),
        ]);

        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }
}