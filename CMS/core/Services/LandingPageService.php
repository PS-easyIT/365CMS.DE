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

if (!defined('ABSPATH')) {
    exit;
}

class LandingPageService
{
    private static ?self $instance = null;
    private $db;

    private const ALLOWED_CONTENT_TYPES = ['features', 'text', 'posts'];
    private const ALLOWED_PLUGIN_AREAS = ['header', 'content', 'footer'];
    private const ALLOWED_LOGO_POSITIONS = ['top', 'left'];
    private const ALLOWED_HEADER_LAYOUTS = ['standard', 'compact'];
    private const ALLOWED_ICON_LAYOUTS = ['top', 'left'];
    private const ALLOWED_SHADOWS = ['none', 'sm', 'md', 'lg'];
    private const ALLOWED_COLUMNS = ['auto', '2', '3', '4'];
    private const ALLOWED_PADDINGS = ['sm', 'md', 'lg', 'xl'];
    private const ALLOWED_BORDER_WIDTHS = ['0', '1px', '2px', '3px'];
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->db = Database::instance();
    }

    /**
     * Stellt sicher, dass alle Basis-Sektionen der Landing Page vorhanden sind
     * und aktualisiert ältere Standard-Inhalte auf die aktuellen 365CMS-Features.
     */
    public function ensureDefaults(): void
    {
        try {
            if (!$this->hasSectionRecord('header')) {
                $header = $this->getDefaultHeader();
                unset($header['id']);
                $this->updateHeader($header);
            }

            $featureCount = $this->countSectionsByType('feature');
            if ($featureCount === 0) {
                foreach ($this->getDefaultFeatures() as $feature) {
                    $this->saveFeature(null, $feature);
                }
            } else {
                $this->backfillMissingDefaultFeatures();
                $this->upgradeLegacyFeatureDefaults();
            }

            $this->ensureSingleSectionRecord('content', $this->getDefaultContentSettings(), 50);
            $this->ensureSingleSectionRecord('footer', $this->getDefaultFooter(), 99);
            $this->ensureSingleSectionRecord('design', $this->getDefaultDesign(), 90);
            $this->ensureSingleSectionRecord('settings', $this->getDefaultSettings(), 100);
            $this->upgradeLegacyFooterDefaults();
        } catch (\Throwable $e) {
            error_log('LandingPageService::ensureDefaults() Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get Landing Page Header
     */
    public function getHeader(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'header' LIMIT 1");
            
            if (!$stmt) {
                return $this->getDefaultHeader();
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result) {
                return $this->getDefaultHeader();
            }
            
            $data = json_decode($result['data'] ?? '{}', true);
            return [
                'id' => $result['id'],
                'title' => $data['title'] ?? 'IT Expert Network CMS',
                'subtitle' => $data['subtitle'] ?? 'Modernes Content Management System',
                'logo_position' => $data['logo_position'] ?? 'top',
                'header_layout' => $data['header_layout'] ?? 'standard',
                'description' => $data['description'] ?? 'Ein leistungsstarkes, sicheres und erweiterbares CMS.',
                'header_buttons' => $data['header_buttons'] ?? [],
                'github_url' => $data['github_url'] ?? '',
                'github_text' => $data['github_text'] ?? '💻 GitHub Projekt',
                'gitlab_url' => $data['gitlab_url'] ?? '',
                'gitlab_text' => $data['gitlab_text'] ?? '🦊 GitLab Projekt',
                'version' => $data['version'] ?? (defined('CMS_VERSION') ? CMS_VERSION : '2.5.4'),
                'logo' => $data['logo'] ?? '',
                'colors' => $data['colors'] ?? $this->getDefaultColors()
            ];
        } catch (\Exception $e) {
            error_log('LandingPageService::getHeader() Error: ' . $e->getMessage());
            return $this->getDefaultHeader();
        }
    }
    
    /**
     * Update Landing Page Header
     */
    public function updateHeader(array $data): bool
    {
        // Get existing header to preserve data not being updated
        $existing = $this->getHeader();
        
        // Extract colors if present, otherwise keep existing
        $colors = [];
        if (isset($data['hero_gradient_start'])) {
            $colors = [
                'hero_gradient_start' => $data['hero_gradient_start'] ?? '#1e293b',
                'hero_gradient_end' => $data['hero_gradient_end'] ?? '#0f172a',
                'hero_border' => $data['hero_border'] ?? '#3b82f6',
                'hero_text' => $data['hero_text'] ?? '#ffffff',
                'features_bg' => $data['features_bg'] ?? '#f8fafc',
                'feature_card_bg' => $data['feature_card_bg'] ?? '#ffffff',
                'feature_card_hover' => $data['feature_card_hover'] ?? '#3b82f6',
                'primary_button' => $data['primary_button'] ?? '#3b82f6'
            ];
        } else {
            // Keep existing colors
            $colors = $existing['colors'] ?? $this->getDefaultColors();
        }
        
        // Keep existing logo if not provided
        $logo = isset($data['logo']) ? $this->sanitizeRelativeAssetPath((string)$data['logo']) : ($existing['logo'] ?? '');

        $headerButtons = $this->sanitizeHeaderButtons($data['header_buttons'] ?? $existing['header_buttons'] ?? []);
        
        $headerData = json_encode([
            'title' => $this->sanitizePlainText((string)($data['title'] ?? $existing['title']), 120),
            'subtitle' => $this->sanitizePlainText((string)($data['subtitle'] ?? $existing['subtitle']), 160),
            'logo_position' => $this->sanitizeEnum((string)($data['logo_position'] ?? $existing['logo_position'] ?? 'top'), self::ALLOWED_LOGO_POSITIONS, 'top'),
            'header_layout' => $this->sanitizeEnum((string)($data['header_layout'] ?? $existing['header_layout'] ?? 'standard'), self::ALLOWED_HEADER_LAYOUTS, 'standard'),
            'description' => $this->normalizeHtml($data['description'] ?? $existing['description']),
            'header_buttons' => $headerButtons,
            'github_url' => $this->sanitizeUrl((string)($data['github_url'] ?? $existing['github_url'] ?? '')),
            'github_text' => $this->sanitizePlainText((string)($data['github_text'] ?? $existing['github_text'] ?? '💻 GitHub Projekt'), 40),
            'gitlab_url' => $this->sanitizeUrl((string)($data['gitlab_url'] ?? $existing['gitlab_url'] ?? '')),
            'gitlab_text' => $this->sanitizePlainText((string)($data['gitlab_text'] ?? $existing['gitlab_text'] ?? '🦊 GitLab Projekt'), 40),
            'version' => $this->sanitizePlainText((string)($data['version'] ?? $existing['version']), 40),
            'logo' => $logo,
            'colors' => $colors
        ]);
        
        try {
            // Check if header exists
            $existing = $this->db->prepare("SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'header' LIMIT 1");
            if (!$existing) {
                return false;
            }
            
            $existing->execute();
            $row = $existing->fetch();
            
            if ($row) {
                // Update
                $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE type = 'header'");
                if (!$stmt) {
                    return false;
                }
                return $stmt->execute([$headerData]);
            } else {
                // Insert
                $stmt = $this->db->prepare("INSERT INTO {$this->db->prefix()}landing_sections (type, data, created_at, updated_at) VALUES ('header', ?, NOW(), NOW())");
                if (!$stmt) {
                    return false;
                }
                return $stmt->execute([$headerData]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::updateHeader() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all Feature Cards (4x3 Grid)
     */
    public function getFeatures(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'feature' ORDER BY sort_order ASC");
            
            if (!$stmt) {
                return $this->getDefaultFeatures();
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($results)) {
                return $this->getDefaultFeatures();
            }
            
            $features = [];
            foreach ($results as $row) {
                $data = json_decode($row['data'] ?? '{}', true);
                $features[] = [
                    'id' => $row['id'],
                    'icon' => $data['icon'] ?? '🎯',
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                    'sort_order' => $row['sort_order']
                ];
            }
            
            return $features;
        } catch (\Exception $e) {
            error_log('LandingPageService::getFeatures() Error: ' . $e->getMessage());
            return $this->getDefaultFeatures();
        }
    }
    
    /**
     * Add/Update Feature
     */
    public function saveFeature(?int $id, array $data): int
    {
        $featureData = json_encode([
            'icon' => $this->sanitizePlainText((string)($data['icon'] ?? '🎯'), 16),
            'title' => $this->sanitizePlainText((string)($data['title'] ?? ''), 80),
            'description' => $this->normalizeHtml($data['description'] ?? '')
        ]);
        
        $sortOrder = max(1, min(999, (int)($data['sort_order'] ?? 999)));
        
        try {
            if ($id) {
                // Update
                $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
                if (!$stmt) {
                    return 0;
                }
                $stmt->execute([$featureData, $sortOrder, $id]);
                return $id;
            } else {
                // Insert
                $stmt = $this->db->prepare("INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())");
                if (!$stmt) {
                    return 0;
                }
                $stmt->execute([$featureData, $sortOrder]);
                return (int)$this->db->lastInsertId();
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::saveFeature() Error: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete Feature
     */
    public function deleteFeature(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->db->prefix()}landing_sections WHERE id = ? AND type = 'feature'");
        return $stmt->execute([$id]);
    }
    
    /**
     * Liefert leeren String zurück wenn HTML keinen sichtbaren Text enthält
     * (z.B. <p><br></p> aus SunEditor bei leerem Feld).
     */
    private function normalizeHtml(mixed $value): string
    {
        $str = (string)($value ?? '');
        if (trim(strip_tags($str)) === '') {
            return '';
        }

        if (function_exists('sanitize_html')) {
            return (string)sanitize_html($str, 'default');
        }

        return $str;
    }

    /**
     * Default Header Data
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
            'colors' => $this->getDefaultColors()
        ];
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
        $this->ensureDefaults();
    }
    
    /**
     * Get Default Colors
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
            'primary_button' => '#3b82f6'
        ];
    }
    
    /**
     * Get Colors only
     */
    public function getColors(): array
    {
        $header = $this->getHeader();
        return $header['colors'] ?? $this->getDefaultColors();
    }

    /**
     * Get Footer Section
     */
    public function getFooter(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'footer' LIMIT 1");
            
            if (!$stmt) {
                return $this->getDefaultFooter();
            }
            
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result) {
                return $this->getDefaultFooter();
            }
            
            $data = json_decode($result['data'] ?? '{}', true);
            return [
                'id' => $result['id'],
                'content' => $data['content'] ?? '',
                'button_text' => $data['button_text'] ?? '',
                'button_url' => $data['button_url'] ?? '',
                'copyright' => $data['copyright'] ?? '&copy; ' . date('Y') . ' IT Expert Network',
                'show_footer' => $data['show_footer'] ?? true
            ];
        } catch (\Exception $e) {
            error_log('LandingPageService::getFooter() Error: ' . $e->getMessage());
            return $this->getDefaultFooter();
        }
    }

    /**
     * Update Footer Section
     */
    public function updateFooter(array $data): bool
    {
        $footerData = json_encode([
            'content' => $this->normalizeHtml($data['footer_content'] ?? ''),
            'button_text' => $this->sanitizePlainText((string)($data['footer_button_text'] ?? ''), 60),
            'button_url' => $this->sanitizeUrl((string)($data['footer_button_url'] ?? '')),
            'copyright' => $this->sanitizeCopyright((string)($data['footer_copyright'] ?? '')),
            'show_footer' => isset($data['show_footer'])
        ]);
        
        try {
            // Check if footer exists
            $existing = $this->db->prepare("SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'footer' LIMIT 1");
            $existing->execute();
            $row = $existing->fetch(\PDO::FETCH_ASSOC);
            
            if ($row) {
                // Handle both array and object return types to be safe
                $id = is_array($row) ? $row['id'] : $row->id;
                $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?");
                return $stmt->execute([$footerData, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('footer', ?, 99, NOW(), NOW())");
                return $stmt->execute([$footerData]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::updateFooter() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultFooter(): array
    {
        return array(
            'id' => null,
            'content' => '<p><strong>365CMS</strong> verbindet Content-Management, Design Editor, Mitgliederbereich und modulare Business-Features in einer modernen Plattform.</p><p>Ideal für Unternehmensseiten, Portale, Netzwerke, Events und redaktionelle Websites mit Wachstumspotenzial.</p>',
            'button_text' => 'Zum Login',
            'button_url' => '/login',
            'copyright' => '&copy; ' . date('Y') . ' 365CMS',
            'show_footer' => true
        );
    }

    /**
     * Get Content Section Settings (Grid vs. Freitext)
     */
    public function getContentSettings(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'content' LIMIT 1");
            if (!$stmt) {
                return $this->getDefaultContentSettings();
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$result) {
                return $this->getDefaultContentSettings();
            }
            $data = json_decode($result['data'] ?? '{}', true);
            return [
                'id'           => $result['id'],
                'content_type' => $data['content_type'] ?? 'features',
                'content_text' => $data['content_text'] ?? '',
                'posts_count'  => max(1, (int)($data['posts_count'] ?? 5)),
            ];
        } catch (\Exception $e) {
            error_log('LandingPageService::getContentSettings() Error: ' . $e->getMessage());
            return $this->getDefaultContentSettings();
        }
    }

    /**
     * Update Content Section Settings
     */
    public function updateContentSettings(array $data): bool
    {
        $contentData = json_encode([
            'content_type' => $this->sanitizeEnum((string)($data['content_type'] ?? 'features'), self::ALLOWED_CONTENT_TYPES, 'features'),
            'content_text' => $this->normalizeHtml($data['content_text'] ?? ''),
            'posts_count'  => max(1, min(50, (int)($data['posts_count'] ?? 5))),
        ]);
        try {
            $existing = $this->db->prepare("SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'content' LIMIT 1");
            $existing->execute();
            $row = $existing->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $id = is_array($row) ? $row['id'] : $row->id;
                $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?");
                return $stmt->execute([$contentData, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('content', ?, 50, NOW(), NOW())");
                return $stmt->execute([$contentData]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::updateContentSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultContentSettings(): array
    {
        return [
            'id'           => null,
            'content_type' => 'features',
            'content_text' => '',
            'posts_count'  => 5,
        ];
    }

    /**
     * Update Colors (stored inside header record)
     */
    public function updateColors(array $data): bool
    {
        $existing = $this->getHeader();
        $colors = [
            'hero_gradient_start' => $data['hero_gradient_start'] ?? $existing['colors']['hero_gradient_start'] ?? '#1e293b',
            'hero_gradient_end'   => $data['hero_gradient_end']   ?? $existing['colors']['hero_gradient_end']   ?? '#0f172a',
            'hero_border'         => $data['hero_border']         ?? $existing['colors']['hero_border']         ?? '#3b82f6',
            'hero_text'           => $data['hero_text']           ?? $existing['colors']['hero_text']           ?? '#ffffff',
            'features_bg'         => $data['features_bg']         ?? $existing['colors']['features_bg']         ?? '#f8fafc',
            'feature_card_bg'     => $data['feature_card_bg']     ?? $existing['colors']['feature_card_bg']     ?? '#ffffff',
            'feature_card_hover'  => $data['feature_card_hover']  ?? $existing['colors']['feature_card_hover']  ?? '#3b82f6',
            'primary_button'      => $data['primary_button']      ?? $existing['colors']['primary_button']      ?? '#3b82f6',
        ];
        // Merge into header data
        $merged = array_merge($existing, $colors);
        $merged['hero_gradient_start'] = $colors['hero_gradient_start'];
        $merged['hero_gradient_end']   = $colors['hero_gradient_end'];
        $merged['hero_border']         = $colors['hero_border'];
        $merged['hero_text']           = $colors['hero_text'];
        $merged['features_bg']         = $colors['features_bg'];
        $merged['feature_card_bg']     = $colors['feature_card_bg'];
        $merged['feature_card_hover']  = $colors['feature_card_hover'];
        $merged['primary_button']      = $colors['primary_button'];
        return $this->updateHeader($merged);
    }

    /**
     * Get Landing Page Settings
     */
    public function getSettings(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'settings' LIMIT 1");
            if (!$stmt) {
                return $this->getDefaultSettings();
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$result) {
                return $this->getDefaultSettings();
            }
            $data = json_decode($result['data'] ?? '{}', true);
            return array_merge($this->getDefaultSettings(), $data, ['id' => $result['id']]);
        } catch (\Exception $e) {
            error_log('LandingPageService::getSettings() Error: ' . $e->getMessage());
            return $this->getDefaultSettings();
        }
    }

    /**
     * Update Landing Page Settings
     */
    public function updateSettings(array $data): bool
    {
        $settingsData = json_encode([
            'show_header'         => isset($data['show_header']),
            'show_content'        => isset($data['show_content']),
            'show_footer_section' => isset($data['show_footer_section']),
            'landing_slug'        => $this->sanitizeLandingSlug((string)($data['landing_slug'] ?? '')),
            'maintenance_mode'    => isset($data['maintenance_mode']),
        ]);
        try {
            $existing = $this->db->prepare("SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'settings' LIMIT 1");
            $existing->execute();
            $row = $existing->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $id = is_array($row) ? $row['id'] : $row->id;
                $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?");
                return $stmt->execute([$settingsData, $id]);
            } else {
                $stmt = $this->db->prepare("INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('settings', ?, 100, NOW(), NOW())");
                return $stmt->execute([$settingsData]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::updateSettings() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultSettings(): array
    {
        return [
            'id'                  => null,
            'show_header'         => true,
            'show_content'        => true,
            'show_footer_section' => true,
            'landing_slug'        => '',
            'maintenance_mode'    => false,
        ];
    }

    // ── Design Tokens ────────────────────────────────────────────────

    /**
     * Get Landing Page Design Tokens (shape, layout, spacing).
     */
    public function getDesign(): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'design' LIMIT 1"
            );
            if (!$stmt) {
                return $this->getDefaultDesign();
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$result) {
                return $this->getDefaultDesign();
            }
            $data = json_decode($result['data'] ?? '{}', true);
            $merged = array_merge($this->getDefaultDesign(), is_array($data) ? $data : []);
            $merged['id'] = $result['id'];
            return $merged;
        } catch (\Exception $e) {
            error_log('LandingPageService::getDesign() Error: ' . $e->getMessage());
            return $this->getDefaultDesign();
        }
    }

    /**
     * Update Landing Page Design Tokens.
     */
    public function updateDesign(array $data): bool
    {
        $defaults = $this->getDefaultDesign();
        $existing = $this->getDesign();
        $allowed = array_keys($defaults);
        unset($allowed[array_search('id', $allowed, true)]);

        $designData = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $designData[$key] = $data[$key];
            } else {
                $designData[$key] = $existing[$key] ?? $defaults[$key];
            }
        }
        // Sanitize numeric values
        $designData['card_border_radius'] = max(0, min(48, (int)($designData['card_border_radius'] ?? 12)));
        $designData['button_border_radius'] = max(0, min(50, (int)($designData['button_border_radius'] ?? 8)));
        $designData['card_icon_layout'] = $this->sanitizeEnum((string)($designData['card_icon_layout'] ?? 'top'), self::ALLOWED_ICON_LAYOUTS, 'top');
        $designData['card_shadow'] = $this->sanitizeEnum((string)($designData['card_shadow'] ?? 'sm'), self::ALLOWED_SHADOWS, 'sm');
        $designData['feature_columns'] = $this->sanitizeEnum((string)($designData['feature_columns'] ?? 'auto'), self::ALLOWED_COLUMNS, 'auto');
        $designData['hero_padding'] = $this->sanitizeEnum((string)($designData['hero_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['feature_padding'] = $this->sanitizeEnum((string)($designData['feature_padding'] ?? 'md'), self::ALLOWED_PADDINGS, 'md');
        $designData['card_border_width'] = $this->sanitizeEnum((string)($designData['card_border_width'] ?? '1px'), self::ALLOWED_BORDER_WIDTHS, '1px');
        $designData['card_border_color'] = $this->sanitizeColor((string)($designData['card_border_color'] ?? '#e2e8f0'), '#e2e8f0');
        $designData['footer_bg'] = $this->sanitizeColor((string)($designData['footer_bg'] ?? '#1e293b'), '#1e293b');
        $designData['footer_text_color'] = $this->sanitizeColor((string)($designData['footer_text_color'] ?? '#94a3b8'), '#94a3b8');
        $designData['content_section_bg'] = $this->sanitizeColor((string)($designData['content_section_bg'] ?? '#ffffff'), '#ffffff');

        $json = json_encode($designData);
        try {
            $existing = $this->db->prepare(
                "SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'design' LIMIT 1"
            );
            $existing->execute();
            $row = $existing->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $id = is_array($row) ? $row['id'] : $row->id;
                $stmt = $this->db->prepare(
                    "UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?"
                );
                return $stmt->execute([$json, $id]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('design', ?, 90, NOW(), NOW())"
                );
                return $stmt->execute([$json]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::updateDesign() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultDesign(): array
    {
        return [
            'id'                    => null,
            'card_border_radius'    => 18,
            'button_border_radius'  => 12,
            'card_icon_layout'      => 'top',
            'card_border_color'     => '#e2e8f0',
            'card_border_width'     => '1px',
            'card_shadow'           => 'md',
            'feature_columns'       => 'auto',
            'hero_padding'          => 'md',
            'feature_padding'       => 'md',
            'footer_bg'             => '#0f172a',
            'footer_text_color'     => '#cbd5e1',
            'content_section_bg'    => '#ffffff',
        ];
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
        if (!class_exists('\\CMS\\Hooks')) {
            return [];
        }
        $plugins = \CMS\Hooks::applyFilters('landing_page_plugins', []);

        if (!is_array($plugins)) {
            return [];
        }

        $normalized = [];
        foreach ($plugins as $pluginId => $plugin) {
            if (!is_array($plugin)) {
                continue;
            }

            $id = is_string($pluginId) && $pluginId !== ''
                ? $pluginId
                : $this->sanitizePluginId((string)($plugin['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $targets = array_values(array_intersect(
                self::ALLOWED_PLUGIN_AREAS,
                array_map('strval', (array)($plugin['targets'] ?? []))
            ));

            $normalized[$id] = [
                'id' => $id,
                'name' => $this->sanitizePlainText((string)($plugin['name'] ?? $id), 120),
                'description' => $this->sanitizePlainText((string)($plugin['description'] ?? ''), 280),
                'version' => $this->sanitizePlainText((string)($plugin['version'] ?? ''), 40),
                'author' => $this->sanitizePlainText((string)($plugin['author'] ?? ''), 80),
                'targets' => $targets,
                'settings_callback' => is_callable($plugin['settings_callback'] ?? null) ? $plugin['settings_callback'] : null,
            ];
        }

        return $normalized;
    }

    /**
     * Get active plugin overrides per area.
     * Returns: ['header' => 'plugin-id'|null, 'content' => ..., 'footer' => ...,
     *           'plugin_settings' => ['plugin-id' => [...]]]
     */
    public function getPluginOverrides(): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = 'plugin_overrides' LIMIT 1"
            );
            if (!$stmt) {
                return $this->getDefaultPluginOverrides();
            }
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$result) {
                return $this->getDefaultPluginOverrides();
            }
            $data = json_decode($result['data'] ?? '{}', true);
            $defaults = $this->getDefaultPluginOverrides();
            $merged   = array_merge($defaults, is_array($data) ? $data : []);
            $merged['id'] = $result['id'];
            // Ensure plugin_settings is always an array
            if (!is_array($merged['plugin_settings'])) {
                $merged['plugin_settings'] = [];
            }
            return $merged;
        } catch (\Exception $e) {
            error_log('LandingPageService::getPluginOverrides() Error: ' . $e->getMessage());
            return $this->getDefaultPluginOverrides();
        }
    }

    /**
     * Activate or deactivate a plugin override for a specific area.
     * Pass $data['area'] ('header'|'content'|'footer') and
     * $data['plugin_id'] (plugin id string, or '' to reset to CMS default).
     */
    public function updatePluginOverride(array $data): bool
    {
        $area     = $data['area'] ?? '';
        $pluginId = $this->sanitizePluginId((string)($data['plugin_id'] ?? ''));

        if (!in_array($area, self::ALLOWED_PLUGIN_AREAS, true)) {
            return false;
        }

        if ($pluginId !== '') {
            $plugins = $this->getRegisteredPlugins();
            if (!isset($plugins[$pluginId]) || !in_array($area, $plugins[$pluginId]['targets'], true)) {
                return false;
            }
        }

        $overrides = $this->getPluginOverrides();
        $overrides[$area] = ($pluginId === '') ? null : $pluginId;
        unset($overrides['id']);

        return $this->_savePluginOverridesRecord($overrides);
    }

    /**
     * Save plugin-specific settings for one plugin.
     */
    public function savePluginSettings(string $pluginId, array $data): bool
    {
        $pluginId = $this->sanitizePluginId($pluginId);
        if ($pluginId === '') {
            return false;
        }

        $plugins = $this->getRegisteredPlugins();
        if (!isset($plugins[$pluginId]) || !is_callable($plugins[$pluginId]['settings_callback'] ?? null)) {
            return false;
        }

        $overrides = $this->getPluginOverrides();
        if (!is_array($overrides['plugin_settings'])) {
            $overrides['plugin_settings'] = [];
        }
        $cleanSettings = [];
        foreach ($data as $key => $value) {
            $cleanKey = $this->sanitizePluginId((string)$key);
            if ($cleanKey === '') {
                continue;
            }
            if (is_array($value)) {
                $cleanSettings[$cleanKey] = $this->sanitizePluginSettingsArray($value);
                continue;
            }
            $cleanSettings[$cleanKey] = $this->sanitizePlainText((string)$value, 5000);
        }

        $overrides['plugin_settings'][$pluginId] = $cleanSettings;
        unset($overrides['id']);

        return $this->_savePluginOverridesRecord($overrides);
    }

    /**
     * Get plugin-specific settings for one plugin.
     */
    public function getPluginSettings(string $pluginId): array
    {
        $overrides = $this->getPluginOverrides();
        return (array)($overrides['plugin_settings'][$pluginId] ?? []);
    }

    /**
     * Helper: upsert the plugin_overrides record.
     */
    private function _savePluginOverridesRecord(array $overrides): bool
    {
        $json = json_encode($overrides);
        try {
            $existing = $this->db->prepare(
                "SELECT id FROM {$this->db->prefix()}landing_sections WHERE type = 'plugin_overrides' LIMIT 1"
            );
            $existing->execute();
            $row = $existing->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $id = is_array($row) ? $row['id'] : $row->id;
                $stmt = $this->db->prepare(
                    "UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?"
                );
                return $stmt->execute([$json, $id]);
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('plugin_overrides', ?, 200, NOW(), NOW())"
                );
                return $stmt->execute([$json]);
            }
        } catch (\Exception $e) {
            error_log('LandingPageService::_savePluginOverridesRecord() Error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultPluginOverrides(): array
    {
        return [
            'id'             => null,
            'header'         => null,
            'content'        => null,
            'footer'         => null,
            'plugin_settings' => [],
        ];
    }

    private function sanitizePlainText(string $value, int $maxLength = 255): string
    {
        $value = trim(strip_tags($value));
        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function sanitizeUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('esc_url_raw')) {
            $sanitized = (string)esc_url_raw($value);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        if (preg_match('#^/[a-z0-9/_\-\.\?=&%]*$#i', $value) === 1) {
            return $value;
        }

        return '';
    }

    private function sanitizeLandingSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '/') {
            return '';
        }

        $value = preg_replace('#[^a-z0-9/_\-]#i', '', $value) ?? '';
        $value = '/' . trim($value, '/');

        return $value === '/' ? '' : $value;
    }

    private function sanitizeColor(string $value, string $fallback): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
    }

    private function sanitizeEnum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function sanitizeCopyright(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = strip_tags($value);
        $value = preg_replace('/\{year\}/i', '{year}', $value) ?? $value;
        return mb_substr($value, 0, 255);
    }

    private function sanitizeRelativeAssetPath(string $value): string
    {
        $value = trim(str_replace('\\', '/', $value));
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '..')) {
            return '';
        }

        return ltrim($value, '/');
    }

    private function sanitizeHeaderButtons(array $buttons): array
    {
        $cleanButtons = [];

        foreach (array_slice($buttons, 0, 4) as $button) {
            if (!is_array($button)) {
                continue;
            }

            $text = $this->sanitizePlainText((string)($button['text'] ?? ''), 40);
            $url = $this->sanitizeUrl((string)($button['url'] ?? ''));
            $icon = $this->sanitizePlainText((string)($button['icon'] ?? ''), 16);
            $target = $this->sanitizeEnum((string)($button['target'] ?? '_self'), ['_self', '_blank'], '_self');
            $outline = !empty($button['outline']);

            if ($text === '' && $url === '') {
                continue;
            }

            $cleanButtons[] = [
                'text' => $text,
                'url' => $url,
                'icon' => $icon,
                'target' => $target,
                'outline' => $outline,
            ];
        }

        return $cleanButtons;
    }

    private function sanitizePluginId(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/[^a-z0-9_\-]/', '', $value) ?? '';
    }

    private function sanitizePluginSettingsArray(array $settings): array
    {
        $clean = [];
        foreach ($settings as $key => $value) {
            $cleanKey = $this->sanitizePluginId((string)$key);
            if ($cleanKey === '') {
                continue;
            }
            $clean[$cleanKey] = is_array($value)
                ? $this->sanitizePluginSettingsArray($value)
                : $this->sanitizePlainText((string)$value, 5000);
        }

        return $clean;
    }
}
