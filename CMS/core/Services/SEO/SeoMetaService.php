<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Database;
use CMS\VendorRegistry;
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\Thing;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

final class SeoMetaService
{
    public function __construct(
        private readonly Database $db,
        private readonly string $prefix
    ) {
        $this->ensureSeoMetaTable();
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
            'canonical_url' => (string) ($row->canonical_url ?? ''),
            'robots_index' => (int) ($row->robots_index ?? 1) === 1,
            'robots_follow' => (int) ($row->robots_follow ?? 1) === 1,
            'og_title' => (string) ($row->og_title ?? ''),
            'og_description' => (string) ($row->og_description ?? ''),
            'og_image' => (string) ($row->og_image ?? ''),
            'og_type' => (string) ($row->og_type ?? ''),
            'twitter_card' => (string) ($row->twitter_card ?? ''),
            'twitter_title' => (string) ($row->twitter_title ?? ''),
            'twitter_description' => (string) ($row->twitter_description ?? ''),
            'twitter_image' => (string) ($row->twitter_image ?? ''),
            'focus_keyphrase' => (string) ($row->focus_keyphrase ?? ''),
            'schema_type' => (string) ($row->schema_type ?? ''),
            'sitemap_priority' => $row->sitemap_priority !== null ? (string) $row->sitemap_priority : '',
            'sitemap_changefreq' => (string) ($row->sitemap_changefreq ?? ''),
            'hreflang_group' => (string) ($row->hreflang_group ?? ''),
        ];
    }

    public function saveContentMeta(string $contentType, int $contentId, array $data): void
    {
        if ($contentId <= 0) {
            return;
        }

        $payload = [
            'content_type' => $contentType,
            'content_id' => $contentId,
            'canonical_url' => $this->sanitizeOptionalUrl((string) ($data['canonical_url'] ?? '')),
            'robots_index' => array_key_exists('robots_index', $data) ? (!empty($data['robots_index']) ? 1 : 0) : 1,
            'robots_follow' => array_key_exists('robots_follow', $data) ? (!empty($data['robots_follow']) ? 1 : 0) : 1,
            'og_title' => $this->sanitizeText((string) ($data['og_title'] ?? ''), 255),
            'og_description' => $this->sanitizeLongText((string) ($data['og_description'] ?? '')),
            'og_image' => $this->sanitizeOptionalUrl((string) ($data['og_image'] ?? '')),
            'og_type' => $this->sanitizeText((string) ($data['og_type'] ?? 'article'), 50),
            'twitter_card' => $this->sanitizeText((string) ($data['twitter_card'] ?? 'summary_large_image'), 50),
            'twitter_title' => $this->sanitizeText((string) ($data['twitter_title'] ?? ''), 255),
            'twitter_description' => $this->sanitizeLongText((string) ($data['twitter_description'] ?? '')),
            'twitter_image' => $this->sanitizeOptionalUrl((string) ($data['twitter_image'] ?? '')),
            'focus_keyphrase' => $this->sanitizeText((string) ($data['focus_keyphrase'] ?? ''), 255),
            'schema_type' => $this->sanitizeText((string) ($data['schema_type'] ?? 'WebPage'), 100),
            'sitemap_priority' => $this->sanitizePriority((string) ($data['sitemap_priority'] ?? '')),
            'sitemap_changefreq' => $this->sanitizeChangefreq((string) ($data['sitemap_changefreq'] ?? '')),
            'hreflang_group' => $this->sanitizeText((string) ($data['hreflang_group'] ?? ''), 120),
        ];

        $existing = $this->db->get_row(
            "SELECT id FROM {$this->prefix}seo_meta WHERE content_type = ? AND content_id = ? LIMIT 1",
            [$contentType, $contentId]
        );

        if ($existing) {
            $this->db->update('seo_meta', $payload, ['id' => (int) $existing->id]);
            return;
        }

        $this->db->insert('seo_meta', $payload);
    }

    public function renderCurrentHeadTags(): string
    {
        $payload = $this->getCurrentSeoPayload();
        if ($payload === []) {
            return '';
        }

        $lines = [];
        $metaDescription = trim((string) ($payload['description'] ?? ''));
        if ($metaDescription !== '') {
            $lines[] = '<meta name="description" content="' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '">';
        }

        $robots = [];
        $robots[] = !empty($payload['robots_index']) ? 'index' : 'noindex';
        $robots[] = !empty($payload['robots_follow']) ? 'follow' : 'nofollow';
        $lines[] = '<meta name="robots" content="' . htmlspecialchars(implode(',', $robots), ENT_QUOTES, 'UTF-8') . '">';

        if (!empty($payload['canonical_url'])) {
            $lines[] = '<link rel="canonical" href="' . htmlspecialchars((string) $payload['canonical_url'], ENT_QUOTES, 'UTF-8') . '">';
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
            if ((string) $value === '') {
                continue;
            }
            $lines[] = '<meta property="' . htmlspecialchars($property, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $twitterMap = [
            'twitter:card' => $payload['twitter_card'] ?? 'summary_large_image',
            'twitter:title' => $payload['twitter_title'] ?? '',
            'twitter:description' => $payload['twitter_description'] ?? '',
            'twitter:image' => $payload['twitter_image'] ?? '',
        ];

        foreach ($twitterMap as $name => $value) {
            if ((string) $value === '') {
                continue;
            }
            $lines[] = '<meta name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" content="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
        }

        $schema = $this->renderSchemaForPayload($payload);
        if ($schema !== '') {
            $lines[] = $schema;
        }

        return implode("\n", $lines) . "\n";
    }

    public function generateOrganizationSchema(): string
    {
        return $this->renderSchemaGraph([$this->buildOrganizationThing()]);
    }

    public function generateWebSiteSchema(): string
    {
        $schema = new Thing(
            type: 'WebSite',
            props: [
                'name' => SITE_NAME,
                'url' => SITE_URL,
                'potentialAction' => new Thing(
                    type: 'SearchAction',
                    props: [
                        'target' => SITE_URL . '/search?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ]
                ),
            ]
        );

        return $this->renderSchemaGraph([$schema]);
    }

    public function generateWebPageSchema(string $title, string $description, string $url): string
    {
        return $this->renderSchemaGraph([
            $this->buildWebPageThing([
                'title' => $title,
                'description' => $description,
                'canonical_url' => $url,
                'url' => $url,
            ]),
        ]);
    }

    public function getCustomHeaderCode(): string
    {
        return $this->getSetting('custom_header_code', '');
    }

    public function getAnalyticsHeadCode(): string
    {
        if ($this->getAnalyticsSetting('analytics_exclude_admins') === '1') {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                return '';
            }
        }

        $respectDnt = $this->getAnalyticsSetting('analytics_respect_dnt') === '1';
        $anonymizeIp = $this->getAnalyticsSetting('analytics_anonymize_ip') === '1';
        $output = '';

        if ($this->getAnalyticsSetting('analytics_matomo_enabled') === '1') {
            $customCode = trim($this->getAnalyticsSetting('analytics_matomo_code'));
            if ($customCode !== '') {
                $output .= "\n" . $customCode . "\n";
            } else {
                $mUrl = rtrim($this->getAnalyticsSetting('analytics_matomo_url'), '/') . '/';
                $mSiteId = $this->getAnalyticsSetting('analytics_matomo_site_id') ?: '1';
                if ($mUrl !== '/') {
                    $dntLine = $respectDnt ? "\n  if (navigator.doNotTrack == '1') { return; }" : '';
                    $anonLine = $anonymizeIp ? "\n  _paq.push(['setDoNotTrack', true]);\n  _paq.push(['disableCookies']);" : '';
                    $output .= "\n<!-- Matomo Analytics -->\n<script>\n  var _paq = window._paq = window._paq || [];" . $dntLine . $anonLine . "\n  _paq.push(['trackPageView']);\n  _paq.push(['enableLinkTracking']);\n  (function() {\n    var u=\"{$mUrl}\";\n    _paq.push(['setTrackerUrl', u+'matomo.php']);\n    _paq.push(['setSiteId', '{$mSiteId}']);\n    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n    g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);\n  })();\n</script>\n<!-- End Matomo Code -->\n";
                }
            }
        }

        if ($this->getAnalyticsSetting('analytics_ga4_enabled') === '1') {
            $ga4Id = trim($this->getAnalyticsSetting('analytics_ga4_id'));
            if ($ga4Id !== '') {
                $configOptions = $anonymizeIp ? "{ 'anonymize_ip': true }" : "{}";
                $dntBlock = $respectDnt ? "\n  if (navigator.doNotTrack === '1') { window['ga-disable-{$ga4Id}'] = true; }" : '';
                $output .= "\n<!-- Google Analytics 4 -->\n<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga4Id}\"></script>\n<script>{$dntBlock}\n  window.dataLayer = window.dataLayer || [];\n  function gtag(){dataLayer.push(arguments);}\n  gtag('js', new Date());\n  gtag('config', '{$ga4Id}', {$configOptions});\n</script>\n";
            }
        }

        if ($this->getAnalyticsSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->getAnalyticsSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $dntBlock = $respectDnt ? "\n  if (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});{$dntBlock}\nvar f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','{$gtmId}');</script>\n<!-- End Google Tag Manager -->\n";
            }
        }

        if ($this->getAnalyticsSetting('analytics_fb_pixel_enabled') === '1') {
            $pixelId = trim($this->getAnalyticsSetting('analytics_fb_pixel_id'));
            if ($pixelId !== '') {
                $dntBlock = $respectDnt ? "\nif (navigator.doNotTrack === '1') { return; }" : '';
                $output .= "\n<!-- Meta Pixel Code -->\n<script>{$dntBlock}\n!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?\nn.callMethod.apply(n,arguments):n.queue.push(arguments)};\nif(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\nn.queue=[];t=b.createElement(e);t.async=!0;\nt.src=v;s=b.getElementsByTagName(e)[0];\ns.parentNode.insertBefore(t,s)}(window,document,'script',\n'https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '{$pixelId}');\nfbq('track', 'PageView');\n</script>\n<noscript><img height=\"1\" width=\"1\" style=\"display:none\"\nsrc=\"https://www.facebook.com/tr?id={$pixelId}&ev=PageView&noscript=1\"/></noscript>\n<!-- End Meta Pixel Code -->\n";
            }
        }

        $customHead = trim($this->getAnalyticsSetting('analytics_custom_head'));
        if ($customHead !== '') {
            $output .= "\n<!-- Custom Analytics Head Code -->\n" . $customHead . "\n";
        }

        return $output;
    }

    public function getAnalyticsBodyCode(): string
    {
        if ($this->getAnalyticsSetting('analytics_exclude_admins') === '1') {
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                return '';
            }
        }

        $output = '';

        if ($this->getAnalyticsSetting('analytics_gtm_enabled') === '1') {
            $gtmId = trim($this->getAnalyticsSetting('analytics_gtm_id'));
            if ($gtmId !== '') {
                $output .= "\n<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id={$gtmId}\"\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->\n";
            }
        }

        $customBody = trim($this->getAnalyticsSetting('analytics_custom_body'));
        if ($customBody !== '') {
            $output .= "\n<!-- Custom Analytics Body Code -->\n" . $customBody . "\n";
        }

        return $output;
    }

    public function getHomepageTitle(string $default = ''): string
    {
        return $this->getSetting('homepage_title', $default);
    }

    public function getHomepageDescription(string $default = ''): string
    {
        return $this->getSetting('homepage_description', $default);
    }

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
            'pages_priority' => $this->getSetting('sitemap_pages_priority', '0.8'),
            'pages_changefreq' => $this->getSetting('sitemap_pages_changefreq', 'weekly'),
            'posts_priority' => $this->getSetting('sitemap_posts_priority', '0.6'),
            'posts_changefreq' => $this->getSetting('sitemap_posts_changefreq', 'monthly'),
            'ping_google' => $this->getSetting('sitemap_ping_google', '0') === '1',
            'ping_bing' => $this->getSetting('sitemap_ping_bing', '0') === '1',
            'news_publication_name' => $this->getSetting('sitemap_news_publication_name', defined('SITE_NAME') ? SITE_NAME : '365CMS'),
            'news_language' => $this->getSetting('sitemap_news_language', 'de'),
        ];
    }

    public function getSiteTitleFormat(): string
    {
        return $this->getSetting('site_title_format', '%title% | %sitename%');
    }

    public function getTitleSeparator(): string
    {
        return $this->getSetting('title_separator', '|');
    }

    public function getSetting(string $key, string $default = ''): string
    {
        try {
            $value = $this->db->get_var(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
                ['seo_' . $key]
            );

            return $value !== null ? (string) $value : $default;
        } catch (\Throwable) {
            return $default;
        }
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

    private function getAnalyticsSetting(string $key, string $default = ''): string
    {
        return $this->getSetting($key, $default);
    }

    private function getCurrentSeoPayload(): array
    {
        $analysis = \CMS\Services\SeoAnalysisService::getInstance();
        $uri = isset($_SERVER['REQUEST_URI']) ? strtok((string) $_SERVER['REQUEST_URI'], '?') : '/';
        $uri = $uri !== false ? $uri : '/';
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
                'updated_at' => date(DATE_W3C),
            ];
        }

        $id = (int) ($this->readField($content, 'id') ?? 0);
        $resolvedContext = [
            'title' => (string) ($this->readField($content, 'title') ?? SITE_NAME),
            'slug' => (string) ($this->readField($content, 'slug') ?? ''),
            'content' => (string) ($this->readField($content, 'content') ?? ''),
            'excerpt' => (string) ($this->readField($content, 'excerpt') ?? ''),
            'meta_title' => (string) ($this->readField($content, 'meta_title') ?? ''),
            'meta_description' => (string) ($this->readField($content, 'meta_description') ?? ''),
        ];

        $title = trim($analysis->resolveMetaTitle($resolvedContext));
        $description = trim($analysis->resolveMetaDescription($resolvedContext));
        $featuredImage = trim((string) ($this->readField($content, 'featured_image') ?? ''));
        $meta = $this->getContentMeta($contentType, $id);
        $updatedAt = (string) ($this->readField($content, 'updated_at') ?? $this->readField($content, 'created_at') ?? date(DATE_W3C));

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
            'updated_at' => $updatedAt,
        ];
    }

    private function renderSchemaForPayload(array $payload): string
    {
        $schemaType = $this->normalizeSchemaType((string) ($payload['schema_type'] ?? 'WebPage'));
        $things = [];

        if ($schemaType === 'BreadcrumbList') {
            $breadcrumb = $this->buildBreadcrumbThing(
                (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL),
                (string) ($payload['title'] ?? SITE_NAME)
            );
            if ($breadcrumb !== null) {
                $things[] = $breadcrumb;
            }
        } else {
            $primary = $this->buildPrimarySchemaThing($schemaType, $payload);
            if ($primary !== null) {
                $things[] = $primary;
            }

            if ($this->shouldIncludeBreadcrumbSchema()) {
                $breadcrumb = $this->buildBreadcrumbThing(
                    (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL),
                    (string) ($payload['title'] ?? SITE_NAME)
                );
                if ($breadcrumb !== null) {
                    $things[] = $breadcrumb;
                }
            }
        }

        if ($schemaType !== 'Organization' && $this->isOrganizationSchemaEnabled()) {
            $things[] = $this->buildOrganizationThing();
        }

        return $this->renderSchemaGraph($things);
    }

    private function buildPrimarySchemaThing(string $schemaType, array $payload): ?Thing
    {
        return match ($schemaType) {
            'Article', 'BlogPosting', 'NewsArticle' => $this->buildArticleThing($payload, $schemaType),
            'Organization' => $this->buildOrganizationThing(),
            default => $this->buildWebPageThing($payload, $schemaType),
        };
    }

    private function buildWebPageThing(array $payload, string $type = 'WebPage'): Thing
    {
        $props = [
            'url' => $payload['canonical_url'] ?? ($payload['url'] ?? SITE_URL),
            'name' => $payload['title'] ?? SITE_NAME,
            'description' => $payload['description'] ?? '',
            'inLanguage' => 'de-DE',
            'isPartOf' => new Thing(
                type: 'WebSite',
                props: [
                    'name' => SITE_NAME,
                    'url' => SITE_URL,
                ]
            ),
        ];

        if (!empty($payload['og_image'])) {
            $props['primaryImageOfPage'] = new Thing(
                type: 'ImageObject',
                props: [
                    'url' => (string) $payload['og_image'],
                ]
            );
        }

        return new Thing(type: $type, props: $this->filterEmptyProps($props));
    }

    private function buildArticleThing(array $payload, string $type = 'Article'): Thing
    {
        $url = (string) ($payload['canonical_url'] ?? $payload['url'] ?? SITE_URL);
        $props = [
            'headline' => $payload['title'] ?? SITE_NAME,
            'name' => $payload['title'] ?? SITE_NAME,
            'description' => $payload['description'] ?? '',
            'url' => $url,
            'dateModified' => $this->normalizeSchemaDate((string) ($payload['updated_at'] ?? date(DATE_W3C))),
            'mainEntityOfPage' => new Thing(
                type: 'WebPage',
                props: [
                    'url' => $url,
                    'name' => $payload['title'] ?? SITE_NAME,
                ]
            ),
            'isPartOf' => new Thing(
                type: 'WebSite',
                props: [
                    'name' => SITE_NAME,
                    'url' => SITE_URL,
                ]
            ),
            'publisher' => $this->buildOrganizationThing(),
        ];

        if (!empty($payload['og_image'])) {
            $props['image'] = [(string) $payload['og_image']];
        }

        return new Thing(type: $type, props: $this->filterEmptyProps($props));
    }

    private function buildOrganizationThing(): Thing
    {
        $name = $this->getSetting('schema_org_name', defined('SITE_NAME') ? SITE_NAME : '365CMS');
        $logo = $this->getSetting('schema_org_logo', SITE_URL . '/assets/images/logo.png');
        $twitter = $this->getSetting('twitter_site', '');
        $sameAs = [];

        if ($twitter !== '') {
            $sameAs[] = 'https://twitter.com/' . ltrim($twitter, '@');
        }

        $props = [
            'name' => $name !== '' ? $name : SITE_NAME,
            'url' => SITE_URL,
            'logo' => $logo,
            'description' => $this->getSetting('meta_description', '365CMS SEO Integration'),
            'email' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'info@' . (parse_url(SITE_URL, PHP_URL_HOST) ?: 'example.com'),
            'sameAs' => $sameAs !== [] ? $sameAs : null,
        ];

        return new Thing(type: 'Organization', props: $this->filterEmptyProps($props));
    }

    private function buildBreadcrumbThing(string $url, string $title = ''): ?Thing
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn(string $segment): bool => $segment !== ''));

        $items = [
            new Thing(
                type: 'ListItem',
                props: [
                    'position' => 1,
                    'name' => SITE_NAME,
                    'item' => SITE_URL . '/',
                ]
            ),
        ];

        $position = 2;
        $currentPath = '';
        foreach ($segments as $index => $segment) {
            $currentPath .= '/' . $segment;
            $name = $index === array_key_last($segments) && $title !== ''
                ? $title
                : ucwords(str_replace(['-', '_'], ' ', $segment));

            $items[] = new Thing(
                type: 'ListItem',
                props: [
                    'position' => $position++,
                    'name' => $name,
                    'item' => SITE_URL . $currentPath,
                ]
            );
        }

        return new Thing(type: 'BreadcrumbList', props: ['itemListElement' => $items]);
    }

    /**
     * @param array<int, Thing> $things
     */
    private function renderSchemaGraph(array $things): string
    {
        if ($things === []) {
            return '';
        }

        return (string) new Schema(...$things);
    }

    private function normalizeSchemaType(string $schemaType): string
    {
        $schemaType = trim($schemaType);
        if ($schemaType === '') {
            return 'WebPage';
        }

        return match ($schemaType) {
            'BlogPosting', 'NewsArticle', 'Article', 'WebPage', 'BreadcrumbList', 'Organization' => $schemaType,
            default => 'WebPage',
        };
    }

    private function shouldIncludeBreadcrumbSchema(): bool
    {
        return $this->getSetting('schema_breadcrumb_enabled', '1') === '1';
    }

    private function isOrganizationSchemaEnabled(): bool
    {
        return $this->getSetting('schema_organization_enabled', '1') === '1';
    }

    private function normalizeSchemaDate(string $value): string
    {
        $timestamp = strtotime($value);
        return $timestamp !== false ? date(DATE_W3C, $timestamp) : date(DATE_W3C);
    }

    /**
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function filterEmptyProps(array $props): array
    {
        return array_filter($props, static function (mixed $value): bool {
            if ($value === null) {
                return false;
            }

            if (is_string($value)) {
                return trim($value) !== '';
            }

            if (is_array($value)) {
                return $value !== [];
            }

            return true;
        });
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

        $float = (float) $value;
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
}
