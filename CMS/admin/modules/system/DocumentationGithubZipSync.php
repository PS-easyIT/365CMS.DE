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

            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new RuntimeException('GitHub-ZIP konnte nicht entpackt werden.');
            }
            $zip->close();

            $sourceDocs = $this->filesystem->findDocDirectory($extractDir);
            if ($sourceDocs === null || !is_dir($sourceDocs)) {
                throw new RuntimeException('Der Ordner /DOC wurde im heruntergeladenen Archiv nicht gefunden.');
            }

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
            return ['success' => false, 'error' => 'DOC-Sync via GitHub fehlgeschlagen: ' . $e->getMessage()];
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
}