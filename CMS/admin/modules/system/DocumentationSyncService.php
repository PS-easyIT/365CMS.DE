<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/DocumentationSyncEnvironment.php';
require_once __DIR__ . '/DocumentationSyncFilesystem.php';
require_once __DIR__ . '/DocumentationSyncDownloader.php';
require_once __DIR__ . '/DocumentationGitSync.php';
require_once __DIR__ . '/DocumentationGithubZipSync.php';

use CMS\AuditLogger;
use CMS\Logger;

final class DocumentationSyncService
{
    private const ALLOWED_ZIP_HOSTS = [
        'codeload.github.com',
        'github.com',
        'raw.githubusercontent.com',
    ];

    private readonly DocumentationSyncEnvironment $environment;
    private readonly DocumentationGitSync $gitSync;
    private readonly DocumentationGithubZipSync $githubZipSync;

    public function __construct(
        private readonly string $repoRoot,
        private readonly string $docsRoot,
        private readonly string $githubZipUrl,
        private readonly string $approvedDocsBundleHash,
        private readonly int $approvedDocsBundleFileCount,
        private readonly string $defaultRemote,
        private readonly string $defaultBranch
    ) {
        $filesystem = new DocumentationSyncFilesystem();
        $downloader = new DocumentationSyncDownloader($filesystem);

        $this->environment = new DocumentationSyncEnvironment($this->repoRoot);
        $this->gitSync = new DocumentationGitSync(
            $this->repoRoot,
            $this->docsRoot,
            $this->defaultRemote,
            $this->defaultBranch,
            $this->environment
        );
        $this->githubZipSync = new DocumentationGithubZipSync(
            $this->repoRoot,
            $this->docsRoot,
            $this->githubZipUrl,
            $this->approvedDocsBundleHash,
            $this->approvedDocsBundleFileCount,
            $downloader,
            $filesystem
        );
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function syncDocsFromRepository(): array
    {
        $layoutCheck = $this->assertSyncConfiguration();
        if ($layoutCheck !== null) {
            return $layoutCheck;
        }

        $capabilities = $this->environment->getSyncCapabilities();
        $normalizedCapabilities = $this->normalizeCapabilities($capabilities);

        if (($normalizedCapabilities['can_sync'] ?? false) !== true) {
            return $this->failResult(
                'documentation.sync.unavailable',
                'Doku-Sync ist auf diesem Server nicht verfügbar.',
                [
                    'capabilities' => $normalizedCapabilities,
                ]
            );
        }

        $syncMode = (string) ($normalizedCapabilities['mode'] ?? 'none');
        if (($normalizedCapabilities['git'] ?? false) === true) {
            return $this->finalizeSyncResult($this->gitSync->sync(), 'git', $normalizedCapabilities);
        }

        if (($normalizedCapabilities['github_zip'] ?? false) === true && $syncMode === 'github-zip') {
            return $this->finalizeSyncResult($this->githubZipSync->sync(), 'github-zip', $normalizedCapabilities);
        }

        return $this->failResult(
            'documentation.sync.invalid_capabilities',
            'Doku-Sync ist auf diesem Server nicht konsistent konfiguriert.',
            [
                'capabilities' => $normalizedCapabilities,
            ]
        );
    }

    /**
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    public function getSyncCapabilities(): array
    {
        return $this->normalizeCapabilities($this->environment->getSyncCapabilities());
    }

    /** @return array{success: false, error: string}|null */
    private function assertSyncConfiguration(): ?array
    {
        $resolvedRepoRoot = realpath($this->repoRoot);
        if ($resolvedRepoRoot === false || !is_dir($resolvedRepoRoot) || is_link($this->repoRoot)) {
            return $this->failResult('documentation.sync.invalid_repo_root', 'Repository-Root für den Doku-Sync ist ungültig.', [
                'repo_root' => $this->repoRoot,
            ]);
        }

        if (!is_dir($resolvedRepoRoot . DIRECTORY_SEPARATOR . 'CMS')) {
            return $this->failResult('documentation.sync.invalid_repo_layout', 'Repository-Layout für den Doku-Sync ist ungültig.', [
                'repo_root' => $resolvedRepoRoot,
            ]);
        }

        $expectedDocsRoot = rtrim($resolvedRepoRoot, '\\/') . DIRECTORY_SEPARATOR . 'DOC';
        if (rtrim($this->docsRoot, '\/') !== $expectedDocsRoot || is_link($this->docsRoot)) {
            return $this->failResult('documentation.sync.invalid_docs_root', 'Doku-Sync darf nur den lokalen /DOC-Ordner im Repository-Root verwalten.', [
                'docs_root' => $this->docsRoot,
                'expected_docs_root' => $expectedDocsRoot,
            ]);
        }

        if (file_exists($this->docsRoot) && !is_dir($this->docsRoot)) {
            return $this->failResult('documentation.sync.docs_root_not_directory', 'Der lokale /DOC-Pfad ist ungültig konfiguriert.', [
                'docs_root' => $this->docsRoot,
            ]);
        }

        if (!is_dir(dirname($this->docsRoot)) || !is_writable(dirname($this->docsRoot))) {
            return $this->failResult('documentation.sync.docs_parent_not_writable', 'Der lokale /DOC-Zielpfad ist nicht beschreibbar.', [
                'docs_root' => $this->docsRoot,
                'docs_parent' => dirname($this->docsRoot),
            ]);
        }

        if (!$this->isValidGitRefPart($this->defaultRemote) || !$this->isValidGitRefPart($this->defaultBranch)) {
            return $this->failResult('documentation.sync.invalid_git_ref', 'Remote oder Branch für den Doku-Sync sind ungültig konfiguriert.', [
                'remote' => $this->defaultRemote,
                'branch' => $this->defaultBranch,
            ]);
        }

        if (!$this->isValidGithubZipUrl($this->githubZipUrl)) {
            return $this->failResult('documentation.sync.invalid_zip_url', 'Die GitHub-ZIP-Quelle für den Doku-Sync ist ungültig konfiguriert.', [
                'zip_url' => $this->githubZipUrl,
            ]);
        }

        if (!$this->isValidApprovedBundleConfiguration()) {
            return $this->failResult('documentation.sync.invalid_integrity_profile', 'Das Integritätsprofil für den Doku-Sync ist ungültig konfiguriert.', [
                'approved_hash' => $this->approvedDocsBundleHash,
                'approved_file_count' => $this->approvedDocsBundleFileCount,
            ]);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $capabilities
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    private function normalizeCapabilities(array $capabilities): array
    {
        $mode = (string) ($capabilities['mode'] ?? 'none');
        if (!in_array($mode, ['git', 'github-zip', 'none'], true)) {
            $mode = 'none';
        }

        return [
            'can_sync' => ($capabilities['can_sync'] ?? false) === true,
            'git' => ($capabilities['git'] ?? false) === true,
            'github_zip' => ($capabilities['github_zip'] ?? false) === true,
            'mode' => $mode,
            'label' => $this->sanitizeLogString((string) ($capabilities['label'] ?? 'Nicht verfügbar'), 120),
            'message' => $this->sanitizeLogString((string) ($capabilities['message'] ?? 'Doku-Sync ist auf diesem Server nicht verfügbar.'), 240),
        ];
    }

    private function isValidGitRefPart(string $value): bool
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9._\/-]+$/', $value) === 1;
    }

    private function isValidGithubZipUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || !in_array($host, self::ALLOWED_ZIP_HOSTS, true)) {
            return false;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
            return false;
        }

        return str_contains($path, '/zip/') || str_contains($path, '/zipball/');
    }

    private function isValidApprovedBundleConfiguration(): bool
    {
        return preg_match('/^[0-9a-f]{64}$/', strtolower($this->approvedDocsBundleHash)) === 1
            && $this->approvedDocsBundleFileCount > 0;
    }

    /**
     * @param array{success: bool, message?: string, error?: string} $result
     * @param array<string, mixed> $capabilities
     * @return array{success: bool, message?: string, error?: string}
     */
    private function finalizeSyncResult(array $result, string $mode, array $capabilities): array
    {
        if (($result['success'] ?? false) === true) {
            $this->logSuccess('documentation.sync.completed', 'Doku-Sync erfolgreich abgeschlossen.', [
                'mode' => $mode,
                'capabilities' => $capabilities,
            ]);

            return $result;
        }

        $this->logFailure('documentation.sync.failed', 'Doku-Sync fehlgeschlagen.', [
            'mode' => $mode,
            'capabilities' => $capabilities,
            'result_error' => $this->sanitizeLogString((string) ($result['error'] ?? ''), 240),
        ]);

        return $result;
    }

    /** @param array<string, mixed> $context */
    private function failResult(string $action, string $message, array $context = []): array
    {
        $this->logFailure($action, $message, $context);

        return ['success' => false, 'error' => $message . ' Bitte Logs prüfen.'];
    }

    /** @param array<string, mixed> $context */
    private function logFailure(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('admin.documentation')->warning($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            'warning'
        );
    }

    /** @param array<string, mixed> $context */
    private function logSuccess(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('admin.documentation')->info($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            'info'
        );
    }

    private function sanitizeLogString(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }
}
