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
                $xml .= $this->sitemapUrl($url, $lastmod, '0.8', 'weekly');
            }
        } catch (\Exception $e) {
            error_log('SEOService::generateSitemap() Error: ' . $e->getMessage());
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
        $customContent = $this->getSetting('robots_txt_custom');
        
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
            
            return file_put_contents($filepath, $xml) !== false;
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
}
