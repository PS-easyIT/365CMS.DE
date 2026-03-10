<?php
declare(strict_types=1);

namespace CMS\Services\Media;

use CMS\Auth;
use CMS\Logger;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class UploadHandler
{
    private Logger $logger;
    private ?\Closure $uploadValidator;

    public function __construct(
        private readonly string $uploadPath,
        private readonly MediaRepository $repository,
        private readonly ImageProcessor $imageProcessor,
        ?\Closure $uploadValidator = null
    ) {
        $this->logger = Logger::instance()->withChannel('media.upload');
        $this->uploadValidator = $uploadValidator;
    }

    public function createFolder(string $path, string $name): bool|WP_Error
    {
        $path = trim($path, '/\\');
        $name = trim($name);

        if ($name === '' || preg_match('/[\\\/\:\*\?"<>\|]/', $name)) {
            return new WP_Error('invalid_folder_name', 'Ungültiger Ordnername');
        }

        $basePath = $this->resolvePath($path);
        if ($basePath instanceof WP_Error) {
            return $basePath;
        }

        $folderPath = $basePath . DIRECTORY_SEPARATOR . $name;
        if (is_dir($folderPath)) {
            return new WP_Error('folder_exists', 'Ordner existiert bereits');
        }

        if (!is_dir($basePath)) {
            return new WP_Error('parent_not_found', 'Übergeordnetes Verzeichnis nicht gefunden');
        }

        if (!is_writable($basePath)) {
            return new WP_Error('parent_not_writable', 'Übergeordnetes Verzeichnis ist nicht beschreibbar');
        }

        if (mkdir($folderPath, 0755) || is_dir($folderPath)) {
            return true;
        }

        $this->logFilesystemFailure('mkdir', 'Ordner konnte nicht erstellt werden', [
            'path' => $folderPath,
        ]);

        return new WP_Error('folder_create_failed', 'Ordner konnte nicht erstellt werden');
    }

    public function uploadFile(array $file, string $path = '', bool $convertImagesToWebP = false, ?array $validationSettings = null): array|WP_Error
    {
        $validation = $this->validateFile($file, $validationSettings);
        if ($validation instanceof WP_Error) {
            return $validation;
        }

        $targetDir = $this->resolvePath($path);
        if ($targetDir instanceof WP_Error) {
            return $targetDir;
        }

        $directoryReady = $this->ensureDirectoryExists($targetDir, 'upload_dir_missing', 'Upload-Verzeichnis konnte nicht erstellt werden');
        if ($directoryReady instanceof WP_Error) {
            return $directoryReady;
        }

        $originalName = $file['name'];
        $sanitizedName = $this->sanitizeFileName($originalName);
        $targetPath = $this->createUniqueTargetPath($targetDir, $sanitizedName);
        $sourcePath = (string) $file['tmp_name'];

        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            $this->logFilesystemFailure('preflight', 'Temporäre Upload-Datei ist nicht lesbar', [
                'source' => $sourcePath,
                'target' => $targetPath,
            ]);

            return new WP_Error('invalid_upload_source', 'Temporäre Upload-Datei ist nicht verfügbar');
        }

        if (!$this->moveUploadedFileToTarget($sourcePath, $targetPath)) {
            $this->logFilesystemFailure('upload_move', 'Datei konnte nicht verschoben werden', [
                'source' => $sourcePath,
                'target' => $targetPath,
                'is_uploaded_file' => is_uploaded_file($sourcePath),
            ]);

            return new WP_Error('upload_move_failed', 'Datei konnte nicht verschoben werden');
        }

        $relativePath = trim(($path !== '' ? trim($path, '/\\') . '/' : '') . basename($targetPath), '/');
        $category = $this->repository->detectSystemCategory($relativePath);
        $meta = $this->repository->loadMeta();
        $currentUser = Auth::getCurrentUser();
        $meta['files'][str_replace('\\', '/', $relativePath)] = [
            'uploaded_at' => date('c'),
            'uploaded_by' => (string)($currentUser->username ?? $currentUser->email ?? 'system'),
            'uploader_id' => isset($currentUser->id) ? (int)$currentUser->id : null,
            'original_name' => $originalName,
            'category' => $category,
        ];
        $this->repository->saveMeta($meta);

        if ($convertImagesToWebP && $this->isConvertibleImage($targetPath)) {
            $this->imageProcessor->convertToWebP($targetPath);
        }

        return [
            'name' => basename($targetPath),
            'path' => $relativePath,
            'size' => filesize($targetPath),
            'type' => mime_content_type($targetPath),
            'url' => $this->repository->buildPublicUrl($relativePath),
            'category' => $category,
        ];
    }

    public function moveFile(string $sourcePath, string $targetPath): bool|WP_Error
    {
        $sourceFullPath = $this->resolvePath($sourcePath);
        $targetFullPath = $this->resolvePath($targetPath);

        if ($sourceFullPath instanceof WP_Error) {
            return $sourceFullPath;
        }
        if ($targetFullPath instanceof WP_Error) {
            return $targetFullPath;
        }
        if (!file_exists($sourceFullPath)) {
            return new WP_Error('source_not_found', 'Quelle nicht gefunden');
        }

        $targetDir = dirname($targetFullPath);
        $directoryReady = $this->ensureDirectoryExists($targetDir, 'target_dir_missing', 'Zielverzeichnis konnte nicht erstellt werden');
        if ($directoryReady instanceof WP_Error) {
            return $directoryReady;
        }

        if (!is_writable(dirname($sourceFullPath)) || !is_writable($targetDir)) {
            return new WP_Error('move_not_writable', 'Quell- oder Zielverzeichnis ist nicht beschreibbar');
        }

        if (!rename($sourceFullPath, $targetFullPath)) {
            $this->logFilesystemFailure('rename', 'Element konnte nicht verschoben werden', [
                'source' => $sourceFullPath,
                'target' => $targetFullPath,
            ]);

            return new WP_Error('move_failed', 'Element konnte nicht verschoben werden');
        }

        $this->repository->renameMetaPath($sourcePath, $targetPath);
        return true;
    }

    public function deleteItem(string $path): bool|WP_Error
    {
        $fullPath = $this->resolvePath($path);
        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!file_exists($fullPath)) {
            return new WP_Error('not_found', 'Element nicht gefunden');
        }

        $result = is_dir($fullPath) ? $this->deleteDirectory($fullPath) : $this->deleteFile($fullPath);
        if (!$result) {
            return new WP_Error('delete_failed', 'Element konnte nicht gelöscht werden');
        }

        $this->repository->purgeMetaForPath($path);
        return true;
    }

    public function renameItem(string $path, string $newName): bool|WP_Error
    {
        $fullPath = $this->resolvePath($path);
        if ($fullPath instanceof WP_Error) {
            return $fullPath;
        }

        if (!file_exists($fullPath)) {
            return new WP_Error('not_found', 'Element nicht gefunden');
        }

        $newName = trim($newName);
        if ($newName === '' || preg_match('/[\\\/\:\*\?"<>\|]/', $newName)) {
            return new WP_Error('invalid_name', 'Ungültiger Name');
        }

        $targetPath = dirname($fullPath) . DIRECTORY_SEPARATOR . $newName;
        if (file_exists($targetPath)) {
            return new WP_Error('target_exists', 'Ziel existiert bereits');
        }

        $parentDir = dirname($fullPath);
        if (!is_writable($parentDir)) {
            return new WP_Error('rename_not_writable', 'Verzeichnis ist nicht beschreibbar');
        }

        if (!rename($fullPath, $targetPath)) {
            $this->logFilesystemFailure('rename', 'Element konnte nicht umbenannt werden', [
                'source' => $fullPath,
                'target' => $targetPath,
            ]);

            return new WP_Error('rename_failed', 'Element konnte nicht umbenannt werden');
        }

        $newRelativePath = trim(dirname($path), '/\\');
        $newRelativePath = ($newRelativePath !== '' ? $newRelativePath . '/' : '') . $newName;
        $this->repository->renameMetaPath($path, $newRelativePath);

        return true;
    }

    public function validateFile(array $file, ?array $validationSettings = null): true|WP_Error
    {
        if ($this->uploadValidator instanceof \Closure) {
            $validation = ($this->uploadValidator)($file, $validationSettings);

            return $validation instanceof WP_Error ? $validation : true;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', 'Upload fehlgeschlagen');
        }

        if (!isset($file['tmp_name'], $file['name']) || !is_file($file['tmp_name'])) {
            return new WP_Error('invalid_upload', 'Ungültige Upload-Datei');
        }

        $maxSize = 25 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            return new WP_Error('file_too_large', 'Datei ist zu groß');
        }

        return true;
    }

    private function resolvePath(string $path): string|WP_Error
    {
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($path));
        if (strpos($path, '..') !== false) {
            return new WP_Error('security_violation', 'Ungültiger Pfad');
        }

        $fullPath = $this->uploadPath;
        if ($path !== '') {
            $fullPath .= DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
        }

        if (strpos($fullPath, $this->uploadPath) !== 0) {
            return new WP_Error('security_violation', 'Pfad außerhalb des Upload-Verzeichnisses');
        }

        return $fullPath;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^A-Za-z0-9._-]+/', '-', $fileName) ?: 'upload';
        return trim($fileName, '-');
    }

    private function createUniqueTargetPath(string $directory, string $fileName): string
    {
        $info = pathinfo($fileName);
        $name = $info['filename'] ?? 'upload';
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';
        $targetPath = $directory . DIRECTORY_SEPARATOR . $name . $extension;
        $counter = 1;

        while (file_exists($targetPath)) {
            $targetPath = $directory . DIRECTORY_SEPARATOR . $name . '-' . $counter . $extension;
            $counter++;
        }

        return $targetPath;
    }

    private function deleteDirectory(string $directory): bool
    {
        $items = scandir($directory);
        if ($items === false) {
            $this->logFilesystemFailure('scandir', 'Verzeichnis konnte nicht gelesen werden', [
                'path' => $directory,
            ]);
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                if (!$this->deleteDirectory($itemPath)) {
                    return false;
                }
            } elseif (!$this->deleteFile($itemPath)) {
                return false;
            }
        }

        if (!is_writable(dirname($directory))) {
            $this->logFilesystemFailure('rmdir_preflight', 'Übergeordnetes Verzeichnis ist nicht beschreibbar', [
                'path' => $directory,
            ]);
            return false;
        }

        if (rmdir($directory)) {
            return true;
        }

        $this->logFilesystemFailure('rmdir', 'Verzeichnis konnte nicht gelöscht werden', [
            'path' => $directory,
        ]);

        return false;
    }

    private function isConvertibleImage(string $filePath): bool
    {
        $mimeType = mime_content_type($filePath) ?: '';
        return in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'], true);
    }

    private function ensureDirectoryExists(string $directory, string $errorCode, string $message): true|WP_Error
    {
        if (is_dir($directory)) {
            if (!is_writable($directory)) {
                return new WP_Error($errorCode, 'Zielverzeichnis ist nicht beschreibbar');
            }

            return true;
        }

        $parentDir = dirname($directory);
        if ($parentDir === $directory || $parentDir === '') {
            return new WP_Error($errorCode, $message);
        }

        if (!is_dir($parentDir)) {
            $parentReady = $this->ensureDirectoryExists($parentDir, $errorCode, $message);
            if ($parentReady instanceof WP_Error) {
                return $parentReady;
            }
        }

        if (!is_writable($parentDir)) {
            return new WP_Error($errorCode, 'Übergeordnetes Verzeichnis ist nicht beschreibbar');
        }

        if (mkdir($directory, 0755) || is_dir($directory)) {
            return true;
        }

        $this->logFilesystemFailure('mkdir', $message, [
            'path' => $directory,
        ]);

        return new WP_Error($errorCode, $message);
    }

    private function moveUploadedFileToTarget(string $sourcePath, string $targetPath): bool
    {
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) || !is_writable($targetDir)) {
            return false;
        }

        if (is_uploaded_file($sourcePath)) {
            return move_uploaded_file($sourcePath, $targetPath);
        }

        if (!is_writable(dirname($sourcePath))) {
            return false;
        }

        return rename($sourcePath, $targetPath);
    }

    private function deleteFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return true;
        }

        if (!is_writable($filePath) && !is_writable(dirname($filePath))) {
            $this->logFilesystemFailure('unlink_preflight', 'Datei oder Verzeichnis ist nicht beschreibbar', [
                'path' => $filePath,
            ]);
            return false;
        }

        if (unlink($filePath)) {
            return true;
        }

        $this->logFilesystemFailure('unlink', 'Datei konnte nicht gelöscht werden', [
            'path' => $filePath,
        ]);

        return false;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFilesystemFailure(string $operation, string $message, array $context = []): void
    {
        $this->logger->warning($message, array_merge(['operation' => $operation], $context));
    }
}
