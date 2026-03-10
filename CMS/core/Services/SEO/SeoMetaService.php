<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Contracts\DatabaseInterface;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoMetaService
{
    private readonly SeoSettingsStore $settings;
    private readonly SeoMetaRepository $repository;
    private readonly SeoSchemaRenderer $schemaRenderer;
    private readonly SeoAnalyticsRenderer $analyticsRenderer;
    private readonly SeoHeadRenderer $headRenderer;

    public function __construct(
        DatabaseInterface $db,
        string $prefix
    ) {
        $this->settings = new SeoSettingsStore($db, $prefix);
        $this->repository = new SeoMetaRepository($db, $prefix);
        $this->schemaRenderer = new SeoSchemaRenderer($this->settings);
        $this->analyticsRenderer = new SeoAnalyticsRenderer($this->settings);
        $this->headRenderer = new SeoHeadRenderer($this->repository, $this->settings, $this->schemaRenderer);
    }

    public function getContentMeta(string $contentType, int $contentId): array
    {
        return $this->repository->getContentMeta($contentType, $contentId);
    }

    public function saveContentMeta(string $contentType, int $contentId, array $data): void
    {
        $this->repository->saveContentMeta($contentType, $contentId, $data);
    }

    public function renderCurrentHeadTags(): string
    {
        return $this->headRenderer->renderCurrentHeadTags();
    }

    public function generateOrganizationSchema(): string
    {
        return $this->schemaRenderer->generateOrganizationSchema();
    }

    public function generateWebSiteSchema(): string
    {
        return $this->schemaRenderer->generateWebSiteSchema();
    }

    public function generateWebPageSchema(string $title, string $description, string $url): string
    {
        return $this->schemaRenderer->generateWebPageSchema($title, $description, $url);
    }

    public function getCustomHeaderCode(): string
    {
        return $this->settings->getCustomHeaderCode();
    }

    public function getAnalyticsHeadCode(): string
    {
        return $this->analyticsRenderer->getAnalyticsHeadCode();
    }

    public function getAnalyticsBodyCode(): string
    {
        return $this->analyticsRenderer->getAnalyticsBodyCode();
    }

    public function getHomepageTitle(string $default = ''): string
    {
        return $this->settings->getHomepageTitle($default);
    }

    public function getHomepageDescription(string $default = ''): string
    {
        return $this->settings->getHomepageDescription($default);
    }

    public function getMetaDescription(string $default = ''): string
    {
        return $this->settings->getMetaDescription($default);
    }

    public function getSitemapSettings(): array
    {
        return $this->settings->getSitemapSettings();
    }

    public function getSiteTitleFormat(): string
    {
        return $this->settings->getSiteTitleFormat();
    }

    public function getTitleSeparator(): string
    {
        return $this->settings->getTitleSeparator();
    }

    public function getSetting(string $key, string $default = ''): string
    {
        return $this->settings->getSetting($key, $default);
    }
}
