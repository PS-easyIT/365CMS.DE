<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class HubTemplateProfileCatalog
{
    private const TEMPLATE_OPTIONS = [
        'general-it' => 'IT Themen Allgemein',
        'general-table' => 'Allg. Table',
        'microsoft-365' => 'Microsoft 365',
        'm365-table' => 'M365 Table',
        'powershell-table' => 'PowerShell Table',
        'datenschutz' => 'Datenschutz',
        'compliance' => 'Compliance',
        'datenschutz-compliance-table' => 'Datenschutz & Compliance Table',
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
        'powershell-table' => [
            'summary' => 'PowerShell-Table-Hub für Cmdlet-Matrizen, Automations-Runbooks, Skriptmodule und operative Shell-Referenzen im phinit-Look.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'contain', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'Admins, Automation & Operations',
                'owner' => 'Platform Automation',
                'update_cycle' => 'Wöchentlich',
                'focus' => 'Cmdlets, Runbooks & Module',
                'kpi' => 'Automation Health',
            ],
            'links' => [
                ['label' => 'Cmdlets', 'url' => '#cmdlets'],
                ['label' => 'Runbooks', 'url' => '#runbooks'],
                ['label' => 'Module', 'url' => '#module'],
                ['label' => 'Automation', 'url' => '#automation'],
            ],
            'sections' => [
                ['title' => 'Cmdlet- & Modul-Tabellen', 'text' => 'Platzhalter für Cmdlet-Übersichten, Modul-Vergleiche, Parameter-Tabellen und wiederkehrende Shell-Referenzen.', 'actionLabel' => 'Cmdlets öffnen', 'actionUrl' => '#cmdlets'],
                ['title' => 'Runbooks & Automation', 'text' => 'Platzhalter für Betriebsrunbooks, Scheduling, Aufgabenketten und PowerShell-basierte Automationspfade.', 'actionLabel' => 'Runbooks ansehen', 'actionUrl' => '#runbooks'],
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
        'datenschutz-compliance-table' => [
            'summary' => 'Table-Hub für Datenschutz & Compliance mit Nachweis-Matrizen, Kontrolltabellen, Fristen-Tracking und auditierbaren Übersichten im phinit-Stil.',
            'navigation' => ['toc_enabled' => false],
            'card_design' => ['layout' => 'standard', 'image_position' => 'top', 'image_fit' => 'cover', 'image_ratio' => 'wide', 'meta_layout' => 'split', 'card_radius' => 20],
            'meta' => [
                'audience' => 'DSB, Legal, Audit & Governance',
                'owner' => 'Privacy & Compliance Office',
                'update_cycle' => 'Monatlich',
                'focus' => 'Nachweise, Controls & Fristen',
                'kpi' => 'Evidence Coverage',
            ],
            'links' => [
                ['label' => 'Nachweise', 'url' => '#nachweise'],
                ['label' => 'Controls', 'url' => '#controls'],
                ['label' => 'Fristen', 'url' => '#fristen'],
                ['label' => 'Audits', 'url' => '#audits'],
            ],
            'sections' => [
                ['title' => 'Nachweis- & Kontrolltabellen', 'text' => 'Platzhalter für kombinierte DSGVO-/Compliance-Tabellen zu Nachweisen, Rollen, Maßnahmen und Kontrollfamilien.', 'actionLabel' => 'Kontrollen öffnen', 'actionUrl' => '#controls'],
                ['title' => 'Fristen, Audits & Maßnahmen', 'text' => 'Platzhalter für Fristen-Tracking, Auditpunkte, Reviews und priorisierte Maßnahmenlisten im Tabellenkontext.', 'actionLabel' => 'Audits ansehen', 'actionUrl' => '#audits'],
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

    public static function getTemplateOptions(): array
    {
        return self::TEMPLATE_OPTIONS;
    }

    public static function getTemplatePresets(): array
    {
        return self::TEMPLATE_PRESETS;
    }

    public static function buildRawDefaultProfiles(): array
    {
        $profiles = [];
        foreach (self::TEMPLATE_PRESETS as $key => $preset) {
            $profiles[$key] = [
                'label' => self::TEMPLATE_OPTIONS[$key] ?? $key,
                'base_template' => $key,
                'summary' => $preset['summary'] ?? '',
                'meta' => $preset['meta'] ?? [],
                'meta_labels' => self::defaultMetaLabels($key),
                'navigation' => $preset['navigation'] ?? ['toc_enabled' => false],
                'links' => $preset['links'] ?? [],
                'sections' => $preset['sections'] ?? [],
                'colors' => self::defaultTemplateColors($key),
                'card_schema' => self::defaultCardSchema($key),
                'card_design' => $preset['card_design'] ?? [],
                'starter_cards' => self::defaultStarterCards($key),
            ];
        }

        return $profiles;
    }

    public static function defaultMetaLabels(string $template): array
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
            'powershell-table' => [
                'audience' => 'Scope',
                'owner' => 'Automation Owner',
                'update_cycle' => 'Cadence',
                'focus' => 'Cmdlet Area',
                'kpi' => 'Runbook Health',
            ],
            'datenschutz' => [
                'audience' => 'Geltungsbereich',
                'owner' => 'Verantwortung',
                'update_cycle' => 'Prüfintervall',
                'focus' => 'Schutzziel',
                'kpi' => 'Nachweis',
            ],
            'datenschutz-compliance-table' => [
                'audience' => 'Scope',
                'owner' => 'Evidence Owner',
                'update_cycle' => 'Review',
                'focus' => 'Control Area',
                'kpi' => 'Evidence',
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

    public static function defaultCardSchema(string $template = 'general-it'): array
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
            'powershell-table' => [
                'title_label' => 'Runbook / Cmdlet-Thema',
                'summary_label' => 'Skript- / Modul-Kontext',
                'badge_label' => 'Shell Area',
                'meta_left_label' => 'Module',
                'meta_right_label' => 'Cadence',
                'image_label' => 'Script / Preview',
                'image_alt_label' => 'Script-Alt',
                'button_text_label' => 'Runbook-CTA',
                'button_link_label' => 'Runbook-Link',
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
            'datenschutz-compliance-table' => [
                'title_label' => 'Control / Nachweis',
                'summary_label' => 'Pflicht / Audit-Kontext',
                'badge_label' => 'Control Family',
                'meta_left_label' => 'Owner',
                'meta_right_label' => 'Review',
                'image_label' => 'Evidence / Preview',
                'image_alt_label' => 'Evidence-Alt',
                'button_text_label' => 'Evidence-CTA',
                'button_link_label' => 'Evidence-Link',
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

    public static function defaultTemplateColors(string $template): array
    {
        return match ($template) {
            'microsoft-365' => ['hero_start' => '#2d547a', 'hero_end' => '#1e3a5f', 'accent' => '#14b8a6', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#eef4fb', 'table_header_start' => '#2d547a', 'table_header_end' => '#1e3a5f'],
            'm365-table' => ['hero_start' => '#2d547a', 'hero_end' => '#1e3a5f', 'accent' => '#14b8a6', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#eef4fb', 'table_header_start' => '#2d547a', 'table_header_end' => '#1e3a5f'],
            'powershell-table' => ['hero_start' => '#0f2240', 'hero_end' => '#2d547a', 'accent' => '#14b8a6', 'surface' => '#111827', 'card_background' => '#162030', 'card_text' => '#f8fafc', 'section_background' => '#111827', 'table_header_start' => '#2d547a', 'table_header_end' => '#14b8a6'],
            'general-table' => ['hero_start' => '#1e3a5f', 'hero_end' => '#0f2240', 'accent' => '#0d9488', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#f1f5f9', 'table_header_start' => '#0f2240', 'table_header_end' => '#2d547a'],
            'datenschutz' => ['hero_start' => '#0f2240', 'hero_end' => '#0d9488', 'accent' => '#0d9488', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#f2fbfa', 'table_header_start' => '#0d9488', 'table_header_end' => '#1e3a5f'],
            'compliance' => ['hero_start' => '#1e3a5f', 'hero_end' => '#0f2240', 'accent' => '#e8a838', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#fbf8f1', 'table_header_start' => '#1e3a5f', 'table_header_end' => '#e8a838'],
            'datenschutz-compliance-table' => ['hero_start' => '#0f2240', 'hero_end' => '#1e3a5f', 'accent' => '#e8a838', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#f7f8f6', 'table_header_start' => '#0d9488', 'table_header_end' => '#1e3a5f'],
            'linux' => ['hero_start' => '#111827', 'hero_end' => '#0f2240', 'accent' => '#e8a838', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f1f5f9', 'section_background' => '#111827', 'table_header_start' => '#1e3a5f', 'table_header_end' => '#e8a838'],
            'linux-table' => ['hero_start' => '#111827', 'hero_end' => '#0f2240', 'accent' => '#e8a838', 'surface' => '#111827', 'card_background' => '#111827', 'card_text' => '#f1f5f9', 'section_background' => '#111827', 'table_header_start' => '#1e3a5f', 'table_header_end' => '#e8a838'],
            default => ['hero_start' => '#1e3a5f', 'hero_end' => '#0f2240', 'accent' => '#0d9488', 'surface' => '#ffffff', 'card_background' => '#ffffff', 'card_text' => '#1e293b', 'section_background' => '#f1f5f9', 'table_header_start' => '#1e3a5f', 'table_header_end' => '#0f2240'],
        };
    }

    public static function defaultStarterCards(string $template): array
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
            'powershell-table' => [
                ['title' => 'Cmdlet-Matrix', 'summary' => 'Platzhalter für Cmdlet-Kataloge, Parametervergleiche und PowerShell-Referenztabellen im dunklen phinit-Look.', 'badge' => 'Cmdlets', 'meta_left' => 'Module', 'meta_right' => 'Stand', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Cmdlets öffnen', 'button_link' => '#cmdlets', 'url' => '#cmdlets'],
                ['title' => 'Runbook-Tabelle', 'summary' => 'Platzhalter für Betriebsrunbooks, Schedules, Trigger und Zuständigkeiten in der zweiten Kartenhälfte.', 'badge' => 'Runbooks', 'meta_left' => 'Ops', 'meta_right' => 'Cadence', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Runbooks ansehen', 'button_link' => '#runbooks', 'url' => '#runbooks'],
                ['title' => 'Modul- & Script-Details', 'summary' => 'Platzhalter für Script-Bibliotheken, Modul-Notes und zusätzliche Automationsübersichten unterhalb der ersten Reihe.', 'badge' => 'Module', 'meta_left' => 'Library', 'meta_right' => 'Health', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Automation öffnen', 'button_link' => '#automation', 'url' => '#automation'],
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
            'datenschutz-compliance-table' => [
                ['title' => 'Evidence-Matrix', 'summary' => 'Platzhalter für Nachweis-, Audit- und Kontrolltabellen mit kombiniertem Datenschutz-/Compliance-Fokus.', 'badge' => 'Evidence', 'meta_left' => 'Owner', 'meta_right' => 'Review', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Evidence öffnen', 'button_link' => '#nachweise', 'url' => '#nachweise'],
                ['title' => 'Control- & Fristen-Tabelle', 'summary' => 'Platzhalter für Review-Zyklen, Fristen, Kontrollfamilien und Maßnahmenverfolgung in der zweiten Kartenhälfte.', 'badge' => 'Controls', 'meta_left' => 'Scope', 'meta_right' => 'Frist', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Controls ansehen', 'button_link' => '#controls', 'url' => '#controls'],
                ['title' => 'Audit-Details', 'summary' => 'Platzhalter für zusätzliche Audit- und Maßnahmenübersichten unterhalb der ersten Reihe.', 'badge' => 'Audit', 'meta_left' => 'Status', 'meta_right' => 'Coverage', 'image_url' => '', 'image_alt' => '', 'button_text' => 'Audits öffnen', 'button_link' => '#audits', 'url' => '#audits'],
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
