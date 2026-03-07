<?php
/**
 * SEO Service
 * 
 * Schema.org, Sitemap, robots.txt
 * 
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

class SEOService
{
    private static ?self $instance = null;
    private Database $db;
    private string $prefix;
    
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
        $this->prefix = $this->db->getPrefix();
        $this->ensureSeoMetaTable();
    }

    private function ensureSeoMetaTable(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}seo_meta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                content_type VARCHAR(20) NOT NULL,
                content_id BIGINT UNSIGNED NOT NULL,
                canonical_url VARCHAR(500) DEFAULT NULL,
                robots_index TINYINT(1) NOT NULL DEFAULT 1,
                robots_follow TINYINT(1) NOT NULL DEFAULT 1,
                og_title VARCHAR(255) DEFAULT NULL,
                og_description TEXT DEFAULT NULL,
                og_image VARCHAR(500) DEFAULT NULL,
                og_type VARCHAR(50) DEFAULT NULL,
                twitter_card VARCHAR(50) DEFAULT NULL,
                twitter_title VARCHAR(255) DEFAULT NULL,
                twitter_description TEXT DEFAULT NULL,
                twitter_image VARCHAR(500) DEFAULT NULL,
                focus_keyphrase VARCHAR(255) DEFAULT NULL,
                schema_type VARCHAR(100) DEFAULT NULL,
                sitemap_priority DECIMAL(2,1) DEFAULT NULL,
                sitemap_changefreq VARCHAR(20) DEFAULT NULL,
                hreflang_group VARCHAR(120) DEFAULT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_content (content_type, content_id),
                INDEX idx_content_type (content_type),
                INDEX idx_focus_keyphrase (focus_keyphrase)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getContentMeta(string $contentType, int $contentId): array
    {
        if ($contentId <= 0) {
            return $this->getDefaultMeta();
        }

        $row = $this->db->get_row(
            "SELECT * FROM {$this->prefix}seo_meta WHERE content_type = ? AND content_id = ? LIMIT 1",
            [$contentType, $contentId]
        );

        if (!$row) {
            return $this->getDefaultMeta();
        }

        return [
            'canonical_url'       => (string)($row->canonical_url ?? ''),
            'robots_index'        => (int)($row->robots_index ?? 1) === 1,
            'robots_follow'       => (int)($row->robots_follow ?? 1) === 1,
            'og_title'            => (string)($row->og_title ?? ''),
            'og_description'      => (string)($row->og_description ?? ''),
            'og_image'            => (string)($row->og_image ?? ''),
            'og_type'             => (string)($row->og_type ?? ''),
            'twitter_card'        => (string)($row->twitter_card ?? ''),
            'twitter_title'       => (string)($row->twitter_title ?? ''),
            'twitter_description' => (string)($row->twitter_description ?? ''),
            'twitter_image'       => (string)($row->twitter_image ?? ''),
            'focus_keyphrase'     => (string)($row->focus_keyphrase ?? ''),
            'schema_type'         => (string)($row->schema_type ?? ''),
            'sitemap_priority'    => $row->sitemap_priority !== null ? (string)$row->sitemap_priority : '',
            'sitemap_changefreq'  => (string)($row->sitemap_changefreq ?? ''),
            'hreflang_group'      => (string)($row->hreflang_group ?? ''),
        ];
    }

    public function saveContentMeta(string $contentType, int $contentId, array $data): void
    {
        if ($contentId <= 0) {
            return;
        }

        $payload = [
            'content_type'        => $contentType,
            'content_id'          => $contentId,
            'canonical_url'       => $this->sanitizeOptionalUrl((string)($data['canonical_url'] ?? '')),
            'robots_index'        => !empty($data['robots_index']) ? 1 : 0,
            'robots_follow'       => !empty($data['robots_follow']) ? 1 : 0,
            'og_title'            => $this->sanitizeText((string)($data['og_title'] ?? ''), 255),
            'og_description'      => $this->sanitizeLongText((string)($data['og_description'] ?? '')),
            'og_image'            => $this->sanitizeOptionalUrl((string)($data['og_image'] ?? '')),
            'og_type'             => $this->sanitizeText((string)($data['og_type'] ?? 'article'), 50),
            'twitter_card'        => $this->sanitizeText((string)($data['twitter_card'] ?? 'summary_large_image'), 50),
            'twitter_title'       => $this->sanitizeText((string)($data['twitter_title'] ?? ''), 255),
            'twitter_description' => $this->sanitizeLongText((string)($data['twitter_description'] ?? '')),
            'twitter_image'       => $this->sanitizeOptionalUrl((string)($data['twitter_image'] ?? '')),
            'focus_keyphrase'     => $this->sanitizeText((string)($data['focus_keyphrase'] ?? ''), 255),
            'schema_type'         => $this->sanitizeText((string)($data['schema_type'] ?? 'WebPage'), 100),
            'sitemap_priority'    => $this->sanitizePriority((string)($data['sitemap_priority'] ?? '')),
            'sitemap_changefreq'  => $this->sanitizeChangefreq((string)($data['sitemap_changefreq'] ?? '')),
            'hreflang_group'      => $this->sanitizeText((string)($data['hreflang_group'] ?? ''), 120),
        ];

        $existing = $this->db->get_row(
            "SELECT id FROM {$this->prefix}seo_meta WHERE content_type = ? AND content_id = ? LIMIT 1",
            [$contentType, $contentId]
        );

        if ($existing) {
            $this->db->update('seo_meta', $payload, ['id' => (int)$existing->id]);
            return;
        }

        $this->db->insert('seo_meta', $payload);
    }

    public function getAuditRows(): array
    {
        $pages = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.meta_title, p.meta_description, p.status,
                    sm.canonical_url, sm.robots_index, sm.robots_follow, sm.og_title, sm.og_description,
                    sm.og_image, sm.og_type, sm.twitter_card, sm.twitter_title, sm.twitter_description,
                    sm.twitter_image, sm.focus_keyphrase, sm.schema_type, sm.sitemap_priority, sm.sitemap_changefreq
             FROM {$this->prefix}pages p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'page' AND sm.content_id = p.id
             WHERE p.status IN ('published', 'draft', 'private')
             ORDER BY p.updated_at DESC"
        ) ?: [];

        $posts = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.meta_title, p.meta_description, p.status,
                    sm.canonical_url, sm.robots_index, sm.robots_follow, sm.og_title, sm.og_description,
                    sm.og_image, sm.og_type, sm.twitter_card, sm.twitter_title, sm.twitter_description,
                    sm.twitter_image, sm.focus_keyphrase, sm.schema_type, sm.sitemap_priority, sm.sitemap_changefreq
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'post' AND sm.content_id = p.id
             WHERE p.status IN ('published', 'draft')
             ORDER BY p.updated_at DESC"
        ) ?: [];

        $mapRows = static function (array $rows, string $type): array {
            return array_map(static function ($row) use ($type): array {
                return [
                    'type' => $type,
                    'id' => (int)($row->id ?? 0),
                    'title' => (string)($row->title ?? ''),
                    'slug' => (string)($row->slug ?? ''),
                    'status' => (string)($row->status ?? ''),
                    'meta_title' => (string)($row->meta_title ?? ''),
                    'meta_description' => (string)($row->meta_description ?? ''),
                    'canonical_url' => (string)($row->canonical_url ?? ''),
                    'robots_index' => (int)($row->robots_index ?? 1) === 1,
                    'robots_follow' => (int)($row->robots_follow ?? 1) === 1,
                    'og_title' => (string)($row->og_title ?? ''),
                    'og_description' => (string)($row->og_description ?? ''),
                    'og_image' => (string)($row->og_image ?? ''),
                    'og_type' => (string)($row->og_type ?? ''),
                    'twitter_card' => (string)($row->twitter_card ?? ''),
                    'twitter_title' => (string)($row->twitter_title ?? ''),
                    'twitter_description' => (string)($row->twitter_description ?? ''),
                    'twitter_image' => (string)($row->twitter_image ?? ''),
                    'focus_keyphrase' => (string)($row->focus_keyphrase ?? ''),
                    'schema_type' => (string)($row->schema_type ?? ''),
                    'sitemap_priority' => (string)($row->sitemap_priority ?? ''),
                    'sitemap_changefreq' => (string)($row->sitemap_changefreq ?? ''),
                ];
            }, $rows);
        };

        return array_merge($mapRows($pages, 'page'), $mapRows($posts, 'post'));
    }

    public function renderCurrentHeadTags(): string
    {
        $payload = $this->getCurrentSeoPayload();
        if ($payload === []) {
            return '';
        }

        $lines = [];
        $metaDescription = trim((string)($payload['description'] ?? ''));
        if ($metaDescription !== '') {
            $lines[] = '<meta name="description" content="' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '">';
        }

        $robots = [];
        $robots[] = !empty($payload['robots_index']) ? 'index' : 'noindex';
        $robots[] = !empty($payload['robots_follow']) ? 'follow' : 'nofollow';
        $lines[] = '<meta name="robots" content="' . htmlspecialchars(implode(',', $robots), ENT_QUOTES, 'UTF-8') . '">';

        if (!empty($payload['canonical_url'])) {
            $lines[] = '<link rel="canonical" href="' . htmlspecialchars((string)$payload['canonical_url'], ENT_QUOTES, 'UTF-8') . '">';
        }

        $ogMap = [
            'og:title' => $payload['og_title'] ?? '',
            'og:description' => $payload['og_description'] ?? '',
            'og:image' => $payload['og_image'] ?? '',
            'og:type' => $payload['og_type'] ?? 'website',
            'og:url' => $payload['canonical_url'] ?? '',
            'og:site_name' => SITE_NAME,
        ];

        foreach ($ogMap as $property => $value) {
            if ((string)$value === '') {
                continue;
            }
            $lines[] = '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $twitterMap = [
            'twitter:card' => $payload['twitter_card'] ?? 'summary_large_image',
            'twitter:title' => $payload['twitter_title'] ?? '',
            'twitter:description' => $payload['twitter_description'] ?? '',
            'twitter:image' => $payload['twitter_image'] ?? '',
        ];

        foreach ($twitterMap as $name => $value) {
            if ((string)$value === '') {
                continue;
            }
            $lines[] = '<meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $schema = $this->renderSchemaForPayload($payload);
        if ($schema !== '') {
            $lines[] = $schema;
        }

        return implode("\n", $lines) . "\n";
    }
    
    /**
     * Generate Schema.org JSON-LD for Organization
     */
    public function generateOrganizationSchema(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'logo' => SITE_URL . '/assets/images/logo.png',
            'description' => $this->getSetting('meta_description', 'IT Expert Network Platform'),
            'email' => ADMIN_EMAIL ?? 'info@' . parse_url(SITE_URL, PHP_URL_HOST),
            'foundingDate' => '2026',
            'sameAs' => []
        ];
        
        // Add social profiles if configured
        $twitter = $this->getSetting('twitter_site');
        if ($twitter) {
            $schema['sameAs'][] = 'https://twitter.com/' . ltrim($twitter, '@');
        }
        
        return '<script type="application/ld+json">' . "\n" . 
               json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
               "\n</script>";
    }
    
    /**
     * Generate Schema.org JSON-LD for WebSite
     */
    public function generateWebSiteSchema(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => SITE_URL . '/search?q={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        return '<script type="application/ld+json">' . "\n" . 
               json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
               "\n</script>";
    }
    
    /**
     * Generate Schema.org JSON-LD for WebPage
     */
    public function generateWebPageSchema(string $title, string $description, string $url): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'inLanguage' => 'de-DE',
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => SITE_NAME,
                'url' => SITE_URL
            ]
        ];
        
        return '<script type="application/ld+json">' . "\n" . 
               json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
               "\n</script>";
    }
    
    /**
     * Generate XML Sitemap
     */
    public function generateSitemap(): string
    {
        $settings = $this->getSitemapSettings();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $xml .= $this->sitemapUrl(SITE_URL, date('Y-m-d'), '1.0', 'daily');
        
        // Pages
        try {
            $stmt = $this->db->query("
                SELECT slug, updated_at 
                FROM {$this->db->getPrefix()}pages 
                WHERE status = 'published'
                ORDER BY updated_at DESC
            ");
            
            $pages = $stmt->fetchAll(\PDO::FETCH_OBJ);
            
            foreach ($pages as $page) {
                $url = SITE_URL . '/' . $page->slug;
                $lastmod = date('Y-m-d', strtotime($page->updated_at));
                $seoMeta = $this->getContentMeta('page', (int)($page->id ?? 0));
                $priority = $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['pages_priority'];
                $changefreq = $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['pages_changefreq'];
                $xml .= $this->sitemapUrl($url, $lastmod, $priority, $changefreq);
            }
        } catch (\Exception $e) {
            error_log('SEOService::generateSitemap() Error: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->query(
                "SELECT id, slug, updated_at FROM {$this->prefix}posts WHERE status = 'published' ORDER BY updated_at DESC"
            );

            $posts = $stmt->fetchAll(\PDO::FETCH_OBJ);

            foreach ($posts as $post) {
                $url = SITE_URL . '/blog/' . $post->slug;
                $lastmod = date('Y-m-d', strtotime($post->updated_at));
                $seoMeta = $this->getContentMeta('post', (int)($post->id ?? 0));
                $priority = $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['posts_priority'];
                $changefreq = $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['posts_changefreq'];
                $xml .= $this->sitemapUrl($url, $lastmod, $priority, $changefreq);
            }
        } catch (\Exception $e) {
            error_log('SEOService::generateSitemap() Post Error: ' . $e->getMessage());
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Generate sitemap URL entry
     */
    private function sitemapUrl(string $loc, string $lastmod, string $priority, string $changefreq): string
    {
        return sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <priority>%s</priority>\n    <changefreq>%s</changefreq>\n  </url>\n",
            htmlspecialchars($loc),
            $lastmod,
            $priority,
            $changefreq
        );
    }
    
    /**
     * Generate robots.txt
     */
    public function generateRobotsTxt(): string
    {
        // Check for custom robots.txt content
        $customContent = $this->getSetting('robots_txt_content');
        
        if (!empty($customContent)) {
            return $customContent;
        }

        $txt = "# robots.txt for " . SITE_NAME . "\n";
        $txt .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        $txt .= "User-agent: *\n";
        $txt .= "Allow: /\n";
        $txt .= "Disallow: /admin/\n";
        $txt .= "Disallow: /core/\n";
        $txt .= "Disallow: /cache/\n";
        $txt .= "Disallow: /logs/\n";
        $txt .= "Disallow: /backups/\n";
        $txt .= "\n";
        
        // Sitemap
        $txt .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";
        
        return $txt;
    }
    
    /**
     * Save sitemap to file
     */
    public function saveSitemap(): bool
    {
        try {
            $xml = $this->generateSitemap();
            $filepath = ABSPATH . 'sitemap.xml';

            $saved = file_put_contents($filepath, $xml) !== false;
            if ($saved) {
                $this->pingSearchEngines();
            }

            return $saved;
        } catch (\Exception $e) {
            error_log('SEOService::saveSitemap() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save robots.txt to file
     */
    public function saveRobotsTxt(): bool
    {
        try {
            $txt = $this->generateRobotsTxt();
            $filepath = ABSPATH . 'robots.txt';
            
            return file_put_contents($filepath, $txt) !== false;
        } catch (\Exception $e) {
            error_log('SEOService::saveRobotsTxt() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Custom Header Code
     */
    public function getCustomHeaderCode(): string
    {
        return $this->getSetting('custom_header_code', '');
    }

    /**
     * Get all Analytics Head Code (Matomo, GA4, GTM, FB Pixel, Custom)
     * Call this inside <head> of your layout/theme.
     */
    public function getAnalyticsHeadCode(): string
    {
        // Check if current user is admin and exclusion is enabled
        if ($this->getAnalyticsSetting('analytics_exclude_admins') === '1') {
            // Simple check via session (adjust to your auth system if needed)
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                return '';
            }
        }

        $respectDnt   = $this->getAnalyticsSetting('analytics_respect_dnt') === '1';
        $anonymizeIp  = $this->getAnalyticsSetting('analytics_anonymize_ip') === '1';

        $output = '';

        // ── Matomo ────────────────────────────────────────────────────────────
        if ($this->getAnalyticsSetting('analytics_matomo_enabled') === '1') {
            $customCode = trim($this->getAnalyticsSetting('analytics_matomo_code'));
            if ($customCode !== '') {
                $output .= "\n" . $customCode . "\n";
            } else {
                $mUrl    = rtrim($this->getAnalyticsSetting('analytics_matomo_url'), '/') . '/';
                $mSiteId = $this->getAnalyticsSetting('analytics_matomo_site_id') ?: '1';
                if ($mUrl !== '/') {
                    $dntLine = $respectDnt
                        ? "\n  if (navigator.doNotTrack == '1') { return; }" : '';
                    $anonLine = $anonymizeIp
                        ? "\n  _paq.push(['setDoNotTrack', true]);\n  _paq.push(['disableCookies']);" : '';
                    $output .= "\n<!-- Matomo Analytics -->\n<script>\n  var _paq = window._paq = window._paq || [];" . $dntLine . $anonLine . "\n  _paq.push(['trackPageView']);\n  _paq.push(['enableLinkTracking']);\n  (function() {\n    var u=\"{$mUrl}\";\n    _paq.push(['setTrackerUrl', u+'matomo.php']);\n    _paq.push(['setSiteId', '{$mSiteId}']);\n    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);\n  })();\n</script>\n<!-- End Matomo Code -->\n";
                }
            }
        }

        // ── Google Analytics 4 ────────────────────────────────────────────────
        if ($this->getAnalyticsSetting('analytics_ga4_enabled') === '1') {
            $ga4Id = trim($this->getAnalyticsSetting('analytics_ga4_id'));
            if ($ga4Id !== '') {
                $configOptions = $anonymizeIp
                    ? "{ 'anonymize_ip': true }" : "{}";
                $dntBlock = $respectDnt
                    ? "\n  if (navigator.doNotTrack === '1') { window['ga-disable-{$ga4Id}'] = true; }" : '';
                $output .= "\n<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga4Id}\"></script>\n<script>{$dntBlock}\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', '{$ga4Id}', {$configOptions});\n</script>\n";
            }
        }

        // ── Google Tag Manager ────────────────────────────────────────────────
        if ($this->getAnalyticsSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->getAnalyticsSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $dntBlock = $respectDnt
                    ? "\n  if (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});{$dntBlock}\nvar f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','{$gtmId}');</script>\n<!-- End Google Tag Manager -->\n";
            }
        }

        // ── Facebook / Meta Pixel ─────────────────────────────────────────────
        if ($this->getAnalyticsSetting('analytics_fb_pixel_enabled') === '1') {
            $pixelId = trim($this->getAnalyticsSetting('analytics_fb_pixel_id'));
            if ($pixelId !== '') {
                $dntBlock = $respectDnt
                    ? "\nif (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Meta Pixel Code -->\n<script>{$dntBlock}\n!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window,document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '{$pixelId}');\nfbq('track', 'PageView');\n</script>\n<noscript><img height=\"1\" width=\"1\" style=\"display:none\"\nsrc=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"/></noscript>\n<!-- End Meta Pixel Code -->\n";
            }
        }

        // ── Custom Head Code ──────────────────────────────────────────────────
        $customHead = trim($this->getAnalyticsSetting('analytics_custom_head'));
        if ($customHead !== '') {
            $output .= "\n<!-- Custom Analytics Head Code -->\n" . $customHead . "\n";
        }

        return $output;
    }

    /**
     * Get Analytics Body Code (GTM noscript + Custom Body Code).
     * Call this directly after the opening <body> tag.
     */
    public function getAnalyticsBodyCode(): string
    {
        if ($this->getAnalyticsSetting('analytics_exclude_admins') === '1') {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                return '';
            }
        }

        $output = '';

        // GTM noscript
        if ($this->getAnalyticsSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->getAnalyticsSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $output .= "\n<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\"\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->\n";
            }
        }

        // Custom Body Code
        $customBody = trim($this->getAnalyticsSetting('analytics_custom_body'));
        if ($customBody !== '') {
            $output .= "\n<!-- Custom Analytics Body Code -->\n" . $customBody . "\n";
        }

        return $output;
    }

    /**
     * Helper: read analytics-prefixed setting (stored as seo_analytics_*)
     */
    private function getAnalyticsSetting(string $key, string $default = ''): string
    {
        try {
            $r = $this->db->fetchOne(
                "SELECT option_value FROM {$this->db->getPrefix()}settings WHERE option_name = ?",
                ['seo_' . $key]
            );
            return $r ? (string)$r['option_value'] : $default;
        } catch (\Exception) {
            return $default;
        }
    }

    /**
     * Get SEO setting
     */
    private function getSetting(string $key, string $default = ''): string
    {
        try {
            $stmt = $this->db->prepare("
                SELECT option_value 
                FROM {$this->db->getPrefix()}settings 
                WHERE option_name = ?
            ");
            
            $stmt->execute(['seo_' . $key]);
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            return $result ? (string)$result->option_value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    // ─── Public Getters für Frontend-Integration ────────────────────────────

    /**
     * Get SEO homepage title (from admin SEO settings)
     */
    public function getHomepageTitle(string $default = ''): string
    {
        return $this->getSetting('homepage_title', $default);
    }

    /**
     * Get SEO homepage meta description (from admin SEO settings)
     */
    public function getHomepageDescription(string $default = ''): string
    {
        return $this->getSetting('homepage_description', $default);
    }

    /**
     * Get global meta description (fallback: homepage_description)
     */
    public function getMetaDescription(string $default = ''): string
    {
        $desc = $this->getSetting('meta_description', '');
        if ($desc === '') {
            $desc = $this->getSetting('homepage_description', '');
        }
        return $desc !== '' ? $desc : $default;
    }

    public function getSitemapSettings(): array
    {
        return [
            'pages_priority'   => $this->getSetting('sitemap_pages_priority', '0.8'),
            'pages_changefreq' => $this->getSetting('sitemap_pages_changefreq', 'weekly'),
            'posts_priority'   => $this->getSetting('sitemap_posts_priority', '0.6'),
            'posts_changefreq' => $this->getSetting('sitemap_posts_changefreq', 'monthly'),
            'ping_google'      => $this->getSetting('sitemap_ping_google', '0') === '1',
            'ping_bing'        => $this->getSetting('sitemap_ping_bing', '0') === '1',
        ];
    }

    private function pingSearchEngines(): void
    {
        $settings = $this->getSitemapSettings();
        $sitemapUrl = SITE_URL . '/sitemap.xml';
        $targets = [];

        if ($settings['ping_google']) {
            $targets[] = 'https://www.google.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        }
        if ($settings['ping_bing']) {
            $targets[] = 'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        }

        foreach ($targets as $target) {
            try {
                @file_get_contents($target);
            } catch (\Throwable $e) {
                error_log('SEOService::pingSearchEngines() Warning: ' . $e->getMessage());
            }
        }
    }

    private function getCurrentSeoPayload(): array
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? strtok((string)$_SERVER['REQUEST_URI'], '?') : '/';
        $canonicalUrl = SITE_URL . ($uri === '/' ? '/' : $uri);

        $pageData = $GLOBALS['page'] ?? null;
        $postData = $GLOBALS['post'] ?? null;
        $content = null;
        $contentType = 'page';

        if (is_object($postData) || is_array($postData)) {
            $content = $postData;
            $contentType = 'post';
        } elseif (is_object($pageData) || is_array($pageData)) {
            $content = $pageData;
            $contentType = 'page';
        }

        if ($content === null) {
            return [
                'description' => $this->getMetaDescription(''),
                'canonical_url' => $canonicalUrl,
                'robots_index' => true,
                'robots_follow' => true,
                'og_title' => SITE_NAME,
                'og_description' => $this->getMetaDescription(''),
                'og_image' => '',
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_title' => SITE_NAME,
                'twitter_description' => $this->getMetaDescription(''),
                'twitter_image' => '',
                'schema_type' => 'WebPage',
                'title' => SITE_NAME,
                'url' => $canonicalUrl,
            ];
        }

        $id = (int)($this->readField($content, 'id') ?? 0);
        $title = trim((string)($this->readField($content, 'meta_title') ?: $this->readField($content, 'title') ?: SITE_NAME));
        $description = trim((string)($this->readField($content, 'meta_description') ?: $this->readField($content, 'excerpt') ?: $this->getMetaDescription('')));
        $featuredImage = trim((string)($this->readField($content, 'featured_image') ?? ''));
        $meta = $this->getContentMeta($contentType, $id);

        return [
            'title' => $title,
            'description' => $description,
            'canonical_url' => $meta['canonical_url'] !== '' ? $meta['canonical_url'] : $canonicalUrl,
            'robots_index' => $meta['robots_index'],
            'robots_follow' => $meta['robots_follow'],
            'og_title' => $meta['og_title'] !== '' ? $meta['og_title'] : $title,
            'og_description' => $meta['og_description'] !== '' ? $meta['og_description'] : $description,
            'og_image' => $meta['og_image'] !== '' ? $meta['og_image'] : $featuredImage,
            'og_type' => $meta['og_type'] !== '' ? $meta['og_type'] : ($contentType === 'post' ? 'article' : 'website'),
            'twitter_card' => $meta['twitter_card'] !== '' ? $meta['twitter_card'] : 'summary_large_image',
            'twitter_title' => $meta['twitter_title'] !== '' ? $meta['twitter_title'] : $title,
            'twitter_description' => $meta['twitter_description'] !== '' ? $meta['twitter_description'] : $description,
            'twitter_image' => $meta['twitter_image'] !== '' ? $meta['twitter_image'] : ($meta['og_image'] !== '' ? $meta['og_image'] : $featuredImage),
            'schema_type' => $meta['schema_type'] !== '' ? $meta['schema_type'] : ($contentType === 'post' ? 'Article' : 'WebPage'),
            'url' => $canonicalUrl,
            'content_type' => $contentType,
        ];
    }

    private function renderSchemaForPayload(array $payload): string
    {
        $schemaType = (string)($payload['schema_type'] ?? 'WebPage');
        if ($schemaType === '') {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'headline' => $payload['title'] ?? SITE_NAME,
            'name' => $payload['title'] ?? SITE_NAME,
            'description' => $payload['description'] ?? '',
            'url' => $payload['canonical_url'] ?? ($payload['url'] ?? SITE_URL),
        ];

        if (!empty($payload['og_image'])) {
            $schema['image'] = [$payload['og_image']];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }

    private function readField(object|array $source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        return $source->{$key} ?? null;
    }

    private function getDefaultMeta(): array
    {
        return [
            'canonical_url' => '',
            'robots_index' => true,
            'robots_follow' => true,
            'og_title' => '',
            'og_description' => '',
            'og_image' => '',
            'og_type' => 'article',
            'twitter_card' => 'summary_large_image',
            'twitter_title' => '',
            'twitter_description' => '',
            'twitter_image' => '',
            'focus_keyphrase' => '',
            'schema_type' => 'WebPage',
            'sitemap_priority' => '',
            'sitemap_changefreq' => '',
            'hreflang_group' => '',
        ];
    }

    private function sanitizeOptionalUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) ? $value : '';
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function sanitizeLongText(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function sanitizePriority(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $float = (float)$value;
        if ($float < 0.0 || $float > 1.0) {
            return '';
        }

        return number_format($float, 1, '.', '');
    }

    private function sanitizeChangefreq(string $value): string
    {
        $allowed = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        return in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * Get site title format (e.g. "%title% | %sitename%")
     */
    public function getSiteTitleFormat(): string
    {
        return $this->getSetting('site_title_format', '%title% | %sitename%');
    }

    /**
     * Get title separator
     */
    public function getTitleSeparator(): string
    {
        return $this->getSetting('title_separator', '|');
    }
}
