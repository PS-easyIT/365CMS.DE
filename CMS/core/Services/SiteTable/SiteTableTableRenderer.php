<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

use CMS\Database;
use CMS\Json;
use CMS\Services\PurifierService;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableTableRenderer
{
    /** @var array<string,mixed>|null */
    private static ?array $displaySettings = null;

    public function __construct(private SiteTableTemplateRegistry $templateRegistry)
    {
    }

    public function renderTable(int $tableId, array $table): string
    {
        $columns = $this->normalizeColumns($table['columns'] ?? []);
        if ($columns === []) {
            return '';
        }

        $rows = $this->normalizeRows($table['rows'] ?? [], $columns);
        $settings = $this->getSettings($table);
        $displaySettings = $this->loadDisplaySettings();

        $themeClassMap = [
            'default' => 'cms-site-table--default',
            'stripe' => 'cms-site-table--stripe',
            'hover' => 'cms-site-table--hover',
            'cell-border' => 'cms-site-table--cell-border',
        ];
        $activeStyle = in_array((string) ($settings['style_theme'] ?? ''), $displaySettings['enabled_styles'], true)
            ? (string) ($settings['style_theme'] ?? '')
            : (string) $displaySettings['default_style'];
        $themeClass = $themeClassMap[$activeStyle] ?? $themeClassMap['default'];
        $wrapperClasses = ['cms-site-table-wrap'];
        if (!empty($settings['responsive']) && !empty($displaySettings['responsive_default'])) {
            $wrapperClasses[] = 'cms-site-table-wrap--responsive';
        }

        $caption = trim((string) ($settings['caption'] ?? ''));
        $ariaLabel = trim((string) ($settings['aria_label'] ?? ''));
        $tableName = htmlspecialchars((string) ($table['name'] ?? 'Tabelle'), ENT_QUOTES, 'UTF-8');
        $tableLabel = htmlspecialchars($ariaLabel !== '' ? $ariaLabel : (string) ($table['name'] ?? 'Tabelle'), ENT_QUOTES, 'UTF-8');

        $html = '<div class="' . implode(' ', $wrapperClasses) . '">';
        if (!empty($displaySettings['show_meta_panel'])) {
            $metaHtml = '';
            if (!empty($displaySettings['show_table_name'])) {
                $metaHtml .= '<h3 class="cms-site-table-title">' . $tableName . '</h3>';
            }
            if (!empty($displaySettings['show_description']) && !empty($table['description'])) {
                $metaHtml .= '<div class="cms-site-table-description">' . $this->renderEmbeddedContent((string) $table['description'], $tableId) . '</div>';
            }
            if (!empty($displaySettings['show_export_links'])) {
                $metaHtml .= $this->renderExportLinks($tableId, $settings);
            }
            if ($metaHtml !== '') {
                $html .= '<div class="cms-site-table-meta">' . $metaHtml . '</div>';
            }
        }
        $html .= '<table class="cms-site-table ' . $themeClass . '" role="grid" aria-label="' . $tableLabel . '">';
        if (!empty($displaySettings['show_caption']) && $caption !== '') {
            $html .= '<caption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</caption>';
        }
        $html .= '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th scope="col">' . $this->renderColumnLabel((string) ($column['label'] ?? '')) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        if ($rows === []) {
            $html .= '<tr><td colspan="' . count($columns) . '">Keine Tabellenzeilen vorhanden.</td></tr>';
        } else {
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($columns as $index => $column) {
                    $label = (string) ($column['label'] ?? ('Spalte ' . ($index + 1)));
                    $html .= '<td>' . $this->renderEmbeddedContent((string) ($row[$label] ?? ''), $tableId) . '</td>';
                }
                $html .= '</tr>';
            }
        }

        return $html . '</tbody></table></div>';
    }

    public function streamExport(array $table, string $format, bool $respectFrontendPermissions, callable $slugSanitizer): bool
    {
        $format = strtolower($format);
        if (!in_array($format, ['csv', 'json'], true)) {
            return false;
        }

        $settings = $this->getSettings($table);
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

        $columns = $this->normalizeColumns($table['columns'] ?? []);
        $rows = $this->normalizeRows($table['rows'] ?? [], $columns);
        $fileName = $slugSanitizer((string) ($table['name'] ?? 'site-table'));
        $fileName = is_string($fileName) && $fileName !== '' ? $fileName : 'site-table';

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

    private function getSettings(array $table): array
    {
        return array_merge($this->templateRegistry->getDefaultSettings(), $table['settings'] ?? []);
    }

    private function normalizeColumns(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $index => $column) {
            if (!is_array($column)) {
                continue;
            }
            $label = trim((string) ($column['label'] ?? ''));
            $plainLabel = trim(html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($plainLabel === '') {
                $label = 'Spalte ' . ($index + 1);
                $plainLabel = $label;
            }

            $normalized[] = [
                'label' => $label,
                'plain_label' => mb_substr($plainLabel, 0, 120),
                'type' => 'text',
            ];
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
                $plainLabel = (string) ($column['plain_label'] ?? $label);
                $value = $row[$label] ?? $row[$plainLabel] ?? $row[$index] ?? '';
                if (is_array($value) || is_object($value)) {
                    $value = '';
                }
                $cleanRow[$label] = trim((string) $value);
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

    private function renderEmbeddedContent(string $value, int $currentTableId): string
    {
        if ($value === '') {
            return '';
        }

        $placeholders = [];
        $prepared = (string) preg_replace_callback(
            '/\[(?:site-table|table)\s+id\s*=\s*["\']?(\d+)["\']?\s*\/?\]/i',
            static function (array $matches) use (&$placeholders, $currentTableId): string {
                $targetTableId = (int) ($matches[1] ?? 0);
                if ($targetTableId <= 0 || $targetTableId === $currentTableId) {
                    return (string) ($matches[0] ?? '');
                }

                $token = '%%CMS_SITE_TABLE_' . count($placeholders) . '%%';
                $placeholders[$token] = \CMS\Services\SiteTableService::getInstance()->renderTableById($targetTableId);

                return $token;
            },
            $value
        );

        $containsHtml = $this->containsHtml($prepared);
        $html = $containsHtml
            ? $this->sanitizeRichHtml($prepared)
            : nl2br(htmlspecialchars($prepared, ENT_QUOTES, 'UTF-8'));

        foreach ($placeholders as $token => $replacement) {
            $html = str_replace(
                $containsHtml ? $token : htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
                $replacement,
                $html
            );
        }

        return $html;
    }

    private function renderColumnLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if ($this->containsHtml($value)) {
            return $this->sanitizeRichHtml($value);
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function containsHtml(string $value): bool
    {
        return preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $value) === 1;
    }

    private function sanitizeRichHtml(string $value): string
    {
        return PurifierService::getInstance()->purify($value, 'table');
    }

    /** @return array<string,mixed> */
    private function loadDisplaySettings(): array
    {
        if (self::$displaySettings !== null) {
            return self::$displaySettings;
        }

        $db = Database::instance();
        $row = $db->get_row(
            "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1",
            [SiteTableDisplaySettings::OPTION_KEY]
        );

        $saved = [];
        if ($row && !empty($row->option_value)) {
            $saved = Json::decodeArray($row->option_value ?? null, []);
        }

        self::$displaySettings = SiteTableDisplaySettings::normalize(is_array($saved) ? $saved : []);

        return self::$displaySettings;
    }
}
