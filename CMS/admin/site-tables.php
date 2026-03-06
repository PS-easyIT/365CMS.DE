<?php
/**
 * Site Tables – Tabellen-Manager
 *
 * Spreadsheet-ähnliches Backend zum Erstellen und Verwalten von Tabellen mit
 * Import/Export (CSV, Excel, JSON, HTML), interaktiver Frontend-Darstellung
 * und Einbettung per Shortcode oder Widget.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!defined('ABSPATH')) { exit; }
if (!Auth::instance()->isAdmin()) { header('Location: ' . SITE_URL); exit; }

$db       = Database::instance();
$security = Security::instance();
$success  = '';
$error    = '';

// ─── Ensure DB table exists ────────────────────────────────────────────────
$db->execute("CREATE TABLE IF NOT EXISTS {$db->getPrefix()}site_tables (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_name    VARCHAR(120) NOT NULL,
    table_slug    VARCHAR(120) NOT NULL UNIQUE,
    description   TEXT,
    columns_json  LONGTEXT NOT NULL COMMENT 'JSON array of column definitions',
    rows_json     LONGTEXT NOT NULL COMMENT 'JSON array of row data',
    settings_json TEXT     NOT NULL COMMENT 'JSON settings per table',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Global default settings (stored per-table inside settings_json)
$defaultTableSettings = [
    // Frontend features
    'enable_search'          => true,
    'enable_sorting'         => true,
    'enable_pagination'      => true,
    'page_size'              => 10,
    'enable_filter_dropdowns'=> false,
    'enable_col_search'      => false,
    'enable_alphabet_search' => false,
    'search_highlight'       => true,
    'enable_search_panes'    => false,
    'auto_filter'            => false,
    // Layout
    'responsive'             => true,
    'fixed_header'           => false,
    'fixed_cols'             => 0,
    'row_grouping'           => false,
    'index_column'           => false,
    'counter_column'         => false,
    // Highlight
    'highlight_rows'         => false,
    'highlight_cols'         => false,
    // Order
    'default_sort_col'       => 0,
    'default_sort_dir'       => 'asc',
    'col_reorder'            => false,
    'row_reorder'            => false,
    // Style
    'style_theme'            => 'default', // default, stripe, hover, cell-border
    'custom_css'             => '',
    'user_action_buttons'    => false,
    // Math / Formulas
    'enable_formulas'        => false,
    // Accessibility
    'aria_label'             => '',
    'caption'                => '',
    // Export
    'allow_export_csv'       => true,
    'allow_export_json'      => false,
    'allow_export_excel'     => false,
    // Embed
    'shortcode'              => '',
];

function st_sanitize_text(string $value, int $maxLength = 255): string {
    return mb_substr(trim(strip_tags($value)), 0, $maxLength);
}

function st_sanitize_slug(string $value): string {
    $value = strtolower(trim($value));
    $value = (string) preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = trim($value, '-');
    return mb_substr($value, 0, 120);
}

function st_sanitize_json_array(string $json, array $fallback = []): array {
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function st_sanitize_table_columns(array $columns): array {
    $sanitized = [];
    foreach ($columns as $index => $column) {
        if (!is_array($column)) {
            continue;
        }

        $label = st_sanitize_text((string) ($column['label'] ?? ('Spalte ' . ($index + 1))), 120);
        if ($label === '') {
            $label = 'Spalte ' . ($index + 1);
        }

        $sanitized[] = [
            'label' => $label,
            'type' => 'text',
        ];
    }

    return $sanitized !== [] ? $sanitized : [['label' => 'Spalte 1', 'type' => 'text']];
}

function st_sanitize_table_rows(array $rows, array $columns): array {
    $sanitized = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $cleanRow = [];
        foreach ($columns as $index => $column) {
            $label = (string) ($column['label'] ?? ('Spalte ' . ($index + 1)));
            $value = $row[$label] ?? $row[$index] ?? '';
            if (is_array($value) || is_object($value)) {
                $value = '';
            }
            $cleanRow[$label] = mb_substr(trim((string) $value), 0, 5000);
        }
        $sanitized[] = $cleanRow;
    }

    return $sanitized;
}

function st_build_table_settings(array $post, array $defaults): array {
    $allowedThemes = ['default', 'stripe', 'hover', 'cell-border'];
    $settings = $defaults;

    foreach ($defaults as $key => $default) {
        if (is_bool($default)) {
            $settings[$key] = isset($post[$key]);
            continue;
        }

        $settings[$key] = $post[$key] ?? $default;
    }

    $settings['page_size'] = max(5, min(100, (int) ($post['page_size'] ?? $defaults['page_size'])));
    $settings['fixed_cols'] = max(0, min(20, (int) ($post['fixed_cols'] ?? $defaults['fixed_cols'])));
    $settings['default_sort_col'] = max(0, min(1000, (int) ($post['default_sort_col'] ?? $defaults['default_sort_col'])));
    $settings['default_sort_dir'] = (($post['default_sort_dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';
    $settings['style_theme'] = in_array((string) ($post['style_theme'] ?? ''), $allowedThemes, true)
        ? (string) $post['style_theme']
        : $defaults['style_theme'];
    $settings['custom_css'] = trim((string) ($post['custom_css'] ?? ''));
    $settings['aria_label'] = st_sanitize_text((string) ($post['aria_label'] ?? ''), 180);
    $settings['caption'] = st_sanitize_text((string) ($post['caption'] ?? ''), 180);
    $settings['shortcode'] = '';

    return $settings;
}

function st_get_unique_slug(Database $db, string $slug, int $excludeId = 0): string {
    $baseSlug = $slug !== '' ? $slug : 'tabelle';
    $uniqueSlug = $baseSlug;
    $suffix = 2;

    while (true) {
        $params = [$uniqueSlug];
        $sql = "SELECT id FROM {$db->getPrefix()}site_tables WHERE table_slug = ?";
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $existing = $db->fetchOne($sql . ' LIMIT 1', $params);
        if (!$existing) {
            return $uniqueSlug;
        }
        $uniqueSlug = $baseSlug . '-' . $suffix++;
    }
}

function st_send_export(string $tableName, string $format, array $columns, array $rows): void {
    $safeName = st_sanitize_slug($tableName) ?: 'site-table';

    if ($format === 'json') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '.json"');
        echo json_encode([
            'columns' => $columns,
            'rows' => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $safeName . '.csv"');
    $out = fopen('php://output', 'wb');
    if ($out === false) {
        exit;
    }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_map(fn(array $col): string => (string) ($col['label'] ?? ''), $columns), ';');
    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $index => $column) {
            $label = (string) ($column['label'] ?? ('Spalte ' . ($index + 1)));
            $line[] = (string) ($row[$label] ?? '');
        }
        fputcsv($out, $line, ';');
    }
    fclose($out);
    exit;
}

$globalSettingsKey = 'site_tables_default_settings';
$globalDefaultsRow = $db->fetchOne("SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1", [$globalSettingsKey]);
$storedGlobalDefaults = $globalDefaultsRow ? json_decode((string) ($globalDefaultsRow['option_value'] ?? '{}'), true) : [];
$globalTableSettings = is_array($storedGlobalDefaults)
    ? array_merge($defaultTableSettings, $storedGlobalDefaults)
    : $defaultTableSettings;

// ─── Action Routing ────────────────────────────────────────────────────────
$action    = $_GET['action'] ?? 'list';
$table_id  = (int) ($_GET['id'] ?? 0);

if ($action === 'export' && $table_id > 0) {
    $format = ($_GET['fmt'] ?? 'csv') === 'json' ? 'json' : 'csv';
    $exportTable = $db->fetchOne("SELECT table_name, columns_json, rows_json FROM {$db->getPrefix()}site_tables WHERE id = ? LIMIT 1", [$table_id]);
    if ($exportTable) {
        $columns = st_sanitize_table_columns(st_sanitize_json_array((string) ($exportTable['columns_json'] ?? '[]')));
        $rows = st_sanitize_table_rows(st_sanitize_json_array((string) ($exportTable['rows_json'] ?? '[]')), $columns);
        st_send_export((string) ($exportTable['table_name'] ?? 'site-table'), $format, $columns, $rows);
    }
    $error = 'Die angeforderte Tabelle wurde nicht gefunden.';
    $action = 'list';
}

// Save / create table
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'site_tables')) {
        $error = 'Sicherheitsprüfung fehlgeschlagen.';
    } else {

        if ($postAction === 'save_global_settings') {
            $globalTableSettings = st_build_table_settings($_POST, $defaultTableSettings);
            $globalJson = json_encode($globalTableSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $exists = $db->fetchOne("SELECT id FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1", [$globalSettingsKey]);
            if ($exists) {
                $db->execute("UPDATE {$db->getPrefix()}settings SET option_value = ? WHERE option_name = ?", [$globalJson, $globalSettingsKey]);
            } else {
                $db->execute("INSERT INTO {$db->getPrefix()}settings (option_name, option_value) VALUES (?, ?)", [$globalSettingsKey, $globalJson]);
            }
            $success = 'Globale Tabellen-Standards gespeichert.';
            $action = 'list';
        }

        if ($postAction === 'save_table') {
            $id = (int)($_POST['table_id'] ?? 0);
            $name = st_sanitize_text((string) ($_POST['table_name'] ?? ''), 120);
            $desc = st_sanitize_text((string) ($_POST['description'] ?? ''), 1000);
            $slug = st_sanitize_slug((string) ($_POST['table_slug'] ?? ''));
            if ($slug === '') {
                $slug = st_sanitize_slug($name);
            }
            $slug = st_get_unique_slug($db, $slug, $id);

            $columns = st_sanitize_table_columns(st_sanitize_json_array((string) ($_POST['columns_json'] ?? '[]')));
            $rows = st_sanitize_table_rows(st_sanitize_json_array((string) ($_POST['rows_json'] ?? '[]')), $columns);
            $ts = st_build_table_settings($_POST, $globalTableSettings);
            $settings_json = json_encode($ts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $cols_json = json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $rows_json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($name === '') {
                $error = 'Ein Tabellenname ist erforderlich.';
            } else {
                if ($id > 0) {
                    $db->execute("UPDATE {$db->getPrefix()}site_tables
                                  SET table_name=?,table_slug=?,description=?,columns_json=?,rows_json=?,settings_json=?
                                  WHERE id=?",
                        [$name,$slug,$desc,$cols_json,$rows_json,$settings_json,$id]);
                    $success = 'Tabelle gespeichert.';
                    $table_id = $id; $action = 'edit';
                } else {
                    $db->execute("INSERT INTO {$db->getPrefix()}site_tables (table_name,table_slug,description,columns_json,rows_json,settings_json)
                                  VALUES (?,?,?,?,?,?)",
                        [$name,$slug,$desc,$cols_json,$rows_json,$settings_json]);
                    $table_id = (int)$db->lastInsertId();
                    $success = 'Tabelle erstellt.';
                    $action = 'edit';
                }
            }
        }

        if ($postAction === 'delete_table') {
            $del_id = (int)($_POST['table_id'] ?? 0);
            if ($del_id > 0) {
                $db->execute("DELETE FROM {$db->getPrefix()}site_tables WHERE id=?", [$del_id]);
                $success = 'Tabelle gelöscht.';
            }
            $action = 'list';
        }

        // Import CSV/JSON
        if ($postAction === 'import_table' && isset($_FILES['import_file'])) {
            $uploadName = (string) ($_FILES['import_file']['name'] ?? '');
            $tmpName = (string) ($_FILES['import_file']['tmp_name'] ?? '');
            $ext  = strtolower(pathinfo($uploadName, PATHINFO_EXTENSION));
            $raw  = is_uploaded_file($tmpName) ? file_get_contents($tmpName) : false;
            $columns = [['label' => 'Spalte 1', 'type' => 'text']];
            $rowData = [];

            if ($raw === false || $raw === '') {
                $error = 'Die Importdatei konnte nicht gelesen werden.';
            } elseif ($ext === 'csv') {
                $lines = preg_split('/\r?\n/', trim((string) $raw));
                $headers = isset($lines[0]) ? str_getcsv((string) array_shift($lines), ';') : [];
                $headers = array_map(fn($header, $index) => st_sanitize_text((string) $header, 120) ?: ('Spalte ' . ($index + 1)), $headers, array_keys($headers));
                $columns = st_sanitize_table_columns(array_map(fn($header) => ['label' => $header, 'type' => 'text'], $headers));
                foreach (array_filter($lines, static fn($line) => trim((string) $line) !== '') as $line) {
                    $values = str_getcsv((string) $line, ';');
                    $row = [];
                    foreach ($columns as $index => $column) {
                        $row[$column['label']] = mb_substr(trim((string) ($values[$index] ?? '')), 0, 5000);
                    }
                    $rowData[] = $row;
                }
            } elseif ($ext === 'json') {
                $decoded = json_decode((string) $raw, true);
                if (is_array($decoded) && isset($decoded['columns'], $decoded['rows']) && is_array($decoded['columns']) && is_array($decoded['rows'])) {
                    $columns = st_sanitize_table_columns($decoded['columns']);
                    $rowData = st_sanitize_table_rows($decoded['rows'], $columns);
                } elseif (is_array($decoded)) {
                    $firstRow = $decoded[0] ?? [];
                    $columns = is_array($firstRow)
                        ? st_sanitize_table_columns(array_map(fn($key) => ['label' => (string) $key, 'type' => 'text'], array_keys($firstRow)))
                        : [['label' => 'Spalte 1', 'type' => 'text']];
                    $rowData = st_sanitize_table_rows($decoded, $columns);
                } else {
                    $error = 'Die JSON-Datei enthält kein unterstütztes Tabellenformat.';
                }
            } else {
                $error = 'Nur CSV- und JSON-Importe werden unterstützt.';
            }

            if ($error === '') {
                $name  = st_sanitize_text((string) pathinfo($uploadName, PATHINFO_FILENAME), 120);
                $name = $name !== '' ? $name : 'Importierte Tabelle';
                $slug  = st_get_unique_slug($db, st_sanitize_slug($name) . '-' . time());
                $db->execute("INSERT INTO {$db->getPrefix()}site_tables (table_name,table_slug,description,columns_json,rows_json,settings_json)
                              VALUES (?,?,?,?,?,?)",
                    [$name,$slug,'Importiert',json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),json_encode($rowData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),json_encode($globalTableSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                $table_id = (int)$db->lastInsertId();
                $success = 'Tabelle importiert.'; $action = 'edit';
            }
        }
    }
}

// Load table for edit view
$editTable = null;
if ($action === 'edit' && $table_id > 0) {
    $editTable = $db->fetchOne("SELECT * FROM {$db->getPrefix()}site_tables WHERE id=?", [$table_id]);
    if ($editTable) {
        $ts = array_merge($globalTableSettings, (array) json_decode($editTable['settings_json'] ?? '{}', true));
    }
}

// Load all tables for list view
$allTables = $db->fetchAll("SELECT id, table_name, table_slug, description, created_at, updated_at FROM {$db->getPrefix()}site_tables ORDER BY created_at DESC");

require_once __DIR__ . '/partials/admin-menu.php';

function st_chk(mixed $val, mixed $compare): string {
    if (is_array($compare)) return in_array((string)$val, array_map('strval', $compare)) ? 'checked' : '';
    return ((string)$val === (string)$compare) ? 'checked' : '';
}
function st_sel(mixed $val, mixed $compare): void {
    echo ((string)$val === (string)$compare) ? 'selected' : '';
}
?>
<?php renderAdminLayoutStart('Tabellen', 'site-tables'); ?>
        <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Tabellen erstellen, verwalten und per Shortcode oder Widget einbetten.</div>
                <h2 class="page-title">📊 Tabellen</h2>
            </div>
        </div>
    </div>

    <?php renderAdminAlerts(); ?>

    <?php if ($action === 'list'): ?>
    <!-- ════════════════════════════════════════════
         LIST VIEW
    ════════════════════════════════════════════ -->
    <div class="st-card">
        <div class="st-card-head">
            <span>Alle Tabellen (<?php echo count($allTables); ?>)</span>
            <div style="display:flex;gap:.75rem;align-items:center">
                <!-- Import -->
                <form method="post" enctype="multipart/form-data" style="display:flex;align-items:center;gap:.5rem">
                    <input type="hidden" name="action" value="import_table">
                    <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('site_tables'); ?>">
                    <input type="file" name="import_file" accept=".csv,.json" style="font-size:.85rem">
                    <button type="submit" class="btn btn-secondary" style="padding:.35rem .9rem;font-size:.85rem">⬆️ Import</button>
                </form>
                <a href="?action=edit" class="btn btn-primary" style="padding:.35rem .9rem;font-size:.85rem">+ Neue Tabelle</a>
            </div>
        </div>
        <div class="st-card-body" style="padding:0">
            <?php if (empty($allTables)): ?>
                <div class="empty-state">
                    <p style="font-size:2.5rem;margin:0;">📋</p>
                    <p><strong>Noch keine Tabellen vorhanden</strong></p>
                    <p class="text-muted">Erstelle deine erste Tabelle oder importiere CSV-/JSON-Daten.</p>
                    <a href="?action=edit" class="btn btn-primary" style="margin-top:1rem;">➕ Erste Tabelle erstellen</a>
                </div>
            <?php else: ?>
            <table class="table table-vcenter card-table st-tables-list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shortcode</th>
                        <th>Beschreibung</th>
                        <th>Erstellt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allTables as $t): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($t['table_name']); ?></strong></td>
                        <td><span class="badge-sc">[site-table id="<?php echo $t['id']; ?>"]</span></td>
                        <td style="color:#64748b"><?php echo htmlspecialchars($t['description'] ?: '—'); ?></td>
                        <td style="color:#94a3b8;font-size:.82rem"><?php echo date('d.m.Y', strtotime($t['created_at'])); ?></td>
                        <td>
                            <a href="?action=edit&id=<?php echo $t['id']; ?>" class="btn btn-secondary" style="padding:.25rem .7rem;font-size:.82rem">✏️ Bearbeiten</a>
                            <a href="?action=export&id=<?php echo $t['id']; ?>&fmt=csv" class="btn btn-secondary" style="padding:.25rem .7rem;font-size:.82rem">⬇️ CSV</a>
                            <form method="post" style="display:inline" class="js-needs-confirm"
                                  data-msg="Tabelle wirklich löschen? Alle darin gespeicherten Daten gehen unwiderruflich verloren.">
                                <input type="hidden" name="action" value="delete_table">
                                <input type="hidden" name="table_id" value="<?php echo $t['id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('site_tables'); ?>">
                                <button type="submit" class="btn btn-secondary" style="padding:.25rem .7rem;font-size:.82rem;color:#ef4444">🗑️</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Global Settings card -->
    <div class="st-card">
        <div class="st-card-head">⚙️ Globale Standardeinstellungen</div>
        <div class="st-card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="save_global_settings">
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('site_tables'); ?>">
                <p style="color:#64748b;margin-top:0">Diese Werte werden als Standard für alle neuen Tabellen verwendet und können pro Tabelle überschrieben werden.</p>
                <div class="st-row">
                    <label>Responsive</label>
                    <label class="st-chk-list"><input type="checkbox" name="responsive" value="1" <?php echo st_chk('1', $globalTableSettings['responsive'] ? '1':'0'); ?>> Auf allen Bildschirmgrößen optimiert darstellen</label>
                </div>
                <div class="st-row">
                    <label>Interaktivität</label>
                    <div class="st-chk-list">
                        <label><input type="checkbox" name="enable_search" value="1" <?php echo st_chk('1', $globalTableSettings['enable_search'] ? '1':'0'); ?>> Suche aktivieren</label>
                        <label><input type="checkbox" name="enable_sorting" value="1" <?php echo st_chk('1', $globalTableSettings['enable_sorting'] ? '1':'0'); ?>> Sortierung aktivieren</label>
                        <label><input type="checkbox" name="enable_pagination" value="1" <?php echo st_chk('1', $globalTableSettings['enable_pagination'] ? '1':'0'); ?>> Pagination aktivieren</label>
                        <label><input type="checkbox" name="search_highlight" value="1" <?php echo st_chk('1', $globalTableSettings['search_highlight'] ? '1':'0'); ?>> Suchtreffer hervorheben</label>
                    </div>
                </div>
                <div class="st-row">
                    <label>Standard Seitengröße</label>
                    <select name="page_size" class="form-select" style="max-width:120px">
                        <?php foreach ([5,10,25,50,100] as $ps): ?>
                            <option value="<?php echo $ps; ?>" <?php st_sel($ps, $globalTableSettings['page_size']); ?>><?php echo $ps; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="st-row">
                    <label>Design-Theme</label>
                    <select name="style_theme" class="form-select" style="max-width:200px">
                        <option value="default" <?php st_sel('default', $globalTableSettings['style_theme']); ?>>Standard</option>
                        <option value="stripe" <?php st_sel('stripe', $globalTableSettings['style_theme']); ?>>Gestreift</option>
                        <option value="hover" <?php st_sel('hover', $globalTableSettings['style_theme']); ?>>Hover-Highlight</option>
                        <option value="cell-border" <?php st_sel('cell-border', $globalTableSettings['style_theme']); ?>>Zellrahmen</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:.5rem">Globale Standards speichern</button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- ════════════════════════════════════════════
         EDIT / CREATE VIEW
    ════════════════════════════════════════════ -->
    <?php
        $editCols = $editTable ? json_decode($editTable['columns_json'] ?? '[]', true) : [['label'=>'Spalte 1','type'=>'text']];
        $editRows = $editTable ? json_decode($editTable['rows_json']    ?? '[]', true) : [];
        $ts       = $editTable ? array_merge($globalTableSettings, (array)json_decode($editTable['settings_json'] ?? '{}', true)) : $globalTableSettings;
    ?>
    <div style="margin-bottom:1rem">
        <a href="?action=list" style="color:#64748b;text-decoration:none">← Zurück zur Liste</a>
    </div>

    <form method="post" id="tableForm" action="">
        <input type="hidden" name="action" value="save_table">
        <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('site_tables'); ?>">
        <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
        <input type="hidden" name="columns_json" id="columns_json" value="<?php echo htmlspecialchars($editTable['columns_json'] ?? '[]'); ?>">
        <input type="hidden" name="rows_json" id="rows_json" value="<?php echo htmlspecialchars($editTable['rows_json'] ?? '[]'); ?>">

        <!-- Meta -->
        <div class="st-card">
            <div class="st-card-head">📋 <?php echo $table_id ? 'Tabelle bearbeiten' : 'Neue Tabelle erstellen'; ?></div>
            <div class="st-card-body">
                <div class="st-row">
                    <label>Name *</label>
                    <input type="text" name="table_name" class="form-control" required
                           value="<?php echo htmlspecialchars($editTable['table_name'] ?? ''); ?>"
                           placeholder="Meine Tabelle"
                           oninput="this.form.table_slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-')">
                </div>
                <div class="st-row">
                    <div><label>Slug</label><span class="st-hint">Für internen Zugriff / Shortcode.</span></div>
                    <input type="text" name="table_slug" class="form-control"
                           value="<?php echo htmlspecialchars($editTable['table_slug'] ?? ''); ?>"
                           placeholder="meine-tabelle">
                </div>
                <div class="st-row">
                    <label>Beschreibung</label>
                    <input type="text" name="description" class="form-control"
                           value="<?php echo htmlspecialchars($editTable['description'] ?? ''); ?>">
                </div>
                <?php if ($table_id): ?>
                <div class="st-row">
                    <label>Shortcode</label>
                    <code style="background:#f1f5f9;padding:.4rem .8rem;border-radius:4px">[site-table id="<?php echo $table_id; ?>"]</code>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Spreadsheet builder -->
        <div class="st-card">
            <div class="st-card-head">
                <span>📝 Tabelleninhalt</span>
                <div style="display:flex;gap:.5rem">
                    <button type="button" class="btn btn-secondary" style="padding:.3rem .8rem;font-size:.85rem" onclick="addColumn()">+ Spalte</button>
                    <button type="button" class="btn btn-secondary" style="padding:.3rem .8rem;font-size:.85rem" onclick="addRow()">+ Zeile</button>
                </div>
            </div>
            <div class="st-card-body">
                <div class="sheet-wrap">
                    <table class="sheet-table" id="sheetTable">
                        <thead id="sheetHead">
                            <tr id="colHeaderRow">
                                <?php foreach ($editCols as $ci => $col): ?>
                                <th class="col-head">
                                    <input type="text" value="<?php echo htmlspecialchars($col['label'] ?? ''); ?>"
                                           data-ci="<?php echo $ci; ?>" class="col-name-input"
                                           placeholder="Spalte <?php echo $ci+1; ?>">
                                    <button type="button" class="row-del" onclick="deleteColumn(<?php echo $ci; ?>)">✕</button>
                                </th>
                                <?php endforeach; ?>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="sheetBody">
                            <?php if (empty($editRows)): ?>
                            <tr data-ri="0">
                                <?php foreach ($editCols as $ci => $col): ?>
                                    <td><input type="text" data-ri="0" data-ci="<?php echo $ci; ?>" class="cell-input" value=""></td>
                                <?php endforeach; ?>
                                <td style="text-align:center"><button type="button" class="row-del" onclick="deleteRow(this)">✕</button></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($editRows as $ri => $rowData): ?>
                                <tr data-ri="<?php echo $ri; ?>">
                                    <?php foreach ($editCols as $ci => $col): ?>
                                        <td><input type="text" data-ri="<?php echo $ri; ?>" data-ci="<?php echo $ci; ?>" class="cell-input"
                                                   value="<?php echo htmlspecialchars($rowData[$col['label']] ?? $rowData[$ci] ?? ''); ?>"></td>
                                    <?php endforeach; ?>
                                    <td style="text-align:center"><button type="button" class="row-del" onclick="deleteRow(this)">✕</button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p style="font-size:.82rem;color:#94a3b8;margin-top:.5rem">Formeln werden mit <code>=SUMME(A1:A10)</code> eingegeben (wie in Excel).</p>
            </div>
        </div>

        <!-- Settings Tabs -->
        <div class="st-card">
            <div class="st-card-head">⚙️ Tabellen-Einstellungen</div>
            <div class="st-card-body">
                <div class="st-tabs">
                    <button type="button" class="st-tab active" onclick="stSwitchTab('frontend',this)">🌐 Frontend</button>
                    <button type="button" class="st-tab" onclick="stSwitchTab('design',this)">🎨 Design</button>
                    <button type="button" class="st-tab" onclick="stSwitchTab('columns',this)">📐 Spalten & Zeilen</button>
                    <button type="button" class="st-tab" onclick="stSwitchTab('accessibility',this)">♿ Barrierefreiheit</button>
                    <button type="button" class="st-tab" onclick="stSwitchTab('export',this)">⬇️ Export</button>
                </div>

                <!-- Frontend Tab -->
                <div id="tab-frontend" class="st-tab-panel active">
                    <div class="st-row">
                        <label>Interaktivität</label>
                        <div class="st-chk-list">
                            <label><input type="checkbox" name="enable_search" value="1" <?php echo st_chk('1', $ts['enable_search'] ? '1':'0'); ?>> Suche aktivieren</label>
                            <label><input type="checkbox" name="enable_sorting" value="1" <?php echo st_chk('1', $ts['enable_sorting'] ? '1':'0'); ?>> Sortierung aktivieren</label>
                            <label><input type="checkbox" name="enable_pagination" value="1" <?php echo st_chk('1', $ts['enable_pagination'] ? '1':'0'); ?>> Pagination aktivieren</label>
                            <label><input type="checkbox" name="search_highlight" value="1" <?php echo st_chk('1', $ts['search_highlight'] ? '1':'0'); ?>> Suchtreffer hervorheben</label>
                        </div>
                    </div>
                    <div class="st-row">
                        <label>Seitengröße</label>
                        <select name="page_size" class="form-select" style="max-width:120px">
                            <?php foreach ([5,10,25,50,100] as $ps): ?>
                                <option value="<?php echo $ps; ?>" <?php st_sel($ps, $ts['page_size']); ?>><?php echo $ps; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="st-row">
                        <label>Filter & Suche</label>
                        <div class="st-chk-list">
                            <label><input type="checkbox" name="enable_filter_dropdowns" value="1" <?php echo st_chk('1', $ts['enable_filter_dropdowns'] ? '1':'0'); ?>> Filter-Dropdowns</label>
                            <label><input type="checkbox" name="enable_col_search" value="1" <?php echo st_chk('1', $ts['enable_col_search'] ? '1':'0'); ?>> Spaltenweise Suche</label>
                            <label><input type="checkbox" name="enable_alphabet_search" value="1" <?php echo st_chk('1', $ts['enable_alphabet_search'] ? '1':'0'); ?>> Alphabet-Suche</label>
                            <label><input type="checkbox" name="enable_search_panes" value="1" <?php echo st_chk('1', $ts['enable_search_panes'] ? '1':'0'); ?>> Search Panes</label>
                            <label><input type="checkbox" name="auto_filter" value="1" <?php echo st_chk('1', $ts['auto_filter'] ? '1':'0'); ?>> Automatisches Filtern</label>
                        </div>
                    </div>
                    <div class="st-row">
                        <label>Sortierung</label>
                        <div style="display:flex;gap:1rem;align-items:center">
                            <label style="font-weight:normal">Standard Spalte:
                                <input type="number" name="default_sort_col" value="<?php echo (int)$ts['default_sort_col']; ?>" style="width:60px;padding:4px" min="0">
                            </label>
                            <select name="default_sort_dir" class="form-select" style="max-width:120px">
                                <option value="asc" <?php st_sel('asc', $ts['default_sort_dir']); ?>>Aufsteigend</option>
                                <option value="desc" <?php st_sel('desc', $ts['default_sort_dir']); ?>>Absteigend</option>
                            </select>
                            <label style="font-weight:normal"><input type="checkbox" name="col_reorder" value="1" <?php echo st_chk('1', $ts['col_reorder'] ? '1':'0'); ?>> Spalten verschieben</label>
                            <label style="font-weight:normal"><input type="checkbox" name="row_reorder" value="1" <?php echo st_chk('1', $ts['row_reorder'] ? '1':'0'); ?>> Zeilen verschieben</label>
                        </div>
                    </div>
                    <div class="st-row">
                        <label>Aktions-Buttons</label>
                        <label class="st-chk-list">
                            <input type="checkbox" name="user_action_buttons" value="1" <?php echo st_chk('1', $ts['user_action_buttons'] ? '1':'0'); ?>>
                            Benutzer-Aktionsschaltflächen aktivieren
                        </label>
                    </div>
                    <div class="st-row">
                        <label>Formeln</label>
                        <label class="st-chk-list">
                            <input type="checkbox" name="enable_formulas" value="1" <?php echo st_chk('1', $ts['enable_formulas'] ? '1':'0'); ?>>
                            Excel-ähnliche Formeln aktivieren (<code>=SUMME</code>, <code>=MITTELWERT</code> …)
                        </label>
                    </div>
                </div>

                <!-- Design Tab -->
                <div id="tab-design" class="st-tab-panel">
                    <div class="st-row">
                        <label>Responsive</label>
                        <label class="st-chk-list">
                            <input type="checkbox" name="responsive" value="1" <?php echo st_chk('1', $ts['responsive'] ? '1':'0'); ?>>
                            Auf allen Bildschirmgrößen optimiert
                        </label>
                    </div>
                    <div class="st-row">
                        <label>Style-Theme</label>
                        <select name="style_theme" class="form-select" style="max-width:200px">
                            <option value="default" <?php st_sel('default', $ts['style_theme']); ?>>Standard</option>
                            <option value="stripe"  <?php st_sel('stripe',  $ts['style_theme']); ?>>Gestreift</option>
                            <option value="hover"   <?php st_sel('hover',   $ts['style_theme']); ?>>Hover-Highlight</option>
                            <option value="cell-border" <?php st_sel('cell-border', $ts['style_theme']); ?>>Zellrahmen</option>
                        </select>
                    </div>
                    <div class="st-row">
                        <label>Hervorhebung</label>
                        <div class="st-chk-list">
                            <label><input type="checkbox" name="highlight_rows" value="1" <?php echo st_chk('1', $ts['highlight_rows'] ? '1':'0'); ?>> Zeilenhervorhebung bei Hover</label>
                            <label><input type="checkbox" name="highlight_cols" value="1" <?php echo st_chk('1', $ts['highlight_cols'] ? '1':'0'); ?>> Spaltenhervorhebung bei Hover</label>
                        </div>
                    </div>
                    <div class="st-row">
                        <div><label>Benutzerdefiniertes CSS</label><span class="st-hint">Wird direkt in den Style-Block der Tabelle eingefügt.</span></div>
                        <textarea name="custom_css" class="form-control" rows="5" style="font-family:monospace;font-size:.9rem"><?php echo htmlspecialchars($ts['custom_css']); ?></textarea>
                    </div>
                </div>

                <!-- Columns & Rows Tab -->
                <div id="tab-columns" class="st-tab-panel">
                    <div class="st-row">
                        <label>Fixierte Zeilen/Spalten</label>
                        <div style="display:flex;gap:1.5rem;align-items:center">
                            <label style="font-weight:normal"><input type="checkbox" name="fixed_header" value="1" <?php echo st_chk('1', $ts['fixed_header'] ? '1':'0'); ?>> Header fixieren</label>
                            <label style="font-weight:normal">Fixierte Spalten:
                                <input type="number" name="fixed_cols" value="<?php echo (int)$ts['fixed_cols']; ?>" style="width:60px;padding:4px" min="0">
                            </label>
                        </div>
                    </div>
                    <div class="st-row">
                        <label>Zeilen- & Indexspalten</label>
                        <div class="st-chk-list">
                            <label><input type="checkbox" name="row_grouping" value="1" <?php echo st_chk('1', $ts['row_grouping'] ? '1':'0'); ?>> Zeilen gruppieren</label>
                            <label><input type="checkbox" name="index_column" value="1" <?php echo st_chk('1', $ts['index_column'] ? '1':'0'); ?>> Index-Spalte anzeigen</label>
                            <label><input type="checkbox" name="counter_column" value="1" <?php echo st_chk('1', $ts['counter_column'] ? '1':'0'); ?>> Zähler-Spalte anzeigen</label>
                        </div>
                    </div>
                </div>

                <!-- Accessibility Tab -->
                <div id="tab-accessibility" class="st-tab-panel">
                    <div class="st-row">
                        <div><label>ARIA-Label</label><span class="st-hint">Screenreader-Beschriftung der Tabelle.</span></div>
                        <input type="text" name="aria_label" class="form-control"
                               value="<?php echo htmlspecialchars($ts['aria_label']); ?>"
                               placeholder="Tabelle: Produktvergleich">
                    </div>
                    <div class="st-row">
                        <div><label>Caption</label><span class="st-hint">Sichtbarer &lt;caption&gt;-Text unter der Tabelle.</span></div>
                        <input type="text" name="caption" class="form-control"
                               value="<?php echo htmlspecialchars($ts['caption']); ?>"
                               placeholder="Tabelle 1: Produktdaten Q1/2026">
                    </div>
                    <p style="color:#64748b;font-size:.88rem;margin:0">
                        Tabellen werden mit semantischem HTML ausgegeben: <code>&lt;table&gt;</code>, <code>&lt;thead&gt;</code>, <code>&lt;th scope&gt;</code>, <code>role="grid"</code> und <code>aria-*</code>-Attributen nach WCAG 2.2.
                    </p>
                </div>

                <!-- Export Tab -->
                <div id="tab-export" class="st-tab-panel">
                    <div class="st-row">
                        <label>Frontend-Export erlauben</label>
                        <div class="st-chk-list">
                            <label><input type="checkbox" name="allow_export_csv" value="1" <?php echo st_chk('1', $ts['allow_export_csv'] ? '1':'0'); ?>> CSV-Download für Besucher</label>
                            <label><input type="checkbox" name="allow_export_json" value="1" <?php echo st_chk('1', $ts['allow_export_json'] ? '1':'0'); ?>> JSON-Download für Besucher</label>
                            <label><input type="checkbox" name="allow_export_excel" value="1" <?php echo st_chk('1', $ts['allow_export_excel'] ? '1':'0'); ?>> Excel-Download für Besucher</label>
                        </div>
                    </div>
                    <?php if ($table_id): ?>
                    <div class="st-row">
                        <label>Admin-Export</label>
                        <div style="display:flex;gap:.75rem">
                            <a href="?action=export&id=<?php echo $table_id; ?>&fmt=csv" class="btn btn-secondary">⬇️ Als CSV</a>
                            <a href="?action=export&id=<?php echo $table_id; ?>&fmt=json" class="btn btn-secondary">⬇️ Als JSON</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="padding-bottom:3rem;display:flex;gap:1rem;align-items:center">
            <button type="submit" class="btn btn-primary" style="padding:.75rem 2rem;font-size:1.05rem">Tabelle speichern</button>
            <a href="?action=list" class="btn btn-secondary" style="padding:.75rem 1.5rem">Abbrechen</a>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
// ─── Spreadsheet Builder ─────────────────────────────────────────
function getColumns() {
    return Array.from(document.querySelectorAll('#colHeaderRow .col-name-input'))
        .map(i => ({ label: i.value.trim() || 'Spalte', type: 'text' }));
}
function getRows() {
    const rows = [];
    document.querySelectorAll('#sheetBody tr').forEach(tr => {
        const row = {};
        const cols = getColumns();
        tr.querySelectorAll('.cell-input').forEach((inp, ci) => {
            row[cols[ci]?.label ?? ci] = inp.value;
        });
        rows.push(row);
    });
    return rows;
}
function serializeTable() {
    document.getElementById('columns_json').value = JSON.stringify(getColumns());
    document.getElementById('rows_json').value    = JSON.stringify(getRows());
    return true;
}
document.getElementById('tableForm')?.addEventListener('submit', serializeTable);

function addColumn() {
    const cols = getColumns();
    const newIdx = cols.length;
    const th = document.createElement('th');
    th.className = 'col-head';
    th.innerHTML = `<input type="text" class="col-name-input" data-ci="${newIdx}" placeholder="Spalte ${newIdx+1}">
                    <button type="button" class="row-del" onclick="deleteColumn(${newIdx})">✕</button>`;
    document.querySelector('#colHeaderRow th:last-child').before(th);
    document.querySelectorAll('#sheetBody tr').forEach((tr, ri) => {
        const td = document.createElement('td');
        td.innerHTML = `<input type="text" class="cell-input" data-ri="${ri}" data-ci="${newIdx}" value="">`;
        tr.querySelector('td:last-child').before(td);
    });
}
function deleteColumn(ci) {
    document.querySelectorAll(`#colHeaderRow th`)[ci]?.remove();
    document.querySelectorAll('#sheetBody tr').forEach(tr => {
        tr.querySelectorAll('td')[ci]?.remove();
    });
}
function addRow() {
    const cols = getColumns();
    const ri = document.querySelectorAll('#sheetBody tr').length;
    const tr = document.createElement('tr');
    tr.dataset.ri = ri;
    cols.forEach((_, ci) => {
        tr.innerHTML += `<td><input type="text" class="cell-input" data-ri="${ri}" data-ci="${ci}" value=""></td>`;
    });
    tr.innerHTML += `<td style="text-align:center"><button type="button" class="row-del" onclick="deleteRow(this)">✕</button></td>`;
    document.getElementById('sheetBody').appendChild(tr);
}
function deleteRow(btn) {
    btn.closest('tr').remove();
}

// ─── Tab switcher ─────────────────────────────────────────────────
function stSwitchTab(id, btn) {
    document.querySelectorAll('.st-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.st-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id)?.classList.add('active');
    btn.classList.add('active');
}
</script>
<!-- .js-needs-confirm → globales cmsConfirm()-Modal aus admin-menu.php -->
<script>
(function(){
    document.querySelectorAll('.js-needs-confirm').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var f = form;
            cmsConfirm(f.dataset.msg || 'Wirklich fortfahren?', function(){ f.submit(); });
        });
    });
})();
</script><?php renderAdminLayoutEnd(); ?>
