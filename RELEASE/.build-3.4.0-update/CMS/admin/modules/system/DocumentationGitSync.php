<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationGitSync
{
    /** @var resource|null */
    private $syncLockHandle = null;

    public function __construct(
        private readonly string $repoRoot,
        private readonly string $docsRoot,
        private readonly string $defaultRemote,
        private readonly string $defaultBranch,
        private readonly DocumentationSyncEnvironment $environment
    ) {
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function sync(): array
    {
        try {
            $this->assertSyncTargets();
            $this->acquireSyncLock();
        } catch (RuntimeException $e) {
            return $this->failResult('documentation.sync.git.invalid_target', 'DOC-Sync via Git konnte nicht vorbereitet werden.', $e, [
                'repo_root' => $this->sanitizePath($this->repoRoot),
                'docs_root' => $this->sanitizePath($this->docsRoot),
            ]);
        }

        try {
            $fetch = $this->environment->runCommand(sprintf(
                'git -C %s fetch --no-tags --prune --no-recurse-submodules %s %s 2>&1',
                escapeshellarg($this->repoRoot),
                escapeshellarg($this->defaultRemote),
                escapeshellarg($this->defaultBranch)
            ));

            if (!$fetch->isSuccess()) {
                return $this->failResult('documentation.sync.git.fetch_failed', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', null, [
                    'command' => 'fetch',
                    'output' => $this->limitCommandOutput($fetch->output()),
                    'exit_code' => $fetch->exitCode(),
                ]);
            }

            if (!$this->remoteReferenceExists()) {
                return $this->failResult('documentation.sync.git.missing_ref', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', null, [
                    'command' => 'rev-parse',
                    'remote_ref' => $this->sanitizeRef($this->defaultRemote . '/' . $this->defaultBranch),
                ]);
            }

            $localDocChanges = $this->getLocalDocStatus();
            if ($localDocChanges !== '') {
                return $this->failResult('documentation.sync.git.local_changes', 'DOC-Sync via Git wurde abgebrochen, weil lokale Änderungen im /DOC-Ordner vorliegen.', null, [
                    'command' => 'status',
                    'changes' => $localDocChanges,
                ]);
            }

            $remoteRef = $this->defaultRemote . '/' . $this->defaultBranch;

            $checkout = $this->environment->runCommand(sprintf(
                'git -C %s checkout %s -- DOC 2>&1',
                escapeshellarg($this->repoRoot),
                escapeshellarg($remoteRef)
            ));

            if (!$checkout->isSuccess()) {
                return $this->failResult('documentation.sync.git.checkout_failed', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', null, [
                    'command' => 'checkout',
                    'output' => $this->limitCommandOutput($checkout->output()),
                    'exit_code' => $checkout->exitCode(),
                ]);
            }

            $status = $this->environment->runCommand(sprintf(
                'git -C %s status --short -- DOC 2>&1',
                escapeshellarg($this->repoRoot)
            ));

            if (!$status->isSuccess()) {
                return $this->failResult('documentation.sync.git.status_failed', 'DOC-Sync via Git konnte nicht vollständig verifiziert werden.', null, [
                    'command' => 'status',
                    'output' => $this->limitCommandOutput($status->output()),
                    'exit_code' => $status->exitCode(),
                ]);
            }

            $statusOutput = $this->normalizeStatusOutput($status->output());
            $message = 'Der lokale Ordner /DOC wurde mit ' . $this->defaultRemote . '/' . $this->defaultBranch . ' synchronisiert.';

            if ($statusOutput !== '') {
                $message .= ' Geänderte Dateien: ' . $statusOutput;
            } elseif (is_dir($this->repoRoot . DIRECTORY_SEPARATOR . 'DOC')) {
                $message .= ' Keine weiteren Unterschiede im Arbeitsbaum für /DOC.';
            }

            return ['success' => true, 'message' => $message];
        } catch (RuntimeException $e) {
            return $this->failResult('documentation.sync.git.runtime_failed', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', $e, [
                'repo_root' => $this->sanitizePath($this->repoRoot),
                'docs_root' => $this->sanitizePath($this->docsRoot),
            ]);
        } finally {
            $this->releaseSyncLock();
        }
    }

    private function assertSyncTargets(): void
    {
        $resolvedRepoRoot = realpath($this->repoRoot);
        if ($resolvedRepoRoot === false || !is_dir($resolvedRepoRoot)) {
            throw new RuntimeException('Repository-Root konnte nicht aufgelöst werden.');
        }

        $expectedDocsRoot = rtrim($resolvedRepoRoot, '\\/') . DIRECTORY_SEPARATOR . 'DOC';
        if (rtrim($this->docsRoot, '\\/') !== $expectedDocsRoot) {
            throw new RuntimeException('Git-Doku-Sync darf nur den /DOC-Ordner im Repository-Root verarbeiten.');
        }

        if (!is_dir($resolvedRepoRoot . DIRECTORY_SEPARATOR . '.git')) {
            throw new RuntimeException('Lokaler Git-Checkout wurde nicht gefunden.');
        }

        if (is_link($this->docsRoot)) {
            throw new RuntimeException('Der lokale /DOC-Ordner darf kein symbolischer Link sein.');
        }

        if (!$this->isValidGitRefPart($this->defaultRemote) || !$this->isValidGitRefPart($this->defaultBranch)) {
            throw new RuntimeException('Remote oder Branch für den Doku-Sync sind ungültig konfiguriert.');
        }
    }

    private function isValidGitRefPart(string $value): bool
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9._\/-]+$/', $value) === 1;
    }

    private function remoteReferenceExists(): bool
    {
        $remoteRef = $this->defaultRemote . '/' . $this->defaultBranch;
        $result = $this->environment->runCommand(sprintf(
            'git -C %s rev-parse --verify --quiet %s^{commit} 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($remoteRef)
        ));

        return $result->isSuccess();
    }

    private function getLocalDocStatus(): string
    {
        $status = $this->environment->runCommand(sprintf(
            'git -C %s status --short --untracked-files=all -- DOC 2>&1',
            escapeshellarg($this->repoRoot)
        ));

        if (!$status->isSuccess()) {
            throw new RuntimeException('Lokaler DOC-Status konnte nicht geprüft werden.');
        }

        return $this->normalizeStatusOutput($status->output());
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
            throw new RuntimeException('Es läuft bereits eine Git-Dokumentationssynchronisation.');
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

        return rtrim($tempRoot, '\\/') . DIRECTORY_SEPARATOR . '365cms_doc_git_sync_' . $repoHash . '.lock';
    }

    private function normalizeStatusOutput(string $output): string
    {
        $output = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($output)) ?? '';
        if ($output === '') {
            return '';
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $output) ?: [])));
        $lines = array_slice($lines, 0, 8);

        return implode('; ', $lines);
    }

    private function limitCommandOutput(string $output): string
    {
        $output = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', trim($output)) ?? '';

        return function_exists('mb_substr') ? mb_substr($output, 0, 1000) : substr($output, 0, 1000);
    }

    private function sanitizePath(string $path): string
    {
        return $this->limitCommandOutput(str_replace('\\', '/', $path));
    }

    private function sanitizeRef(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._\/-]+/', '', $value) ?? '';
    }

    /** @param array<string, mixed> $context */
    private function failResult(string $action, string $message, ?Throwable $exception = null, array $context = []): array
    {
        if ($exception !== null) {
            $context['exception'] = $this->limitCommandOutput($exception->getMessage());
        }

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