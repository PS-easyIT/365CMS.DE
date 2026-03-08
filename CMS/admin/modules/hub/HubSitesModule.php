<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class HubSitesModule
{
    private Database $db;
    private string $prefix;
    private ?bool $hasTableSlugColumn = null;
    private ?array $templateProfilesCache = null;

    private const TEMPLATE_SETTING_KEY = 'hub_site_templates';

    private const TEMPLATE_OPTIONS = [
        'general-it' => 'IT Themen Allgemein',
        'microsoft-365' => 'Microsoft 365',
        'datenschutz' => 'Datenschutz',
        'compliance' => 'Compliance',
        'linux' => 'Linux',
    ];

    private const DEFAULT_SETTINGS = [
        'content_mode' => 'hub',
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

    private const TEMPLATE_PRESETS = [
        'general-it' => [
            'summary' => 'Breites IT-Hub für Strategie, Betrieb, Infrastruktur und Security.',
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
            'meta' => [
                'audience' => 'IT-Leitung',
                'owner' => 'IT-Operations',
                'update_cycle' => 'Monatlich',
                'focus' => 'Architektur & Betrieb',
                'kpi' => 'Servicequalität',
            ],
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
            'summary' => 'Modern-Work-Hub mit Fokus auf Collaboration, Adoption und Governance.',
            'card_design' => ['layout' => 'feature', 'image_position' => 'left', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
            'meta' => [
                'audience' => 'Workspace & Modern Work',
                'owner' => 'M365-Team',
                'update_cycle' => '14-tägig',
                'focus' => 'Adoption & Governance',
                'kpi' => 'Nutzungsquote',
            ],
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
            'summary' => 'Strukturiertes Datenschutz-Hub für Nachweise, Prozesse und Rechtsgrundlagen.',
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked'],
            'meta' => [
                'audience' => 'DSB & Fachbereiche',
                'owner' => 'Datenschutz',
                'update_cycle' => 'Quartalsweise',
                'focus' => 'Nachweise & Prozesse',
                'kpi' => 'Bearbeitungsstatus',
            ],
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
            'summary' => 'Governance-/Compliance-Hub für Policies, Audits und Kontrolllandschaften.',
            'card_design' => ['layout' => 'feature', 'image_position' => 'right', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
            'meta' => [
                'audience' => 'Management & Audit',
                'owner' => 'Compliance Office',
                'update_cycle' => 'Monatlich',
                'focus' => 'Kontrollen & Policies',
                'kpi' => 'Audit-Readiness',
            ],
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
            'summary' => 'Technisches Linux-Hub für Platform Engineering, Automatisierung und Hardening.',
            'card_design' => ['layout' => 'compact', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked'],
            'meta' => [
                'audience' => 'Admins & Platform Team',
                'owner' => 'Platform Engineering',
                'update_cycle' => 'Wöchentlich',
                'focus' => 'Automatisierung & Hardening',
                'kpi' => 'Deployment-Health',
            ],
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

    public function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getListData(): array
    {
        $search = trim((string)($_GET['q'] ?? ''));
        $where = '';
        $params = [];
        $selectSlug = $this->hasTableSlugColumn() ? 'table_slug,' : "'' AS table_slug,";

        if ($search !== '') {
            $where = ' AND (table_name LIKE ? OR description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $rows = $this->db->get_results(
            "SELECT id, table_name, description, {$selectSlug} rows_json, settings_json,
                    JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) AS hub_slug,
                    created_at, updated_at
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'{$where}
             ORDER BY updated_at DESC
             LIMIT 200",
            $params
        ) ?: [];

        $sites = array_map(function ($row): array {
            $item = (array)$row;
            $settings = json_decode((string)($item['settings_json'] ?? '{}'), true) ?: [];
            return [
                'id' => (int)($item['id'] ?? 0),
                'table_name' => (string)($item['table_name'] ?? ''),
                'description' => (string)($item['description'] ?? ''),
                'hub_slug' => (string)(($item['hub_slug'] ?? '') !== '' ? $item['hub_slug'] : ($item['table_slug'] ?? '')),
                'template' => (string)($settings['hub_template'] ?? 'general-it'),
                'card_count' => count(json_decode((string)($item['rows_json'] ?? '[]'), true) ?: []),
                'updated_at' => (string)($item['updated_at'] ?? $item['created_at'] ?? ''),
            ];
        }, $rows);

        return [
            'sites' => $sites,
            'total' => count($sites),
            'search' => $search,
            'templateOptions' => $this->getTemplateProfileChoices(),
        ];
    }

    public function getEditData(?int $id): array
    {
        $site = null;

        if ($id !== null) {
            $row = $this->db->get_row(
                "SELECT * FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1",
                [$id]
            );

            if ($row) {
                $site = (array)$row;
                $site['cards'] = json_decode((string)($site['rows_json'] ?? '[]'), true) ?: [];
                $site['settings'] = array_merge(
                    self::DEFAULT_SETTINGS,
                    json_decode((string)($site['settings_json'] ?? '{}'), true) ?: []
                );
                if (($site['settings']['hub_slug'] ?? '') === '' && !empty($site['table_slug'])) {
                    $site['settings']['hub_slug'] = (string)$site['table_slug'];
                }
            }
        }

        return [
            'site' => $site,
            'isNew' => $site === null,
            'defaults' => self::DEFAULT_SETTINGS,
            'templateOptions' => $this->getTemplateProfileChoices(),
            'templateProfiles' => $this->getTemplateProfiles(),
        ];
    }

    public function getTemplateListData(): array
    {
        $profiles = $this->getTemplateProfiles();
        $items = [];

        foreach ($profiles as $key => $profile) {
            $items[] = [
                'key' => $key,
                'label' => (string)($profile['label'] ?? $key),
                'base_template' => (string)($profile['base_template'] ?? 'general-it'),
                'summary' => (string)($profile['summary'] ?? ''),
                'usage_count' => $this->countTemplateUsage($key),
            ];
        }

        usort($items, static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return [
            'templates' => $items,
            'baseTemplateOptions' => self::TEMPLATE_OPTIONS,
        ];
    }

    public function getTemplateEditData(?string $key): array
    {
        $profiles = $this->getTemplateProfiles();
        $normalizedKey = $key !== null ? $this->sanitizeTemplateKey($key) : '';
        $template = null;

        if ($normalizedKey !== '' && isset($profiles[$normalizedKey])) {
            $template = $profiles[$normalizedKey];
            $template['key'] = $normalizedKey;
        }

        if ($template === null) {
            $template = [
                'key' => '',
                'label' => '',
                'base_template' => 'general-it',
                'summary' => '',
                'meta' => [
                    'audience' => '',
                    'owner' => '',
                    'update_cycle' => '',
                    'focus' => '',
                    'kpi' => '',
                ],
                'meta_labels' => [
                    'audience' => 'Zielgruppe',
                    'owner' => 'Verantwortlich',
                    'update_cycle' => 'Update-Zyklus',
                    'focus' => 'Fokus',
                    'kpi' => 'KPI',
                ],
                'links' => [],
                'sections' => [],
                'colors' => [
                    'hero_start' => '#1f2937',
                    'hero_end' => '#0f172a',
                    'accent' => '#2563eb',
                    'surface' => '#ffffff',
                    'card_background' => '#ffffff',
                    'card_text' => '#0f172a',
                    'section_background' => '#ffffff',
                ],
                'card_schema' => [
                    'columns' => 2,
                    'min_cards' => 1,
                    'max_cards' => 3,
                    'title_label' => 'Titel',
                    'summary_label' => 'Kurzbeschreibung',
                    'badge_label' => 'Badge',
                    'meta_left_label' => 'Meta links',
                    'meta_right_label' => 'Meta rechts',
                    'image_label' => 'Bild-URL',
                    'image_alt_label' => 'Bild-Alt',
                    'button_text_label' => 'Button-Text',
                    'button_link_label' => 'Button-Link',
                ],
                'card_design' => [
                    'layout' => 'standard',
                    'image_position' => 'top',
                    'image_fit' => 'cover',
                    'image_ratio' => 'wide',
                    'meta_layout' => 'split',
                ],
                'starter_cards' => [],
            ];
        }

        return [
            'template' => $template,
            'isNew' => $normalizedKey === '' || !isset($profiles[$normalizedKey]),
            'baseTemplateOptions' => self::TEMPLATE_OPTIONS,
        ];
    }

    public function save(array $post): array
    {
        $id = (int)($post['id'] ?? 0);
        $name = trim(strip_tags((string)($post['site_name'] ?? '')));
        $description = trim(strip_tags((string)($post['description'] ?? '')));
        $cards = json_decode((string)($post['cards_json'] ?? '[]'), true);

        if ($name === '') {
            return ['success' => false, 'error' => 'Name darf nicht leer sein.'];
        }

        if (!is_array($cards)) {
            $cards = [];
        }

        $slug = $this->buildUniqueHubSlug($name, $id > 0 ? $id : null);

        $existingSettings = $id > 0 ? $this->getExistingHubSettings($id) : self::DEFAULT_SETTINGS;
        $templateChoices = $this->getTemplateProfileChoices();

        $settings = [
            'content_mode' => 'hub',
            'hub_slug' => $slug,
            'hub_template' => array_key_exists((string)($post['hub_template'] ?? ''), $templateChoices) ? (string)$post['hub_template'] : 'general-it',
            'hub_badge' => mb_substr(trim(strip_tags((string)($post['hub_badge'] ?? ''))), 0, 80),
            'hub_hero_title' => mb_substr(trim(strip_tags((string)($post['hub_hero_title'] ?? ''))), 0, 160),
            'hub_hero_text' => mb_substr(trim((string)($post['hub_hero_text'] ?? '')), 0, 1200),
            'hub_cta_label' => mb_substr(trim(strip_tags((string)($post['hub_cta_label'] ?? ''))), 0, 60),
            'hub_cta_url' => filter_var((string)($post['hub_cta_url'] ?? ''), FILTER_SANITIZE_URL),
            'hub_meta_audience' => mb_substr(trim(strip_tags((string)($post['hub_meta_audience'] ?? ''))), 0, 120),
            'hub_meta_owner' => mb_substr(trim(strip_tags((string)($post['hub_meta_owner'] ?? ''))), 0, 120),
            'hub_meta_update_cycle' => mb_substr(trim(strip_tags((string)($post['hub_meta_update_cycle'] ?? ''))), 0, 120),
            'hub_meta_focus' => mb_substr(trim(strip_tags((string)($post['hub_meta_focus'] ?? ''))), 0, 160),
            'hub_meta_kpi' => mb_substr(trim(strip_tags((string)($post['hub_meta_kpi'] ?? ''))), 0, 120),
            'hub_links_json' => array_key_exists('hub_links_json', $post)
                ? $this->normalizeJsonArray((string)$post['hub_links_json'], 'link')
                : (string)($existingSettings['hub_links_json'] ?? '[]'),
            'hub_sections_json' => array_key_exists('hub_sections_json', $post)
                ? $this->normalizeJsonArray((string)$post['hub_sections_json'], 'section')
                : (string)($existingSettings['hub_sections_json'] ?? '[]'),
            'hub_card_layout' => array_key_exists('hub_card_layout', $post)
                ? $this->normalizeSetting((string)$post['hub_card_layout'], ['standard', 'feature', 'compact'], 'standard')
                : (string)($existingSettings['hub_card_layout'] ?? 'standard'),
            'hub_card_image_position' => array_key_exists('hub_card_image_position', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_position'], ['top', 'left', 'right'], 'top')
                : (string)($existingSettings['hub_card_image_position'] ?? 'top'),
            'hub_card_image_fit' => array_key_exists('hub_card_image_fit', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_fit'], ['cover', 'contain'], 'cover')
                : (string)($existingSettings['hub_card_image_fit'] ?? 'cover'),
            'hub_card_image_ratio' => array_key_exists('hub_card_image_ratio', $post)
                ? $this->normalizeSetting((string)$post['hub_card_image_ratio'], ['wide', 'square', 'portrait'], 'wide')
                : (string)($existingSettings['hub_card_image_ratio'] ?? 'wide'),
            'hub_card_meta_layout' => array_key_exists('hub_card_meta_layout', $post)
                ? $this->normalizeSetting((string)$post['hub_card_meta_layout'], ['split', 'stacked'], 'split')
                : (string)($existingSettings['hub_card_meta_layout'] ?? 'split'),
        ];

        $normalizedCards = [];
        foreach ($cards as $card) {
            if (!is_array($card)) {
                continue;
            }

            $title = mb_substr(trim(strip_tags((string)($card['title'] ?? ''))), 0, 160);
            $url = filter_var((string)($card['url'] ?? ''), FILTER_SANITIZE_URL);
            if ($title === '') {
                continue;
            }

            $normalizedCards[] = [
                'title' => $title,
                'url' => $url !== '' ? $url : '#',
                'summary' => mb_substr(trim((string)($card['summary'] ?? '')), 0, 600),
                'badge' => mb_substr(trim(strip_tags((string)($card['badge'] ?? ''))), 0, 80),
                'meta' => mb_substr(trim(strip_tags((string)($card['meta'] ?? ''))), 0, 120),
                'meta_left' => mb_substr(trim(strip_tags((string)($card['meta_left'] ?? ''))), 0, 120),
                'meta_right' => mb_substr(trim(strip_tags((string)($card['meta_right'] ?? ''))), 0, 120),
                'image_url' => mb_substr(trim((string)($card['image_url'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim(strip_tags((string)($card['image_alt'] ?? ''))), 0, 160),
                'button_text' => mb_substr(trim(strip_tags((string)($card['button_text'] ?? ''))), 0, 80),
                'button_link' => mb_substr(trim((string)($card['button_link'] ?? '')), 0, 500),
            ];
        }

        try {
            if ($id > 0) {
                $params = [
                    $name,
                    $description,
                    json_encode($normalizedCards, JSON_UNESCAPED_UNICODE),
                    json_encode($settings, JSON_UNESCAPED_UNICODE),
                ];

                $sql = "UPDATE {$this->prefix}site_tables
                        SET table_name = ?, description = ?, columns_json = '[]', rows_json = ?, settings_json = ?";

                if ($this->hasTableSlugColumn()) {
                    $sql .= ', table_slug = ?';
                    $params[] = $slug;
                }

                $sql .= ', updated_at = NOW() WHERE id = ?';
                $params[] = $id;

                $this->db->execute($sql, $params);

                return ['success' => true, 'id' => $id, 'slug' => $slug, 'message' => 'Routing / Hub Site aktualisiert.'];
            }

            $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json'];
            $placeholders = ['?', '?', "'[]'", '?', '?'];
            $params = [
                $name,
                $description,
                json_encode($normalizedCards, JSON_UNESCAPED_UNICODE),
                json_encode($settings, JSON_UNESCAPED_UNICODE),
            ];

            if ($this->hasTableSlugColumn()) {
                $columns[] = 'table_slug';
                $placeholders[] = '?';
                $params[] = $slug;
            }

            $columns[] = 'created_at';
            $columns[] = 'updated_at';
            $placeholders[] = 'NOW()';
            $placeholders[] = 'NOW()';

            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $placeholders) . ")",
                $params
            );

            return ['success' => true, 'id' => (int)$this->db->insert_id(), 'slug' => $slug, 'message' => 'Routing / Hub Site erstellt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        try {
            $this->db->execute("DELETE FROM {$this->prefix}site_tables WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Routing / Hub Site gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen.'];
        }
    }

    public function duplicate(int $id): array
    {
        $source = $this->db->get_row(
            "SELECT * FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1",
            [$id]
        );

        if (!$source) {
            return ['success' => false, 'error' => 'Routing / Hub Site nicht gefunden.'];
        }

        $data = (array)$source;

        try {
            $copyName = ((string)($data['table_name'] ?? 'Routing / Hub Site')) . ' (Kopie)';
            $settings = json_decode((string)($data['settings_json'] ?? '{}'), true) ?: [];
            $settings = array_merge(self::DEFAULT_SETTINGS, $settings);
            $settings['hub_slug'] = $this->buildUniqueHubSlug($copyName, null);

            $columns = ['table_name', 'description', 'columns_json', 'rows_json', 'settings_json', 'created_at', 'updated_at'];
            $placeholders = ['?', '?', '?', '?', '?', 'NOW()', 'NOW()'];
            $params = [
                $copyName,
                (string)($data['description'] ?? ''),
                (string)($data['columns_json'] ?? '[]'),
                (string)($data['rows_json'] ?? '[]'),
                json_encode($settings, JSON_UNESCAPED_UNICODE),
            ];

            if ($this->hasTableSlugColumn()) {
                $columns[] = 'table_slug';
                $placeholders[] = '?';
                $params[] = (string)$settings['hub_slug'];
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}site_tables (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $placeholders) . ")",
                $params
            );

            return ['success' => true, 'id' => (int)$this->db->insert_id(), 'slug' => (string)$settings['hub_slug'], 'message' => 'Routing / Hub Site dupliziert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Duplizieren.'];
        }
    }

    public function saveTemplate(array $post): array
    {
        $profiles = $this->getTemplateProfiles();
        $existingKey = $this->sanitizeTemplateKey((string)($post['template_key'] ?? ''));
        $label = mb_substr(trim(strip_tags((string)($post['template_label'] ?? ''))), 0, 120);
        $baseTemplate = $this->normalizeSetting((string)($post['base_template'] ?? 'general-it'), array_keys(self::TEMPLATE_OPTIONS), 'general-it');

        if ($label === '') {
            return ['success' => false, 'error' => 'Template-Name darf nicht leer sein.'];
        }

        $key = $existingKey !== '' && isset($profiles[$existingKey])
            ? $existingKey
            : $this->buildUniqueTemplateKey($label, $profiles);

        $profiles[$key] = [
            'label' => $label,
            'base_template' => $baseTemplate,
            'summary' => mb_substr(trim((string)($post['template_summary'] ?? '')), 0, 600),
            'meta' => [
                'audience' => mb_substr(trim(strip_tags((string)($post['template_meta_audience'] ?? ''))), 0, 120),
                'owner' => mb_substr(trim(strip_tags((string)($post['template_meta_owner'] ?? ''))), 0, 120),
                'update_cycle' => mb_substr(trim(strip_tags((string)($post['template_meta_update_cycle'] ?? ''))), 0, 120),
                'focus' => mb_substr(trim(strip_tags((string)($post['template_meta_focus'] ?? ''))), 0, 160),
                'kpi' => mb_substr(trim(strip_tags((string)($post['template_meta_kpi'] ?? ''))), 0, 120),
            ],
            'meta_labels' => [
                'audience' => mb_substr(trim(strip_tags((string)($post['template_label_audience'] ?? 'Zielgruppe'))), 0, 80),
                'owner' => mb_substr(trim(strip_tags((string)($post['template_label_owner'] ?? 'Verantwortlich'))), 0, 80),
                'update_cycle' => mb_substr(trim(strip_tags((string)($post['template_label_update_cycle'] ?? 'Update-Zyklus'))), 0, 80),
                'focus' => mb_substr(trim(strip_tags((string)($post['template_label_focus'] ?? 'Fokus'))), 0, 80),
                'kpi' => mb_substr(trim(strip_tags((string)($post['template_label_kpi'] ?? 'KPI'))), 0, 80),
            ],
            'links' => json_decode($this->normalizeJsonArray((string)($post['template_links_json'] ?? '[]'), 'link'), true) ?: [],
            'sections' => json_decode($this->normalizeJsonArray((string)($post['template_sections_json'] ?? '[]'), 'section'), true) ?: [],
            'colors' => [
                'hero_start' => $this->normalizeColor((string)($post['template_color_hero_start'] ?? '#1f2937'), '#1f2937'),
                'hero_end' => $this->normalizeColor((string)($post['template_color_hero_end'] ?? '#0f172a'), '#0f172a'),
                'accent' => $this->normalizeColor((string)($post['template_color_accent'] ?? '#2563eb'), '#2563eb'),
                'surface' => $this->normalizeColor((string)($post['template_color_surface'] ?? '#ffffff'), '#ffffff'),
                'card_background' => $this->normalizeColor((string)($post['template_color_card_background'] ?? '#ffffff'), '#ffffff'),
                'card_text' => $this->normalizeColor((string)($post['template_color_card_text'] ?? '#0f172a'), '#0f172a'),
                'section_background' => $this->normalizeColor((string)($post['template_color_section_background'] ?? '#ffffff'), '#ffffff'),
            ],
            'card_schema' => [
                'columns' => $this->normalizeNumber((int)($post['template_card_columns'] ?? 2), 1, 3, 2),
                'min_cards' => 1,
                'max_cards' => 3,
                'title_label' => mb_substr(trim(strip_tags((string)($post['template_card_title_label'] ?? 'Titel'))), 0, 80),
                'summary_label' => mb_substr(trim(strip_tags((string)($post['template_card_summary_label'] ?? 'Kurzbeschreibung'))), 0, 80),
                'badge_label' => mb_substr(trim(strip_tags((string)($post['template_card_badge_label'] ?? 'Badge'))), 0, 80),
                'meta_left_label' => mb_substr(trim(strip_tags((string)($post['template_card_meta_left_label'] ?? 'Meta links'))), 0, 80),
                'meta_right_label' => mb_substr(trim(strip_tags((string)($post['template_card_meta_right_label'] ?? 'Meta rechts'))), 0, 80),
                'image_label' => mb_substr(trim(strip_tags((string)($post['template_card_image_label'] ?? 'Bild-URL'))), 0, 80),
                'image_alt_label' => mb_substr(trim(strip_tags((string)($post['template_card_image_alt_label'] ?? 'Bild-Alt'))), 0, 80),
                'button_text_label' => mb_substr(trim(strip_tags((string)($post['template_card_button_text_label'] ?? 'Button-Text'))), 0, 80),
                'button_link_label' => mb_substr(trim(strip_tags((string)($post['template_card_button_link_label'] ?? 'Button-Link'))), 0, 80),
            ],
            'card_design' => [
                'layout' => $this->normalizeSetting((string)($post['hub_card_layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard'),
                'image_position' => $this->normalizeSetting((string)($post['hub_card_image_position'] ?? 'top'), ['top', 'left', 'right'], 'top'),
                'image_fit' => $this->normalizeSetting((string)($post['hub_card_image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
                'image_ratio' => $this->normalizeSetting((string)($post['hub_card_image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide'),
                'meta_layout' => $this->normalizeSetting((string)($post['hub_card_meta_layout'] ?? 'split'), ['split', 'stacked'], 'split'),
            ],
            'starter_cards' => $this->normalizeStarterCards((string)($post['template_starter_cards_json'] ?? '[]')),
        ];

        $this->saveTemplateProfiles($profiles);

        return ['success' => true, 'key' => $key, 'message' => 'Template gespeichert.'];
    }

    public function duplicateTemplate(string $key): array
    {
        $profiles = $this->getTemplateProfiles();
        $key = $this->sanitizeTemplateKey($key);

        if ($key === '' || !isset($profiles[$key])) {
            return ['success' => false, 'error' => 'Template nicht gefunden.'];
        }

        $copy = $profiles[$key];
        $copy['label'] = ((string)($copy['label'] ?? 'Template')) . ' (Kopie)';
        $newKey = $this->buildUniqueTemplateKey((string)$copy['label'], $profiles);
        $profiles[$newKey] = $copy;
        $this->saveTemplateProfiles($profiles);

        return ['success' => true, 'key' => $newKey, 'message' => 'Template kopiert.'];
    }

    public function deleteTemplate(string $key): array
    {
        $profiles = $this->getTemplateProfiles();
        $key = $this->sanitizeTemplateKey($key);

        if ($key === '' || !isset($profiles[$key])) {
            return ['success' => false, 'error' => 'Template nicht gefunden.'];
        }

        if (isset(self::TEMPLATE_PRESETS[$key])) {
            return ['success' => false, 'error' => 'Standard-Templates können nicht gelöscht werden. Du kannst sie aber bearbeiten, kopieren und umbenennen.'];
        }

        if ($this->countTemplateUsage($key) > 0) {
            return ['success' => false, 'error' => 'Template wird noch von Hub-Sites verwendet und kann nicht gelöscht werden.'];
        }

        unset($profiles[$key]);
        $this->saveTemplateProfiles($profiles);

        return ['success' => true, 'message' => 'Template gelöscht.'];
    }

    private function buildUniqueHubSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = $this->sanitizeSlug($title);
        if ($baseSlug === '' || $this->isReservedSlug($baseSlug)) {
            $baseSlug = 'hub-site';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->hubSlugExists($slug, $excludeId) || $this->pageSlugExists($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function hubSlugExists(string $slug, ?int $excludeId = null): bool
    {
                $slugSql = $this->hasTableSlugColumn()
                        ? "(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ? OR table_slug = ?)"
                        : "JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_slug')) = ?";

                $sql = "SELECT id
                                FROM {$this->prefix}site_tables
                                WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
                                    AND {$slugSql}";
                $params = $this->hasTableSlugColumn() ? [$slug, $slug] : [$slug];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return $this->db->get_var($sql . ' LIMIT 1', $params) !== null;
    }

    private function pageSlugExists(string $slug): bool
    {
        return $this->db->get_var(
            "SELECT id FROM {$this->prefix}pages WHERE slug = ? LIMIT 1",
            [$slug]
        ) !== null || $this->isReservedSlug($slug);
    }

    private function isReservedSlug(string $slug): bool
    {
        static $reserved = [
            'admin', 'api', 'login', 'logout', 'register', 'member', 'dashboard', 'order',
            'search', 'blog', 'sitemap.xml', 'robots.txt', 'cookie-einstellungen', 'site-table',
        ];

        return in_array($slug, $reserved, true);
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = (string)preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function normalizeJsonArray(string $json, string $mode): string
    {
        $items = json_decode($json, true);
        if (!is_array($items)) {
            return '[]';
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ($mode === 'link') {
                $label = mb_substr(trim(strip_tags((string)($item['label'] ?? ''))), 0, 80);
                $url = mb_substr(trim((string)($item['url'] ?? '')), 0, 240);
                if ($label === '') {
                    continue;
                }
                $normalized[] = [
                    'label' => $label,
                    'url' => $url !== '' ? $url : '#',
                ];
                continue;
            }

            $title = mb_substr(trim(strip_tags((string)($item['title'] ?? ''))), 0, 120);
            $text = mb_substr(trim((string)($item['text'] ?? '')), 0, 600);
            $actionLabel = mb_substr(trim(strip_tags((string)($item['actionLabel'] ?? ''))), 0, 80);
            $actionUrl = mb_substr(trim((string)($item['actionUrl'] ?? '')), 0, 240);

            if ($title === '' && $text === '') {
                continue;
            }

            $normalized[] = [
                'title' => $title,
                'text' => $text,
                'actionLabel' => $actionLabel,
                'actionUrl' => $actionUrl,
            ];
        }

        return json_encode($normalized, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function normalizeSetting(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function getTemplateProfileChoices(): array
    {
        $choices = [];
        foreach ($this->getTemplateProfiles() as $key => $profile) {
            $choices[$key] = (string)($profile['label'] ?? $key);
        }

        return $choices;
    }

    private function getTemplateProfiles(): array
    {
        if ($this->templateProfilesCache !== null) {
            return $this->templateProfilesCache;
        }

        $defaultProfiles = $this->buildDefaultTemplateProfiles();
        $row = $this->db->get_row(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            [self::TEMPLATE_SETTING_KEY]
        );

        $stored = [];
        if ($row && !empty($row->option_value)) {
            $decoded = json_decode((string)$row->option_value, true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $profile) {
                    if (!is_array($profile)) {
                        continue;
                    }
                    $normalizedKey = $this->sanitizeTemplateKey((string)$key);
                    if ($normalizedKey === '') {
                        continue;
                    }
                    $stored[$normalizedKey] = $this->normalizeTemplateProfile($normalizedKey, $profile, $defaultProfiles[$normalizedKey] ?? null);
                }
            }
        }

        foreach ($defaultProfiles as $key => $profile) {
            if (!isset($stored[$key])) {
                $stored[$key] = $profile;
            }
        }

        $this->templateProfilesCache = $stored;
        return $this->templateProfilesCache;
    }

    private function saveTemplateProfiles(array $profiles): void
    {
        $payload = [];
        foreach ($profiles as $key => $profile) {
            if (!is_array($profile)) {
                continue;
            }
            $normalizedKey = $this->sanitizeTemplateKey((string)$key);
            if ($normalizedKey === '') {
                continue;
            }
            $payload[$normalizedKey] = $this->normalizeTemplateProfile($normalizedKey, $profile, null);
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $exists = (int)($this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [self::TEMPLATE_SETTING_KEY]) ?? 0);
        if ($exists > 0) {
            $this->db->update('settings', ['option_value' => $json], ['option_name' => self::TEMPLATE_SETTING_KEY]);
        } else {
            $this->db->insert('settings', ['option_name' => self::TEMPLATE_SETTING_KEY, 'option_value' => $json]);
        }

        $this->templateProfilesCache = $payload;
    }

    private function buildDefaultTemplateProfiles(): array
    {
        $profiles = [];
        foreach (self::TEMPLATE_PRESETS as $key => $preset) {
            $profiles[$key] = $this->normalizeTemplateProfile($key, [
                'label' => self::TEMPLATE_OPTIONS[$key] ?? $key,
                'base_template' => $key,
                'summary' => $preset['summary'] ?? '',
                'meta' => $preset['meta'] ?? [],
                'meta_labels' => [
                    'audience' => 'Zielgruppe',
                    'owner' => 'Verantwortlich',
                    'update_cycle' => 'Update-Zyklus',
                    'focus' => 'Fokus',
                    'kpi' => 'KPI',
                ],
                'links' => $preset['links'] ?? [],
                'sections' => $preset['sections'] ?? [],
                'colors' => $this->defaultTemplateColors($key),
                'card_schema' => $this->defaultCardSchema(),
                'card_design' => $preset['card_design'] ?? [],
                'starter_cards' => $this->defaultStarterCards($key),
            ], null);
        }

        return $profiles;
    }

    private function normalizeTemplateProfile(string $key, array $profile, ?array $fallback): array
    {
        $fallback = $fallback ?? [
            'label' => $key,
            'base_template' => 'general-it',
            'summary' => '',
            'meta' => ['audience' => '', 'owner' => '', 'update_cycle' => '', 'focus' => '', 'kpi' => ''],
            'meta_labels' => ['audience' => 'Zielgruppe', 'owner' => 'Verantwortlich', 'update_cycle' => 'Update-Zyklus', 'focus' => 'Fokus', 'kpi' => 'KPI'],
            'links' => [],
            'sections' => [],
            'colors' => $this->defaultTemplateColors('general-it'),
            'card_schema' => $this->defaultCardSchema(),
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split'],
            'starter_cards' => [],
        ];

        return [
            'label' => mb_substr(trim(strip_tags((string)($profile['label'] ?? $fallback['label']))), 0, 120),
            'base_template' => $this->normalizeSetting((string)($profile['base_template'] ?? $fallback['base_template']), array_keys(self::TEMPLATE_OPTIONS), 'general-it'),
            'summary' => mb_substr(trim((string)($profile['summary'] ?? $fallback['summary'])), 0, 600),
            'meta' => [
                'audience' => mb_substr(trim(strip_tags((string)($profile['meta']['audience'] ?? $fallback['meta']['audience'] ?? ''))), 0, 120),
                'owner' => mb_substr(trim(strip_tags((string)($profile['meta']['owner'] ?? $fallback['meta']['owner'] ?? ''))), 0, 120),
                'update_cycle' => mb_substr(trim(strip_tags((string)($profile['meta']['update_cycle'] ?? $fallback['meta']['update_cycle'] ?? ''))), 0, 120),
                'focus' => mb_substr(trim(strip_tags((string)($profile['meta']['focus'] ?? $fallback['meta']['focus'] ?? ''))), 0, 160),
                'kpi' => mb_substr(trim(strip_tags((string)($profile['meta']['kpi'] ?? $fallback['meta']['kpi'] ?? ''))), 0, 120),
            ],
            'meta_labels' => [
                'audience' => mb_substr(trim(strip_tags((string)($profile['meta_labels']['audience'] ?? $fallback['meta_labels']['audience'] ?? 'Zielgruppe'))), 0, 80),
                'owner' => mb_substr(trim(strip_tags((string)($profile['meta_labels']['owner'] ?? $fallback['meta_labels']['owner'] ?? 'Verantwortlich'))), 0, 80),
                'update_cycle' => mb_substr(trim(strip_tags((string)($profile['meta_labels']['update_cycle'] ?? $fallback['meta_labels']['update_cycle'] ?? 'Update-Zyklus'))), 0, 80),
                'focus' => mb_substr(trim(strip_tags((string)($profile['meta_labels']['focus'] ?? $fallback['meta_labels']['focus'] ?? 'Fokus'))), 0, 80),
                'kpi' => mb_substr(trim(strip_tags((string)($profile['meta_labels']['kpi'] ?? $fallback['meta_labels']['kpi'] ?? 'KPI'))), 0, 80),
            ],
            'links' => json_decode($this->normalizeJsonArray(json_encode($profile['links'] ?? $fallback['links'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'link'), true) ?: [],
            'sections' => json_decode($this->normalizeJsonArray(json_encode($profile['sections'] ?? $fallback['sections'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'section'), true) ?: [],
            'colors' => [
                'hero_start' => $this->normalizeColor((string)($profile['colors']['hero_start'] ?? $fallback['colors']['hero_start'] ?? '#1f2937'), '#1f2937'),
                'hero_end' => $this->normalizeColor((string)($profile['colors']['hero_end'] ?? $fallback['colors']['hero_end'] ?? '#0f172a'), '#0f172a'),
                'accent' => $this->normalizeColor((string)($profile['colors']['accent'] ?? $fallback['colors']['accent'] ?? '#2563eb'), '#2563eb'),
                'surface' => $this->normalizeColor((string)($profile['colors']['surface'] ?? $fallback['colors']['surface'] ?? '#ffffff'), '#ffffff'),
                'card_background' => $this->normalizeColor((string)($profile['colors']['card_background'] ?? $fallback['colors']['card_background'] ?? '#ffffff'), '#ffffff'),
                'card_text' => $this->normalizeColor((string)($profile['colors']['card_text'] ?? $fallback['colors']['card_text'] ?? '#0f172a'), '#0f172a'),
                'section_background' => $this->normalizeColor((string)($profile['colors']['section_background'] ?? $fallback['colors']['section_background'] ?? '#ffffff'), '#ffffff'),
            ],
            'card_schema' => [
                'columns' => $this->normalizeNumber((int)($profile['card_schema']['columns'] ?? $fallback['card_schema']['columns'] ?? 2), 1, 3, 2),
                'min_cards' => 1,
                'max_cards' => 3,
                'title_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['title_label'] ?? $fallback['card_schema']['title_label'] ?? 'Titel'))), 0, 80),
                'summary_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['summary_label'] ?? $fallback['card_schema']['summary_label'] ?? 'Kurzbeschreibung'))), 0, 80),
                'badge_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['badge_label'] ?? $fallback['card_schema']['badge_label'] ?? 'Badge'))), 0, 80),
                'meta_left_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['meta_left_label'] ?? $fallback['card_schema']['meta_left_label'] ?? 'Meta links'))), 0, 80),
                'meta_right_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['meta_right_label'] ?? $fallback['card_schema']['meta_right_label'] ?? 'Meta rechts'))), 0, 80),
                'image_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['image_label'] ?? $fallback['card_schema']['image_label'] ?? 'Bild-URL'))), 0, 80),
                'image_alt_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['image_alt_label'] ?? $fallback['card_schema']['image_alt_label'] ?? 'Bild-Alt'))), 0, 80),
                'button_text_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['button_text_label'] ?? $fallback['card_schema']['button_text_label'] ?? 'Button-Text'))), 0, 80),
                'button_link_label' => mb_substr(trim(strip_tags((string)($profile['card_schema']['button_link_label'] ?? $fallback['card_schema']['button_link_label'] ?? 'Button-Link'))), 0, 80),
            ],
            'card_design' => [
                'layout' => $this->normalizeSetting((string)($profile['card_design']['layout'] ?? $fallback['card_design']['layout'] ?? 'standard'), ['standard', 'feature', 'compact'], 'standard'),
                'image_position' => $this->normalizeSetting((string)($profile['card_design']['image_position'] ?? $fallback['card_design']['image_position'] ?? 'top'), ['top', 'left', 'right'], 'top'),
                'image_fit' => $this->normalizeSetting((string)($profile['card_design']['image_fit'] ?? $fallback['card_design']['image_fit'] ?? 'cover'), ['cover', 'contain'], 'cover'),
                'image_ratio' => $this->normalizeSetting((string)($profile['card_design']['image_ratio'] ?? $fallback['card_design']['image_ratio'] ?? 'wide'), ['wide', 'square', 'portrait'], 'wide'),
                'meta_layout' => $this->normalizeSetting((string)($profile['card_design']['meta_layout'] ?? $fallback['card_design']['meta_layout'] ?? 'split'), ['split', 'stacked'], 'split'),
            ],
            'starter_cards' => $this->normalizeStarterCards(json_encode($profile['starter_cards'] ?? $fallback['starter_cards'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]'),
        ];
    }

    private function countTemplateUsage(string $key): int
    {
        return (int)($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
               AND JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_template')) = ?",
            [$key]
        ) ?? 0);
    }

    private function buildUniqueTemplateKey(string $label, array $profiles): string
    {
        $base = $this->sanitizeTemplateKey($label);
        if ($base === '') {
            $base = 'hub-template';
        }

        $key = $base;
        $suffix = 2;
        while (isset($profiles[$key])) {
            $key = $base . '-' . $suffix;
            $suffix++;
        }

        return $key;
    }

    private function sanitizeTemplateKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = (string)preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-');
    }

    private function getExistingHubSettings(int $id): array
    {
        $row = $this->db->get_row("SELECT settings_json FROM {$this->prefix}site_tables WHERE id = ? LIMIT 1", [$id]);
        if (!$row || empty($row->settings_json)) {
            return self::DEFAULT_SETTINGS;
        }

        $decoded = json_decode((string)$row->settings_json, true);
        return array_merge(self::DEFAULT_SETTINGS, is_array($decoded) ? $decoded : []);
    }

    private function normalizeColor(string $value, string $fallback): string
    {
        $value = trim($value);
        if ((bool)preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return strtolower($value);
        }

        return strtolower($fallback);
    }

    private function normalizeNumber(int $value, int $min, int $max, int $fallback): int
    {
        if ($value < $min || $value > $max) {
            return $fallback;
        }

        return $value;
    }

    private function normalizeStarterCards(string $json): array
    {
        $cards = json_decode($json, true);
        if (!is_array($cards)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($cards, 0, 3) as $card) {
            if (!is_array($card)) {
                continue;
            }

            $normalized[] = [
                'title' => mb_substr(trim(strip_tags((string)($card['title'] ?? ''))), 0, 160),
                'summary' => mb_substr(trim((string)($card['summary'] ?? '')), 0, 600),
                'badge' => mb_substr(trim(strip_tags((string)($card['badge'] ?? ''))), 0, 80),
                'meta_left' => mb_substr(trim(strip_tags((string)($card['meta_left'] ?? ''))), 0, 120),
                'meta_right' => mb_substr(trim(strip_tags((string)($card['meta_right'] ?? ''))), 0, 120),
                'image_url' => mb_substr(trim((string)($card['image_url'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim(strip_tags((string)($card['image_alt'] ?? ''))), 0, 160),
                'button_text' => mb_substr(trim(strip_tags((string)($card['button_text'] ?? ''))), 0, 80),
                'button_link' => mb_substr(trim((string)($card['button_link'] ?? '')), 0, 500),
                'url' => mb_substr(trim((string)($card['url'] ?? '#')), 0, 500),
            ];
        }

        return $normalized;
    }

    private function defaultCardSchema(): array
    {
        return [
            'columns' => 2,
            'min_cards' => 1,
            'max_cards' => 3,
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

    private function defaultTemplateColors(string $template): array
    {
        return match ($template) {
            'microsoft-365' => ['hero_start' => '#0f4c81', 'hero_end' => '#2563eb', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f8fbff'],
            'datenschutz' => ['hero_start' => '#0f766e', 'hero_end' => '#115e59', 'accent' => '#0f766e', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f0fdfa'],
            'compliance' => ['hero_start' => '#4c1d95', 'hero_end' => '#6d28d9', 'accent' => '#6d28d9', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#faf5ff'],
            'linux' => ['hero_start' => '#111827', 'hero_end' => '#b45309', 'accent' => '#b45309', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f3f4f6', 'section_background' => '#111827'],
            default => ['hero_start' => '#1f2937', 'hero_end' => '#0f172a', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#ffffff'],
        };
    }

    private function defaultStarterCards(string $template): array
    {
        return match ($template) {
            'microsoft-365' => [
                ['title' => 'Teams & Meetings', 'summary' => 'Platzhalter für Collaboration-, Telefonie- und Meeting-Szenarien.', 'badge' => 'Collaboration', 'meta_left' => 'Use Case', 'meta_right' => 'Priorität', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Mehr erfahren', 'button_link' => '#teams', 'url' => '#teams'],
                ['title' => 'SharePoint & Intranet', 'summary' => 'Platzhalter für Dokumentenmanagement, Wissensräume und Portale.', 'badge' => 'Knowledge', 'meta_left' => 'Owner', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Bereich öffnen', 'button_link' => '#sharepoint', 'url' => '#sharepoint'],
            ],
            'linux' => [
                ['title' => 'Platform Engineering', 'summary' => 'Platzhalter für Server, Container und Plattform-Bausteine.', 'badge' => 'Platform', 'meta_left' => 'Stack', 'meta_right' => 'Health', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Stack ansehen', 'button_link' => '#plattform', 'url' => '#plattform'],
                ['title' => 'Automation', 'summary' => 'Platzhalter für CI/CD, Provisioning und Shell-Automatisierung.', 'badge' => 'Automation', 'meta_left' => 'Pipeline', 'meta_right' => 'Owner', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Pipelines öffnen', 'button_link' => '#automation', 'url' => '#automation'],
            ],
            default => [
                ['title' => 'Bereich 1', 'summary' => 'Platzhalter für den ersten thematischen Kachelbereich.', 'badge' => 'Bereich', 'meta_left' => 'Label links', 'meta_right' => 'Label rechts', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Mehr erfahren', 'button_link' => '#bereich-1', 'url' => '#bereich-1'],
                ['title' => 'Bereich 2', 'summary' => 'Platzhalter für den zweiten thematischen Kachelbereich.', 'badge' => 'Bereich', 'meta_left' => 'Label links', 'meta_right' => 'Label rechts', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Mehr erfahren', 'button_link' => '#bereich-2', 'url' => '#bereich-2'],
            ],
        };
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
