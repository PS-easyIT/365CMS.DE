<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

final class HubTemplateProfileManager
{
    private const TEMPLATE_SETTING_KEY = 'hub_site_templates';

    private const TEMPLATE_OPTIONS = [
        'general-it' => 'IT Themen Allgemein',
        'general-table' => 'Allg. Table',
        'microsoft-365' => 'Microsoft 365',
        'm365-table' => 'M365 Table',
        'datenschutz' => 'Datenschutz',
        'compliance' => 'Compliance',
        'linux' => 'Linux',
        'linux-table' => 'Linux Table',
    ];

    private const TEMPLATE_PRESETS = [
        'general-it' => [
            'summary' => 'Breites IT-Hub für Strategie, Betrieb, Infrastruktur und Security mit neutraler, vielseitiger Informationsarchitektur.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'IT-Leitung & Fachbereiche',
                'owner' => 'IT-Operations',
                'update_cycle' => 'Monatlich',
                'focus' => 'Architektur, Betrieb & Security',
                'kpi' => 'Servicequalität',
            ],
            'links' => [
                ['label' => 'Strategie', 'url' => '#strategie'],
                ['label' => 'Plattform', 'url' => '#plattform'],
                ['label' => 'Security', 'url' => '#security'],
                ['label' => 'Services', 'url' => '#services'],
            ],
            'sections' => [
                ['title' => 'Roadmap & Architektur', 'text' => 'Platzhalter für Zielbilder, Modernisierung, Plattformentscheidungen und operative Prioritäten.', 'actionLabel' => 'Roadmap ansehen', 'actionUrl' => '#roadmap'],
                ['title' => 'Services & Betriebsmodelle', 'text' => 'Platzhalter für Managed Services, Service-Catalog, SLA-Modelle und Zuständigkeiten.', 'actionLabel' => 'Services öffnen', 'actionUrl' => '#services'],
            ],
        ],
        'general-table' => [
            'summary' => 'Allgemeines Table-Hub für neutrale Übersichten, Matrix-Tabellen und strukturierte Fachinformationen im 2-Spalten-Kartenlayout.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Fachbereiche & IT',
                'owner' => 'Hub Redaktion',
                'update_cycle' => 'Nach Bedarf',
                'focus' => 'Übersichten, Tabellen & Referenzen',
                'kpi' => 'Aktualität',
            ],
            'links' => [
                ['label' => 'Übersicht', 'url' => '#uebersicht'],
                ['label' => 'Tabellen', 'url' => '#tabellen'],
                ['label' => 'Vergleiche', 'url' => '#vergleiche'],
                ['label' => 'Details', 'url' => '#details'],
            ],
            'sections' => [
                ['title' => 'Tabellen-Übersicht', 'text' => 'Platzhalter für allgemeine Referenztabellen, Übersichten und strukturierte Vergleichsansichten.', 'actionLabel' => 'Tabellen öffnen', 'actionUrl' => '#tabellen'],
                ['title' => 'Fachliche Details', 'text' => 'Platzhalter für erläuternde Texte, Hinweise und weiterführende Informationen unterhalb der Tabellen.', 'actionLabel' => 'Details ansehen', 'actionUrl' => '#details'],
            ],
        ],
        'microsoft-365' => [
            'summary' => 'Modern-Work-Hub mit Fokus auf Workloads, Adoption Journeys, Governance und messbaren Workspace-Nutzen.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'feature', 'image_position' => 'left', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Workspace Owner & Fachbereiche',
                'owner' => 'M365 Enablement',
                'update_cycle' => '14-tägig',
                'focus' => 'Adoption, Governance & Automation',
                'kpi' => 'Adoption Rate',
            ],
            'links' => [
                ['label' => 'Teams', 'url' => '#teams'],
                ['label' => 'SharePoint', 'url' => '#sharepoint'],
                ['label' => 'Copilot', 'url' => '#copilot'],
                ['label' => 'Governance', 'url' => '#governance'],
            ],
            'sections' => [
                ['title' => 'Workload Journey', 'text' => 'Platzhalter für Teams, Exchange, SharePoint, Viva und Copilot entlang echter Nutzerpfade.', 'actionLabel' => 'Journey öffnen', 'actionUrl' => '#workspace'],
                ['title' => 'Adoption & Guardrails', 'text' => 'Platzhalter für Enablement, Rollout-Stände, Policies und Governance-Standards.', 'actionLabel' => 'Guardrails prüfen', 'actionUrl' => '#governance'],
            ],
        ],
        'm365-table' => [
            'summary' => 'Microsoft-365-Table-Hub für Lizenzmatrizen, Feature-Vergleiche, Rollout-Tabellen und strukturierte Use-Case-Übersichten.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Workspace Owner & Fachbereiche',
                'owner' => 'M365 Enablement',
                'update_cycle' => '14-tägig',
                'focus' => 'Lizenzierung, Features & Rollout',
                'kpi' => 'Adoption Rate',
            ],
            'links' => [
                ['label' => 'Lizenzen', 'url' => '#lizenzen'],
                ['label' => 'Features', 'url' => '#features'],
                ['label' => 'Use Cases', 'url' => '#usecases'],
                ['label' => 'Rollout', 'url' => '#rollout'],
            ],
            'sections' => [
                ['title' => 'Lizenz- & Feature-Tabellen', 'text' => 'Platzhalter für SKU-Vergleiche, Funktionsmatrizen und Workload-Zuordnungen im Tabellenformat.', 'actionLabel' => 'Matrizen öffnen', 'actionUrl' => '#lizenzen'],
                ['title' => 'Rollout & Use Cases', 'text' => 'Platzhalter für strukturierte Rollout-Tabellen, Verantwortlichkeiten und Business-Nutzen je Szenario.', 'actionLabel' => 'Use Cases ansehen', 'actionUrl' => '#usecases'],
            ],
        ],
        'datenschutz' => [
            'summary' => 'Strukturiertes Datenschutz-Hub für Nachweise, Prüfpfade, Verantwortlichkeiten und belastbare Rechtsgrundlagen.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked', 'card_radius' => 20],
            'meta' => [
                'audience' => 'DSB, Legal & Fachbereiche',
                'owner' => 'Datenschutz-Office',
                'update_cycle' => 'Quartalsweise',
                'focus' => 'Nachweise, Prozesse & Fristen',
                'kpi' => 'Prüfstatus',
            ],
            'links' => [
                ['label' => 'DSGVO', 'url' => '#dsgvo'],
                ['label' => 'VVT', 'url' => '#vvt'],
                ['label' => 'TOMs', 'url' => '#toms'],
                ['label' => 'Betroffenenrechte', 'url' => '#betroffenenrechte'],
            ],
            'sections' => [
                ['title' => 'Nachweise & Prüfpfade', 'text' => 'Platzhalter für TOMs, AV-Verträge, Löschkonzepte und eine sauber nachvollziehbare Dokumentation.', 'actionLabel' => 'Nachweise ansehen', 'actionUrl' => '#nachweise'],
                ['title' => 'Fristen & Umsetzungsbedarf', 'text' => 'Platzhalter für Betroffenenrechte, Risiken, Audits und priorisierte Maßnahmenpakete.', 'actionLabel' => 'Maßnahmen öffnen', 'actionUrl' => '#umsetzung'],
            ],
        ],
        'compliance' => [
            'summary' => 'Governance-/Compliance-Hub für Policies, Audits, Rollenkonzepte und belastbare Kontrolllandschaften.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'feature', 'image_position' => 'right', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
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
            'summary' => 'Technisches Linux-Hub für Platform Engineering, Automatisierung, Observability und reproduzierbares Hardening.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'compact', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'square', 'meta_layout' => 'stacked', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Admins, SRE & Platform Team',
                'owner' => 'Platform Engineering',
                'update_cycle' => 'Wöchentlich',
                'focus' => 'Automation, Hardening & Runtime',
                'kpi' => 'Runtime-Health',
            ],
            'links' => [
                ['label' => 'Server', 'url' => '#server'],
                ['label' => 'Container', 'url' => '#container'],
                ['label' => 'Pipelines', 'url' => '#pipelines'],
                ['label' => 'Hardening', 'url' => '#hardening'],
            ],
            'sections' => [
                ['title' => 'Platform Runtime', 'text' => 'Platzhalter für Linux-Betrieb, Container, Kubernetes, Compute-Stacks und Baseline-Services.', 'actionLabel' => 'Runtime öffnen', 'actionUrl' => '#plattform'],
                ['title' => 'Shell, Pipelines & Hardening', 'text' => 'Platzhalter für Runbooks, CI/CD, Observability und Security-Baselines.', 'actionLabel' => 'Runbooks ansehen', 'actionUrl' => '#hardening'],
            ],
        ],
        'linux-table' => [
            'summary' => 'Linux-Table-Hub für Paketvergleiche, Server-Matrizen, Runbook-Tabellen und technische Betriebsübersichten im 2er-Kachelraster.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Admins, SRE & Platform Team',
                'owner' => 'Platform Engineering',
                'update_cycle' => 'Wöchentlich',
                'focus' => 'Matrizen, Runbooks & Betriebsdaten',
                'kpi' => 'Runtime-Health',
            ],
            'links' => [
                ['label' => 'Server', 'url' => '#server'],
                ['label' => 'Pakete', 'url' => '#pakete'],
                ['label' => 'Runbooks', 'url' => '#runbooks'],
                ['label' => 'Hardening', 'url' => '#hardening'],
            ],
            'sections' => [
                ['title' => 'System- & Paket-Tabellen', 'text' => 'Platzhalter für Paketstände, Systemvarianten, Baselines und technische Vergleichstabellen.', 'actionLabel' => 'Tabellen öffnen', 'actionUrl' => '#pakete'],
                ['title' => 'Runbooks & Betriebsansichten', 'text' => 'Platzhalter für Wartungsfenster, Betriebsdaten und strukturierte Linux-Runbooks mit Tabellenbezug.', 'actionLabel' => 'Runbooks ansehen', 'actionUrl' => '#runbooks'],
            ],
        ],
    ];

    private Database $db;
    private string $prefix;
    private ?array $templateProfilesCache = null;

    public function __construct(Database $db, string $prefix)
    {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    public function getChoices(): array
    {
        $choices = [];
        foreach ($this->getTemplateProfiles() as $key => $profile) {
            $choices[$key] = (string)($profile['label'] ?? $key);
        }

        return $choices;
    }

    public function getProfiles(): array
    {
        return $this->getTemplateProfiles();
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
                'navigation' => [
                    'toc_enabled' => false,
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
                    'card_radius' => 20,
                ],
                'starter_cards' => [],
            ];
        }

        return [
            'template' => $template,
            'isNew' => $normalizedKey === '' || !isset($profiles[$normalizedKey]),
            'baseTemplateOptions' => self::TEMPLATE_OPTIONS,
            'baseTemplateDefaults' => $this->buildDefaultTemplateProfiles(),
        ];
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

        $previousProfile = isset($profiles[$key]) && is_array($profiles[$key]) ? $profiles[$key] : null;

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
            'navigation' => [
                'toc_enabled' => !empty($post['template_toc_enabled']),
            ],
            'links' => \CMS\Json::decodeArray($this->normalizeJsonArray((string)($post['template_links_json'] ?? '[]'), 'link'), []),
            'sections' => \CMS\Json::decodeArray($this->normalizeJsonArray((string)($post['template_sections_json'] ?? '[]'), 'section'), []),
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
                'card_radius' => $this->normalizeNumber((int)($post['template_card_radius'] ?? 20), 0, 48, 20),
            ],
            'starter_cards' => $this->normalizeStarterCards((string)($post['template_starter_cards_json'] ?? '[]')),
        ];

        $profiles[$key] = $this->normalizeTemplateProfile($key, $profiles[$key], $previousProfile);

        $this->saveTemplateProfiles($profiles);
        $this->syncInheritedHubSitesWithTemplate($key, $previousProfile, $profiles[$key]);

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

    private function normalizeJsonArray(string $json, string $mode): string
    {
        $items = \CMS\Json::decodeArray($json, []);
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
            $decoded = \CMS\Json::decodeArray($row->option_value ?? null, []);
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

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}';
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
                'meta_labels' => $this->defaultMetaLabels($key),
                'navigation' => $preset['navigation'] ?? ['toc_enabled' => false],
                'links' => $preset['links'] ?? [],
                'sections' => $preset['sections'] ?? [],
                'colors' => $this->defaultTemplateColors($key),
                'card_schema' => $this->defaultCardSchema($key),
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
            'navigation' => ['toc_enabled' => false],
            'links' => [],
            'sections' => [],
            'colors' => $this->defaultTemplateColors('general-it'),
            'card_schema' => $this->defaultCardSchema(),
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
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
            'navigation' => [
                'toc_enabled' => !empty($profile['navigation']['toc_enabled'] ?? $fallback['navigation']['toc_enabled'] ?? false),
            ],
            'links' => \CMS\Json::decodeArray($this->normalizeJsonArray(json_encode($profile['links'] ?? $fallback['links'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'link'), []),
            'sections' => \CMS\Json::decodeArray($this->normalizeJsonArray(json_encode($profile['sections'] ?? $fallback['sections'] ?? [], JSON_UNESCAPED_UNICODE) ?: '[]', 'section'), []),
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
                'card_radius' => $this->normalizeNumber((int)($profile['card_design']['card_radius'] ?? $fallback['card_design']['card_radius'] ?? 20), 0, 48, 20),
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
        $cards = \CMS\Json::decodeArray($json, []);
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

    private function syncInheritedHubSitesWithTemplate(string $templateKey, ?array $previousProfile, array $currentProfile): void
    {
        if ($previousProfile === null) {
            return;
        }

        $sites = $this->db->get_results(
            "SELECT id, rows_json, settings_json
             FROM {$this->prefix}site_tables
             WHERE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.content_mode')), 'table') = 'hub'
               AND JSON_UNQUOTE(JSON_EXTRACT(settings_json, '$.hub_template')) = ?",
            [$templateKey]
        ) ?: [];

        if ($sites === []) {
            return;
        }

        $previousLinks = $this->normalizeComparableLinks($previousProfile['links'] ?? []);
        $previousStarterCards = $this->normalizeComparableStarterCards($previousProfile['starter_cards'] ?? []);
        $currentStarterCards = $this->buildSiteRowsFromStarterCards($currentProfile['starter_cards'] ?? []);

        foreach ($sites as $site) {
            $siteId = (int) ($site->id ?? 0);
            if ($siteId <= 0) {
                continue;
            }

            $settings = \CMS\Json::decodeArray($site->settings_json ?? null, []);
            $rows = \CMS\Json::decodeArray($site->rows_json ?? null, []);

            if (!is_array($settings)) {
                $settings = [];
            }
            if (!is_array($rows)) {
                $rows = [];
            }

            $settingsChanged = false;
            $rowsChanged = false;

            $storedLinks = $this->normalizeComparableLinks(\CMS\Json::decodeArray((string) ($settings['hub_links_json'] ?? '[]'), []));
            if ($storedLinks !== [] && $storedLinks === $previousLinks) {
                $settings['hub_links_json'] = '[]';
                $settingsChanged = true;
            }

            $storedRowsComparable = $this->normalizeComparableSiteRows($rows);
            if ($storedRowsComparable !== [] && $storedRowsComparable === $previousStarterCards) {
                $rows = $currentStarterCards;
                $rowsChanged = true;
            }

            if (!$settingsChanged && !$rowsChanged) {
                continue;
            }

            $this->db->update('site_tables', [
                'settings_json' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                'rows_json' => json_encode($rows, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s'),
            ], [
                'id' => $siteId,
            ]);
        }
    }

    private function normalizeComparableLinks(mixed $links): array
    {
        if (!is_array($links)) {
            return [];
        }

        $normalized = [];
        foreach ($links as $link) {
            if (!is_array($link)) {
                continue;
            }

            $label = mb_substr(trim(strip_tags((string) ($link['label'] ?? ''))), 0, 80);
            $url = mb_substr(trim((string) ($link['url'] ?? '#')), 0, 240);
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'url' => $url !== '' ? $url : '#',
            ];
        }

        return array_slice($normalized, 0, 6);
    }

    private function normalizeComparableStarterCards(mixed $cards): array
    {
        if (!is_array($cards)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($cards, 0, 3) as $card) {
            if (!is_array($card)) {
                continue;
            }

            $normalized[] = [
                'title' => mb_substr(trim(strip_tags((string) ($card['title'] ?? ''))), 0, 160),
                'summary' => mb_substr(trim((string) ($card['summary'] ?? '')), 0, 600),
                'badge' => mb_substr(trim(strip_tags((string) ($card['badge'] ?? ''))), 0, 80),
                'meta_left' => mb_substr(trim(strip_tags((string) ($card['meta_left'] ?? ''))), 0, 120),
                'meta_right' => mb_substr(trim(strip_tags((string) ($card['meta_right'] ?? ''))), 0, 120),
                'image_url' => mb_substr(trim((string) ($card['image_url'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim(strip_tags((string) ($card['image_alt'] ?? ''))), 0, 160),
                'button_text' => mb_substr(trim(strip_tags((string) ($card['button_text'] ?? ''))), 0, 80),
                'button_link' => mb_substr(trim((string) ($card['button_link'] ?? '')), 0, 500),
                'url' => mb_substr(trim((string) ($card['url'] ?? '#')), 0, 500),
            ];
        }

        return $normalized;
    }

    private function normalizeComparableSiteRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $normalized = [];
        foreach (array_slice($rows, 0, 3) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = [
                'title' => mb_substr(trim(strip_tags((string) ($row['title'] ?? ''))), 0, 160),
                'summary' => mb_substr(trim((string) ($row['summary'] ?? '')), 0, 600),
                'badge' => mb_substr(trim(strip_tags((string) ($row['badge'] ?? ''))), 0, 80),
                'meta_left' => mb_substr(trim(strip_tags((string) ($row['meta_left'] ?? ''))), 0, 120),
                'meta_right' => mb_substr(trim(strip_tags((string) ($row['meta_right'] ?? ''))), 0, 120),
                'image_url' => mb_substr(trim((string) ($row['image_url'] ?? '')), 0, 500),
                'image_alt' => mb_substr(trim(strip_tags((string) ($row['image_alt'] ?? ''))), 0, 160),
                'button_text' => mb_substr(trim(strip_tags((string) ($row['button_text'] ?? ''))), 0, 80),
                'button_link' => mb_substr(trim((string) ($row['button_link'] ?? '')), 0, 500),
                'url' => mb_substr(trim((string) ($row['url'] ?? '#')), 0, 500),
            ];
        }

        return $normalized;
    }

    private function buildSiteRowsFromStarterCards(mixed $cards): array
    {
        $starterCards = $this->normalizeComparableStarterCards($cards);
        $rows = [];

        foreach ($starterCards as $card) {
            $rows[] = [
                'title' => (string) ($card['title'] ?? ''),
                'title_en' => '',
                'url' => (string) ($card['url'] ?? '#'),
                'summary' => (string) ($card['summary'] ?? ''),
                'summary_en' => '',
                'badge' => (string) ($card['badge'] ?? ''),
                'badge_en' => '',
                'meta' => '',
                'meta_en' => '',
                'meta_left' => (string) ($card['meta_left'] ?? ''),
                'meta_left_en' => '',
                'meta_right' => (string) ($card['meta_right'] ?? ''),
                'meta_right_en' => '',
                'image_url' => (string) ($card['image_url'] ?? ''),
                'image_alt' => (string) ($card['image_alt'] ?? ''),
                'image_alt_en' => '',
                'button_text' => (string) ($card['button_text'] ?? ''),
                'button_text_en' => '',
                'button_link' => (string) ($card['button_link'] ?? ''),
            ];
        }

        return $rows;
    }

    private function defaultMetaLabels(string $template): array
    {
        return match ($template) {
            'microsoft-365' => [
                'audience' => 'Use Case',
                'owner' => 'Owner',
                'update_cycle' => 'Rollout',
                'focus' => 'Scope',
                'kpi' => 'Adoption',
            ],
            'm365-table' => [
                'audience' => 'Use Case',
                'owner' => 'Owner',
                'update_cycle' => 'Update',
                'focus' => 'Matrix',
                'kpi' => 'Coverage',
            ],
            'datenschutz' => [
                'audience' => 'Geltungsbereich',
                'owner' => 'Verantwortung',
                'update_cycle' => 'Prüfintervall',
                'focus' => 'Schutzziel',
                'kpi' => 'Nachweis',
            ],
            'linux' => [
                'audience' => 'Stack',
                'owner' => 'Ops Owner',
                'update_cycle' => 'Cadence',
                'focus' => 'Layer',
                'kpi' => 'Signal',
            ],
            'linux-table' => [
                'audience' => 'Stack',
                'owner' => 'Ops Owner',
                'update_cycle' => 'Update',
                'focus' => 'Matrix',
                'kpi' => 'Health',
            ],
            'compliance' => [
                'audience' => 'Scope',
                'owner' => 'Control Owner',
                'update_cycle' => 'Review',
                'focus' => 'Policy Area',
                'kpi' => 'Readiness',
            ],
            default => [
                'audience' => 'Zielgruppe',
                'owner' => 'Verantwortlich',
                'update_cycle' => 'Update-Zyklus',
                'focus' => 'Fokus',
                'kpi' => 'KPI',
            ],
        };
    }

    private function defaultCardSchema(string $template = 'general-it'): array
    {
        $labels = match ($template) {
            'microsoft-365' => [
                'title_label' => 'Use Case / Thema',
                'summary_label' => 'Business-Nutzen',
                'badge_label' => 'Workload',
                'meta_left_label' => 'Owner',
                'meta_right_label' => 'Rollout',
                'image_label' => 'Visual / Screenshot',
                'image_alt_label' => 'Visual-Alt',
                'button_text_label' => 'CTA-Text',
                'button_link_label' => 'CTA-Link',
            ],
            'm365-table' => [
                'title_label' => 'Tabelle / Thema',
                'summary_label' => 'Einordnung / Beschreibung',
                'badge_label' => 'Workload',
                'meta_left_label' => 'Bereich',
                'meta_right_label' => 'Stand',
                'image_label' => 'Vorschaubild',
                'image_alt_label' => 'Bild-Alt',
                'button_text_label' => 'CTA-Text',
                'button_link_label' => 'CTA-Link',
            ],
            'general-table' => [
                'title_label' => 'Tabelle / Thema',
                'summary_label' => 'Kurzbeschreibung',
                'badge_label' => 'Kategorie',
                'meta_left_label' => 'Bereich',
                'meta_right_label' => 'Stand',
                'image_label' => 'Vorschaubild',
                'image_alt_label' => 'Bild-Alt',
                'button_text_label' => 'Button-Text',
                'button_link_label' => 'Button-Link',
            ],
            'datenschutz' => [
                'title_label' => 'Nachweis / Thema',
                'summary_label' => 'Pflicht / Kontext',
                'badge_label' => 'Rechtsbasis',
                'meta_left_label' => 'Status',
                'meta_right_label' => 'Frist',
                'image_label' => 'Dokument / Grafik',
                'image_alt_label' => 'Dokument-Alt',
                'button_text_label' => 'Aktion',
                'button_link_label' => 'Aktion-Link',
            ],
            'linux' => [
                'title_label' => 'Service / Stack',
                'summary_label' => 'Runbook / Kontext',
                'badge_label' => 'Layer',
                'meta_left_label' => 'Scope',
                'meta_right_label' => 'State',
                'image_label' => 'Diagramm / Icon',
                'image_alt_label' => 'Diagramm-Alt',
                'button_text_label' => 'Command / CTA',
                'button_link_label' => 'Runbook-Link',
            ],
            'linux-table' => [
                'title_label' => 'Tabelle / Stack',
                'summary_label' => 'Runbook / Kontext',
                'badge_label' => 'Layer',
                'meta_left_label' => 'Bereich',
                'meta_right_label' => 'Stand',
                'image_label' => 'Diagramm / Preview',
                'image_alt_label' => 'Diagramm-Alt',
                'button_text_label' => 'Runbook-CTA',
                'button_link_label' => 'Runbook-Link',
            ],
            'compliance' => [
                'title_label' => 'Control / Thema',
                'summary_label' => 'Risiko / Kontext',
                'badge_label' => 'Control Family',
                'meta_left_label' => 'Owner',
                'meta_right_label' => 'Audit',
                'image_label' => 'Control-Visual',
                'image_alt_label' => 'Control-Alt',
                'button_text_label' => 'Evidence-CTA',
                'button_link_label' => 'Evidence-Link',
            ],
            default => [
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
        };

        return ['columns' => 2, 'min_cards' => 1, 'max_cards' => 3] + $labels;
    }

    private function defaultTemplateColors(string $template): array
    {
        return match ($template) {
            'microsoft-365' => ['hero_start' => '#0f4c81', 'hero_end' => '#2563eb', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f8fbff'],
            'm365-table' => ['hero_start' => '#0f4c81', 'hero_end' => '#2563eb', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f8fbff'],
            'general-table' => ['hero_start' => '#1f2937', 'hero_end' => '#0f172a', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#ffffff'],
            'datenschutz' => ['hero_start' => '#0f766e', 'hero_end' => '#115e59', 'accent' => '#0f766e', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#f0fdfa'],
            'compliance' => ['hero_start' => '#4c1d95', 'hero_end' => '#6d28d9', 'accent' => '#6d28d9', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#faf5ff'],
            'linux' => ['hero_start' => '#111827', 'hero_end' => '#b45309', 'accent' => '#b45309', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f3f4f6', 'section_background' => '#111827'],
            'linux-table' => ['hero_start' => '#111827', 'hero_end' => '#b45309', 'accent' => '#b45309', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f3f4f6', 'section_background' => '#111827'],
            default => ['hero_start' => '#1f2937', 'hero_end' => '#0f172a', 'accent' => '#2563eb', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#0f172a', 'section_background' => '#ffffff'],
        };
    }

    private function defaultStarterCards(string $template): array
    {
        return match ($template) {
            'microsoft-365' => [
                ['title' => 'Teams & Meetings', 'summary' => 'Platzhalter für Collaboration-, Meeting-, Calling- und Workspace-Szenarien mit klarem Business-Nutzen.', 'badge' => 'Teams', 'meta_left' => 'Enablement', 'meta_right' => 'Rollout', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Use Case öffnen', 'button_link' => '#teams', 'url' => '#teams'],
                ['title' => 'SharePoint & Intranet', 'summary' => 'Platzhalter für Wissensräume, Dokumentenmanagement, Intranet-Strecken und Content Governance.', 'badge' => 'SharePoint', 'meta_left' => 'Owner', 'meta_right' => 'Lifecycle', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Workspace öffnen', 'button_link' => '#sharepoint', 'url' => '#sharepoint'],
                ['title' => 'Copilot & Automation', 'summary' => 'Platzhalter für Prompts, Agenten, Power Platform und Automation entlang echter Workflows.', 'badge' => 'Copilot', 'meta_left' => 'Scenario', 'meta_right' => 'Value', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Automation ansehen', 'button_link' => '#copilot', 'url' => '#copilot'],
            ],
            'm365-table' => [
                ['title' => 'Lizenzmatrix', 'summary' => 'Platzhalter für Microsoft-365-Lizenzvergleiche, Feature-Matrizen oder eingebettete Tabellen per Shortcode.', 'badge' => 'Lizenz', 'meta_left' => 'M365', 'meta_right' => 'Stand', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Matrix öffnen', 'button_link' => '#lizenzen', 'url' => '#lizenzen'],
                ['title' => 'Feature-Vergleich', 'summary' => 'Platzhalter für Workload-, Copilot-, Teams- oder SharePoint-Vergleiche im strukturierten Tabellenformat.', 'badge' => 'Features', 'meta_left' => 'Vergleich', 'meta_right' => 'Update', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Vergleich ansehen', 'button_link' => '#features', 'url' => '#features'],
                ['title' => 'Rollout-Tabelle', 'summary' => 'Platzhalter für Rollout-Stände, Zuständigkeiten und Maßnahmenlisten unterhalb der ersten Kartenreihe.', 'badge' => 'Rollout', 'meta_left' => 'Plan', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Rollout öffnen', 'button_link' => '#rollout', 'url' => '#rollout'],
            ],
            'general-table' => [
                ['title' => 'Referenztabelle', 'summary' => 'Platzhalter für neutrale Übersichten, Matrix-Tabellen und allgemeine Strukturinformationen.', 'badge' => 'Übersicht', 'meta_left' => 'Bereich', 'meta_right' => 'Stand', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Tabelle öffnen', 'button_link' => '#tabellen', 'url' => '#tabellen'],
                ['title' => 'Vergleichsansicht', 'summary' => 'Platzhalter für Feature-, Leistungs- oder Bereichsvergleiche in einer zweiten 50%-Karte.', 'badge' => 'Vergleich', 'meta_left' => 'Scope', 'meta_right' => 'Update', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Vergleich öffnen', 'button_link' => '#vergleiche', 'url' => '#vergleiche'],
                ['title' => 'Detailtabelle', 'summary' => 'Platzhalter für zusätzliche Karten unterhalb der ersten Reihe mit weiteren Tabellen oder Detailansichten.', 'badge' => 'Details', 'meta_left' => 'Info', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Details ansehen', 'button_link' => '#details', 'url' => '#details'],
            ],
            'datenschutz' => [
                ['title' => 'Verzeichnis & Rechtsgrundlagen', 'summary' => 'Platzhalter für Verarbeitungstätigkeiten, Zwecke, Rechtsgrundlagen und Dokumentationsstand.', 'badge' => 'DSGVO', 'meta_left' => 'Status', 'meta_right' => 'Prüfung', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Nachweis öffnen', 'button_link' => '#vvt', 'url' => '#vvt'],
                ['title' => 'TOMs & Verträge', 'summary' => 'Platzhalter für technische Maßnahmen, AV-Verträge und Lieferanten-Nachweise.', 'badge' => 'TOMs', 'meta_left' => 'Scope', 'meta_right' => 'Frist', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Maßnahmen prüfen', 'button_link' => '#toms', 'url' => '#toms'],
                ['title' => 'Betroffenenrechte', 'summary' => 'Platzhalter für Auskunft, Löschung, Fristenmanagement und Eskalationspfade.', 'badge' => 'Rights', 'meta_left' => 'Case', 'meta_right' => 'SLA', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Fälle ansehen', 'button_link' => '#betroffenenrechte', 'url' => '#betroffenenrechte'],
            ],
            'linux' => [
                ['title' => 'Platform Runtime', 'summary' => 'Platzhalter für Hosts, Container, Kubernetes und stabile Runtime-Bausteine.', 'badge' => 'Runtime', 'meta_left' => 'Layer', 'meta_right' => 'Health', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Runtime ansehen', 'button_link' => '#plattform', 'url' => '#plattform'],
                ['title' => 'Automation & Pipelines', 'summary' => 'Platzhalter für Provisioning, CI/CD, Shell-Automatisierung und wiederholbare Deployments.', 'badge' => 'Pipeline', 'meta_left' => 'Scope', 'meta_right' => 'Owner', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Pipelines öffnen', 'button_link' => '#pipelines', 'url' => '#pipelines'],
                ['title' => 'Hardening & Observability', 'summary' => 'Platzhalter für Baselines, Monitoring, Logging und operative Security-Kontrollen.', 'badge' => 'Hardening', 'meta_left' => 'Signal', 'meta_right' => 'Baseline', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Runbook öffnen', 'button_link' => '#hardening', 'url' => '#hardening'],
            ],
            'linux-table' => [
                ['title' => 'Server-Matrix', 'summary' => 'Platzhalter für Serverstände, Rollenverteilungen und Betriebsübersichten im Tabellenformat.', 'badge' => 'Server', 'meta_left' => 'Stack', 'meta_right' => 'Stand', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Matrix öffnen', 'button_link' => '#server', 'url' => '#server'],
                ['title' => 'Paket- & Service-Tabelle', 'summary' => 'Platzhalter für Paketstände, Service-Mappings oder technische Vergleichstabellen in der zweiten 50%-Karte.', 'badge' => 'Pakete', 'meta_left' => 'Scope', 'meta_right' => 'Update', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Tabelle ansehen', 'button_link' => '#pakete', 'url' => '#pakete'],
                ['title' => 'Runbook-Details', 'summary' => 'Platzhalter für weitere Karten unterhalb der ersten Reihe mit Runbooks, Betriebsfenstern oder Hardening-Daten.', 'badge' => 'Runbook', 'meta_left' => 'Ops', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Runbook öffnen', 'button_link' => '#runbooks', 'url' => '#runbooks'],
            ],
            'compliance' => [
                ['title' => 'Policies & Controls', 'summary' => 'Platzhalter für Richtlinien, Kontrollfamilien und zentrale Governance-Vorgaben.', 'badge' => 'Policy', 'meta_left' => 'Owner', 'meta_right' => 'Review', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Control öffnen', 'button_link' => '#policies', 'url' => '#policies'],
                ['title' => 'Audit Readiness', 'summary' => 'Platzhalter für Audits, Evidence Packs, Maßnahmenlisten und Reifegrade.', 'badge' => 'Audit', 'meta_left' => 'Evidence', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Audit ansehen', 'button_link' => '#audits', 'url' => '#audits'],
                ['title' => 'Roles & Responsibilities', 'summary' => 'Platzhalter für Rollenmodelle, Kontrollverantwortung und Freigabepfade.', 'badge' => 'Governance', 'meta_left' => 'Role', 'meta_right' => 'Scope', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Rollen öffnen', 'button_link' => '#rollen', 'url' => '#rollen'],
            ],
            default => [
                ['title' => 'Architektur & Strategie', 'summary' => 'Platzhalter für Zielbilder, Prinzipien und priorisierte Initiativen im Themenfeld.', 'badge' => 'Strategie', 'meta_left' => 'Owner', 'meta_right' => 'Priorität', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Mehr erfahren', 'button_link' => '#strategie', 'url' => '#strategie'],
                ['title' => 'Services & Betrieb', 'summary' => 'Platzhalter für Services, Zuständigkeiten, Support-Level und operative KPIs.', 'badge' => 'Betrieb', 'meta_left' => 'Scope', 'meta_right' => 'Status', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Service öffnen', 'button_link' => '#services', 'url' => '#services'],
                ['title' => 'Security & Standards', 'summary' => 'Platzhalter für Schutzmaßnahmen, Richtlinien, Baselines und technische Leitplanken.', 'badge' => 'Security', 'meta_left' => 'Control', 'meta_right' => 'Review', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Standards prüfen', 'button_link' => '#security', 'url' => '#security'],
            ],
        };
    }
}
