<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Services\SeoAnalysisService;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoHeadRenderer
{
    public function __construct(
        private readonly SeoMetaRepository $repository,
        private readonly SeoSettingsStore $settings,
        private readonly SeoSchemaRenderer $schemaRenderer
    ) {
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

        $schema = $this->schemaRenderer->renderSchemaForPayload($payload);
        if ($schema !== '') {
            $lines[] = $schema;
        }

        return implode("\n", $lines) . "\n";
    }

    private function getCurrentSeoPayload(): array
    {
        $analysis = SeoAnalysisService::getInstance();
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
            $description = $this->settings->getMetaDescription('');
            return [
                'description' => $description,
                'canonical_url' => $canonicalUrl,
                'robots_index' => true,
                'robots_follow' => true,
                'og_title' => SITE_NAME,
                'og_description' => $description,
                'og_image' => '',
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'twitter_title' => SITE_NAME,
                'twitter_description' => $description,
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
        $meta = $this->repository->getContentMeta($contentType, $id);
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

    private function readField(object|array $source, string $key): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? null;
        }

        return $source->{$key} ?? null;
    }
}
