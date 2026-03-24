<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationGitSync
{
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
        } catch (RuntimeException $e) {
            return $this->failResult('documentation.sync.git.invalid_target', 'DOC-Sync via Git konnte nicht vorbereitet werden.', $e, [
                'repo_root' => $this->repoRoot,
                'docs_root' => $this->docsRoot,
            ]);
        }

        $fetch = $this->environment->runCommand(sprintf(
            'git -C %s fetch %s %s 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($fetch['exitCode'] ?? 1) !== 0) {
            return $this->failResult('documentation.sync.git.fetch_failed', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', null, [
                'command' => 'fetch',
                'output' => $this->limitCommandOutput((string) ($fetch['output'] ?? '')),
                'exit_code' => (int) ($fetch['exitCode'] ?? 1),
            ]);
        }

        $checkout = $this->environment->runCommand(sprintf(
            'git -C %s checkout %s/%s -- DOC 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($checkout['exitCode'] ?? 1) !== 0) {
            return $this->failResult('documentation.sync.git.checkout_failed', 'DOC-Sync via Git konnte nicht abgeschlossen werden.', null, [
                'command' => 'checkout',
                'output' => $this->limitCommandOutput((string) ($checkout['output'] ?? '')),
                'exit_code' => (int) ($checkout['exitCode'] ?? 1),
            ]);
        }

        $status = $this->environment->runCommand(sprintf(
            'git -C %s status --short -- DOC 2>&1',
            escapeshellarg($this->repoRoot)
        ));

        $statusOutput = $this->normalizeStatusOutput((string) ($status['output'] ?? ''));
        $message = 'Der lokale Ordner /DOC wurde mit ' . $this->defaultRemote . '/' . $this->defaultBranch . ' synchronisiert.';

        if ($statusOutput !== '') {
            $message .= ' Geänderte Dateien: ' . $statusOutput;
        } elseif (is_dir($this->repoRoot . DIRECTORY_SEPARATOR . 'DOC')) {
            $message .= ' Keine weiteren Unterschiede im Arbeitsbaum für /DOC.';
        }

        return ['success' => true, 'message' => $message];
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

    /** @param array<string, mixed> $context */
    private function failResult(string $action, string $message, ?Throwable $exception = null, array $context = []): array
    {
        if ($exception !== null) {
            $context['exception'] = $exception->getMessage();
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