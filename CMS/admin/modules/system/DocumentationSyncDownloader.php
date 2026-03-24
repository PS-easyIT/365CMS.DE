<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncDownloader
{
    private const MAX_DOWNLOAD_BYTES = 25 * 1024 * 1024;

    private const ALLOWED_DOWNLOAD_HOSTS = [
        'codeload.github.com',
        'github.com',
        'raw.githubusercontent.com',
    ];

    public function __construct(private readonly DocumentationSyncFilesystem $filesystem)
    {
    }

    /**
     * @return array{success: bool, error?: string}
     */
    public function downloadFile(string $url, string $destination): array
    {
        if (!$this->isAllowedDownloadUrl($url)) {
            return ['success' => false, 'error' => 'Der Dokumentations-Download liegt außerhalb der erlaubten GitHub-Hosts.'];
        }

        if (!$this->isAllowedDestination($destination)) {
            return ['success' => false, 'error' => 'Die temporäre ZIP-Zieldatei liegt außerhalb des erlaubten Temp-Bereichs.'];
        }

        if (!class_exists('\\CMS\\Http\\Client')) {
            return ['success' => false, 'error' => 'Der zentrale HTTP-Client ist nicht verfügbar.'];
        }

        $parentDir = dirname($destination);
        if (!is_dir($parentDir) && !mkdir($parentDir, 0755, true) && !is_dir($parentDir)) {
            return ['success' => false, 'error' => 'Temporäres Download-Verzeichnis konnte nicht erstellt werden.'];
        }

        $response = \CMS\Http\Client::getInstance()->get($url, [
            'userAgent' => '365CMS-DocumentationSync/1.0',
            'timeout' => 120,
            'connectTimeout' => 10,
            'maxBytes' => self::MAX_DOWNLOAD_BYTES,
            'allowedContentTypes' => ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'],
        ]);

        if (($response['success'] ?? false) !== true) {
            if (is_file($destination)) {
                $this->filesystem->deleteFile($destination);
            }

            return ['success' => false, 'error' => (string) ($response['error'] ?? 'GitHub-ZIP konnte per HTTPS nicht geladen werden.')];
        }

        $body = (string) ($response['body'] ?? '');
        if ($body === '') {
            return ['success' => false, 'error' => 'Der GitHub-Download lieferte keinen ZIP-Inhalt zurück.'];
        }

        $written = file_put_contents($destination, $body, LOCK_EX);
        if (!is_int($written) || $written < 1) {
            if (is_file($destination)) {
                $this->filesystem->deleteFile($destination);
            }

            return ['success' => false, 'error' => 'Die geladene ZIP-Datei konnte nicht lokal gespeichert werden.'];
        }

        return ['success' => true];
    }

    private function isAllowedDestination(string $destination): bool
    {
        $destination = trim($destination);
        if ($destination === '' || !str_ends_with(strtolower($destination), '.zip')) {
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

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        return in_array($host, self::ALLOWED_DOWNLOAD_HOSTS, true);
    }
}