<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Theme Explorer – Dateibaum + Code-Editor
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$themeSlug    = $data['themeSlug'] ?? '';
$files        = $data['files'] ?? [];
$currentFile  = $data['currentFile'] ?? '';
$fileContent  = $data['fileContent'] ?? '';
$fileLanguage = $data['fileLanguage'] ?? 'plaintext';
$fileWarning  = $data['fileWarning'] ?? null;
$fileMeta     = is_array($data['fileMeta'] ?? null) ? $data['fileMeta'] : [];
$treeSummary  = is_array($data['treeSummary'] ?? null) ? $data['treeSummary'] : ['items' => 0, 'skipped_items' => 0, 'warnings' => []];
$constraints  = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$fileEditable = !empty($fileMeta['is_editable']);
$themeExplorerBaseUrl = htmlspecialchars(SITE_URL . '/admin/theme-explorer', ENT_QUOTES);
$themeExplorerConfig = [
    'searchInputSelector' => '#themeExplorerSearch',
    'treeLinkSelector' => '[data-theme-explorer-path]',
    'treeFolderSelector' => '[data-theme-explorer-folder]',
    'editorSelector' => '#codeEditor',
    'formSelector' => '#themeExplorerForm',
    'saveButtonSelector' => '#themeExplorerSaveButton',
    'savePendingText' => 'Speichert …',
    'unsavedChangesMessage' => 'Es gibt ungespeicherte Änderungen im Theme-Explorer.',
];

$renderFileTree = static function (array $items, string $currentFile, string $baseUrl) use (&$renderFileTree): string {
    $html = '<ul class="list-unstyled mb-0">';
    foreach ($items as $item) {
        if (($item['type'] ?? '') === 'dir') {
            $html .= '<li class="mb-1">';
            $html .= '<div class="d-flex align-items-center py-1 px-2 text-muted fw-bold small">';
            $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l3 3h7a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2"/></svg>';
            $html .= htmlspecialchars((string)($item['name'] ?? ''));
            $html .= '</div>';
            if (!empty($item['children']) && is_array($item['children'])) {
                $html .= '<div class="ms-3">' . $renderFileTree($item['children'], $currentFile, $baseUrl) . '</div>';
            }
            $html .= '</li>';
            continue;
        }

        $path = (string)($item['path'] ?? '');
        $isActive = ($path === $currentFile);
        $html .= '<li>';
        $html .= '<a href="' . $baseUrl . '?file=' . rawurlencode($path) . '" class="d-flex align-items-center py-1 px-2 text-decoration-none rounded' . ($isActive ? ' bg-primary text-white' : ' text-body') . '">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-code me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M10 13l-1 2l1 2"/><path d="M14 13l1 2l-1 2"/></svg>';
        $html .= '<span class="small">' . htmlspecialchars((string)($item['name'] ?? '')) . '</span>';
        $html .= '</a>';
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
};
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Theme Explorer</h2>
            <div class="text-muted mt-1">Aktives Theme: <strong><?php echo htmlspecialchars($themeSlug); ?></strong></div>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php endif; ?>

    <?php if (!empty($fileWarning)): ?>
        <?php $alertData = ['type' => 'warning', 'message' => (string)$fileWarning]; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php endif; ?>

    <div class="row">
        <!-- File Tree -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <div class="w-100">
                        <h3 class="card-title mb-2">Dateien</h3>
                        <input type="search" class="form-control form-control-sm" id="themeExplorerSearch" placeholder="Dateien filtern …" maxlength="120" autocomplete="off" spellcheck="false">
                        <div class="small text-muted mt-2">
                            <?php echo (int) ($treeSummary['items'] ?? 0); ?> Einträge geladen
                            <?php if (!empty($treeSummary['skipped_items'])): ?>
                                · <?php echo (int) ($treeSummary['skipped_items'] ?? 0); ?> übersprungen
                            <?php endif; ?>
                            <?php if (!empty($constraints['tree_max_items'])): ?>
                                · Limit <?php echo (int) ($constraints['tree_max_items'] ?? 0); ?> Einträge
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body p-2" style="max-height: 70vh; overflow-y: auto;">
                    <?php if (!empty($treeSummary['warnings']) && is_array($treeSummary['warnings'])): ?>
                        <?php
                        $alertData = [
                            'type' => 'warning',
                            'message' => 'Der Dateibaum wurde mit Schutzgrenzen geladen.',
                            'details' => array_values(array_map(static fn (mixed $warning): string => (string) $warning, $treeSummary['warnings'])),
                        ];
                        $alertMarginClass = 'mb-3';
                        require __DIR__ . '/../partials/flash-alert.php';
                        ?>
                    <?php endif; ?>
                    <?php echo str_replace('<div class="d-flex align-items-center py-1 px-2 text-muted fw-bold small">', '<div class="d-flex align-items-center py-1 px-2 text-muted fw-bold small" data-theme-explorer-folder="1">', str_replace('<a href="', '<a data-theme-explorer-path="1" href="', $renderFileTree($files, (string) $currentFile, $themeExplorerBaseUrl))); ?>
                </div>
            </div>
        </div>

        <!-- Editor -->
        <div class="col-md-9">
            <?php if ($currentFile): ?>
                <form method="post" id="themeExplorerForm" data-unsaved-message="Es gibt ungespeicherte Änderungen im Theme-Explorer.">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_file">
                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($currentFile); ?>">

                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h3 class="card-title mb-1">
                                    <code><?php echo htmlspecialchars($currentFile); ?></code>
                                </h3>
                                <div class="d-flex flex-wrap gap-2 text-secondary small">
                                    <?php if (!empty($fileMeta['extension'])): ?>
                                        <span class="badge bg-secondary-lt text-secondary">.<?php echo htmlspecialchars((string) $fileMeta['extension']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($fileMeta['size_label'])): ?>
                                        <span><?php echo htmlspecialchars((string) $fileMeta['size_label']); ?></span>
                                    <?php endif; ?>
                                    <span class="badge <?php echo $fileEditable ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning'; ?>">
                                        <?php echo $fileEditable ? 'Bearbeitbar' : 'Nur eingeschränkt bearbeitbar'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-azure"><?php echo htmlspecialchars($fileLanguage); ?></span>
                                <button type="submit" id="themeExplorerSaveButton" class="btn btn-primary btn-sm" data-pending-text="Speichert …" <?php echo !$fileEditable ? 'disabled title="' . htmlspecialchars((string) ($fileMeta['save_disabled_reason'] ?? '')) . '"' : ''; ?>>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                                    Speichern
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($fileMeta['save_disabled_reason'])): ?>
                            <div class="card-alert alert alert-warning mb-0 rounded-0 border-0">
                                <?php echo htmlspecialchars((string) $fileMeta['save_disabled_reason']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="card-body p-0">
                            <textarea name="content" id="codeEditor"
                                      class="form-control font-monospace border-0 rounded-0"
                                      style="min-height: 60vh; resize: vertical; tab-size: 4; white-space: pre; overflow-wrap: normal; overflow-x: auto;"
                                      spellcheck="false"
                                      <?php echo !$fileEditable ? 'readonly' : ''; ?>><?php echo htmlspecialchars($fileContent); ?></textarea>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-code mb-3" width="48" height="48" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.3;"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/><path d="M10 13l-1 2l1 2"/><path d="M14 13l1 2l-1 2"/></svg>
                        <h3>Wähle eine Datei aus</h3>
                        <p class="text-muted">Klicke auf eine Datei im Dateibaum, um sie zu bearbeiten.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="application/json" id="theme-explorer-config"><?php echo htmlspecialchars((string) json_encode($themeExplorerConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_NOQUOTES, 'UTF-8'); ?></script>
