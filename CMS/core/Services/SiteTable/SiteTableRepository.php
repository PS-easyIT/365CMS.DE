<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

use CMS\Database;
use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableRepository
{
    public function __construct(
        private Database $db,
        private string $prefix,
    ) {
    }

    public function getTableById(int $tableId): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $row = $this->db->fetchOne(
            "SELECT id, table_name, description, columns_json, rows_json, settings_json, updated_at
             FROM {$this->prefix}site_tables
             WHERE id = ? LIMIT 1",
            [$tableId]
        );

        return is_array($row) ? $this->hydrateTable($row) : null;
    }

    public function getHubTableBySlug(string $slug): ?array
    {
        if ($slug === '') {
            return null;
        }

        $row = $this->db->fetchOne(
            "SELECT id, table_name, description, columns_json, rows_json, settings_json, updated_at
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
               AND JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ?
             LIMIT 1",
            [$slug]
        );

        return is_array($row) ? $this->hydrateTable($row) : null;
    }

    public function getStoredTemplateProfiles(string $settingKey): array
    {
        $row = $this->db->fetchOne(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            [$settingKey]
        );

        if (!is_array($row) || empty($row['option_value'])) {
            return [];
        }

        $stored = Json::decodeArray($row['option_value'] ?? null, []);

        return is_array($stored) ? $stored : [];
    }

    private function hydrateTable(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['table_name'] ?? 'Tabelle'),
            'description' => trim((string) ($row['description'] ?? '')),
            'columns' => Json::decodeArray($row['columns_json'] ?? null, []),
            'rows' => Json::decodeArray($row['rows_json'] ?? null, []),
            'settings' => Json::decodeArray($row['settings_json'] ?? null, []),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
