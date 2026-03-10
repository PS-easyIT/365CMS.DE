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
    private ?bool $hasTableSlugColumn = null;

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
                    settings_json,
                    JSON_LENGTH(columns_json) AS col_count,
                    JSON_LENGTH(rows_json)    AS row_count,
                    created_at, updated_at
             FROM {$this->prefix}site_tables
             {$where}
             ORDER BY updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $tables = array_values(array_filter(array_map(function ($table): array {
            $item = (array)$table;
            $settings = \CMS\Json::decodeArray($item['settings_json'] ?? null, []);
            $item['content_mode'] = (string)($settings['content_mode'] ?? 'table');
            return $item;
        }, $tables), static fn(array $table): bool => ($table['content_mode'] ?? 'table') !== 'hub'));

        $total = count($tables);

        return [
            'tables' => $tables,
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
                $table['columns'] = \CMS\Json::decodeArray($table['columns_json'] ?? null, []);
                $table['rows']    = \CMS\Json::decodeArray($table['rows_json'] ?? null, []);
                $table['settings'] = array_merge(
                    self::DEFAULT_SETTINGS,
                    \CMS\Json::decodeArray($table['settings_json'] ?? null, [])
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
        $columns = \CMS\Json::decodeArray($post['columns_json'] ?? null, []);
        $rows    = \CMS\Json::decodeArray($post['rows_json'] ?? null, []);

        if (!is_array($columns)) $columns = [];
        if (!is_array($rows))    $rows = [];

        // Settings zusammenstellen
        $settings = self::DEFAULT_SETTINGS;
        $settings['content_mode'] = 'table';
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
        $tableSlug    = $this->buildUniqueTableSlug($tableName, $id > 0 ? $id : null);

        try {
            if ($id > 0) {
                $params = [$tableName, $description, $columnsJson, $rowsJson, $settingsJson];
                $sql = "UPDATE {$this->prefix}site_tables 
                        SET table_name = ?, description = ?, columns_json = ?, rows_json = ?, settings_json = ?";
                if ($this->hasTableSlugColumn()) {
                    $sql .= ', table_slug = ?';
                    $params[] = $tableSlug;
                }
                $sql .= ', updated_at = NOW() WHERE id = ?';
                $params[] = $id;
                $this->db->execute($sql, $params);
                return ['success' => true, 'id' => $id, 'message' => 'Tabelle aktualisiert.'];
            } else {
                $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json', 'created_at', 'updated_at'];
                $placeholders = ['?', '?', '?', '?', '?', 'NOW()', 'NOW()'];
                $params = [$tableName, $description, $columnsJson, $rowsJson, $settingsJson];
                if ($this->hasTableSlugColumn()) {
                    $columns[] = 'table_slug';
                    $placeholders[] = '?';
                    $params[] = $tableSlug;
                }
                $this->db->execute(
                    "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                     VALUES (" . implode(', ', $placeholders) . ")",
                    $params
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
            $copyName = ($sourceData['table_name'] ?? 'Tabelle') . ' (Kopie)';
            $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json', 'created_at', 'updated_at'];
            $placeholders = ['?', '?', '?', '?', '?', 'NOW()', 'NOW()'];
            $params = [
                $copyName,
                $sourceData['description'] ?? '',
                $sourceData['columns_json'] ?? '[]',
                $sourceData['rows_json'] ?? '[]',
                $sourceData['settings_json'] ?? '{}',
            ];
            if ($this->hasTableSlugColumn()) {
                $columns[] = 'table_slug';
                $placeholders[] = '?';
                $params[] = $this->buildUniqueTableSlug((string)$copyName, null);
            }
            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $placeholders) . ")",
                $params
            );
            $newId = (int)$this->db->lastInsertId();
            return ['success' => true, 'id' => $newId, 'message' => 'Tabelle dupliziert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Duplizieren.'];
        }
    }

    private function buildUniqueTableSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = $this->sanitizeSlug($title);
        if ($baseSlug === '') {
            $baseSlug = 'tabelle';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->tableSlugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function tableSlugExists(string $slug, ?int $excludeId = null): bool
    {
        if (!$this->hasTableSlugColumn()) {
            return false;
        }

        $sql = "SELECT id FROM {$this->prefix}site_tables WHERE table_slug = ?";
        $params = [$slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return $this->db->get_var($sql . ' LIMIT 1', $params) !== null;
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = (string)preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function hasTableSlugColumn(): bool
    {
        if ($this->hasTableSlugColumn !== null) {
            return $this->hasTableSlugColumn;
        }

        try {
            $column = $this->db->get_var("SHOW COLUMNS FROM {$this->prefix}site_tables LIKE 'table_slug'");
            $this->hasTableSlugColumn = $column !== null;
        } catch (\Throwable) {
            $this->hasTableSlugColumn = false;
        }

        return $this->hasTableSlugColumn;
    }
}
