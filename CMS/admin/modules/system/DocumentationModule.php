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

use CMS\Auth;
use CMS\Logger;

require_once __DIR__ . '/DocumentationCatalog.php';
require_once __DIR__ . '/DocumentationRenderer.php';
require_once __DIR__ . '/DocumentationSyncService.php';

final class DocumentationViewData
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private readonly array $data)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}

final class DocumentationSyncActionResult
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

    public function getMessage(): string
    {
        return $this->message ?? $this->error ?? 'Unbekannte Antwort beim Doku-Sync.';
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
}

final class DocumentationModule
{
    private const GITHUB_DOC_BASE = 'https://github.com/PS-easyIT/365CMS.DE/blob/main/DOC/';
    private const GITHUB_DOC_TREE = 'https://github.com/PS-easyIT/365CMS.DE/tree/main/DOC';
    private const GITHUB_DOC_ZIP = 'https://codeload.github.com/PS-easyIT/365CMS.DE/zip/refs/heads/main';
    private const APPROVED_DOC_BUNDLE_SHA256 = '284c5860b90e059019ba7eac1035b5777d78ca16b263aacc99066d1b47f52dcb';
    private const APPROVED_DOC_BUNDLE_FILE_COUNT = 121;
    private const DEFAULT_REMOTE = 'origin';
    private const DEFAULT_BRANCH = 'main';
    private const MAX_SELECTED_DOC_LENGTH = 240;
    private const MAX_UI_ERROR_LENGTH = 180;

    private readonly string $docsRoot;
    private readonly string $repoRoot;
    private readonly DocumentationCatalog $catalog;
    private readonly DocumentationRenderer $renderer;
    private readonly DocumentationSyncService $syncService;
    private readonly Logger $logger;

    public function __construct()
    {
        $this->repoRoot = rtrim((string) dirname((string) ABSPATH), '\\/');
        $this->docsRoot = $this->repoRoot . DIRECTORY_SEPARATOR . 'DOC';
        $this->logger = Logger::instance()->withChannel('admin.documentation');
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
            self::APPROVED_DOC_BUNDLE_SHA256,
            self::APPROVED_DOC_BUNDLE_FILE_COUNT,
            self::DEFAULT_REMOTE,
            self::DEFAULT_BRANCH
        );
    }

    public function getData(?string $selectedDoc = null): DocumentationViewData
    {
        if (!$this->canAccess()) {
            return $this->errorData('Zugriff verweigert.');
        }

        if (!$this->hasValidRepositoryLayout()) {
            return $this->errorData('Das Dokumentationsmodul ist lokal nicht korrekt konfiguriert.');
        }

        if (!is_dir($this->docsRoot)) {
            return $this->errorData('Das Dokumentationsverzeichnis /DOC wurde im Repository nicht gefunden.');
        }

        try {
            $syncCapabilities = $this->syncService->getSyncCapabilities()->toArray();
            $catalogSelection = $this->loadCatalogSelection($selectedDoc);

            return $this->buildAvailableData(
                $catalogSelection['catalog'],
                $catalogSelection['selected_document'],
                $syncCapabilities
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Dokumentationsdaten konnten nicht geladen werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeUiText($e->getMessage()),
            ]);

            return $this->errorData('Die Dokumentation konnte gerade nicht geladen werden. Bitte Logs prüfen.');
        }
    }

    public function syncDocsFromRepository(): DocumentationSyncActionResult
    {
        if (!$this->canAccess()) {
            return $this->denySyncResult('Sie dürfen die Dokumentation nicht synchronisieren.');
        }

        if (!$this->hasValidRepositoryLayout()) {
            return $this->errorSyncResult('Das Dokumentationsmodul ist lokal nicht korrekt konfiguriert. Bitte Logs prüfen.');
        }

        try {
            return $this->sanitizeSyncResult($this->syncService->syncDocsFromRepository()->toArray());
        } catch (\Throwable $e) {
            $this->logger->warning('Dokumentations-Sync ist unerwartet fehlgeschlagen.', [
                'exception' => $e::class,
                'message' => $this->sanitizeUiText($e->getMessage()),
            ]);

            return $this->errorSyncResult('Doku-Sync fehlgeschlagen. Bitte Logs prüfen.');
        }
    }

    private function canAccess(): bool
    {
        return class_exists(Auth::class) && Auth::instance()->isAdmin();
    }

    private function hasValidRepositoryLayout(): bool
    {
        $resolvedRepoRoot = realpath($this->repoRoot);
        $resolvedDocsRoot = file_exists($this->docsRoot) ? realpath($this->docsRoot) : $this->docsRoot;

        if ($resolvedRepoRoot === false || !is_dir($resolvedRepoRoot) || is_link($this->repoRoot)) {
            $this->logger->warning('Dokumentationsmodul: ungültiger Repository-Root.', [
                'repo_root' => $this->repoRoot,
            ]);
            return false;
        }

        if (!is_dir($resolvedRepoRoot . DIRECTORY_SEPARATOR . 'CMS')) {
            $this->logger->warning('Dokumentationsmodul: Repository-Layout ungültig.', [
                'repo_root' => $resolvedRepoRoot,
            ]);
            return false;
        }

        if ($resolvedDocsRoot !== $this->docsRoot && (!is_string($resolvedDocsRoot) || !str_starts_with($resolvedDocsRoot, rtrim($resolvedRepoRoot, '\\/') . DIRECTORY_SEPARATOR))) {
            $this->logger->warning('Dokumentationsmodul: DOC-Pfad liegt außerhalb des Repository-Roots.', [
                'docs_root' => $this->docsRoot,
                'resolved_docs_root' => $resolvedDocsRoot,
            ]);
            return false;
        }

        return true;
    }

    private function normalizeSelectedDocument(?string $selectedDoc): ?string
    {
        $selectedDoc = trim((string) $selectedDoc);
        if ($selectedDoc === '') {
            return null;
        }

        $selectedDoc = $this->sanitizeUiText($selectedDoc, self::MAX_SELECTED_DOC_LENGTH);
        $extension = strtolower((string) pathinfo($selectedDoc, PATHINFO_EXTENSION));

        if (!in_array($extension, ['md', 'csv'], true)) {
            return null;
        }

        return $selectedDoc;
    }

    private function normalizeDocumentExtension(string $extension): string
    {
        return in_array($extension, ['md', 'csv'], true) ? $extension : 'md';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildViewData(array $data): DocumentationViewData
    {
        return new DocumentationViewData($data);
    }

    /**
     * @return array{catalog: array<string, mixed>, selected_document: mixed}
     */
    private function loadCatalogSelection(?string $selectedDoc): array
    {
        $catalogData = $this->catalog->buildCatalog();
        $selection = $this->catalog->resolveSelection(
            $this->normalizeSelectedDocument($selectedDoc),
            $catalogData['all_docs']
        );

        return [
            'catalog' => $catalogData,
            'selected_document' => $selection['selected_document'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $catalogData
     * @param array<string, mixed> $syncCapabilities
     */
    private function buildAvailableData(array $catalogData, mixed $selectedDocument, array $syncCapabilities): DocumentationViewData
    {
        $selectedPayload = $this->buildSelectedDocumentPayload($selectedDocument);

        return $this->buildViewData($this->buildBasePayload([
            'available' => true,
            'sections' => $catalogData['sections'],
            'featured_docs' => $catalogData['featured_docs'],
            'selected_document' => $selectedPayload['selected_document'],
            'selected_html' => $selectedPayload['selected_html'],
            'selected_raw' => $selectedPayload['selected_raw'],
            'doc_count' => $catalogData['doc_count'],
            'section_count' => $catalogData['section_count'],
            'is_selected_csv' => $selectedPayload['is_selected_csv'],
            'git_available' => (bool) ($syncCapabilities['git'] ?? false),
            'sync_capabilities' => $syncCapabilities,
            'error' => null,
        ]));
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function buildBasePayload(array $overrides = []): array
    {
        return array_merge([
            'available' => false,
            'docs_root' => $this->docsRoot,
            'repo_root' => $this->repoRoot,
            'github_root_url' => self::GITHUB_DOC_TREE,
            'sections' => [],
            'featured_docs' => [],
            'selected_document' => null,
            'selected_html' => '',
            'selected_raw' => '',
            'doc_count' => 0,
            'section_count' => 0,
            'is_selected_csv' => false,
            'git_available' => false,
            'sync_capabilities' => [],
            'error' => null,
        ], $overrides);
    }

    /**
     * @param mixed $selectedDocument
     * @return array{selected_document: mixed, selected_html: string, selected_raw: string, is_selected_csv: bool}
     */
    private function buildSelectedDocumentPayload(mixed $selectedDocument): array
    {
        $payload = [
            'selected_document' => is_array($selectedDocument) ? $selectedDocument : null,
            'selected_html' => '',
            'selected_raw' => '',
            'is_selected_csv' => false,
        ];

        if (!is_array($selectedDocument) || empty($selectedDocument['full_path'])) {
            return $payload;
        }

        $fullPath = (string) $selectedDocument['full_path'];
        if (!is_file($fullPath)) {
            return $payload;
        }

        $selectedRaw = $this->catalog->readDocumentContents($fullPath);
        $extension = strtolower((string) ($selectedDocument['extension'] ?? 'md'));

        $payload['selected_raw'] = $selectedRaw;
        $payload['selected_html'] = $this->renderer->renderDocument(
            $selectedRaw,
            $this->normalizeDocumentExtension($extension),
            (string) ($selectedDocument['relative_path'] ?? '')
        );
        $payload['is_selected_csv'] = $extension === 'csv';

        return $payload;
    }

    private function sanitizeSyncResult(array $result): DocumentationSyncActionResult
    {
        $message = !empty($result['message'])
            ? $this->sanitizeUiText((string) $result['message'])
            : null;
        $error = !empty($result['error'])
            ? $this->sanitizeUiText((string) $result['error'])
            : null;

        return new DocumentationSyncActionResult(
            !empty($result['success']),
            $message,
            $error
        );
    }

    private function denySyncResult(string $message): DocumentationSyncActionResult
    {
        return new DocumentationSyncActionResult(false, null, $message);
    }

    private function errorSyncResult(string $message): DocumentationSyncActionResult
    {
        return new DocumentationSyncActionResult(false, null, $message);
    }

    private function errorData(string $message): DocumentationViewData
    {
        return $this->buildViewData($this->buildBasePayload([
            'error' => $message,
        ]));
    }

    private function sanitizeUiText(string $value, int $maxLength = self::MAX_UI_ERROR_LENGTH): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }
}
