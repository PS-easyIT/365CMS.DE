<?php
/**
 * Sitemap-Service auf Basis von melbahja/seo.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\VendorRegistry;
use Melbahja\Seo\Sitemap;
use Melbahja\Seo\Sitemap\OutputMode;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

final class SitemapService
{
    private string $baseUrl;
    private string $saveDir;

    /** @var array<int, array<string, mixed>> */
    private array $pages = [];

    /** @var array<int, array<string, mixed>> */
    private array $posts = [];

    /** @var array<int, array<string, mixed>> */
    private array $images = [];

    /** @var array<int, array<string, mixed>> */
    private array $news = [];

    private string $newsPublicationName = '365CMS';
    private string $newsLanguage = 'de';
    private OutputMode $outputMode;

    public function __construct(string $baseUrl, string $saveDir)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->saveDir = rtrim($saveDir, DIRECTORY_SEPARATOR);
        $this->outputMode = $this->resolveOutputMode();
    }

    /**
     * Registriert statische Seiten für `pages.xml`.
     *
     * @param array<int, string|array<string, mixed>> $urls
     */
    public function generatePages(array $urls): void
    {
        $this->pages = $this->normalizeItems(
            $urls,
            fn(string|array $item): array => $this->normalizePageItem($item)
        );
    }

    /**
     * Registriert Beiträge für `posts.xml`.
     *
     * @param array<int, array<string, mixed>> $posts
     */
    public function generatePosts(array $posts): void
    {
        $this->posts = $this->normalizeItems(
            $posts,
            fn(array $item): array => $this->normalizePostItem($item)
        );
    }

    /**
     * Registriert Bild-URLs für `images.xml`.
     *
     * @param array<int, array<string, mixed>> $items
     */
    public function generateImages(array $items): void
    {
        $this->images = $this->normalizeItems(
            $items,
            fn(array $item): array => $this->normalizeImageItem($item)
        );
    }

    /**
     * Registriert News-Artikel für `news.xml`.
     *
     * @param array<int, array<string, mixed>> $articles
     */
    public function generateNews(array $articles, string $pubName = '365CMS', string $lang = 'de'): void
    {
        $this->newsPublicationName = trim($pubName) !== '' ? trim($pubName) : '365CMS';
        $this->newsLanguage = trim($lang) !== '' ? trim($lang) : 'de';
        $this->news = $this->normalizeItems(
            $articles,
            fn(array $item): array => $this->normalizeNewsItem($item)
        );
    }

    /**
     * Schreibt die registrierten Sitemap-Dateien und erzeugt automatisch `sitemap.xml`.
     *
     * @throws \RuntimeException
     */
    public function generate(): void
    {
        $this->ensureSaveDirectory();
        $this->deleteExistingTargets();

        $pages = $this->pages;
        $posts = $this->posts;
        $images = $this->images;
        $news = $this->news;
        $publicationName = $this->newsPublicationName;
        $language = $this->newsLanguage;

        if ($pages === [] && $posts === [] && $images === [] && $news === []) {
            $pages = [$this->normalizePageItem('/')];
        }

        $sitemap = new Sitemap(
            baseUrl: $this->baseUrl,
            saveDir: $this->saveDir,
            indexName: 'sitemap.xml',
            mode: $this->outputMode,
        );

        if ($pages !== []) {
            $sitemap->links('pages.xml', function ($map) use ($pages): void {
                foreach ($pages as $page) {
                    $map->loc($page['url']);
                    $this->applyCommonMapOptions($map, $page);
                }
            });
        }

        if ($posts !== []) {
            $sitemap->links('posts.xml', function ($map) use ($posts): void {
                foreach ($posts as $post) {
                    $map->loc($post['url']);
                    $this->applyCommonMapOptions($map, $post);
                }
            });
        }

        if ($images !== []) {
            $sitemap->links(['name' => 'images.xml', 'images' => true], function ($map) use ($images): void {
                foreach ($images as $item) {
                    $map->loc($item['url']);
                    $this->applyCommonMapOptions($map, $item);
                    $imageOptions = array_filter([
                        'title' => $item['title'] ?? null,
                        'caption' => $item['caption'] ?? null,
                        'license' => $item['license'] ?? null,
                        'geo_location' => $item['geo_location'] ?? null,
                    ], static fn(mixed $value): bool => is_string($value) && trim($value) !== '');
                    $map->image((string) $item['image'], $imageOptions);
                }
            });
        }

        if ($news !== []) {
            $sitemap->news('news.xml', function ($map) use ($news, $publicationName, $language): void {
                if (method_exists($map, 'setPublication')) {
                    $map->setPublication($publicationName, $language);
                }

                foreach ($news as $article) {
                    $map->loc($article['url']);
                    $this->applyCommonMapOptions($map, $article);
                    $map->news([
                        'name' => $publicationName,
                        'language' => $language,
                        'publication_date' => (string) $article['publication_date'],
                        'title' => (string) $article['title'],
                    ]);
                }
            });
        }

        $rendered = $sitemap->render();
        if ($rendered !== true) {
            throw new \RuntimeException('Sitemap-Index konnte nicht geschrieben werden.');
        }

        foreach ($this->expectedFiles($pages, $posts, $images, $news) as $file) {
            if (!is_file($this->saveDir . DIRECTORY_SEPARATOR . $file)) {
                throw new \RuntimeException('Erwartete Sitemap-Datei fehlt: ' . $file);
            }
        }
    }

    /**
     * @param string|array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizePageItem(string|array $item): array
    {
        if (is_string($item)) {
            $item = ['url' => $item];
        }

        return [
            'url' => $this->normalizeUrl((string) ($item['url'] ?? '')),
            'lastmod' => $this->normalizeDate($item['lastmod'] ?? $item['updated_at'] ?? date('c')),
            'priority' => $this->normalizePriority($item['priority'] ?? 0.8),
            'changefreq' => $this->normalizeChangeFreq((string) ($item['changefreq'] ?? 'weekly')),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizePostItem(array $item): array
    {
        return [
            'url' => $this->normalizeUrl((string) ($item['url'] ?? '')),
            'lastmod' => $this->normalizeDate($item['lastMod'] ?? $item['lastmod'] ?? $item['updated_at'] ?? date('c')),
            'priority' => $this->normalizePriority($item['priority'] ?? 0.8),
            'changefreq' => $this->normalizeChangeFreq((string) ($item['changefreq'] ?? 'weekly')),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeImageItem(array $item): array
    {
        $url = $this->normalizeUrl((string) ($item['url'] ?? ''));
        $image = $this->normalizeUrl((string) ($item['image'] ?? $item['image_url'] ?? ''));

        if ($image === '') {
            throw new \RuntimeException('Für Image-Sitemaps ist `image` bzw. `image_url` erforderlich.');
        }

        return [
            'url' => $url,
            'image' => $image,
            'lastmod' => $this->normalizeDate($item['lastmod'] ?? $item['updated_at'] ?? date('c')),
            'priority' => $this->normalizePriority($item['priority'] ?? 0.7),
            'changefreq' => $this->normalizeChangeFreq((string) ($item['changefreq'] ?? 'monthly')),
            'title' => trim((string) ($item['title'] ?? '')),
            'caption' => trim((string) ($item['caption'] ?? '')),
            'license' => trim((string) ($item['license'] ?? '')),
            'geo_location' => trim((string) ($item['geo_location'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function normalizeNewsItem(array $item): array
    {
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('Für News-Sitemaps ist `title` erforderlich.');
        }

        return [
            'url' => $this->normalizeUrl((string) ($item['url'] ?? '')),
            'title' => $title,
            'publication_date' => $this->normalizeDate($item['publication_date'] ?? $item['updated_at'] ?? date('c')),
            'lastmod' => $this->normalizeDate($item['lastmod'] ?? $item['updated_at'] ?? date('c')),
            'priority' => $this->normalizePriority($item['priority'] ?? 0.9),
            'changefreq' => $this->normalizeChangeFreq((string) ($item['changefreq'] ?? 'daily')),
        ];
    }

    /**
     * @param object $map
     * @param array<string, mixed> $item
     */
    private function applyCommonMapOptions(object $map, array $item): void
    {
        if (!empty($item['lastmod'])) {
            $map->lastMod((string) $item['lastmod']);
        }

        if (!empty($item['priority'])) {
            $map->priority((float) $item['priority']);
        }

        if (!empty($item['changefreq'])) {
            $map->changeFreq((string) $item['changefreq']);
        }
    }

    private function ensureSaveDirectory(): void
    {
        if (is_dir($this->saveDir)) {
            if (!is_writable($this->saveDir)) {
                throw new \RuntimeException('Sitemap-Zielverzeichnis ist nicht beschreibbar: ' . $this->saveDir);
            }
            return;
        }

        if (!mkdir($this->saveDir, 0755, true) && !is_dir($this->saveDir)) {
            throw new \RuntimeException('Sitemap-Zielverzeichnis konnte nicht erstellt werden: ' . $this->saveDir);
        }
    }

    private function deleteExistingTargets(): void
    {
        foreach (['sitemap.xml', 'pages.xml', 'posts.xml', 'images.xml', 'news.xml'] as $file) {
            $path = $this->saveDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($path) && !unlink($path)) {
                throw new \RuntimeException('Vorhandene Sitemap-Datei konnte nicht ersetzt werden: ' . $path);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $pages
     * @param array<int, array<string, mixed>> $posts
     * @param array<int, array<string, mixed>> $images
     * @param array<int, array<string, mixed>> $news
     * @return array<int, string>
     */
    private function expectedFiles(array $pages, array $posts, array $images, array $news): array
    {
        $files = ['sitemap.xml'];

        if ($pages !== []) {
            $files[] = 'pages.xml';
        }
        if ($posts !== []) {
            $files[] = 'posts.xml';
        }
        if ($images !== []) {
            $files[] = 'images.xml';
        }
        if ($news !== []) {
            $files[] = 'news.xml';
        }

        return $files;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new \RuntimeException('Sitemap-Einträge benötigen eine URL.');
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        if ($url === '/') {
            return $this->baseUrl . '/';
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }

    private function normalizeDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_W3C);
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return date(DATE_W3C, (int) $value);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return date(DATE_W3C);
        }

        if ($stringValue === '0000-00-00 00:00:00' || $stringValue === '0000-00-00') {
            return date(DATE_W3C);
        }

        $timestamp = strtotime($stringValue);
        if ($timestamp === false) {
            return date(DATE_W3C);
        }

        return date(DATE_W3C, $timestamp);
    }

    private function normalizePriority(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
        }

        if ($value === '' || $value === null) {
            return 0.8;
        }

        $priority = (float) $value;
        if ($priority < 0.0) {
            return 0.0;
        }

        if ($priority > 1.0) {
            return 1.0;
        }

        return $priority;
    }

    private function normalizeChangeFreq(string $value): string
    {
        $allowed = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
        $normalized = strtolower(trim($value));

        if (!in_array($normalized, $allowed, true)) {
            return 'weekly';
        }

        return $normalized;
    }

    /**
     * @template TInput
     * @param array<int, TInput> $items
     * @param callable(TInput): array<string, mixed> $normalizer
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items, callable $normalizer): array
    {
        $normalized = [];

        foreach ($items as $item) {
            try {
                $normalized[] = $normalizer($item);
            } catch (\Throwable) {
                continue;
            }
        }

        return $normalized;
    }

    private function resolveOutputMode(): OutputMode
    {
        $tempDir = sys_get_temp_dir();

        if ($this->isDifferentWindowsVolume($tempDir, $this->saveDir)) {
            return OutputMode::FILE;
        }

        return OutputMode::TEMP;
    }

    private function isDifferentWindowsVolume(string $sourcePath, string $targetPath): bool
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            return false;
        }

        $sourceVolume = $this->extractWindowsVolume($sourcePath);
        $targetVolume = $this->extractWindowsVolume($targetPath);

        return $sourceVolume !== ''
            && $targetVolume !== ''
            && strcasecmp($sourceVolume, $targetVolume) !== 0;
    }

    private function extractWindowsVolume(string $path): string
    {
        if (preg_match('/^[A-Za-z]:/', $path, $matches) !== 1) {
            return '';
        }

        return strtoupper($matches[0]);
    }
}