<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationSyncCapabilities
{
    public function __construct(
        private readonly bool $canSync,
        private readonly bool $git,
        private readonly bool $githubZip,
        private readonly string $mode,
        private readonly string $label,
        private readonly string $message,
    ) {
    }

    public function canSync(): bool
    {
        return $this->canSync;
    }

    public function hasGit(): bool
    {
        return $this->git;
    }

    public function hasGithubZip(): bool
    {
        return $this->githubZip;
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'can_sync' => $this->canSync,
            'git' => $this->git,
            'github_zip' => $this->githubZip,
            'mode' => $this->mode,
            'label' => $this->label,
            'message' => $this->message,
        ];
    }

    /** @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string} */
    public function toLogContext(): array
    {
        return $this->toArray();
    }
}

final class DocumentationShellCommandResult
{
    public function __construct(
        private readonly string $output,
        private readonly int $exitCode,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->exitCode === 0;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }
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

    public function getSyncCapabilities(): DocumentationSyncCapabilities
    {
        $hasGitCheckout = $this->normalizedRepoRoot !== '' && is_dir($this->normalizedRepoRoot . DIRECTORY_SEPARATOR . '.git');
        $gitAvailable = $hasGitCheckout && $this->isGitAvailable();
        $zipAvailable = extension_loaded('zip');
        $httpAvailable = $this->canDownloadOverHttp();
        $githubZipAvailable = $zipAvailable && $httpAvailable;

        if ($gitAvailable) {
            return new DocumentationSyncCapabilities(
                true,
                true,
                $githubZipAvailable,
                'git',
                'Git-Sync bereit',
                'Lokaler Git-Checkout erkannt. /DOC kann direkt aus dem Repository synchronisiert werden.'
            );
        }

        if ($githubZipAvailable) {
            return new DocumentationSyncCapabilities(
                true,
                false,
                true,
                'github-zip',
                'GitHub-Sync bereit',
                'Kein Git-Checkout nötig: /DOC wird direkt als ZIP von GitHub geladen und lokal ersetzt.'
            );
        }

        $reasons = [];
        if (!$zipAvailable) {
            $reasons[] = 'ZIP-Extension fehlt';
        }
        if (!$httpAvailable) {
            $reasons[] = 'kein HTTPS-Download per zentralem cURL-HTTP-Client verfügbar';
        }

        return new DocumentationSyncCapabilities(
            false,
            false,
            false,
            'none',
            'Nicht verfügbar',
            $reasons === []
                ? 'Doku-Sync ist auf diesem Server derzeit nicht verfügbar.'
                : 'Doku-Sync ist auf diesem Server nicht verfügbar: ' . implode(', ', $reasons) . '.'
        );
    }

    public function runCommand(string $command): DocumentationShellCommandResult
    {
        if (!$this->isCommandExecutionAvailable()) {
            return new DocumentationShellCommandResult('Befehlsausführung ist auf diesem Server deaktiviert.', 1);
        }

        $command = $this->sanitizeCommand($command);
        if ($command === '' || !$this->isAllowedCommand($command)) {
            return new DocumentationShellCommandResult('Befehlsausführung ist für dieses Kommando nicht erlaubt.', 1);
        }

        $output = [];
        $exitCode = 1;
        exec($command, $output, $exitCode);

        return new DocumentationShellCommandResult(trim(implode("\n", $output)), $exitCode);
    }

    private function isGitAvailable(): bool
    {
        $result = $this->runCommand('git --version 2>&1');
        return $result->isSuccess() && str_contains(strtolower($result->output()), 'git version');
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