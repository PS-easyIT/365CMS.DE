<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationGithubZipSync
{
    public function __construct(
        private readonly string $repoRoot,
        private readonly string $docsRoot,
        private readonly string $githubZipUrl,
        private readonly string $approvedDocsBundleHash,
        private readonly int $approvedDocsBundleFileCount,
        private readonly DocumentationSyncDownloader $downloader,
        private readonly DocumentationSyncFilesystem $filesystem
    ) {
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function sync(): array
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
            $this->assertDocsRootLocation();
            $this->assertWorkingPaths($zipFile, $extractDir, $stagingDir, $backupDir);

            $download = $this->downloader->downloadFile($this->githubZipUrl, $zipFile);
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

            if (!$this->validateZipEntries($zip)) {
                $zip->close();
                throw new RuntimeException('GitHub-ZIP enthält unsichere oder unerwartete Archiv-Einträge.');
            }

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new RuntimeException('GitHub-ZIP konnte nicht entpackt werden.');
            }
            $zip->close();

            $sourceDocs = $this->filesystem->findDocDirectory($extractDir);
            if ($sourceDocs === null || !is_dir($sourceDocs)) {
                throw new RuntimeException('Der Ordner /DOC wurde im heruntergeladenen Archiv nicht gefunden.');
            }

            $this->assertApprovedDocsBundle($sourceDocs);

            if (is_dir($stagingDir)) {
                $this->filesystem->deleteDirectory($stagingDir);
            }

            if (!mkdir($stagingDir, 0755, true) && !is_dir($stagingDir)) {
                throw new RuntimeException('Staging-Verzeichnis für den Doku-Sync konnte nicht erstellt werden.');
            }

            $this->filesystem->copyDirectory($sourceDocs, $stagingDir);

            $hadExistingDocs = is_dir($this->docsRoot);
            if ($hadExistingDocs && !$this->filesystem->renamePath($this->docsRoot, $backupDir, 'Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.')) {
                throw new RuntimeException('Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.');
            }

            if (!$this->filesystem->renamePath($stagingDir, $this->docsRoot, 'Der neue /DOC-Stand konnte nicht aktiviert werden.')) {
                if ($hadExistingDocs && is_dir($backupDir)) {
                    $this->filesystem->renamePath($backupDir, $this->docsRoot, 'Der gesicherte /DOC-Ordner konnte nach fehlgeschlagener Aktivierung nicht wiederhergestellt werden.');
                }
                throw new RuntimeException('Der neue /DOC-Stand konnte nicht aktiviert werden.');
            }

            if ($hadExistingDocs && is_dir($backupDir)) {
                $this->filesystem->deleteDirectory($backupDir);
            }

            return [
                'success' => true,
                'message' => 'Der lokale Ordner /DOC wurde per GitHub-Download synchronisiert. ' . $this->filesystem->countSupportedDocuments($this->docsRoot) . ' Dokumente sind jetzt lokal verfügbar.',
            ];
        } catch (Throwable $e) {
            return $this->failResult('documentation.sync.github_zip.failed', 'DOC-Sync via GitHub konnte nicht abgeschlossen werden.', $e, [
                'zip_url' => $this->githubZipUrl,
                'docs_root' => $this->docsRoot,
            ]);
        } finally {
            if (is_file($zipFile)) {
                $this->filesystem->deleteFile($zipFile);
            }
            if (is_dir($extractDir)) {
                $this->filesystem->deleteDirectory($extractDir);
            }
            if (is_dir($stagingDir)) {
                $this->filesystem->deleteDirectory($stagingDir);
            }
        }
    }

    private function validateZipEntries(ZipArchive $zip): bool
    {
        $hasEntries = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (!is_string($entryName) || $entryName === '') {
                return false;
            }

            if (str_contains($entryName, "\0") || preg_match('/[\x00-\x1F\x7F]/', $entryName) === 1) {
                return false;
            }

            $normalized = str_replace('\\', '/', $entryName);
            $normalized = ltrim($normalized, '/');

            if ($normalized === ''
                || str_contains($normalized, '../')
                || str_contains($normalized, '..\\')
                || preg_match('~^[A-Za-z]:/~', $normalized) === 1
            ) {
                return false;
            }

            $segments = array_values(array_filter(explode('/', rtrim($normalized, '/')), static fn(string $segment): bool => $segment !== ''));
            foreach ($segments as $segment) {
                if ($segment === '.' || $segment === '..') {
                    return false;
                }
            }

            $hasEntries = true;
        }

        return $hasEntries;
    }

    private function assertDocsRootLocation(): void
    {
        $expectedDocsRoot = rtrim($this->repoRoot, '\\/') . DIRECTORY_SEPARATOR . 'DOC';
        if (rtrim($this->docsRoot, '\\/') !== $expectedDocsRoot) {
            throw new RuntimeException('Der Doku-Sync darf /DOC nur direkt im Repository-Root neben /CMS verwalten.');
        }

        if (is_link($this->docsRoot)) {
            throw new RuntimeException('Der lokale /DOC-Ordner darf kein symbolischer Link sein.');
        }
    }

    private function assertWorkingPaths(string $zipFile, string $extractDir, string $stagingDir, string $backupDir): void
    {
        $repoRoot = rtrim((string) realpath($this->repoRoot), '\\/');
        $tempRoot = rtrim((string) realpath(sys_get_temp_dir()), '\\/');

        if ($repoRoot === '' || $tempRoot === '') {
            throw new RuntimeException('Arbeitsverzeichnisse für den Doku-Sync konnten nicht sicher aufgelöst werden.');
        }

        if (!$this->isPathInsideRoot($zipFile, $tempRoot)
            || !$this->isPathInsideRoot($extractDir, $tempRoot)
            || !$this->isPathInsideRoot($stagingDir, $repoRoot)
            || !$this->isPathInsideRoot($backupDir, $repoRoot)
        ) {
            throw new RuntimeException('Temporäre Arbeitsverzeichnisse des Doku-Sync liegen außerhalb der erlaubten Roots.');
        }

        foreach ([$zipFile, $extractDir, $stagingDir, $backupDir] as $path) {
            if (is_link($path)) {
                throw new RuntimeException('Der Doku-Sync verarbeitet keine symbolischen Links als Arbeitsverzeichnisse.');
            }
        }
    }

    private function assertApprovedDocsBundle(string $sourceDocs): void
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $this->approvedDocsBundleHash) || $this->approvedDocsBundleFileCount <= 0) {
            throw new RuntimeException('Für den Doku-Sync ist kein gültiges freigegebenes Integritätsprofil hinterlegt.');
        }

        $integrity = $this->filesystem->calculateDirectoryIntegrity($sourceDocs);
        $actualHash = strtolower((string) ($integrity['hash'] ?? ''));
        $actualFileCount = (int) ($integrity['file_count'] ?? 0);

        if ($actualHash !== $this->approvedDocsBundleHash || $actualFileCount !== $this->approvedDocsBundleFileCount) {
            throw new RuntimeException('Der heruntergeladene /DOC-Baum entspricht nicht dem freigegebenen Dokumentations-Bundle.');
        }
    }

    private function isPathInsideRoot(string $path, string $root): bool
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($path, '\\/'));
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($root, '\\/'));

        return $normalizedPath === $normalizedRoot
            || str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR);
    }

    /** @param array<string, mixed> $context */
    private function failResult(string $action, string $message, Throwable $exception, array $context = []): array
    {
        $context['exception'] = $exception->getMessage();

        \CMS\Logger::instance()->withChannel('admin.documentation')->error($message, $context);
        \CMS\AuditLogger::instance()->log(
            \CMS\AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            'error'
        );

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }
}