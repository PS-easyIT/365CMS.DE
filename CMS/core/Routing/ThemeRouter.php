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
use CMS\Json;
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
        $this->router->addRoute('GET', '/sitemap', [$this, 'renderHtmlSitemap']);
        $this->router->addRoute('GET', '/search', [$this, 'renderSearch']);
        $this->router->addRoute('GET', '/contact', [$this, 'renderContact']);
        $this->router->addRoute('GET', '/kontakt', [$this, 'renderContact']);
        $this->router->addRoute('GET', '/autoren', [$this, 'renderAuthorsIndex']);
        $this->router->addRoute('GET', '/authors', [$this, 'renderAuthorsIndex']);
        $this->router->addRoute('GET', '/author/:identifier', [$this, 'renderAuthorPage']);
        $this->router->addRoute('GET', '/kategorie/:slug', [$this, 'renderCategoryArchive']);
        $this->router->addRoute('GET', '/tag/:slug', [$this, 'renderTagArchive']);
        $this->router->addRoute('GET', '/site-table/export/:id/:format', [$this, 'streamSiteTableExport']);
        $this->router->addRoute('GET', '/blog', [$this, 'renderBlogIndex']);
        $this->router->addRoute('GET', $currentPostRoutePattern, [$this, 'renderBlogSingle']);
        if ($currentPostRoutePattern !== Services\PermalinkService::LEGACY_POST_ROUTE_PATTERN) {
            $this->router->addRoute('GET', Services\PermalinkService::LEGACY_POST_ROUTE_PATTERN, [$this, 'renderLegacyBlogSingle']);
        }
        $this->router->addRoute('GET', '/feed', [$this, 'serveRssFeed']);
        $this->router->addRoute('GET', '/sitemap.xml', [$this, 'serveSitemap']);
        $this->router->addRoute('GET', '/robots.txt', [$this, 'serveRobotsTxt']);
        $this->router->addRoute('GET', '/security.txt', [$this, 'serveSecurityTxt']);
        $this->router->addRoute('GET', '/.well-known/security.txt', [$this, 'serveSecurityTxt']);
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
        $contentLocale = $this->getResolvedContentLocale();

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
                        if (!$this->postMatchesLocaleAvailability($row, $contentLocale)) {
                            continue;
                        }
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
                $localeFilter = $this->buildPostLocaleAvailabilityExpression('p', $contentLocale);
                if ($contentLocale === 'en') {
                    $stmt = $db->prepare(
                        "SELECT * FROM {$prefix}posts p
                         WHERE p.status = 'published'
                           AND {$localeFilter}
                           AND (
                               COALESCE(NULLIF(p.title_en, ''), p.title) LIKE ?
                               OR COALESCE(NULLIF(p.content_en, ''), p.content) LIKE ?
                               OR COALESCE(NULLIF(p.excerpt_en, ''), p.excerpt) LIKE ?
                           )
                         ORDER BY created_at DESC LIMIT 20"
                    );
                    $stmt->execute([$like, $like, $like]);
                } else {
                    $stmt = $db->prepare(
                        "SELECT * FROM {$prefix}posts p
                         WHERE p.status = 'published'
                           AND {$localeFilter}
                           AND (p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)
                         ORDER BY created_at DESC LIMIT 20"
                    );
                    $stmt->execute([$like, $like, $like]);
                }
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
            $page = PageManager::instance()->getPageBySlug($slug, $locale);
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
        $locale = $this->getResolvedContentLocale();
        $localeFilter = $this->buildPostLocaleAvailabilityExpression('p', $locale);
        $page = max(1, (int)($_GET['p'] ?? 1));
        $perPage = 9;
        $offset = ($page - 1) * $perPage;
        $total = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts p WHERE p.status = 'published' AND {$localeFilter}");
        $posts = $db->get_results(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.status = 'published' AND {$localeFilter}
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

    public function renderCategoryArchive(string $slug): void
    {
        $slug = trim(rawurldecode($slug));
        if ($slug === '') {
            $this->router->render404();
            return;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $locale = $this->getResolvedContentLocale();
        $category = $db->get_row(
            "SELECT id, name, slug, description, parent_id
             FROM {$prefix}post_categories
             WHERE slug = ?
             LIMIT 1",
            [$slug]
        );

        if ($category === null) {
            $this->router->render404();
            return;
        }

        $query = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? $_GET['p'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $categoryIds = $this->getCategoryArchiveIds((int) ($category->id ?? 0));
        if ($categoryIds === []) {
            $categoryIds = [(int) ($category->id ?? 0)];
        }

        $categoryPlaceholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $categoryMatchSql = "(
            p.category_id IN ({$categoryPlaceholders})
            OR EXISTS (
                SELECT 1
                FROM {$prefix}post_category_rel pcr
                WHERE pcr.post_id = p.id
                  AND pcr.category_id IN ({$categoryPlaceholders})
            )
        )";
        $where = ["p.status = 'published'", $this->buildPostLocaleAvailabilityExpression('p', $locale), $categoryMatchSql];
        $params = array_merge($categoryIds, $categoryIds);

        if ($query !== '') {
            $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
            $like = '%' . $query . '%';
            array_push($params, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $db->get_var(
            "SELECT COUNT(*)
             FROM {$prefix}posts p
             WHERE {$whereSql}",
            $params
        );

        $posts = $db->get_results(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                    COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE {$whereSql}
             ORDER BY COALESCE(p.published_at, p.created_at) DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        ) ?: [];

        ThemeManager::instance()->render('category', [
            'category' => (array) $category,
            'posts' => $posts,
            'query' => $query,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'perPage' => $perPage,
        ]);
    }

    public function renderTagArchive(string $slug): void
    {
        $normalizedSlug = $this->normalizeArchiveSlug(rawurldecode($slug));
        if ($normalizedSlug === '') {
            $this->router->render404();
            return;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $locale = $this->getResolvedContentLocale();
        $query = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? $_GET['p'] ?? 1));
        $perPage = 10;

        $tagRow = $db->get_row(
            "SELECT id, name, slug
             FROM {$prefix}post_tags
             WHERE slug = ?
             LIMIT 1",
            [$normalizedSlug]
        );

        if ($tagRow !== null) {
            $where = ["p.status = 'published'", $this->buildPostLocaleAvailabilityExpression('p', $locale), 'ptr.tag_id = ?'];
            $params = [(int) ($tagRow->id ?? 0)];

            if ($query !== '') {
                $where[] = '(p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)';
                $like = '%' . $query . '%';
                array_push($params, $like, $like, $like);
            }

            $whereSql = implode(' AND ', $where);
            $total = (int) $db->get_var(
                "SELECT COUNT(DISTINCT p.id)
                 FROM {$prefix}posts p
                 INNER JOIN {$prefix}post_tag_rel ptr ON ptr.post_id = p.id
                 WHERE {$whereSql}",
                $params
            );

            $totalPages = max(1, (int) ceil($total / $perPage));
            $page = min($page, $totalPages);
            $offset = ($page - 1) * $perPage;

            $posts = $db->get_results(
                "SELECT DISTINCT p.*, c.name AS category_name, c.slug AS category_slug,
                        COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
                 FROM {$prefix}posts p
                 INNER JOIN {$prefix}post_tag_rel ptr ON ptr.post_id = p.id
                 LEFT JOIN {$prefix}users u ON u.id = p.author_id
                 LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                 WHERE {$whereSql}
                 ORDER BY COALESCE(p.published_at, p.created_at) DESC
                 LIMIT {$perPage} OFFSET {$offset}",
                $params
            ) ?: [];

            ThemeManager::instance()->render('tag', [
                'tag' => [
                    'name' => (string) ($tagRow->name ?? str_replace('-', ' ', $normalizedSlug)),
                    'slug' => (string) ($tagRow->slug ?? $normalizedSlug),
                ],
                'posts' => $posts,
                'query' => $query,
                'total' => $total,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'perPage' => $perPage,
            ]);
            return;
        }

        $rows = $db->get_results(
            "SELECT p.*, c.name AS category_name,
                    COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
               WHERE p.status = 'published' AND " . $this->buildPostLocaleAvailabilityExpression('p', $locale) . " AND p.tags IS NOT NULL AND p.tags != ''
             ORDER BY COALESCE(p.published_at, p.created_at) DESC"
        ) ?: [];

        $allMatchingPosts = [];
        $matchingPosts = [];
        $resolvedTagName = '';

        foreach ($rows as $row) {
            $post = (array) $row;
            foreach ($this->parsePostTags((string) ($post['tags'] ?? '')) as $tag) {
                if (($tag['slug'] ?? '') !== $normalizedSlug) {
                    continue;
                }

                if ($resolvedTagName === '') {
                    $resolvedTagName = (string) ($tag['name'] ?? '');
                }

                $allMatchingPosts[] = (object) $post;

                if ($this->matchesArchiveSearch($post, $query)) {
                    $matchingPosts[] = (object) $post;
                }

                break;
            }
        }

        if ($allMatchingPosts === []) {
            $this->router->render404();
            return;
        }

        $total = count($matchingPosts);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $posts = array_slice($matchingPosts, $offset, $perPage);

        ThemeManager::instance()->render('tag', [
            'tag' => [
                'name' => $resolvedTagName !== '' ? $resolvedTagName : str_replace('-', ' ', $normalizedSlug),
                'slug' => $normalizedSlug,
            ],
            'posts' => $posts,
            'query' => $query,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
        ]);
    }

    public function renderAuthorsIndex(): void
    {
        ThemeManager::instance()->render('authors');
    }

    public function renderHtmlSitemap(): void
    {
        ThemeManager::instance()->render('sitemap');
    }

    public function renderAuthorPage(string $identifier): void
    {
        $viewerIsLoggedIn = \CMS\Auth::instance()->isLoggedIn();
        $author = $this->resolveAuthorPageProfile($identifier, $viewerIsLoggedIn);

        if ($author === null) {
            $this->router->render404();
            return;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();
        $locale = $this->getResolvedContentLocale();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $authorId = (int)($author['id'] ?? 0);
        $localeFilter = $this->buildPostLocaleAvailabilityExpression('p', $locale);

        $total = (int)$db->get_var(
            "SELECT COUNT(*) FROM {$prefix}posts p WHERE p.author_id = ? AND p.status = 'published' AND {$localeFilter}",
            [$authorId]
        );

        $posts = $db->get_results(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE p.author_id = ? AND p.status = 'published' AND {$localeFilter}
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

    /**
     * @return array<string,mixed>|null
     */
    private function resolveAuthorPageProfile(string $identifier, bool $viewerIsLoggedIn): ?array
    {
        $memberService = Services\MemberService::getInstance();
        $author = $memberService->getPublicAuthorProfile($identifier, $viewerIsLoggedIn);
        if ($author !== null) {
            return $author;
        }

        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $userId = 0;
        if (preg_match('/^user-(\d+)$/', $identifier, $matches) === 1) {
            $userId = (int) ($matches[1] ?? 0);
        } elseif (ctype_digit($identifier)) {
            $userId = (int) $identifier;
        }

        $db = Database::instance();
        $prefix = $db->getPrefix();

        $user = $userId > 0
            ? $db->get_row(
                "SELECT id, username, display_name, status
                 FROM {$prefix}users
                 WHERE id = ?
                 LIMIT 1",
                [$userId]
            )
            : $db->get_row(
                "SELECT id, username, display_name, status
                 FROM {$prefix}users
                 WHERE username = ?
                 LIMIT 1",
                [$identifier]
            );

        if ($user === null || (string) ($user->status ?? 'active') === 'banned') {
            return null;
        }

        $resolvedUserId = (int) ($user->id ?? 0);
        if ($resolvedUserId <= 0) {
            return null;
        }

        $publishedPosts = (int) $db->get_var(
            "SELECT COUNT(*)
             FROM {$prefix}posts
             WHERE author_id = ? AND status = 'published'",
            [$resolvedUserId]
        );

        if ($publishedPosts <= 0) {
            return null;
        }

        $displayName = trim((string) ($user->display_name ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($user->username ?? 'Autor'));
        }

        return [
            'id' => $resolvedUserId,
            'slug' => 'user-' . $resolvedUserId,
            'username' => (string) ($user->username ?? ''),
            'display_name' => $displayName !== '' ? $displayName : 'Autor',
            'bio' => '',
            'avatar_url' => '',
            'details' => [],
            'profile_visibility' => 'public',
            'show_activity' => true,
            'profile_url' => $memberService->buildPublicAuthorPath($resolvedUserId),
        ];
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
        $locale = $this->router->getRequestLocale();
        $localeAvailability = $this->buildPostLocaleAvailabilityExpression('p', $locale);
        $slugField = $locale === 'en' ? '(p.slug_en = ? OR p.slug = ?)' : 'p.slug = ?';
        $slugParams = $locale === 'en' ? [$slug, $slug] : [$slug];
        $postRow = $db->get_row(
            "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             WHERE {$slugField} AND p.status = 'published' AND {$localeAvailability}",
            $slugParams
        );

        if (!$postRow) {
            if ($locale === 'de') {
                $englishAvailability = $this->buildPostLocaleAvailabilityExpression('p', 'en');
                $englishRow = $db->get_row(
                    "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name, c.slug AS category_slug
                     FROM {$prefix}posts p
                     LEFT JOIN {$prefix}users u ON u.id = p.author_id
                     LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                     WHERE (p.slug_en = ? OR p.slug = ?) AND p.status = 'published' AND {$englishAvailability}",
                    [$slug, $slug]
                );

                if ($englishRow) {
                    $englishPost = Services\ContentLocalizationService::getInstance()->localizePost((array) $englishRow, 'en');
                    $this->router->redirect(Services\PermalinkService::getInstance()->buildPostPath($englishPost, 'en'), 301);
                    return;
                }
            }

            if (Services\PermalinkService::getInstance()->usesSlugOnlyStructure() && count($segments) === 1 && $this->renderPageFallback($slug)) {
                return;
            }

            $this->router->render404();
            return;
        }

        $requestContext = $this->router->getRequestContext();
        $requestBaseUri = (string)($requestContext['base_uri'] ?? '');
        $postData = Services\ContentLocalizationService::getInstance()->localizePost((array)$postRow, $locale);
        $permalinkService = Services\PermalinkService::getInstance();
        $canonicalBasePath = $permalinkService->buildPostPath($postData);
        $canonicalPath = $permalinkService->buildPostPath($postData, $locale);
        $localizedCanonicalContext = Services\ContentLocalizationService::getInstance()->resolveRequestContext($canonicalPath);
        $expectedRequestBasePath = $locale === 'de'
            ? $canonicalBasePath
            : (string) ($localizedCanonicalContext['base_uri'] ?? $canonicalBasePath);

        if ($requestBaseUri !== '' && $requestBaseUri !== $expectedRequestBasePath) {
            $query = trim((string)($_SERVER['QUERY_STRING'] ?? ''));
            $target = $canonicalPath . ($query !== '' ? '?' . $query : '');
            $this->router->redirect($target, 301);
            return;
        }

        $postData = $this->attachLegacyCompatibleTagsToPost($postData);

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
        $localeFilter = $this->buildPostLocaleAvailabilityExpression('p', $locale);
        $siteTitle = defined('SITE_NAME') ? (string) SITE_NAME : '365CMS';
        $siteDescription = 'Aktuelle Beiträge von ' . $siteTitle;
        $feedUrl = SITE_URL . '/feed';
        $language = $locale === 'en' ? 'en' : 'de';

        $posts = $db->get_results(
            "SELECT p.*, COALESCE(NULLIF(p.author_display_name, ''), NULLIF(u.display_name, ''), NULLIF(u.username, ''), 'Autor') AS author_name, c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
               WHERE p.status = 'published' AND {$localeFilter}
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
            $description = $this->buildFeedDescription($excerpt, $content);
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

    public function serveSecurityTxt(): void
    {
        $canonicalUrl = rtrim(SITE_URL, '/') . '/.well-known/security.txt';
        $contactEmail = $this->resolveSecurityContactEmail();
        $expires = gmdate('Y-m-d\TH:i:s\Z', strtotime('+180 days'));

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        $lines = [
            'Contact: mailto:' . $contactEmail,
            'Canonical: ' . $canonicalUrl,
            'Preferred-Languages: de, en',
            'Expires: ' . $expires,
        ];

        echo implode("\n", $lines) . "\n";
        exit;
    }

    private function renderPageFallback(string $slug): bool
    {
        $hubPage = Services\SiteTableService::getInstance()->getHubPageBySlug($slug, $this->router->getRequestLocale());
        if ($hubPage !== null) {
            ThemeManager::instance()->render('page', ['page' => $hubPage, 'contentLocale' => $this->router->getRequestLocale()]);
            return true;
        }

        $page = PageManager::instance()->getPageBySlug($slug, $this->router->getRequestLocale());
        if ($page !== null && ($page['status'] ?? '') === 'published') {
            $locale = $this->router->getRequestLocale();
            $page = Services\ContentLocalizationService::getInstance()->localizePage($page, $locale);
            if (!empty($page['content'])) {
                $page['content'] = $this->router->prepareRenderableContent((string)$page['content'], 'page', (int)($page['id'] ?? 0));
            }

            ThemeManager::instance()->render('page', ['page' => $page, 'contentLocale' => $locale]);
            return true;
        }

        return false;
    }

    private function normalizeArchiveSlug(string $value): string
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
     * @return array<int,int>
     */
    private function getCategoryArchiveIds(int $categoryId): array
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
     * @param array<string,mixed> $postData
     * @return array<string,mixed>
     */
    private function attachLegacyCompatibleTagsToPost(array $postData): array
    {
        $postId = (int) ($postData['id'] ?? 0);
        if ($postId <= 0) {
            return $postData;
        }

        $tagRows = $this->getPostTagRows($postId);
        if ($tagRows !== []) {
            $postData['tags'] = implode(', ', array_map(
                static fn(array $tag): string => (string) ($tag['name'] ?? ''),
                $tagRows
            ));
            $postData['tag_items'] = $tagRows;
            return $postData;
        }

        $legacyTags = $this->parsePostTags((string) ($postData['tags'] ?? ''));
        if ($legacyTags !== []) {
            $postData['tag_items'] = $legacyTags;
        }

        return $postData;
    }

    /**
     * @return array<int,array{name:string,slug:string}>
     */
    private function getPostTagRows(int $postId): array
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

    /**
     * @return array<int,array{name:string,slug:string}>
     */
    private function parsePostTags(string $rawTags): array
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

    private function matchesArchiveSearch(array $post, string $query): bool
    {
        if ($query === '') {
            return true;
        }

        $haystack = mb_strtolower(
            trim((string) ($post['title'] ?? '')) . ' ' . trim((string) ($post['excerpt'] ?? '')) . ' ' . trim((string) ($post['content'] ?? '')),
            'UTF-8'
        );

        return str_contains($haystack, mb_strtolower($query, 'UTF-8'));
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function wrapCdata(string $value): string
    {
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $value) . ']]>';
    }

    private function buildFeedDescription(string $excerpt, string $content): string
    {
        $sources = array_values(array_filter([$excerpt, $content], static fn(string $value): bool => trim($value) !== ''));
        if ($sources === []) {
            return '';
        }

        foreach ($sources as $source) {
            $plainText = $this->extractFeedPlainText($source);
            if ($plainText !== '') {
                return mb_substr($plainText, 0, 320);
            }
        }

        return '';
    }

    private function extractFeedPlainText(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return '';
        }

        $rendered = Services\EditorService::getInstance()->renderContent($source);
        $plainText = $this->normalizeFeedPlainText($rendered);

        if ($plainText !== '' && !$this->looksLikeEditorJsPayload($plainText)) {
            return $plainText;
        }

        $editorJsText = $this->extractEditorJsSnippet($source);
        if ($editorJsText !== '') {
            return $editorJsText;
        }

        if ($source !== $rendered) {
            $plainText = $this->normalizeFeedPlainText($source);
        }

        return $this->looksLikeEditorJsPayload($plainText) ? '' : $plainText;
    }

    private function normalizeFeedPlainText(string $value): string
    {
        $plainText = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plainText = preg_replace('/\s+/u', ' ', $plainText) ?? '';

        return trim($plainText);
    }

    private function looksLikeEditorJsPayload(string $value): bool
    {
        $normalized = ltrim($value);

        return str_starts_with($normalized, '{"time":')
            || str_starts_with($normalized, '{"blocks":')
            || str_starts_with($normalized, '{"id":')
            || str_contains($normalized, '"blocks":[{');
    }

    private function extractEditorJsSnippet(string $source): string
    {
        $decoded = Json::decodeArray($source, []);
        if ($decoded !== [] && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
            $collected = [];
            foreach ($decoded['blocks'] as $block) {
                if (!is_array($block)) {
                    continue;
                }

                $text = trim((string) ($block['data']['text'] ?? $block['data']['html'] ?? ''));
                if ($text !== '') {
                    $collected[] = $this->normalizeFeedPlainText($text);
                }

                if (count($collected) >= 3) {
                    break;
                }
            }

            return trim(implode(' ', array_filter($collected, static fn(string $value): bool => $value !== '')));
        }

        if (preg_match_all('/"(?:text|html)"\s*:\s*"((?:\\.|[^"\\\\])*)"/u', $source, $matches) === 1 || (isset($matches[1]) && $matches[1] !== [])) {
            $parts = [];
            foreach ($matches[1] as $rawMatch) {
                $decodedMatch = json_decode('"' . $rawMatch . '"', true);
                $text = is_string($decodedMatch) ? $decodedMatch : stripcslashes($rawMatch);
                $text = $this->normalizeFeedPlainText($text);
                if ($text !== '') {
                    $parts[] = $text;
                }
                if (count($parts) >= 3) {
                    break;
                }
            }

            return trim(implode(' ', $parts));
        }

        return '';
    }

    private function resolveSecurityContactEmail(): string
    {
        if (defined('ADMIN_EMAIL')) {
            $adminEmail = trim((string) ADMIN_EMAIL);
            if ($adminEmail !== '') {
                return $adminEmail;
            }
        }

        $host = (string) (parse_url(SITE_URL, PHP_URL_HOST) ?: 'localhost');

        return 'security@' . preg_replace('/^www\./i', '', $host);
    }

    private function getResolvedContentLocale(): string
    {
        $locale = Services\ContentLocalizationService::getInstance()->normalizeLocale($this->router->getRequestLocale());

        return $locale !== '' ? $locale : 'de';
    }

    private function buildPostLocaleAvailabilityExpression(string $alias, string $locale): string
    {
        $localization = Services\ContentLocalizationService::getInstance();
        $locale = $localization->normalizeLocale($locale);
        $baseContent = $this->buildBasePostContentExpression($alias);
        $englishLegacyOnly = $this->buildLegacyEnglishOnlyPostExpression($alias);

        if ($locale === '' || $locale === 'de') {
            return "{$baseContent} AND NOT {$englishLegacyOnly}";
        }

        if (!in_array($locale, $localization->getContentLocales(), true)) {
            return '1=1';
        }

        $localizedContent = $this->buildLocalizedPostContentExpression($alias, $locale);

        if ($locale === 'en') {
            return "({$localizedContent} OR {$englishLegacyOnly})";
        }

        return $localizedContent;
    }

    private function buildBasePostContentExpression(string $alias): string
    {
        return "(CHAR_LENGTH(TRIM(COALESCE({$alias}.content, ''))) > 0"
            . " OR CHAR_LENGTH(TRIM(COALESCE({$alias}.excerpt, ''))) > 0"
            . " OR CHAR_LENGTH(TRIM(COALESCE({$alias}.title, ''))) > 0)";
    }

    private function buildLocalizedPostContentExpression(string $alias, string $locale): string
    {
        return "(CHAR_LENGTH(TRIM(COALESCE({$alias}.content_{$locale}, ''))) > 0"
            . " OR CHAR_LENGTH(TRIM(COALESCE({$alias}.excerpt_{$locale}, ''))) > 0"
            . " OR CHAR_LENGTH(TRIM(COALESCE({$alias}.title_{$locale}, ''))) > 0)";
    }

    private function buildLegacyEnglishOnlyPostExpression(string $alias): string
    {
        $englishContent = $this->buildLocalizedPostContentExpression($alias, 'en');

        return "(CHAR_LENGTH(TRIM(COALESCE({$alias}.slug_en, ''))) > 0 AND NOT {$englishContent})";
    }

    /**
     * @param array<string, mixed> $post
     */
    private function postMatchesLocaleAvailability(array $post, string $locale): bool
    {
        $locale = Services\ContentLocalizationService::getInstance()->normalizeLocale($locale);
        $hasBaseContent = $this->postHasBaseContent($post);
        $hasEnglishContent = $this->postHasLocalizedContent($post, 'en');
        $hasEnglishSlug = trim((string) ($post['slug_en'] ?? '')) !== '';
        $isLegacyEnglishOnly = $hasEnglishSlug && !$hasEnglishContent;

        if ($locale === '' || $locale === 'de') {
            return $hasBaseContent && !$isLegacyEnglishOnly;
        }

        if ($locale === 'en') {
            return $hasEnglishContent || $isLegacyEnglishOnly;
        }

        return $this->postHasLocalizedContent($post, $locale);
    }

    /**
     * @param array<string, mixed> $post
     */
    private function postHasBaseContent(array $post): bool
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
            if (trim((string) ($post[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $post
     */
    private function postHasLocalizedContent(array $post, string $locale): bool
    {
        foreach (['title', 'excerpt', 'content'] as $field) {
            if (trim((string) ($post[$field . '_' . $locale] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}
