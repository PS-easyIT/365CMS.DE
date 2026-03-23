<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function meridian_get_categories(int $limit = 0): array
{
    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $categories = $db->get_results(
            "SELECT c.id, c.name, c.slug, c.parent_id, c.sort_order,
                    (SELECT COUNT(*) FROM {$prefix}posts p WHERE p.category_id = c.id AND " . cms_post_publication_where('p') . ") AS post_count_direct
             FROM {$prefix}post_categories c
             ORDER BY c.sort_order ASC, c.name ASC"
        );

        $rows = $categories ? array_map(static fn($category): array => (array) $category, $categories) : [];
        $ordered = meridian_build_category_tree_rows($rows);

        if ($limit > 0) {
            $ordered = array_slice($ordered, 0, $limit);
        }

        return $ordered;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * @param array<int,array<string,mixed>> $rows
 * @return array<int,array<string,mixed>>
 */
function meridian_build_category_tree_rows(array $rows): array
{
    $byId = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }

        $row['id'] = $id;
        $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
        $row['post_count_direct'] = (int) ($row['post_count_direct'] ?? $row['post_count'] ?? 0);
        $byId[$id] = $row;
    }

    $byParent = [];
    foreach ($byId as $id => $row) {
        $parentId = (int) ($row['parent_id'] ?? 0);
        if ($parentId > 0 && !isset($byId[$parentId])) {
            $parentId = 0;
        }
        $byParent[$parentId][] = $id;
    }

    $flat = [];
    $walker = function (int $parentId, int $depth) use (&$walker, &$flat, $byParent, $byId): int {
        $branchTotal = 0;

        foreach ($byParent[$parentId] ?? [] as $categoryId) {
            if (!isset($byId[$categoryId])) {
                continue;
            }

            $row = $byId[$categoryId];
            $row['depth'] = $depth;
            $row['name_plain'] = (string) ($row['name'] ?? '');
            $row['name'] = str_repeat('— ', $depth) . (string) ($row['name'] ?? '');
            $index = count($flat);
            $flat[] = $row;
            $childrenTotal = $walker($categoryId, $depth + 1);
            $row['post_count'] = (int) ($row['post_count_direct'] ?? 0) + $childrenTotal;
            $flat[$index] = $row;
            $branchTotal += (int) $row['post_count'];
        }

        return $branchTotal;
    };

    $walker(0, 0);

    return $flat;
}

function meridian_get_tags(int $limit = 30): array
{
    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $stmt = $db->execute("SELECT tags FROM {$prefix}posts WHERE " . cms_post_publication_where() . " AND tags IS NOT NULL AND tags != ''");
        $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $counts = [];

        foreach ($rows as $row) {
            foreach (array_filter(array_map('trim', explode(',', $row))) as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        arsort($counts);
        if ($limit > 0) {
            $counts = array_slice($counts, 0, $limit, true);
        }

        $tags = [];
        foreach ($counts as $name => $count) {
            $tags[] = [
                'name' => $name,
                'slug' => urlencode(strtolower($name)),
                'count' => $count,
            ];
        }

        return $tags;
    } catch (\Throwable $e) {
        return [];
    }
}

function meridian_get_recent_posts(int $limit = 5, ?int $excludeId = null): array
{
    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $sql = "SELECT p.id, p.title, p.slug, p.featured_image, p.published_at, p.created_at, c.name AS category_name
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                WHERE " . cms_post_publication_where('p');
        $params = [];

        if ($excludeId !== null) {
            $sql .= ' AND p.id != ?';
            $params[] = $excludeId;
        }

        $sql .= ' ORDER BY p.published_at DESC LIMIT ?';
        $params[] = $limit;

        $rows = $db->get_results($sql, $params);

        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : [];
    } catch (\Throwable $e) {
        return [];
    }
}

function meridian_get_related_posts(int $categoryId, int $excludeId, int $limit = 3): array
{
    if ($categoryId <= 0) {
        return meridian_get_recent_posts($limit, $excludeId);
    }

    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT p.id, p.title, p.slug, p.featured_image, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE " . cms_post_publication_where('p') . " AND p.category_id = ? AND p.id != ?
             ORDER BY p.published_at DESC LIMIT ?",
            [$categoryId, $excludeId, $limit]
        );

        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : meridian_get_recent_posts($limit, $excludeId);
    } catch (\Throwable $e) {
        return meridian_get_recent_posts($limit, $excludeId);
    }
}

function meridian_get_posts(array $args = []): array
{
    $defaults = [
        'limit' => 5,
        'offset' => 0,
        'sticky' => false,
        'exclude' => [],
        'category' => null,
        'orderby' => 'published_at',
        'order' => 'DESC',
    ];
    $args = array_merge($defaults, $args);

    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();

        $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                   COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                LEFT JOIN {$prefix}users u ON u.id = p.author_id
                WHERE " . cms_post_publication_where('p');
        $params = [];

        if (!empty($args['exclude'])) {
            $ids = array_map('intval', (array)$args['exclude']);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $ids);
            }
        }

        if ($args['sticky'] === true) {
            try {
                $db->execute("SELECT is_sticky FROM {$prefix}posts LIMIT 1");
                $sql .= ' AND p.is_sticky = 1';
            } catch (\Throwable $e) {
            }
        }

        $validOrders = ['published_at', 'created_at', 'title', 'views'];
        $orderby = in_array($args['orderby'], $validOrders, true) ? $args['orderby'] : 'published_at';
        $order = strtoupper((string)$args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY p.{$orderby} {$order}";
        $sql .= ' LIMIT ? OFFSET ?';
        $params[] = (int)$args['limit'];
        $params[] = (int)$args['offset'];

        $rows = $db->get_results($sql, $params);

        return $rows ? array_map(static fn(object $row): array => (array)$row, $rows) : [];
    } catch (\Throwable $e) {
        return [];
    }
}

function meridian_get_category_post_count(int $categoryId): int
{
    try {
        $db = \CMS\Database::instance();
        $prefix = $db->getPrefix();
        $categories = $db->get_results(
            "SELECT id, parent_id FROM {$prefix}post_categories",
            []
        ) ?: [];

        $byParent = [];
        foreach ($categories as $category) {
            $byParent[(int) ($category->parent_id ?? 0)][] = (int) ($category->id ?? 0);
        }

        $categoryIds = [];
        $walker = function (int $currentId) use (&$walker, &$categoryIds, $byParent): void {
            if ($currentId <= 0 || isset($categoryIds[$currentId])) {
                return;
            }

            $categoryIds[$currentId] = true;
            foreach ($byParent[$currentId] ?? [] as $childId) {
                $walker((int) $childId);
            }
        };

        $walker($categoryId);
        if ($categoryIds === []) {
            return 0;
        }

        $ids = array_map('intval', array_keys($categoryIds));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $row = $db->execute(
            "SELECT COUNT(*) AS cnt FROM {$prefix}posts WHERE category_id IN ({$placeholders}) AND " . cms_post_publication_where(),
            $ids
        )->fetch();

        return $row ? (int) $row->cnt : 0;
    } catch (\Throwable $e) {
        return 0;
    }
}
