<?php
declare(strict_types=1);

/**
 * Tables Module – CRUD-Logik für Site-Tabellen
 *
 * Arbeitet direkt mit der cms_site_tables Tabelle.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class TablesModule
{
    private Database $db;
    private string $prefix;

    private const DEFAULT_SETTINGS = [
        'responsive'         => true,
        'style_theme'        => 'default',
        'caption'            => '',
        'aria_label'         => '',
        'allow_export_csv'   => true,
        'allow_export_json'  => false,
        'allow_export_excel' => false,
        'enable_search'      => true,
        'enable_sorting'     => true,
        'enable_pagination'  => true,
        'page_size'          => 10,
        'highlight_rows'     => false,
        'custom_css'         => '',
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Daten für die Listenansicht
     */
    public function getListData(): array
    {
        $search = trim($_GET['q'] ?? '');

        $where  = '';
        $params = [];
        if ($search !== '') {
            $where    = 'WHERE table_name LIKE ? OR description LIKE ?';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $tables = $this->db->get_results(
            "SELECT id, table_name, description,
                    JSON_LENGTH(columns_json) AS col_count,
                    JSON_LENGTH(rows_json)    AS row_count,
                    created_at, updated_at
             FROM {$this->prefix}site_tables
             {$where}
             ORDER BY updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $total = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}site_tables");

        return [
            'tables' => array_map(fn($t) => (array)$t, $tables),
            'total'  => $total,
            'search' => $search,
        ];
    }

    /**
     * Daten für Edit/Create
     */
    public function getEditData(?int $id): array
    {
        $table = null;
        if ($id !== null) {
            $table = $this->db->get_row(
                "SELECT * FROM {$this->prefix}site_tables WHERE id = ?",
                [$id]
            );
            if ($table) {
                $table = (array)$table;
                $table['columns'] = json_decode($table['columns_json'] ?? '[]', true) ?: [];
                $table['rows']    = json_decode($table['rows_json'] ?? '[]', true) ?: [];
                $table['settings'] = array_merge(
                    self::DEFAULT_SETTINGS,
                    json_decode($table['settings_json'] ?? '{}', true) ?: []
                );
            }
        }

        return [
            'table'    => $table,
            'isNew'    => $table === null,
            'defaults' => self::DEFAULT_SETTINGS,
        ];
    }

    /**
     * Tabelle speichern
     */
    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $tableName   = trim($post['table_name'] ?? '');
        $description = trim($post['description'] ?? '');

        if ($tableName === '') {
            return ['success' => false, 'error' => 'Tabellenname darf nicht leer sein.'];
        }

        // Spalten & Zeilen aus JSON-Feldern
        $columns = json_decode($post['columns_json'] ?? '[]', true);
        $rows    = json_decode($post['rows_json'] ?? '[]', true);

        if (!is_array($columns)) $columns = [];
        if (!is_array($rows))    $rows = [];

        // Settings zusammenstellen
        $settings = self::DEFAULT_SETTINGS;
        foreach (self::DEFAULT_SETTINGS as $key => $default) {
            if (is_bool($default)) {
                $settings[$key] = isset($post['setting_' . $key]);
            } elseif (is_int($default)) {
                $settings[$key] = (int)($post['setting_' . $key] ?? $default);
            } else {
                $settings[$key] = trim((string)($post['setting_' . $key] ?? $default));
            }
        }

        $columnsJson  = json_encode($columns, JSON_UNESCAPED_UNICODE);
        $rowsJson     = json_encode($rows, JSON_UNESCAPED_UNICODE);
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);

        try {
            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}site_tables 
                     SET table_name = ?, description = ?, columns_json = ?, rows_json = ?, settings_json = ?, updated_at = NOW()
                     WHERE id = ?",
                    [$tableName, $description, $columnsJson, $rowsJson, $settingsJson, $id]
                );
                return ['success' => true, 'id' => $id, 'message' => 'Tabelle aktualisiert.'];
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}site_tables
                     (table_name, description, columns_json, rows_json, settings_json, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                    [$tableName, $description, $columnsJson, $rowsJson, $settingsJson]
                );
                $newId = (int)$this->db->lastInsertId();
                return ['success' => true, 'id' => $newId, 'message' => 'Tabelle erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Tabelle löschen
     */
    public function delete(int $id): array
    {
        try {
            $this->db->execute("DELETE FROM {$this->prefix}site_tables WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Tabelle gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen.'];
        }
    }

    /**
     * Tabelle duplizieren
     */
    public function duplicate(int $id): array
    {
        $source = $this->db->get_row(
            "SELECT * FROM {$this->prefix}site_tables WHERE id = ?",
            [$id]
        );

        if (!$source) {
            return ['success' => false, 'error' => 'Tabelle nicht gefunden.'];
        }

        $sourceData = (array)$source;

        try {
            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables
                 (table_name, description, columns_json, rows_json, settings_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    ($sourceData['table_name'] ?? 'Tabelle') . ' (Kopie)',
                    $sourceData['description'] ?? '',
                    $sourceData['columns_json'] ?? '[]',
                    $sourceData['rows_json'] ?? '[]',
                    $sourceData['settings_json'] ?? '{}',
                ]
            );
            $newId = (int)$this->db->lastInsertId();
            return ['success' => true, 'id' => $newId, 'message' => 'Tabelle dupliziert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Duplizieren.'];
        }
    }
}
