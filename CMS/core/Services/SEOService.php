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
use CMS\Http\Client as HttpClient;
use CMS\Logger;
use CMS\VendorRegistry;
use CMS\Services\SEO\SeoAuditService;
use CMS\Services\SEO\SeoMetaService;
use CMS\Services\SEO\SeoSitemapService;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

class SEOService
{
    private static ?self $instance = null;

    private readonly SeoMetaService $metaService;
    private readonly SeoSitemapService $sitemapService;
    private readonly SeoAuditService $auditService;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $db = Database::instance();
        $logger = Logger::instance()->withChannel('seo');
        $prefix = $db->getPrefix();

        $this->metaService = new SeoMetaService($db, $prefix);
        $this->sitemapService = new SeoSitemapService($db, $logger, HttpClient::getInstance(), $prefix, $this->metaService);
        $this->auditService = new SeoAuditService($db, $prefix);
    }

    public function getContentMeta(string $contentType, int $contentId): array
    {
        return $this->metaService->getContentMeta($contentType, $contentId);
    }

    public function saveContentMeta(string $contentType, int $contentId, array $data): void
    {
        $this->metaService->saveContentMeta($contentType, $contentId, $data);
    }

    public function getAuditRows(): array
    {
        return $this->auditService->getAuditRows();
    }

    public function renderCurrentHeadTags(): string
    {
        return $this->metaService->renderCurrentHeadTags();
    }

    /**
     * Generate Schema.org JSON-LD for Organization.
     */
    public function generateOrganizationSchema(): string
    {
        return $this->metaService->generateOrganizationSchema();
    }

    /**
     * Generate Schema.org JSON-LD for WebSite.
     */
    public function generateWebSiteSchema(): string
    {
        return $this->metaService->generateWebSiteSchema();
    }

    /**
     * Generate Schema.org JSON-LD for WebPage.
     */
    public function generateWebPageSchema(string $title, string $description, string $url): string
    {
        return $this->metaService->generateWebPageSchema($title, $description, $url);
    }

    /**
     * Generate XML Sitemap index.
     */
    public function generateSitemap(): string
    {
        return $this->sitemapService->generateSitemap();
    }

    /**
     * Generate robots.txt.
     */
    public function generateRobotsTxt(): string
    {
        return $this->sitemapService->generateRobotsTxt();
    }

    /**
     * Save sitemap bundle to disk.
     */
    public function saveSitemap(): bool
    {
        return $this->sitemapService->saveSitemap();
    }

    public function generateImageSitemap(): string
    {
        return $this->sitemapService->generateImageSitemap();
    }

    public function generateNewsSitemap(): string
    {
        return $this->sitemapService->generateNewsSitemap();
    }

    public function saveImageSitemap(): bool
    {
        return $this->sitemapService->saveImageSitemap();
    }

    public function saveNewsSitemap(): bool
    {
        return $this->sitemapService->saveNewsSitemap();
    }

    public function saveSitemapBundle(): bool
    {
        return $this->sitemapService->saveSitemapBundle();
    }

    /**
     * Save robots.txt to file.
     */
    public function saveRobotsTxt(): bool
    {
        return $this->sitemapService->saveRobotsTxt();
    }

    /**
     * Get Custom Header Code.
     */
    public function getCustomHeaderCode(): string
    {
        return $this->metaService->getCustomHeaderCode();
    }

    /**
     * Get all Analytics Head Code.
     */
    public function getAnalyticsHeadCode(): string
    {
        return $this->metaService->getAnalyticsHeadCode();
    }

    /**
     * Get Analytics Body Code.
     */
    public function getAnalyticsBodyCode(): string
    {
        return $this->metaService->getAnalyticsBodyCode();
    }

    /**
     * Get SEO homepage title.
     */
    public function getHomepageTitle(string $default = ''): string
    {
        return $this->metaService->getHomepageTitle($default);
    }

    /**
     * Get SEO homepage meta description.
     */
    public function getHomepageDescription(string $default = ''): string
    {
        return $this->metaService->getHomepageDescription($default);
    }

    /**
     * Get global meta description.
     */
    public function getMetaDescription(string $default = ''): string
    {
        return $this->metaService->getMetaDescription($default);
    }

    public function getSitemapSettings(): array
    {
        return $this->metaService->getSitemapSettings();
    }

    /**
     * Get site title format.
     */
    public function getSiteTitleFormat(): string
    {
        return $this->metaService->getSiteTitleFormat();
    }

    /**
     * Get title separator.
     */
    public function getTitleSeparator(): string
    {
        return $this->metaService->getTitleSeparator();
    }

    public function getLastSitemapError(): ?string
    {
        return $this->sitemapService->getLastSitemapError();
    }
}
