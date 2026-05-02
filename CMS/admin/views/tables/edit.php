<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tables – Edit / Create View
 *
 * Erwartet: $data (aus TablesModule::getEditData())
 *           $csrfToken
 */

$table    = $data['table'] ?? null;
$isNew    = $data['isNew'] ?? true;
$defaults = $data['defaults'] ?? [];
$editorSummary = is_array($data['editorSummary'] ?? null) ? $data['editorSummary'] : ['columns' => 0, 'rows' => 0, 'cells' => 0];
$editorConfigJson = (string) ($data['editorConfigJson'] ?? '{"columns":[],"rows":[],"limits":{}}');
$editorLimits = is_array($data['editorLimits'] ?? null) ? $data['editorLimits'] : [];

$tableName   = htmlspecialchars($table['table_name'] ?? '');
$description = htmlspecialchars($table['description'] ?? '');
$columns     = $table['columns'] ?? [];
$rows        = $table['rows'] ?? [];
$settings    = $table['settings'] ?? $defaults;
$contentSource = is_array($data['contentSource'] ?? null) ? $data['contentSource'] : ['sourceOptions' => [], 'fieldOptions' => []];
$contentSourceEnabled = !empty($settings['content_source_enabled']);
$contentSourceTypes = is_array($settings['content_source_types'] ?? null) ? $settings['content_source_types'] : ['pages', 'posts'];
$contentSourceFields = is_array($settings['content_source_fields'] ?? null) ? $settings['content_source_fields'] : ['type', 'title', 'url', 'status', 'published_at', 'updated_at'];
$siteTablesBaseUrl = '/admin/site-tables';
$siteTablesSettingsUrl = $siteTablesBaseUrl . '?action=settings';
$encodeTableJson = static function (mixed $value): string {
    return (string) json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars($siteTablesBaseUrl); ?>" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Tabellen</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neue Tabelle' : 'Tabelle bearbeiten'; ?></h2>
            </div>
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars($siteTablesSettingsUrl); ?>" class="btn btn-outline-secondary btn-sm">
                    Einstellungen
                </a>
            </div>
            <?php if (!$isNew): ?>
                <div class="col-auto">
                    <span class="badge bg-azure-lt">Shortcodes: [site-table id="<?php echo (int)$table['id']; ?>"] · [table id=<?php echo (int)$table['id']; ?> /]</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="post" id="tableForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)$table['id']; ?>">
            <?php endif; ?>

            <div class="row">
                <!-- Hauptbereich -->
                <div class="col-lg-8">

                    <!-- Name & Beschreibung -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required" for="table_name">Tabellenname</label>
                                <input type="text" class="form-control" id="table_name" name="table_name" value="<?php echo $tableName; ?>" maxlength="150" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="description">Beschreibung</label>
                                <textarea class="form-control" id="description" name="description" rows="2" maxlength="1000"><?php echo $description; ?></textarea>
                            </div>
                            <div class="form-hint mt-2">
                                <?php echo (int) ($editorSummary['columns'] ?? 0); ?> Spalten · <?php echo (int) ($editorSummary['rows'] ?? 0); ?> Zeilen · <?php echo (int) ($editorSummary['cells'] ?? 0); ?> Zellen im aktuellen Editorzustand
                            </div>
                        </div>
                    </div>

                    <!-- Spalten-Editor -->
                    <div class="card mb-3" data-manual-table-editor>
                        <div class="card-header">
                            <h3 class="card-title">Spalten</h3>
                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addColumn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                                    Spalte hinzufügen
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table mb-0" id="columnsTable">
                                    <thead>
                                        <tr>
                                            <th>Label</th>
                                            <th class="w-1"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="columnsBody">
                                    <!-- JS-befüllt -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Zeilen-Editor -->
                    <div class="card mb-3" data-manual-table-editor>
                        <div class="card-header">
                            <h3 class="card-title">Daten</h3>
                            <div class="card-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addRow">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                                    Zeile hinzufügen
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table mb-0" id="rowsTable">
                                    <thead id="rowsHead">
                                        <tr></tr>
                                    </thead>
                                    <tbody id="rowsBody">
                                    <!-- JS-befüllt -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center py-3 text-secondary d-none" id="noRowsHint">
                                Keine Zeilen. Füge zuerst Spalten hinzu, dann Zeilen.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sidebar (Einstellungen) -->
                <div class="col-lg-4">

                    <!-- Darstellung -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Einstellungen</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 pb-3 border-bottom">
                                <label class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="contentSourceEnabled" name="setting_content_source_enabled" value="1" <?php echo $contentSourceEnabled ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Als Site Table aus Seiten/Beiträgen verwenden</span>
                                </label>
                                <div class="form-hint mb-3">
                                    Aktiviert eine dynamische Tabelle aus veröffentlichten Seiten und/oder Beiträgen. Freie Spalten- und Zelleneingaben sind dann deaktiviert.
                                </div>

                                <div id="contentSourceOptions" class="<?php echo $contentSourceEnabled ? '' : 'd-none'; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Quellen</label>
                                        <?php foreach (($contentSource['sourceOptions'] ?? []) as $sourceKey => $sourceLabel): ?>
                                            <label class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" name="content_source_types[]" value="<?php echo htmlspecialchars((string)$sourceKey, ENT_QUOTES); ?>" <?php echo in_array((string)$sourceKey, $contentSourceTypes, true) ? 'checked' : ''; ?>>
                                                <span class="form-check-label"><?php echo htmlspecialchars((string)$sourceLabel); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Spalten aus Seiten/Beiträgen</label>
                                        <?php foreach (($contentSource['fieldOptions'] ?? []) as $fieldKey => $field): ?>
                                            <label class="form-check mb-1">
                                                <input class="form-check-input" type="checkbox" name="content_source_fields[]" value="<?php echo htmlspecialchars((string)$fieldKey, ENT_QUOTES); ?>" <?php echo in_array((string)$fieldKey, $contentSourceFields, true) ? 'checked' : ''; ?>>
                                                <span class="form-check-label"><?php echo htmlspecialchars((string)($field['label'] ?? $fieldKey)); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="setting_style_theme">Stil</label>
                                <select class="form-select" id="setting_style_theme" name="setting_style_theme">
                                    <?php foreach (($data['styleOptions'] ?? []) as $val => $style): ?>
                                        <option value="<?php echo htmlspecialchars((string)$val); ?>" <?php echo ($settings['style_theme'] ?? '') === $val ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($style['label'] ?? $val)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-hint">Verfügbare Stile werden zentral unter Tabellen → Einstellungen gesteuert.</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="setting_page_size">Zeilen pro Seite</label>
                                <input type="number" class="form-control" id="setting_page_size" name="setting_page_size" min="5" max="100" value="<?php echo (int)($settings['page_size'] ?? 10); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="setting_caption">Tabellenunterschrift</label>
                                <input type="text" class="form-control" id="setting_caption" name="setting_caption" value="<?php echo htmlspecialchars($settings['caption'] ?? ''); ?>" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="setting_aria_label">ARIA Label</label>
                                <input type="text" class="form-control" id="setting_aria_label" name="setting_aria_label" value="<?php echo htmlspecialchars($settings['aria_label'] ?? ''); ?>" maxlength="255">
                            </div>
                            <div class="form-hint mb-3">
                                Editor-Limits: max. <?php echo (int) ($editorLimits['maxColumns'] ?? 25); ?> Spalten, <?php echo (int) ($editorLimits['maxRows'] ?? 250); ?> Zeilen und <?php echo (int) ($editorLimits['maxCellLength'] ?? 5000); ?> Zeichen pro Zelle.
                            </div>

                            <?php
                            $boolSettings = [
                                'responsive'       => 'Responsive',
                                'enable_search'    => 'Suche aktivieren',
                                'enable_sorting'   => 'Sortierung aktivieren',
                                'enable_pagination' => 'Paginierung aktivieren',
                                'highlight_rows'   => 'Zeilen hervorheben',
                            ];
                            foreach ($boolSettings as $key => $label): ?>
                                <label class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="setting_<?php echo $key; ?>" value="1"
                                        <?php echo !empty($settings[$key]) ? 'checked' : ''; ?>>
                                    <span class="form-check-label"><?php echo $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Export -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Export</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            $exportSettings = [
                                'allow_export_csv'   => 'CSV-Export erlauben',
                                'allow_export_json'  => 'JSON-Export erlauben',
                                'allow_export_excel' => 'Excel-Export erlauben',
                            ];
                            foreach ($exportSettings as $key => $label): ?>
                                <label class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="setting_<?php echo $key; ?>" value="1"
                                        <?php echo !empty($settings[$key]) ? 'checked' : ''; ?>>
                                    <span class="form-check-label"><?php echo $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Speichern -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                                <?php echo $isNew ? 'Erstellen' : 'Aktualisieren'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden JSON-Felder (werden bei Submit befüllt) -->
            <input type="hidden" name="columns_json" id="columnsJsonInput" value="<?php echo htmlspecialchars($encodeTableJson($columns)); ?>">
            <input type="hidden" name="rows_json" id="rowsJsonInput" value="<?php echo htmlspecialchars($encodeTableJson($rows)); ?>">
        </form>
    </div>
</div>
<script type="application/json" id="site-tables-editor-config"><?php echo $editorConfigJson; ?></script>
