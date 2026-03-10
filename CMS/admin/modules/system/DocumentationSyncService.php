<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncService
{
    public function __construct(
        private readonly string $repoRoot,
        private readonly string $docsRoot,
        private readonly string $githubZipUrl,
        private readonly string $defaultRemote,
        private readonly string $defaultBranch
    ) {
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function syncDocsFromRepository(): array
    {
        $capabilities = $this->getSyncCapabilities();

        if (($capabilities['can_sync'] ?? false) !== true) {
            return ['success' => false, 'error' => (string) ($capabilities['message'] ?? 'Doku-Sync ist auf diesem Server nicht verfügbar.')];
        }

        if (($capabilities['git'] ?? false) === true) {
            return $this->syncDocsViaGit();
        }

        return $this->syncDocsViaGithubZip();
    }

    /**
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    public function getSyncCapabilities(): array
    {
        $hasGitCheckout = is_dir($this->repoRoot . DIRECTORY_SEPARATOR . '.git');
        $gitAvailable = $hasGitCheckout && $this->isGitAvailable();
        $zipAvailable = extension_loaded('zip');
        $httpAvailable = $this->canDownloadOverHttp();
        $githubZipAvailable = $zipAvailable && $httpAvailable;

        if ($gitAvailable) {
            return [
                'can_sync' => true,
                'git' => true,
                'github_zip' => $githubZipAvailable,
                'mode' => 'git',
                'label' => 'Git-Sync bereit',
                'message' => 'Lokaler Git-Checkout erkannt. /DOC kann direkt aus dem Repository synchronisiert werden.',
            ];
        }

        if ($githubZipAvailable) {
            return [
                'can_sync' => true,
                'git' => false,
                'github_zip' => true,
                'mode' => 'github-zip',
                'label' => 'GitHub-Sync bereit',
                'message' => 'Kein Git-Checkout nötig: /DOC wird direkt als ZIP von GitHub geladen und lokal ersetzt.',
            ];
        }

        $reasons = [];
        if (!$zipAvailable) {
            $reasons[] = 'ZIP-Extension fehlt';
        }
        if (!$httpAvailable) {
            $reasons[] = 'kein HTTPS-Download per zentralem cURL-HTTP-Client verfügbar';
        }

        return [
            'can_sync' => false,
            'git' => false,
            'github_zip' => false,
            'mode' => 'none',
            'label' => 'Nicht verfügbar',
            'message' => $reasons === []
                ? 'Doku-Sync ist auf diesem Server derzeit nicht verfügbar.'
                : 'Doku-Sync ist auf diesem Server nicht verfügbar: ' . implode(', ', $reasons) . '.',
        ];
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    private function syncDocsViaGit(): array
    {
        $fetch = $this->runCommand(sprintf(
            'git -C %s fetch %s %s 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($fetch['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Fetch): ' . trim((string) ($fetch['output'] ?? 'Unbekannter Fehler'))];
        }

        $checkout = $this->runCommand(sprintf(
            'git -C %s checkout %s/%s -- DOC 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($checkout['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Checkout): ' . trim((string) ($checkout['output'] ?? 'Unbekannter Fehler'))];
        }

        $status = $this->runCommand(sprintf(
            'git -C %s status --short -- DOC 2>&1',
            escapeshellarg($this->repoRoot)
        ));

        $statusOutput = trim((string) ($status['output'] ?? ''));
        $message = 'Der lokale Ordner /DOC wurde mit ' . $this->defaultRemote . '/' . $this->defaultBranch . ' synchronisiert.';

        if ($statusOutput !== '') {
            $message .= ' Geänderte Dateien: ' . $statusOutput;
        } elseif (is_dir($this->docsRoot)) {
            $message .= ' Keine weiteren Unterschiede im Arbeitsbaum für /DOC.';
        }

        return ['success' => true, 'message' => $message];
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    private function syncDocsViaGithubZip(): array
    {
        if (!extension_loaded('zip')) {
            return ['success' => false, 'error' => 'ZIP-Extension ist für den GitHub-Doku-Sync nicht verfügbar.'];
        }

        $tempBase = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . '365cms_doc_sync_' . bin2hex(random_bytes(6));
        $zipFile = $tempBase . '.zip';
        $extractDir = $tempBase . '_extract';
        $stagingDir = $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC.__sync_' . bin2hex(random_bytes(4));
        $backupDir = $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC.__backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

        try {
            $download = $this->downloadFile($this->githubZipUrl, $zipFile);
            if (($download['success'] ?? false) !== true) {
                throw new RuntimeException((string) ($download['error'] ?? 'ZIP-Datei konnte nicht geladen werden.'));
            }

            if (!is_dir($extractDir) && !mkdir($extractDir, 0755, true) && !is_dir($extractDir)) {
                throw new RuntimeException('Temporäres Entpack-Verzeichnis konnte nicht erstellt werden.');
            }

            $zip = new ZipArchive();
            $zipResult = $zip->open($zipFile);
            if ($zipResult !== true) {
                throw new RuntimeException('GitHub-ZIP konnte nicht geöffnet werden (Fehlercode: ' . $zipResult . ').');
            }

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new RuntimeException('GitHub-ZIP konnte nicht entpackt werden.');
            }
            $zip->close();

            $sourceDocs = $this->findDocDirectory($extractDir);
            if ($sourceDocs === null || !is_dir($sourceDocs)) {
                throw new RuntimeException('Der Ordner /DOC wurde im heruntergeladenen Archiv nicht gefunden.');
            }

            if (is_dir($stagingDir)) {
                $this->deleteDirectory($stagingDir);
            }

            if (!mkdir($stagingDir, 0755, true) && !is_dir($stagingDir)) {
                throw new RuntimeException('Staging-Verzeichnis für den Doku-Sync konnte nicht erstellt werden.');
            }

            $this->copyDirectory($sourceDocs, $stagingDir);

            $hadExistingDocs = is_dir($this->docsRoot);
            if ($hadExistingDocs && !$this->renamePath($this->docsRoot, $backupDir, 'Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.')) {
                throw new RuntimeException('Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.');
            }

            if (!$this->renamePath($stagingDir, $this->docsRoot, 'Der neue /DOC-Stand konnte nicht aktiviert werden.')) {
                if ($hadExistingDocs && is_dir($backupDir)) {
                    $this->renamePath($backupDir, $this->docsRoot, 'Der gesicherte /DOC-Ordner konnte nach fehlgeschlagener Aktivierung nicht wiederhergestellt werden.');
                }
                throw new RuntimeException('Der neue /DOC-Stand konnte nicht aktiviert werden.');
            }

            if ($hadExistingDocs && is_dir($backupDir)) {
                $this->deleteDirectory($backupDir);
            }

            return [
                'success' => true,
                'message' => 'Der lokale Ordner /DOC wurde per GitHub-Download synchronisiert. ' . $this->countSupportedDocuments($this->docsRoot) . ' Dokumente sind jetzt lokal verfügbar.',
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'DOC-Sync via GitHub fehlgeschlagen: ' . $e->getMessage()];
        } finally {
            if (is_file($zipFile)) {
                $this->deleteFile($zipFile);
            }
            if (is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }
            if (is_dir($stagingDir)) {
                $this->deleteDirectory($stagingDir);
            }
        }
    }

    private function isGitAvailable(): bool
    {
        $result = $this->runCommand('git --version 2>&1');
        return ($result['exitCode'] ?? 1) === 0 && str_contains(strtolower((string) ($result['output'] ?? '')), 'git version');
    }

    /**
     * @return array{output: string, exitCode: int}
     */
    private function runCommand(string $command): array
    {
        if (!$this->isCommandExecutionAvailable()) {
            return ['output' => 'Befehlsausführung ist auf diesem Server deaktiviert.', 'exitCode' => 1];
        }

        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        return [
            'output' => trim(implode("\n", $output)),
            'exitCode' => $exitCode,
        ];
    }

    private function isCommandExecutionAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = (string) ini_get('disable_functions');
        if ($disabled === '') {
            return true;
        }

        return !in_array('exec', array_map('trim', explode(',', $disabled)), true);
    }

    private function canDownloadOverHttp(): bool
    {
        return extension_loaded('curl') && class_exists('\\CMS\\Http\\Client');
    }

    /**
     * @return array{success: bool, error?: string}
     */
    private function downloadFile(string $url, string $destination): array
    {
        if (!str_starts_with($url, 'https://')) {
            return ['success' => false, 'error' => 'Es sind nur HTTPS-Downloads erlaubt.'];
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
            'maxBytes' => 25 * 1024 * 1024,
            'allowedContentTypes' => ['application/zip', 'application/octet-stream', 'application/x-zip-compressed'],
        ]);

        if (($response['success'] ?? false) !== true) {
            if (is_file($destination)) {
                $this->deleteFile($destination);
            }

            return ['success' => false, 'error' => (string) ($response['error'] ?? 'GitHub-ZIP konnte per HTTPS nicht geladen werden.')];
        }

        $written = $this->runFilesystemOperation('file_put_contents', 'Die geladene ZIP-Datei konnte nicht lokal gespeichert werden.', [
            'path' => $destination,
        ], static fn (): int|false => file_put_contents($destination, (string) ($response['body'] ?? '')));

        if (!is_int($written)) {
            if (is_file($destination)) {
                $this->deleteFile($destination);
            }

            return ['success' => false, 'error' => 'Die geladene ZIP-Datei konnte nicht lokal gespeichert werden.'];
        }

        return ['success' => true];
    }

    private function findDocDirectory(string $extractRoot): ?string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if (!$item->isDir() || strcasecmp($item->getFilename(), 'DOC') !== 0) {
                continue;
            }

            $docPath = $item->getPathname();
            if (is_file($docPath . DIRECTORY_SEPARATOR . 'README.md') || is_dir($docPath . DIRECTORY_SEPARATOR . 'admin')) {
                return $docPath;
            }
        }

        return null;
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            throw new RuntimeException('Quellverzeichnis für den Doku-Sync existiert nicht.');
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

    private function countSupportedDocuments(string $docsRoot): int
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
            if ($item->isFile()) {
                $extension = strtolower((string) pathinfo($item->getPathname(), PATHINFO_EXTENSION));
                if (in_array($extension, ['md', 'csv'], true)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function deleteDirectory(string $dir): bool
    {
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
            if (is_dir($path)) {
                $this->deleteDirectory($path);
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

    private function renamePath(string $source, string $destination, string $message): bool
    {
        if (!file_exists($source)) {
            $this->logFilesystemFailure('rename_preflight', $message, [
                'source' => $source,
                'destination' => $destination,
                'reason' => 'source_missing',
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

    private function deleteFile(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
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
}
