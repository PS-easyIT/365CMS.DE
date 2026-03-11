<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncEnvironment
{
    public function __construct(private readonly string $repoRoot)
    {
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
     * @return array{output: string, exitCode: int}
     */
    public function runCommand(string $command): array
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

    private function isGitAvailable(): bool
    {
        $result = $this->runCommand('git --version 2>&1');
        return ($result['exitCode'] ?? 1) === 0 && str_contains(strtolower((string) ($result['output'] ?? '')), 'git version');
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
}