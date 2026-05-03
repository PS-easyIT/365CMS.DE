<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

/**
 * @var array<string, mixed> $data
 */

$available        = (bool) ($data['available'] ?? false);
$error            = $data['error'] ?? null;
$sections         = is_array($data['sections'] ?? null) ? $data['sections'] : [];
$featuredDocs     = is_array($data['featured_docs'] ?? null) ? $data['featured_docs'] : [];
$selectedDocument = $data['selected_document'] ?? null;
$selectedHtml     = (string) ($data['selected_html'] ?? '');
$docsRoot         = (string) ($data['docs_root'] ?? '');
$isSelectedCsv    = (bool) ($data['is_selected_csv'] ?? false);
$selectedPath     = is_array($selectedDocument) ? (string) ($selectedDocument['relative_path'] ?? '') : '';
$selectedTitle    = is_array($selectedDocument) ? (string) ($selectedDocument['title'] ?? 'Dokument auswählen') : 'Dokument auswählen';
$alertData        = is_array($alert ?? null) ? $alert : [];
$alertMarginClass = 'mb-4';

if (!function_exists('cms_admin_documentation_view_document_admin_url')) {
    function cms_admin_documentation_view_document_admin_url(array $document): string
    {
        return (string) ($document['admin_url'] ?? '#');
    }
}

if (!function_exists('cms_admin_documentation_view_document_title')) {
    function cms_admin_documentation_view_document_title(array $document): string
    {
        return (string) ($document['title'] ?? basename((string) ($document['relative_path'] ?? 'Dokument')));
    }
}

if (!function_exists('cms_admin_documentation_view_document_relative_path')) {
    function cms_admin_documentation_view_document_relative_path(array $document): string
    {
        return (string) ($document['relative_path'] ?? '');
    }
}

if (!function_exists('cms_admin_documentation_view_document_extension')) {
    function cms_admin_documentation_view_document_extension(array $document): string
    {
        return strtolower((string) ($document['extension'] ?? 'md'));
    }
}

if (!function_exists('cms_admin_documentation_view_is_active_document')) {
    function cms_admin_documentation_view_is_active_document(array $document, string $selectedPath): bool
    {
        return cms_admin_documentation_view_document_relative_path($document) === $selectedPath;
    }
}

if (!function_exists('cms_admin_documentation_view_node_contains_active_document')) {
    function cms_admin_documentation_view_node_contains_active_document(array $node, string $selectedPath): bool
    {
        if ($selectedPath === '') {
            return false;
        }

        foreach ((array) ($node['documents'] ?? []) as $document) {
            if (is_array($document) && cms_admin_documentation_view_is_active_document($document, $selectedPath)) {
                return true;
            }
        }

        foreach ((array) ($node['children'] ?? []) as $child) {
            if (is_array($child) && cms_admin_documentation_view_node_contains_active_document($child, $selectedPath)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cms_admin_documentation_view_directory_collapse_id')) {
    function cms_admin_documentation_view_directory_collapse_id(array $node, int $depth): string
    {
        $relativePath = (string) ($node['relative_path'] ?? 'root');
        $hash = substr(hash('sha256', $relativePath . '|' . (string) $depth), 0, 12);

        return 'doc-tree-' . $hash;
    }
}

if (!function_exists('cms_admin_documentation_view_render_document_link')) {
    function cms_admin_documentation_view_render_document_link(array $document, string $selectedPath, bool $compact = false): void
    {
        $relativePath = cms_admin_documentation_view_document_relative_path($document);
        $isActive = $relativePath === $selectedPath;
        $extension = cms_admin_documentation_view_document_extension($document);
        ?>
        <a href="<?= htmlspecialchars(cms_admin_documentation_view_document_admin_url($document), ENT_QUOTES, 'UTF-8') ?>" class="doc-tree-link<?= $compact ? ' doc-tree-link-compact' : '' ?><?= $isActive ? ' active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <div class="doc-tree-link-main">
                <span class="doc-tree-file-type doc-tree-file-type-<?= htmlspecialchars($extension, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($extension, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="doc-tree-file-title text-truncate"><?= htmlspecialchars(cms_admin_documentation_view_document_title($document), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if (!$compact && $relativePath !== ''): ?>
                <div class="doc-tree-file-path text-truncate"><?= htmlspecialchars($relativePath, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </a>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_documents')) {
    function cms_admin_documentation_view_render_documents(array $documents, string $selectedPath): void
    {
        foreach ($documents as $document) {
            if (is_array($document)) {
                cms_admin_documentation_view_render_document_link($document, $selectedPath);
            }
        }
    }
}

if (!function_exists('cms_admin_documentation_view_is_readme_document')) {
    function cms_admin_documentation_view_is_readme_document(array $document): bool
    {
        return strtolower(basename(cms_admin_documentation_view_document_relative_path($document))) === 'readme.md';
    }
}

if (!function_exists('cms_admin_documentation_view_split_readme_documents')) {
    /**
     * @return array{readme:list<array<string,mixed>>,other:list<array<string,mixed>>}
     */
    function cms_admin_documentation_view_split_readme_documents(array $documents): array
    {
        $readme = [];
        $other = [];

        foreach ($documents as $document) {
            if (!is_array($document)) {
                continue;
            }

            if (cms_admin_documentation_view_is_readme_document($document)) {
                $readme[] = $document;
                continue;
            }

            $other[] = $document;
        }

        return ['readme' => $readme, 'other' => $other];
    }
}

if (!function_exists('cms_admin_documentation_view_render_directory_node')) {
    function cms_admin_documentation_view_render_directory_node(array $node, string $selectedPath, int $depth = 0): void
    {
        $documents = is_array($node['documents'] ?? null) ? $node['documents'] : [];
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];
        $splitDocuments = cms_admin_documentation_view_split_readme_documents($documents);
        $title = (string) ($node['title'] ?? '/DOC');
        $relativePath = (string) ($node['relative_path'] ?? '');
        $docCount = (int) ($node['doc_count'] ?? count($documents));
        $isRoot = $depth === 0;
        $isOpen = $isRoot || cms_admin_documentation_view_node_contains_active_document($node, $selectedPath);
        $collapseId = cms_admin_documentation_view_directory_collapse_id($node, $depth);
        $paddingClass = $depth > 0 ? ' ps-' . min(4, $depth + 2) : '';
        ?>
        <div class="doc-tree-node doc-tree-depth-<?= (int) $depth ?>">
            <?php if (!$isRoot): ?>
                <div class="doc-tree-folder<?= htmlspecialchars($paddingClass, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="doc-tree-folder-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="<?= $isOpen ? 'true' : 'false' ?>" aria-controls="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="doc-tree-folder-main">
                            <span class="doc-tree-caret" aria-hidden="true"></span>
                            <span class="doc-tree-folder-name text-truncate"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="doc-tree-count"><?= htmlspecialchars((string) $docCount, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </button>
                    <?php if ($relativePath !== ''): ?>
                        <div class="doc-tree-folder-path text-truncate"><?= htmlspecialchars($relativePath, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div id="<?= htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') ?>" class="doc-tree-children<?= $depth > 0 ? ' collapse' : '' ?><?= $depth > 0 && $isOpen ? ' show' : '' ?>">
                <?php cms_admin_documentation_view_render_documents($splitDocuments['readme'], $selectedPath); ?>

                <?php foreach ($children as $child): ?>
                    <?php if (is_array($child)) cms_admin_documentation_view_render_directory_node($child, $selectedPath, $depth + 1); ?>
                <?php endforeach; ?>

                <?php cms_admin_documentation_view_render_documents($splitDocuments['other'], $selectedPath); ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_filter_featured_markdown')) {
    /**
     * @return list<array<string, mixed>>
     */
    function cms_admin_documentation_view_filter_featured_markdown(array $featuredDocs): array
    {
        $markdownDocs = [];
        foreach ($featuredDocs as $document) {
            if (!is_array($document) || cms_admin_documentation_view_document_extension($document) !== 'md') {
                continue;
            }

            $markdownDocs[] = $document;
        }

        return $markdownDocs;
    }
}

if (!function_exists('cms_admin_documentation_view_render_featured_docs')) {
    function cms_admin_documentation_view_render_featured_docs(array $featuredDocs, string $selectedPath): void
    {
        $markdownDocs = cms_admin_documentation_view_filter_featured_markdown($featuredDocs);
        if ($markdownDocs === []) {
            return;
        }
        ?>
        <nav class="docs-sidebar documentation-sidebar documentation-sidebar-primary mb-3" aria-label="Wichtige Dokumente">
            <div class="docs-sidebar-header">Wichtige Dokumente</div>
            <div class="docs-nav-group documentation-quick-links">
                <div class="doc-tree-list">
                    <?php foreach ($markdownDocs as $document): ?>
                        <?php cms_admin_documentation_view_render_document_link($document, $selectedPath, true); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </nav>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_tree')) {
    function cms_admin_documentation_view_render_tree(array $sections, string $selectedPath): void
    {
        foreach ($sections as $section) {
            if (is_array($section)) {
                cms_admin_documentation_view_render_directory_node($section, $selectedPath);
            }
        }
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">System &amp; Doku</div>
                <h2 class="page-title">Dokumentation</h2>
                <div class="text-secondary mt-1">Live aus <code>/DOC</code>: Ordner, Unterordner und Dateien werden direkt aus dem lokalen Verzeichnis gelesen.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php require __DIR__ . '/../partials/flash-alert.php'; ?>

        <?php if (!$available): ?>
            <?php
            $alertData = [
                'type' => 'danger',
                'message' => 'Dokumentation nicht verfügbar: ' . (string) $error,
                'details' => $docsRoot !== '' ? ['Erwarteter Pfad: ' . $docsRoot] : [],
            ];
            $alertDismissible = false;
            $alertMarginClass = 'mb-4';
            require __DIR__ . '/../partials/flash-alert.php';
            ?>
        <?php else: ?>
            <div class="row row-cards">
                <div class="col-12 col-xl-4">
                    <?php cms_admin_documentation_view_render_featured_docs($featuredDocs, $selectedPath); ?>

                    <nav class="docs-sidebar documentation-sidebar" aria-label="DOC-Dateibaum">
                        <div class="docs-sidebar-header">
                            <div>
                                <div>/DOC</div>
                                <div class="documentation-sidebar-subtitle">Ordner einklappbar, README.md zuerst</div>
                            </div>
                        </div>
                        <div class="documentation-tree doc-tree-list">
                            <?php cms_admin_documentation_view_render_tree($sections, $selectedPath); ?>
                        </div>
                    </nav>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title mb-1"><?= htmlspecialchars($selectedTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if ($selectedPath !== ''): ?>
                                    <div class="text-secondary small"><code><?= htmlspecialchars($selectedPath, ENT_QUOTES, 'UTF-8') ?></code></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($selectedHtml === ''): ?>
                                <div class="empty">
                                    <p class="empty-title">Kein Dokument ausgewählt</p>
                                    <p class="empty-subtitle text-secondary">Wähle links eine Datei aus dem Live-Baum von <code>/DOC</code>.</p>
                                </div>
                            <?php else: ?>
                                <div class="documentation-rendered">
                                    <?php echo $selectedHtml; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($isSelectedCsv): ?>
                                <div class="mt-4 small text-secondary">CSV-Dateien werden direkt aus dem lokalen <code>/DOC</code>-Verzeichnis tabellarisch dargestellt.</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer small text-secondary">
                            Quelle: <?= htmlspecialchars($docsRoot !== '' ? $docsRoot : '/DOC', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
