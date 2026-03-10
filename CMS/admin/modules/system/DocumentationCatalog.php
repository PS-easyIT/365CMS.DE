<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationCatalog
{
    public function __construct(
        private readonly string $docsRoot,
        private readonly string $githubDocBase,
        private readonly string $githubDocTree,
        private readonly string $siteUrl
    ) {
    }

    /**
     * @return array{sections: array<int, array<string, mixed>>, all_docs: array<string, array<string, mixed>>, featured_docs: array<int, array<string, mixed>>, doc_count: int, section_count: int}
     */
    public function buildCatalog(): array
    {
        $sections = $this->scanSections();
        $allDocs = $this->flattenDocuments($sections);

        return [
            'sections' => $sections,
            'all_docs' => $allDocs,
            'featured_docs' => $this->getFeaturedDocuments($allDocs),
            'doc_count' => count($allDocs),
            'section_count' => count($sections),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $allDocs
     * @return array{selected_relative: string, selected_document: array<string, mixed>|null}
     */
    public function resolveSelection(?string $selectedDoc, array $allDocs): array
    {
        $selectedRelative = $this->normalizeRelativePath((string) $selectedDoc);
        if ($selectedRelative === '' || !isset($allDocs[$selectedRelative])) {
            $selectedRelative = $this->resolveDefaultDocument(array_keys($allDocs));
        }

        return [
            'selected_relative' => $selectedRelative,
            'selected_document' => $selectedRelative !== '' && isset($allDocs[$selectedRelative]) ? $allDocs[$selectedRelative] : null,
        ];
    }

    public function readDocumentContents(string $fullPath): string
    {
        if (!is_file($fullPath) || !is_readable($fullPath)) {
            $this->logFilesystemFailure('file_get_contents_preflight', 'Dokument konnte nicht gelesen werden.', [
                'path' => $fullPath,
                'reason' => 'file_unreadable',
            ]);

            return '';
        }

        $warning = null;
        set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
            $warning = $message;
            return true;
        });

        try {
            $contents = file_get_contents($fullPath);
        } finally {
            restore_error_handler();
        }

        if (!is_string($contents)) {
            $context = ['path' => $fullPath];
            if ($warning !== null) {
                $context['warning'] = $warning;
            }
            $this->logFilesystemFailure('file_get_contents', 'Dokument konnte nicht gelesen werden.', $context);
            return '';
        }

        return $contents;
    }

    public function resolveLink(string $currentDocument, string $target): string
    {
        $target = trim($target);
        if ($target === '') {
            return '#';
        }

        if ($target[0] === '#') {
            return $target;
        }

        if ($this->isExternalUrl($target) || str_starts_with($target, '/')) {
            return $target;
        }

        $baseDir = trim(str_replace('\\', '/', dirname($currentDocument)), '.');
        $combined = $baseDir === '' ? $target : $baseDir . '/' . $target;
        $normalized = $this->normalizeRelativePath($combined);

        if ($normalized === '') {
            return '#';
        }

        $extension = strtolower((string) pathinfo($normalized, PATHINFO_EXTENSION));
        if (in_array($extension, ['md', 'csv'], true)) {
            return $this->buildAdminUrl($normalized);
        }

        return $this->buildGithubTreeUrl($normalized);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanSections(): array
    {
        $sections = [];
        $rootDocs = [];

        $entries = scandir($this->docsRoot);
        if ($entries === false) {
            return [];
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $this->docsRoot . DIRECTORY_SEPARATOR . $entry;

            if (is_file($fullPath) && $this->isSupportedDocument($fullPath)) {
                $rootDocs[] = $this->buildDocumentMeta($fullPath);
                continue;
            }

            if (is_dir($fullPath)) {
                $documents = $this->scanDocumentsInDirectory($fullPath);
                if ($documents === []) {
                    continue;
                }

                $sections[] = [
                    'slug' => $entry,
                    'title' => $this->resolveSectionTitle($entry),
                    'description' => $this->resolveSectionDescription($entry),
                    'github_url' => $this->buildGithubTreeUrl($entry),
                    'doc_count' => count($documents),
                    'documents' => $documents,
                ];
            }
        }

        usort($sections, static function (array $left, array $right): int {
            return strcasecmp((string) $left['title'], (string) $right['title']);
        });

        if ($rootDocs !== []) {
            usort($rootDocs, [$this, 'compareDocuments']);
            array_unshift($sections, [
                'slug' => 'root',
                'title' => 'Basisdokumente',
                'description' => 'Zentrale Einstiegs- und Referenzdokumente aus dem Wurzelverzeichnis von /DOC.',
                'github_url' => $this->githubDocTree,
                'doc_count' => count($rootDocs),
                'documents' => $rootDocs,
            ]);
        }

        return $sections;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scanDocumentsInDirectory(string $directory): array
    {
        $documents = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $fullPath = $file->getPathname();
            if (!$this->isSupportedDocument($fullPath)) {
                continue;
            }

            $documents[] = $this->buildDocumentMeta($fullPath);
        }

        usort($documents, [$this, 'compareDocuments']);

        return $documents;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocumentMeta(string $fullPath): array
    {
        $relativePath = $this->relativePath($fullPath);
        $contents = $this->readDocumentContents($fullPath);
        $extension = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));

        return [
            'title' => $this->extractTitle($relativePath, $contents),
            'excerpt' => $this->extractExcerpt($contents, $extension),
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'extension' => $extension,
            'github_url' => $this->buildGithubBlobUrl($relativePath),
            'admin_url' => $this->buildAdminUrl($relativePath),
        ];
    }

    private function relativePath(string $fullPath): string
    {
        $relative = substr($fullPath, strlen(rtrim($this->docsRoot, '\\/')) + 1);
        return $this->normalizeRelativePath((string) $relative);
    }

    private function extractTitle(string $relativePath, string $contents): string
    {
        if (preg_match('/^#\s+(.+)$/m', $contents, $matches) === 1) {
            return trim($matches[1]);
        }

        return (string) pathinfo($relativePath, PATHINFO_FILENAME);
    }

    private function extractExcerpt(string $contents, string $extension): string
    {
        if ($extension === 'csv') {
            return 'CSV-Export bzw. tabellarische Dokumentationsdaten.';
        }

        $lines = preg_split('/\R/', $contents) ?: [];
        $paragraph = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($paragraph !== []) {
                    break;
                }
                continue;
            }

            if (
                str_starts_with($trimmed, '#')
                || str_starts_with($trimmed, '---')
                || str_starts_with($trimmed, '```')
                || str_starts_with($trimmed, '![')
                || preg_match('/^\|.+\|$/', $trimmed) === 1
            ) {
                continue;
            }

            $paragraph[] = $trimmed;
            if (count($paragraph) >= 3) {
                break;
            }
        }

        $text = $this->stripMarkdown(implode(' ', $paragraph));
        if ($text === '') {
            return 'Dokumentation aus dem Repository-Bereich /DOC.';
        }

        if (mb_strlen($text) > 180) {
            $text = rtrim(mb_substr($text, 0, 177)) . '…';
        }

        return $text;
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/!\[[^\]]*\]\([^\)]*\)/', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[*_`>#-]/', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<int, string> $availablePaths
     */
    private function resolveDefaultDocument(array $availablePaths): string
    {
        $preferred = [
            'README.md',
            'INDEX.md',
            'admin/README.md',
            'member/README.md',
            'theme/README.md',
        ];

        foreach ($preferred as $candidate) {
            if (in_array($candidate, $availablePaths, true)) {
                return $candidate;
            }
        }

        return $availablePaths[0] ?? '';
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<string, array<string, mixed>>
     */
    private function flattenDocuments(array $sections): array
    {
        $documents = [];

        foreach ($sections as $section) {
            foreach ((array) ($section['documents'] ?? []) as $document) {
                if (!is_array($document) || empty($document['relative_path'])) {
                    continue;
                }

                $documents[(string) $document['relative_path']] = $document;
            }
        }

        return $documents;
    }

    /**
     * @param array<string, array<string, mixed>> $documents
     * @return array<int, array<string, mixed>>
     */
    private function getFeaturedDocuments(array $documents): array
    {
        $paths = [
            'README.md',
            'INSTALLATION.md',
            'INDEX.md',
            'admin/README.md',
            'member/README.md',
            'theme/README.md',
            'core/README.md',
            'workflow/PLUGIN-REGISTRATION-WORKFLOW.MD',
        ];

        $featured = [];
        foreach ($paths as $path) {
            if (isset($documents[$path])) {
                $featured[] = $documents[$path];
            }
        }

        return $featured;
    }

    private function compareDocuments(array $left, array $right): int
    {
        $leftPath = (string) ($left['relative_path'] ?? '');
        $rightPath = (string) ($right['relative_path'] ?? '');

        $leftIsReadme = str_ends_with(strtolower($leftPath), '/readme.md') || strtolower($leftPath) === 'readme.md';
        $rightIsReadme = str_ends_with(strtolower($rightPath), '/readme.md') || strtolower($rightPath) === 'readme.md';

        if ($leftIsReadme !== $rightIsReadme) {
            return $leftIsReadme ? -1 : 1;
        }

        return strcasecmp($leftPath, $rightPath);
    }

    private function isSupportedDocument(string $fullPath): bool
    {
        $extension = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));
        return in_array($extension, ['md', 'csv'], true);
    }

    private function resolveSectionTitle(string $slug): string
    {
        return match ($slug) {
            'admin' => 'Admin-Panel',
            'audits' => 'Audits',
            'core' => 'Core & Architektur',
            'feature' => 'Feature-Dokumentation',
            'member' => 'Mitglieder-Bereich',
            'plugins' => 'Plugins',
            'screenshots' => 'Screenshots',
            'theme' => 'Themes',
            'workflow' => 'Workflows',
            default => ucwords(str_replace(['-', '_'], ' ', $slug)),
        };
    }

    private function resolveSectionDescription(string $slug): string
    {
        return match ($slug) {
            'admin' => 'Bedienung und Architektur des Admin-Panels inklusive Unterbereiche.',
            'audits' => 'Prüfberichte, Analysen und Sicherheitsbewertungen.',
            'core' => 'Grundlagen zu Bootstrap, Router, Auth, Datenbank und Systemarchitektur.',
            'feature' => 'Fachliche Dokumentation einzelner Features und Funktionsbereiche.',
            'member' => 'Doku für Dashboard, Profil, Medien, Nachrichten und Datenschutz im Member-Bereich.',
            'plugins' => 'Entwicklungsleitfäden und Referenzen für Plugins und Integrationen.',
            'screenshots' => 'Bildmaterial und visuelle Dokumentationsartefakte.',
            'theme' => 'Theme-System, Customizer, Komponenten und Frontend-Entwicklung.',
            'workflow' => 'Abläufe, Registrierungsprozesse und technische Journeys.',
            default => 'Dokumentationssammlung aus dem Repository-Bereich /DOC.',
        };
    }

    private function buildGithubBlobUrl(string $relativePath): string
    {
        return $this->githubDocBase . $this->encodePath($relativePath);
    }

    private function buildGithubTreeUrl(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');
        if ($relativePath === '') {
            return $this->githubDocTree;
        }

        return $this->githubDocTree . '/' . $this->encodePath($relativePath);
    }

    private function encodePath(string $path): string
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', $path)), static fn (string $segment): bool => $segment !== '');
        return implode('/', array_map('rawurlencode', $segments));
    }

    private function buildAdminUrl(string $relativePath): string
    {
        return $this->siteUrl . '/admin/documentation?doc=' . rawurlencode($relativePath);
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function isExternalUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) === 1;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFilesystemFailure(string $operation, string $message, array $context = []): void
    {
        \CMS\Logger::instance()->withChannel('admin.documentation')->warning($message, array_merge([
            'operation' => $operation,
        ], $context));
    }
}
