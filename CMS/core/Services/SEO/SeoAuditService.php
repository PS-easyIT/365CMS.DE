<?php
declare(strict_types=1);

namespace CMS\Services\SEO;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoAuditService
{
    public function __construct(
        private readonly Database $db,
        private readonly string $prefix
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAuditRows(): array
    {
        $pages = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.content, p.featured_image, p.meta_title, p.meta_description, p.status,
                    p.updated_at, p.created_at,
                    sm.canonical_url, sm.robots_index, sm.robots_follow, sm.og_title, sm.og_description,
                    sm.og_image, sm.og_type, sm.twitter_card, sm.twitter_title, sm.twitter_description,
                    sm.twitter_image, sm.focus_keyphrase, sm.schema_type, sm.sitemap_priority, sm.sitemap_changefreq,
                    sm.hreflang_group
             FROM {$this->prefix}pages p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'page' AND sm.content_id = p.id
             WHERE p.status IN ('published', 'draft', 'private')
             ORDER BY p.updated_at DESC"
        ) ?: [];

        $posts = $this->db->get_results(
            "SELECT p.id, p.title, p.slug, p.content, p.excerpt, p.featured_image, p.meta_title, p.meta_description, p.status,
                    p.updated_at, p.created_at,
                    sm.canonical_url, sm.robots_index, sm.robots_follow, sm.og_title, sm.og_description,
                    sm.og_image, sm.og_type, sm.twitter_card, sm.twitter_title, sm.twitter_description,
                    sm.twitter_image, sm.focus_keyphrase, sm.schema_type, sm.sitemap_priority, sm.sitemap_changefreq,
                    sm.hreflang_group
             FROM {$this->prefix}posts p
             LEFT JOIN {$this->prefix}seo_meta sm ON sm.content_type = 'post' AND sm.content_id = p.id
             WHERE p.status IN ('published', 'draft')
             ORDER BY p.updated_at DESC"
        ) ?: [];

        return array_merge($this->mapRows($pages, 'page'), $this->mapRows($posts, 'post'));
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapRows(array $rows, string $type): array
    {
        return array_map(static function (object $row) use ($type): array {
            return [
                'type' => $type,
                'id' => (int) ($row->id ?? 0),
                'title' => (string) ($row->title ?? ''),
                'slug' => (string) ($row->slug ?? ''),
                'status' => (string) ($row->status ?? ''),
                'content' => (string) ($row->content ?? ''),
                'excerpt' => (string) ($row->excerpt ?? ''),
                'featured_image' => (string) ($row->featured_image ?? ''),
                'meta_title' => (string) ($row->meta_title ?? ''),
                'meta_description' => (string) ($row->meta_description ?? ''),
                'canonical_url' => (string) ($row->canonical_url ?? ''),
                'robots_index' => (int) ($row->robots_index ?? 1) === 1,
                'robots_follow' => (int) ($row->robots_follow ?? 1) === 1,
                'og_title' => (string) ($row->og_title ?? ''),
                'og_description' => (string) ($row->og_description ?? ''),
                'og_image' => (string) ($row->og_image ?? ''),
                'og_type' => (string) ($row->og_type ?? ''),
                'twitter_card' => (string) ($row->twitter_card ?? ''),
                'twitter_title' => (string) ($row->twitter_title ?? ''),
                'twitter_description' => (string) ($row->twitter_description ?? ''),
                'twitter_image' => (string) ($row->twitter_image ?? ''),
                'focus_keyphrase' => (string) ($row->focus_keyphrase ?? ''),
                'schema_type' => (string) ($row->schema_type ?? ''),
                'sitemap_priority' => (string) ($row->sitemap_priority ?? ''),
                'sitemap_changefreq' => (string) ($row->sitemap_changefreq ?? ''),
                'hreflang_group' => (string) ($row->hreflang_group ?? ''),
                'updated_at' => (string) ($row->updated_at ?? ''),
                'created_at' => (string) ($row->created_at ?? ''),
            ];
        }, $rows);
    }
}
