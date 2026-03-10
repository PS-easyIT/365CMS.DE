<?php
declare(strict_types=1);

/**
 * Dokumentations-Modul
 *
 * Lädt die lokale Repo-Dokumentation aus /DOC und bildet damit den
 * GitHub-Dokumentationsbereich innerhalb des Admin-Panels ab.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationModule
{
    private const GITHUB_DOC_BASE = 'https://github.com/PS-easyIT/365CMS.DE/blob/main/DOC/';
    private const GITHUB_DOC_TREE = 'https://github.com/PS-easyIT/365CMS.DE/tree/main/DOC';
    private const GITHUB_DOC_ZIP = 'https://codeload.github.com/PS-easyIT/365CMS.DE/zip/refs/heads/main';
    private const DEFAULT_REMOTE = 'origin';
    private const DEFAULT_BRANCH = 'main';

    /**
     * @return array<string, mixed>
     */
    public function getData(?string $selectedDoc = null): array
    {
        $docsRoot = $this->getDocsRoot();

        if (!is_dir($docsRoot)) {
            return [
                'available'        => false,
                'docs_root'        => $docsRoot,
                'github_root_url'  => self::GITHUB_DOC_TREE,
                'sections'         => [],
                'featured_docs'    => [],
                'selected_document'=> null,
                'selected_html'    => '',
                'selected_raw'     => '',
                'doc_count'        => 0,
                'section_count'    => 0,
                'is_selected_csv'  => false,
                'error'            => 'Das Dokumentationsverzeichnis /DOC wurde im Repository nicht gefunden.',
            ];
        }

        $sections = $this->scanSections($docsRoot);
        $allDocs  = $this->flattenDocuments($sections);

        $selectedRelative = $this->normalizeRelativePath((string) $selectedDoc);
        if ($selectedRelative === '' || !isset($allDocs[$selectedRelative])) {
            $selectedRelative = $this->resolveDefaultDocument(array_keys($allDocs));
        }

        $selectedDocument = $selectedRelative !== '' && isset($allDocs[$selectedRelative])
            ? $allDocs[$selectedRelative]
            : null;

        $selectedRaw = '';
        $selectedHtml = '';
        $isSelectedCsv = false;

        if (is_array($selectedDocument) && !empty($selectedDocument['full_path']) && is_file($selectedDocument['full_path'])) {
            $selectedRaw = (string) file_get_contents($selectedDocument['full_path']);
            $extension = strtolower((string) ($selectedDocument['extension'] ?? 'md'));
            $isSelectedCsv = $extension === 'csv';
            $selectedHtml = $isSelectedCsv
                ? $this->renderCsv($selectedRaw)
                : $this->renderMarkdown($selectedRaw, (string) $selectedDocument['relative_path']);
        }

        return [
            'available'         => true,
            'docs_root'         => $docsRoot,
            'repo_root'         => $this->getRepoRoot(),
            'github_root_url'   => self::GITHUB_DOC_TREE,
            'sections'          => $sections,
            'featured_docs'     => $this->getFeaturedDocuments($allDocs),
            'selected_document' => $selectedDocument,
            'selected_html'     => $selectedHtml,
            'selected_raw'      => $selectedRaw,
            'doc_count'         => count($allDocs),
            'section_count'     => count($sections),
            'is_selected_csv'   => $isSelectedCsv,
            'git_available'     => $this->isGitAvailable(),
            'sync_capabilities' => $this->getSyncCapabilities(),
            'error'             => null,
        ];
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
     * @return array{success: bool, message?: string, error?: string}
     */
    private function syncDocsViaGit(): array
    {
        $repoRoot = $this->getRepoRoot();
        $docsRoot = $this->getDocsRoot();

        $fetch = $this->runCommand(sprintf(
            'git -C %s fetch %s %s 2>&1',
            escapeshellarg($repoRoot),
            escapeshellarg(self::DEFAULT_REMOTE),
            escapeshellarg(self::DEFAULT_BRANCH)
        ));

        if (($fetch['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Fetch): ' . trim((string)($fetch['output'] ?? 'Unbekannter Fehler'))];
        }

        $checkout = $this->runCommand(sprintf(
            'git -C %s checkout %s/%s -- DOC 2>&1',
            escapeshellarg($repoRoot),
            escapeshellarg(self::DEFAULT_REMOTE),
            escapeshellarg(self::DEFAULT_BRANCH)
        ));

        if (($checkout['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Checkout): ' . trim((string)($checkout['output'] ?? 'Unbekannter Fehler'))];
        }

        $status = $this->runCommand(sprintf(
            'git -C %s status --short -- DOC 2>&1',
            escapeshellarg($repoRoot)
        ));

        $statusOutput = trim((string)($status['output'] ?? ''));
        $message = 'Der lokale Ordner /DOC wurde mit ' . self::DEFAULT_REMOTE . '/' . self::DEFAULT_BRANCH . ' synchronisiert.';

        if ($statusOutput !== '') {
            $message .= ' Geänderte Dateien: ' . $statusOutput;
        } elseif (is_dir($docsRoot)) {
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

        $repoRoot = $this->getRepoRoot();
        $docsRoot = $this->getDocsRoot();
        $tempBase = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . '365cms_doc_sync_' . bin2hex(random_bytes(6));
        $zipFile = $tempBase . '.zip';
        $extractDir = $tempBase . '_extract';
        $stagingDir = $repoRoot . DIRECTORY_SEPARATOR . 'DOC.__sync_' . bin2hex(random_bytes(4));
        $backupDir = $repoRoot . DIRECTORY_SEPARATOR . 'DOC.__backup_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));

        try {
            $download = $this->downloadFile(self::GITHUB_DOC_ZIP, $zipFile);
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

            $hadExistingDocs = is_dir($docsRoot);
            if ($hadExistingDocs) {
                if (!$this->renamePath($docsRoot, $backupDir, 'Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.')) {
                    throw new RuntimeException('Der bestehende lokale /DOC-Ordner konnte nicht gesichert werden.');
                }
            }

            if (!$this->renamePath($stagingDir, $docsRoot, 'Der neue /DOC-Stand konnte nicht aktiviert werden.')) {
                if ($hadExistingDocs && is_dir($backupDir)) {
                    $this->renamePath($backupDir, $docsRoot, 'Der gesicherte /DOC-Ordner konnte nach fehlgeschlagener Aktivierung nicht wiederhergestellt werden.');
                }
                throw new RuntimeException('Der neue /DOC-Stand konnte nicht aktiviert werden.');
            }

            if ($hadExistingDocs && is_dir($backupDir)) {
                $this->deleteDirectory($backupDir);
            }

            $docCount = $this->countSupportedDocuments($docsRoot);

            return [
                'success' => true,
                'message' => 'Der lokale Ordner /DOC wurde per GitHub-Download synchronisiert. ' . $docCount . ' Dokumente sind jetzt lokal verfügbar.',
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

    private function getDocsRoot(): string
    {
        return rtrim((string) dirname((string) ABSPATH), '\\/') . DIRECTORY_SEPARATOR . 'DOC';
    }

    private function getRepoRoot(): string
    {
        return rtrim((string) dirname((string) ABSPATH), '\\/');
    }

    /**
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    private function getSyncCapabilities(): array
    {
        $repoRoot = $this->getRepoRoot();
        $hasGitCheckout = is_dir($repoRoot . DIRECTORY_SEPARATOR . '.git');
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

    private function isGitAvailable(): bool
    {
        $result = $this->runCommand('git --version 2>&1');
        return ($result['exitCode'] ?? 1) === 0 && str_contains(strtolower((string)($result['output'] ?? '')), 'git version');
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

        $disabled = (string)ini_get('disable_functions');
        if ($disabled === '') {
            return true;
        }

        $disabledFunctions = array_map('trim', explode(',', $disabled));
        return !in_array('exec', $disabledFunctions, true);
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
                unlink($destination);
            }

            return ['success' => false, 'error' => (string) ($response['error'] ?? 'GitHub-ZIP konnte per HTTPS nicht geladen werden.')];
        }

        if (file_put_contents($destination, (string) ($response['body'] ?? '')) === false) {
            if (is_file($destination)) {
                unlink($destination);
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
            if (!$item->isDir()) {
                continue;
            }

            if (strcasecmp($item->getFilename(), 'DOC') !== 0) {
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
            $this->logFilesystemFailure('rmdir_preflight', 'Verzeichnis oder Elternpfad ist nicht beschreibbar.', [
                'path' => $dir,
            ]);

            return false;
        }

        $removed = $this->runFilesystemOperation('rmdir', 'Verzeichnis konnte nicht gelöscht werden.', [
            'path' => $dir,
        ], static fn (): bool => rmdir($dir));

        return $removed === true;
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

        $renamed = $this->runFilesystemOperation('rename', $message, [
            'source' => $source,
            'destination' => $destination,
        ], static fn (): bool => rename($source, $destination));

        return $renamed === true;
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

        $copied = $this->runFilesystemOperation('copy', 'Datei konnte nicht kopiert werden.', [
            'source' => $source,
            'destination' => $destination,
        ], static fn (): bool => copy($source, $destination));

        return $copied === true;
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

        $deleted = $this->runFilesystemOperation('unlink', 'Datei konnte nicht gelöscht werden.', [
            'path' => $path,
        ], static fn (): bool => unlink($path));

        return $deleted === true;
    }

    private function readFileContents(string $fullPath): string
    {
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            $this->logFilesystemFailure('file_get_contents_preflight', 'Dokument konnte nicht gelesen werden.', [
                'path' => $fullPath,
                'reason' => 'file_unreadable',
            ]);

            return '';
        }

        $contents = $this->runFilesystemOperation('file_get_contents', 'Dokument konnte nicht gelesen werden.', [
            'path' => $fullPath,
        ], static fn (): string|false => file_get_contents($fullPath));

        return is_string($contents) ? $contents : '';
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
            if ($item->isFile() && $this->isSupportedDocument($item->getPathname())) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanSections(string $docsRoot): array
    {
        $sections = [];
        $rootDocs = [];

        $entries = scandir($docsRoot);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $docsRoot . DIRECTORY_SEPARATOR . $entry;

            if (is_file($fullPath) && $this->isSupportedDocument($fullPath)) {
                $rootDocs[] = $this->buildDocumentMeta($fullPath, $docsRoot);
                continue;
            }

            if (is_dir($fullPath)) {
                $documents = $this->scanDocumentsInDirectory($fullPath, $docsRoot);
                if ($documents === []) {
                    continue;
                }

                $sections[] = [
                    'slug'        => $entry,
                    'title'       => $this->resolveSectionTitle($entry),
                    'description' => $this->resolveSectionDescription($entry),
                    'github_url'  => $this->buildGithubTreeUrl($entry),
                    'doc_count'   => count($documents),
                    'documents'   => $documents,
                ];
            }
        }

        usort($sections, static function (array $a, array $b): int {
            return strcasecmp((string) $a['title'], (string) $b['title']);
        });

        if ($rootDocs !== []) {
            usort($rootDocs, [$this, 'compareDocuments']);
            array_unshift($sections, [
                'slug'        => 'root',
                'title'       => 'Basisdokumente',
                'description' => 'Zentrale Einstiegs- und Referenzdokumente aus dem Wurzelverzeichnis von /DOC.',
                'github_url'  => self::GITHUB_DOC_TREE,
                'doc_count'   => count($rootDocs),
                'documents'   => $rootDocs,
            ]);
        }

        return $sections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanDocumentsInDirectory(string $directory, string $docsRoot): array
    {
        $documents = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $fullPath = $file->getPathname();
            if (!$this->isSupportedDocument($fullPath)) {
                continue;
            }

            $documents[] = $this->buildDocumentMeta($fullPath, $docsRoot);
        }

        usort($documents, [$this, 'compareDocuments']);

        return $documents;
    }

    private function compareDocuments(array $left, array $right): int
    {
        $leftPath = (string) ($left['relative_path'] ?? '');
        $rightPath = (string) ($right['relative_path'] ?? '');

        $leftIsReadme = str_ends_with(strtolower($leftPath), '/readme.md') || strtolower($leftPath) === 'readme.md';
        $rightIsReadme = str_ends_with(strtolower($rightPath), '/readme.md') || strtolower($rightPath) === 'readme.md';

        if ($leftIsReadme !== $rightIsReadme) {
            return $leftIsReadme ? -1 : 1;
        }

        return strcasecmp($leftPath, $rightPath);
    }

    private function isSupportedDocument(string $fullPath): bool
    {
        $extension = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));
        return in_array($extension, ['md', 'csv'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocumentMeta(string $fullPath, string $docsRoot): array
    {
        $relativePath = $this->relativePath($fullPath, $docsRoot);
        $contents = $this->readFileContents($fullPath);
        $extension = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));

        return [
            'title'         => $this->extractTitle($relativePath, $contents),
            'excerpt'       => $this->extractExcerpt($contents, $extension),
            'relative_path' => $relativePath,
            'full_path'     => $fullPath,
            'extension'     => $extension,
            'github_url'    => $this->buildGithubBlobUrl($relativePath),
            'admin_url'     => $this->buildAdminUrl($relativePath),
        ];
    }

    private function relativePath(string $fullPath, string $docsRoot): string
    {
        $relative = substr($fullPath, strlen(rtrim($docsRoot, '\\/')) + 1);
        return $this->normalizeRelativePath((string) $relative);
    }

    private function extractTitle(string $relativePath, string $contents): string
    {
        if (preg_match('/^#\s+(.+)$/m', $contents, $matches) === 1) {
            return trim($matches[1]);
        }

        return (string) pathinfo($relativePath, PATHINFO_FILENAME);
    }

    private function extractExcerpt(string $contents, string $extension): string
    {
        if ($extension === 'csv') {
            return 'CSV-Export bzw. tabellarische Dokumentationsdaten.';
        }

        $lines = preg_split('/\R/', $contents) ?: [];
        $paragraph = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($paragraph !== []) {
                    break;
                }
                continue;
            }

            if (
                str_starts_with($trimmed, '#')
                || str_starts_with($trimmed, '---')
                || str_starts_with($trimmed, '```')
                || str_starts_with($trimmed, '![')
                || preg_match('/^\|.+\|$/', $trimmed) === 1
            ) {
                continue;
            }

            $paragraph[] = $trimmed;
            if (count($paragraph) >= 3) {
                break;
            }
        }

        $text = $this->stripMarkdown(implode(' ', $paragraph));
        if ($text === '') {
            return 'Dokumentation aus dem Repository-Bereich /DOC.';
        }

        if (mb_strlen($text) > 180) {
            $text = rtrim(mb_substr($text, 0, 177)) . '…';
        }

        return $text;
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\([^\)]*\)/', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[*_`>#-]/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function resolveDefaultDocument(array $availablePaths): string
    {
        $preferred = [
            'README.md',
            'INDEX.md',
            'admin/README.md',
            'member/README.md',
            'theme/README.md',
        ];

        foreach ($preferred as $candidate) {
            if (in_array($candidate, $availablePaths, true)) {
                return $candidate;
            }
        }

        return $availablePaths[0] ?? '';
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<string, array<string, mixed>>
     */
    private function flattenDocuments(array $sections): array
    {
        $documents = [];

        foreach ($sections as $section) {
            foreach ((array) ($section['documents'] ?? []) as $document) {
                if (!is_array($document) || empty($document['relative_path'])) {
                    continue;
                }

                $documents[(string) $document['relative_path']] = $document;
            }
        }

        return $documents;
    }

    /**
     * @param array<string, array<string, mixed>> $documents
     * @return array<int, array<string, mixed>>
     */
    private function getFeaturedDocuments(array $documents): array
    {
        $paths = [
            'README.md',
            'INSTALLATION.md',
            'INDEX.md',
            'admin/README.md',
            'member/README.md',
            'theme/README.md',
            'core/README.md',
            'workflow/PLUGIN-REGISTRATION-WORKFLOW.MD',
        ];

        $featured = [];
        foreach ($paths as $path) {
            if (isset($documents[$path])) {
                $featured[] = $documents[$path];
            }
        }

        return $featured;
    }

    private function resolveSectionTitle(string $slug): string
    {
        return match ($slug) {
            'admin'       => 'Admin-Panel',
            'audits'      => 'Audits',
            'core'        => 'Core & Architektur',
            'feature'     => 'Feature-Dokumentation',
            'member'      => 'Mitglieder-Bereich',
            'plugins'     => 'Plugins',
            'screenshots' => 'Screenshots',
            'theme'       => 'Themes',
            'workflow'    => 'Workflows',
            default       => ucwords(str_replace(['-', '_'], ' ', $slug)),
        };
    }

    private function resolveSectionDescription(string $slug): string
    {
        return match ($slug) {
            'admin'       => 'Bedienung und Architektur des Admin-Panels inklusive Unterbereiche.',
            'audits'      => 'Prüfberichte, Analysen und Sicherheitsbewertungen.',
            'core'        => 'Grundlagen zu Bootstrap, Router, Auth, Datenbank und Systemarchitektur.',
            'feature'     => 'Fachliche Dokumentation einzelner Features und Funktionsbereiche.',
            'member'      => 'Doku für Dashboard, Profil, Medien, Nachrichten und Datenschutz im Member-Bereich.',
            'plugins'     => 'Entwicklungsleitfäden und Referenzen für Plugins und Integrationen.',
            'screenshots' => 'Bildmaterial und visuelle Dokumentationsartefakte.',
            'theme'       => 'Theme-System, Customizer, Komponenten und Frontend-Entwicklung.',
            'workflow'    => 'Abläufe, Registrierungsprozesse und technische Journeys.',
            default       => 'Dokumentationssammlung aus dem Repository-Bereich /DOC.',
        };
    }

    private function buildGithubBlobUrl(string $relativePath): string
    {
        return self::GITHUB_DOC_BASE . $this->encodePath($relativePath);
    }

    private function buildGithubTreeUrl(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');
        if ($relativePath === '') {
            return self::GITHUB_DOC_TREE;
        }

        return self::GITHUB_DOC_TREE . '/' . $this->encodePath($relativePath);
    }

    private function encodePath(string $path): string
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', $path)), static fn (string $segment): bool => $segment !== '');
        return implode('/', array_map('rawurlencode', $segments));
    }

    private function buildAdminUrl(string $relativePath): string
    {
        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        return $siteUrl . '/admin/documentation?doc=' . rawurlencode($relativePath);
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function renderMarkdown(string $markdown, string $currentDocument): string
    {
        $lines = preg_split('/\R/', str_replace(["\r\n", "\r"], "\n", $markdown)) ?: [];
        $html = '';
        $paragraph = [];
        $listType = null;
        $tableLines = [];
        $inCodeBlock = false;
        $codeLines = [];
        $codeLanguage = '';

        foreach ($lines as $line) {
            $trimmed = rtrim($line);
            $clean = trim($trimmed);

            if (preg_match('/^```\s*([^`]*)$/', $clean, $matches) === 1) {
                $html .= $this->flushTable($tableLines, $currentDocument);
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);

                if ($inCodeBlock) {
                    $html .= '<pre class="bg-dark-lt p-3 rounded overflow-auto"><code>'
                        . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES)
                        . '</code></pre>';
                    $inCodeBlock = false;
                    $codeLines = [];
                    $codeLanguage = '';
                } else {
                    $inCodeBlock = true;
                    $codeLanguage = trim((string) ($matches[1] ?? ''));
                }

                continue;
            }

            if ($inCodeBlock) {
                $codeLines[] = $trimmed;
                continue;
            }

            if ($clean === '') {
                $html .= $this->flushTable($tableLines, $currentDocument);
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                continue;
            }

            if (preg_match('/^\|.+\|$/', $clean) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                $tableLines[] = $clean;
                continue;
            }

            if ($tableLines !== []) {
                $html .= $this->flushTable($tableLines, $currentDocument);
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                $level = strlen($matches[1]);
                $html .= sprintf(
                    '<h%d class="mt-4 mb-3">%s</h%d>',
                    $level,
                    $this->renderInline(trim($matches[2]), $currentDocument),
                    $level
                );
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                if ($listType !== 'ul') {
                    $html .= $this->closeList($listType);
                    $html .= '<ul class="mb-3">';
                    $listType = 'ul';
                }
                $html .= '<li>' . $this->renderInline(trim($matches[1]), $currentDocument) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                if ($listType !== 'ol') {
                    $html .= $this->closeList($listType);
                    $html .= '<ol class="mb-3">';
                    $listType = 'ol';
                }
                $html .= '<li>' . $this->renderInline(trim($matches[1]), $currentDocument) . '</li>';
                continue;
            }

            $html .= $this->closeList($listType);
            $paragraph[] = $clean;
        }

        if ($inCodeBlock) {
            $html .= '<pre class="bg-dark-lt p-3 rounded overflow-auto"><code>'
                . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES)
                . '</code></pre>';
        }

        $html .= $this->flushTable($tableLines, $currentDocument);
        $html .= $this->flushParagraph($paragraph, $currentDocument);
        $html .= $this->closeList($listType);

        return $html;
    }

    /**
     * @param array<int, string> $paragraph
     */
    private function flushParagraph(array &$paragraph, string $currentDocument): string
    {
        if ($paragraph === []) {
            return '';
        }

        $text = implode(' ', $paragraph);
        $paragraph = [];

        if ($text === '---') {
            return '<hr class="my-4">';
        }

        return '<p>' . $this->renderInline($text, $currentDocument) . '</p>';
    }

    private function closeList(?string &$listType): string
    {
        if ($listType === null) {
            return '';
        }

        $tag = $listType;
        $listType = null;
        return '</' . $tag . '>';
    }

    /**
     * @param array<int, string> $tableLines
     */
    private function flushTable(array &$tableLines, string $currentDocument): string
    {
        if ($tableLines === []) {
            return '';
        }

        $rows = [];
        foreach ($tableLines as $line) {
            $trimmed = trim($line);
            $trimmed = trim($trimmed, '|');
            $cells = array_map('trim', explode('|', $trimmed));
            $rows[] = $cells;
        }

        $tableLines = [];
        if ($rows === []) {
            return '';
        }

        $header = $rows[0];
        $bodyRows = array_slice($rows, 1);

        if ($bodyRows !== [] && $this->isTableSeparator($bodyRows[0])) {
            $bodyRows = array_slice($bodyRows, 1);
        } else {
            $bodyRows = array_slice($rows, 1);
        }

        $html = '<div class="table-responsive mb-4"><table class="table table-bordered table-striped table-sm">';
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . $this->renderInline($cell, $currentDocument) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($bodyRows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $this->renderInline($cell, $currentDocument) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * @param array<int, string> $row
     */
    private function isTableSeparator(array $row): bool
    {
        foreach ($row as $cell) {
            if (preg_match('/^:?-{3,}:?$/', trim($cell)) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function renderInline(string $text, string $currentDocument): string
    {
        $rendered = htmlspecialchars($text, ENT_QUOTES);

        $rendered = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function (array $matches) use ($currentDocument): string {
            $label = $matches[1];
            $target = html_entity_decode($matches[2], ENT_QUOTES);
            $href = $this->resolveLink($currentDocument, $target);
            $isExternal = $this->isExternalUrl($href);

            return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"'
                . ($isExternal ? ' target="_blank" rel="noopener noreferrer"' : '')
                . '>' . $label . '</a>';
        }, $rendered) ?? $rendered;

        $rendered = preg_replace_callback('/`([^`]+)`/', static function (array $matches): string {
            return '<code>' . $matches[1] . '</code>';
        }, $rendered) ?? $rendered;

        $rendered = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $rendered) ?? $rendered;
        $rendered = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $rendered) ?? $rendered;

        return $rendered;
    }

    private function resolveLink(string $currentDocument, string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '#';
        }

        if ($target[0] === '#') {
            return $target;
        }

        if ($this->isExternalUrl($target) || str_starts_with($target, '/')) {
            return $target;
        }

        $baseDir = trim(str_replace('\\', '/', dirname($currentDocument)), '.');
        $combined = $baseDir === '' ? $target : $baseDir . '/' . $target;
        $normalized = $this->normalizeRelativePath($combined);

        if ($normalized === '') {
            return '#';
        }

        $extension = strtolower((string) pathinfo($normalized, PATHINFO_EXTENSION));
        if (in_array($extension, ['md', 'csv'], true)) {
            return $this->buildAdminUrl($normalized);
        }

        return $this->buildGithubTreeUrl($normalized);
    }

    private function isExternalUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) === 1;
    }

    private function renderCsv(string $contents): string
    {
        $lines = preg_split('/\R/', trim($contents)) ?: [];
        if ($lines === []) {
            return '<div class="text-secondary">Keine CSV-Inhalte vorhanden.</div>';
        }

        $rows = array_map(static fn (string $line): array => str_getcsv($line), $lines);
        $header = array_shift($rows) ?: [];

        $html = '<div class="table-responsive"><table class="table table-bordered table-striped table-sm">';
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . htmlspecialchars((string) $cell, ENT_QUOTES) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }
}
