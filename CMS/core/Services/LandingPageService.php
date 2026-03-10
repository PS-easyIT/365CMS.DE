<?php
/**
 * Landing Page Service
 * 
 * Verwaltet Landing Page Header und Feature Grid
 * 
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Services\Landing\LandingPluginService;
use CMS\Services\Landing\LandingRepository;
use CMS\Services\Landing\LandingSanitizer;
use CMS\Services\Landing\LandingSectionService;

if (!defined('ABSPATH')) {
    exit;
}

class LandingPageService
{
    private static ?self $instance = null;
    private readonly LandingSectionService $sectionService;
    private readonly LandingPluginService $pluginService;
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $repository = new LandingRepository(Database::instance());
        $sanitizer = new LandingSanitizer();

        $this->sectionService = new LandingSectionService($repository, $sanitizer);
        $this->pluginService = new LandingPluginService($repository, $sanitizer);
    }

    /**
     * Stellt sicher, dass alle Basis-Sektionen der Landing Page vorhanden sind
     * und aktualisiert ältere Standard-Inhalte auf die aktuellen 365CMS-Features.
     */
    public function ensureDefaults(): void
    {
        $this->sectionService->ensureDefaults();
    }
    
    /**
     * Get Landing Page Header
     */
    public function getHeader(): array
    {
        return $this->sectionService->getHeader();
    }
    
    /**
     * Update Landing Page Header
     */
    public function updateHeader(array $data): bool
    {
        return $this->sectionService->updateHeader($data);
    }
    
    /**
     * Get all Feature Cards (4x3 Grid)
     */
    public function getFeatures(): array
    {
        return $this->sectionService->getFeatures();
    }
    
    /**
     * Add/Update Feature
     */
    public function saveFeature(?int $id, array $data): int
    {
        return $this->sectionService->saveFeature($id, $data);
    }
    
    /**
     * Delete Feature
     */
    public function deleteFeature(int $id): bool
    {
        return $this->sectionService->deleteFeature($id);
    }
    
    /**
     * Default Features (12 Features für 4x3 Grid)
     */
    private function getDefaultFeatures(): array
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
            ['id' => null, 'icon' => '🧠', 'title' => 'Themes & Hooks', 'description' => 'Customizer, Hooks und Erweiterungspunkte für individuelle 365CMS-Lösungen.', 'sort_order' => 12]
        ];
    }
    
    /**
     * Initialize Default Landing Page
     */
    public function initializeDefaults(): void
    {
        $this->sectionService->initializeDefaults();
    }
    
    /**
     * Get Colors only
     */
    public function getColors(): array
    {
        return $this->sectionService->getColors();
    }

    /**
     * Get Footer Section
     */
    public function getFooter(): array
    {
        return $this->sectionService->getFooter();
    }

    /**
     * Update Footer Section
     */
    public function updateFooter(array $data): bool
    {
        return $this->sectionService->updateFooter($data);
    }

    /**
     * Get Content Section Settings (Grid vs. Freitext)
     */
    public function getContentSettings(): array
    {
        return $this->sectionService->getContentSettings();
    }

    /**
     * Update Content Section Settings
     */
    public function updateContentSettings(array $data): bool
    {
        return $this->sectionService->updateContentSettings($data);
    }

    /**
     * Update Colors (stored inside header record)
     */
    public function updateColors(array $data): bool
    {
        return $this->sectionService->updateColors($data);
    }

    /**
     * Get Landing Page Settings
     */
    public function getSettings(): array
    {
        return $this->sectionService->getSettings();
    }

    /**
     * Update Landing Page Settings
     */
    public function updateSettings(array $data): bool
    {
        return $this->sectionService->updateSettings($data);
    }

    // ── Design Tokens ────────────────────────────────────────────────

    /**
     * Get Landing Page Design Tokens (shape, layout, spacing).
     */
    public function getDesign(): array
    {
        return $this->sectionService->getDesign();
    }

    /**
     * Update Landing Page Design Tokens.
     */
    public function updateDesign(array $data): bool
    {
        return $this->sectionService->updateDesign($data);
    }

    private function hasSectionRecord(string $type): bool
    {
        return $this->countSectionsByType($type) > 0;
    }

    private function countSectionsByType(string $type): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->db->prefix()}landing_sections WHERE type = ?");
            if (!$stmt) {
                return 0;
            }

            $stmt->execute([$type]);

            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('LandingPageService::countSectionsByType() Error: ' . $e->getMessage());
            return 0;
        }
    }

    private function ensureSingleSectionRecord(string $type, array $data, int $sortOrder): void
    {
        if ($this->hasSectionRecord($type)) {
            return;
        }

        $payload = $data;
        unset($payload['id']);

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())"
        );

        if ($stmt) {
            $stmt->execute([$type, json_encode($payload), $sortOrder]);
        }
    }

    private function upgradeLegacyFeatureDefaults(): void
    {
        $features = $this->getFeatures();
        $legacyTitles = [
            'Blitzschnell',
            'Sicher',
            'Responsive',
            'Anpassbar',
            'Erweiterbar',
            'Analytics',
            'Multi-User',
            'SEO-Ready',
            'REST API',
            'Backups',
            'Updates',
            'Editor',
        ];

        $currentTitles = array_values(array_map(
            static fn(array $feature): string => trim((string)($feature['title'] ?? '')),
            $features
        ));

        if ($currentTitles !== $legacyTitles) {
            return;
        }

        $deleteStmt = $this->db->prepare("DELETE FROM {$this->db->prefix()}landing_sections WHERE type = 'feature'");
        if ($deleteStmt) {
            $deleteStmt->execute();
        }

        foreach ($this->getDefaultFeatures() as $feature) {
            $this->saveFeature(null, $feature);
        }
    }

    private function backfillMissingDefaultFeatures(): void
    {
        $existingFeatures = $this->getFeatures();
        $defaultFeatures = $this->getDefaultFeatures();

        if (count($existingFeatures) >= count($defaultFeatures)) {
            return;
        }

        $existingTitles = [];
        $existingSortOrders = [];

        foreach ($existingFeatures as $feature) {
            $title = trim((string)($feature['title'] ?? ''));
            if ($title !== '') {
                $existingTitles[] = mb_strtolower($title);
            }

            $sortOrder = (int)($feature['sort_order'] ?? 0);
            if ($sortOrder > 0) {
                $existingSortOrders[] = $sortOrder;
            }
        }

        foreach ($defaultFeatures as $feature) {
            $defaultTitle = mb_strtolower(trim((string)($feature['title'] ?? '')));
            $defaultSortOrder = (int)($feature['sort_order'] ?? 0);

            if (
                ($defaultTitle !== '' && in_array($defaultTitle, $existingTitles, true))
                || ($defaultSortOrder > 0 && in_array($defaultSortOrder, $existingSortOrders, true))
            ) {
                continue;
            }

            $this->saveFeature(null, $feature);
        }
    }

    private function mergeWithDefaultFeatures(array $features): array
    {
        $defaultFeatures = $this->getDefaultFeatures();

        if (count($features) >= count($defaultFeatures)) {
            usort($features, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));
            return $features;
        }

        $merged = $features;
        $existingTitles = [];
        $existingSortOrders = [];

        foreach ($features as $feature) {
            $title = trim((string)($feature['title'] ?? ''));
            if ($title !== '') {
                $existingTitles[] = mb_strtolower($title);
            }

            $sortOrder = (int)($feature['sort_order'] ?? 0);
            if ($sortOrder > 0) {
                $existingSortOrders[] = $sortOrder;
            }
        }

        foreach ($defaultFeatures as $feature) {
            $defaultTitle = mb_strtolower(trim((string)($feature['title'] ?? '')));
            $defaultSortOrder = (int)($feature['sort_order'] ?? 0);

            if (
                ($defaultTitle !== '' && in_array($defaultTitle, $existingTitles, true))
                || ($defaultSortOrder > 0 && in_array($defaultSortOrder, $existingSortOrders, true))
            ) {
                continue;
            }

            $merged[] = $feature;
        }

        usort($merged, static fn(array $a, array $b): int => ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0)));

        return $merged;
    }

    private function upgradeLegacyFooterDefaults(): void
    {
        $footer = $this->getFooter();
        $legacyContent = '<p>Kontaktieren Sie uns für weitere Informationen.</p>';
        $currentContent = trim((string)($footer['content'] ?? ''));

        if ($currentContent !== $legacyContent) {
            return;
        }

        $defaultFooter = $this->getDefaultFooter();
        $this->updateFooter([
            'footer_content' => $defaultFooter['content'],
            'footer_button_text' => $defaultFooter['button_text'],
            'footer_button_url' => $defaultFooter['button_url'],
            'footer_copyright' => $defaultFooter['copyright'],
            'show_footer' => true,
        ]);
    }

    // ── Plugin Override System ────────────────────────────────────────────────

    /**
     * Get all registered Landing Page plugins.
     * Plugins register via Hooks::addFilter('landing_page_plugins', callback).
     * Each entry: ['id', 'name', 'description', 'version', 'author',
     *              'targets' => ['header'|'content'|'footer'],
     *              'settings_callback' => callable|null]
     */
    public function getRegisteredPlugins(): array
    {
        return $this->pluginService->getRegisteredPlugins();
    }

    /**
     * Get active plugin overrides per area.
     * Returns: ['header' => 'plugin-id'|null, 'content' => ..., 'footer' => ...,
     *           'plugin_settings' => ['plugin-id' => [...]]]
     */
    public function getPluginOverrides(): array
    {
        return $this->pluginService->getPluginOverrides();
    }

    /**
     * Activate or deactivate a plugin override for a specific area.
     * Pass $data['area'] ('header'|'content'|'footer') and
     * $data['plugin_id'] (plugin id string, or '' to reset to CMS default).
     */
    public function updatePluginOverride(array $data): bool
    {
        return $this->pluginService->updatePluginOverride($data);
    }

    /**
     * Save plugin-specific settings for one plugin.
     */
    public function savePluginSettings(string $pluginId, array $data): bool
    {
        return $this->pluginService->savePluginSettings($pluginId, $data);
    }

    /**
     * Get plugin-specific settings for one plugin.
     */
    public function getPluginSettings(string $pluginId): array
    {
        return $this->pluginService->getPluginSettings($pluginId);
    }
}
