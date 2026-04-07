<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class PostsCategoryViewModelBuilder
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function buildOrderedCategoryOptions(array $rows): array
    {
        return array_map(static function (array $row): array {
            $row['option_label'] = (string) ($row['option_label'] ?? $row['name'] ?? '');
            return $row;
        }, $this->buildCategoryTreeRows($rows, false));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function buildAdminCategoryRows(array $rows): array
    {
        return $this->buildCategoryTreeRows($rows, true);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function buildCategoryTreeRows(array $rows, bool $includeAdminMeta): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $row['id'] = $id;
            $row['parent_id'] = (int) ($row['parent_id'] ?? 0);
            $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
            $row['replacement_category_id'] = (int) ($row['replacement_category_id'] ?? 0);
            $row['post_count_direct'] = (int) ($row['assigned_post_count'] ?? $row['post_count'] ?? 0);
            $row['domains'] = $this->decodeCategoryDomains((string) ($row['alias_domains_json'] ?? ''));
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
        $walker = function (int $parentId, int $depth) use (&$walker, &$flat, $byParent, $byId, $includeAdminMeta): int {
            $branchTotal = 0;

            foreach ($byParent[$parentId] ?? [] as $categoryId) {
                if (!isset($byId[$categoryId])) {
                    continue;
                }

                $row = $byId[$categoryId];
                $row['depth'] = $depth;
                $row['option_label'] = str_repeat('— ', $depth) . (string) ($row['name'] ?? '');
                $row['is_main_category'] = $depth === 0;
                $row['parent_name'] = '';

                if ((int) ($row['parent_id'] ?? 0) > 0 && isset($byId[(int) $row['parent_id']])) {
                    $row['parent_name'] = (string) ($byId[(int) $row['parent_id']]['name'] ?? '');
                }

                $replacementId = (int) ($row['replacement_category_id'] ?? 0);
                $row['replacement_category_name'] = '';
                if ($replacementId > 0 && isset($byId[$replacementId])) {
                    $row['replacement_category_name'] = (string) ($byId[$replacementId]['name'] ?? '');
                }

                $index = count($flat);
                $flat[] = $row;
                $childrenTotal = $walker($categoryId, $depth + 1);
                $row['post_count_total'] = (int) ($row['post_count_direct'] ?? 0) + $childrenTotal;

                if (!$includeAdminMeta) {
                    unset($row['domains']);
                    unset($row['post_count_direct']);
                    unset($row['post_count_total']);
                    unset($row['parent_name']);
                    unset($row['is_main_category']);
                    unset($row['replacement_category_id']);
                    unset($row['replacement_category_name']);
                }

                $flat[$index] = $row;
                $branchTotal += (int) ($row['post_count_total'] ?? ($row['post_count_direct'] ?? 0));
            }

            return $branchTotal;
        };

        $walker(0, 0);

        return $flat;
    }

    /**
     * @return array<int,string>
     */
    private function decodeCategoryDomains(string $json): array
    {
        $decoded = \CMS\Json::decodeArray($json !== '' ? $json : '[]', []);
        if (!is_array($decoded)) {
            return [];
        }

        $domains = [];
        foreach ($decoded as $candidate) {
            $host = $this->normalizeDomainHost((string) $candidate);
            if ($host !== '') {
                $domains[] = $host;
            }
        }

        return array_values(array_unique($domains));
    }

    private function normalizeDomainHost(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $candidate = preg_match('#^https?://#i', $value) === 1 ? $value : 'https://' . ltrim($value, '/');
        $parts = parse_url($candidate);
        if ($parts === false) {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '.'));
        if ($host === '') {
            return '';
        }

        if (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/') {
            return '';
        }

        if (isset($parts['query']) || isset($parts['fragment'])) {
            return '';
        }

        if (!preg_match('/^(?=.{1,253}$)(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.(?:xn--)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $host)) {
            return '';
        }

        return $host;
    }
}
