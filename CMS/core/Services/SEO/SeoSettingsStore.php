<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Contracts\DatabaseInterface;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoSettingsStore
{
    public function __construct(
        private readonly DatabaseInterface $db,
        private readonly string $prefix
    ) {
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

    public function getCustomHeaderCode(): string
    {
        return $this->getSetting('custom_header_code', '');
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
}
