<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Contracts\DatabaseInterface;
use CMS\Contracts\LoggerInterface;
use CMS\Hooks;
use CMS\Http\Client as HttpClient;
use CMS\Services\PermalinkService;
use CMS\Services\SitemapService;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoSitemapService
{
    /** Obergrenze für Plugin-Einträge (eine Sitemap-Datei darf max. 50.000 URLs enthalten). */
    private const MAX_PLUGIN_ENTRIES = 10000;

    private ?string $lastSitemapError = null;

    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $httpClient,
        private readonly string $prefix,
        private readonly SeoMetaService $metaService
    ) {
    }

    public function generateSitemap(): string
    {
        return $this->renderSitemapFile('sitemap.xml');
    }

    public function generateRobotsTxt(): string
    {
        $customContent = $this->metaService->getSetting('robots_txt_content');
        if ($customContent !== '') {
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
        $txt .= "Disallow: /backups/\n\n";
        $txt .= "Sitemap: " . SITE_URL . "/sitemap.xml\n";

        return $txt;
    }

    public function saveSitemap(): bool
    {
        return $this->saveSitemapBundle();
    }

    public function generateImageSitemap(): string
    {
        return $this->renderSitemapFile('images.xml');
    }

    public function generateNewsSitemap(): string
    {
        return $this->renderSitemapFile('news.xml');
    }

    public function saveImageSitemap(): bool
    {
        return $this->saveSitemapBundle();
    }

    public function saveNewsSitemap(): bool
    {
        return $this->saveSitemapBundle();
    }

    public function saveSitemapBundle(): bool
    {
        $this->lastSitemapError = null;

        try {
            $service = $this->buildSitemapService(ABSPATH);
            $this->registerSitemapContent($service);
            $service->generate();
            $this->pingSearchEngines();

            return true;
        } catch (\Throwable $e) {
            $this->lastSitemapError = $e->getMessage();
            $this->logger->error('SEOService::saveSitemapBundle() fehlgeschlagen.', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function saveRobotsTxt(): bool
    {
        try {
            return file_put_contents(ABSPATH . 'robots.txt', $this->generateRobotsTxt()) !== false;
        } catch (\Throwable $e) {
            $this->logger->error('SEOService::saveRobotsTxt() fehlgeschlagen.', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function getLastSitemapError(): ?string
    {
        return $this->lastSitemapError;
    }

    private function pingSearchEngines(): void
    {
        $settings = $this->metaService->getSitemapSettings();
        $sitemapUrl = SITE_URL . '/sitemap.xml';
        $targets = [];

        if (!empty($settings['ping_google'])) {
            $targets[] = 'https://www.google.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        }
        if (!empty($settings['ping_bing'])) {
            $targets[] = 'https://www.bing.com/ping?sitemap=' . rawurlencode($sitemapUrl);
        }

        foreach ($targets as $target) {
            try {
                $this->httpClient->get($target, [
                    'timeout' => 5,
                    'connectTimeout' => 3,
                    'userAgent' => '365CMS SEO',
                ]);
            } catch (\Throwable $e) {
                $this->logger->warning('SEO-Ping an Suchmaschine fehlgeschlagen.', [
                    'target' => $target,
                    'exception' => $e,
                ]);
            }
        }
    }

    private function renderSitemapFile(string $fileName): string
    {
        $tmpDir = $this->createTemporaryDirectory();

        try {
            $service = $this->buildSitemapService($tmpDir);
            $this->registerSitemapContent($service);
            $service->generate();

            $path = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($path)) {
                return $this->fallbackSitemapContent($fileName);
            }

            $content = file_get_contents($path);
            if ($content === false) {
                throw new \RuntimeException('Sitemap-Datei konnte nicht gelesen werden: ' . $path);
            }

            return $content;
        } catch (\Throwable $e) {
            $this->logger->error('SEOService::renderSitemapFile() fehlgeschlagen.', [
                'file' => $fileName,
                'exception' => $e,
            ]);
            return $this->fallbackSitemapContent($fileName);
        } finally {
            $this->deleteDirectory($tmpDir);
        }
    }

    private function buildSitemapService(string $saveDir): SitemapService
    {
        return new SitemapService(SITE_URL, $saveDir);
    }

    private function registerSitemapContent(SitemapService $service): void
    {
        $service->generatePages($this->getPageSitemapEntries());
        $service->generatePosts($this->getPostSitemapEntries());
        $service->generateEnglishPages($this->getEnglishPageSitemapEntries());
        $service->generateEnglishPosts($this->getEnglishPostSitemapEntries());
        $service->generatePlugins($this->getPluginSitemapEntries());

        if ($this->metaService->getSetting('sitemap_image_enabled', '1') === '1') {
            $service->generateImages($this->getImageSitemapEntries());
        }

        if ($this->metaService->getSetting('sitemap_news_enabled', '0') === '1') {
            $settings = $this->metaService->getSitemapSettings();
            $service->generateNews(
                $this->getNewsSitemapEntries(),
                (string) ($settings['news_publication_name'] ?? SITE_NAME),
                (string) ($settings['news_language'] ?? 'de')
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPageSitemapEntries(): array
    {
        $settings = $this->metaService->getSitemapSettings();
        $entries = [[
            'url' => SITE_URL . '/',
            'lastmod' => date(DATE_W3C),
            'priority' => $settings['pages_priority'],
            'changefreq' => $settings['pages_changefreq'],
        ]];

        $rows = $this->db->get_results(
            "SELECT id, slug, title, updated_at
             FROM {$this->prefix}pages
             WHERE status = 'published'
               AND (CHAR_LENGTH(TRIM(COALESCE(title, ''))) > 0
                    OR CHAR_LENGTH(TRIM(COALESCE(content, ''))) > 0)
             ORDER BY title ASC"
        ) ?: [];

        foreach ($rows as $row) {
            $slug = trim((string) ($row->slug ?? ''));
            if ($slug === '') {
                continue;
            }

            $seoMeta = $this->metaService->getContentMeta('page', (int) ($row->id ?? 0));
            $entries[] = [
                'url' => $this->buildPathUrl($slug),
                'lastmod' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'priority' => $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['pages_priority'],
                'changefreq' => $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['pages_changefreq'],
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPostSitemapEntries(): array
    {
        $settings = $this->metaService->getSitemapSettings();
        $rows = $this->db->get_results(
            "SELECT id, slug, updated_at, published_at, created_at
             FROM {$this->prefix}posts
               WHERE " . \cms_post_publication_where() . "
                 AND (CHAR_LENGTH(TRIM(COALESCE(title, ''))) > 0
                      OR CHAR_LENGTH(TRIM(COALESCE(content, ''))) > 0
                      OR CHAR_LENGTH(TRIM(COALESCE(excerpt, ''))) > 0)
             ORDER BY COALESCE(published_at, created_at) DESC"
        ) ?: [];

        $entries = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row->slug ?? ''));
            if ($slug === '') {
                continue;
            }

            $seoMeta = $this->metaService->getContentMeta('post', (int) ($row->id ?? 0));
            $entries[] = [
                'url' => PermalinkService::getInstance()->buildPostUrlFromValues(
                    $slug,
                    (string) ($row->published_at ?? ''),
                    (string) ($row->created_at ?? '')
                ),
                'lastmod' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'priority' => $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['posts_priority'],
                'changefreq' => $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['posts_changefreq'],
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEnglishPageSitemapEntries(): array
    {
        $localization = \CMS\Services\ContentLocalizationService::getInstance();
        $settings = $this->metaService->getSitemapSettings();
        $entries = [[
            'url' => rtrim((string) SITE_URL, '/') . '/en/',
            'lastmod' => date(DATE_W3C),
            'priority' => $settings['pages_priority'],
            'changefreq' => $settings['pages_changefreq'],
        ]];

        $rows = $this->db->get_results(
            "SELECT id, slug, slug_en, title_en, content_en, updated_at
             FROM {$this->prefix}pages
             WHERE status = 'published'
               AND CHAR_LENGTH(TRIM(COALESCE(title_en, ''))) > 0
               AND CHAR_LENGTH(TRIM(COALESCE(content_en, ''))) > 0
             ORDER BY title_en ASC"
        ) ?: [];

        foreach ($rows as $row) {
            $page = (array) $row;
            $slug = $localization->resolveLocalizedSlug($page, 'en');
            if ($slug === '') {
                continue;
            }

            $seoMeta = $this->metaService->getContentMeta('page', (int) ($row->id ?? 0));
            $entries[] = [
                'url' => $localization->buildLocalizedPath('/' . ltrim($slug, '/'), 'en'),
                'lastmod' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'priority' => $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['pages_priority'],
                'changefreq' => $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['pages_changefreq'],
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEnglishPostSitemapEntries(): array
    {
        $settings = $this->metaService->getSitemapSettings();
        $rows = $this->db->get_results(
            "SELECT id, slug, slug_en, title_en, content_en, excerpt_en, updated_at, published_at, created_at
             FROM {$this->prefix}posts
             WHERE " . \cms_post_publication_where() . "
               AND CHAR_LENGTH(TRIM(COALESCE(title_en, ''))) > 0
               AND CHAR_LENGTH(TRIM(COALESCE(content_en, ''))) > 0
             ORDER BY COALESCE(published_at, created_at) DESC"
        ) ?: [];

        $entries = [];
        foreach ($rows as $row) {
            $postData = \CMS\Services\ContentLocalizationService::getInstance()->localizePost((array) $row, 'en');
            if (trim((string) ($postData['slug'] ?? '')) === '') {
                continue;
            }

            $seoMeta = $this->metaService->getContentMeta('post', (int) ($row->id ?? 0));
            $entries[] = [
                'url' => PermalinkService::getInstance()->buildPostPath($postData, 'en'),
                'lastmod' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'priority' => $seoMeta['sitemap_priority'] !== '' ? $seoMeta['sitemap_priority'] : $settings['posts_priority'],
                'changefreq' => $seoMeta['sitemap_changefreq'] !== '' ? $seoMeta['sitemap_changefreq'] : $settings['posts_changefreq'],
            ];
        }

        return $entries;
    }

    /**
     * Öffentliche Plugin-Seiten (z. B. Message Center, Tools, Matrizen) für `plugins.xml`.
     *
     * Plugins liefern über den Filter `cms_sitemap_entries` Einträge der Form
     * `['url' => 'pfad/relativ/zu/SITE_URL', 'lastmod' => '2026-09-29 12:00:00', 'changefreq' => 'daily', 'priority' => 0.6]`
     * oder absolute URLs der eigenen Domain. Fremde Hosts, Duplikate und ungültige Einträge werden verworfen.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getPluginSitemapEntries(): array
    {
        try {
            $entries = Hooks::applyFilters('cms_sitemap_entries', []);
        } catch (\Throwable $e) {
            $this->logger->warning('Plugin-Einträge für die Sitemap konnten nicht ermittelt werden.', [
                'exception' => $e,
            ]);
            return [];
        }

        if (!is_array($entries)) {
            return [];
        }

        $siteHost = strtolower((string) parse_url((string) SITE_URL, PHP_URL_HOST));
        $result = [];
        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $entry = ['url' => $entry];
            }
            if (!is_array($entry) || count($result) >= self::MAX_PLUGIN_ENTRIES) {
                continue;
            }

            $url = trim((string) ($entry['url'] ?? ''));
            if ($url === '' || preg_match('/[\s<>"]/', $url) === 1) {
                continue;
            }

            if (preg_match('#^https?://#i', $url) === 1) {
                if (strtolower((string) parse_url($url, PHP_URL_HOST)) !== $siteHost) {
                    continue;
                }
            } elseif (str_starts_with($url, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) === 1) {
                continue;
            } else {
                $url = $this->buildPathUrl($url);
            }

            if (isset($result[$url])) {
                continue;
            }

            $result[$url] = [
                'url' => $url,
                'lastmod' => $entry['lastmod'] ?? date(DATE_W3C),
                'priority' => $entry['priority'] ?? 0.6,
                'changefreq' => (string) ($entry['changefreq'] ?? 'weekly'),
            ];
        }

        return array_values($result);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getImageSitemapEntries(): array
    {
        $rows = [];

        $pages = $this->db->get_results(
            "SELECT p.id, p.slug, p.updated_at, p.title, p.featured_image, sm.og_image
             FROM {$this->prefix}pages p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'page' AND sm.content_id = p.id
             WHERE p.status = 'published'
             ORDER BY p.title ASC"
        ) ?: [];

        foreach ($pages as $page) {
            $image = trim((string) ($page->og_image ?? $page->featured_image ?? ''));
            if ($image === '') {
                continue;
            }

            $rows[] = [
                'url' => $this->buildPathUrl((string) ($page->slug ?? '')),
                'image' => $image,
                'title' => (string) ($page->title ?? ''),
                'lastmod' => (string) ($page->updated_at ?? date(DATE_W3C)),
                'priority' => 0.7,
                'changefreq' => 'monthly',
            ];
        }

        $posts = $this->db->get_results(
            "SELECT p.id, p.slug, p.updated_at, p.published_at, p.created_at, p.title, p.featured_image, sm.og_image
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'post' AND sm.content_id = p.id
               WHERE " . \cms_post_publication_where('p') . "
             ORDER BY COALESCE(p.published_at, p.created_at) DESC"
        ) ?: [];

        foreach ($posts as $post) {
            $image = trim((string) ($post->og_image ?? $post->featured_image ?? ''));
            if ($image === '') {
                continue;
            }

            $rows[] = [
                'url' => PermalinkService::getInstance()->buildPostUrlFromValues(
                    (string) ($post->slug ?? ''),
                    (string) ($post->published_at ?? ''),
                    (string) ($post->created_at ?? '')
                ),
                'image' => $image,
                'title' => (string) ($post->title ?? ''),
                'lastmod' => (string) ($post->updated_at ?? date(DATE_W3C)),
                'priority' => 0.7,
                'changefreq' => 'monthly',
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getNewsSitemapEntries(): array
    {
        $rows = $this->db->get_results(
            "SELECT slug, title, updated_at, published_at, created_at
             FROM {$this->prefix}posts
               WHERE " . \cms_post_publication_where() . "
             ORDER BY COALESCE(published_at, created_at) DESC
             LIMIT 100"
        ) ?: [];

        $entries = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row->slug ?? ''));
            $title = trim((string) ($row->title ?? ''));
            if ($slug === '' || $title === '') {
                continue;
            }

            $entries[] = [
                'url' => PermalinkService::getInstance()->buildPostUrlFromValues(
                    $slug,
                    (string) ($row->published_at ?? ''),
                    (string) ($row->created_at ?? '')
                ),
                'title' => $title,
                'publication_date' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'lastmod' => (string) ($row->updated_at ?? date(DATE_W3C)),
                'priority' => 0.9,
                'changefreq' => 'daily',
            ];
        }

        return $entries;
    }

    private function buildPathUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return SITE_URL . '/';
        }

        return SITE_URL . '/' . ltrim($path, '/');
    }

    private function createTemporaryDirectory(): string
    {
        try {
            $suffix = bin2hex(random_bytes(8));
        } catch (\Throwable) {
            $suffix = uniqid('seo', true);
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '365cms-seo-' . $suffix;
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Temporäres Sitemap-Verzeichnis konnte nicht erstellt werden.');
        }

        return $dir;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }

            if (!$this->deleteFile($path)) {
                return;
            }
        }

        $this->removeDirectory($dir);
    }

    private function fallbackSitemapContent(string $fileName): string
    {
        return match ($fileName) {
            'sitemap.xml' => '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>',
            'en-pages.xml', 'en-posts.xml' => '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>',
            'images.xml' => '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"></urlset>',
            'news.xml' => '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"></urlset>',
            default => '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>',
        };
    }

    private function deleteFile(string $path): bool
    {
        if (!is_file($path)) {
            return true;
        }

        if (!unlink($path)) {
            $this->logger->warning('SeoSitemapService: Temporäre Datei konnte nicht gelöscht werden.', [
                'path' => $path,
            ]);
            return false;
        }

        return true;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        if (!rmdir($path)) {
            $this->logger->warning('SeoSitemapService: Temporäres Verzeichnis konnte nicht gelöscht werden.', [
                'path' => $path,
            ]);
        }
    }
}
