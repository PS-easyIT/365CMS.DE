<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Json;
use PDO;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingSectionService
{
    private const ALLOWED_CONTENT_TYPES = ['features', 'text', 'posts'];
    private const ALLOWED_LOGO_POSITIONS = ['top', 'left'];
    private const ALLOWED_HEADER_LAYOUTS = ['standard', 'compact'];
    private const ALLOWED_ICON_LAYOUTS = ['top', 'left'];
    private const ALLOWED_SHADOWS = ['none', 'sm', 'md', 'lg'];
    private const ALLOWED_COLUMNS = ['auto', '2', '3', '4'];
    private const ALLOWED_PADDINGS = ['sm', 'md', 'lg', 'xl'];
    private const ALLOWED_BORDER_WIDTHS = ['0', '1px', '2px', '3px'];

    public function __construct(
        private readonly LandingRepository $repository,
        private readonly LandingSanitizer $sanitizer,
    ) {
    }

    public function ensureDefaults(): void
    {
        try {
            if (!$this->repository->hasSectionRecord('header')) {
                $header = $this->getDefaultHeader();
                unset($header['id']);
                $this->updateHeader($header);
            }

            $featureCount = $this->repository->countSectionsByType('feature');
            if ($featureCount === 0) {
                foreach ($this->getDefaultFeatures() as $feature) {
                    $this->saveFeature(null, $feature);
                }
            } else {
                $this->backfillMissingDefaultFeatures();
                $this->upgradeLegacyFeatureDefaults();
            }

            $this->repository->ensureSingleSectionRecord('content', $this->getDefaultContentSettings(), 50);
            $this->repository->ensureSingleSectionRecord('footer', $this->getDefaultFooter(), 99);
            $this->repository->ensureSingleSectionRecord('design', $this->getDefaultDesign(), 90);
            $this->repository->ensureSingleSectionRecord('settings', $this->getDefaultSettings(), 100);
            $this->upgradeLegacyFooterDefaults();
        } catch (\Throwable $e) {
            error_log('LandingSectionService::ensureDefaults() Error: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeader(): array
    {
        try {
            $result = $this->repository->getSection('header');
            if ($result === null) {
                return $this->getDefaultHeader();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'title' => $data['title'] ?? 'IT Expert Network CMS',
                'subtitle' => $data['subtitle'] ?? 'Modernes Content Management System',
                'logo_position' => $data['logo_position'] ?? 'top',
                'header_layout' => $data['header_layout'] ?? 'standard',
                'description' => $data['description'] ?? 'Ein leistungsstarkes, sicheres und erweiterbares CMS.',
                'header_buttons' => is_array($data['header_buttons'] ?? null) ? $data['header_buttons'] : [],
                'github_url' => $data['github_url'] ?? '',
                'github_text' => $data['github_text'] ?? '💻 GitHub Projekt',
                'gitlab_url' => $data['gitlab_url'] ?? '',
                'gitlab_text' => $data['gitlab_text'] ?? '🦊 GitLab Projekt',
                'version' => $data['version'] ?? (defined('CMS_VERSION') ? CMS_VERSION : '2.5.4'),
                'logo' => $data['logo'] ?? '',
                'colors' => is_array($data['colors'] ?? null) ? $data['colors'] : $this->getDefaultColors(),
            ];
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getHeader() Error: ' . $e->getMessage());
            return $this->getDefaultHeader();
        }
    }

    public function updateHeader(array $data): bool
    {
        $existing = $this->getHeader();

        $colors = isset($data['hero_gradient_start'])
            ? [
                'hero_gradient_start' => $data['hero_gradient_start'] ?? '#1e293b',
                'hero_gradient_end' => $data['hero_gradient_end'] ?? '#0f172a',
                'hero_border' => $data['hero_border'] ?? '#3b82f6',
                'hero_text' => $data['hero_text'] ?? '#ffffff',
                'features_bg' => $data['features_bg'] ?? '#f8fafc',
                'feature_card_bg' => $data['feature_card_bg'] ?? '#ffffff',
                'feature_card_hover' => $data['feature_card_hover'] ?? '#3b82f6',
                'primary_button' => $data['primary_button'] ?? '#3b82f6',
            ]
            : ($existing['colors'] ?? $this->getDefaultColors());

        $payload = [
            'title' => $this->sanitizer->sanitizePlainText((string)($data['title'] ?? $existing['title']), 120),
            'subtitle' => $this->sanitizer->sanitizePlainText((string)($data['subtitle'] ?? $existing['subtitle']), 160),
            'logo_position' => $this->sanitizer->sanitizeEnum((string)($data['logo_position'] ?? $existing['logo_position'] ?? 'top'), self::ALLOWED_LOGO_POSITIONS, 'top'),
            'header_layout' => $this->sanitizer->sanitizeEnum((string)($data['header_layout'] ?? $existing['header_layout'] ?? 'standard'), self::ALLOWED_HEADER_LAYOUTS, 'standard'),
            'description' => $this->sanitizer->normalizeHtml($data['description'] ?? $existing['description']),
            'header_buttons' => $this->sanitizer->sanitizeHeaderButtons($data['header_buttons'] ?? $existing['header_buttons'] ?? []),
            'github_url' => $this->sanitizer->sanitizeUrl((string)($data['github_url'] ?? $existing['github_url'] ?? '')),
            'github_text' => $this->sanitizer->sanitizePlainText((string)($data['github_text'] ?? $existing['github_text'] ?? '💻 GitHub Projekt'), 40),
            'gitlab_url' => $this->sanitizer->sanitizeUrl((string)($data['gitlab_url'] ?? $existing['gitlab_url'] ?? '')),
            'gitlab_text' => $this->sanitizer->sanitizePlainText((string)($data['gitlab_text'] ?? $existing['gitlab_text'] ?? '🦊 GitLab Projekt'), 40),
            'version' => $this->sanitizer->sanitizePlainText((string)($data['version'] ?? $existing['version']), 40),
            'logo' => isset($data['logo'])
                ? $this->sanitizer->sanitizeRelativeAssetPath((string)$data['logo'])
                : ($existing['logo'] ?? ''),
            'colors' => $colors,
        ];

        try {
            return $this->repository->upsertSection('header', $payload, 0);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::updateHeader() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeatures(): array
    {
        try {
            $results = $this->repository->getSectionsByType('feature');
            if ($results === []) {
                return $this->getDefaultFeatures();
            }

            $features = [];
            foreach ($results as $row) {
                $data = Json::decodeArray($row['data'] ?? null, []);
                $data = is_array($data) ? $data : [];
                $features[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'icon' => $data['icon'] ?? '🎯',
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                    'sort_order' => (int)($row['sort_order'] ?? 0),
                ];
            }

            return $this->mergeWithDefaultFeatures($features);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getFeatures() Error: ' . $e->getMessage());
            return $this->getDefaultFeatures();
        }
    }

    public function saveFeature(?int $id, array $data): int
    {
        $payload = [
            'icon' => $this->sanitizer->sanitizePlainText((string)($data['icon'] ?? '🎯'), 16),
            'title' => $this->sanitizer->sanitizePlainText((string)($data['title'] ?? ''), 80),
            'description' => $this->sanitizer->normalizeHtml($data['description'] ?? ''),
        ];

        $sortOrder = max(1, min(999, (int)($data['sort_order'] ?? 999)));

        try {
            return $this->repository->saveFeature($id, $payload, $sortOrder);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::saveFeature() Error: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteFeature(int $id): bool
    {
        return $this->repository->deleteFeature($id);
    }

    public function initializeDefaults(): void
    {
        $this->ensureDefaults();
    }

    /**
     * @return array<string, string>
     */
    public function getColors(): array
    {
        $header = $this->getHeader();
        return is_array($header['colors'] ?? null) ? $header['colors'] : $this->getDefaultColors();
    }

    /**
     * @return array<string, mixed>
     */
    public function getFooter(): array
    {
        try {
            $result = $this->repository->getSection('footer');
            if ($result === null) {
                return $this->getDefaultFooter();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'content' => $data['content'] ?? '',
                'button_text' => $data['button_text'] ?? '',
                'button_url' => $data['button_url'] ?? '',
                'copyright' => $data['copyright'] ?? '&copy; ' . date('Y') . ' IT Expert Network',
                'show_footer' => $data['show_footer'] ?? true,
            ];
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getFooter() Error: ' . $e->getMessage());
            return $this->getDefaultFooter();
        }
    }

    public function updateFooter(array $data): bool
    {
        $payload = [
            'content' => $this->sanitizer->normalizeHtml($data['footer_content'] ?? ''),
            'button_text' => $this->sanitizer->sanitizePlainText((string)($data['footer_button_text'] ?? ''), 60),
            'button_url' => $this->sanitizer->sanitizeUrl((string)($data['footer_button_url'] ?? '')),
            'copyright' => $this->sanitizer->sanitizeCopyright((string)($data['footer_copyright'] ?? '')),
            'show_footer' => isset($data['show_footer']),
        ];

        try {
            return $this->repository->upsertSection('footer', $payload, 99);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::updateFooter() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getContentSettings(): array
    {
        try {
            $result = $this->repository->getSection('content');
            if ($result === null) {
                return $this->getDefaultContentSettings();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return [
                'id' => (int)($result['id'] ?? 0),
                'content_type' => $data['content_type'] ?? 'features',
                'content_text' => $data['content_text'] ?? '',
                'posts_count' => max(1, (int)($data['posts_count'] ?? 5)),
            ];
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getContentSettings() Error: ' . $e->getMessage());
            return $this->getDefaultContentSettings();
        }
    }

    public function updateContentSettings(array $data): bool
    {
        $payload = [
            'content_type' => $this->sanitizer->sanitizeEnum((string)($data['content_type'] ?? 'features'), self::ALLOWED_CONTENT_TYPES, 'features'),
            'content_text' => $this->sanitizer->normalizeHtml($data['content_text'] ?? ''),
            'posts_count' => max(1, min(50, (int)($data['posts_count'] ?? 5))),
        ];

        try {
            return $this->repository->upsertSection('content', $payload, 50);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::updateContentSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateColors(array $data): bool
    {
        $existing = $this->getHeader();
        $colors = [
            'hero_gradient_start' => $data['hero_gradient_start'] ?? $existing['colors']['hero_gradient_start'] ?? '#1e293b',
            'hero_gradient_end' => $data['hero_gradient_end'] ?? $existing['colors']['hero_gradient_end'] ?? '#0f172a',
            'hero_border' => $data['hero_border'] ?? $existing['colors']['hero_border'] ?? '#3b82f6',
            'hero_text' => $data['hero_text'] ?? $existing['colors']['hero_text'] ?? '#ffffff',
            'features_bg' => $data['features_bg'] ?? $existing['colors']['features_bg'] ?? '#f8fafc',
            'feature_card_bg' => $data['feature_card_bg'] ?? $existing['colors']['feature_card_bg'] ?? '#ffffff',
            'feature_card_hover' => $data['feature_card_hover'] ?? $existing['colors']['feature_card_hover'] ?? '#3b82f6',
            'primary_button' => $data['primary_button'] ?? $existing['colors']['primary_button'] ?? '#3b82f6',
        ];

        $merged = array_merge($existing, $colors);
        foreach ($colors as $key => $value) {
            $merged[$key] = $value;
        }

        return $this->updateHeader($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        try {
            $result = $this->repository->getSection('settings');
            if ($result === null) {
                return $this->getDefaultSettings();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];

            return array_merge($this->getDefaultSettings(), $data, ['id' => (int)($result['id'] ?? 0)]);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getSettings() Error: ' . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }

    public function updateSettings(array $data): bool
    {
        $payload = [
            'show_header' => isset($data['show_header']),
            'show_content' => isset($data['show_content']),
            'show_footer_section' => isset($data['show_footer_section']),
            'landing_slug' => $this->sanitizer->sanitizeLandingSlug((string)($data['landing_slug'] ?? '')),
            'maintenance_mode' => isset($data['maintenance_mode']),
        ];

        try {
            return $this->repository->upsertSection('settings', $payload, 100);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::updateSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getDesign(): array
    {
        try {
            $result = $this->repository->getSection('design');
            if ($result === null) {
                return $this->getDefaultDesign();
            }

            $data = Json::decodeArray($result['data'] ?? null, []);
            $data = is_array($data) ? $data : [];
            $merged = array_merge($this->getDefaultDesign(), $data);
            $merged['id'] = (int)($result['id'] ?? 0);
            return $merged;
        } catch (\Throwable $e) {
            error_log('LandingSectionService::getDesign() Error: ' . $e->getMessage());
            return $this->getDefaultDesign();
        }
    }

    public function updateDesign(array $data): bool
    {
        $defaults = $this->getDefaultDesign();
        $existing = $this->getDesign();
        $allowedKeys = array_keys($defaults);

        $designData = [];
        foreach ($allowedKeys as $key) {
            if ($key === 'id') {
                continue;
            }
            $designData[$key] = array_key_exists($key, $data)
                ? $data[$key]
                : ($existing[$key] ?? $defaults[$key]);
        }

        $designData['card_border_radius'] = max(0, min(48, (int)($designData['card_border_radius'] ?? 12)));
        $designData['button_border_radius'] = max(0, min(50, (int)($designData['button_border_radius'] ?? 8)));
        $designData['card_icon_layout'] = $this->sanitizer->sanitizeEnum((string)($designData['card_icon_layout'] ?? 'top'), self::ALLOWED_ICON_LAYOUTS, 'top');
        $designData['card_shadow'] = $this->sanitizer->sanitizeEnum((string)($designData['card_shadow'] ?? 'sm'), self::ALLOWED_SHADOWS, 'sm');
        $designData['feature_columns'] = $this->sanitizer->sanitizeEnum((string)($designData['feature_columns'] ?? 'auto'), self::ALLOWED_COLUMNS, 'auto');
        $designData['hero_padding'] = $this->sanitizer->sanitizeEnum((string)($designData['hero_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['feature_padding'] = $this->sanitizer->sanitizeEnum((string)($designData['feature_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['card_border_width'] = $this->sanitizer->sanitizeEnum((string)($designData['card_border_width'] ?? '1px'), self::ALLOWED_BORDER_WIDTHS, '1px');
        $designData['card_border_color'] = $this->sanitizer->sanitizeColor((string)($designData['card_border_color'] ?? '#e2e8f0'), '#e2e8f0');
        $designData['footer_bg'] = $this->sanitizer->sanitizeColor((string)($designData['footer_bg'] ?? '#1e293b'), '#1e293b');
        $designData['footer_text_color'] = $this->sanitizer->sanitizeColor((string)($designData['footer_text_color'] ?? '#94a3b8'), '#94a3b8');
        $designData['content_section_bg'] = $this->sanitizer->sanitizeColor((string)($designData['content_section_bg'] ?? '#ffffff'), '#ffffff');

        try {
            return $this->repository->upsertSection('design', $designData, 90);
        } catch (\Throwable $e) {
            error_log('LandingSectionService::updateDesign() Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultHeader(): array
    {
        return [
            'id' => null,
            'title' => '365CMS – modernes CMS für Inhalte, Portale und Mitgliederbereiche',
            'subtitle' => 'Landing Pages, Redaktion, Plugins, Member Area und Design Editor in einem System',
            'description' => '365CMS vereint Content-Management, Design-Anpassung, Mitgliederfunktionen, System-Mails und modulare Business-Features in einer flexiblen Plattform für professionelle Websites und Portale.',
            'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
            'github_text' => '💻 GitHub Projekt',
            'gitlab_url' => '',
            'gitlab_text' => '🦊 GitLab Projekt',
            'version' => defined('CMS_VERSION') ? CMS_VERSION : '2.5.4',
            'colors' => $this->getDefaultColors(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
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
            ['id' => null, 'icon' => '🧠', 'title' => 'Themes & Hooks', 'description' => 'Customizer, Hooks und Erweiterungspunkte für individuelle 365CMS-Lösungen.', 'sort_order' => 12],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getDefaultColors(): array
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
    private function getDefaultFooter(): array
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
    private function getDefaultContentSettings(): array
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
    private function getDefaultSettings(): array
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
    private function getDefaultDesign(): array
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

        $this->repository->deleteAllFeatures();
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

    /**
     * @param array<int, array<string, mixed>> $features
     * @return array<int, array<string, mixed>>
     */
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
}
