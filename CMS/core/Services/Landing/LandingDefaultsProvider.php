<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Version;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingDefaultsProvider
{
    /**
     * @return array<string, mixed>
     */
    public function getDefaultHeader(): array
    {
        return [
            'id' => null,
            'title' => '365CMS – modernes CMS für Inhalte, Portale und Mitgliederbereiche',
            'subtitle' => 'Landing Pages, Redaktion, Plugins, Member Area und Design Editor in einem System',
            'badge_text' => defined('CMS_VERSION') ? CMS_VERSION : Version::CURRENT,
            'description' => '365CMS vereint Content-Management, Design-Anpassung, Mitgliederfunktionen, System-Mails und modulare Business-Features in einer flexiblen Plattform für professionelle Websites und Portale.',
            'bg_image' => '',
            'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
            'github_text' => '💻 GitHub Projekt',
            'gitlab_url' => '',
            'gitlab_text' => '🦊 GitLab Projekt',
            'version' => defined('CMS_VERSION') ? CMS_VERSION : Version::CURRENT,
            'colors' => $this->getDefaultColors(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDefaultFeatures(): array
    {
        return [
            ['id' => null, 'icon' => '🧩', 'title' => 'Seiten, Beiträge & Landing Pages', 'description' => 'Verwalte klassische Seiten, Blogbeiträge, Hero-Bereiche und eigenständige Landing Pages in einem durchgängigen Workflow.', 'sort_order' => 1],
            ['id' => null, 'icon' => '🧱', 'title' => 'Editor.js & Content-Blöcke', 'description' => 'Nutze strukturierte Inhalte mit modernen Blöcken wie Medien+Text, Galerien, Tabellen, Accordions und weiteren Editor.js-Tools.', 'sort_order' => 2],
            ['id' => null, 'icon' => '🎨', 'title' => 'Theme-Customizer & Design', 'description' => 'Passe Farben, Layouts, Header, Footer, Kartenstile und Theme-Bereiche ohne Code direkt im Admin an.', 'sort_order' => 3],
            ['id' => null, 'icon' => '🖼️', 'title' => 'Medienbibliothek & Uploads', 'description' => 'Organisiere Bilder, Dateien, WebP-Assets und Uploads zentral mit komfortabler Bibliothek und Picker-Workflows.', 'sort_order' => 4],
            ['id' => null, 'icon' => '🔌', 'title' => 'Plugin-Ökosystem', 'description' => 'Erweitere 365CMS flexibel um Unternehmen, Events, Experten, Jobs, Feeds, Formulare und weitere Business-Module.', 'sort_order' => 5],
            ['id' => null, 'icon' => '👤', 'title' => 'Mitgliederbereich', 'description' => 'Biete Dashboard, Profile, Favoriten, Benachrichtigungen und persönliche Bereiche für registrierte Nutzer direkt im System an.', 'sort_order' => 6],
            ['id' => null, 'icon' => '🔐', 'title' => 'Rollen, Passkeys & 2FA', 'description' => 'Arbeite mit granularen Rechten, sicherer Authentifizierung, Passkeys, TOTP und zusätzlicher Zugriffshärtung.', 'sort_order' => 7],
            ['id' => null, 'icon' => '🌐', 'title' => 'SEO, Sitemap & IndexNow', 'description' => 'Steuere Meta-Daten, Redirects, Sitemaps, technische SEO-Prüfungen und IndexNow direkt im Core.', 'sort_order' => 8],
            ['id' => null, 'icon' => '🔎', 'title' => 'Suche & Indizierung', 'description' => 'Nutze Volltextsuche, TNTSearch-Indizes und aktualisierte Suchdaten für Seiten, Beiträge und mehrsprachige Inhalte.', 'sort_order' => 9],
            ['id' => null, 'icon' => '✉️', 'title' => 'Mail Queue & Zustellung', 'description' => 'Versende System- und Projektmails zuverlässig über Queue, SMTP, MIME sowie moderne OAuth- und Retry-Pfade.', 'sort_order' => 10],
            ['id' => null, 'icon' => '📣', 'title' => 'Formulare, Leads & Kontakt', 'description' => 'Bündele Kontaktanfragen, Newsletter-Workflows, Lead-Erfassung und automatische Benachrichtigungen an einer Stelle.', 'sort_order' => 11],
            ['id' => null, 'icon' => '⚙️', 'title' => 'Cron Runner & Automationen', 'description' => 'Starte Cron-Aufgaben, Worker und geplante Prozesse direkt aus dem Admin oder automatisiert im Hintergrund.', 'sort_order' => 12],
            ['id' => null, 'icon' => '🚀', 'title' => 'Performance & Cache', 'description' => 'Verbessere Auslieferung, Assets, Medien, Cache-Verhalten und Reaktionszeiten für schnelle Frontends.', 'sort_order' => 13],
            ['id' => null, 'icon' => '📊', 'title' => 'Monitoring & Health Checks', 'description' => 'Überwache Cron, Antwortzeiten, Speicher, Disk-Usage, Health-Checks und Systemzustände direkt im Dashboard.', 'sort_order' => 14],
            ['id' => null, 'icon' => '♻️', 'title' => 'Updates & Backups', 'description' => 'Halte Core, Themes und Plugins aktuell und kombiniere das mit Backup- und Wiederherstellungsprozessen.', 'sort_order' => 15],
            ['id' => null, 'icon' => '🧾', 'title' => 'DSGVO & Legal Sites', 'description' => 'Pflege Datenschutz- und Rechtsseiten, Consent, Datenexporte sowie Löschprozesse systemweit nachvollziehbar.', 'sort_order' => 16],
            ['id' => null, 'icon' => '🧭', 'title' => 'Menüs, Redirects & Navigation', 'description' => 'Verwalte Menüpositionen, slugbasierte Links, Weiterleitungen und Navigationsstrukturen zentral im Admin.', 'sort_order' => 17],
            ['id' => null, 'icon' => '🧠', 'title' => 'Themes, Hooks & APIs', 'description' => 'Setze auf Customizer, Hooks, Services und dokumentierte Erweiterungspunkte für individuelle 365CMS-Lösungen.', 'sort_order' => 18],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getDefaultColors(): array
    {
        return [
            'hero_gradient_start' => '#1e293b',
            'hero_gradient_end' => '#0f172a',
            'hero_border' => '#3b82f6',
            'hero_text' => '#ffffff',
            'features_bg' => '#f8fafc',
            'feature_card_bg' => '#ffffff',
            'feature_card_hover' => '#3b82f6',
            'primary_button' => '#3b82f6',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultFooter(): array
    {
        return [
            'id' => null,
            'content' => '<p><strong>365CMS</strong> verbindet Content-Management, Design Editor, Mitgliederbereich und modulare Business-Features in einer modernen Plattform.</p><p>Ideal für Unternehmensseiten, Portale, Netzwerke, Events und redaktionelle Websites mit Wachstumspotenzial.</p>',
            'button_text' => 'Zum Login',
            'button_url' => '/cms-login',
            'copyright' => '&copy; ' . date('Y') . ' 365CMS',
            'show_footer' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultContentSettings(): array
    {
        return [
            'id' => null,
            'content_type' => 'features',
            'content_text' => '',
            'posts_count' => 5,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
    {
        return [
            'id' => null,
            'show_header' => true,
            'show_content' => true,
            'show_footer_section' => true,
            'landing_slug' => '',
            'maintenance_mode' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultDesign(): array
    {
        return [
            'id' => null,
            'card_border_radius' => 18,
            'button_border_radius' => 12,
            'card_icon_layout' => 'top',
            'card_border_color' => '#e2e8f0',
            'card_border_width' => '1px',
            'card_shadow' => 'md',
            'feature_columns' => 'auto',
            'hero_padding' => 'md',
            'feature_padding' => 'md',
            'footer_bg' => '#0f172a',
            'footer_text_color' => '#cbd5e1',
            'content_section_bg' => '#ffffff',
        ];
    }
}
