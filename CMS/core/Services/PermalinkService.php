<?php
declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class PermalinkService
{
    public const PRESET_BLOG = '/blog/%postname%';
    public const PRESET_DATED = '/%year%/%monthnum%/%day%/%postname%';
    public const PRESET_SLUG = '/%postname%';
    public const PRESET_YEAR = '/%year%/%postname%';
    public const DEFAULT_POST_STRUCTURE = self::PRESET_BLOG;
    public const LEGACY_POST_ROUTE_PATTERN = '/blog/:slug';

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getPresetStructures(): array
    {
        return [
            'blog' => self::PRESET_BLOG,
            'dated' => self::PRESET_DATED,
            'slug' => self::PRESET_SLUG,
            'year' => self::PRESET_YEAR,
        ];
    }

    /**
     * @return list<string>
     */
    public static function getSupportedTokens(): array
    {
        return ['%year%', '%monthnum%', '%day%', '%postname%'];
    }

    public static function normalizePostStructure(string $structure): string
    {
        $structure = html_entity_decode(trim($structure), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($structure === '') {
            return self::DEFAULT_POST_STRUCTURE;
        }

        if (preg_match('#^https?://#i', $structure) === 1) {
            $structure = (string) (parse_url($structure, PHP_URL_PATH) ?? '');
        }

        $structure = str_replace(['{slug}', '{postname}'], '%postname%', $structure);
        $structure = str_ireplace(['%post_name%', '%slug%', '%month%'], ['%postname%', '%postname%', '%monthnum%'], $structure);

        $segments = array_values(array_filter(explode('/', trim($structure, '/')), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return self::DEFAULT_POST_STRUCTURE;
        }

        $normalizedSegments = [];
        foreach ($segments as $segment) {
            $token = strtolower($segment);
            if (in_array($token, self::getSupportedTokens(), true)) {
                $normalizedSegments[] = $token;
                continue;
            }

            $staticSegment = strtolower(trim($segment));
            $staticSegment = preg_replace('/[^a-z0-9._~-]+/', '-', $staticSegment) ?? '';
            $staticSegment = trim($staticSegment, '-');
            if ($staticSegment !== '') {
                $normalizedSegments[] = $staticSegment;
            }
        }

        if (!in_array('%postname%', $normalizedSegments, true)) {
            $normalizedSegments[] = '%postname%';
        }

        if ($normalizedSegments === []) {
            return self::DEFAULT_POST_STRUCTURE;
        }

        return '/' . implode('/', $normalizedSegments);
    }

    public static function inferPostStructurePreset(string $structure): string
    {
        $normalized = self::normalizePostStructure($structure);

        foreach (self::getPresetStructures() as $preset => $presetStructure) {
            if ($normalized === $presetStructure) {
                return $preset;
            }
        }

        return 'custom';
    }

    public static function buildExamplePath(string $structure, string $sampleSlug = 'beispielbeitrag', ?string $sampleDate = null): string
    {
        $normalized = self::normalizePostStructure($structure);
        $timestamp = strtotime($sampleDate ?? date('Y-m-d H:i:s')) ?: time();

        return str_replace(
            ['%year%', '%monthnum%', '%day%', '%postname%'],
            [date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp), $sampleSlug],
            $normalized
        );
    }

    public function getPostPermalinkStructure(): string
    {
        if (class_exists('CMS\\Database')) {
            try {
                $db = \CMS\Database::instance();
                $value = $db->get_var(
                    "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1",
                    ['setting_post_permalink_structure']
                );
                if (is_string($value) && trim($value) !== '') {
                    return self::normalizePostStructure($value);
                }
            } catch (\Throwable) {
            }
        }

        return self::DEFAULT_POST_STRUCTURE;
    }

    public function usesSlugOnlyStructure(): bool
    {
        return $this->getPostPermalinkStructure() === self::PRESET_SLUG;
    }

    public function buildPostRoutePattern(): string
    {
        return str_replace(
            ['%year%', '%monthnum%', '%day%', '%postname%'],
            [':year', ':month', ':day', ':slug'],
            $this->getPostPermalinkStructure()
        );
    }

    public function getLegacyPostPath(string $slug, string $locale = 'de'): string
    {
        $slug = $this->normalizeSlugSegment($slug);
        $path = '/blog' . ($slug !== '' ? '/' . $slug : '');

        return $this->appendLocale($path, $locale);
    }

    public function getLegacyPostUrl(string $slug, string $locale = 'de'): string
    {
        return rtrim((string) SITE_URL, '/') . $this->getLegacyPostPath($slug, $locale);
    }

    /**
     * @param array<string, mixed>|object $post
     */
    public function buildPostPath(array|object $post, string $locale = 'de'): string
    {
        $slug = $this->readSlugField($post, $locale);
        $publishedAt = $this->readField($post, 'published_at');
        $createdAt = $this->readField($post, 'created_at');

        return $this->buildPostPathFromValues($slug, $publishedAt, $createdAt, $locale);
    }

    /**
     * @param array<string, mixed>|object $post
     */
    public function buildPostUrl(array|object $post, string $locale = 'de'): string
    {
        return rtrim((string) SITE_URL, '/') . $this->buildPostPath($post, $locale);
    }

    public function buildPostPathFromValues(string $slug, ?string $publishedAt = null, ?string $createdAt = null, string $locale = 'de'): string
    {
        $structure = $this->getPostPermalinkStructure();
        [$year, $month, $day] = $this->resolveDateParts($publishedAt, $createdAt);

        $slugSegment = $slug === '{slug}' ? '{slug}' : $this->normalizeSlugSegment($slug);
        if ($slugSegment === '') {
            $slugSegment = 'beitrag';
        }

        $path = str_replace(
            ['%year%', '%monthnum%', '%day%', '%postname%'],
            [$year, $month, $day, $slugSegment],
            $structure
        );

        return $this->appendLocale($path, $locale);
    }

    public function buildPostUrlFromValues(string $slug, ?string $publishedAt = null, ?string $createdAt = null, string $locale = 'de'): string
    {
        return rtrim((string) SITE_URL, '/') . $this->buildPostPathFromValues($slug, $publishedAt, $createdAt, $locale);
    }

    public function buildPostUrlTemplate(?string $publishedAt = null, ?string $createdAt = null, string $locale = 'de'): string
    {
        return $this->buildPostUrlFromValues('{slug}', $publishedAt, $createdAt, $locale);
    }

    public function extractPostSlugFromPath(string $path): ?string
    {
        $path = $this->stripLocalizedAffix($this->normalizePath($path));
        if ($path === '') {
            return null;
        }

        if (preg_match('#^/blog/(?P<slug>[^/]+)$#', $path, $matches) === 1) {
            return rawurldecode((string) ($matches['slug'] ?? ''));
        }

        $regex = $this->buildStructureRegex($this->getPostPermalinkStructure());
        if ($regex !== '' && preg_match($regex, $path, $matches) === 1) {
            return rawurldecode((string) ($matches['slug'] ?? ''));
        }

        return null;
    }

    public static function resolveImportedSourceSlug(?string $sourceSlug, ?string $sourceUrl = null, string $fallback = ''): string
    {
        $candidate = self::preserveImportedSlug((string) ($sourceSlug ?? ''));
        if ($candidate !== '') {
            return $candidate;
        }

        $fromUrl = self::extractSlugFromUrl((string) ($sourceUrl ?? ''));
        if ($fromUrl !== '') {
            return self::preserveImportedSlug($fromUrl);
        }

        return self::preserveImportedSlug($fallback);
    }

    public static function preserveImportedSlug(string $slug): string
    {
        $slug = html_entity_decode(trim($slug), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $slug = trim($slug, "/ \t\n\r\0\x0B");
        $slug = preg_replace('/[\x00-\x1F\x7F]+/u', '', $slug) ?? $slug;

        if ($slug === '') {
            return '';
        }

        if (preg_match('/^[\p{L}\p{N}\-._~%]+$/u', $slug) === 1) {
            return mb_substr($slug, 0, 190);
        }

        return self::sanitizeImportedSlug($slug);
    }

    public static function extractSlugFromUrl(string $url): string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?? ''));
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return '';
        }

        return rawurldecode((string) end($segments));
    }

    private static function sanitizeImportedSlug(string $slug): string
    {
        $slug = mb_strtolower(trim($slug));
        $slug = preg_replace('/[^\p{L}\p{N}\-]/u', '-', $slug) ?? $slug;
        $slug = preg_replace('/-{2,}/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return mb_substr($slug !== '' ? $slug : 'imported', 0, 190);
    }

    private function normalizeSlugSegment(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '' || $slug === '{slug}') {
            return $slug;
        }

        return trim($slug, '/');
    }

    /**
     * @param array<string, mixed>|object $post
     */
    private function readField(array|object $post, string $field): string
    {
        if (is_array($post)) {
            return trim((string) ($post[$field] ?? ''));
        }

        return trim((string) ($post->{$field} ?? ''));
    }

    private function readSlugField(array|object $post, string $locale): string
    {
        $normalizedLocale = strtolower(trim($locale));
        if ($normalizedLocale !== '' && $normalizedLocale !== 'de') {
            $localizedField = 'slug_' . $normalizedLocale;
            $localizedSlug = $this->readField($post, $localizedField);
            if ($localizedSlug !== '') {
                return $localizedSlug;
            }
        }

        return $this->readField($post, 'slug');
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function resolveDateParts(?string $publishedAt, ?string $createdAt): array
    {
        $timestamp = strtotime((string) ($publishedAt ?? ''));
        if ($timestamp === false) {
            $timestamp = strtotime((string) ($createdAt ?? ''));
        }
        if ($timestamp === false) {
            $timestamp = time();
        }

        return [date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp)];
    }

    private function appendLocale(string $path, string $locale): string
    {
        $normalized = $this->normalizePath($path);
        if ($locale === '' || $locale === 'de') {
            return $normalized;
        }

        if ($normalized === '/') {
            return '/' . rawurlencode($locale);
        }

        return '/' . rawurlencode($locale) . $normalized;
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim(trim($path), '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

        return rtrim($normalized, '/') ?: '/';
    }

    private function stripLocalizedAffix(string $path): string
    {
        $normalized = $this->normalizePath($path);
        if (!class_exists('CMS\\Services\\ContentLocalizationService')) {
            return $normalized;
        }

        try {
            foreach (ContentLocalizationService::getInstance()->getContentLocales() as $locale) {
                if ($locale === 'de') {
                    continue;
                }

                $prefix = '/' . $locale;
                if ($normalized === $prefix || str_starts_with($normalized, $prefix . '/')) {
                    $stripped = substr($normalized, strlen($prefix));
                    return $this->normalizePath($stripped === false || $stripped === '' ? '/' : $stripped);
                }
            }
        } catch (\Throwable) {
        }

        return $normalized;
    }

    private function buildStructureRegex(string $structure): string
    {
        $pattern = preg_quote($structure, '#');
        $pattern = str_replace(
            ['%year%', '%monthnum%', '%day%', '%postname%'],
            ['(?P<year>[0-9]{4})', '(?P<month>[0-9]{1,2})', '(?P<day>[0-9]{1,2})', '(?P<slug>[^/]+)'],
            $pattern
        );

        return '#^' . $pattern . '$#u';
    }
}
