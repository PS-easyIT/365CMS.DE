<?php
/**
 * Theme Archive Repository
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS\Routing;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class ThemeArchiveRepository
{
    /**
     * @return array<int,int>
     */
    public function getCategoryArchiveIds(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT id, parent_id FROM {$prefix}post_categories",
            []
        ) ?: [];

        $byParent = [];
        foreach ($rows as $row) {
            $parentId = (int) ($row->parent_id ?? 0);
            $byParent[$parentId][] = (int) ($row->id ?? 0);
        }

        $collected = [];
        $walker = function (int $currentId) use (&$walker, &$collected, $byParent): void {
            if ($currentId <= 0 || isset($collected[$currentId])) {
                return;
            }

            $collected[$currentId] = true;
            foreach ($byParent[$currentId] ?? [] as $childId) {
                $walker((int) $childId);
            }
        };

        $walker($categoryId);

        return array_map('intval', array_keys($collected));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPublishedCategoryOverview(string $locale, string $localeAvailabilityExpression): array
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();
        $categories = $db->get_results(
            "SELECT id, name, slug, description, parent_id
             FROM {$prefix}post_categories
             ORDER BY name ASC"
        ) ?: [];

        $postIdsByCategory = [];
        foreach ($categories as $category) {
            $categoryId = (int) ($category->id ?? 0);
            if ($categoryId > 0) {
                $postIdsByCategory[$categoryId] = [];
            }
        }

        $primaryRows = $db->get_results(
            "SELECT p.id AS post_id, p.category_id AS category_id
             FROM {$prefix}posts p
             WHERE " . \cms_post_publication_where('p') . "
               AND {$localeAvailabilityExpression}
               AND p.category_id IS NOT NULL
               AND p.category_id > 0"
        ) ?: [];

        foreach ($primaryRows as $row) {
            $categoryId = (int) ($row->category_id ?? 0);
            $postId = (int) ($row->post_id ?? 0);
            if ($categoryId > 0 && $postId > 0) {
                $postIdsByCategory[$categoryId][$postId] = true;
            }
        }

        $relationRows = $db->get_results(
            "SELECT p.id AS post_id, pcr.category_id AS category_id
             FROM {$prefix}post_category_rel pcr
             INNER JOIN {$prefix}posts p ON p.id = pcr.post_id
             WHERE " . \cms_post_publication_where('p') . "
               AND {$localeAvailabilityExpression}"
        ) ?: [];

        foreach ($relationRows as $row) {
            $categoryId = (int) ($row->category_id ?? 0);
            $postId = (int) ($row->post_id ?? 0);
            if ($categoryId > 0 && $postId > 0) {
                $postIdsByCategory[$categoryId][$postId] = true;
            }
        }

        $items = [];
        foreach ($categories as $category) {
            $categoryId = (int) ($category->id ?? 0);
            $count = count($postIdsByCategory[$categoryId] ?? []);
            $slug = trim((string) ($category->slug ?? ''));

            if ($count <= 0 || $slug === '') {
                continue;
            }

            $items[] = [
                'title' => trim((string) ($category->name ?? 'Kategorie')),
                'slug' => $slug,
                'description' => trim((string) ($category->description ?? '')),
                'count' => $count,
                'url' => \cms_get_archive_url('category', $slug, $locale),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $countCompare = ((int) ($right['count'] ?? 0)) <=> ((int) ($left['count'] ?? 0));
            if ($countCompare !== 0) {
                return $countCompare;
            }

            return strnatcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPublishedTagOverview(string $locale, string $localeAvailabilityExpression): array
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();
        $tagPostMap = [];
        $tagLabels = [];

        $relationRows = $db->get_results(
            "SELECT p.id AS post_id, t.name, t.slug
             FROM {$prefix}post_tags t
             INNER JOIN {$prefix}post_tag_rel ptr ON ptr.tag_id = t.id
             INNER JOIN {$prefix}posts p ON p.id = ptr.post_id
             WHERE " . \cms_post_publication_where('p') . "
               AND {$localeAvailabilityExpression}"
        ) ?: [];

        foreach ($relationRows as $row) {
            $slug = trim((string) ($row->slug ?? ''));
            $postId = (int) ($row->post_id ?? 0);
            if ($slug === '' || $postId <= 0) {
                continue;
            }

            $tagPostMap[$slug][$postId] = true;
            $tagLabels[$slug] = trim((string) ($row->name ?? $slug));
        }

        $legacyRows = $db->get_results(
            "SELECT p.id, p.tags
             FROM {$prefix}posts p
             WHERE " . \cms_post_publication_where('p') . "
               AND {$localeAvailabilityExpression}
               AND p.tags IS NOT NULL
               AND p.tags != ''"
        ) ?: [];

        foreach ($legacyRows as $row) {
            $postId = (int) ($row->id ?? 0);
            if ($postId <= 0) {
                continue;
            }

            foreach ($this->parsePostTags((string) ($row->tags ?? '')) as $tag) {
                $slug = trim((string) ($tag['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }

                $tagPostMap[$slug][$postId] = true;
                if (!isset($tagLabels[$slug]) || trim((string) $tagLabels[$slug]) === '') {
                    $tagLabels[$slug] = trim((string) ($tag['name'] ?? $slug));
                }
            }
        }

        $items = [];
        foreach ($tagPostMap as $slug => $postMap) {
            $count = count($postMap);
            if ($count <= 0) {
                continue;
            }

            $title = trim((string) ($tagLabels[$slug] ?? $slug));
            $items[] = [
                'title' => $title !== '' ? $title : $slug,
                'slug' => (string) $slug,
                'description' => '',
                'count' => $count,
                'url' => \cms_get_archive_url('tag', (string) $slug, $locale),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $countCompare = ((int) ($right['count'] ?? 0)) <=> ((int) ($left['count'] ?? 0));
            if ($countCompare !== 0) {
                return $countCompare;
            }

            return strnatcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return $items;
    }

    /**
     * @return array<int,array{name:string,slug:string}>
     */
    public function getPostTagRows(int $postId): array
    {
        if ($postId <= 0) {
            return [];
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT t.name, t.slug
             FROM {$prefix}post_tags t
             INNER JOIN {$prefix}post_tag_rel ptr ON ptr.tag_id = t.id
             WHERE ptr.post_id = ?
             ORDER BY t.name ASC",
            [$postId]
        ) ?: [];

        return array_values(array_filter(array_map(static function (object $row): array {
            $name = trim((string) ($row->name ?? ''));
            $slug = trim((string) ($row->slug ?? ''));

            return $name !== '' && $slug !== ''
                ? ['name' => $name, 'slug' => $slug]
                : [];
        }, $rows)));
    }

    public function normalizeArchiveSlug(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * @return array<int,array{name:string,slug:string}>
     */
    public function parsePostTags(string $rawTags): array
    {
        $tags = [];

        foreach (array_filter(array_map('trim', explode(',', $rawTags))) as $tagName) {
            $tags[] = [
                'name' => $tagName,
                'slug' => $this->normalizeArchiveSlug($tagName),
            ];
        }

        return $tags;
    }
}
