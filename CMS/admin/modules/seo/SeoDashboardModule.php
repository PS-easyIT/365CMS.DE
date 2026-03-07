<?php
declare(strict_types=1);

/**
 * SeoDashboardModule – SEO-Übersicht und Analyse
 */

if (!defined('ABSPATH')) {
    exit;
}

class SeoDashboardModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $pages = $this->db->get_results(
            "SELECT id, title, slug, status FROM {$this->prefix}pages WHERE status = 'published' ORDER BY title ASC"
        ) ?: [];

        $posts = $this->db->get_results(
            "SELECT id, title, slug, meta_title, meta_description, status FROM {$this->prefix}posts WHERE status = 'published' ORDER BY title ASC"
        ) ?: [];

        $allContent = array_merge(
            array_map(fn($p) => (array)$p + ['meta_title' => '', 'meta_description' => '', 'type' => 'Seite'], $pages),
            array_map(fn($p) => (array)$p + ['type' => 'Beitrag'], $posts)
        );

        // SEO-Analyse
        $issues = [];
        $scores = ['good' => 0, 'warning' => 0, 'bad' => 0];

        foreach ($allContent as &$item) {
            $item['seo_issues'] = [];

            // Meta-Titel
            $metaTitle = $item['meta_title'] ?? '';
            if ($metaTitle === '') {
                $item['seo_issues'][] = ['type' => 'warning', 'msg' => 'Kein Meta-Titel'];
            } elseif (mb_strlen($metaTitle) > 60) {
                $item['seo_issues'][] = ['type' => 'warning', 'msg' => 'Meta-Titel zu lang (' . mb_strlen($metaTitle) . '/60)'];
            }

            // Meta-Beschreibung
            $metaDesc = $item['meta_description'] ?? '';
            if ($metaDesc === '') {
                $item['seo_issues'][] = ['type' => 'bad', 'msg' => 'Keine Meta-Beschreibung'];
            } elseif (mb_strlen($metaDesc) > 160) {
                $item['seo_issues'][] = ['type' => 'warning', 'msg' => 'Meta-Beschreibung zu lang (' . mb_strlen($metaDesc) . '/160)'];
            } elseif (mb_strlen($metaDesc) < 50) {
                $item['seo_issues'][] = ['type' => 'warning', 'msg' => 'Meta-Beschreibung zu kurz'];
            }

            // Slug
            if (empty($item['slug'])) {
                $item['seo_issues'][] = ['type' => 'bad', 'msg' => 'Kein Slug definiert'];
            }

            // Score
            if (empty($item['seo_issues'])) {
                $scores['good']++;
                $item['seo_score'] = 'good';
            } elseif (array_filter($item['seo_issues'], fn($i) => $i['type'] === 'bad')) {
                $scores['bad']++;
                $item['seo_score'] = 'bad';
            } else {
                $scores['warning']++;
                $item['seo_score'] = 'warning';
            }
        }

        // Sitemap-Status
        $sitemapPath = defined('ABSPATH') ? ABSPATH . 'sitemap.xml' : '';
        $sitemapExists = $sitemapPath && file_exists($sitemapPath);
        $sitemapDate   = $sitemapExists ? date('d.m.Y H:i', filemtime($sitemapPath)) : null;

        // Robots.txt
        $robotsPath = defined('ABSPATH') ? ABSPATH . 'robots.txt' : '';
        $robotsExists = $robotsPath && file_exists($robotsPath);

        return [
            'content'        => $allContent,
            'scores'         => $scores,
            'total'          => count($allContent),
            'sitemap_exists' => $sitemapExists,
            'sitemap_date'   => $sitemapDate,
            'robots_exists'  => $robotsExists,
        ];
    }

    public function regenerateSitemap(): array
    {
        $sitemapPath = defined('ABSPATH') ? ABSPATH . 'sitemap.xml' : '';
        if (!$sitemapPath) {
            return ['success' => false, 'error' => 'ABSPATH nicht definiert.'];
        }

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $pages = $this->db->get_results("SELECT slug, updated_at FROM {$this->prefix}pages WHERE status = 'published'") ?: [];
        $posts = $this->db->get_results("SELECT slug, updated_at FROM {$this->prefix}posts WHERE status = 'published'") ?: [];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Startseite
        $xml .= "  <url>\n    <loc>" . htmlspecialchars($siteUrl) . "/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

        foreach ($pages as $p) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($siteUrl . '/' . $p->slug) . "</loc>\n";
            if ($p->updated_at) {
                $xml .= "    <lastmod>" . date('Y-m-d', strtotime($p->updated_at)) . "</lastmod>\n";
            }
            $xml .= "    <changefreq>weekly</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
        }

        foreach ($posts as $p) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($siteUrl . '/blog/' . $p->slug) . "</loc>\n";
            if ($p->updated_at) {
                $xml .= "    <lastmod>" . date('Y-m-d', strtotime($p->updated_at)) . "</lastmod>\n";
            }
            $xml .= "    <changefreq>monthly</changefreq>\n    <priority>0.6</priority>\n  </url>\n";
        }

        $xml .= "</urlset>\n";

        if (file_put_contents($sitemapPath, $xml) !== false) {
            return ['success' => true, 'message' => 'Sitemap neu generiert (' . (count($pages) + count($posts) + 1) . ' URLs).'];
        }
        return ['success' => false, 'error' => 'Sitemap konnte nicht geschrieben werden.'];
    }
}
