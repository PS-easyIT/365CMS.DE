<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
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
$selectedPath     = is_array($selectedDocument) ? (string) ($selectedDocument['relative_path'] ?? '') : '';
$selectedTitle    = is_array($selectedDocument) ? (string) ($selectedDocument['title'] ?? 'Dokument auswählen') : 'Dokument auswählen';
$selectedExcerpt  = is_array($selectedDocument) ? (string) ($selectedDocument['excerpt'] ?? '') : '';
$selectedGithub   = is_array($selectedDocument) ? (string) ($selectedDocument['github_url'] ?? $githubRootUrl) : $githubRootUrl;
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Info &amp; Diagnose</div>
                <h2 class="page-title">Dokumentation</h2>
                <div class="text-secondary mt-1">Live-Ansicht der Repository-Dokumentation aus <code>/DOC</code> – strukturiert wie im GitHub-Bereich, aber direkt im Admin verfügbar.</div>
            </div>
            <div class="col-auto d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                    <input type="hidden" name="action" value="sync_docs">
                    <button type="submit" class="btn btn-primary"<?php echo $syncAvailable ? '' : ' disabled'; ?>>Lokalen /DOC-Ordner syncen</button>
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
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <?php if (!$available): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Dokumentation nicht verfügbar:</strong>
                <?php echo htmlspecialchars((string) $error); ?>
                <div class="small text-secondary mt-2">Erwarteter Pfad: <code><?php echo htmlspecialchars($docsRoot); ?></code></div>
            </div>
        <?php else: ?>
            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">Dokumente</div>
                            <div class="h1 mb-0"><?php echo htmlspecialchars((string) $docCount); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">Bereiche</div>
                            <div class="h1 mb-0"><?php echo htmlspecialchars((string) $sectionCount); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">Quelle</div>
                            <div class="h3 mb-0">Repo <code>/DOC</code></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">Aktuell geladen</div>
                            <div class="small fw-semibold text-truncate"><?php echo htmlspecialchars($selectedPath !== '' ? $selectedPath : 'README.md'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="subheader">Sync-Status</div>
                            <div class="h3 mb-1 <?php echo htmlspecialchars($syncClass); ?>"><?php echo htmlspecialchars($syncLabel); ?></div>
                            <div class="small text-secondary text-truncate"><?php echo htmlspecialchars($repoRoot !== '' ? $repoRoot : 'Repo-Pfad unbekannt'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($syncMessage !== ''): ?>
                <div class="alert alert-<?php echo $syncAvailable ? 'success' : 'warning'; ?> mb-4" role="alert">
                    <div class="fw-semibold mb-1">Synchronisationsmodus: <?php echo htmlspecialchars($syncMode); ?></div>
                    <div><?php echo htmlspecialchars($syncMessage); ?></div>
                </div>
            <?php endif; ?>

            <div class="row row-cards">
                <div class="col-12 col-xl-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Schnellstart</h3>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($featuredDocs as $document): ?>
                                <?php if (!is_array($document)) { continue; } ?>
                                <a href="<?php echo htmlspecialchars((string) ($document['admin_url'] ?? '#')); ?>"
                                   class="list-group-item list-group-item-action<?php echo ((string) ($document['relative_path'] ?? '') === $selectedPath) ? ' active' : ''; ?>">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string) ($document['title'] ?? 'Dokument')); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars((string) ($document['relative_path'] ?? '')); ?></div>
                                        </div>
                                        <span class="badge bg-azure-lt"><?php echo htmlspecialchars(strtoupper((string) ($document['extension'] ?? 'md'))); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Dokumentationsbereiche</h3>
                        </div>
                        <div class="accordion accordion-flush" id="documentation-sections">
                            <?php foreach ($sections as $index => $section): ?>
                                <?php
                                if (!is_array($section)) {
                                    continue;
                                }

                                $sectionSlug = (string) ($section['slug'] ?? ('section-' . $index));
                                $sectionTitle = (string) ($section['title'] ?? $sectionSlug);
                                $sectionDescription = (string) ($section['description'] ?? '');
                                $documents = is_array($section['documents'] ?? null) ? $section['documents'] : [];
                                $sectionActive = false;
                                foreach ($documents as $document) {
                                    if (is_array($document) && (string) ($document['relative_path'] ?? '') === $selectedPath) {
                                        $sectionActive = true;
                                        break;
                                    }
                                }
                                ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?php echo htmlspecialchars($sectionSlug); ?>">
                                        <button class="accordion-button<?php echo $sectionActive ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo htmlspecialchars($sectionSlug); ?>" aria-expanded="<?php echo $sectionActive ? 'true' : 'false'; ?>">
                                            <span>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($sectionTitle); ?></span>
                                                <span class="text-secondary small ms-2"><?php echo htmlspecialchars((string) ($section['doc_count'] ?? count($documents))); ?> Dateien</span>
                                            </span>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?php echo htmlspecialchars($sectionSlug); ?>" class="accordion-collapse collapse<?php echo $sectionActive ? ' show' : ''; ?>" data-bs-parent="#documentation-sections">
                                        <div class="accordion-body p-0">
                                            <div class="p-3 border-bottom bg-body-secondary">
                                                <div class="small text-secondary mb-2"><?php echo htmlspecialchars($sectionDescription); ?></div>
                                                <a href="<?php echo htmlspecialchars((string) ($section['github_url'] ?? $githubRootUrl)); ?>" class="small" target="_blank" rel="noopener noreferrer">Bereich auf GitHub öffnen</a>
                                            </div>
                                            <div class="list-group list-group-flush">
                                                <?php foreach ($documents as $document): ?>
                                                    <?php if (!is_array($document)) { continue; } ?>
                                                    <?php $isActive = (string) ($document['relative_path'] ?? '') === $selectedPath; ?>
                                                    <a href="<?php echo htmlspecialchars((string) ($document['admin_url'] ?? '#')); ?>" class="list-group-item list-group-item-action<?php echo $isActive ? ' active' : ''; ?>">
                                                        <div class="fw-semibold small"><?php echo htmlspecialchars((string) ($document['title'] ?? 'Dokument')); ?></div>
                                                        <div class="text-secondary small"><?php echo htmlspecialchars((string) ($document['relative_path'] ?? '')); ?></div>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title mb-1"><?php echo htmlspecialchars($selectedTitle); ?></h3>
                                <?php if ($selectedPath !== ''): ?>
                                    <div class="text-secondary small"><code><?php echo htmlspecialchars($selectedPath); ?></code></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($selectedExcerpt !== ''): ?>
                                <div class="alert alert-info" role="alert">
                                    <?php echo htmlspecialchars($selectedExcerpt); ?>
                                </div>
                            <?php endif; ?>

                            <div class="small text-secondary mb-4">
                                Quelle: lokale Repository-Dokumentation unter <code><?php echo htmlspecialchars($docsRoot); ?></code>. Inhalt entspricht dem GitHub-Bereich <code>/DOC</code> des Projekts.
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
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
