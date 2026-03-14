<?php
declare(strict_types=1);

/**
 * CookieManagerModule – Cookie-Consent-Kategorien & Einstellungen
 */

if (!defined('ABSPATH')) {
    exit;
}

class CookieManagerModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    /** @var array<int, array<string, mixed>> */
    private const array DEFAULT_CATEGORIES = [
        ['name' => 'Essenziell', 'slug' => 'necessary', 'description' => 'Technisch notwendige Services und Cookies.', 'is_required' => 1, 'sort_order' => 0],
        ['name' => 'Funktional', 'slug' => 'functional', 'description' => 'Erweiterte Komfort- und Bedienfunktionen.', 'is_required' => 0, 'sort_order' => 10],
        ['name' => 'Analytics', 'slug' => 'analytics', 'description' => 'Messung und Optimierung der Nutzung.', 'is_required' => 0, 'sort_order' => 20],
        ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Tracking, Kampagnen und Remarketing.', 'is_required' => 0, 'sort_order' => 30],
        ['name' => 'Externe Medien', 'slug' => 'external_media', 'description' => 'Eingebettete Videos, Maps und Social Widgets.', 'is_required' => 0, 'sort_order' => 40],
    ];

    /** @var array<string, array<string, mixed>> */
    private const array CURATED_SERVICES = [
        'google_analytics' => ['name' => 'Google Analytics', 'provider' => 'Google', 'category_slug' => 'analytics', 'description' => 'Webanalyse mit Events, Seitenaufrufen und Kampagnenmessung.', 'patterns' => ['google-analytics.com', 'www.googletagmanager.com/gtag/js', 'gtag("config"', "gtag('config'"]],
        'google_tag_manager' => ['name' => 'Google Tag Manager', 'provider' => 'Google', 'category_slug' => 'analytics', 'description' => 'Container für Tracking- und Marketing-Tags.', 'patterns' => ['googletagmanager.com/gtm.js', 'googletagmanager.com/ns.html']],
        'matomo' => ['name' => 'Matomo', 'provider' => 'Matomo', 'category_slug' => 'analytics', 'description' => 'Datenschutzfreundliche Webanalyse.', 'patterns' => ['matomo', '_paq']],
        'facebook_pixel' => ['name' => 'Facebook Pixel', 'provider' => 'Meta', 'category_slug' => 'marketing', 'description' => 'Conversion- und Remarketing-Tracking für Facebook/Instagram.', 'patterns' => ['connect.facebook.net', 'fbq(']],
        'linkedin_insight' => ['name' => 'LinkedIn Insight Tag', 'provider' => 'LinkedIn', 'category_slug' => 'marketing', 'description' => 'Kampagnen- und Zielgruppenmessung für LinkedIn Ads.', 'patterns' => ['snap.licdn.com', '_linkedin_partner_id']],
        'youtube' => ['name' => 'YouTube', 'provider' => 'Google', 'category_slug' => 'external_media', 'description' => 'Eingebettete YouTube-Videos.', 'patterns' => ['youtube.com/embed', 'youtu.be/', 'youtube-nocookie.com']],
        'vimeo' => ['name' => 'Vimeo', 'provider' => 'Vimeo', 'category_slug' => 'external_media', 'description' => 'Eingebettete Vimeo-Videos.', 'patterns' => ['player.vimeo.com', 'vimeo.com']],
        'google_maps' => ['name' => 'Google Maps', 'provider' => 'Google', 'category_slug' => 'external_media', 'description' => 'Karten und Standort-Einbindungen.', 'patterns' => ['maps.googleapis.com', 'www.google.com/maps/embed', 'google.com/maps/embed']],
        'cms_feed' => ['name' => 'CMS Feed', 'provider' => '365CMS', 'category_slug' => 'external_media', 'description' => 'Öffentliche Feed-Archive und externe Feed-Daten für den Bereich CMS Feed.', 'patterns' => ['cms-feed', '/feed/', 'CMS_Feed']],
        'hubspot' => ['name' => 'HubSpot', 'provider' => 'HubSpot', 'category_slug' => 'marketing', 'description' => 'CRM-, Form- und Marketing-Automation.', 'patterns' => ['js.hs-scripts.com', 'hs-script-loader']],
        'cms_core' => ['name' => '365CMS Kernfunktionen', 'provider' => '365CMS', 'category_slug' => 'necessary', 'description' => 'Session-, Login-, Sicherheits- und Formularfunktionen.', 'patterns' => ['csrf_token', 'PHPSESSID'], 'is_essential' => true],
    ];

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTables();
        $this->ensureDefaultCategories();
        $this->ensureManagedDefaultServices();
    }

    private function ensureTables(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}cookie_categories (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name        VARCHAR(100) NOT NULL,
                slug        VARCHAR(100) NOT NULL,
                description TEXT,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                scripts     TEXT,
                sort_order  INT NOT NULL DEFAULT 0,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}cookie_services (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                provider VARCHAR(120) DEFAULT NULL,
                category_slug VARCHAR(100) NOT NULL DEFAULT 'necessary',
                description TEXT DEFAULT NULL,
                cookie_names TEXT DEFAULT NULL,
                code_snippet MEDIUMTEXT DEFAULT NULL,
                is_essential TINYINT(1) NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_slug (slug),
                INDEX idx_category (category_slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function ensureDefaultCategories(): void
    {
        foreach (self::DEFAULT_CATEGORIES as $category) {
            $exists = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}cookie_categories WHERE slug = ?",
                [$category['slug']]
            );

            if ($exists === 0) {
                $this->db->insert('cookie_categories', [
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'is_required' => $category['is_required'],
                    'is_active' => 1,
                    'scripts' => '',
                    'sort_order' => $category['sort_order'],
                ]);
            }
        }
    }

    private function ensureManagedDefaultServices(): void
    {
        foreach (['cms_core', 'cms_feed'] as $slug) {
            if (!isset(self::CURATED_SERVICES[$slug])) {
                continue;
            }

            $service = self::CURATED_SERVICES[$slug];
            $existing = $this->db->get_row(
                "SELECT id, category_slug, description, provider FROM {$this->prefix}cookie_services WHERE slug = ? LIMIT 1",
                [$slug]
            );

            $data = [
                'name' => (string)$service['name'],
                'provider' => (string)$service['provider'],
                'category_slug' => !empty($service['is_essential']) ? 'necessary' : (string)($service['category_slug'] ?? 'necessary'),
                'description' => (string)($service['description'] ?? ''),
                'cookie_names' => '',
                'code_snippet' => '',
                'is_essential' => !empty($service['is_essential']) ? 1 : 0,
                'is_active' => 1,
            ];

            if ($existing !== null) {
                $this->db->update('cookie_services', $data, ['slug' => $slug]);
                continue;
            }

            $this->db->insert('cookie_services', [
                'name' => (string)$service['name'],
                'slug' => $slug,
                'provider' => (string)$service['provider'],
                'category_slug' => $data['category_slug'],
                'description' => $data['description'],
                'cookie_names' => '',
                'code_snippet' => '',
                'is_essential' => $data['is_essential'],
                'is_active' => 1,
            ]);
        }
    }

    public function getData(): array
    {
        $categories = $this->db->get_results(
            "SELECT * FROM {$this->prefix}cookie_categories ORDER BY sort_order, name"
        ) ?: [];

        $services = $this->db->get_results(
            "SELECT * FROM {$this->prefix}cookie_services ORDER BY is_essential DESC, provider ASC, name ASC"
        ) ?: [];

        $settingKeys = [
            'cookie_banner_enabled',
            'cookie_consent_enabled',
            'cookie_banner_position',
            'cookie_banner_text',
            'cookie_banner_style',
            'cookie_lifetime_days',
            'cookie_policy_url',
            'cookie_accept_text',
            'cookie_reject_text',
            'cookie_essential_text',
            'cookie_matomo_self_hosted_url',
            'cookie_matomo_site_id',
            'cookie_matomo_hosting_region',
            'cookie_matomo_ip_anonymization',
            'cookie_matomo_respect_dnt',
            'cookie_matomo_disable_cookies',
            'cookie_matomo_log_retention_days',
            'cookie_matomo_dsgvo_note',
            'cookie_scan_results',
            'cookie_scan_last_run',
        ];
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingKeys
        ) ?: [];
        $settings = array_fill_keys($settingKeys, '');
        foreach ($rows as $row) {
            $settings[$row->option_name] = $row->option_value;
        }

        $scanResults = \CMS\Json::decodeArray($settings['cookie_scan_results'] ?? null, []);
        if (!is_array($scanResults)) {
            $scanResults = [];
        }
        $scanResults = $this->sanitizeStoredScanResults($scanResults);

        return [
            'categories' => array_map(fn($c) => (array)$c, $categories),
            'services'   => array_map(fn($s) => (array)$s, $services),
            'settings'   => $settings,
            'scan_results' => $scanResults,
            'curated_services' => self::CURATED_SERVICES,
        ];
    }

    public function saveSettings(array $post): array
    {
        $keys = [
            'cookie_banner_enabled'  => isset($post['cookie_banner_enabled']) ? '1' : '0',
            'cookie_consent_enabled' => isset($post['cookie_banner_enabled']) ? '1' : '0',
            'cookie_banner_position' => in_array($post['cookie_banner_position'] ?? '', ['bottom', 'top', 'center'], true)
                ? $post['cookie_banner_position'] : 'bottom',
            'cookie_banner_text'     => strip_tags($post['cookie_banner_text'] ?? '', '<p><a><strong><em><br>'),
            'cookie_banner_style'    => in_array($post['cookie_banner_style'] ?? '', ['light', 'dark', 'custom'], true)
                ? $post['cookie_banner_style'] : 'dark',
            'cookie_lifetime_days'   => (string)max(1, min(365, (int)($post['cookie_lifetime_days'] ?? 30))),
            'cookie_policy_url'      => trim((string)($post['cookie_policy_url'] ?? '/datenschutz')),
            'cookie_accept_text'     => trim((string)($post['cookie_accept_text'] ?? 'Akzeptieren')),
            'cookie_reject_text'     => trim((string)($post['cookie_reject_text'] ?? 'Ablehnen')),
            'cookie_essential_text'  => trim((string)($post['cookie_essential_text'] ?? 'Nur Essenzielle')),
            'cookie_matomo_self_hosted_url' => $this->normalizeOptionalUrlForStorage((string)($post['cookie_matomo_self_hosted_url'] ?? '')),
            'cookie_matomo_site_id' => trim((string)($post['cookie_matomo_site_id'] ?? '1')),
            'cookie_matomo_hosting_region' => trim((string)($post['cookie_matomo_hosting_region'] ?? 'Deutschland / EU')),
            'cookie_matomo_ip_anonymization' => isset($post['cookie_matomo_ip_anonymization']) ? '1' : '0',
            'cookie_matomo_respect_dnt' => isset($post['cookie_matomo_respect_dnt']) ? '1' : '0',
            'cookie_matomo_disable_cookies' => isset($post['cookie_matomo_disable_cookies']) ? '1' : '0',
            'cookie_matomo_log_retention_days' => (string)max(1, min(3650, (int)($post['cookie_matomo_log_retention_days'] ?? 180))),
            'cookie_matomo_dsgvo_note' => strip_tags((string)($post['cookie_matomo_dsgvo_note'] ?? ''), '<p><a><strong><em><br><ul><ol><li>'),
        ];

        try {
            foreach ($keys as $key => $value) {
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            return ['success' => true, 'message' => 'Cookie-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function saveCategory(array $post): array
    {
        $id   = (int)($post['category_id'] ?? 0);
        $name = trim($post['category_name'] ?? '');
        if ($name === '') {
            return ['success' => false, 'error' => 'Name ist erforderlich.'];
        }

        $slug = $post['category_slug'] ?? '';
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug ?: $name))) ?? '';

        $isRequired = isset($post['is_required']) || $slug === 'necessary' ? 1 : 0;
        $isActive = $isRequired === 1 ? 1 : (isset($post['is_active']) ? 1 : 0);

        $data = [
            'name'        => $name,
            'slug'        => $slug,
            'description' => strip_tags($post['category_description'] ?? ''),
            'is_required' => $isRequired,
            'is_active'   => $isActive,
            'scripts'     => strip_tags($post['category_scripts'] ?? '', '<script><noscript>'),
            'sort_order'  => (int)($post['sort_order'] ?? 0),
        ];

        try {
            if ($id > 0) {
                $this->db->update('cookie_categories', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Kategorie aktualisiert.'];
            }
            $this->db->insert('cookie_categories', $data);
            return ['success' => true, 'message' => 'Kategorie erstellt.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function deleteCategory(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $cat = $this->db->get_row(
            "SELECT is_required FROM {$this->prefix}cookie_categories WHERE id = ?",
            [$id]
        );
        if ($cat && (int)$cat->is_required === 1) {
            return ['success' => false, 'error' => 'Pflicht-Kategorien können nicht gelöscht werden.'];
        }
        $this->db->delete('cookie_categories', ['id' => $id]);
        return ['success' => true, 'message' => 'Kategorie gelöscht.'];
    }

    public function saveService(array $post): array
    {
        $id = (int)($post['service_id'] ?? 0);
        $name = trim((string)($post['service_name'] ?? ''));
        $slug = trim((string)($post['service_slug'] ?? ''));
        $provider = trim((string)($post['service_provider'] ?? ''));
        $categorySlug = trim((string)($post['category_slug'] ?? 'necessary'));
        $description = trim((string)($post['service_description'] ?? ''));
        $cookieNames = trim((string)($post['cookie_names'] ?? ''));
        $codeSnippet = trim((string)($post['code_snippet'] ?? ''));
        $isEssential = isset($post['is_essential']) ? 1 : 0;
        $isActive = $isEssential === 1 ? 1 : (isset($post['is_active']) ? 1 : 0);

        if ($name === '') {
            return ['success' => false, 'error' => 'Service-Name ist erforderlich.'];
        }

        if ($slug === '') {
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name))) ?? '';
        }

        if ($isEssential === 1) {
            $categorySlug = 'necessary';
            $isActive = 1;
        }

        $categoryExists = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}cookie_categories WHERE slug = ?",
            [$categorySlug]
        );
        if ($categoryExists === 0) {
            $categorySlug = 'necessary';
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'provider' => $provider,
            'category_slug' => $categorySlug,
            'description' => $description,
            'cookie_names' => $cookieNames,
            'code_snippet' => strip_tags($codeSnippet, '<script><noscript><iframe><img><div><span><a>'),
            'is_essential' => $isEssential,
            'is_active' => $isActive,
        ];

        try {
            if ($id > 0) {
                $this->db->update('cookie_services', $data, ['id' => $id]);
                return ['success' => true, 'message' => 'Service aktualisiert.'];
            }

            $this->db->insert('cookie_services', $data);
            return ['success' => true, 'message' => 'Service angelegt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    public function deleteService(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        $service = $this->db->get_row(
            "SELECT is_essential FROM {$this->prefix}cookie_services WHERE id = ? LIMIT 1",
            [$id]
        );
        if ($service && (int)$service->is_essential === 1) {
            return ['success' => false, 'error' => 'Essenzielle Services dürfen nicht gelöscht werden.'];
        }

        $this->db->delete('cookie_services', ['id' => $id]);
        return ['success' => true, 'message' => 'Service gelöscht.'];
    }

    public function importCuratedService(string $slug, bool $selfHosted = false): array
    {
        $slug = trim($slug);
        if (!isset(self::CURATED_SERVICES[$slug])) {
            return ['success' => false, 'error' => 'Unbekannter Standard-Service.'];
        }

        $existing = (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}cookie_services WHERE slug = ?",
            [$slug]
        );
        if ($existing > 0 && !($slug === 'matomo' && $selfHosted)) {
            return ['success' => true, 'message' => 'Standard-Service ist bereits vorhanden.'];
        }

        $service = self::CURATED_SERVICES[$slug];
        $data = [
            'name' => $service['name'],
            'slug' => $slug,
            'provider' => $service['provider'],
            'category_slug' => $service['is_essential'] ?? false ? 'necessary' : $service['category_slug'],
            'description' => $service['description'],
            'cookie_names' => '',
            'code_snippet' => '',
            'is_essential' => !empty($service['is_essential']) ? 1 : 0,
            'is_active' => 1,
        ];

        if ($slug === 'matomo' && $selfHosted) {
            $data['name'] = 'Matomo (Self-Hosted)';
            $data['provider'] = 'Matomo Self-Hosted';
            $data['category_slug'] = 'necessary';
            $data['description'] = 'Self-hosted Matomo-Instanz. Kann bei berechtigtem berechtigtem Interesse bzw. DSGVO-konformer Eigenhosting-Konfiguration als essenzieller Dienst behandelt werden.';
            $data['is_essential'] = 1;
            $data['is_active'] = 1;
        }

        if ($existing > 0) {
            $this->db->update('cookie_services', $data, ['slug' => $slug]);
            return ['success' => true, 'message' => 'Vorhandener Service wurde auf die gewählte Matomo-Konfiguration aktualisiert.'];
        }

        $this->db->insert('cookie_services', $data);

        return ['success' => true, 'message' => 'Standard-Service wurde übernommen.'];
    }

    public function runScanner(): array
    {
        $detected = [];
        $sources = [];

        $scanTargets = [
            defined('ABSPATH') ? ABSPATH . 'themes/' : '',
            defined('ABSPATH') ? ABSPATH . 'includes/' : '',
            defined('ABSPATH') ? ABSPATH . 'assets/js/' : '',
        ];

        foreach ($scanTargets as $target) {
            if ($target === '' || !is_dir($target)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['php', 'js', 'html', 'css'], true)) {
                    continue;
                }

                $content = @file_get_contents($file->getPathname());
                if (!is_string($content) || $content === '') {
                    continue;
                }

                $this->collectMatches($content, str_replace((string)ABSPATH, '', $file->getPathname()), $detected, $sources);
            }
        }

        try {
            $pageRows = $this->db->get_results("SELECT slug, title, content FROM {$this->prefix}pages ORDER BY id DESC LIMIT 250") ?: [];
            foreach ($pageRows as $row) {
                $content = (string)($row->content ?? '');
                if ($content !== '') {
                    $this->collectMatches($content, 'DB: page/' . ($row->slug ?: $row->title ?: 'unbekannt'), $detected, $sources);
                }
            }

            $this->scanConfiguredAnalyticsServices($detected, $sources);
            $this->scanSnippetSettings($detected, $sources);
        } catch (\Throwable) {
        }

        $results = [];
        foreach ($detected as $slug => $name) {
            $results[] = [
                'slug' => $slug,
                'name' => $name,
                'provider' => self::CURATED_SERVICES[$slug]['provider'] ?? '',
                'category_slug' => self::CURATED_SERVICES[$slug]['category_slug'] ?? 'necessary',
                'sources' => array_values(array_unique($sources[$slug] ?? [])),
                'self_hostable' => $slug === 'matomo',
            ];
        }

        $this->storeSetting('cookie_scan_results', json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]');
        $this->storeSetting('cookie_scan_last_run', date('Y-m-d H:i:s'));

        return ['success' => true, 'message' => count($results) . ' Services/Signaturen erkannt.', 'results' => $results];
    }

    private function collectMatches(string $content, string $source, array &$detected, array &$sources): void
    {
        $contentLower = strtolower($content);
        foreach (self::CURATED_SERVICES as $slug => $service) {
            foreach ((array)($service['patterns'] ?? []) as $pattern) {
                if ($pattern !== '' && str_contains($contentLower, strtolower((string)$pattern))) {
                    $detected[$slug] = (string)$service['name'];
                    $sources[$slug][] = $source;
                    break;
                }
            }
        }
    }

    private function scanConfiguredAnalyticsServices(array &$detected, array &$sources): void
    {
        $settingNames = [
            'seo_analytics_matomo_enabled',
            'seo_analytics_matomo_code',
            'seo_analytics_matomo_url',
            'seo_analytics_matomo_site_id',
            'seo_analytics_ga4_enabled',
            'seo_analytics_ga4_id',
            'seo_analytics_gtm_enabled',
            'seo_analytics_gtm_id',
            'seo_analytics_fb_pixel_enabled',
            'seo_analytics_fb_pixel_id',
        ];

        $settings = $this->getSettingsMap($settingNames);

        if (($settings['seo_analytics_matomo_enabled'] ?? '0') === '1') {
            $matomoCode = trim((string)($settings['seo_analytics_matomo_code'] ?? ''));
            $matomoUrl = trim((string)($settings['seo_analytics_matomo_url'] ?? ''));
            if ($matomoCode !== '' || $matomoUrl !== '') {
                $detected['matomo'] = self::CURATED_SERVICES['matomo']['name'];
                $sources['matomo'][] = 'System: Analytics-Einstellungen (Matomo)';
            }
        }

        $ga4Id = trim((string)($settings['seo_analytics_ga4_id'] ?? ''));
        if (($settings['seo_analytics_ga4_enabled'] ?? '0') === '1' && !$this->isPlaceholderAnalyticsId($ga4Id)) {
            $detected['google_analytics'] = self::CURATED_SERVICES['google_analytics']['name'];
            $sources['google_analytics'][] = 'System: Analytics-Einstellungen (GA4)';
        }

        $gtmId = trim((string)($settings['seo_analytics_gtm_id'] ?? ''));
        if (($settings['seo_analytics_gtm_enabled'] ?? '0') === '1' && !$this->isPlaceholderAnalyticsId($gtmId)) {
            $detected['google_tag_manager'] = self::CURATED_SERVICES['google_tag_manager']['name'];
            $sources['google_tag_manager'][] = 'System: Analytics-Einstellungen (GTM)';
        }

        $pixelId = trim((string)($settings['seo_analytics_fb_pixel_id'] ?? ''));
        if (($settings['seo_analytics_fb_pixel_enabled'] ?? '0') === '1' && !$this->isPlaceholderAnalyticsId($pixelId)) {
            $detected['facebook_pixel'] = self::CURATED_SERVICES['facebook_pixel']['name'];
            $sources['facebook_pixel'][] = 'System: Analytics-Einstellungen (Meta Pixel)';
        }
    }

    private function scanSnippetSettings(array &$detected, array &$sources): void
    {
        $settingNames = [
            'seo_analytics_custom_head',
            'seo_analytics_custom_body',
            'seo_analytics_matomo_code',
        ];

        $settings = $this->getSettingsMap($settingNames);
        foreach ($settings as $optionName => $content) {
            if ($this->shouldScanSettingValue($optionName, $content)) {
                $this->collectMatches($content, 'DB: setting/' . $optionName, $detected, $sources);
            }
        }
    }

    /** @return array<string, string> */
    private function getSettingsMap(array $settingNames): array
    {
        if ($settingNames === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($settingNames), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingNames
        ) ?: [];

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string)$row->option_name] = (string)$row->option_value;
        }

        return $settings;
    }

    private function shouldScanSettingValue(string $optionName, string $content): bool
    {
        $optionName = strtolower($optionName);
        $content = trim($content);
        if ($content === '') {
            return false;
        }

        $idOnlyPatterns = [
            '/^gtm-[a-z0-9]+$/i',
            '/^g-[a-z0-9]+$/i',
            '/^ua-\d+-\d+$/i',
            '/^aw-\d+$/i',
            '/^\d{6,}$/',
        ];

        foreach ($idOnlyPatterns as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                return false;
            }
        }

        $excludedOptionFragments = ['measurement_id', 'analytics_id', 'tag_manager_id', 'pixel_id', 'tracking_id', 'google_analytics', 'ga4_id', 'gtm_id'];
        foreach ($excludedOptionFragments as $fragment) {
            if (str_contains($optionName, $fragment) && !str_contains($content, '<script')) {
                return false;
            }
        }

        return str_contains($content, '<script')
            || str_contains($content, '<iframe')
            || str_contains($content, '<noscript')
            || str_contains($content, 'google-analytics.com')
            || str_contains($content, 'googletagmanager.com/')
            || str_contains($content, 'connect.facebook.net')
            || str_contains($content, 'snap.licdn.com')
            || str_contains($content, 'player.vimeo.com')
            || str_contains($content, 'youtube.com/embed')
            || str_contains($content, 'youtube-nocookie.com')
            || str_contains($content, 'maps.googleapis.com')
            || str_contains($content, 'google.com/maps/embed')
            || str_contains($content, '_paq');
    }

    private function isPlaceholderAnalyticsId(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return true;
        }

        return preg_match('/^(G-|UA-|GTM-|AW-)?X{4,}[A-Z0-9-]*$/i', $value) === 1;
    }

    /** @param array<int, mixed> $scanResults */
    private function sanitizeStoredScanResults(array $scanResults): array
    {
        $normalized = [];

        foreach ($scanResults as $result) {
            if (!is_array($result)) {
                continue;
            }

            $slug = (string)($result['slug'] ?? '');
            $sources = array_values(array_filter(array_map('strval', (array)($result['sources'] ?? []))));

            if ($this->isStoredResultFalsePositive($slug, $sources)) {
                continue;
            }

            $result['sources'] = $sources;
            $result['self_hostable'] = $slug === 'matomo';
            $normalized[] = $result;
        }

        return $normalized;
    }

    /** @param array<int, string> $sources */
    private function isStoredResultFalsePositive(string $slug, array $sources): bool
    {
        $placeholderSources = [
            'DB: setting/google_analytics',
            'DB: setting/seo_analytics_ga4_id',
            'DB: setting/seo_analytics_gtm_id',
            'DB: setting/seo_analytics_fb_pixel_id',
        ];

        if (in_array($slug, ['google_analytics', 'google_tag_manager', 'facebook_pixel'], true) && $sources === []) {
            return true;
        }

        if (in_array($slug, ['google_analytics', 'google_tag_manager', 'facebook_pixel'], true) && $sources !== []) {
            foreach ($sources as $source) {
                if (!in_array($source, $placeholderSources, true)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function storeSetting(string $key, string $value): void
    {
        $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
        if ($exists) {
            $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
            return;
        }

        $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
    }

    private function sanitizeOptionalUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && preg_match('~^[a-z0-9.-]+(?::\d+)?(?:/.*)?$~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '';
    }

    private function normalizeOptionalUrlForStorage(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $url) && preg_match('~^[a-z0-9._-]+(?::\d+)?(?:/.*)?$~i', $url)) {
            return 'https://' . ltrim($url, '/');
        }

        return $url;
    }
}
