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
                'version' => $data['version'] ?? '2.0.0',
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
        $logo = isset($data['logo']) ? $data['logo'] : ($existing['logo'] ?? '');
        
        $headerData = json_encode([
            'title' => $data['title'] ?? $existing['title'],
            'subtitle' => $data['subtitle'] ?? $existing['subtitle'],
            'logo_position' => $data['logo_position'] ?? $existing['logo_position'] ?? 'top',
            'header_layout' => $data['header_layout'] ?? $existing['header_layout'] ?? 'standard',
            'description' => $data['description'] ?? $existing['description'],
            'header_buttons' => $data['header_buttons'] ?? $existing['header_buttons'] ?? [],
            'github_url' => $data['github_url'] ?? $existing['github_url'],
            'github_text' => $data['github_text'] ?? $existing['github_text'] ?? '💻 GitHub Projekt',
            'gitlab_url' => $data['gitlab_url'] ?? $existing['gitlab_url'],
            'gitlab_text' => $data['gitlab_text'] ?? $existing['gitlab_text'] ?? '🦊 GitLab Projekt',
            'version' => $data['version'] ?? $existing['version'],
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
            'icon' => $data['icon'] ?? '🎯',
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? ''
        ]);
        
        $sortOrder = $data['sort_order'] ?? 999;
        
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
     * Default Header Data
     */
    private function getDefaultHeader(): array
    {
        return [
            'id' => null,
            'title' => 'IT Expert Network CMS',
            'subtitle' => 'Modernes Content Management System',
            'description' => 'Ein leistungsstarkes, sicheres und erweiterbares CMS für professionelle Websites.',
            'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
            'github_text' => '💻 GitHub Projekt',
            'gitlab_url' => '',
            'gitlab_text' => '🦊 GitLab Projekt',
            'version' => '2.0.0',
            'colors' => $this->getDefaultColors()
        ];
    }
    
    /**
     * Default Features (12 Features für 4x3 Grid)
     */
    private function getDefaultFeatures(): array
    {
        return [
            ['id' => null, 'icon' => '🚀', 'title' => 'Blitzschnell', 'description' => 'Optimierte Performance für schnelle Ladezeiten', 'sort_order' => 1],
            ['id' => null, 'icon' => '🔒', 'title' => 'Sicher', 'description' => 'Moderne Sicherheitsstandards und Verschlüsselung', 'sort_order' => 2],
            ['id' => null, 'icon' => '📱', 'title' => 'Responsive', 'description' => 'Perfekte Darstellung auf allen Geräten', 'sort_order' => 3],
            ['id' => null, 'icon' => '🎨', 'title' => 'Anpassbar', 'description' => 'Flexibles Theme-System für individuelle Designs', 'sort_order' => 4],
            ['id' => null, 'icon' => '🔌', 'title' => 'Erweiterbar', 'description' => 'Plugin-System für unbegrenzte Möglichkeiten', 'sort_order' => 5],
            ['id' => null, 'icon' => '📊', 'title' => 'Analytics', 'description' => 'Integrierte Statistiken und Monitoring', 'sort_order' => 6],
            ['id' => null, 'icon' => '👥', 'title' => 'Multi-User', 'description' => 'Rollen-basierte Benutzerverwaltung', 'sort_order' => 7],
            ['id' => null, 'icon' => '🌐', 'title' => 'SEO-Ready', 'description' => 'Suchmaschinenoptimiertes Framework', 'sort_order' => 8],
            ['id' => null, 'icon' => '⚡', 'title' => 'REST API', 'description' => 'Moderne API für Integrationen', 'sort_order' => 9],
            ['id' => null, 'icon' => '💾', 'title' => 'Backups', 'description' => 'Automatische Datensicherung', 'sort_order' => 10],
            ['id' => null, 'icon' => '🔄', 'title' => 'Updates', 'description' => 'Einfache Update-Verwaltung', 'sort_order' => 11],
            ['id' => null, 'icon' => '📝', 'title' => 'Editor', 'description' => 'Intuitiver Content-Editor', 'sort_order' => 12]
        ];
    }
    
    /**
     * Initialize Default Landing Page
     */
    public function initializeDefaults(): void
    {
        // Insert default header
        $header = $this->getDefaultHeader();
        unset($header['id']);
        $this->updateHeader($header);
        
        // Insert default features
        $features = $this->getDefaultFeatures();
        foreach ($features as $feature) {
            unset($feature['id']);
            $this->saveFeature(null, $feature);
        }
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
            'content' => $data['footer_content'] ?? '',
            'button_text' => $data['footer_button_text'] ?? '',
            'button_url' => $data['footer_button_url'] ?? '',
            'copyright' => $data['footer_copyright'] ?? '',
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
            'content' => '<p>Kontaktieren Sie uns für weitere Informationen.</p>',
            'copyright' => '&copy; ' . date('Y') . ' IT Expert Network',
            'show_footer' => true
        );
    }
}
