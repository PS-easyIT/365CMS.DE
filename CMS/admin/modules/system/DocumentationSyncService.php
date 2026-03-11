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

final class DocumentationSyncService
{
    private readonly DocumentationSyncEnvironment $environment;
    private readonly DocumentationGitSync $gitSync;
    private readonly DocumentationGithubZipSync $githubZipSync;

    public function __construct(
        private readonly string $repoRoot,
        private readonly string $docsRoot,
        private readonly string $githubZipUrl,
        private readonly string $defaultRemote,
        private readonly string $defaultBranch
    ) {
        $filesystem = new DocumentationSyncFilesystem();
        $downloader = new DocumentationSyncDownloader($filesystem);

        $this->environment = new DocumentationSyncEnvironment($this->repoRoot);
        $this->gitSync = new DocumentationGitSync(
            $this->repoRoot,
            $this->defaultRemote,
            $this->defaultBranch,
            $this->environment
        );
        $this->githubZipSync = new DocumentationGithubZipSync(
            $this->repoRoot,
            $this->docsRoot,
            $this->githubZipUrl,
            $downloader,
            $filesystem
        );
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function syncDocsFromRepository(): array
    {
        $capabilities = $this->environment->getSyncCapabilities();

        if (($capabilities['can_sync'] ?? false) !== true) {
            return ['success' => false, 'error' => (string) ($capabilities['message'] ?? 'Doku-Sync ist auf diesem Server nicht verfügbar.')];
        }

        if (($capabilities['git'] ?? false) === true) {
            return $this->gitSync->sync();
        }

        return $this->githubZipSync->sync();
    }

    /**
     * @return array{can_sync: bool, git: bool, github_zip: bool, mode: string, label: string, message: string}
     */
    public function getSyncCapabilities(): array
    {
        return $this->environment->getSyncCapabilities();
    }
}
