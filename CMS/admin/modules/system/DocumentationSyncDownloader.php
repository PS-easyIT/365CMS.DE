<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationDownloadResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $error = null,
        private readonly int $status = 0,
        private readonly string $contentType = '',
        private readonly int $bytes = 0,
        private readonly string $sha256 = ''
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    public function sha256(): string
    {
        return $this->sha256;
    }

    public static function failure(
        string $error,
        int $status = 0,
        string $contentType = '',
        int $bytes = 0
    ): self {
        return new self(false, $error, $status, $contentType, $bytes);
    }

    public static function success(
        int $status,
        string $contentType,
        int $bytes,
        string $sha256
    ): self {
        return new self(true, null, $status, $contentType, $bytes, $sha256);
    }
}

final class DocumentationDownloadPayload
{
    public function __construct(
        private readonly string $body,
        private readonly string $contentType
    ) {
    }

    public function body(): string
    {
        return $this->body;
    }

    public function contentType(): string
    {
        return $this->contentType;
    }

    public function bytes(): int
    {
        return strlen($this->body);
    }
}

final class DocumentationSyncDownloader
{
    private const MAX_DOWNLOAD_BYTES = 25 * 1024 * 1024;
    private const MIN_DOWNLOAD_BYTES = 128;
    private const MAX_LOG_VALUE_LENGTH = 220;
    private const ZIP_MAGIC_HEADERS = ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"];
    private const DOWNLOAD_FILE_PREFIX = '365cms_doc_sync_';
    private const ALLOWED_CONTENT_TYPES = ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'];

    private const ALLOWED_DOWNLOAD_HOSTS = [
        'codeload.github.com',
        'github.com',
        'raw.githubusercontent.com',
    ];

    public function __construct(private readonly DocumentationSyncFilesystem $filesystem)
    {
    }

    public function downloadFile(string $url, string $destination): DocumentationDownloadResult
    {
        if (!$this->isAllowedDownloadUrl($url)) {
            return $this->rejectDownload(
                'documentation.sync.download.invalid_url',
                'Der Dokumentations-Download liegt außerhalb der erlaubten GitHub-Hosts.',
                ['url' => $this->sanitizeUrlForLog($url)]
            );
        }

        if (!$this->isAllowedDestination($destination)) {
            return $this->rejectDownload(
                'documentation.sync.download.invalid_destination',
                'Die temporäre ZIP-Zieldatei liegt außerhalb des erlaubten Temp-Bereichs.',
                ['destination' => $this->sanitizePathForLog($destination)]
            );
        }

        if (!class_exists('\\CMS\\Http\\Client')) {
            return $this->rejectDownload(
                'documentation.sync.download.missing_http_client',
                'Der zentrale HTTP-Client ist nicht verfügbar.'
            );
        }

        if (!$this->ensureDestinationDirectory($destination)) {
            return $this->rejectDownload(
                'documentation.sync.download.parent_create_failed',
                'Temporäres Download-Verzeichnis konnte nicht erstellt werden.',
                ['destination' => $this->sanitizePathForLog($destination)]
            );
        }

        $response = $this->requestDownload($url);

        if (($response['success'] ?? false) !== true) {
            return $this->handleFailedResponse($url, $destination, $response);
        }

        $validatedPayload = $this->validateResponsePayload($url, $destination, $response);
        if ($validatedPayload === null) {
            return DocumentationDownloadResult::failure(
                'Der GitHub-Download lieferte keinen ZIP-Inhalt zurück.',
                (int) ($response['status'] ?? 0),
                strtolower(trim((string) ($response['contentType'] ?? ''))),
                strlen((string) ($response['body'] ?? ''))
            );
        }

        return $this->persistDownloadedArchive($url, $destination, $response, $validatedPayload);
    }

    /** @return array<string, mixed> */
    private function requestDownload(string $url): array
    {
        return \CMS\Http\Client::getInstance()->get($url, [
            'userAgent' => '365CMS-DocumentationSync/1.0',
            'timeout' => 120,
            'connectTimeout' => 10,
            'maxBytes' => self::MAX_DOWNLOAD_BYTES,
            'allowedContentTypes' => self::ALLOWED_CONTENT_TYPES,
        ]);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function handleFailedResponse(string $url, string $destination, array $response): DocumentationDownloadResult
    {
        $this->deleteDestinationIfPresent($destination);

        $this->logFailure('documentation.sync.download.failed', 'GitHub-ZIP konnte per HTTPS nicht geladen werden.', [
            'url' => $this->sanitizeUrlForLog($url),
            'error' => $this->sanitizeLogString((string) ($response['error'] ?? ''), self::MAX_LOG_VALUE_LENGTH),
        ]);

            return DocumentationDownloadResult::failure(
            'GitHub-ZIP konnte per HTTPS nicht geladen werden.',
                (int) ($response['status'] ?? 0),
                (string) ($response['contentType'] ?? '')
        );
    }

    /**
     * @param array<string, mixed> $response
     * @return DocumentationDownloadPayload|null
     */
    private function validateResponsePayload(string $url, string $destination, array $response): ?DocumentationDownloadPayload
    {
        $body = (string) ($response['body'] ?? '');
        $bodyLength = strlen($body);
        $contentType = strtolower(trim((string) ($response['contentType'] ?? '')));

        if ($body === ''
            || $bodyLength < self::MIN_DOWNLOAD_BYTES
            || $bodyLength > self::MAX_DOWNLOAD_BYTES
            || !$this->hasZipMagicHeader($body)
            || !$this->hasConsistentContentLength((array) ($response['headers'] ?? []), $bodyLength)
            || !$this->hasAllowedResponseContentType($contentType)
        ) {
            $this->deleteDestinationIfPresent($destination);

            $this->logFailure('documentation.sync.download.invalid_body', 'Der GitHub-Download lieferte keinen gültigen ZIP-Inhalt zurück.', [
                'url' => $this->sanitizeUrlForLog($url),
                'bytes' => $bodyLength,
                'content_type' => $this->sanitizeLogString($contentType, 80),
            ]);

            return null;
        }

        return new DocumentationDownloadPayload($body, $contentType);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function persistDownloadedArchive(string $url, string $destination, array $response, DocumentationDownloadPayload $payload): DocumentationDownloadResult
    {
        $written = file_put_contents($destination, $payload->body(), LOCK_EX);
        if (!is_int($written) || $written < self::MIN_DOWNLOAD_BYTES || $written > self::MAX_DOWNLOAD_BYTES) {
            $this->deleteDestinationIfPresent($destination);

            $this->logFailure('documentation.sync.download.write_failed', 'Die geladene ZIP-Datei konnte nicht lokal gespeichert werden.', [
                'destination' => $this->sanitizePathForLog($destination),
                'bytes' => is_int($written) ? $written : 0,
            ]);

            return DocumentationDownloadResult::failure(
                'Die geladene ZIP-Datei konnte nicht lokal gespeichert werden.',
                (int) ($response['status'] ?? 0),
                $payload->contentType(),
                is_int($written) ? $written : 0
            );
        }

        $sha256 = hash_file('sha256', $destination);
        if (!is_string($sha256) || preg_match('/^[0-9a-f]{64}$/', $sha256) !== 1) {
            $this->deleteDestinationIfPresent($destination);

            $this->logFailure('documentation.sync.download.hash_failed', 'Die geladene ZIP-Datei konnte nicht mit einer Integritäts-Checksumme versehen werden.', [
                'destination' => $this->sanitizePathForLog($destination),
            ]);

            return DocumentationDownloadResult::failure(
                'Die geladene ZIP-Datei konnte nicht lokal validiert werden.',
                (int) ($response['status'] ?? 0),
                $payload->contentType(),
                $written
            );
        }

        $this->logSuccess('documentation.sync.download.completed', 'GitHub-ZIP erfolgreich heruntergeladen.', [
            'url' => $this->sanitizeUrlForLog($url),
            'destination' => $this->sanitizePathForLog($destination),
            'bytes' => $written,
            'content_type' => $this->sanitizeLogString($payload->contentType(), 80),
            'sha256' => $sha256,
        ]);

        return DocumentationDownloadResult::success(
            (int) ($response['status'] ?? 0),
            $payload->contentType(),
            $written,
            $sha256
        );
    }

    private function ensureDestinationDirectory(string $destination): bool
    {
        $parentDir = dirname($destination);

        return is_dir($parentDir) || (mkdir($parentDir, 0755, true) || is_dir($parentDir));
    }

    private function deleteDestinationIfPresent(string $destination): void
    {
        if (is_file($destination)) {
            $this->filesystem->deleteFile($destination);
        }
    }

    /** @param array<string, mixed> $context */
    private function rejectDownload(string $action, string $message, array $context = []): DocumentationDownloadResult
    {
        $this->logFailure($action, $message, $context);

        return new DocumentationDownloadResult(false, $message);
    }

    private function isAllowedDestination(string $destination): bool
    {
        $destination = trim($destination);
        if ($destination === '' || !str_ends_with(strtolower($destination), '.zip')) {
            return false;
        }

        $fileName = basename($destination);
        if (!str_starts_with($fileName, self::DOWNLOAD_FILE_PREFIX) || preg_match('/^365cms_doc_sync_[a-f0-9]{12}\.zip$/i', $fileName) !== 1) {
            return false;
        }

        $tempRoot = realpath(sys_get_temp_dir());
        $parentDir = dirname($destination);
        $resolvedParent = realpath($parentDir);

        if ($tempRoot === false || $resolvedParent === false || is_link($destination) || is_link($parentDir)) {
            return false;
        }

        if ($this->isPathInsideRoot($destination, $tempRoot) === false || $this->isPathInsideRoot($resolvedParent, $tempRoot) === false) {
            return false;
        }

        return !file_exists($destination);
    }

    private function isPathInsideRoot(string $path, string $root): bool
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($path, '\\/'));
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($root, '\\/'));

        return $normalizedPath === $normalizedRoot
            || str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR);
    }

    private function isAllowedDownloadUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $user = (string) parse_url($url, PHP_URL_USER);
        $pass = (string) parse_url($url, PHP_URL_PASS);

        if ($host === '' || $path === '' || $user !== '' || $pass !== '') {
            return false;
        }

        return in_array($host, self::ALLOWED_DOWNLOAD_HOSTS, true)
            && (str_contains($path, '/zip/') || str_contains($path, '/zipball/'));
    }

    private function hasZipMagicHeader(string $body): bool
    {
        foreach (self::ZIP_MAGIC_HEADERS as $header) {
            if (str_starts_with($body, $header)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, string> $headers */
    private function hasConsistentContentLength(array $headers, int $bodyLength): bool
    {
        $contentLength = trim((string) ($headers['content-length'] ?? ''));
        if ($contentLength === '') {
            return true;
        }

        if (!ctype_digit($contentLength)) {
            return false;
        }

        $declaredLength = (int) $contentLength;

        return $declaredLength >= self::MIN_DOWNLOAD_BYTES
            && $declaredLength <= self::MAX_DOWNLOAD_BYTES
            && $declaredLength === $bodyLength;
    }

    private function hasAllowedResponseContentType(string $contentType): bool
    {
        if ($contentType === '') {
            return false;
        }

        foreach (self::ALLOWED_CONTENT_TYPES as $allowedType) {
            if (stripos($contentType, $allowedType) === 0) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $context */
    private function logFailure(string $action, string $message, array $context): void
    {
        \CMS\Logger::instance()->withChannel('admin.documentation')->warning($message, $context);
        \CMS\AuditLogger::instance()->log(
            \CMS\AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            'warning'
        );
    }

    /** @param array<string, mixed> $context */
    private function logSuccess(string $action, string $message, array $context): void
    {
        \CMS\Logger::instance()->withChannel('admin.documentation')->info($message, $context);
        \CMS\AuditLogger::instance()->log(
            \CMS\AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            'info'
        );
    }

    private function sanitizeUrlForLog(string $url): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return $this->sanitizeLogString($url, self::MAX_LOG_VALUE_LENGTH);
        }

        return $this->sanitizeLogString(strtolower((string) ($parts['host'] ?? 'invalid-host')) . (string) ($parts['path'] ?? ''), self::MAX_LOG_VALUE_LENGTH);
    }

    private function sanitizePathForLog(string $path): string
    {
        return $this->sanitizeLogString(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), self::MAX_LOG_VALUE_LENGTH);
    }

    private function sanitizeLogString(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }
}