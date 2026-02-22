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
}
