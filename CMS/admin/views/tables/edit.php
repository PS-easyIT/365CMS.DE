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

$tableName   = htmlspecialchars($table['table_name'] ?? '');
$description = htmlspecialchars($table['description'] ?? '');
$columns     = $table['columns'] ?? [];
$rows        = $table['rows'] ?? [];
$settings    = $table['settings'] ?? $defaults;
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Tabellen</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neue Tabelle' : 'Tabelle bearbeiten'; ?></h2>
            </div>
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables?action=settings" class="btn btn-outline-secondary btn-sm">
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
        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables" id="tableForm">
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
                                <input type="text" class="form-control" id="table_name" name="table_name" value="<?php echo $tableName; ?>" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="description">Beschreibung</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo $description; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Spalten-Editor -->
                    <div class="card mb-3">
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
                    <div class="card mb-3">
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
                                <input type="text" class="form-control" id="setting_caption" name="setting_caption" value="<?php echo htmlspecialchars($settings['caption'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="setting_aria_label">ARIA Label</label>
                                <input type="text" class="form-control" id="setting_aria_label" name="setting_aria_label" value="<?php echo htmlspecialchars($settings['aria_label'] ?? ''); ?>">
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
            <input type="hidden" name="columns_json" id="columnsJsonInput" value="<?php echo htmlspecialchars(json_encode($columns)); ?>">
            <input type="hidden" name="rows_json" id="rowsJsonInput" value="<?php echo htmlspecialchars(json_encode($rows)); ?>">
        </form>
    </div>
</div>

<script>
(function() {
    var columns = <?php echo json_encode($columns, JSON_UNESCAPED_UNICODE); ?>;
    var rows    = <?php echo json_encode($rows, JSON_UNESCAPED_UNICODE); ?>;

    var columnsBody = document.getElementById('columnsBody');
    var rowsHead    = document.getElementById('rowsHead').querySelector('tr');
    var rowsBody    = document.getElementById('rowsBody');
    var noRowsHint  = document.getElementById('noRowsHint');

    function renderColumns() {
        columnsBody.innerHTML = '';
        columns.forEach(function(col, i) {
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type="text" class="form-control form-control-sm" value="' + escapeAttr(col.label || '') + '" data-col-idx="' + i + '" onchange="window._tblUpdateColLabel(this)"></td>' +
                '<td><button type="button" class="btn btn-ghost-danger btn-icon btn-sm" onclick="window._tblRemoveCol(' + i + ')" title="Spalte entfernen">' +
                '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg></button></td>';
            columnsBody.appendChild(tr);
        });
        renderRowsHead();
        renderRows();
    }

    function renderRowsHead() {
        rowsHead.innerHTML = '';
        columns.forEach(function(col) {
            var th = document.createElement('th');
            th.textContent = col.label || '—';
            rowsHead.appendChild(th);
        });
        var thAction = document.createElement('th');
        thAction.className = 'w-1';
        rowsHead.appendChild(thAction);
    }

    function renderRows() {
        rowsBody.innerHTML = '';
        if (columns.length === 0 || rows.length === 0) {
            noRowsHint.classList.toggle('d-none', rows.length > 0);
            return;
        }
        noRowsHint.classList.add('d-none');
        rows.forEach(function(row, ri) {
            var tr = document.createElement('tr');
            columns.forEach(function(col) {
                var td = document.createElement('td');
                var input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = row[col.label] || '';
                input.dataset.rowIdx = ri;
                input.dataset.colLabel = col.label;
                input.onchange = function() { rows[ri][col.label] = this.value; };
                td.appendChild(input);
                tr.appendChild(td);
            });
            var tdAction = document.createElement('td');
            tdAction.innerHTML = '<button type="button" class="btn btn-ghost-danger btn-icon btn-sm" onclick="window._tblRemoveRow(' + ri + ')">' +
                '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg></button>';
            tr.appendChild(tdAction);
            rowsBody.appendChild(tr);
        });
    }

    function escapeAttr(s) {
        return s.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Public API
    window._tblUpdateColLabel = function(input) {
        var idx = parseInt(input.dataset.colIdx, 10);
        var oldLabel = columns[idx].label;
        var newLabel = input.value.trim() || 'Spalte ' + (idx+1);
        columns[idx].label = newLabel;
        // Zeilen-Keys umbenennen
        rows.forEach(function(row) {
            if (oldLabel !== newLabel && row.hasOwnProperty(oldLabel)) {
                row[newLabel] = row[oldLabel];
                delete row[oldLabel];
            }
        });
        renderRowsHead();
        renderRows();
    };

    window._tblRemoveCol = function(idx) {
        var label = columns[idx].label;
        columns.splice(idx, 1);
        rows.forEach(function(row) { delete row[label]; });
        renderColumns();
    };

    window._tblRemoveRow = function(idx) {
        rows.splice(idx, 1);
        renderRows();
    };

    document.getElementById('addColumn').addEventListener('click', function() {
        var label = 'Spalte ' + (columns.length + 1);
        columns.push({ label: label, type: 'text' });
        renderColumns();
    });

    document.getElementById('addRow').addEventListener('click', function() {
        if (columns.length === 0) return;
        var row = {};
        columns.forEach(function(col) { row[col.label] = ''; });
        rows.push(row);
        renderRows();
    });

    // Vor Submit JSON serialisieren
    document.getElementById('tableForm').addEventListener('submit', function() {
        document.getElementById('columnsJsonInput').value = JSON.stringify(columns);
        document.getElementById('rowsJsonInput').value = JSON.stringify(rows);
    });

    // Initial rendern
    renderColumns();
})();
</script>
