<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

use CMS\Database;
use CMS\Services\ContentLocalizationService;
use CMS\Services\PermalinkService;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableContentSource
{
    /** @return array<string,string> */
    public static function sourceOptions(): array
    {
        return [
            'pages' => 'Seiten',
            'posts' => 'Beiträge',
        ];
    }

    /** @return array<string,array{label:string,sources:list<string>}> */
    public static function fieldOptions(): array
    {
        return [
            'type' => ['label' => 'Typ', 'sources' => ['pages', 'posts']],
            'title' => ['label' => 'Titel', 'sources' => ['pages', 'posts']],
            'slug' => ['label' => 'Slug', 'sources' => ['pages', 'posts']],
            'url' => ['label' => 'Public-Link', 'sources' => ['pages', 'posts']],
            'status' => ['label' => 'Status', 'sources' => ['pages', 'posts']],
            'excerpt' => ['label' => 'Kurzfassung', 'sources' => ['pages', 'posts']],
            'category' => ['label' => 'Kategorie', 'sources' => ['pages', 'posts']],
            'author' => ['label' => 'Autor', 'sources' => ['pages', 'posts']],
            'published_at' => ['label' => 'Veröffentlicht', 'sources' => ['pages', 'posts']],
            'updated_at' => ['label' => 'Aktualisiert', 'sources' => ['pages', 'posts']],
        ];
    }

    /** @return list<string> */
    public static function defaultSources(): array
    {
        return ['pages', 'posts'];
    }

    /** @return list<string> */
    public static function defaultFields(): array
    {
        return ['type', 'title', 'url', 'status', 'published_at', 'updated_at'];
    }

    /**
     * @param mixed $sources
     * @param mixed $fields
     * @return array{enabled:bool,sources:list<string>,fields:list<string>,error:string}
     */
    public static function normalizeSettings(bool $enabled, mixed $sources, mixed $fields): array
    {
        $sourceOptions = self::sourceOptions();
        $fieldOptions = self::fieldOptions();

        $normalizedSources = [];
        foreach (is_array($sources) ? $sources : [] as $source) {
            $source = is_scalar($source) ? trim((string) $source) : '';
            if (isset($sourceOptions[$source])) {
                $normalizedSources[$source] = $source;
            }
        }

        $normalizedFields = [];
        foreach (is_array($fields) ? $fields : [] as $field) {
            $field = is_scalar($field) ? trim((string) $field) : '';
            if (isset($fieldOptions[$field])) {
                $normalizedFields[$field] = $field;
            }
        }

        if (!$enabled) {
            return [
                'enabled' => false,
                'sources' => array_values($normalizedSources ?: self::defaultSources()),
                'fields' => array_values($normalizedFields ?: self::defaultFields()),
                'error' => '',
            ];
        }

        if ($normalizedSources === []) {
            return ['enabled' => true, 'sources' => [], 'fields' => [], 'error' => 'Bitte mindestens Seiten oder Beiträge als Quelle auswählen.'];
        }

        if ($normalizedFields === []) {
            return ['enabled' => true, 'sources' => array_values($normalizedSources), 'fields' => [], 'error' => 'Bitte mindestens eine Spalte aus Seiten/Beiträgen auswählen.'];
        }

        return [
            'enabled' => true,
            'sources' => array_values($normalizedSources),
            'fields' => array_values($normalizedFields),
            'error' => '',
        ];
    }

    /**
     * @param array{fields?:list<string>} $settings
     * @return list<array{label:string,type:string,source_field:string}>
     */
    public static function buildColumns(array $settings): array
    {
        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : self::defaultFields();
        $fieldOptions = self::fieldOptions();
        $columns = [];

        foreach ($fields as $field) {
            $field = is_scalar($field) ? (string) $field : '';
            if (!isset($fieldOptions[$field])) {
                continue;
            }

            $columns[] = [
                'label' => $fieldOptions[$field]['label'],
                'type' => 'text',
                'source_field' => $field,
            ];
        }

        return $columns;
    }

    /**
     * @param array{sources?:list<string>,fields?:list<string>} $settings
     * @return list<array<string,string>>
     */
    public static function buildRows(Database $db, string $prefix, array $settings, int $limit = 250): array
    {
        $sources = is_array($settings['sources'] ?? null) ? $settings['sources'] : self::defaultSources();
        $fields = is_array($settings['fields'] ?? null) ? $settings['fields'] : self::defaultFields();
        $limit = max(1, min(500, $limit));
        $locale = self::resolveCurrentLocale();
        $rows = [];

        if (in_array('pages', $sources, true)) {
            foreach (self::loadPages($db, $prefix, $limit, $locale) as $page) {
                $rows[] = self::buildRow('page', $page, $fields, $locale);
                if (count($rows) >= $limit) {
                    return $rows;
                }
            }
        }

        if (in_array('posts', $sources, true)) {
            foreach (self::loadPosts($db, $prefix, $limit, $locale) as $post) {
                $rows[] = self::buildRow('post', $post, $fields, $locale);
                if (count($rows) >= $limit) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private static function loadPages(Database $db, string $prefix, int $limit, string $locale): array
    {
        $localizedWhere = $locale !== 'de'
            ? " AND (CHAR_LENGTH(TRIM(COALESCE(p.title_{$locale}, ''))) > 0 OR CHAR_LENGTH(TRIM(COALESCE(p.content_{$locale}, ''))) > 0 OR CHAR_LENGTH(TRIM(COALESCE(p.slug_{$locale}, ''))) > 0)"
            : '';
        $localizedSelect = $locale !== 'de'
            ? "p.title_{$locale}, p.slug_{$locale}, p.content_{$locale}"
            : "p.title AS title_de, p.slug AS slug_de, p.content AS content_de";

        $items = $db->get_results(
            "SELECT p.id, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}pages p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             {$localizedWhere}
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
             LIMIT ?",
            [$limit]
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /** @return list<array<string,mixed>> */
    private static function loadPosts(Database $db, string $prefix, int $limit, string $locale): array
    {
        $localizedWhere = $locale !== 'de'
            ? " AND (CHAR_LENGTH(TRIM(COALESCE(p.title_{$locale}, ''))) > 0 OR CHAR_LENGTH(TRIM(COALESCE(p.content_{$locale}, ''))) > 0 OR CHAR_LENGTH(TRIM(COALESCE(p.excerpt_{$locale}, ''))) > 0 OR CHAR_LENGTH(TRIM(COALESCE(p.slug_{$locale}, ''))) > 0)"
            : '';
        $localizedSelect = $locale !== 'de'
            ? "p.title_{$locale}, p.slug_{$locale}, p.excerpt_{$locale}, p.content_{$locale}"
            : "p.title AS title_de, p.slug AS slug_de, p.excerpt AS excerpt_de, p.content AS content_de";

        $items = $db->get_results(
            "SELECT p.id, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
             {$localizedWhere}
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
             LIMIT ?",
            [$limit]
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /**
     * @param list<string> $fields
     * @return array<string,string>
     */
    private static function buildRow(string $type, array $item, array $fields, string $locale): array
    {
        $fieldOptions = self::fieldOptions();
        $row = [];

        foreach ($fields as $field) {
            $field = is_scalar($field) ? (string) $field : '';
            if (!isset($fieldOptions[$field])) {
                continue;
            }

            $label = $fieldOptions[$field]['label'];
            $row[$label] = self::resolveFieldValue($type, $item, $field, $locale);
        }

        return $row;
    }

    private static function resolveFieldValue(string $type, array $item, string $field, string $locale): string
    {
        $localizedItem = self::localizeItem($type, $item, $locale);

        return match ($field) {
            'type' => $type === 'page' ? 'Seite' : 'Beitrag',
            'title' => self::plain((string) ($localizedItem['title'] ?? '')),
            'slug' => self::plain((string) ($localizedItem['slug'] ?? '')),
            'url' => self::buildPublicLink($type, $localizedItem, $locale),
            'status' => self::plain((string) ($localizedItem['status'] ?? '')),
            'excerpt' => self::plain((string) ($localizedItem['excerpt'] ?? '')),
            'category' => self::plain((string) ($localizedItem['category'] ?? '')),
            'author' => self::plain((string) ($localizedItem['author'] ?? '')),
            'published_at' => self::formatDate((string) ($localizedItem['published_at'] ?? $localizedItem['created_at'] ?? '')),
            'updated_at' => self::formatDate((string) ($localizedItem['updated_at'] ?? '')),
            default => '',
        };
    }

    private static function buildPublicLink(string $type, array $item, string $locale): string
    {
        $slug = trim((string) ($item['slug'] ?? ''));
        if ($slug === '') {
            return '';
        }

        if ($type === 'post') {
            $path = PermalinkService::getInstance()->buildPostPathFromValues(
                $slug,
                (string) ($item['published_at'] ?? ''),
                (string) ($item['created_at'] ?? ''),
                $locale
            );
        } else {
            $path = ContentLocalizationService::getInstance()->buildLocalizedPath('/' . trim($slug, '/'), $locale);
        }

        $label = $path;
        $href = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

        return '<a href="' . $href . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    private static function resolveCurrentLocale(): string
    {
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? (string) $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $context = ContentLocalizationService::getInstance()->resolveRequestContext(is_string($path) ? $path : '/');
        $locale = (string) ($context['locale'] ?? 'de');

        return preg_match('/^[a-z]{2}$/', $locale) === 1 ? $locale : 'de';
    }

    /** @return array<string,mixed> */
    private static function localizeItem(string $type, array $item, string $locale): array
    {
        if ($locale === 'de') {
            return $item;
        }

        $service = ContentLocalizationService::getInstance();

        return $type === 'post'
            ? $service->localizePost($item, $locale)
            : $service->localizePage($item, $locale);
    }

    private static function plain(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', trim(strip_tags($value))) ?? '';

        return $value;
    }

    private static function formatDate(string $value): string
    {
        $timestamp = strtotime(trim($value));

        return $timestamp !== false ? date('d.m.Y', $timestamp) : '';
    }
}
