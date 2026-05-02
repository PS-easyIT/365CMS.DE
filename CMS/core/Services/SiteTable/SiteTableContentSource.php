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
    /** @var array<string,string> */
    private const FIXED_COLUMNS = [
        'type' => 'Typ',
        'title' => 'Titel',
        'url' => 'Public-Link',
        'category' => 'Kategorie',
    ];

    /** @return array<string,string> */
    public static function selectionModeOptions(): array
    {
        return [
            'items' => 'Ausgewählte Seiten/Beiträge',
            'category' => 'Kategorie-Filter',
        ];
    }

    public static function defaultSelectionMode(): string
    {
        return 'items';
    }

    /** @return list<string> */
    public static function defaultItemKeys(): array
    {
        return [];
    }

    public static function defaultCategoryId(): int
    {
        return 0;
    }

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
     * @return array{
     *   modeOptions:array<string,string>,
     *   itemOptions:array{pages:list<array{key:string,label:string,meta:string}>,posts:list<array{key:string,label:string,meta:string}>},
     *   categoryOptions:list<array{id:int,label:string,slug:string}>,
     *   fixedColumns:list<string>
     * }
     */
    public static function adminOptions(Database $db, string $prefix): array
    {
        return [
            'modeOptions' => self::selectionModeOptions(),
            'itemOptions' => self::loadAdminItemOptions($db, $prefix),
            'categoryOptions' => self::loadCategoryOptions($db, $prefix),
            'fixedColumns' => array_values(self::FIXED_COLUMNS),
        ];
    }

    /**
     * @param mixed $mode
     * @param mixed $itemKeys
     * @param mixed $categoryId
     * @return array{enabled:bool,mode:string,item_keys:list<string>,category_id:int,error:string}
     */
    public static function normalizeSettings(bool $enabled, mixed $mode, mixed $itemKeys, mixed $categoryId): array
    {
        $normalizedMode = self::normalizeSelectionMode($mode);
        $normalizedItemKeys = self::normalizeItemKeys($itemKeys);
        $normalizedCategoryId = self::normalizePositiveId($categoryId);

        if (!$enabled) {
            return [
                'enabled' => false,
                'mode' => $normalizedMode,
                'item_keys' => self::defaultItemKeys(),
                'category_id' => self::defaultCategoryId(),
                'error' => '',
            ];
        }

        if ($normalizedMode === 'items' && $normalizedItemKeys === []) {
            return ['enabled' => true, 'mode' => $normalizedMode, 'item_keys' => [], 'category_id' => 0, 'error' => 'Bitte mindestens eine Seite oder einen Beitrag auswählen.'];
        }

        if ($normalizedMode === 'category' && $normalizedCategoryId <= 0) {
            return ['enabled' => true, 'mode' => $normalizedMode, 'item_keys' => [], 'category_id' => 0, 'error' => 'Bitte eine Kategorie für den Filter auswählen.'];
        }

        if ($normalizedMode === 'items') {
            $normalizedCategoryId = 0;
        } else {
            $normalizedItemKeys = [];
        }

        return [
            'enabled' => true,
            'mode' => $normalizedMode,
            'item_keys' => $normalizedItemKeys,
            'category_id' => $normalizedCategoryId,
            'error' => '',
        ];
    }

    /**
     * Prüft feste Admin-Auswahlwerte nochmals serverseitig gegen die tatsächlich
     * angebotenen Optionen. Clientseitig deaktivierte Felder sind nur UX, keine Sicherheit.
     *
     * @param array{enabled?:bool,mode?:string,item_keys?:list<string>,category_id?:int} $settings
     */
    public static function validateSelection(Database $db, string $prefix, array $settings): string
    {
        if (empty($settings['enabled'])) {
            return '';
        }

        $mode = self::normalizeSelectionMode($settings['mode'] ?? self::defaultSelectionMode());
        if ($mode === 'category') {
            $categoryId = self::normalizePositiveId($settings['category_id'] ?? 0);
            $allowedCategoryIds = [];
            foreach (self::loadCategoryOptions($db, $prefix) as $category) {
                $allowedCategoryIds[(int) $category['id']] = true;
            }

            return $categoryId > 0 && isset($allowedCategoryIds[$categoryId])
                ? ''
                : 'Die ausgewählte Kategorie ist nicht verfügbar.';
        }

        $itemKeys = self::normalizeItemKeys($settings['item_keys'] ?? []);
        $allowedItemKeys = [];
        $itemOptions = self::loadAdminItemOptions($db, $prefix);
        foreach (['pages', 'posts'] as $group) {
            foreach ($itemOptions[$group] ?? [] as $item) {
                $allowedItemKeys[(string) $item['key']] = true;
            }
        }

        foreach ($itemKeys as $itemKey) {
            if (!isset($allowedItemKeys[$itemKey])) {
                return 'Mindestens eine ausgewählte Seite oder ein ausgewählter Beitrag ist nicht verfügbar.';
            }
        }

        return $itemKeys !== []
            ? ''
            : 'Bitte mindestens eine Seite oder einen Beitrag auswählen.';
    }

    /**
     * @param array<string,mixed> $settings
     * @return list<array{label:string,type:string,source_field:string}>
     */
    public static function buildColumns(array $settings): array
    {
        if (self::shouldUseLegacySettings($settings)) {
            return self::buildLegacyColumns($settings);
        }

        $columns = [];
        foreach (self::FIXED_COLUMNS as $field => $label) {
            $columns[] = [
                'label' => $label,
                'type' => 'text',
                'source_field' => $field,
            ];
        }

        return $columns;
    }

    /**
     * @param array<string,mixed> $settings
     * @return list<array<string,string>>
     */
    public static function buildRows(Database $db, string $prefix, array $settings, int $limit = 250): array
    {
        if (self::shouldUseLegacySettings($settings)) {
            return self::buildLegacyRows($db, $prefix, $settings, $limit);
        }

        $limit = max(1, min(500, $limit));
        $locale = self::resolveCurrentLocale();
        $mode = self::normalizeSelectionMode($settings['mode'] ?? self::defaultSelectionMode());

        $items = $mode === 'category'
            ? self::loadItemsByCategory($db, $prefix, (int) ($settings['category_id'] ?? 0), $locale)
            : self::loadItemsByKeys($db, $prefix, self::normalizeItemKeys($settings['item_keys'] ?? []), $locale);

        return self::buildFixedRows($items, $locale, $limit);
    }

    private static function normalizeSelectionMode(mixed $mode): string
    {
        $mode = is_scalar($mode) ? trim((string) $mode) : '';

        return array_key_exists($mode, self::selectionModeOptions())
            ? $mode
            : self::defaultSelectionMode();
    }

    private static function normalizePositiveId(mixed $value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }

        if (!is_scalar($value)) {
            return 0;
        }

        $value = trim((string) $value);
        if ($value === '' || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return 0;
        }

        return (int) $value;
    }

    /** @return list<string> */
    private static function normalizeItemKeys(mixed $itemKeys): array
    {
        $normalized = [];
        foreach (is_array($itemKeys) ? $itemKeys : [] as $itemKey) {
            $itemKey = is_scalar($itemKey) ? trim((string) $itemKey) : '';
            if (preg_match('/^(page|post):([1-9][0-9]*)$/', $itemKey) !== 1) {
                continue;
            }

            $normalized[$itemKey] = $itemKey;
        }

        return array_values($normalized);
    }

    /**
     * @param list<string> $itemKeys
     * @return array{pages:list<int>,posts:list<int>}
     */
    private static function splitItemKeys(array $itemKeys): array
    {
        $pageIds = [];
        $postIds = [];

        foreach ($itemKeys as $itemKey) {
            if (preg_match('/^(page|post):([1-9][0-9]*)$/', $itemKey, $matches) !== 1) {
                continue;
            }

            $id = (int) ($matches[2] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if (($matches[1] ?? '') === 'page') {
                $pageIds[$id] = $id;
            } else {
                $postIds[$id] = $id;
            }
        }

        return [
            'pages' => array_values($pageIds),
            'posts' => array_values($postIds),
        ];
    }

    /** @return array{pages:list<array{key:string,label:string,meta:string}>,posts:list<array{key:string,label:string,meta:string}>} */
    private static function loadAdminItemOptions(Database $db, string $prefix): array
    {
        return [
            'pages' => self::loadAdminPages($db, $prefix),
            'posts' => self::loadAdminPosts($db, $prefix),
        ];
    }

    /** @return list<array{key:string,label:string,meta:string}> */
    private static function loadAdminPages(Database $db, string $prefix): array
    {
        $items = $db->get_results(
            "SELECT p.id, p.title, p.slug, c.name AS category
             FROM {$prefix}pages p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY p.title ASC, p.slug ASC, p.id ASC"
        ) ?: [];

        return array_map(static function (object $item): array {
            $row = (array) $item;
            $title = trim((string) ($row['title'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));

            return [
                'key' => 'page:' . (int) ($row['id'] ?? 0),
                'label' => $title !== '' ? $title : ('Seite #' . (int) ($row['id'] ?? 0)),
                'meta' => trim(($slug !== '' ? '/' . trim($slug, '/') : '') . ($category !== '' ? ' · ' . $category : '')),
            ];
        }, $items);
    }

    /** @return list<array{key:string,label:string,meta:string}> */
    private static function loadAdminPosts(Database $db, string $prefix): array
    {
        $items = $db->get_results(
            "SELECT p.id, p.title, p.slug, c.name AS category
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND (p.published_at IS NULL OR p.published_at <= NOW())
             ORDER BY p.title ASC, p.slug ASC, p.id ASC"
        ) ?: [];

        return array_map(static function (object $item): array {
            $row = (array) $item;
            $title = trim((string) ($row['title'] ?? ''));
            $slug = trim((string) ($row['slug'] ?? ''));
            $category = trim((string) ($row['category'] ?? ''));

            return [
                'key' => 'post:' . (int) ($row['id'] ?? 0),
                'label' => $title !== '' ? $title : ('Beitrag #' . (int) ($row['id'] ?? 0)),
                'meta' => trim(($slug !== '' ? '/blog/' . trim($slug, '/') : '') . ($category !== '' ? ' · ' . $category : '')),
            ];
        }, $items);
    }

    /** @return list<array{id:int,label:string,slug:string}> */
    private static function loadCategoryOptions(Database $db, string $prefix): array
    {
        $items = $db->get_results(
            "SELECT id, name, slug
             FROM {$prefix}post_categories
             ORDER BY name ASC, slug ASC, id ASC"
        ) ?: [];

        return array_map(static function (object $item): array {
            $row = (array) $item;

            return [
                'id' => (int) ($row['id'] ?? 0),
                'label' => trim((string) ($row['name'] ?? '')),
                'slug' => trim((string) ($row['slug'] ?? '')),
            ];
        }, $items);
    }

    /**
     * @param array<string,mixed> $settings
     */
    private static function shouldUseLegacySettings(array $settings): bool
    {
        $itemKeys = self::normalizeItemKeys($settings['item_keys'] ?? []);
        $categoryId = self::normalizePositiveId($settings['category_id'] ?? 0);
        $legacySources = is_array($settings['sources'] ?? null) ? $settings['sources'] : [];
        $legacyFields = is_array($settings['fields'] ?? null) ? $settings['fields'] : [];

        return $itemKeys === []
            && $categoryId <= 0
            && ($legacySources !== [] || $legacyFields !== []);
    }

    /** @return list<array<string,mixed>> */
    private static function loadItemsByKeys(Database $db, string $prefix, array $itemKeys, string $locale): array
    {
        $split = self::splitItemKeys($itemKeys);
        $items = [];

        if ($split['pages'] !== []) {
            $items = array_merge($items, self::loadPagesByIds($db, $prefix, $split['pages'], $locale));
        }

        if ($split['posts'] !== []) {
            $items = array_merge($items, self::loadPostsByIds($db, $prefix, $split['posts'], $locale));
        }

        return $items;
    }

    /** @return list<array<string,mixed>> */
    private static function loadItemsByCategory(Database $db, string $prefix, int $categoryId, string $locale): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        return array_merge(
            self::loadPagesByCategory($db, $prefix, $categoryId, $locale),
            self::loadPostsByCategory($db, $prefix, $categoryId, $locale)
        );
    }

    /**
     * @param list<int> $ids
     * @return list<array<string,mixed>>
     */
    private static function loadPagesByIds(Database $db, string $prefix, array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $localizedWhere = self::buildLocalizedWhereClause('p', $locale, ['title', 'content', 'slug']);
        $localizedSelect = self::buildPageLocalizedSelect($locale);

        $items = $db->get_results(
            "SELECT p.id, 'page' AS source_type, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}pages p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.id IN ({$placeholders})
               AND p.status = 'published'
               {$localizedWhere}
             ORDER BY p.title ASC, p.slug ASC, p.id ASC",
            $ids
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /**
     * @param list<int> $ids
     * @return list<array<string,mixed>>
     */
    private static function loadPostsByIds(Database $db, string $prefix, array $ids, string $locale): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $localizedWhere = self::buildLocalizedWhereClause('p', $locale, ['title', 'content', 'excerpt', 'slug']);
        $localizedSelect = self::buildPostLocalizedSelect($locale);

        $items = $db->get_results(
            "SELECT p.id, 'post' AS source_type, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.id IN ({$placeholders})
               AND p.status = 'published'
               AND (p.published_at IS NULL OR p.published_at <= NOW())
               {$localizedWhere}
             ORDER BY p.title ASC, p.slug ASC, p.id ASC",
            $ids
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /** @return list<array<string,mixed>> */
    private static function loadPagesByCategory(Database $db, string $prefix, int $categoryId, string $locale): array
    {
        $localizedWhere = self::buildLocalizedWhereClause('p', $locale, ['title', 'content', 'slug']);
        $localizedSelect = self::buildPageLocalizedSelect($locale);

        $items = $db->get_results(
            "SELECT p.id, 'page' AS source_type, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}pages p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
               AND p.category_id = ?
               {$localizedWhere}
             ORDER BY p.title ASC, p.slug ASC, p.id ASC",
            [$categoryId]
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /** @return list<array<string,mixed>> */
    private static function loadPostsByCategory(Database $db, string $prefix, int $categoryId, string $locale): array
    {
        $localizedWhere = self::buildLocalizedWhereClause('p', $locale, ['title', 'content', 'excerpt', 'slug']);
        $localizedSelect = self::buildPostLocalizedSelect($locale);

        $items = $db->get_results(
            "SELECT DISTINCT p.id, 'post' AS source_type, p.title, {$localizedSelect}, p.slug, p.status, p.excerpt, p.created_at, p.updated_at, p.published_at,
                    u.display_name AS author, c.name AS category
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             LEFT JOIN {$prefix}post_category_rel pcr ON pcr.post_id = p.id
             WHERE p.status = 'published'
               AND (p.published_at IS NULL OR p.published_at <= NOW())
               AND (p.category_id = ? OR pcr.category_id = ?)
               {$localizedWhere}
             ORDER BY p.title ASC, p.slug ASC, p.id ASC",
            [$categoryId, $categoryId]
        ) ?: [];

        return array_map(static fn(object $item): array => (array) $item, $items);
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return list<array<string,string>>
     */
    private static function buildFixedRows(array $items, string $locale, int $limit): array
    {
        $rows = [];

        foreach ($items as $item) {
            $sourceType = (string) ($item['source_type'] ?? '');
            $normalizedType = $sourceType === 'page' ? 'page' : 'post';
            $localizedItem = self::localizeItem($normalizedType, $item, $locale);
            $title = self::plain((string) ($localizedItem['title'] ?? ''));
            if ($title === '') {
                $title = self::plain((string) ($localizedItem['slug'] ?? $item['slug'] ?? ''));
            }

            $rows[] = [
                '__sort_group' => (string) self::sourceTypeOrder($normalizedType),
                '__sort_title' => self::normalizeSortValue($title),
                self::FIXED_COLUMNS['type'] => $normalizedType === 'page' ? 'Seite' : 'Beitrag',
                self::FIXED_COLUMNS['title'] => $title,
                self::FIXED_COLUMNS['url'] => self::buildPublicLink($normalizedType, $localizedItem, $locale),
                self::FIXED_COLUMNS['category'] => self::plain((string) ($localizedItem['category'] ?? '')),
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            $groupCompare = ((int) ($left['__sort_group'] ?? 99)) <=> ((int) ($right['__sort_group'] ?? 99));
            if ($groupCompare !== 0) {
                return $groupCompare;
            }

            $titleCompare = strcmp((string) ($left['__sort_title'] ?? ''), (string) ($right['__sort_title'] ?? ''));
            if ($titleCompare !== 0) {
                return $titleCompare;
            }

            return strcmp((string) ($left[self::FIXED_COLUMNS['title']] ?? ''), (string) ($right[self::FIXED_COLUMNS['title']] ?? ''));
        });

        $rows = array_slice($rows, 0, $limit);

        return array_map(static function (array $row): array {
            unset($row['__sort_group'], $row['__sort_title']);

            return $row;
        }, $rows);
    }

    private static function sourceTypeOrder(string $sourceType): int
    {
        return $sourceType === 'page' ? 0 : 1;
    }

    private static function normalizeSortValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private static function buildLocalizedWhereClause(string $alias, string $locale, array $fields): string
    {
        if ($locale === 'de') {
            return '';
        }

        $conditions = [];
        foreach ($fields as $field) {
            $conditions[] = "CHAR_LENGTH(TRIM(COALESCE({$alias}.{$field}_{$locale}, ''))) > 0";
        }

        return $conditions === [] ? '' : ' AND (' . implode(' OR ', $conditions) . ')';
    }

    private static function buildPageLocalizedSelect(string $locale): string
    {
        return $locale !== 'de'
            ? "p.title_{$locale}, p.slug_{$locale}, p.content_{$locale}"
            : 'p.title AS title_de, p.slug AS slug_de, p.content AS content_de';
    }

    private static function buildPostLocalizedSelect(string $locale): string
    {
        return $locale !== 'de'
            ? "p.title_{$locale}, p.slug_{$locale}, p.excerpt_{$locale}, p.content_{$locale}"
            : 'p.title AS title_de, p.slug AS slug_de, p.excerpt AS excerpt_de, p.content AS content_de';
    }

    /**
     * @param array<string,mixed> $settings
     * @return list<array{label:string,type:string,source_field:string}>
     */
    private static function buildLegacyColumns(array $settings): array
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
     * @param array<string,mixed> $settings
     * @return list<array<string,string>>
     */
    private static function buildLegacyRows(Database $db, string $prefix, array $settings, int $limit = 250): array
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

        return in_array($locale, ['de', 'en'], true) ? $locale : 'de';
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
