<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncEnvironment
{
    private const int MAX_COMMAND_LENGTH = 512;
    private const array ALLOWED_GIT_SUBCOMMANDS = ['--version', 'fetch', 'checkout', 'status', 'rev-parse'];

    private readonly string $normalizedRepoRoot;

    public function __construct(private readonly string $repoRoot)
    {
        $this->normalizedRepoRoot = $this->normalizeRepoRoot($repoRoot);
    }

    /**
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    public function getSyncCapabilities(): array
    {
        $hasGitCheckout = $this->normalizedRepoRoot !== '' && is_dir($this->normalizedRepoRoot . DIRECTORY_SEPARATOR . '.git');
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

        $command = $this->sanitizeCommand($command);
        if ($command === '' || !$this->isAllowedCommand($command)) {
            return ['output' => 'Befehlsausführung ist für dieses Kommando nicht erlaubt.', 'exitCode' => 1];
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

    private function normalizeRepoRoot(string $repoRoot): string
    {
        $repoRoot = trim($repoRoot);
        if ($repoRoot === '' || is_link($repoRoot)) {
            return '';
        }

        $resolved = realpath($repoRoot);
        if ($resolved === false || !is_dir($resolved)) {
            return '';
        }

        return rtrim($resolved, '\\/');
    }

    private function sanitizeCommand(string $command): string
    {
        $command = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($command)) ?? '';
        $command = preg_replace('/\s+/', ' ', $command) ?? '';

        if ($command === '') {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($command, 0, self::MAX_COMMAND_LENGTH) : substr($command, 0, self::MAX_COMMAND_LENGTH);
    }

    private function isAllowedCommand(string $command): bool
    {
        if (!preg_match('/^git(?:\s+-C\s+[^\s]+)?\s+(.+)$/', $command, $matches)) {
            return false;
        }

        if (preg_match('/(?:^|\s)(?:;|&&|\|\||\||`|\$\(|>|<)/', $command) === 1) {
            return str_ends_with($command, '2>&1') && preg_match('/(?:^|\s)(?:;|&&|\|\||\||`|\$\(|(?<!2)>|<)/', $command) !== 1;
        }

        $subcommand = strtolower(strtok((string)($matches[1] ?? ''), ' '));
        if ($subcommand === '' || !in_array($subcommand, self::ALLOWED_GIT_SUBCOMMANDS, true)) {
            return false;
        }

        if ($subcommand !== '--version' && $this->normalizedRepoRoot === '') {
            return false;
        }

        return true;
    }
}