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
$selectedPath     = is_array($selectedDocument) ? (string) ($selectedDocument['relative_path'] ?? '') : '';
$selectedTitle    = is_array($selectedDocument) ? (string) ($selectedDocument['title'] ?? 'Dokument auswählen') : 'Dokument auswählen';
$selectedExcerpt  = is_array($selectedDocument) ? (string) ($selectedDocument['excerpt'] ?? '') : '';
$selectedGithub   = is_array($selectedDocument) ? (string) ($selectedDocument['github_url'] ?? $githubRootUrl) : $githubRootUrl;
$selectedPathLabel = $selectedPath !== '' ? $selectedPath : 'README.md';
$alertData = is_array($alert ?? null) ? $alert : [];
$alertMarginClass = 'mb-4';
$syncButtonDisabled = $syncAvailable ? '' : ' disabled';
$renderMetricCard = static function (string $label, string $value, string $valueClass = '', string $subValue = ''): void {
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
};
$renderCardHeaderTitle = static function (string $title, string $subtitle = '', bool $compactTitle = false): void {
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
};
$renderAlertBlock = static function (string $type, string $message, string $title = '', string $detail = '', string $marginClass = ''): void {
    ?>
    <div class="alert alert-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?><?= $marginClass !== '' ? ' ' . htmlspecialchars($marginClass, ENT_QUOTES, 'UTF-8') : '' ?>" role="alert">
        <?php if ($title !== ''): ?>
            <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php endif; ?>
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($detail !== ''): ?>
            <div class="small text-secondary mt-2"><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <?php
};
$documentAdminUrl = static fn (array $document): string => (string) ($document['admin_url'] ?? '#');
$documentTitle = static fn (array $document): string => (string) ($document['title'] ?? 'Dokument');
$documentRelativePath = static fn (array $document): string => (string) ($document['relative_path'] ?? '');
$documentExtension = static fn (array $document): string => strtoupper((string) ($document['extension'] ?? 'md'));
$isActiveDocument = static fn (array $document): bool => (string) ($document['relative_path'] ?? '') === $selectedPath;
$documentItemClass = static fn (array $document): string => $isActiveDocument($document) ? ' active' : '';
$renderDocumentListItem = static function (array $document, bool $compact = false) use ($documentAdminUrl, $documentItemClass, $documentTitle, $documentRelativePath, $documentExtension): void {
    ?>
    <a href="<?= htmlspecialchars($documentAdminUrl($document), ENT_QUOTES, 'UTF-8') ?>" class="list-group-item list-group-item-action<?= $documentItemClass($document) ?>">
        <div class="d-flex align-items-start justify-content-between gap-3">
            <div>
                <div class="<?= $compact ? 'fw-semibold small' : 'fw-semibold' ?>"><?= htmlspecialchars($documentTitle($document), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-secondary small"><?= htmlspecialchars($documentRelativePath($document), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php if (!$compact): ?>
                <span class="badge bg-azure-lt"><?= htmlspecialchars($documentExtension($document), ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </a>
    <?php
};
$findSectionActive = static function (array $documents) use ($isActiveDocument): bool {
    foreach ($documents as $document) {
        if (is_array($document) && $isActiveDocument($document)) {
            return true;
        }
    }

    return false;
};
$sectionSlugValue = static fn (array $section, int $index): string => (string) ($section['slug'] ?? ('section-' . $index));
$sectionTitleValue = static fn (array $section, string $fallback): string => (string) ($section['title'] ?? $fallback);
$sectionDescriptionValue = static fn (array $section): string => (string) ($section['description'] ?? '');
$sectionDocCountValue = static fn (array $section, array $documents): int => (int) ($section['doc_count'] ?? count($documents));
$sectionGithubUrl = static fn (array $section) => (string) ($section['github_url'] ?? $githubRootUrl);
$renderSectionIntro = static function (string $description, string $githubUrl): void {
    ?>
    <div class="p-3 border-bottom bg-body-secondary">
        <div class="small text-secondary mb-2"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></div>
        <a href="<?= htmlspecialchars($githubUrl, ENT_QUOTES, 'UTF-8') ?>" class="small" target="_blank" rel="noopener noreferrer">Bereich auf GitHub öffnen</a>
    </div>
    <?php
};
$renderSectionAccordionItem = static function (
    string $sectionSlug,
    string $sectionTitle,
    int $sectionDocCount,
    bool $sectionActive,
    string $sectionDescription,
    string $sectionGithubUrl,
    array $documents
) use ($renderSectionIntro, $renderDocumentListItem): void {
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
                <?php $renderSectionIntro($sectionDescription, $sectionGithubUrl); ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($documents as $document): ?>
                        <?php if (!is_array($document)) { continue; } ?>
                        <?php $renderDocumentListItem($document, true); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
};
$renderSelectedDocumentContent = static function (
    string $selectedExcerpt,
    string $documentationSourceText,
    string $selectedHtml,
    bool $isSelectedCsv
) use ($renderAlertBlock): void {
    ?>
    <div class="card-body">
        <?php if ($selectedExcerpt !== ''): ?>
            <?php $renderAlertBlock('info', $selectedExcerpt); ?>
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
};
$documentationSourceText = $docsRoot !== ''
    ? 'Quelle: lokale Repository-Dokumentation unter ' . $docsRoot . '. Inhalt entspricht dem GitHub-Bereich /DOC des Projekts.'
    : 'Quelle: lokale Repository-Dokumentation. Inhalt entspricht dem GitHub-Bereich /DOC des Projekts.';
$metricCards = [
    ['label' => 'Dokumente', 'value' => (string) $docCount, 'class' => '', 'sub' => ''],
    ['label' => 'Bereiche', 'value' => (string) $sectionCount, 'class' => '', 'sub' => ''],
    ['label' => 'Quelle', 'value' => 'Repo /DOC', 'class' => '', 'sub' => ''],
    ['label' => 'Aktuell geladen', 'value' => $selectedPathLabel, 'class' => '', 'sub' => ''],
    ['label' => 'Sync-Status', 'value' => $syncLabel, 'class' => $syncClass, 'sub' => $repoRoot !== '' ? $repoRoot : 'Repo-Pfad unbekannt'],
];
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
            <?php $renderAlertBlock('danger', (string) $error, 'Dokumentation nicht verfügbar: ', 'Erwarteter Pfad: ' . $docsRoot); ?>
        <?php else: ?>
            <div class="row row-deck row-cards mb-4">
                <?php foreach ($metricCards as $metricCard): ?>
                    <?php $renderMetricCard($metricCard['label'], $metricCard['value'], $metricCard['class'], $metricCard['sub']); ?>
                <?php endforeach; ?>
            </div>

            <?php if ($syncMessage !== ''): ?>
                <?php $renderAlertBlock($syncAlertClass, $syncMessage, 'Synchronisationsmodus: ' . $syncMode, '', 'mb-4'); ?>
            <?php endif; ?>

            <div class="row row-cards">
                <div class="col-12 col-xl-4">
                    <div class="card mb-4">
                        <?php $renderCardHeaderTitle('Schnellstart'); ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($featuredDocs as $document): ?>
                                <?php if (!is_array($document)) { continue; } ?>
                                <?php $renderDocumentListItem($document); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="card">
                        <?php $renderCardHeaderTitle('Dokumentationsbereiche'); ?>
                        <div class="accordion accordion-flush" id="documentation-sections">
                            <?php foreach ($sections as $index => $section): ?>
                                <?php
                                if (!is_array($section)) {
                                    continue;
                                }

                                $sectionSlug = $sectionSlugValue($section, $index);
                                $documents = is_array($section['documents'] ?? null) ? $section['documents'] : [];
                                $sectionTitle = $sectionTitleValue($section, $sectionSlug);
                                $sectionDescription = $sectionDescriptionValue($section);
                                $sectionDocCount = $sectionDocCountValue($section, $documents);
                                $sectionActive = $findSectionActive($documents);
                                ?>
                                <?php $renderSectionAccordionItem($sectionSlug, $sectionTitle, $sectionDocCount, $sectionActive, $sectionDescription, $sectionGithubUrl($section), $documents); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <?php $renderCardHeaderTitle($selectedTitle, $selectedPath, true); ?>
                        <?php $renderSelectedDocumentContent($selectedExcerpt, $documentationSourceText, $selectedHtml, $isSelectedCsv); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
