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

    private const TEMPLATE_SETTING_KEY = 'hub_site_templates';

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
        'hub_card_layout' => 'standard',
        'hub_card_image_position' => 'top',
        'hub_card_image_fit' => 'cover',
        'hub_card_image_ratio' => 'wide',
        'hub_card_meta_layout' => 'split',
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

    private const DEFAULT_TEMPLATE_CARD_DESIGN = [
        'general-it' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
        'microsoft-365' => ['layout' => 'feature', 'image_position' => 'left', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
        'datenschutz' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked'],
        'compliance' => ['layout' => 'feature', 'image_position' => 'right', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
        'linux' => ['layout' => 'compact', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked'],
    ];

    private const DEFAULT_META_LABELS = [
        'audience' => 'Zielgruppe',
        'owner' => 'Verantwortlich',
        'update_cycle' => 'Update-Zyklus',
        'focus' => 'Fokus',
        'kpi' => 'KPI',
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
        $templateKey = (string)($settings['hub_template'] ?? 'general-it');
        $templateProfile = $this->getTemplateProfile($templateKey);
        $template = (string)($templateProfile['base_template'] ?? $templateKey ?: 'general-it');
        $pageSlug = trim((string)($settings['hub_slug'] ?? ''));
        $heroTitle = trim((string)($settings['hub_hero_title'] ?? '')) ?: (string)($table['name'] ?? 'Hub Site');
        $heroText = trim((string)($settings['hub_hero_text'] ?? '')) ?: trim((string)($table['description'] ?? ''));
        $heroBadge = trim((string)($settings['hub_badge'] ?? ''));
        $ctaLabel = trim((string)($settings['hub_cta_label'] ?? ''));
        $ctaUrl = trim((string)($settings['hub_cta_url'] ?? ''));
        $cards = $this->normalizeHubCards($table['rows']);
        $quickLinks = $this->normalizeTemplateLinks($templateProfile, $template);
        $sections = $this->normalizeTemplateSections($templateProfile, $template);
        $metaSettings = array_merge([
            'hub_meta_audience' => (string)($templateProfile['meta']['audience'] ?? ''),
            'hub_meta_owner' => (string)($templateProfile['meta']['owner'] ?? ''),
            'hub_meta_update_cycle' => (string)($templateProfile['meta']['update_cycle'] ?? ''),
            'hub_meta_focus' => (string)($templateProfile['meta']['focus'] ?? ''),
            'hub_meta_kpi' => (string)($templateProfile['meta']['kpi'] ?? ''),
        ], $settings);
        foreach (['hub_meta_audience', 'hub_meta_owner', 'hub_meta_update_cycle', 'hub_meta_focus', 'hub_meta_kpi'] as $metaKey) {
            if (trim((string)($metaSettings[$metaKey] ?? '')) === '') {
                $metaSettings[$metaKey] = (string)($templateProfile['meta'][str_replace('hub_meta_', '', $metaKey)] ?? $templateProfile['meta'][match ($metaKey) {
                    'hub_meta_update_cycle' => 'update_cycle',
                    default => str_replace('hub_meta_', '', $metaKey),
                }] ?? '');
            }
        }
        $metaItems = $this->buildHubMetaItems($metaSettings, $template, $templateProfile);
        $cardDesign = is_array($templateProfile['card_design'] ?? null) ? $templateProfile['card_design'] : [];
        $cardSchema = is_array($templateProfile['card_schema'] ?? null) ? $templateProfile['card_schema'] : [];
        $colorSettings = is_array($templateProfile['colors'] ?? null) ? $templateProfile['colors'] : [];
        $cardLayout = $this->normalizeOption((string)($cardDesign['layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard');
        $cardImagePosition = $this->normalizeOption((string)($cardDesign['image_position'] ?? 'top'), ['top', 'left', 'right'], 'top');
        $cardImageFit = $this->normalizeOption((string)($cardDesign['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover');
        $cardImageRatio = $this->normalizeOption((string)($cardDesign['image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide');
        $cardMetaLayout = $this->normalizeOption((string)($cardDesign['meta_layout'] ?? 'split'), ['split', 'stacked'], 'split');
        $cardColumns = max(1, min(3, (int)($cardSchema['columns'] ?? 2)));
        $styleVariables = $this->buildHubStyleVariables($colorSettings);

        $html = '<section class="cms-hub-site cms-hub-site--' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '"';
        if ($pageSlug !== '') {
            $html .= ' data-hub-slug="' . htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') . '"';
        }
        if ($styleVariables !== '') {
            $html .= ' style="' . htmlspecialchars($styleVariables, ENT_QUOTES, 'UTF-8') . '"';
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
            $html .= '<div class="cms-hub-site__grid cms-hub-site__grid--' . htmlspecialchars($cardLayout, ENT_QUOTES, 'UTF-8') . ' cms-hub-site__grid--cols-' . $cardColumns . '">';
            foreach ($cards as $card) {
                $url = htmlspecialchars((string)$card['url'], ENT_QUOTES, 'UTF-8');
                $title = htmlspecialchars((string)$card['title'], ENT_QUOTES, 'UTF-8');
                $summary = htmlspecialchars((string)$card['summary'], ENT_QUOTES, 'UTF-8');
                $badge = htmlspecialchars((string)$card['badge'], ENT_QUOTES, 'UTF-8');
                $meta = htmlspecialchars((string)$card['meta'], ENT_QUOTES, 'UTF-8');
                $metaLeft = htmlspecialchars((string)($card['meta_left'] ?? ''), ENT_QUOTES, 'UTF-8');
                $metaRight = htmlspecialchars((string)($card['meta_right'] ?? ''), ENT_QUOTES, 'UTF-8');
                $buttonText = htmlspecialchars((string)($card['button_text'] ?? ''), ENT_QUOTES, 'UTF-8');
                $buttonLink = trim((string)($card['button_link'] ?? ''));
                $imageUrl = trim((string)($card['image_url'] ?? ''));
                $imageAlt = htmlspecialchars((string)($card['image_alt'] ?? $card['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                $hasImage = $imageUrl !== '';
                $cardArticleClass = 'cms-hub-site__card';
                $cardLinkClass = 'cms-hub-site__card-link';

                if ($hasImage) {
                    $cardArticleClass .= ' cms-hub-site__card--image-' . htmlspecialchars($cardImagePosition, ENT_QUOTES, 'UTF-8');
                    $cardLinkClass .= ' cms-hub-site__card-link--image-' . htmlspecialchars($cardImagePosition, ENT_QUOTES, 'UTF-8');
                }

                $cardArticleClass .= ' cms-hub-site__card--meta-' . htmlspecialchars($cardMetaLayout, ENT_QUOTES, 'UTF-8');

                $html .= '<article class="' . $cardArticleClass . '">';
                $html .= '<div class="' . $cardLinkClass . '">';
                if ($hasImage) {
                    if ($url !== '#') {
                        $html .= '<a class="cms-hub-site__card-media-link" href="' . $url . '">';
                    }
                    $html .= '<div class="cms-hub-site__card-media cms-hub-site__card-media--' . htmlspecialchars($cardImageRatio, ENT_QUOTES, 'UTF-8') . ' cms-hub-site__card-media--fit-' . htmlspecialchars($cardImageFit, ENT_QUOTES, 'UTF-8') . '">';
                    $html .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $imageAlt . '" loading="lazy">';
                    $html .= '</div>';
                    if ($url !== '#') {
                        $html .= '</a>';
                    }
                }
                $html .= '<div class="cms-hub-site__card-content">';
                if ($badge !== '') {
                    $html .= '<span class="cms-hub-site__card-badge">' . $badge . '</span>';
                }
                $html .= '<h3 class="cms-hub-site__card-title">';
                if ($url !== '#') {
                    $html .= '<a class="cms-hub-site__card-title-link" href="' . $url . '">' . $title . '</a>';
                } else {
                    $html .= $title;
                }
                $html .= '</h3>';
                if ($summary !== '') {
                    $html .= '<p class="cms-hub-site__card-summary">' . nl2br($summary) . '</p>';
                }
                $html .= '<div class="cms-hub-site__card-footer cms-hub-site__card-footer--' . htmlspecialchars($cardMetaLayout, ENT_QUOTES, 'UTF-8') . '">';
                $html .= '<div class="cms-hub-site__card-meta-row">';
                if ($metaLeft !== '') {
                    $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--left">' . $metaLeft . '</span>';
                } elseif ($meta !== '') {
                    $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--left">' . $meta . '</span>';
                }
                if ($metaRight !== '') {
                    $html .= '<span class="cms-hub-site__card-meta cms-hub-site__card-meta--right">' . $metaRight . '</span>';
                }
                $html .= '</div>';
                if ($url !== '#') {
                    $html .= '<span class="cms-hub-site__card-arrow" aria-hidden="true">→</span>';
                }
                $html .= '</div>';
                if ($buttonText !== '') {
                    $buttonHref = $buttonLink !== '' ? htmlspecialchars($buttonLink, ENT_QUOTES, 'UTF-8') : $url;
                    if ($buttonHref !== '#') {
                        $html .= '<a class="cms-hub-site__card-button" href="' . $buttonHref . '">' . $buttonText . '</a>';
                    } else {
                        $html .= '<span class="cms-hub-site__card-button">' . $buttonText . '</span>';
                    }
                }
                $html .= '</div>';
                $html .= '</div>';
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
                'meta_left' => mb_substr(trim((string)($row['meta_left'] ?? $row['metaLeft'] ?? $row['Meta links'] ?? $row['meta'] ?? '')), 0, 120),
                'meta_right' => mb_substr(trim((string)($row['meta_right'] ?? $row['metaRight'] ?? $row['Meta rechts'] ?? '')), 0, 120),
                'image_url' => mb_substr(trim((string)($row['image_url'] ?? $row['imageUrl'] ?? $row['Bild'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim((string)($row['image_alt'] ?? $row['imageAlt'] ?? '')), 0, 160),
                'button_text' => mb_substr(trim((string)($row['button_text'] ?? $row['buttonText'] ?? $row['Button-Text'] ?? '')), 0, 80),
                'button_link' => mb_substr(trim((string)($row['button_link'] ?? $row['buttonLink'] ?? $row['Button-Link'] ?? '')), 0, 500),
            ];
        }

        return $cards;
    }

    private function normalizeOption(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
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

    private function normalizeTemplateLinks(array $templateProfile, string $template): array
    {
        $links = $templateProfile['links'] ?? [];
        if (!is_array($links) || $links === []) {
            $links = self::TEMPLATE_PLACEHOLDERS[$template]['links'] ?? self::TEMPLATE_PLACEHOLDERS['general-it']['links'];
        }

        return $this->normalizeHubLinks(json_encode($links, JSON_UNESCAPED_UNICODE) ?: '[]', $template);
    }

    private function normalizeTemplateSections(array $templateProfile, string $template): array
    {
        $sections = $templateProfile['sections'] ?? [];
        if (!is_array($sections) || $sections === []) {
            $sections = self::TEMPLATE_PLACEHOLDERS[$template]['sections'] ?? self::TEMPLATE_PLACEHOLDERS['general-it']['sections'];
        }

        return $this->normalizeHubSections(json_encode($sections, JSON_UNESCAPED_UNICODE) ?: '[]', $template);
    }

    private function getTemplateProfile(string $key): array
    {
        $profiles = $this->getTemplateProfiles();
        $key = trim($key);
        if ($key !== '' && isset($profiles[$key])) {
            return $profiles[$key];
        }

        return $profiles['general-it'] ?? [
            'base_template' => 'general-it',
            'meta' => [],
            'meta_labels' => self::DEFAULT_META_LABELS,
            'links' => self::TEMPLATE_PLACEHOLDERS['general-it']['links'],
            'sections' => self::TEMPLATE_PLACEHOLDERS['general-it']['sections'],
            'colors' => $this->getDefaultTemplateColors('general-it'),
            'card_schema' => $this->getDefaultCardSchema(),
            'card_design' => self::DEFAULT_TEMPLATE_CARD_DESIGN['general-it'],
            'starter_cards' => [],
        ];
    }

    private function getTemplateProfiles(): array
    {
        $defaults = [];
        foreach (self::TEMPLATE_PLACEHOLDERS as $key => $placeholder) {
            $defaults[$key] = [
                'base_template' => $key,
                'meta' => [],
                'meta_labels' => self::DEFAULT_META_LABELS,
                'links' => $placeholder['links'] ?? [],
                'sections' => $placeholder['sections'] ?? [],
                'colors' => $this->getDefaultTemplateColors($key),
                'card_schema' => $this->getDefaultCardSchema(),
                'card_design' => self::DEFAULT_TEMPLATE_CARD_DESIGN[$key] ?? self::DEFAULT_TEMPLATE_CARD_DESIGN['general-it'],
                'starter_cards' => [],
            ];
        }

        $row = $this->db->fetchOne(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            [self::TEMPLATE_SETTING_KEY]
        );

        if (!$row || empty($row['option_value'])) {
            return $defaults;
        }

        $stored = json_decode((string)$row['option_value'], true);
        if (!is_array($stored)) {
            return $defaults;
        }

        foreach ($stored as $key => $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $baseTemplate = (string)($profile['base_template'] ?? $key);
            $defaults[$key] = [
                'base_template' => in_array($baseTemplate, array_keys(self::TEMPLATE_PLACEHOLDERS), true) ? $baseTemplate : 'general-it',
                'meta' => is_array($profile['meta'] ?? null) ? $profile['meta'] : [],
                'meta_labels' => is_array($profile['meta_labels'] ?? null) ? array_merge(self::DEFAULT_META_LABELS, $profile['meta_labels']) : self::DEFAULT_META_LABELS,
                'links' => is_array($profile['links'] ?? null) ? $profile['links'] : [],
                'sections' => is_array($profile['sections'] ?? null) ? $profile['sections'] : [],
                'colors' => is_array($profile['colors'] ?? null) ? array_merge($this->getDefaultTemplateColors($baseTemplate), $profile['colors']) : $this->getDefaultTemplateColors($baseTemplate),
                'card_schema' => is_array($profile['card_schema'] ?? null) ? array_merge($this->getDefaultCardSchema(), $profile['card_schema']) : $this->getDefaultCardSchema(),
                'card_design' => is_array($profile['card_design'] ?? null) ? $profile['card_design'] : (self::DEFAULT_TEMPLATE_CARD_DESIGN[$baseTemplate] ?? self::DEFAULT_TEMPLATE_CARD_DESIGN['general-it']),
                'starter_cards' => is_array($profile['starter_cards'] ?? null) ? $profile['starter_cards'] : [],
            ];
        }

        return $defaults;
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

    private function buildHubMetaItems(array $settings, string $template, array $profile = []): array
    {
        $defaults = [
            'general-it' => ['audience' => 'IT-Leitung', 'owner' => 'IT-Operations', 'cycle' => 'Monatlich', 'focus' => 'Architektur & Betrieb', 'kpi' => 'Servicequalität'],
            'microsoft-365' => ['audience' => 'Workspace & Modern Work', 'owner' => 'M365-Team', 'cycle' => '14-tägig', 'focus' => 'Adoption & Governance', 'kpi' => 'Nutzungsquote'],
            'datenschutz' => ['audience' => 'DSB & Fachbereiche', 'owner' => 'Datenschutz', 'cycle' => 'Quartalsweise', 'focus' => 'Nachweise & Prozesse', 'kpi' => 'Bearbeitungsstatus'],
            'compliance' => ['audience' => 'Management & Audit', 'owner' => 'Compliance Office', 'cycle' => 'Monatlich', 'focus' => 'Kontrollen & Policies', 'kpi' => 'Audit-Readiness'],
            'linux' => ['audience' => 'Admins & Platform Team', 'owner' => 'Platform Engineering', 'cycle' => 'Wöchentlich', 'focus' => 'Automatisierung & Hardening', 'kpi' => 'Deployment-Health'],
        ][$template] ?? [];

        $labels = array_merge(self::DEFAULT_META_LABELS, is_array($profile['meta_labels'] ?? null) ? $profile['meta_labels'] : []);

        $map = [
            (string)($labels['audience'] ?? 'Zielgruppe') => trim((string)($settings['hub_meta_audience'] ?? '')) ?: (string)($defaults['audience'] ?? ''),
            (string)($labels['owner'] ?? 'Verantwortlich') => trim((string)($settings['hub_meta_owner'] ?? '')) ?: (string)($defaults['owner'] ?? ''),
            (string)($labels['update_cycle'] ?? 'Update-Zyklus') => trim((string)($settings['hub_meta_update_cycle'] ?? '')) ?: (string)($defaults['cycle'] ?? ''),
            (string)($labels['focus'] ?? 'Fokus') => trim((string)($settings['hub_meta_focus'] ?? '')) ?: (string)($defaults['focus'] ?? ''),
            (string)($labels['kpi'] ?? 'KPI') => trim((string)($settings['hub_meta_kpi'] ?? '')) ?: (string)($defaults['kpi'] ?? ''),
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

    private function buildHubStyleVariables(array $colors): string
    {
        $palette = array_merge($this->getDefaultTemplateColors('general-it'), $colors);

        $pairs = [
            '--cms-hub-hero-start' => $this->normalizeColorValue((string)($palette['hero_start'] ?? '#1f2937'), '#1f2937'),
            '--cms-hub-hero-end' => $this->normalizeColorValue((string)($palette['hero_end'] ?? '#0f172a'), '#0f172a'),
            '--cms-hub-accent' => $this->normalizeColorValue((string)($palette['accent'] ?? '#2563eb'), '#2563eb'),
            '--cms-hub-surface' => $this->normalizeColorValue((string)($palette['surface'] ?? '#ffffff'), '#ffffff'),
            '--cms-hub-card-bg' => $this->normalizeColorValue((string)($palette['card_background'] ?? '#ffffff'), '#ffffff'),
            '--cms-hub-card-text' => $this->normalizeColorValue((string)($palette['card_text'] ?? '#0f172a'), '#0f172a'),
            '--cms-hub-section-bg' => $this->normalizeColorValue((string)($palette['section_background'] ?? '#ffffff'), '#ffffff'),
        ];

        $chunks = [];
        foreach ($pairs as $key => $value) {
            $chunks[] = $key . ':' . $value;
        }

        return implode(';', $chunks);
    }

    private function normalizeColorValue(string $value, string $fallback): string
    {
        $value = trim($value);
        if ((bool)preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        return strtolower($fallback);
    }

    private function getDefaultCardSchema(): array
    {
        return [
            'columns' => 2,
            'title_label' => 'Titel',
            'summary_label' => 'Kurzbeschreibung',
            'badge_label' => 'Badge',
            'meta_left_label' => 'Meta links',
            'meta_right_label' => 'Meta rechts',
            'image_label' => 'Bild-URL',
            'image_alt_label' => 'Bild-Alt',
            'button_text_label' => 'Button-Text',
            'button_link_label' => 'Button-Link',
        ];
    }

    private function getDefaultTemplateColors(string $template): array
    {
        return match ($template) {
            'microsoft-365' => ['hero_start' => '#0f4c81', 'hero_end' => '#2563eb', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f8fbff'],
            'datenschutz' => ['hero_start' => '#0f766e', 'hero_end' => '#115e59', 'accent' => '#0f766e', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f0fdfa'],
            'compliance' => ['hero_start' => '#4c1d95', 'hero_end' => '#6d28d9', 'accent' => '#6d28d9', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#faf5ff'],
            'linux' => ['hero_start' => '#111827', 'hero_end' => '#b45309', 'accent' => '#b45309', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f3f4f6', 'section_background' => '#111827'],
            default => ['hero_start' => '#1f2937', 'hero_end' => '#0f172a', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#ffffff'],
        };
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