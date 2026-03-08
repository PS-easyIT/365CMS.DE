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
    ];

    private const TEMPLATE_PRESETS = [
        'general-it' => [
            'summary' => 'Breites IT-Hub für Strategie, Betrieb, Infrastruktur und Security.',
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
            'templateOptions' => self::TEMPLATE_OPTIONS,
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
            'templateOptions' => self::TEMPLATE_OPTIONS,
            'templatePresets' => self::TEMPLATE_PRESETS,
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

        $settings = [
            'content_mode' => 'hub',
            'hub_slug' => $slug,
            'hub_template' => array_key_exists((string)($post['hub_template'] ?? ''), self::TEMPLATE_OPTIONS) ? (string)$post['hub_template'] : 'general-it',
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
            'hub_links_json' => $this->normalizeJsonArray((string)($post['hub_links_json'] ?? '[]'), 'link'),
            'hub_sections_json' => $this->normalizeJsonArray((string)($post['hub_sections_json'] ?? '[]'), 'section'),
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
