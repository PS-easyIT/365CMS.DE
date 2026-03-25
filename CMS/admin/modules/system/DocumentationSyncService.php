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
use CMS\Auth;
use CMS\Logger;

final class DocumentationSyncServiceResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?string $message = null,
        private readonly ?string $error = null
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function toArray(): array
    {
        $payload = ['success' => $this->success];

        if ($this->message !== null && $this->message !== '') {
            $payload['message'] = $this->message;
        }

        if ($this->error !== null && $this->error !== '') {
            $payload['error'] = $this->error;
        }

        return $payload;
    }

    /**
     * @param array{success: bool, message?: string, error?: string} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            ($result['success'] ?? false) === true,
            isset($result['message']) ? (string) $result['message'] : null,
            isset($result['error']) ? (string) $result['error'] : null
        );
    }
}

final class DocumentationSyncService
{
    private const ALLOWED_ZIP_HOSTS = [
        'codeload.github.com',
        'github.com',
        'raw.githubusercontent.com',
    ];
    private const MAX_LOG_VALUE_LENGTH = 240;

    /** @var resource|null */
    private $syncLockHandle = null;

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
        $filesystem = new DocumentationSyncFilesystem($this->repoRoot, $this->docsRoot, sys_get_temp_dir());
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

    public function syncDocsFromRepository(): DocumentationSyncServiceResult
    {
        if (!$this->canAccess()) {
            return $this->failResult(
                'documentation.sync.access_denied',
                'Doku-Sync darf nur von Administratoren ausgeführt werden.'
            );
        }

        $layoutCheck = $this->assertSyncConfiguration(true);
        if ($layoutCheck !== null) {
            return $layoutCheck;
        }

        try {
            $this->acquireSyncLock();
        } catch (RuntimeException $e) {
            return $this->failResult(
                'documentation.sync.lock_failed',
                'Doku-Sync konnte nicht gestartet werden.',
                ['reason' => $this->sanitizeLogString($e->getMessage(), self::MAX_LOG_VALUE_LENGTH)]
            );
        }

        try {
            $normalizedCapabilities = $this->getSyncCapabilities();

            return $this->resolveSyncExecutionResult($normalizedCapabilities);

        } finally {
            $this->releaseSyncLock();
        }
    }

    public function getSyncCapabilities(): DocumentationSyncCapabilities
    {
        $configurationFailure = $this->getSyncConfigurationFailure();
        if ($configurationFailure !== null) {
            return $this->buildUnavailableCapabilities($configurationFailure['message']);
        }

        return $this->normalizeCapabilities($this->environment->getSyncCapabilities());
    }

    private function assertSyncConfiguration(bool $logFailure = true): ?DocumentationSyncServiceResult
    {
        $failure = $this->getSyncConfigurationFailure();
        if ($failure === null) {
            return null;
        }

        if ($logFailure === false) {
            return $this->createFailureServiceResult($failure['message'] . ' Bitte Logs prüfen.');
        }

        return $this->failResult($failure['action'], $failure['message'], $failure['context']);
    }

    /**
     * @return array{action: string, message: string, context: array<string, mixed>}|null
     */
    private function getSyncConfigurationFailure(): ?array
    {
        $resolvedRepoRoot = realpath($this->repoRoot);
        if ($resolvedRepoRoot === false || !is_dir($resolvedRepoRoot) || is_link($this->repoRoot)) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_repo_root',
                'Repository-Root für den Doku-Sync ist ungültig.',
                [
                    'repo_root' => $this->repoRoot,
                ]
            );
        }

        if (!is_dir($resolvedRepoRoot . DIRECTORY_SEPARATOR . 'CMS')) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_repo_layout',
                'Repository-Layout für den Doku-Sync ist ungültig.',
                [
                    'repo_root' => $resolvedRepoRoot,
                ]
            );
        }

        $expectedDocsRoot = rtrim($resolvedRepoRoot, '\\/') . DIRECTORY_SEPARATOR . 'DOC';
        if (rtrim($this->docsRoot, '\/') !== $expectedDocsRoot || is_link($this->docsRoot)) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_docs_root',
                'Doku-Sync darf nur den lokalen /DOC-Ordner im Repository-Root verwalten.',
                [
                    'docs_root' => $this->docsRoot,
                    'expected_docs_root' => $expectedDocsRoot,
                ]
            );
        }

        if (file_exists($this->docsRoot) && !is_dir($this->docsRoot)) {
            return $this->createConfigurationFailure(
                'documentation.sync.docs_root_not_directory',
                'Der lokale /DOC-Pfad ist ungültig konfiguriert.',
                [
                    'docs_root' => $this->docsRoot,
                ]
            );
        }

        if (!is_dir(dirname($this->docsRoot)) || !is_writable(dirname($this->docsRoot))) {
            return $this->createConfigurationFailure(
                'documentation.sync.docs_parent_not_writable',
                'Der lokale /DOC-Zielpfad ist nicht beschreibbar.',
                [
                    'docs_root' => $this->docsRoot,
                    'docs_parent' => dirname($this->docsRoot),
                ]
            );
        }

        if (!$this->isValidGitRefPart($this->defaultRemote) || !$this->isValidGitRefPart($this->defaultBranch)) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_git_ref',
                'Remote oder Branch für den Doku-Sync sind ungültig konfiguriert.',
                [
                    'remote' => $this->defaultRemote,
                    'branch' => $this->defaultBranch,
                ]
            );
        }

        if (!$this->isValidGithubZipUrl($this->githubZipUrl)) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_zip_url',
                'Die GitHub-ZIP-Quelle für den Doku-Sync ist ungültig konfiguriert.',
                [
                    'zip_url' => $this->githubZipUrl,
                ]
            );
        }

        if (!$this->isValidApprovedBundleConfiguration()) {
            return $this->createConfigurationFailure(
                'documentation.sync.invalid_integrity_profile',
                'Das Integritätsprofil für den Doku-Sync ist ungültig konfiguriert.',
                [
                    'approved_hash' => $this->approvedDocsBundleHash,
                    'approved_file_count' => $this->approvedDocsBundleFileCount,
                ]
            );
        }

        return null;
    }

    private function normalizeCapabilities(DocumentationSyncCapabilities $capabilities): DocumentationSyncCapabilities
    {
        $mode = $capabilities->mode();
        if (!in_array($mode, ['git', 'github-zip', 'none'], true)) {
            $mode = 'none';
        }

        return new DocumentationSyncCapabilities(
            $capabilities->canSync(),
            $capabilities->hasGit(),
            $capabilities->hasGithubZip(),
            $mode,
            $this->sanitizeLogString($capabilities->label(), 120),
            $this->sanitizeLogString($capabilities->message(), 240)
        );
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

    private function canAccess(): bool
    {
        return class_exists(Auth::class) && Auth::instance()->isAdmin();
    }

    private function buildUnavailableCapabilities(string $message): DocumentationSyncCapabilities
    {
        return new DocumentationSyncCapabilities(
            false,
            false,
            false,
            'none',
            'Nicht verfügbar',
            $this->sanitizeLogString($message, self::MAX_LOG_VALUE_LENGTH)
        );
    }

    private function resolveSyncExecutionResult(DocumentationSyncCapabilities $capabilities): DocumentationSyncServiceResult
    {
        if (!$capabilities->canSync()) {
            return $this->failResult(
                'documentation.sync.unavailable',
                'Doku-Sync ist auf diesem Server nicht verfügbar.',
                [
                    'capabilities' => $capabilities->toLogContext(),
                ]
            );
        }

        if ($capabilities->hasGit()) {
            return $this->finalizeSyncResult($this->gitSync->sync(), 'git', $capabilities);
        }

        if ($capabilities->hasGithubZip() && $capabilities->mode() === 'github-zip') {
            return $this->finalizeSyncResult($this->githubZipSync->sync(), 'github-zip', $capabilities);
        }

        return $this->failResult(
            'documentation.sync.invalid_capabilities',
            'Doku-Sync ist auf diesem Server nicht konsistent konfiguriert.',
            [
                'capabilities' => $capabilities->toLogContext(),
            ]
        );
    }

    private function acquireSyncLock(): void
    {
        $lockPath = $this->buildLockPath();
        $handle = @fopen($lockPath, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Synchronisations-Lock konnte nicht initialisiert werden.');
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Es läuft bereits eine Dokumentations-Synchronisation.');
        }

        $this->syncLockHandle = $handle;
    }

    private function releaseSyncLock(): void
    {
        if (!is_resource($this->syncLockHandle)) {
            $this->syncLockHandle = null;

            return;
        }

        @flock($this->syncLockHandle, LOCK_UN);
        @fclose($this->syncLockHandle);
        $this->syncLockHandle = null;
    }

    private function buildLockPath(): string
    {
        $tempRoot = sys_get_temp_dir();
        $repoHash = hash('sha256', $this->repoRoot);

        return rtrim($tempRoot, '\\/') . DIRECTORY_SEPARATOR . '365cms_doc_sync_' . $repoHash . '.lock';
    }

    /**
     * @param array{success: bool, message?: string, error?: string} $result
     */
    private function finalizeSyncResult(array $result, string $mode, DocumentationSyncCapabilities $capabilities): DocumentationSyncServiceResult
    {
        $serviceResult = $this->serviceResultFromArray($result);

        if ($serviceResult->isSuccess()) {
            $this->logSuccess('documentation.sync.completed', 'Doku-Sync erfolgreich abgeschlossen.', [
                'mode' => $mode,
                'capabilities' => $capabilities->toLogContext(),
            ]);

            return $serviceResult;
        }

        $this->logFailure('documentation.sync.failed', 'Doku-Sync fehlgeschlagen.', [
            'mode' => $mode,
            'capabilities' => $capabilities->toLogContext(),
            'result_error' => $this->sanitizeLogString((string) ($result['error'] ?? ''), 240),
        ]);

        return $serviceResult;
    }

    /** @param array<string, mixed> $context */
    private function failResult(string $action, string $message, array $context = []): DocumentationSyncServiceResult
    {
        $this->logFailure($action, $message, $context);

        return $this->createFailureServiceResult($message . ' Bitte Logs prüfen.');
    }

    /**
     * @param array{success: bool, message?: string, error?: string} $result
     */
    private function serviceResultFromArray(array $result): DocumentationSyncServiceResult
    {
        return DocumentationSyncServiceResult::fromArray($result);
    }

    private function createFailureServiceResult(string $message): DocumentationSyncServiceResult
    {
        return $this->serviceResultFromArray([
            'success' => false,
            'error' => $message,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{action: string, message: string, context: array<string, mixed>}
     */
    private function createConfigurationFailure(string $action, string $message, array $context): array
    {
        return [
            'action' => $action,
            'message' => $message,
            'context' => $context,
        ];
    }

    /** @param array<string, mixed> $context */
    private function logFailure(string $action, string $message, array $context = []): void
    {
        $this->writeDocumentationLog('warning', $action, $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function logSuccess(string $action, string $message, array $context = []): void
    {
        $this->writeDocumentationLog('info', $action, $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function writeDocumentationLog(string $level, string $action, string $message, array $context = []): void
    {
        $logger = Logger::instance()->withChannel('admin.documentation');

        if ($level === 'warning') {
            $logger->warning($message, $context);
        } else {
            $logger->info($message, $context);
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $action,
            $message,
            'documentation',
            null,
            $context,
            $level
        );
    }

    private function sanitizeLogString(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }
}
