<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncFilesystem
{
    /** @var list<string> */
    private const array SUPPORTED_DOC_EXTENSIONS = ['md', 'csv'];

    /**
     * @return array{hash: string, file_count: int}
     */
    public function calculateDirectoryIntegrity(string $root): array
    {
        if (!is_dir($root)) {
            throw new RuntimeException('Integritätsprüfung erwartet ein vorhandenes Verzeichnis.');
        }

        if (is_link($root)) {
            throw new RuntimeException('Integritätsprüfung erlaubt keine symbolischen Links als Wurzelverzeichnis.');
        }

        $basePath = rtrim((string) realpath($root), '\\/');
        if ($basePath === '') {
            throw new RuntimeException('Integritätsprüfung konnte den Verzeichnispfad nicht auflösen.');
        }

        $entries = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Integritätsprüfung erlaubt keine symbolischen Links im Dokumentations-Bundle.');
            }

            if (!$item->isFile()) {
                continue;
            }

            $path = $item->getPathname();
            $hash = hash_file('sha256', $path);
            if ($hash === false) {
                throw new RuntimeException('Integritätsprüfung konnte keine SHA-256-Prüfsumme berechnen: ' . $path);
            }

            $relative = substr($path, strlen($basePath) + 1);
            if (!is_string($relative) || $relative === '') {
                throw new RuntimeException('Integritätsprüfung konnte keinen relativen Pfad bestimmen.');
            }

            $entries[str_replace('\\', '/', $relative)] = strtolower($hash);
        }

        ksort($entries, SORT_STRING);

        $payload = implode("\n", array_map(
            static fn(string $relativePath, string $hash): string => $relativePath . ':' . $hash,
            array_keys($entries),
            $entries
        ));

        return [
            'hash' => hash('sha256', $payload),
            'file_count' => count($entries),
        ];
    }

    public function findDocDirectory(string $extractRoot): ?string
    {
        $resolvedExtractRoot = realpath($extractRoot);
        if ($resolvedExtractRoot === false || !is_dir($resolvedExtractRoot)) {
            return null;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                continue;
            }

            if (!$item->isDir() || strcasecmp($item->getFilename(), 'DOC') !== 0) {
                continue;
            }

            $docPath = $item->getPathname();
            $resolvedDocPath = realpath($docPath);
            if ($resolvedDocPath === false || !$this->isPathInsideRoot($resolvedDocPath, $resolvedExtractRoot)) {
                continue;
            }

            if (is_file($docPath . DIRECTORY_SEPARATOR . 'README.md') || is_dir($docPath . DIRECTORY_SEPARATOR . 'admin')) {
                return $resolvedDocPath;
            }
        }

        return null;
    }

    public function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            throw new RuntimeException('Quellverzeichnis für den Doku-Sync existiert nicht.');
        }

        if (is_link($source) || is_link($destination)) {
            throw new RuntimeException('Doku-Sync verarbeitet keine symbolischen Links als Quell- oder Zielverzeichnis.');
        }

        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            throw new RuntimeException('Zielverzeichnis für den Doku-Sync konnte nicht erstellt werden.');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('Doku-Sync verarbeitet keine symbolischen Links im Dokumentations-Bundle: ' . $item->getPathname());
            }

            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new RuntimeException('Unterverzeichnis konnte nicht erstellt werden: ' . $targetPath);
                }
                continue;
            }

            if (!$this->copyFile($item->getPathname(), $targetPath)) {
                throw new RuntimeException('Datei konnte nicht kopiert werden: ' . $item->getPathname());
            }
        }
    }

    public function countSupportedDocuments(string $docsRoot): int
    {
        if (!is_dir($docsRoot)) {
            return 0;
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($docsRoot, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink()) {
                continue;
            }

            if ($item->isFile()) {
                $extension = strtolower((string) pathinfo($item->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, self::SUPPORTED_DOC_EXTENSIONS, true)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function deleteDirectory(string $dir): bool
    {
        if (is_link($dir)) {
            return $this->deleteFile($dir);
        }

        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_link($path)) {
                if (!$this->deleteFile($path)) {
                    return false;
                }
                continue;
            }

            if (is_dir($path)) {
                if (!$this->deleteDirectory($path)) {
                    return false;
                }
                continue;
            }

            if (!$this->deleteFile($path)) {
                return false;
            }
        }

        if (!is_writable($dir) && !is_writable(dirname($dir))) {
            $this->logFilesystemFailure('rmdir_preflight', 'Verzeichnis oder Elternpfad ist nicht beschreibbar.', ['path' => $dir]);
            return false;
        }

        return $this->runFilesystemOperation('rmdir', 'Verzeichnis konnte nicht gelöscht werden.', ['path' => $dir], static fn (): bool => rmdir($dir)) === true;
    }

    public function renamePath(string $source, string $destination, string $message): bool
    {
        if (!file_exists($source)) {
            $this->logFilesystemFailure('rename_preflight', $message, [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'source_missing',
            ]);
            return false;
        }

        if (is_link($source) || is_link($destination) || file_exists($destination)) {
            $this->logFilesystemFailure('rename_preflight', $message, [
                'source' => $source,
                'destination' => $destination,
                'reason' => is_link($source) || is_link($destination) ? 'symbolic_link_detected' : 'destination_exists',
            ]);
            return false;
        }

        $sourceParent = dirname($source);
        $targetParent = dirname($destination);
        if (!is_dir($targetParent)) {
            $this->logFilesystemFailure('rename_preflight', $message, [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'target_parent_missing',
            ]);
            return false;
        }

        if (!is_writable($sourceParent) || !is_writable($targetParent)) {
            $this->logFilesystemFailure('rename_preflight', $message, [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'path_not_writable',
            ]);
            return false;
        }

        return $this->runFilesystemOperation('rename', $message, [
            'source' => $source,
            'destination' => $destination,
        ], static fn (): bool => rename($source, $destination)) === true;
    }

    public function deleteFile(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if (is_dir($path) && !is_link($path)) {
            $this->logFilesystemFailure('unlink_preflight', 'Datei konnte nicht gelöscht werden.', [
                'path' => $path,
                'reason' => 'path_is_directory',
            ]);
            return false;
        }

        if (!is_writable($path) && !is_writable(dirname($path))) {
            $this->logFilesystemFailure('unlink_preflight', 'Datei konnte nicht gelöscht werden.', [
                'path' => $path,
                'reason' => 'path_not_writable',
            ]);
            return false;
        }

        return $this->runFilesystemOperation('unlink', 'Datei konnte nicht gelöscht werden.', ['path' => $path], static fn (): bool => unlink($path)) === true;
    }

    private function copyFile(string $source, string $destination): bool
    {
        if (!is_file($source) || !is_readable($source)) {
            $this->logFilesystemFailure('copy_preflight', 'Datei konnte nicht kopiert werden.', [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'source_unreadable',
            ]);
            return false;
        }

        $targetParent = dirname($destination);
        if (!is_dir($targetParent) || !is_writable($targetParent)) {
            $this->logFilesystemFailure('copy_preflight', 'Datei konnte nicht kopiert werden.', [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'target_not_writable',
            ]);
            return false;
        }

        return $this->runFilesystemOperation('copy', 'Datei konnte nicht kopiert werden.', [
            'source' => $source,
            'destination' => $destination,
        ], static fn (): bool => copy($source, $destination)) === true;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFilesystemFailure(string $operation, string $message, array $context = []): void
    {
        \CMS\Logger::instance()->withChannel('admin.documentation')->warning($message, array_merge([
            'operation' => $operation,
        ], $context));
    }

    /**
     * @param array<string, mixed> $context
     * @param callable(): mixed $callback
     */
    private function runFilesystemOperation(string $operation, string $message, array $context, callable $callback): mixed
    {
        $warning = null;
        set_error_handler(static function (int $severity, string $errorMessage) use (&$warning): bool {
            $warning = $errorMessage;
            return true;
        });

        try {
            $result = $callback();
        } finally {
            restore_error_handler();
        }

        if ($result === false || $result === null) {
            if ($warning !== null) {
                $context['warning'] = $warning;
            }
            $this->logFilesystemFailure($operation, $message, $context);
        }

        return $result;
    }

    private function isPathInsideRoot(string $path, string $root): bool
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($path, '\\/'));
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($root, '\\/'));

        return $normalizedPath === $normalizedRoot
            || str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR);
    }
}