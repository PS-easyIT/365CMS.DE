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
$sections         = $data['sections'] ?? [];
$featuredDocs     = $data['featured_docs'] ?? [];
$selectedDocument = $data['selected_document'] ?? null;
$selectedHtml     = (string) ($data['selected_html'] ?? '');
$docCount         = (int) ($data['doc_count'] ?? 0);
$sectionCount     = (int) ($data['section_count'] ?? 0);
$docsRoot         = (string) ($data['docs_root'] ?? '');
$repoRoot         = (string) ($data['repo_root'] ?? '');
$githubRootUrl    = (string) ($data['github_root_url'] ?? '');
$isSelectedCsv    = (bool) ($data['is_selected_csv'] ?? false);
$gitAvailable     = (bool) ($data['git_available'] ?? false);
$syncCapabilities = is_array($data['sync_capabilities'] ?? null) ? $data['sync_capabilities'] : [];
$syncAvailable    = (bool) ($syncCapabilities['can_sync'] ?? false);
$syncLabel        = (string) ($syncCapabilities['label'] ?? ($gitAvailable ? 'Git-Sync bereit' : 'Nicht verfügbar'));
$syncMessage      = (string) ($syncCapabilities['message'] ?? '');
$syncMode         = (string) ($syncCapabilities['mode'] ?? ($gitAvailable ? 'git' : 'none'));
$syncClass        = $syncAvailable ? 'text-success' : 'text-warning';
$syncAlertClass   = $syncAvailable ? 'success' : 'warning';
$logPath          = (string) ($data['log_path'] ?? '');
$logsUrl          = (string) ($data['logs_url'] ?? '/admin/cms-logs');
$selectedPath     = is_array($selectedDocument) ? (string) ($selectedDocument['relative_path'] ?? '') : '';
$selectedTitle    = is_array($selectedDocument) ? (string) ($selectedDocument['title'] ?? 'Dokument auswählen') : 'Dokument auswählen';
$selectedExcerpt  = is_array($selectedDocument) ? (string) ($selectedDocument['excerpt'] ?? '') : '';
$selectedGithub   = is_array($selectedDocument) ? (string) ($selectedDocument['github_url'] ?? $githubRootUrl) : $githubRootUrl;
$selectedPathLabel = $selectedPath !== '' ? $selectedPath : 'README.md';
$alertData = is_array($alert ?? null) ? $alert : [];
$alertMarginClass = 'mb-4';
$syncButtonDisabled = $syncAvailable ? '' : ' disabled';

if (!function_exists('cms_admin_documentation_view_document_admin_url')) {
    function cms_admin_documentation_view_document_admin_url(array $document): string
    {
        return (string) ($document['admin_url'] ?? '#');
    }
}

if (!function_exists('cms_admin_documentation_view_document_title')) {
    function cms_admin_documentation_view_document_title(array $document): string
    {
        return (string) ($document['title'] ?? 'Dokument');
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
        return strtoupper((string) ($document['extension'] ?? 'md'));
    }
}

if (!function_exists('cms_admin_documentation_view_is_active_document')) {
    function cms_admin_documentation_view_is_active_document(array $document, string $selectedPath): bool
    {
        return cms_admin_documentation_view_document_relative_path($document) === $selectedPath;
    }
}

if (!function_exists('cms_admin_documentation_view_find_section_active')) {
    function cms_admin_documentation_view_find_section_active(array $documents, string $selectedPath): bool
    {
        foreach ($documents as $document) {
            if (is_array($document) && cms_admin_documentation_view_is_active_document($document, $selectedPath)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('cms_admin_documentation_view_section_slug')) {
    function cms_admin_documentation_view_section_slug(array $section, int $index): string
    {
        return (string) ($section['slug'] ?? ('section-' . $index));
    }
}

if (!function_exists('cms_admin_documentation_view_section_title')) {
    function cms_admin_documentation_view_section_title(array $section, string $fallback): string
    {
        return (string) ($section['title'] ?? $fallback);
    }
}

if (!function_exists('cms_admin_documentation_view_section_description')) {
    function cms_admin_documentation_view_section_description(array $section): string
    {
        return (string) ($section['description'] ?? '');
    }
}

if (!function_exists('cms_admin_documentation_view_section_doc_count')) {
    function cms_admin_documentation_view_section_doc_count(array $section, array $documents): int
    {
        return (int) ($section['doc_count'] ?? count($documents));
    }
}

if (!function_exists('cms_admin_documentation_view_section_github_url')) {
    function cms_admin_documentation_view_section_github_url(array $section, string $githubRootUrl): string
    {
        return (string) ($section['github_url'] ?? $githubRootUrl);
    }
}

if (!function_exists('cms_admin_documentation_view_document_view_model')) {
    /**
     * @return array{admin_url:string,title:string,relative_path:string,extension:string,is_active:bool}
     */
    function cms_admin_documentation_view_document_view_model(array $document, string $selectedPath): array
    {
        $relativePath = cms_admin_documentation_view_document_relative_path($document);

        return [
            'admin_url' => cms_admin_documentation_view_document_admin_url($document),
            'title' => cms_admin_documentation_view_document_title($document),
            'relative_path' => $relativePath,
            'extension' => cms_admin_documentation_view_document_extension($document),
            'is_active' => $relativePath === $selectedPath,
        ];
    }
}

if (!function_exists('cms_admin_documentation_view_section_view_model')) {
    /**
     * @return array{slug:string,title:string,description:string,doc_count:int,github_url:string,documents:array,active:bool}
     */
    function cms_admin_documentation_view_section_view_model(array $section, int $index, string $selectedPath, string $githubRootUrl): array
    {
        $slug = cms_admin_documentation_view_section_slug($section, $index);
        $documents = is_array($section['documents'] ?? null) ? $section['documents'] : [];

        return [
            'slug' => $slug,
            'title' => cms_admin_documentation_view_section_title($section, $slug),
            'description' => cms_admin_documentation_view_section_description($section),
            'doc_count' => cms_admin_documentation_view_section_doc_count($section, $documents),
            'github_url' => cms_admin_documentation_view_section_github_url($section, $githubRootUrl),
            'documents' => $documents,
            'active' => cms_admin_documentation_view_find_section_active($documents, $selectedPath),
        ];
    }
}

if (!function_exists('cms_admin_documentation_view_render_metric_card')) {
    function cms_admin_documentation_view_render_metric_card(string $label, string $value, string $valueClass = '', string $subValue = ''): void
    {
        ?>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="<?= $subValue === '' ? 'h1' : 'h3' ?> mb-<?= $subValue === '' ? '0' : '1' ?><?= $valueClass !== '' ? ' ' . htmlspecialchars($valueClass, ENT_QUOTES, 'UTF-8') : '' ?>"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($subValue !== ''): ?>
                        <div class="small text-secondary text-truncate"><?= htmlspecialchars($subValue, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_card_header_title')) {
    function cms_admin_documentation_view_render_card_header_title(string $title, string $subtitle = '', bool $compactTitle = false): void
    {
        ?>
        <div class="card-header">
            <div>
                <h3 class="card-title<?= $compactTitle ? ' mb-1' : '' ?>"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
                <?php if ($subtitle !== ''): ?>
                    <div class="text-secondary small"><code><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></code></div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_document_list_item')) {
    function cms_admin_documentation_view_render_document_list_item(array $document, string $selectedPath, bool $compact = false): void
    {
        $viewModel = cms_admin_documentation_view_document_view_model($document, $selectedPath);
        ?>
        <a href="<?= htmlspecialchars($viewModel['admin_url'], ENT_QUOTES, 'UTF-8') ?>" class="list-group-item list-group-item-action<?= $viewModel['is_active'] ? ' active' : '' ?>">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <div class="<?= $compact ? 'fw-semibold small' : 'fw-semibold' ?>"><?= htmlspecialchars($viewModel['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-secondary small"><?= htmlspecialchars($viewModel['relative_path'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if (!$compact): ?>
                    <span class="badge bg-azure-lt"><?= htmlspecialchars($viewModel['extension'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_document_list')) {
    function cms_admin_documentation_view_render_document_list(array $documents, string $selectedPath, bool $compact = false): void
    {
        foreach ($documents as $document) {
            if (!is_array($document)) {
                continue;
            }

            cms_admin_documentation_view_render_document_list_item($document, $selectedPath, $compact);
        }
    }
}

if (!function_exists('cms_admin_documentation_view_render_section_intro')) {
    function cms_admin_documentation_view_render_section_intro(string $description, string $githubUrl): void
    {
        ?>
        <div class="p-3 border-bottom bg-body-secondary">
            <div class="small text-secondary mb-2"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></div>
            <a href="<?= htmlspecialchars($githubUrl, ENT_QUOTES, 'UTF-8') ?>" class="small" target="_blank" rel="noopener noreferrer">Bereich auf GitHub öffnen</a>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_section_accordion_item')) {
    function cms_admin_documentation_view_render_section_accordion_item(
        string $sectionSlug,
        string $sectionTitle,
        int $sectionDocCount,
        bool $sectionActive,
        string $sectionDescription,
        string $sectionGithubUrl,
        array $documents,
        string $selectedPath
    ): void {
        ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($sectionSlug, ENT_QUOTES, 'UTF-8'); ?>">
                <button class="accordion-button<?php echo $sectionActive ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo htmlspecialchars($sectionSlug, ENT_QUOTES, 'UTF-8'); ?>" aria-expanded="<?php echo $sectionActive ? 'true' : 'false'; ?>">
                    <span>
                        <span class="fw-semibold"><?php echo htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="text-secondary small ms-2"><?php echo htmlspecialchars((string) $sectionDocCount, ENT_QUOTES, 'UTF-8'); ?> Dateien</span>
                    </span>
                </button>
            </h2>
            <div id="collapse-<?php echo htmlspecialchars($sectionSlug, ENT_QUOTES, 'UTF-8'); ?>" class="accordion-collapse collapse<?php echo $sectionActive ? ' show' : ''; ?>" data-bs-parent="#documentation-sections">
                <div class="accordion-body p-0">
                    <?php cms_admin_documentation_view_render_section_intro($sectionDescription, $sectionGithubUrl); ?>
                    <div class="list-group list-group-flush">
                        <?php cms_admin_documentation_view_render_document_list($documents, $selectedPath, true); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_render_selected_document_content')) {
    function cms_admin_documentation_view_render_selected_document_content(
        string $selectedExcerpt,
        string $documentationSourceText,
        string $selectedHtml,
        bool $isSelectedCsv
    ): void {
        ?>
        <div class="card-body">
            <?php if ($selectedExcerpt !== ''): ?>
                <?php
                $alertData = ['type' => 'info', 'message' => $selectedExcerpt];
                $alertDismissible = false;
                $alertMarginClass = 'mb-4';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
            <?php endif; ?>

            <div class="small text-secondary mb-4">
                <?= htmlspecialchars($documentationSourceText, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if ($selectedHtml === ''): ?>
                <div class="empty">
                    <div class="empty-img"><!-- decorative --></div>
                    <p class="empty-title">Kein Dokument ausgewählt</p>
                    <p class="empty-subtitle text-secondary">Wähle links eine Dokumentationsdatei aus, um sie direkt im Admin zu lesen.</p>
                </div>
            <?php else: ?>
                <div class="documentation-rendered">
                    <?php echo $selectedHtml; ?>
                </div>
            <?php endif; ?>

            <?php if ($isSelectedCsv): ?>
                <div class="mt-4 small text-secondary">CSV-Dateien werden tabellarisch dargestellt. Für Weiterverarbeitung kannst du zusätzlich die GitHub-Ansicht öffnen.</div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('cms_admin_documentation_view_source_text')) {
    function cms_admin_documentation_view_source_text(string $docsRoot): string
    {
        return $docsRoot !== ''
            ? 'Quelle: lokales /DOC-Verzeichnis im Hosting-Stamm bzw. Projektpfad unter ' . $docsRoot . '. Inhalt entspricht dem GitHub-Bereich /DOC des Projekts.'
            : 'Quelle: lokales /DOC-Verzeichnis im Hosting-Stamm bzw. Projektpfad. Inhalt entspricht dem GitHub-Bereich /DOC des Projekts.';
    }
}

if (!function_exists('cms_admin_documentation_view_metric_cards')) {
    /**
     * @return list<array{label:string,value:string,class:string,sub:string}>
     */
    function cms_admin_documentation_view_metric_cards(int $docCount, int $sectionCount, string $selectedPathLabel, string $syncLabel, string $syncClass, string $repoRoot): array
    {
        return [
            ['label' => 'Dokumente', 'value' => (string) $docCount, 'class' => '', 'sub' => ''],
            ['label' => 'Bereiche', 'value' => (string) $sectionCount, 'class' => '', 'sub' => ''],
            ['label' => 'Quelle', 'value' => 'Repo /DOC', 'class' => '', 'sub' => ''],
            ['label' => 'Aktuell geladen', 'value' => $selectedPathLabel, 'class' => '', 'sub' => ''],
            ['label' => 'Sync-Status', 'value' => $syncLabel, 'class' => $syncClass, 'sub' => $repoRoot !== '' ? $repoRoot : 'Repo-Pfad unbekannt'],
        ];
    }
}

$documentationSourceText = cms_admin_documentation_view_source_text($docsRoot);
$metricCards = cms_admin_documentation_view_metric_cards($docCount, $sectionCount, $selectedPathLabel, $syncLabel, $syncClass, $repoRoot);
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">System &amp; Doku</div>
                <h2 class="page-title">Dokumentation</h2>
                <div class="text-secondary mt-1">Live-Ansicht der lokalen Dokumentation aus <code>/DOC</code> – bevorzugt direkt neben der öffentlichen <code>index.php</code> im Hosting-Stamm, alternativ aus dem Projekt-Layout.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="sync_docs">
                    <button type="submit" class="btn btn-primary"<?php echo $syncButtonDisabled; ?>>Lokalen /DOC-Ordner syncen</button>
                </form>
                <a href="<?php echo htmlspecialchars($githubRootUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">GitHub /DOC öffnen</a>
                <?php if ($selectedPath !== ''): ?>
                    <a href="<?php echo htmlspecialchars($selectedGithub); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">Aktuelles Dokument auf GitHub</a>
                <?php endif; ?>
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
            <div class="row row-deck row-cards mb-4">
                <?php foreach ($metricCards as $metricCard): ?>
                    <?php cms_admin_documentation_view_render_metric_card($metricCard['label'], $metricCard['value'], $metricCard['class'], $metricCard['sub']); ?>
                <?php endforeach; ?>
            </div>

            <?php if ($syncMessage !== ''): ?>
                <?php
                $alertData = [
                    'type' => $syncAlertClass,
                    'message' => $syncMessage,
                    'details' => array_values(array_filter([
                        'Synchronisationsmodus: ' . $syncMode,
                        $logPath !== '' ? 'Logpfad: ' . $logPath : '',
                    ])),
                ];
                $alertDismissible = false;
                $alertMarginClass = 'mb-4';
                require __DIR__ . '/../partials/flash-alert.php';
                ?>
            <?php endif; ?>

            <div class="row row-cards mb-4">
                <div class="col-12 col-xl-5">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Lokale Quelle &amp; Sync-Diagnose</h3></div>
                        <div class="card-body">
                            <dl class="row mb-3">
                                <dt class="col-sm-4 text-secondary">DOC-Pfad</dt>
                                <dd class="col-sm-8"><code><?php echo htmlspecialchars($docsRoot, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                                <dt class="col-sm-4 text-secondary">Repo-Pfad</dt>
                                <dd class="col-sm-8"><code><?php echo htmlspecialchars($repoRoot, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                                <dt class="col-sm-4 text-secondary">Log-Pfad</dt>
                                <dd class="col-sm-8"><code><?php echo htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8'); ?></code></dd>
                            </dl>
                            <a href="<?php echo htmlspecialchars($logsUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary btn-sm">Logs &amp; Protokolle öffnen</a>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-7">
                    <div class="card h-100">
                        <div class="card-header"><h3 class="card-title">Logs &amp; Verlauf</h3></div>
                        <div class="card-body">
                            <p class="text-secondary mb-3">Ausführliche Doku-Sync-Logs werden bewusst nur zentral unter <strong>Diagnose &gt; Logs &amp; Protokolle</strong> angezeigt. Dadurch bleiben Dokumentation, Dashboard und die Datenbank-/Asset-Diagnose frei von Info-Lograuschen.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?php echo htmlspecialchars($logsUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-sm">Doku-Sync-Logs in Logs &amp; Protokolle öffnen</a>
                                <?php if ($logPath !== ''): ?>
                                    <span class="badge bg-secondary-lt text-break"><code><?php echo htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8'); ?></code></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cards">
                <div class="col-12 col-xl-4">
                    <div class="card mb-4">
                        <?php cms_admin_documentation_view_render_card_header_title('Schnellstart'); ?>
                        <div class="list-group list-group-flush">
                            <?php cms_admin_documentation_view_render_document_list($featuredDocs, $selectedPath); ?>
                        </div>
                    </div>

                    <div class="card">
                        <?php cms_admin_documentation_view_render_card_header_title('Dokumentationsbereiche'); ?>
                        <div class="accordion accordion-flush" id="documentation-sections">
                            <?php foreach ($sections as $index => $section): ?>
                                <?php
                                if (!is_array($section)) {
                                    continue;
                                }

                                $sectionViewModel = cms_admin_documentation_view_section_view_model($section, $index, $selectedPath, $githubRootUrl);
                                ?>
                                <?php cms_admin_documentation_view_render_section_accordion_item($sectionViewModel['slug'], $sectionViewModel['title'], $sectionViewModel['doc_count'], $sectionViewModel['active'], $sectionViewModel['description'], $sectionViewModel['github_url'], $sectionViewModel['documents'], $selectedPath); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <?php cms_admin_documentation_view_render_card_header_title($selectedTitle, $selectedPath, true); ?>
                        <?php cms_admin_documentation_view_render_selected_document_content($selectedExcerpt, $documentationSourceText, $selectedHtml, $isSelectedCsv); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
