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
            ['id' => null, 'icon' => '🧩', 'title' => 'Seiten & Content', 'description' => 'Erstelle Seiten, Beiträge, Landing Pages und strukturierte Inhalte zentral im CMS.', 'sort_order' => 1],
            ['id' => null, 'icon' => '🎨', 'title' => 'Design Editor', 'description' => 'Farben, Layouts, Header, Footer und Theme-Bereiche ohne Code anpassen.', 'sort_order' => 2],
            ['id' => null, 'icon' => '🔌', 'title' => 'Plugin-Ökosystem', 'description' => 'Unternehmen, Events, Experten, Jobs, Feeds und weitere Module flexibel ergänzen.', 'sort_order' => 3],
            ['id' => null, 'icon' => '👤', 'title' => 'Mitgliederbereich', 'description' => 'Dashboard, Profil, Sicherheit, Benachrichtigungen und persönliche Bereiche integriert.', 'sort_order' => 4],
            ['id' => null, 'icon' => '🛡️', 'title' => 'Rollen & Sicherheit', 'description' => 'Granulare Rechte, CSRF-Schutz, sichere Authentifizierung und moderne Security-Bausteine.', 'sort_order' => 5],
            ['id' => null, 'icon' => '🖼️', 'title' => 'Medienverwaltung', 'description' => 'Bilder, Dokumente, Uploads und Assets komfortabel organisieren und bereitstellen.', 'sort_order' => 6],
            ['id' => null, 'icon' => '✉️', 'title' => 'Mail & Zustellung', 'description' => 'SMTP, MIME, OAuth/XOAuth2 und Systemmails für zuverlässige Kommunikation.', 'sort_order' => 7],
            ['id' => null, 'icon' => '🌐', 'title' => 'SEO & Sichtbarkeit', 'description' => 'Meta-Daten, Redirects, saubere URLs und Suchmaschinenfreundlichkeit ab Werk.', 'sort_order' => 8],
            ['id' => null, 'icon' => '📣', 'title' => 'Kontakt & Leads', 'description' => 'Formulare, Newsletter, Anfragen und automatisierte Benachrichtigungen bündeln.', 'sort_order' => 9],
            ['id' => null, 'icon' => '⚙️', 'title' => 'Cron & Automationen', 'description' => 'Hintergrundjobs, Worker und geplante Aufgaben für wiederkehrende Prozesse.', 'sort_order' => 10],
            ['id' => null, 'icon' => '🚀', 'title' => 'Performance', 'description' => 'Saubere Assets, optimierte Auslieferung und schnelle Oberflächen für den Alltag.', 'sort_order' => 11],
            ['id' => null, 'icon' => '🧠', 'title' => 'Themes & Hooks', 'description' => 'Customizer, Hooks und Erweiterungspunkte für individuelle 365CMS-Lösungen.', 'sort_order' => 12],
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
            'button_url' => '/login',
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
