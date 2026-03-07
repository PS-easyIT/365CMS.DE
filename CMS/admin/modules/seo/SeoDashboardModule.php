<?php
declare(strict_types=1);

/**
 * SeoDashboardModule – SEO-Übersicht und Analyse
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\SEOService;
use CMS\Services\SeoAnalysisService;

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
        $allContent = SeoAnalysisService::getInstance()->enrichAuditRows(SEOService::getInstance()->getAuditRows());
        $scores = ['good' => 0, 'warning' => 0, 'bad' => 0];

        foreach ($allContent as &$item) {
            $status = (string)($item['analysis']['status'] ?? 'warning');
            if (!isset($scores[$status])) {
                $status = 'warning';
            }

            $item['seo_score'] = $status;
            $item['seo_score_value'] = (int)($item['analysis']['score'] ?? 0);
            $item['seo_issues'] = array_map(static function (array $rule): array {
                return [
                    'type' => !empty($rule['passed']) ? 'good' : 'warning',
                    'msg' => (string)($rule['label'] ?? ''),
                    'detail' => (string)($rule['message'] ?? ''),
                ];
            }, array_filter($item['analysis']['rules'] ?? [], static fn(array $rule): bool => empty($rule['passed'])));
            $scores[$status]++;
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
            'template_settings' => SeoAnalysisService::getInstance()->getSettings(),
            'sitemap_settings' => SEOService::getInstance()->getSitemapSettings(),
        ];
    }

    public function regenerateSitemap(): array
    {
        if (SEOService::getInstance()->saveSitemap()) {
            return ['success' => true, 'message' => 'Sitemap neu generiert und Suchmaschinen optional benachrichtigt.'];
        }

        return ['success' => false, 'error' => 'Sitemap konnte nicht geschrieben werden.'];
    }

    public function saveMetaTemplates(array $post): array
    {
        $this->persistSettings([
            'seo_site_title_format' => trim((string)($post['site_title_format'] ?? '%%title%% %%sep%% %%sitename%%')),
            'seo_title_separator' => trim((string)($post['title_separator'] ?? '|')),
            'seo_analysis_min_words' => (string)max(100, (int)($post['analysis_min_words'] ?? 300)),
            'seo_analysis_sentence_words' => (string)max(12, (int)($post['analysis_sentence_words'] ?? 24)),
            'seo_analysis_paragraph_words' => (string)max(40, (int)($post['analysis_paragraph_words'] ?? 120)),
        ]);

        return ['success' => true, 'message' => 'Meta-Vorlagen und Analyse-Schwellen gespeichert.'];
    }

    public function saveSitemapSettings(array $post): array
    {
        $this->persistSettings([
            'seo_sitemap_pages_priority' => (string)($post['pages_priority'] ?? '0.8'),
            'seo_sitemap_pages_changefreq' => (string)($post['pages_changefreq'] ?? 'weekly'),
            'seo_sitemap_posts_priority' => (string)($post['posts_priority'] ?? '0.6'),
            'seo_sitemap_posts_changefreq' => (string)($post['posts_changefreq'] ?? 'monthly'),
            'seo_sitemap_ping_google' => !empty($post['ping_google']) ? '1' : '0',
            'seo_sitemap_ping_bing' => !empty($post['ping_bing']) ? '1' : '0',
        ]);

        return ['success' => true, 'message' => 'Sitemap-Einstellungen gespeichert.'];
    }

    public function saveAuditItem(array $post): array
    {
        $contentType = (string)($post['content_type'] ?? '');
        $id = (int)($post['content_id'] ?? 0);
        if (!in_array($contentType, ['page', 'post'], true) || $id <= 0) {
            return ['success' => false, 'error' => 'Ungültiger Inhalt für das SEO-Audit.'];
        }

        $table = $contentType === 'page' ? 'pages' : 'posts';
        $this->db->execute(
            "UPDATE {$this->prefix}{$table} SET meta_title = ?, meta_description = ? WHERE id = ?",
            [trim((string)($post['meta_title'] ?? '')), trim((string)($post['meta_description'] ?? '')), $id]
        );
        SEOService::getInstance()->saveContentMeta($contentType, $id, [
            'focus_keyphrase' => (string)($post['focus_keyphrase'] ?? ''),
        ]);

        return ['success' => true, 'message' => 'SEO-Audit-Eintrag aktualisiert.'];
    }

    private function persistSettings(array $values): void
    {
        foreach ($values as $key => $value) {
            $exists = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?",
                [$key]
            ) > 0;

            if ($exists) {
                $this->db->execute(
                    "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                    [(string)$value, (string)$key]
                );
                continue;
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)",
                [(string)$key, (string)$value]
            );
        }
    }
}
