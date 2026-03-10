<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableTableRenderer
{
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
}
