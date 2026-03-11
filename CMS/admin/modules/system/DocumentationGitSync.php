<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationGitSync
{
    public function __construct(
        private readonly string $repoRoot,
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
        $fetch = $this->environment->runCommand(sprintf(
            'git -C %s fetch %s %s 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($fetch['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Fetch): ' . trim((string) ($fetch['output'] ?? 'Unbekannter Fehler'))];
        }

        $checkout = $this->environment->runCommand(sprintf(
            'git -C %s checkout %s/%s -- DOC 2>&1',
            escapeshellarg($this->repoRoot),
            escapeshellarg($this->defaultRemote),
            escapeshellarg($this->defaultBranch)
        ));

        if (($checkout['exitCode'] ?? 1) !== 0) {
            return ['success' => false, 'error' => 'DOC-Sync fehlgeschlagen (Checkout): ' . trim((string) ($checkout['output'] ?? 'Unbekannter Fehler'))];
        }

        $status = $this->environment->runCommand(sprintf(
            'git -C %s status --short -- DOC 2>&1',
            escapeshellarg($this->repoRoot)
        ));

        $statusOutput = trim((string) ($status['output'] ?? ''));
        $message = 'Der lokale Ordner /DOC wurde mit ' . $this->defaultRemote . '/' . $this->defaultBranch . ' synchronisiert.';

        if ($statusOutput !== '') {
            $message .= ' Geänderte Dateien: ' . $statusOutput;
        } elseif (is_dir($this->repoRoot . DIRECTORY_SEPARATOR . 'DOC')) {
            $message .= ' Keine weiteren Unterschiede im Arbeitsbaum für /DOC.';
        }

        return ['success' => true, 'message' => $message];
    }
}