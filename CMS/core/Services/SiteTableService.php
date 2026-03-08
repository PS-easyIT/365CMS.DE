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
        'content_mode' => 'table',
        'hub_slug' => '',
        'hub_template' => 'general-it',
        'hub_badge' => '',
        'hub_hero_title' => '',
        'hub_hero_text' => '',
        'hub_cta_label' => '',
        'hub_cta_url' => '',
        'hub_meta_audience' => '',
        'hub_meta_owner' => '',
        'hub_meta_update_cycle' => '',
        'hub_meta_focus' => '',
        'hub_meta_kpi' => '',
        'hub_links_json' => '[]',
        'hub_sections_json' => '[]',
    ];

    private const TEMPLATE_PLACEHOLDERS = [
        'general-it' => [
            'links' => [
                ['label' => 'Strategie', 'url' => '#strategie'],
                ['label' => 'Infrastruktur', 'url' => '#infrastruktur'],
                ['label' => 'Security', 'url' => '#security'],
                ['label' => 'Betrieb', 'url' => '#betrieb'],
            ],
            'sections' => [
                ['title' => 'IT-Roadmap', 'text' => 'Platzhalter für strategische Themen, Modernisierung, Infrastruktur und operative Prioritäten.', 'actionLabel' => 'Mehr zur Roadmap', 'actionUrl' => '#roadmap'],
                ['title' => 'Betriebsmodelle', 'text' => 'Platzhalter für Managed Services, Support-Level, SLA-Modelle und Betriebsverantwortung.', 'actionLabel' => 'Betrieb ansehen', 'actionUrl' => '#betrieb'],
            ],
        ],
        'microsoft-365' => [
            'links' => [
                ['label' => 'Teams', 'url' => '#teams'],
                ['label' => 'SharePoint', 'url' => '#sharepoint'],
                ['label' => 'Security', 'url' => '#security'],
                ['label' => 'Automation', 'url' => '#automation'],
            ],
            'sections' => [
                ['title' => 'Collaboration Stack', 'text' => 'Platzhalter für Teams, Exchange, SharePoint und Viva-Szenarien.', 'actionLabel' => 'Workspace öffnen', 'actionUrl' => '#workspace'],
                ['title' => 'Governance & Adoption', 'text' => 'Platzhalter für Richtlinien, Rollout-Phasen, Schulungen und Governance-Standards.', 'actionLabel' => 'Governance prüfen', 'actionUrl' => '#governance'],
            ],
        ],
        'datenschutz' => [
            'links' => [
                ['label' => 'DSGVO', 'url' => '#dsgvo'],
                ['label' => 'Verzeichnis', 'url' => '#vvt'],
                ['label' => 'Risiken', 'url' => '#risiken'],
                ['label' => 'Betroffenenrechte', 'url' => '#betroffenenrechte'],
            ],
            'sections' => [
                ['title' => 'Prüfpfade & Nachweise', 'text' => 'Platzhalter für TOMs, AV-Verträge, Löschkonzepte und Nachweisführung.', 'actionLabel' => 'Nachweise ansehen', 'actionUrl' => '#nachweise'],
                ['title' => 'Umsetzungspakete', 'text' => 'Platzhalter für Audits, Gap-Analysen, Schulungen und Datenschutz-Projekte.', 'actionLabel' => 'Pakete öffnen', 'actionUrl' => '#umsetzung'],
            ],
        ],
        'compliance' => [
            'links' => [
                ['label' => 'Policies', 'url' => '#policies'],
                ['label' => 'Audits', 'url' => '#audits'],
                ['label' => 'Rollen', 'url' => '#rollen'],
                ['label' => 'Nachweise', 'url' => '#nachweise'],
            ],
            'sections' => [
                ['title' => 'Governance Framework', 'text' => 'Platzhalter für Richtlinienlandschaft, Rollenkonzepte und Kontrollmechanismen.', 'actionLabel' => 'Framework ansehen', 'actionUrl' => '#framework'],
                ['title' => 'Audit-Vorbereitung', 'text' => 'Platzhalter für Auditpläne, Kontrollpunkte, Maßnahmenlisten und Dokumentation.', 'actionLabel' => 'Audit-Bereich öffnen', 'actionUrl' => '#audit'],
            ],
        ],
        'linux' => [
            'links' => [
                ['label' => 'Server', 'url' => '#server'],
                ['label' => 'Container', 'url' => '#container'],
                ['label' => 'Automation', 'url' => '#automation'],
                ['label' => 'Hardening', 'url' => '#hardening'],
            ],
            'sections' => [
                ['title' => 'Platform Engineering', 'text' => 'Platzhalter für Linux-Betrieb, Hosting, Kubernetes, Container und Plattform-Themen.', 'actionLabel' => 'Plattform öffnen', 'actionUrl' => '#plattform'],
                ['title' => 'Shell, CI/CD & Hardening', 'text' => 'Platzhalter für Automatisierung, Pipelines, Monitoring und Security-Baselines.', 'actionLabel' => 'Hardening ansehen', 'actionUrl' => '#hardening'],
            ],
        ],
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
        if (!str_contains($content, '[site-table') && !str_contains($content, '[hub-site')) {
            return $content;
        }

        $content = (string) preg_replace_callback(
            '/\[site-table\s+id\s*=\s*["\']?(\d+)["\']?\s*\]/i',
            function (array $matches): string {
                return $this->renderTableById((int) ($matches[1] ?? 0));
            },
            $content
        );

        return (string) preg_replace_callback(
            '/\[hub-site\s+id\s*=\s*["\']?(\d+)["\']?\s*\]/i',
            function (array $matches): string {
                return $this->renderHubById((int) ($matches[1] ?? 0));
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

        if (($table['settings']['content_mode'] ?? 'table') === 'hub') {
            return $this->renderHubMarkup($table);
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

    public function renderHubById(int $tableId): string
    {
        if ($tableId <= 0) {
            return '';
        }

        $table = $this->getTableById($tableId);
        if ($table === null) {
            return '';
        }

        if (($table['settings']['content_mode'] ?? 'table') !== 'hub') {
            return '';
        }

        return $this->renderHubMarkup($table);
    }

    public function getHubPageBySlug(string $slug): ?array
    {
        $slug = $this->sanitizeSlug($slug);
        if ($slug === '') {
            return null;
        }

        $row = Database::fetchOne(
            "SELECT id, table_name, description, columns_json, rows_json, settings_json, updated_at
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
               AND JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ?
             LIMIT 1",
            [$slug]
        );

        if (!$row) {
            return null;
        }

        $table = [
            'id' => (int)($row['id'] ?? 0),
            'name' => (string)($row['table_name'] ?? 'Hub Site'),
            'description' => trim((string)($row['description'] ?? '')),
            'columns' => is_array(json_decode((string)($row['columns_json'] ?? '[]'), true)) ? json_decode((string)$row['columns_json'], true) : [],
            'rows' => is_array(json_decode((string)($row['rows_json'] ?? '[]'), true)) ? json_decode((string)$row['rows_json'], true) : [],
            'settings' => is_array(json_decode((string)($row['settings_json'] ?? '{}'), true)) ? json_decode((string)$row['settings_json'], true) : [],
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];

        $settings = array_merge(self::DEFAULT_SETTINGS, $table['settings']);

        return [
            'id' => (int)$table['id'],
            'title' => (string)$table['name'],
            'slug' => $slug,
            'content_type' => 'hub',
            'content' => $this->renderHubMarkup($table),
            'meta_description' => trim((string)($settings['hub_hero_text'] ?? '')) !== ''
                ? trim((string)$settings['hub_hero_text'])
                : (string)$table['description'],
            'updated_at' => (string)($table['updated_at'] ?? ''),
        ];
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

    private function renderHubMarkup(array $table): string
    {
        $settings = array_merge(self::DEFAULT_SETTINGS, $table['settings']);
        $template = (string)($settings['hub_template'] ?? 'general-it');
        $pageSlug = trim((string)($settings['hub_slug'] ?? ''));
        $heroTitle = trim((string)($settings['hub_hero_title'] ?? '')) ?: (string)($table['name'] ?? 'Hub Site');
        $heroText = trim((string)($settings['hub_hero_text'] ?? '')) ?: trim((string)($table['description'] ?? ''));
        $heroBadge = trim((string)($settings['hub_badge'] ?? ''));
        $ctaLabel = trim((string)($settings['hub_cta_label'] ?? ''));
        $ctaUrl = trim((string)($settings['hub_cta_url'] ?? ''));
        $cards = $this->normalizeHubCards($table['rows']);
        $quickLinks = $this->normalizeHubLinks((string)($settings['hub_links_json'] ?? '[]'), $template);
        $sections = $this->normalizeHubSections((string)($settings['hub_sections_json'] ?? '[]'), $template);
        $metaItems = $this->buildHubMetaItems($settings, $template);

        $html = '<section class="cms-hub-site cms-hub-site--' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '"';
        if ($pageSlug !== '') {
            $html .= ' data-hub-slug="' . htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') . '"';
        }
        $html .= '>';
        $html .= '<div class="cms-hub-site__hero">';
        $html .= '<div class="cms-hub-site__hero-inner">';
        if ($heroBadge !== '') {
            $html .= '<span class="cms-hub-site__badge">' . htmlspecialchars($heroBadge, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '<h2 class="cms-hub-site__title">' . htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') . '</h2>';
        if ($heroText !== '') {
            $html .= '<p class="cms-hub-site__lead">' . nl2br(htmlspecialchars($heroText, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        if ($metaItems !== []) {
            $html .= '<div class="cms-hub-site__meta">';
            foreach ($metaItems as $metaItem) {
                $html .= '<span class="cms-hub-site__meta-chip"><strong>' . htmlspecialchars((string)$metaItem['label'], ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars((string)$metaItem['value'], ENT_QUOTES, 'UTF-8') . '</span>';
            }
            $html .= '</div>';
        }
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $html .= '<a class="cms-hub-site__cta" href="' . htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        $html .= '</div>';
        $html .= '</div>';

        if ($quickLinks !== []) {
            $html .= '<nav class="cms-hub-site__quicklinks" aria-label="Hub-Navigation">';
            foreach ($quickLinks as $link) {
                $html .= '<a class="cms-hub-site__quicklink" href="' . htmlspecialchars((string)$link['url'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$link['label'], ENT_QUOTES, 'UTF-8') . '</a>';
            }
            $html .= '</nav>';
        }

        if ($sections !== []) {
            $html .= '<div class="cms-hub-site__sections cms-hub-site__sections--' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '">';
            foreach ($sections as $section) {
                $html .= '<article class="cms-hub-site__section-card">';
                $html .= '<h3 class="cms-hub-site__section-title">' . htmlspecialchars((string)$section['title'], ENT_QUOTES, 'UTF-8') . '</h3>';
                if ((string)$section['text'] !== '') {
                    $html .= '<p class="cms-hub-site__section-text">' . nl2br(htmlspecialchars((string)$section['text'], ENT_QUOTES, 'UTF-8')) . '</p>';
                }
                if ((string)$section['actionLabel'] !== '' && (string)$section['actionUrl'] !== '') {
                    $html .= '<a class="cms-hub-site__section-link" href="' . htmlspecialchars((string)$section['actionUrl'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$section['actionLabel'], ENT_QUOTES, 'UTF-8') . '</a>';
                }
                $html .= '</article>';
            }
            $html .= '</div>';
        }

        if ($cards !== []) {
            $html .= '<div class="cms-hub-site__grid">';
            foreach ($cards as $card) {
                $url = htmlspecialchars((string)$card['url'], ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars((string)$card['title'], ENT_QUOTES, 'UTF-8');
                $summary = htmlspecialchars((string)$card['summary'], ENT_QUOTES, 'UTF-8');
                $badge = htmlspecialchars((string)$card['badge'], ENT_QUOTES, 'UTF-8');
                $meta = htmlspecialchars((string)$card['meta'], ENT_QUOTES, 'UTF-8');

                $html .= '<article class="cms-hub-site__card">';
                $html .= '<a class="cms-hub-site__card-link" href="' . $url . '">';
                if ($badge !== '') {
                    $html .= '<span class="cms-hub-site__card-badge">' . $badge . '</span>';
                }
                $html .= '<h3 class="cms-hub-site__card-title">' . $title . '</h3>';
                if ($summary !== '') {
                    $html .= '<p class="cms-hub-site__card-summary">' . nl2br($summary) . '</p>';
                }
                $html .= '<div class="cms-hub-site__card-footer">';
                if ($meta !== '') {
                    $html .= '<span class="cms-hub-site__card-meta">' . $meta . '</span>';
                }
                $html .= '<span class="cms-hub-site__card-arrow" aria-hidden="true">→</span>';
                $html .= '</div>';
                $html .= '</a>';
                $html .= '</article>';
            }
            $html .= '</div>';
        }

        $html .= '</section>';
        return $html;
    }

    private function normalizeHubCards(array $rows): array
    {
        $cards = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = trim((string)($row['title'] ?? $row['Titel'] ?? ''));
            $url = trim((string)($row['url'] ?? $row['URL'] ?? '#'));

            if ($title === '') {
                continue;
            }

            $cards[] = [
                'title' => mb_substr($title, 0, 160),
                'url' => $url !== '' ? $url : '#',
                'summary' => mb_substr(trim((string)($row['summary'] ?? $row['Beschreibung'] ?? '')), 0, 600),
                'badge' => mb_substr(trim((string)($row['badge'] ?? $row['Kategorie'] ?? '')), 0, 80),
                'meta' => mb_substr(trim((string)($row['meta'] ?? $row['Meta'] ?? '')), 0, 120),
            ];
        }

        return $cards;
    }

    private function normalizeHubLinks(string $json, string $template): array
    {
        $links = json_decode($json, true);
        if (!is_array($links) || $links === []) {
            $links = self::TEMPLATE_PLACEHOLDERS[$template]['links'] ?? self::TEMPLATE_PLACEHOLDERS['general-it']['links'];
        }

        $normalized = [];
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }
            $label = trim((string)($link['label'] ?? ''));
            $url = trim((string)($link['url'] ?? '#'));
            if ($label === '') {
                continue;
            }
            $normalized[] = ['label' => mb_substr($label, 0, 80), 'url' => $url !== '' ? $url : '#'];
        }

        return array_slice($normalized, 0, 6);
    }

    private function normalizeHubSections(string $json, string $template): array
    {
        $sections = json_decode($json, true);
        if (!is_array($sections) || $sections === []) {
            $sections = self::TEMPLATE_PLACEHOLDERS[$template]['sections'] ?? self::TEMPLATE_PLACEHOLDERS['general-it']['sections'];
        }

        $normalized = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $title = trim((string)($section['title'] ?? ''));
            $text = trim((string)($section['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $normalized[] = [
                'title' => mb_substr($title, 0, 120),
                'text' => mb_substr($text, 0, 600),
                'actionLabel' => mb_substr(trim((string)($section['actionLabel'] ?? '')), 0, 80),
                'actionUrl' => mb_substr(trim((string)($section['actionUrl'] ?? '')), 0, 240),
            ];
        }

        return array_slice($normalized, 0, 4);
    }

    private function buildHubMetaItems(array $settings, string $template): array
    {
        $defaults = [
            'general-it' => ['audience' => 'IT-Leitung', 'owner' => 'IT-Operations', 'cycle' => 'Monatlich', 'focus' => 'Architektur & Betrieb', 'kpi' => 'Servicequalität'],
            'microsoft-365' => ['audience' => 'Workspace & Modern Work', 'owner' => 'M365-Team', 'cycle' => '14-tägig', 'focus' => 'Adoption & Governance', 'kpi' => 'Nutzungsquote'],
            'datenschutz' => ['audience' => 'DSB & Fachbereiche', 'owner' => 'Datenschutz', 'cycle' => 'Quartalsweise', 'focus' => 'Nachweise & Prozesse', 'kpi' => 'Bearbeitungsstatus'],
            'compliance' => ['audience' => 'Management & Audit', 'owner' => 'Compliance Office', 'cycle' => 'Monatlich', 'focus' => 'Kontrollen & Policies', 'kpi' => 'Audit-Readiness'],
            'linux' => ['audience' => 'Admins & Platform Team', 'owner' => 'Platform Engineering', 'cycle' => 'Wöchentlich', 'focus' => 'Automatisierung & Hardening', 'kpi' => 'Deployment-Health'],
        ][$template] ?? [];

        $map = [
            'Zielgruppe' => trim((string)($settings['hub_meta_audience'] ?? '')) ?: (string)($defaults['audience'] ?? ''),
            'Verantwortlich' => trim((string)($settings['hub_meta_owner'] ?? '')) ?: (string)($defaults['owner'] ?? ''),
            'Update-Zyklus' => trim((string)($settings['hub_meta_update_cycle'] ?? '')) ?: (string)($defaults['cycle'] ?? ''),
            'Fokus' => trim((string)($settings['hub_meta_focus'] ?? '')) ?: (string)($defaults['focus'] ?? ''),
            'KPI' => trim((string)($settings['hub_meta_kpi'] ?? '')) ?: (string)($defaults['kpi'] ?? ''),
        ];

        $items = [];
        foreach ($map as $label => $value) {
            if ($value === '') {
                continue;
            }
            $items[] = ['label' => $label, 'value' => $value];
        }

        return array_slice($items, 0, 5);
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