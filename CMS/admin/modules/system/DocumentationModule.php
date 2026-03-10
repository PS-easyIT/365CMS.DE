<?php
declare(strict_types=1);

/**
 * Dokumentations-Modul
 *
 * Orchestriert Dokumentkatalog, Rendering und Synchronisation für den
 * Admin-Bereich `/admin/documentation`.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/DocumentationCatalog.php';
require_once __DIR__ . '/DocumentationRenderer.php';
require_once __DIR__ . '/DocumentationSyncService.php';

final class DocumentationModule
{
    private const GITHUB_DOC_BASE = 'https://github.com/PS-easyIT/365CMS.DE/blob/main/DOC/';
    private const GITHUB_DOC_TREE = 'https://github.com/PS-easyIT/365CMS.DE/tree/main/DOC';
    private const GITHUB_DOC_ZIP = 'https://codeload.github.com/PS-easyIT/365CMS.DE/zip/refs/heads/main';
    private const DEFAULT_REMOTE = 'origin';
    private const DEFAULT_BRANCH = 'main';

    private readonly string $docsRoot;
    private readonly string $repoRoot;
    private readonly DocumentationCatalog $catalog;
    private readonly DocumentationRenderer $renderer;
    private readonly DocumentationSyncService $syncService;

    public function __construct()
    {
        $this->repoRoot = rtrim((string) dirname((string) ABSPATH), '\\/');
        $this->docsRoot = $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC';
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';

        $this->catalog = new DocumentationCatalog(
            $this->docsRoot,
            self::GITHUB_DOC_BASE,
            self::GITHUB_DOC_TREE,
            $siteUrl
        );
        $this->renderer = new DocumentationRenderer([$this->catalog, 'resolveLink']);
        $this->syncService = new DocumentationSyncService(
            $this->repoRoot,
            $this->docsRoot,
            self::GITHUB_DOC_ZIP,
            self::DEFAULT_REMOTE,
            self::DEFAULT_BRANCH
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(?string $selectedDoc = null): array
    {
        if (!is_dir($this->docsRoot)) {
            return [
                'available' => false,
                'docs_root' => $this->docsRoot,
                'github_root_url' => self::GITHUB_DOC_TREE,
                'sections' => [],
                'featured_docs' => [],
                'selected_document' => null,
                'selected_html' => '',
                'selected_raw' => '',
                'doc_count' => 0,
                'section_count' => 0,
                'is_selected_csv' => false,
                'error' => 'Das Dokumentationsverzeichnis /DOC wurde im Repository nicht gefunden.',
            ];
        }

        $catalogData = $this->catalog->buildCatalog();
        $selection = $this->catalog->resolveSelection($selectedDoc, $catalogData['all_docs']);
        $selectedDocument = $selection['selected_document'];
        $selectedRaw = '';
        $selectedHtml = '';
        $isSelectedCsv = false;
        $syncCapabilities = $this->syncService->getSyncCapabilities();

        if (is_array($selectedDocument) && !empty($selectedDocument['full_path']) && is_file((string) $selectedDocument['full_path'])) {
            $selectedRaw = $this->catalog->readDocumentContents((string) $selectedDocument['full_path']);
            $extension = strtolower((string) ($selectedDocument['extension'] ?? 'md'));
            $isSelectedCsv = $extension === 'csv';
            $selectedHtml = $this->renderer->renderDocument(
                $selectedRaw,
                $extension,
                (string) ($selectedDocument['relative_path'] ?? '')
            );
        }

        return [
            'available' => true,
            'docs_root' => $this->docsRoot,
            'repo_root' => $this->repoRoot,
            'github_root_url' => self::GITHUB_DOC_TREE,
            'sections' => $catalogData['sections'],
            'featured_docs' => $catalogData['featured_docs'],
            'selected_document' => $selectedDocument,
            'selected_html' => $selectedHtml,
            'selected_raw' => $selectedRaw,
            'doc_count' => $catalogData['doc_count'],
            'section_count' => $catalogData['section_count'],
            'is_selected_csv' => $isSelectedCsv,
            'git_available' => (bool) ($syncCapabilities['git'] ?? false),
            'sync_capabilities' => $syncCapabilities,
            'error' => null,
        ];
    }

    /**
     * @return array{success: bool, message?: string, error?: string}
     */
    public function syncDocsFromRepository(): array
    {
        return $this->syncService->syncDocsFromRepository();
    }
}
