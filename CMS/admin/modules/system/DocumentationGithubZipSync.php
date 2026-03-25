<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationGithubZipWorkspace
{
    public function __construct(
        public readonly string $zipFile,
        public readonly string $extractDir,
        public readonly string $stagingDir,
        public readonly string $backupDir
    ) {
    }

    /** @return list<string> */
    public function cleanupDirectories(): array
    {
        return [$this->extractDir, $this->stagingDir];
    }
}

final class DocumentationGithubZipSync
{
    private const ALLOWED_ZIP_HOSTS = [
        'codeload.github.com',
        'github.com',
        'raw.githubusercontent.com',
    ];
    private const MAX_ZIP_ENTRIES = 2500;
    private const MAX_ARCHIVE_BYTES = 50 * 1024 * 1024;
    private const MAX_LOG_VALUE_LENGTH = 240;

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

        $workspace = $this->createWorkspace();

        try {
            $this->assertDocsRootLocation();
            $this->assertGithubZipUrl();
            $this->assertApprovedBundleConfiguration();
            $this->assertWorkingPaths($workspace);

            $download = $this->downloader->downloadFile($this->githubZipUrl, $workspace->zipFile);
            if (!$download->isSuccess()) {
                throw new RuntimeException((string) ($download->error() ?? 'ZIP-Datei konnte nicht geladen werden.'));
            }

            $this->assertDownloadedArchive($workspace->zipFile, $download);
            $this->extractArchive($workspace);

            $sourceDocs = $this->filesystem->findDocDirectory($workspace->extractDir);
            if ($sourceDocs === null || !is_dir($sourceDocs)) {
                throw new RuntimeException('Der Ordner /DOC wurde im heruntergeladenen Archiv nicht gefunden.');
            }

            $this->assertApprovedDocsBundle($sourceDocs);
            $this->stageDocsSnapshot($sourceDocs, $workspace);
            $this->activateDocsSnapshot($workspace);

            $documentCount = $this->filesystem->countSupportedDocuments($this->docsRoot);

            $this->logSuccess('documentation.sync.github_zip.completed', 'DOC-Sync via GitHub-Download abgeschlossen.', [
                'zip_url' => $this->sanitizeUrlForLog($this->githubZipUrl),
                'docs_root' => $this->sanitizePathForLog($this->docsRoot),
                'document_count' => $documentCount,
            ]);

            return [
                'success' => true,
                'message' => 'Der lokale Ordner /DOC wurde per GitHub-Download synchronisiert. ' . $documentCount . ' Dokumente sind jetzt lokal verfügbar.',
            ];
        } catch (Throwable $e) {
            return $this->failResult('documentation.sync.github_zip.failed', 'DOC-Sync via GitHub konnte nicht abgeschlossen werden.', $e, [
                'zip_url' => $this->sanitizeUrlForLog($this->githubZipUrl),
                'docs_root' => $this->sanitizePathForLog($this->docsRoot),
            ]);
        } finally {
            $this->cleanupWorkspace($workspace);
        }
    }

    private function createWorkspace(): DocumentationGithubZipWorkspace
    {
        $tempBase = rtrim(sys_get_temp_dir(), '\/') . DIRECTORY_SEPARATOR . '365cms_doc_sync_' . bin2hex(random_bytes(6));

        return new DocumentationGithubZipWorkspace(
            $tempBase . '.zip',
            $tempBase . '_extract',
            $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC.__sync_' . bin2hex(random_bytes(4)),
            $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC.__backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3))
        );
    }

    private function extractArchive(DocumentationGithubZipWorkspace $workspace): void
    {
        if (!is_dir($workspace->extractDir) && !mkdir($workspace->extractDir, 0755, true) && !is_dir($workspace->extractDir)) {
            throw new RuntimeException('Temporäres Entpack-Verzeichnis konnte nicht erstellt werden.');
        }

        $zip = new ZipArchive();
        $zipResult = $zip->open($workspace->zipFile);
        if ($zipResult !== true) {
            throw new RuntimeException('GitHub-ZIP konnte nicht geöffnet werden (Fehlercode: ' . $zipResult . ').');
        }

        try {
            if (!$this->validateZipEntries($zip)) {
                throw new RuntimeException('GitHub-ZIP enthält unsichere oder unerwartete Archiv-Einträge.');
            }

            if (!$zip->extractTo($workspace->extractDir)) {
                throw new RuntimeException('GitHub-ZIP konnte nicht entpackt werden.');
            }
        } finally {
            $zip->close();
        }
    }

    private function stageDocsSnapshot(string $sourceDocs, DocumentationGithubZipWorkspace $workspace): void
    {
        if (is_dir($workspace->stagingDir)) {
            $this->filesystem->deleteDirectory($workspace->stagingDir);
        }

        if (!mkdir($workspace->stagingDir, 0755, true) && !is_dir($workspace->stagingDir)) {
            throw new RuntimeException('Staging-Verzeichnis für den Doku-Sync konnte nicht erstellt werden.');
        }

        $this->filesystem->copyDirectory($sourceDocs, $workspace->stagingDir);
    }

    private function activateDocsSnapshot(DocumentationGithubZipWorkspace $workspace): void
    {
        $hadExistingDocs = is_dir($this->docsRoot);
        if ($hadExistingDocs && !$this->filesystem->renamePath($this->docsRoot, $workspace->backupDir, 'Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.')) {
            throw new RuntimeException('Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.');
        }

        if (!$this->filesystem->renamePath($workspace->stagingDir, $this->docsRoot, 'Der neue /DOC-Stand konnte nicht aktiviert werden.')) {
            if ($hadExistingDocs && is_dir($workspace->backupDir)) {
                $this->filesystem->renamePath($workspace->backupDir, $this->docsRoot, 'Der gesicherte /DOC-Ordner konnte nach fehlgeschlagener Aktivierung nicht wiederhergestellt werden.');
            }

            throw new RuntimeException('Der neue /DOC-Stand konnte nicht aktiviert werden.');
        }

        if ($hadExistingDocs && is_dir($workspace->backupDir)) {
            $this->filesystem->deleteDirectory($workspace->backupDir);
        }
    }

    private function cleanupWorkspace(DocumentationGithubZipWorkspace $workspace): void
    {
        if (is_file($workspace->zipFile)) {
            $this->filesystem->deleteFile($workspace->zipFile);
        }

        foreach ($workspace->cleanupDirectories() as $directory) {
            if (is_dir($directory)) {
                $this->filesystem->deleteDirectory($directory);
            }
        }

        if (is_dir($workspace->backupDir) && is_dir($this->docsRoot)) {
            $this->filesystem->deleteDirectory($workspace->backupDir);
        }
    }

    private function validateZipEntries(ZipArchive $zip): bool
    {
        $hasEntries = false;
        $totalUncompressedSize = 0;

        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ZIP_ENTRIES) {
            return false;
        }

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

                if (preg_match('/[\x00-\x1F\x7F]/', $segment) === 1) {
                    return false;
                }
            }

            $stat = $zip->statIndex($index);
            if (!is_array($stat)) {
                return false;
            }

            $entrySize = (int)($stat['size'] ?? 0);
            if ($entrySize < 0) {
                return false;
            }

            $totalUncompressedSize += $entrySize;
            if ($totalUncompressedSize > self::MAX_ARCHIVE_BYTES) {
                return false;
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

    private function assertWorkingPaths(DocumentationGithubZipWorkspace $workspace): void
    {
        $repoRoot = rtrim((string) realpath($this->repoRoot), '\\/');
        $tempRoot = rtrim((string) realpath(sys_get_temp_dir()), '\\/');

        if ($repoRoot === '' || $tempRoot === '') {
            throw new RuntimeException('Arbeitsverzeichnisse für den Doku-Sync konnten nicht sicher aufgelöst werden.');
        }

        if (!$this->isPathInsideRoot($workspace->zipFile, $tempRoot)
            || !$this->isPathInsideRoot($workspace->extractDir, $tempRoot)
            || !$this->isPathInsideRoot($workspace->stagingDir, $repoRoot)
            || !$this->isPathInsideRoot($workspace->backupDir, $repoRoot)
        ) {
            throw new RuntimeException('Temporäre Arbeitsverzeichnisse des Doku-Sync liegen außerhalb der erlaubten Roots.');
        }

        foreach ([$workspace->zipFile, $workspace->extractDir, $workspace->stagingDir, $workspace->backupDir] as $path) {
            if (is_link($path)) {
                throw new RuntimeException('Der Doku-Sync verarbeitet keine symbolischen Links als Arbeitsverzeichnisse.');
            }
        }
    }

    private function assertApprovedDocsBundle(string $sourceDocs): void
    {
        $integrity = $this->filesystem->calculateDirectoryIntegrity($sourceDocs);
        $actualHash = strtolower((string) ($integrity['hash'] ?? ''));
        $actualFileCount = (int) ($integrity['file_count'] ?? 0);

        if ($actualHash !== strtolower($this->approvedDocsBundleHash) || $actualFileCount !== $this->approvedDocsBundleFileCount) {
            throw new RuntimeException('Der heruntergeladene /DOC-Baum entspricht nicht dem freigegebenen Dokumentations-Bundle.');
        }
    }

    private function assertGithubZipUrl(): void
    {
        if (!filter_var($this->githubZipUrl, FILTER_VALIDATE_URL) || !str_starts_with($this->githubZipUrl, 'https://')) {
            throw new RuntimeException('Die GitHub-ZIP-Quelle für den Doku-Sync ist ungültig konfiguriert.');
        }

        $parts = parse_url($this->githubZipUrl);
        if (!is_array($parts)) {
            throw new RuntimeException('Die GitHub-ZIP-Quelle für den Doku-Sync ist ungültig konfiguriert.');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ($host === ''
            || !in_array($host, self::ALLOWED_ZIP_HOSTS, true)
            || $path === ''
            || preg_match('/[\x00-\x1F\x7F]/', $path) === 1
            || isset($parts['query'])
            || isset($parts['fragment'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || (!str_contains($path, '/zip/') && !str_contains($path, '/zipball/'))
        ) {
            throw new RuntimeException('Die GitHub-ZIP-Quelle für den Doku-Sync ist ungültig konfiguriert.');
        }
    }

    private function assertDownloadedArchive(string $zipFile, DocumentationDownloadResult $download): void
    {
        if (!is_file($zipFile) || !is_readable($zipFile) || is_link($zipFile)) {
            throw new RuntimeException('Die heruntergeladene ZIP-Datei ist nicht als sichere lokale Datei verfügbar.');
        }

        $archiveSize = filesize($zipFile);
        if ($archiveSize === false
            || $archiveSize < 1
            || $archiveSize > self::MAX_ARCHIVE_BYTES
            || $archiveSize !== $download->bytes()
        ) {
            throw new RuntimeException('Die heruntergeladene ZIP-Datei hat eine ungültige Größe.');
        }

        $sha256 = hash_file('sha256', $zipFile);
        if (!is_string($sha256) || $sha256 !== $download->sha256()) {
            throw new RuntimeException('Die heruntergeladene ZIP-Datei konnte nicht konsistent validiert werden.');
        }
    }

    private function assertApprovedBundleConfiguration(): void
    {
        if (preg_match('/^[0-9a-f]{64}$/', strtolower($this->approvedDocsBundleHash)) !== 1 || $this->approvedDocsBundleFileCount <= 0) {
            throw new RuntimeException('Für den Doku-Sync ist kein gültiges freigegebenes Integritätsprofil hinterlegt.');
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
        $context['exception'] = $exception::class;
        $context['message'] = $this->sanitizeLogString($exception->getMessage(), self::MAX_LOG_VALUE_LENGTH);

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

    /** @param array<string, mixed> $context */
    private function logSuccess(string $action, string $message, array $context = []): void
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

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        return $this->sanitizeLogString(($host !== '' ? $host : 'invalid-host') . $path, self::MAX_LOG_VALUE_LENGTH);
    }

    private function sanitizePathForLog(string $path): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $normalizedRepoRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->repoRoot), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath, $normalizedRepoRoot)) {
            $normalizedPath = substr($normalizedPath, strlen($normalizedRepoRoot)) ?: 'repo-root';
        }

        return $this->sanitizeLogString($normalizedPath, self::MAX_LOG_VALUE_LENGTH);
    }

    private function sanitizeLogString(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }
}