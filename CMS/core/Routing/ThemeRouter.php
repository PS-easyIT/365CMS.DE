<?php
/**
 * Theme Router Module
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS\Routing;

use CMS\Api;
use CMS\Database;
use CMS\PageManager;
use CMS\PluginManager;
use CMS\Router;
use CMS\Services;
use CMS\ThemeManager;

if (!defined('ABSPATH')) {
    exit;
}

final class ThemeRouter
{
    public function __construct(private readonly Router $router)
    {
    }

    public function registerRoutes(): void
    {
        $permalinkService = Services\PermalinkService::getInstance();
        $currentPostRoutePattern = $permalinkService->buildPostRoutePattern();

        $this->router->addRoute('GET', '/', [$this, 'renderHome']);
        $this->router->addRoute('GET', '/search', [$this, 'renderSearch']);
        $this->router->addRoute('GET', '/contact', [$this, 'renderContact']);
        $this->router->addRoute('GET', '/kontakt', [$this, 'renderContact']);
        $this->router->addRoute('GET', '/author/:identifier', [$this, 'renderAuthorPage']);
        $this->router->addRoute('GET', '/site-table/export/:id/:format', [$this, 'streamSiteTableExport']);
        $this->router->addRoute('GET', '/blog', [$this, 'renderBlogIndex']);
        $this->router->addRoute('GET', $currentPostRoutePattern, [$this, 'renderBlogSingle']);
        if ($currentPostRoutePattern !== Services\PermalinkService::LEGACY_POST_ROUTE_PATTERN) {
            $this->router->addRoute('GET', Services\PermalinkService::LEGACY_POST_ROUTE_PATTERN, [$this, 'renderLegacyBlogSingle']);
        }
        $this->router->addRoute('GET', '/feed', [$this, 'serveRssFeed']);
        $this->router->addRoute('GET', '/sitemap.xml', [$this, 'serveSitemap']);
        $this->router->addRoute('GET', '/robots.txt', [$this, 'serveRobotsTxt']);
    }

    public function renderHome(): void
    {
        ThemeManager::instance()->render('home');
    }

    public function renderSearch(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $type = (string)($_GET['type'] ?? '');
        $location = trim((string)($_GET['location'] ?? ''));
        $filter = trim((string)($_GET['filter'] ?? ''));

        $results = [];
        $pluginMgr = PluginManager::instance();
        $db = Database::instance();
        $prefix = $db->getPrefix();

        $searchService = Services\SearchService::getInstance();
        $useTNT = $searchService->isAvailable() && $query !== '';

        if ($type === '' || $type === 'pages') {
            if ($useTNT) {
                $tntResult = $searchService->search($query, 'pages', 20, true);
                if (!empty($tntResult['ids'])) {
                    $ids = array_map('intval', $tntResult['ids']);
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("SELECT * FROM {$prefix}pages WHERE id IN ({$ph}) AND status = 'published'");
                    $stmt->execute($ids);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $byId = [];
                    foreach ($rows as $row) {
                        $byId[(int)$row['id']] = $row;
                    }
                    foreach ($ids as $id) {
                        if (!isset($byId[$id])) {
                            continue;
                        }
                        $byId[$id]['_type'] = 'page';
                        $byId[$id]['_type_label'] = 'Seite';
                        $results[] = $byId[$id];
                    }
                }
            } else {
                $pageManager = PageManager::instance();
                $pageResults = $pageManager->search($query);
                foreach ($pageResults as $row) {
                    $item = (array)$row;
                    $item['_type'] = 'page';
                    $item['_type_label'] = 'Seite';
                    $results[] = $item;
                }
            }
        }

        if ($type === '' || $type === 'posts') {
            if ($useTNT) {
                $tntResult = $searchService->search($query, 'posts', 20, true);
                if (!empty($tntResult['ids'])) {
                    $ids = array_map('intval', $tntResult['ids']);
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("SELECT * FROM {$prefix}posts WHERE id IN ({$ph}) AND status = 'published'");
                    $stmt->execute($ids);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $byId = [];
                    foreach ($rows as $row) {
                        $byId[(int)$row['id']] = $row;
                    }
                    foreach ($ids as $id) {
                        if (!isset($byId[$id])) {
                            continue;
                        }
                        $byId[$id]['_type'] = 'post';
                        $byId[$id]['_type_label'] = 'Beitrag';
                        $results[] = $byId[$id];
                    }
                }
            } elseif ($query !== '') {
                $like = '%' . $query . '%';
                $stmt = $db->prepare("SELECT * FROM {$prefix}posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) ORDER BY created_at DESC LIMIT 20");
                $stmt->execute([$like, $like, $like]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['_type'] = 'post';
                    $row['_type_label'] = 'Beitrag';
                    $results[] = $row;
                }
            }
        }

        if (($type === '' || $type === 'experts') && $pluginMgr->isPluginActive('cms-experts')) {
            try {
                $where = ["e.status = 'active'"];
                $params = [];
                if ($query !== '') {
                    $where[] = '(e.name LIKE ? OR e.title LIKE ? OR e.skills LIKE ? OR e.specializations LIKE ?)';
                    $like = '%' . $query . '%';
                    $params = array_merge($params, [$like, $like, $like, $like]);
                }
                if ($location !== '') {
                    $where[] = '(e.location LIKE ? OR e.availability LIKE ?)';
                    $locLike = '%' . $location . '%';
                    $params[] = $locLike;
                    $params[] = $locLike;
                }
                if ($filter !== '') {
                    $where[] = '(e.skills LIKE ? OR e.specializations LIKE ?)';
                    $fLike = '%' . $filter . '%';
                    $params[] = $fLike;
                    $params[] = $fLike;
                }
                $sql = "SELECT e.* FROM {$prefix}experts e WHERE " . implode(' AND ', $where) . ' ORDER BY e.created_at DESC LIMIT 20';
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['_type'] = 'expert';
                    $row['_type_label'] = 'Experte';
                    $row['slug'] = 'experts/' . ($row['id'] ?? 0);
                    $row['title'] = $row['name'] ?? $row['display_name'] ?? 'Experte';
                    $row['meta_description'] = $row['title'] ?? $row['skills'] ?? '';
                    $results[] = $row;
                }
            } catch (\Throwable) {
            }
        }

        if (($type === '' || $type === 'companies') && $pluginMgr->isPluginActive('cms-companies')) {
            try {
                $where = ["c.status = 'active'"];
                $params = [];
                if ($query !== '') {
                    $where[] = '(c.name LIKE ? OR c.description LIKE ? OR c.industry LIKE ?)';
                    $like = '%' . $query . '%';
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($location !== '') {
                    $where[] = '(c.location LIKE ? OR c.city LIKE ?)';
                    $locLike = '%' . $location . '%';
                    $params[] = $locLike;
                    $params[] = $locLike;
                }
                if ($filter !== '') {
                    $where[] = '(c.industry LIKE ? OR c.description LIKE ?)';
                    $fLike = '%' . $filter . '%';
                    $params[] = $fLike;
                    $params[] = $fLike;
                }
                $sql = "SELECT c.* FROM {$prefix}companies c WHERE " . implode(' AND ', $where) . ' ORDER BY c.created_at DESC LIMIT 20';
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['_type'] = 'company';
                    $row['_type_label'] = 'Firma';
                    $row['slug'] = 'companies/' . ($row['id'] ?? 0);
                    $row['title'] = $row['name'] ?? $row['company_name'] ?? 'Firma';
                    $row['meta_description'] = $row['description'] ?? $row['short_description'] ?? '';
                    $results[] = $row;
                }
            } catch (\Throwable) {
            }
        }

        if (($type === '' || $type === 'speakers') && $pluginMgr->isPluginActive('cms-speakers')) {
            try {
                $where = ["s.status = 'active'"];
                $params = [];
                if ($query !== '') {
                    $where[] = '(s.name LIKE ? OR s.bio LIKE ? OR s.expertise LIKE ?)';
                    $like = '%' . $query . '%';
                    $params = array_merge($params, [$like, $like, $like]);
                }
                if ($location !== '') {
                    $where[] = '(s.location LIKE ?)';
                    $params[] = '%' . $location . '%';
                }
                if ($filter !== '') {
                    $where[] = '(s.expertise LIKE ? OR s.topics LIKE ?)';
                    $fLike = '%' . $filter . '%';
                    $params[] = $fLike;
                    $params[] = $fLike;
                }
                $sql = "SELECT s.* FROM {$prefix}event_speakers s WHERE " . implode(' AND ', $where) . ' ORDER BY s.created_at DESC LIMIT 20';
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['_type'] = 'speaker';
                    $row['_type_label'] = 'Speaker';
                    $row['slug'] = 'speakers/' . ($row['id'] ?? 0);
                    $row['title'] = $row['name'] ?? 'Speaker';
                    $row['meta_description'] = $row['bio'] ?? $row['expertise'] ?? '';
                    $results[] = $row;
                }
            } catch (\Throwable) {
            }
        }

        if (($type === '' || $type === 'events') && $pluginMgr->isPluginActive('cms-events')) {
            try {
                $where = ["ev.status = 'active'"];
                $params = [];
                if ($query !== '') {
                    $where[] = '(ev.title LIKE ? OR ev.description LIKE ?)';
                    $like = '%' . $query . '%';
                    $params = array_merge($params, [$like, $like]);
                }
                if ($location !== '') {
                    $where[] = '(ev.location LIKE ? OR ev.venue LIKE ?)';
                    $locLike = '%' . $location . '%';
                    $params[] = $locLike;
                    $params[] = $locLike;
                }
                $sql = "SELECT ev.* FROM {$prefix}events ev WHERE " . implode(' AND ', $where) . ' ORDER BY ev.start_date DESC LIMIT 20';
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $row) {
                    $row['_type'] = 'event';
                    $row['_type_label'] = 'Event';
                    $row['slug'] = 'events/' . ($row['id'] ?? 0);
                    $row['title'] = $row['title'] ?? 'Event';
                    $row['meta_description'] = $row['description'] ?? '';
                    $results[] = $row;
                }
            } catch (\Throwable) {
            }
        }

        ThemeManager::instance()->render('search', [
            'results' => $results,
            'query' => $query,
            'type' => $type,
            'location' => $location,
            'filter' => $filter,
        ]);
    }

    public function renderContact(): void
    {
        $themeManager = ThemeManager::instance();
        $contactTemplate = $themeManager->getThemePath() . 'contact.php';

        if (is_file($contactTemplate)) {
            $themeManager->render('contact');
            return;
        }

        foreach (['contact', 'kontakt'] as $slug) {
            $page = PageManager::instance()->getPageBySlug($slug);
            if ($page === null || ($page['status'] ?? '') !== 'published') {
                continue;
            }

            $locale = $this->router->getRequestLocale();
            $page = Services\ContentLocalizationService::getInstance()->localizePage($page, $locale);
            if (!empty($page['content'])) {
                $page['content'] = $this->router->prepareRenderableContent((string)$page['content'], 'page', (int)($page['id'] ?? 0));
            }

            $themeManager->render('page', ['page' => $page, 'contentLocale' => $locale]);
            return;
        }

        $this->router->render404();
    }

    public function streamSiteTableExport(string $id, string $format): void
    {
        $tableId = (int)$id;
        if ($tableId <= 0 || !Services\SiteTableService::getInstance()->streamExportById($tableId, $format, true)) {
            $this->router->render404();
        }
    }

    public function renderBlogIndex(): void
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 9;
        $offset = ($page - 1) * $perPage;
        $total = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE status = 'published'");
        $posts = $db->get_results(
            "SELECT p.*, c.name AS category_name,
                    COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY p.published_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        ) ?: [];

        ThemeManager::instance()->render('blog', [
            'posts' => $posts,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'perPage' => $perPage,
        ]);
    }

    public function renderAuthorPage(string $identifier): void
    {
        $viewerIsLoggedIn = \CMS\Auth::instance()->isLoggedIn();
        $memberService = Services\MemberService::getInstance();
        $author = $memberService->getPublicAuthorProfile($identifier, $viewerIsLoggedIn);

        if ($author === null) {
            $this->router->render404();
            return;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $authorId = (int)($author['id'] ?? 0);

        $total = (int)$db->get_var(
            "SELECT COUNT(*) FROM {$prefix}posts WHERE author_id = ? AND status = 'published'",
            [$authorId]
        );

        $posts = $db->get_results(
            "SELECT p.*, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.author_id = ? AND p.status = 'published'
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT {$perPage} OFFSET {$offset}",
            [$authorId]
        ) ?: [];

        ThemeManager::instance()->render('author', [
            'author' => $author,
            'posts' => $posts,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'perPage' => $perPage,
        ]);
    }

    public function renderBlogSingle(string ...$segments): void
    {
        $slug = rawurldecode(trim((string)end($segments), '/'));
        if ($slug === '') {
            $this->router->render404();
            return;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $postRow = $db->get_row(
            "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = 'published'",
            [$slug]
        );

        if (!$postRow) {
            if (Services\PermalinkService::getInstance()->usesSlugOnlyStructure() && count($segments) === 1 && $this->renderPageFallback($slug)) {
                return;
            }

            $this->router->render404();
            return;
        }

        $requestContext = $this->router->getRequestContext();
        $requestBaseUri = (string)($requestContext['base_uri'] ?? '');
        $locale = $this->router->getRequestLocale();
        $postData = Services\ContentLocalizationService::getInstance()->localizePost((array)$postRow, $locale);
        $permalinkService = Services\PermalinkService::getInstance();
        $canonicalBasePath = $permalinkService->buildPostPath($postData);
        $canonicalPath = $permalinkService->buildPostPath($postData, $locale);

        if ($requestBaseUri !== '' && $requestBaseUri !== $canonicalBasePath) {
            $query = trim((string)($_SERVER['QUERY_STRING'] ?? ''));
            $target = $canonicalPath . ($query !== '' ? '?' . $query : '');
            $this->router->redirect($target, 301);
            return;
        }

        $post = (object)$postData;
        $db->execute("UPDATE {$prefix}posts SET views = views + 1 WHERE id = ?", [(int)$post->id]);

        if (!empty($post->content)) {
            $post->content = $this->router->prepareRenderableContent((string)$post->content, 'post', (int)($post->id ?? 0));
        }

        if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
            $this->router->streamContentAsPdf(
                htmlspecialchars((string)($post->title ?? 'Beitrag'), ENT_QUOTES, 'UTF-8'),
                (string)$post->content,
                $post->author_name ?? null
            );
            return;
        }

        ThemeManager::instance()->render('blog-single', [
            'post' => $post,
            'contentLocale' => $locale,
        ]);
    }

    public function renderLegacyBlogSingle(string $slug): void
    {
        $this->renderBlogSingle($slug);
    }

    public function serveSitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo Services\SEOService::getInstance()->generateSitemap();
        exit;
    }

    public function serveRssFeed(): void
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();
        $locale = $this->router->getRequestLocale();
        $siteTitle = defined('SITE_NAME') ? (string) SITE_NAME : '365CMS';
        $siteDescription = 'Aktuelle Beiträge von ' . $siteTitle;
        $feedUrl = SITE_URL . '/feed';
        $language = $locale === 'en' ? 'en' : 'de';

        $posts = $db->get_results(
            "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published'
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT 25"
        ) ?: [];

        if (!headers_sent()) {
            header('Content-Type: application/rss+xml; charset=utf-8');
            header('X-Robots-Tag: noindex, follow', true);
        }

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<rss version=\"2.0\">\n";
        echo "<channel>\n";
        echo '  <title>' . $this->escapeXml($siteTitle) . "</title>\n";
        echo '  <link>' . $this->escapeXml(SITE_URL . '/') . "</link>\n";
        echo '  <description>' . $this->escapeXml($siteDescription) . "</description>\n";
        echo '  <language>' . $this->escapeXml($language) . "</language>\n";
        echo '  <atom:link xmlns:atom="http://www.w3.org/2005/Atom" href="' . $this->escapeXml($feedUrl) . '" rel="self" type="application/rss+xml" />' . "\n";
        echo '  <lastBuildDate>' . gmdate(DATE_RSS) . "</lastBuildDate>\n";

        foreach ($posts as $postRow) {
            $postData = Services\ContentLocalizationService::getInstance()->localizePost((array) $postRow, $locale);
            $title = trim((string) ($postData['title'] ?? 'Beitrag'));
            $link = SITE_URL . Services\PermalinkService::getInstance()->buildPostPath($postData, $locale);
            $guid = $link;
            $pubDate = (string) ($postData['published_at'] ?? $postData['created_at'] ?? '');
            $excerpt = trim((string) ($postData['excerpt'] ?? ''));
            $content = trim((string) ($postData['content'] ?? ''));
            $description = $excerpt !== '' ? $excerpt : mb_substr(trim(strip_tags($content)), 0, 320);
            $categoryName = trim((string) ($postData['category_name'] ?? ''));
            $author = trim((string) ($postData['author_name'] ?? ''));

            echo "  <item>\n";
            echo '    <title>' . $this->escapeXml($title) . "</title>\n";
            echo '    <link>' . $this->escapeXml($link) . "</link>\n";
            echo '    <guid isPermaLink="true">' . $this->escapeXml($guid) . "</guid>\n";
            if ($pubDate !== '') {
                echo '    <pubDate>' . $this->escapeXml(gmdate(DATE_RSS, strtotime($pubDate))) . "</pubDate>\n";
            }
            if ($author !== '') {
                echo '    <author>' . $this->escapeXml($author) . "</author>\n";
            }
            if ($categoryName !== '') {
                echo '    <category>' . $this->escapeXml($categoryName) . "</category>\n";
            }
            if ($description !== '') {
                echo '    <description>' . $this->wrapCdata($description) . "</description>\n";
            }
            echo "  </item>\n";
        }

        echo "</channel>\n";
        echo "</rss>";
        exit;
    }

    public function serveRobotsTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo Services\SEOService::getInstance()->generateRobotsTxt();
        exit;
    }

    private function renderPageFallback(string $slug): bool
    {
        $page = PageManager::instance()->getPageBySlug($slug);
        if ($page !== null && ($page['status'] ?? '') === 'published') {
            $locale = $this->router->getRequestLocale();
            $page = Services\ContentLocalizationService::getInstance()->localizePage($page, $locale);
            if (!empty($page['content'])) {
                $page['content'] = $this->router->prepareRenderableContent((string)$page['content'], 'page', (int)($page['id'] ?? 0));
            }

            ThemeManager::instance()->render('page', ['page' => $page, 'contentLocale' => $locale]);
            return true;
        }

        $hubPage = Services\SiteTableService::getInstance()->getHubPageBySlug($slug, $this->router->getRequestLocale());
        if ($hubPage !== null) {
            ThemeManager::instance()->render('page', ['page' => $hubPage, 'contentLocale' => $this->router->getRequestLocale()]);
            return true;
        }

        return false;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function wrapCdata(string $value): string
    {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ']]>';
    }
}
