<?php
/**
 * SiteTableService – Frontend-Rendering und Export für Seitentabellen.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableService
{
    private static ?self $instance = null;

    private Database $db;
    private string $prefix;

    private const DEFAULT_SETTINGS = [
        'responsive' => true,
        'style_theme' => 'default',
        'caption' => '',
        'aria_label' => '',
        'allow_export_csv' => true,
        'allow_export_json' => false,
        'allow_export_excel' => false,
    ];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function replaceShortcodes(string $content): string
    {
        if (!str_contains($content, '[site-table')) {
            return $content;
        }

        return (string) preg_replace_callback(
            '/\[site-table\s+id\s*=\s*["\']?(\d+)["\']?\s*\]/i',
            function (array $matches): string {
                return $this->renderTableById((int) ($matches[1] ?? 0));
            },
            $content
        );
    }

    public function renderTableById(int $tableId): string
    {
        if ($tableId <= 0) {
            return '';
        }

        $table = $this->getTableById($tableId);
        if ($table === null) {
            return '';
        }

        $columns = $this->normalizeColumns($table['columns']);
        if ($columns === []) {
            return '';
        }

        $rows = $this->normalizeRows($table['rows'], $columns);
        $settings = array_merge(self::DEFAULT_SETTINGS, $table['settings']);

        $themeClassMap = [
            'default' => 'cms-site-table--default',
            'stripe' => 'cms-site-table--stripe',
            'hover' => 'cms-site-table--hover',
            'cell-border' => 'cms-site-table--cell-border',
        ];
        $themeClass = $themeClassMap[$settings['style_theme']] ?? $themeClassMap['default'];
        $wrapperClasses = ['cms-site-table-wrap'];
        if (!empty($settings['responsive'])) {
            $wrapperClasses[] = 'cms-site-table-wrap--responsive';
        }

        $caption = trim((string) ($settings['caption'] ?? ''));
        $ariaLabel = trim((string) ($settings['aria_label'] ?? ''));
        $tableName = htmlspecialchars((string) ($table['name'] ?? 'Tabelle'), ENT_QUOTES, 'UTF-8');
        $tableLabel = htmlspecialchars($ariaLabel !== '' ? $ariaLabel : (string) ($table['name'] ?? 'Tabelle'), ENT_QUOTES, 'UTF-8');

        $html = '<div class="' . implode(' ', $wrapperClasses) . '">';
        $html .= '<div class="cms-site-table-meta">';
        $html .= '<h3 class="cms-site-table-title">' . $tableName . '</h3>';
        if (!empty($table['description'])) {
            $html .= '<p class="cms-site-table-description">' . htmlspecialchars((string) $table['description'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $html .= $this->renderExportLinks($tableId, $settings);
        $html .= '</div>';
        $html .= '<table class="cms-site-table ' . $themeClass . '" role="grid" aria-label="' . $tableLabel . '">';
        if ($caption !== '') {
            $html .= '<caption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th scope="col">' . htmlspecialchars((string) $column['label'], ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if ($rows === []) {
            $html .= '<tr><td colspan="' . count($columns) . '">Keine Tabellenzeilen vorhanden.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($columns as $index => $column) {
                    $label = (string) ($column['label'] ?? ('Spalte ' . ($index + 1)));
                    $html .= '<td>' . nl2br(htmlspecialchars((string) ($row[$label] ?? ''), ENT_QUOTES, 'UTF-8')) . '</td>';
                }
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    public function streamExportById(int $tableId, string $format, bool $respectFrontendPermissions = true): bool
    {
        $table = $this->getTableById($tableId);
        if ($table === null) {
            return false;
        }

        $format = strtolower($format);
        if (!in_array($format, ['csv', 'json'], true)) {
            return false;
        }

        $settings = array_merge(self::DEFAULT_SETTINGS, $table['settings']);
        if ($respectFrontendPermissions) {
            $allowed = match ($format) {
                'csv' => (bool) ($settings['allow_export_csv'] ?? true),
                'json' => (bool) ($settings['allow_export_json'] ?? false),
                default => false,
            };
            if (!$allowed) {
                return false;
            }
        }

        $columns = $this->normalizeColumns($table['columns']);
        $rows = $this->normalizeRows($table['rows'], $columns);
        $fileName = $this->sanitizeSlug((string) ($table['name'] ?? 'site-table')) ?: 'site-table';

        if ($format === 'json') {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '.json"');
            echo json_encode([
                'columns' => $columns,
                'rows' => $rows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '.csv"');
        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return false;
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

    private function getTableById(int $tableId): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT id, table_name, description, columns_json, rows_json, settings_json
             FROM {$this->prefix}site_tables
             WHERE id = ? LIMIT 1",
            [$tableId]
        );

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['table_name'] ?? 'Tabelle'),
            'description' => trim((string) ($row['description'] ?? '')),
            'columns' => is_array(json_decode((string) ($row['columns_json'] ?? '[]'), true)) ? json_decode((string) $row['columns_json'], true) : [],
            'rows' => is_array(json_decode((string) ($row['rows_json'] ?? '[]'), true)) ? json_decode((string) $row['rows_json'], true) : [],
            'settings' => is_array(json_decode((string) ($row['settings_json'] ?? '{}'), true)) ? json_decode((string) $row['settings_json'], true) : [],
        ];
    }

    private function normalizeColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $index => $column) {
            if (!is_array($column)) {
                continue;
            }
            $label = trim(strip_tags((string) ($column['label'] ?? ('Spalte ' . ($index + 1)))));
            if ($label === '') {
                $label = 'Spalte ' . ($index + 1);
            }
            $normalized[] = ['label' => mb_substr($label, 0, 120), 'type' => 'text'];
        }
        return $normalized;
    }

    private function normalizeRows(array $rows, array $columns): array
    {
        $normalized = [];
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
            $normalized[] = $cleanRow;
        }
        return $normalized;
    }

    private function renderExportLinks(int $tableId, array $settings): string
    {
        $links = [];
        if (!empty($settings['allow_export_csv'])) {
            $links[] = '<a href="' . SITE_URL . '/site-table/export/' . $tableId . '/csv">CSV</a>';
        }
        if (!empty($settings['allow_export_json'])) {
            $links[] = '<a href="' . SITE_URL . '/site-table/export/' . $tableId . '/json">JSON</a>';
        }

        if ($links === []) {
            return '';
        }

        return '<div class="cms-site-table-actions"><span>Export:</span> ' . implode(' <span aria-hidden="true">·</span> ', $links) . '</div>';
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }
}