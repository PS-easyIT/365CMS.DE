<?php
/**
 * Router Class
 * 
 * Handles URL routing and request dispatching
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Router
{
    private static ?self $instance = null;
    private string $requestUri;
    private string $requestMethod;
    private array $routes = [];
    private bool $notFoundLogged = false;
    
    /**
     * Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->requestUri = $this->getRequestUri();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->registerDefaultRoutes();
    }
    
    /**
     * Get request URI
     */
    private function getRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Remove base path if in subdirectory
        if (SITE_URL_PATH !== '/') {
            $uri = str_replace(SITE_URL_PATH, '', $uri);
        }
        
        return '/' . trim($uri, '/');
    }
    
    /**
     * Register default routes
     */
    private function registerDefaultRoutes(): void
    {
        // Home page
        $this->addRoute('GET', '/', [$this, 'renderHome']);
        
        // Auth routes
        $this->addRoute('GET', '/login', [$this, 'renderLogin']);
        $this->addRoute('POST', '/login', [$this, 'handleLogin']);
        $this->addRoute('GET', '/register', [$this, 'renderRegister']);
        $this->addRoute('POST', '/register', [$this, 'handleRegister']);
        $this->addRoute('GET', '/logout', [$this, 'handleLogout']);

        // H-02: MFA-Routen
        $this->addRoute('GET',  '/mfa-challenge', [$this, 'renderMfaChallenge']);
        $this->addRoute('POST', '/mfa-challenge', [$this, 'handleMfaChallenge']);
        $this->addRoute('GET',  '/mfa-setup',     [$this, 'renderMfaSetup']);
        $this->addRoute('POST', '/mfa-setup',     [$this, 'handleMfaSetup']);
        $this->addRoute('POST', '/mfa-disable',   [$this, 'handleMfaDisable']);
        
        // Public Order Page
        $this->addRoute('GET', '/order', [$this, 'renderOrder']);
        $this->addRoute('POST', '/order', [$this, 'handleOrder']);
        
        // Dashboard shortcut – Theme-eigenes Dashboard (z. B. cms-phinit) oder Fallback auf /member/dashboard
        $this->addRoute('GET',  '/dashboard', [$this, 'renderDashboard']);
        $this->addRoute('POST', '/dashboard', [$this, 'renderDashboard']);

        // Member area
        $this->addRoute('GET',  '/member', [$this, 'renderMember']);
        $this->addRoute('POST', '/member', [$this, 'renderMember']);
        // Sub-path routes muss vor /:page stehen (spezifischer), damit sie zuerst geprüft werden
        $this->addRoute('GET',  '/member/plugin/:slug/:action/:id', [$this, 'renderMemberPluginSection']);
        $this->addRoute('POST', '/member/plugin/:slug/:action/:id', [$this, 'renderMemberPluginSection']);
        $this->addRoute('GET',  '/member/plugin/:slug/:action',     [$this, 'renderMemberPluginSection']);
        $this->addRoute('POST', '/member/plugin/:slug/:action',     [$this, 'renderMemberPluginSection']);
        $this->addRoute('GET',  '/member/plugin/:slug', [$this, 'renderMemberPluginSection']);
        $this->addRoute('POST', '/member/plugin/:slug', [$this, 'renderMemberPluginSection']);
        $this->addRoute('GET',  '/member/:page', [$this, 'renderMemberPage']);
        $this->addRoute('POST', '/member/:page', [$this, 'renderMemberPage']);
        
        // Admin area
        $this->addRoute('GET', '/admin', [$this, 'renderAdmin']);
        $this->addRoute('POST', '/admin', [$this, 'renderAdmin']);
        $this->addRoute('GET', '/admin/:page', [$this, 'renderAdminPage']);
        $this->addRoute('POST', '/admin/:page', [$this, 'renderAdminPage']);

        // Plugin Admin Routes
        $this->addRoute('GET', '/admin/plugins/:plugin/:page', [$this, 'renderPluginPage']);
        $this->addRoute('POST', '/admin/plugins/:plugin/:page', [$this, 'renderPluginPage']);

        
        // API Routes (v1)
        $this->addRoute('GET', '/api/v1/status', function() {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'version' => defined('CMS_VERSION') ? CMS_VERSION : '2.1.1']);
            exit;
        });
        
        $this->addRoute('GET', '/api/v1/pages', function() {
            Api::instance()->handleRequest('pages');
        });
        
        $this->addRoute('GET', '/api/v1/pages/:slug', function($slug) {
            Api::instance()->handleRequest('pages', $slug);
        });

        // Admin Grid.js – Server-Side-Data für Tabellen (nur für Admins)
        $this->addRoute('GET', '/api/v1/admin/posts', function () {
            $this->requireAdmin();
            $this->jsonAdminPosts();
        });
        $this->addRoute('GET', '/api/v1/admin/pages', function () {
            $this->requireAdmin();
            $this->jsonAdminPages();
        });
        $this->addRoute('GET', '/api/v1/admin/users', function () {
            $this->requireAdmin();
            $this->jsonAdminUsers();
        });

        $this->addRoute('GET', '/api/v1/admin/media/elfinder', function () {
            $this->requireAdmin();
            $token = (string)($_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
            if (!Security::instance()->verifyPersistentToken($token, 'media_connector')) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Sicherheitsüberprüfung fehlgeschlagen.']);
                exit;
            }

            Services\ElfinderService::getInstance()->handleConnectorRequest();
        });
        $this->addRoute('POST', '/api/v1/admin/media/elfinder', function () {
            $this->requireAdmin();
            $token = (string)($_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
            if (!Security::instance()->verifyPersistentToken($token, 'media_connector')) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Sicherheitsüberprüfung fehlgeschlagen.']);
                exit;
            }

            Services\ElfinderService::getInstance()->handleConnectorRequest();
        });

        // FilePond Upload API
        $this->addRoute('POST', '/api/upload', function() {
            $result = Services\FileUploadService::getInstance()->handleUploadRequest();
            http_response_code((int)($result['status'] ?? 200));
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result['data'] ?? ['error' => 'Unbekannter Fehler']);
            exit;
        });

        // Editor.js Media API (Uploads, Remote-Image-Fetch, Link-Vorschau)
        $this->addRoute('GET', '/api/media', function() {
            Services\EditorJsService::getInstance()->handleMediaApiRequest();
        });
        $this->addRoute('POST', '/api/media', function() {
            Services\EditorJsService::getInstance()->handleMediaApiRequest();
        });
        
        // Search Route – übergreifende Suche über Seiten, Experten, Firmen, Speaker, Events
        $this->addRoute('GET', '/search', function() {
            $query    = trim($_GET['q'] ?? '');
            $type     = $_GET['type'] ?? '';
            $location = trim($_GET['location'] ?? '');
            $filter   = trim($_GET['filter'] ?? '');

            $results    = [];
            $pluginMgr  = PluginManager::instance();
            $db         = Database::instance();
            $prefix     = $db->getPrefix();

            // SearchService für Volltextsuche (TNTSearch)
            $searchService = Services\SearchService::getInstance();
            $useTNT = $searchService->isAvailable() && $query !== '';

            // Seiten-Suche (immer, außer ein spezifischer Bereich ist gewählt)
            if (empty($type) || $type === 'pages') {
                if ($useTNT) {
                    // TNTSearch Volltextsuche (relevanzbasiert)
                    $tntResult = $searchService->search($query, 'pages', 20, true);
                    if (!empty($tntResult['ids'])) {
                        $ids = array_map('intval', $tntResult['ids']);
                        $ph  = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $db->prepare("SELECT * FROM {$prefix}pages WHERE id IN ({$ph}) AND status = 'published'");
                        $stmt->execute($ids);
                        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                        // In TNTSearch-Relevanzreihenfolge bringen
                        $byId = [];
                        foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }
                        foreach ($ids as $id) {
                            if (isset($byId[$id])) {
                                $byId[$id]['_type'] = 'page';
                                $byId[$id]['_type_label'] = 'Seite';
                                $results[] = $byId[$id];
                            }
                        }
                    }
                } else {
                    // Fallback: LIKE-Suche
                    $pageManager = PageManager::instance();
                    $pageResults = $pageManager->search($query);
                    foreach ($pageResults as $r) {
                        $r = (array)$r;
                        $r['_type'] = 'page';
                        $r['_type_label'] = 'Seite';
                        $results[] = $r;
                    }
                }
            }

            // Posts-Suche (Blog)
            if (empty($type) || $type === 'posts') {
                if ($useTNT) {
                    $tntResult = $searchService->search($query, 'posts', 20, true);
                    if (!empty($tntResult['ids'])) {
                        $ids = array_map('intval', $tntResult['ids']);
                        $ph  = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $db->prepare("SELECT * FROM {$prefix}posts WHERE id IN ({$ph}) AND status = 'published'");
                        $stmt->execute($ids);
                        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                        $byId = [];
                        foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }
                        foreach ($ids as $id) {
                            if (isset($byId[$id])) {
                                $byId[$id]['_type'] = 'post';
                                $byId[$id]['_type_label'] = 'Beitrag';
                                $results[] = $byId[$id];
                            }
                        }
                    }
                } elseif ($query !== '') {
                    // Fallback: LIKE-Suche für Posts
                    $like = '%' . $query . '%';
                    $stmt = $db->prepare("SELECT * FROM {$prefix}posts WHERE status = 'published' AND (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) ORDER BY created_at DESC LIMIT 20");
                    $stmt->execute([$like, $like, $like]);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $r['_type'] = 'post';
                        $r['_type_label'] = 'Beitrag';
                        $results[] = $r;
                    }
                }
            }

            // Experten-Suche
            if ((empty($type) || $type === 'experts') && $pluginMgr->isPluginActive('cms-experts')) {
                try {
                    $where = ["e.status = 'active'"];
                    $params = [];
                    if ($query !== '') {
                        $where[] = "(e.name LIKE ? OR e.title LIKE ? OR e.skills LIKE ? OR e.specializations LIKE ?)";
                        $like = '%' . $query . '%';
                        $params = array_merge($params, [$like, $like, $like, $like]);
                    }
                    if ($location !== '') {
                        $where[] = "(e.location LIKE ? OR e.availability LIKE ?)";
                        $locLike = '%' . $location . '%';
                        $params[] = $locLike;
                        $params[] = $locLike;
                    }
                    if ($filter !== '') {
                        $where[] = "(e.skills LIKE ? OR e.specializations LIKE ?)";
                        $fLike = '%' . $filter . '%';
                        $params[] = $fLike;
                        $params[] = $fLike;
                    }
                    $sql = "SELECT e.* FROM {$prefix}experts e WHERE " . implode(' AND ', $where) . " ORDER BY e.created_at DESC LIMIT 20";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $r['_type'] = 'expert';
                        $r['_type_label'] = 'Experte';
                        $r['slug'] = 'experts/' . ($r['id'] ?? 0);
                        $r['title'] = $r['name'] ?? $r['display_name'] ?? 'Experte';
                        $r['meta_description'] = $r['title'] ?? $r['skills'] ?? '';
                        $results[] = $r;
                    }
                } catch (\Throwable $e) { /* Tabelle ggf. nicht vorhanden */ }
            }

            // Firmen-Suche
            if ((empty($type) || $type === 'companies') && $pluginMgr->isPluginActive('cms-companies')) {
                try {
                    $where = ["c.status = 'active'"];
                    $params = [];
                    if ($query !== '') {
                        $where[] = "(c.name LIKE ? OR c.description LIKE ? OR c.industry LIKE ?)";
                        $like = '%' . $query . '%';
                        $params = array_merge($params, [$like, $like, $like]);
                    }
                    if ($location !== '') {
                        $where[] = "(c.location LIKE ? OR c.city LIKE ?)";
                        $locLike = '%' . $location . '%';
                        $params[] = $locLike;
                        $params[] = $locLike;
                    }
                    if ($filter !== '') {
                        $where[] = "(c.industry LIKE ? OR c.description LIKE ?)";
                        $fLike = '%' . $filter . '%';
                        $params[] = $fLike;
                        $params[] = $fLike;
                    }
                    $sql = "SELECT c.* FROM {$prefix}companies c WHERE " . implode(' AND ', $where) . " ORDER BY c.created_at DESC LIMIT 20";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $r['_type'] = 'company';
                        $r['_type_label'] = 'Firma';
                        $r['slug'] = 'companies/' . ($r['id'] ?? 0);
                        $r['title'] = $r['name'] ?? $r['company_name'] ?? 'Firma';
                        $r['meta_description'] = $r['description'] ?? $r['short_description'] ?? '';
                        $results[] = $r;
                    }
                } catch (\Throwable $e) { /* */ }
            }

            // Speaker-Suche
            if ((empty($type) || $type === 'speakers') && $pluginMgr->isPluginActive('cms-speakers')) {
                try {
                    $where = ["s.status = 'active'"];
                    $params = [];
                    if ($query !== '') {
                        $where[] = "(s.name LIKE ? OR s.bio LIKE ? OR s.expertise LIKE ?)";
                        $like = '%' . $query . '%';
                        $params = array_merge($params, [$like, $like, $like]);
                    }
                    if ($location !== '') {
                        $where[] = "(s.location LIKE ?)";
                        $params[] = '%' . $location . '%';
                    }
                    if ($filter !== '') {
                        $where[] = "(s.expertise LIKE ? OR s.topics LIKE ?)";
                        $fLike = '%' . $filter . '%';
                        $params[] = $fLike;
                        $params[] = $fLike;
                    }
                    $sql = "SELECT s.* FROM {$prefix}event_speakers s WHERE " . implode(' AND ', $where) . " ORDER BY s.created_at DESC LIMIT 20";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $r['_type'] = 'speaker';
                        $r['_type_label'] = 'Speaker';
                        $r['slug'] = 'speakers/' . ($r['id'] ?? 0);
                        $r['title'] = $r['name'] ?? 'Speaker';
                        $r['meta_description'] = $r['bio'] ?? $r['expertise'] ?? '';
                        $results[] = $r;
                    }
                } catch (\Throwable $e) { /* */ }
            }

            // Events-Suche
            if ((empty($type) || $type === 'events') && $pluginMgr->isPluginActive('cms-events')) {
                try {
                    $where = ["ev.status = 'active'"];
                    $params = [];
                    if ($query !== '') {
                        $where[] = "(ev.title LIKE ? OR ev.description LIKE ?)";
                        $like = '%' . $query . '%';
                        $params = array_merge($params, [$like, $like]);
                    }
                    if ($location !== '') {
                        $where[] = "(ev.location LIKE ? OR ev.venue LIKE ?)";
                        $locLike = '%' . $location . '%';
                        $params[] = $locLike;
                        $params[] = $locLike;
                    }
                    $sql = "SELECT ev.* FROM {$prefix}events ev WHERE " . implode(' AND ', $where) . " ORDER BY ev.start_date DESC LIMIT 20";
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $r['_type'] = 'event';
                        $r['_type_label'] = 'Event';
                        $r['slug'] = 'events/' . ($r['id'] ?? 0);
                        $r['title'] = $r['title'] ?? 'Event';
                        $r['meta_description'] = $r['description'] ?? '';
                        $results[] = $r;
                    }
                } catch (\Throwable $e) { /* */ }
            }

            ThemeManager::instance()->render('search', [
                'results'  => $results,
                'query'    => $query,
                'type'     => $type,
                'location' => $location,
                'filter'   => $filter,
            ]);
        });

        $this->addRoute('GET', '/cookie-einstellungen', [$this, 'renderCookiePreferencesPage']);

        $this->addRoute('GET', '/site-table/export/:id/:format', function (string $id, string $format) {
            $tableId = (int) $id;
            if ($tableId <= 0 || !Services\SiteTableService::getInstance()->streamExportById($tableId, $format, true)) {
                $this->render404();
            }
        });
        
        // Blog Routes
        $this->addRoute('GET', '/blog', function() {
            $db     = Database::instance();
            $prefix = $db->getPrefix();
            $page   = max(1, (int)($_GET['p'] ?? 1));
            $perPage = 9;
            $offset  = ($page - 1) * $perPage;
            $total   = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE status = 'published'");
            $posts   = $db->get_results(
                "SELECT p.*, c.name AS category_name
                 FROM {$prefix}posts p
                 LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                 WHERE p.status = 'published'
                 ORDER BY p.published_at DESC
                 LIMIT {$perPage} OFFSET {$offset}"
            ) ?: [];
            ThemeManager::instance()->render('blog', [
                'posts'       => $posts,
                'total'       => $total,
                'currentPage' => $page,
                'totalPages'  => max(1, (int)ceil($total / $perPage)),
                'perPage'     => $perPage,
            ]);
        });
        $this->addRoute('GET', '/blog/:slug', function(string $slug) {
            $db     = Database::instance();
            $prefix = $db->getPrefix();
            $post   = $db->get_row(
                "SELECT p.*, u.display_name AS author_name, c.name AS category_name
                 FROM {$prefix}posts p
                 LEFT JOIN {$prefix}users u ON u.id = p.author_id
                 LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                 WHERE p.slug = ? AND p.status = 'published'",
                [$slug]
            );
            if (!$post) {
                http_response_code(404);
                ThemeManager::instance()->render('404');
                return;
            }
            // View-Counter erhöhen
            $db->execute("UPDATE {$prefix}posts SET views = views + 1 WHERE id = ?", [(int)$post->id]);
            // Editor.js JSON → HTML konvertieren (falls Inhalt JSON ist)
            if (!empty($post->content)) {
                $post->content = $this->prepareRenderableContent((string) $post->content, 'post', (int) ($post->id ?? 0));
            }
            // PDF-Download: /blog/slug?pdf=1
            if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
                $this->streamContentAsPdf(
                    htmlspecialchars($post->title ?? 'Beitrag', ENT_QUOTES, 'UTF-8'),
                    $post->content,
                    $post->author_name ?? null
                );
                return;
            }
            ThemeManager::instance()->render('blog-single', ['post' => $post]);
        });

        // SEO Routes
        $this->addRoute('GET', '/sitemap.xml', [$this, 'serveSitemap']);
        $this->addRoute('GET', '/robots.txt', [$this, 'serveRobotsTxt']);

        // Note: Plugins add routes via 'register_routes' action in Bootstrap->run()
    }
    
    /**
     * Add route
     */
    public function addRoute(string $method, string $path, callable $callback): void
    {
        $this->routes[$method][$path] = $callback;
    }
    
    /**
     * Dispatch request
     */
    public function dispatch(): void
    {
        $method = $this->requestMethod;
        $uri    = $this->requestUri;

        if (class_exists('\\CMS\\Services\\RedirectService')) {
            $redirect = Services\RedirectService::getInstance()->findRedirect($uri);
            if (is_array($redirect) && !empty($redirect['target_url'])) {
                http_response_code((int)($redirect['redirect_type'] ?? 301));
                header('Location: ' . (string)$redirect['target_url'], true, (int)($redirect['redirect_type'] ?? 301));
                exit;
            }
        }

        // ── CSRF-Middleware (C-04) ──────────────────────────────────────────────
        // Deckt alle öffentlichen state-ändernden Routen ab.
        // Admin / Member / API / Login / Register steuern CSRF eigenständig.
        $csrfBypassPrefixes = ['/api/', '/admin/', '/member/', '/contact/'];
        $csrfBypassExact    = ['/login', '/register', '/logout', '/contact'];

        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)
            && !in_array($uri, $csrfBypassExact, true)
            && !array_reduce($csrfBypassPrefixes, fn($c, $p) => $c || str_starts_with($uri, $p), false)
        ) {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Security::instance()->verifyToken($csrfToken, 'form_guard')) {
                http_response_code(403);
                error_log('Router [C-04]: CSRF-Fehlschlag für ' . $method . ' ' . $uri);
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'CSRF-Sicherheitsüberprüfung fehlgeschlagen.']);
                } else {
                    echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1><p>CSRF-Validierung fehlgeschlagen.</p></body></html>';
                }
                exit;
            }
        }
        // ───────────────────────────────────────────────────────────────────────
        
        // Check exact match first
        if (isset($this->routes[$method][$uri])) {
            call_user_func($this->routes[$method][$uri]);
            return;
        }
        
        // Check pattern match
        foreach ($this->routes[$method] ?? [] as $pattern => $callback) {
            $params = $this->matchRoute($pattern, $uri);
            if ($params !== false) {
                call_user_func_array($callback, $params);
                return;
            }
        }
        
        // Dynamic Page Check
        try {
            $pageManager = PageManager::instance();
            // Remove leading slash for slug matching
            $slug = trim($uri, '/');
            if (empty($slug)) {
                $slug = 'home';
            }
            
            $page = $pageManager->getPageBySlug($slug);
            if ($page && $page['status'] === 'published') {
                // Editor.js JSON → HTML konvertieren (falls Inhalt JSON ist)
                if (!empty($page['content'])) {
                    $page['content'] = $this->prepareRenderableContent((string) $page['content'], 'page', (int) ($page['id'] ?? 0));
                }
                // PDF-Download: /seiten-slug?pdf=1
                if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
                    $this->streamContentAsPdf(
                        htmlspecialchars($page['title'] ?? 'Seite', ENT_QUOTES, 'UTF-8'),
                        $page['content'],
                        null
                    );
                    return;
                }
                // Render page template
                $themeManager = ThemeManager::instance();
                $themeManager->render('page', ['page' => $page]);
                return;
            }

            // Debug-Logging: Hilft bei 404-Diagnose
            if (CMS_DEBUG) {
                if ($page === null) {
                    error_log(sprintf('Router [404]: Kein Seiten-Eintrag für Slug "%s" gefunden.', $slug));
                } else {
                    error_log(sprintf(
                        'Router [404]: Seite "%s" (ID %d) wurde gefunden, hat aber Status "%s" (erwartet: "published").',
                        $slug,
                        (int)($page['id'] ?? 0),
                        $page['status'] ?? 'unbekannt'
                    ));
                }
            }
        } catch (\Throwable $e) {
            // Log error, proceed to 404
            error_log('Router Page Check Error für "' . ($slug ?? $uri) . '": ' . $e->getMessage());
        }
        
        // 404 Not Found
        $this->render404();
    }
    
    /**
     * Match route pattern
     */
    private function matchRoute(string $pattern, string $uri): array|false
    {
        // Convert :param to regex
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
    
    /**
     * Render home page
     */
    public function renderHome(): void
    {
        ThemeManager::instance()->render('home');
    }
    
    /**
     * Render login page
     */
    public function renderLogin(): void
    {
        if (Auth::instance()->isLoggedIn()) {
            $this->redirect('/member');
            return;
        }
        
        ThemeManager::instance()->render('login');
    }
    
    /**
     * Handle login
     */
    public function handleLogin(): void
    {
        $security = Security::instance();
        
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'login')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->redirect('/login');
            return;
        }
        
        // Login-Formular kann 'username' oder 'email' als Feldnamen verwenden
        $loginInput = $_POST['username'] ?? $_POST['email'] ?? '';
        $result = Auth::instance()->login($loginInput, $_POST['password'] ?? '');
        
        if ($result === true) {
            $this->redirect('/member');
        } elseif ($result === 'MFA_REQUIRED') {
            // H-02: MFA-Challenge erforderlich – ausstehende User-ID ist bereits in Session
            $this->redirect('/mfa-challenge');
        } else {
            $_SESSION['error'] = $result;
            $this->redirect('/login');
        }
    }

    // ── H-02: MFA-Routen ─────────────────────────────────────────────────────

    /**
     * MFA-Challenge-Seite anzeigen (nach erfolgreichem Passwort-Login).
     */
    public function renderMfaChallenge(): void
    {
        if (empty($_SESSION['mfa_pending_user_id'])) {
            $this->redirect('/login');
            return;
        }
        $security  = Security::instance();
        $csrfToken = $security->generateToken('mfa_challenge');
        $error     = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);

        echo '<!DOCTYPE html><html lang="de"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Zwei-Faktor-Authentifizierung – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/main.css">'
            . '</head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;">'
            . '<div style="background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:400px;">'
            . '<h2 style="margin:0 0 0.5rem;font-size:1.375rem;color:#1e293b;">🔐 Zwei-Faktor-Authentifizierung</h2>'
            . '<p style="color:#64748b;margin:0 0 1.5rem;">Gib den 6-stelligen Code aus deiner Authenticator-App ein.</p>';

        if ($error) {
            echo '<div style="background:#fee2e2;color:#991b1b;padding:.875rem 1rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #ef4444;">❌ '
                . htmlspecialchars($error) . '</div>';
        }

        echo '<form method="POST" action="/mfa-challenge" autocomplete="off">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">'
            . '<div style="margin-bottom:1.25rem;">'
            . '<label style="display:block;font-weight:600;margin-bottom:.5rem;color:#1e293b;">Authenticator-Code</label>'
            . '<input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus '
            . 'style="width:100%;padding:.75rem 1rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1.5rem;letter-spacing:.3em;text-align:center;box-sizing:border-box;" '
            . 'placeholder="000000">'
            . '</div>'
            . '<button type="submit" style="width:100%;padding:.875rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">✅ Bestätigen</button>'
            . '</form>'
            . '<p style="text-align:center;margin-top:1.25rem;"><a href="/login" style="color:#64748b;font-size:.875rem;">← Zurück zum Login</a></p>'
            . '</div></body></html>';
    }

    /**
     * MFA-Challenge auswerten.
     */
    public function handleMfaChallenge(): void
    {
        $security = Security::instance();

        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_challenge')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->redirect('/mfa-challenge');
            return;
        }

        $pendingUserId = (int)($_SESSION['mfa_pending_user_id'] ?? 0);
        if ($pendingUserId === 0) {
            $this->redirect('/login');
            return;
        }

        $code = trim($_POST['totp_code'] ?? '');
        if (!Auth::instance()->verifyMfaCode($pendingUserId, $code)) {
            $_SESSION['error'] = 'Ungültiger oder abgelaufener Code. Bitte erneut versuchen.';
            $this->redirect('/mfa-challenge');
            return;
        }

        // MFA bestanden → echte Session setzen
        unset($_SESSION['mfa_pending_user_id']);
        $_SESSION['user_id'] = $pendingUserId;
        session_regenerate_id(true);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'login_mfa_success',
            'Login mit MFA erfolgreich.',
            'user',
            $pendingUserId,
            ['ip' => $security->getClientIp()],
            'info'
        );

        $this->redirect('/member');
    }

    /**
     * MFA-Einrichtungsseite anzeigen (nur eingeloggte Nutzer).
     */
    public function renderMfaSetup(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = (int)$_SESSION['user_id'];

        // Falls MFA bereits aktiv → zur Sicherheitsseite weiterleiten
        if (Auth::instance()->isMfaEnabled($userId)) {
            $_SESSION['success'] = '2FA ist bereits aktiv.';
            $this->redirect('/member/security');
            return;
        }
        $auth     = Auth::instance();
        $security = Security::instance();

        // Setup-Daten erzeugen (Pending-Secret wird in user_meta gespeichert)
        $setup     = $auth->setupMfaSecret($userId);
        $csrfToken = $security->generateToken('mfa_setup');
        $error     = $_SESSION['error'] ?? null;
        $success   = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']);

        echo '<!DOCTYPE html><html lang="de"><head>'
            . '<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>2FA einrichten – ' . htmlspecialchars(SITE_NAME) . '</title>'
            . '<link rel="stylesheet" href="' . SITE_URL . '/assets/css/main.css">'
            . '</head><body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f1f5f9;">'
            . '<div style="background:#fff;padding:2.5rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);width:100%;max-width:480px;">'
            . '<h2 style="margin:0 0 0.5rem;font-size:1.375rem;color:#1e293b;">🔐 Zwei-Faktor-Authentifizierung einrichten</h2>'
            . '<p style="color:#64748b;margin:0 0 1.5rem;">Scanne den QR-Code mit deiner Authenticator-App (z. B. Google Authenticator, Authy) und gib anschließend den 6-stelligen Code ein.</p>';

        if ($error) {
            echo '<div style="background:#fee2e2;color:#991b1b;padding:.875rem;border-radius:8px;margin-bottom:1rem;border-left:4px solid #ef4444;">❌ '
                . htmlspecialchars($error) . '</div>';
        }

        echo '<div style="text-align:center;margin-bottom:1.5rem;">'
            . '<img src="' . htmlspecialchars($setup['qr_url']) . '" alt="QR-Code für Authenticator" width="200" height="200" style="border:4px solid #e2e8f0;border-radius:8px;">'
            . '</div>'
            . '<div style="background:#f8fafc;padding:1rem;border-radius:8px;margin-bottom:1.5rem;font-family:monospace;text-align:center;font-size:1.1rem;letter-spacing:.1em;color:#1e293b;border:1px solid #e2e8f0;">'
            . htmlspecialchars($setup['secret'])
            . '</div>'
            . '<p style="color:#64748b;font-size:.875rem;margin:0 0 1.25rem;">Falls du den QR-Code nicht scannen kannst, gib den obigen Schlüssel manuell in deiner App ein.</p>'
            . '<form method="POST" action="/mfa-setup">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">'
            . '<div style="margin-bottom:1.25rem;">'
            . '<label style="display:block;font-weight:600;margin-bottom:.5rem;color:#1e293b;">Bestätigungscode aus der App</label>'
            . '<input type="text" name="totp_code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus '
            . 'style="width:100%;padding:.75rem 1rem;border:2px solid #e2e8f0;border-radius:8px;font-size:1.5rem;letter-spacing:.3em;text-align:center;box-sizing:border-box;" '
            . 'placeholder="000000">'
            . '</div>'
            . '<button type="submit" style="width:100%;padding:.875rem;background:#3b82f6;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;">✅ 2FA aktivieren</button>'
            . '</form>'
            . '<p style="text-align:center;margin-top:1.25rem;"><a href="/member/security" style="color:#64748b;font-size:.875rem;">← Zurück zur Sicherheitsseite</a></p>'
            . '</div></body></html>';
    }

    /**
     * MFA-Einrichtung bestätigen (ersten Code verifizieren → aktivieren).
     */
    public function handleMfaSetup(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_setup')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->redirect('/mfa-setup');
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $code   = trim($_POST['totp_code'] ?? '');

        if (!Auth::instance()->confirmMfaSetup($userId, $code)) {
            $_SESSION['error'] = 'Ungültiger Code. Bitte erneut scannen und Code eingeben.';
            $this->redirect('/mfa-setup');
            return;
        }

        $_SESSION['success'] = '2FA wurde erfolgreich aktiviert.';
        $this->redirect('/member/security');
    }

    /**
     * MFA deaktivieren (nur eingeloggte Nutzer, mit CSRF-Schutz).
     */
    public function handleMfaDisable(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $security = Security::instance();
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'mfa_disable')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->redirect('/member/security');
            return;
        }

        Auth::instance()->disableMfa((int)$_SESSION['user_id']);
        $_SESSION['success'] = '2FA wurde deaktiviert.';
        $this->redirect('/member/security');
    }
    
    /**
     * Render register page
     */
    public function renderRegister(): void
    {
        if (Auth::instance()->isLoggedIn()) {
            $this->redirect('/member');
            return;
        }
        
        ThemeManager::instance()->render('register');
    }
    
    /**
     * Handle registration
     */
    public function handleRegister(): void
    {
        $security = Security::instance();
        
        if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'register')) {
            $_SESSION['error'] = 'Sicherheitsüberprüfung fehlgeschlagen.';
            $this->redirect('/register');
            return;
        }
        
        $result = Auth::instance()->register($_POST);
        
        if ($result === true) {
            $_SESSION['success'] = 'Registrierung erfolgreich! Sie können sich nun anmelden.';
            $this->redirect('/login');
        } else {
            $_SESSION['error'] = $result;
            $this->redirect('/register');
        }
    }
    
    /**
     * Handle logout
     */
    public function handleLogout(): void
    {
        Auth::instance()->logout();
        $this->redirect('/');
    }
    
    /**
     * Erkennt AJAX-Anfragen (X-Requested-With-Header)
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Render order page
     */
    public function renderOrder(): void
    {
        // CSRF-Token für das Order-Formular generieren (C-04)
        $GLOBALS['cms_form_guard_csrf'] = Security::instance()->generateToken('form_guard');

        // Public access allowed, logic handled in view/controller
        $file = ABSPATH . 'member/order_public.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->render404();
        }
    }

    public function renderCookiePreferencesPage(): void
    {
        if (!class_exists(Services\CookieConsentService::class)) {
            $this->render404();
            return;
        }

        ThemeManager::instance()->render('page', [
            'page' => Services\CookieConsentService::getInstance()->getPublicConsentPage(),
        ]);
    }

    /**
     * Handle order submission
     */
    public function handleOrder(): void
    {
        // Public access allowed, logic handled in view/controller
        $file = ABSPATH . 'member/order_public.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->render404();
        }
    }
    
    /**
     * Render /dashboard – Theme-eigenes Dashboard oder Fallback auf /member/dashboard
     *
     * Prüft ob das aktive Theme ein eigenes member/dashboard.php bereitstellt.
     * Falls ja, wird es direkt gerendert (z. B. cms-phinit).
     * Falls nein, Redirect auf /member/dashboard (CMS-Standard).
     */
    public function renderDashboard(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $themeFile = ThemeManager::instance()->getThemePath() . 'member/dashboard.php';
        if (file_exists($themeFile)) {
            require $themeFile;
            return;
        }

        // Kein Theme-Dashboard vorhanden → Standard-Member-Dashboard
        $this->redirect('/member/dashboard');
    }

    /**
     * Render member area – redirect to /member/dashboard
     */
    public function renderMember(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Slug-basiertes Dashboard: URL zeigt /member/dashboard
        $this->redirect('/member/dashboard');
    }
    
    /**
     * Render member page
     */
    public function renderMemberPage(string $page): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Nur erlaubte Seitennamen (alphanumerisch + Bindestrich) – kein Path-Traversal
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            $this->render404();
            return;
        }

        // Theme-Override: Wenn aktives Theme member/{page}.php bereitstellt
        $themeFile = ThemeManager::instance()->getThemePath() . 'member/' . $page . '.php';
        if (file_exists($themeFile)) {
            require $themeFile;
            return;
        }

        $file = ABSPATH . 'member/' . $page . '.php';
        if (file_exists($file)) {
            // require statt require_once: Stellt sicher, dass die Datei
            // bei jedem Request frisch ausgeführt wird (wichtig für PRG-Pattern)
            require $file;
        } else {
            error_log('Router [renderMemberPage]: Datei nicht gefunden: ' . $file);
            $this->render404();
        }
    }

    /**
     * Render member plugin section (/member/plugin/:slug)
     *
     * Delegiert an PluginDashboardRegistry::handleRoute().
     */
    public function renderMemberPluginSection(string $slug, string $action = '', string $id = ''): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Pfad-Segmente (:action, :id) als $_GET-Parameter injizieren,
        // damit render_callback im Plugin via $_GET['action'] und $_GET['id'] darauf zugreifen kann.
        if ($action !== '') {
            $_GET['action'] = sanitize_key($action);
        }
        if ($id !== '' && ctype_digit($id)) {
            $_GET['id'] = $id;
        }

        // Registry laden (PSR-4 autoloaded: CMS\Member\PluginDashboardRegistry)
        $registry = \CMS\Member\PluginDashboardRegistry::instance();
        $registry->init();
        $registry->handleRoute($slug);
    }


    public function renderAdmin(): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirect('/');
            return;
        }

        $candidates = [
            ABSPATH . 'admin/index.php',
            ABSPATH . 'admin/modules/dashboard/page.php',
            ABSPATH . 'admin/old/index.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }

        $this->render404();
    }
    
    /**
     * Render admin page
     */
    public function renderAdminPage(string $page): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirect('/');
            return;
        }

        // Nur sichere Slugs zulassen (kein Path Traversal)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            $this->render404();
            return;
        }

        $candidates = [
            ABSPATH . 'admin/' . $page . '.php',
            ABSPATH . 'admin/modules/' . $page . '/page.php',
            ABSPATH . 'admin/old/' . $page . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }

        $this->render404();
    }
    
    /**
     * Render 404 page
     */
    private function render404(): void
    {
        if (!$this->notFoundLogged && class_exists('\\CMS\\Services\\RedirectService')) {
            Services\RedirectService::getInstance()->logNotFound($this->requestUri, [
                'request_method' => $this->requestMethod,
                'referrer_url' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
                'ip_address' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);
            $this->notFoundLogged = true;
        }

        http_response_code(404);
        ThemeManager::instance()->render('404');
    }

    /**
     * Bereitet Seiten-/Beitragsinhalt für das Frontend auf.
     */
    private function prepareRenderableContent(string $content, string $type, int $id = 0): string
    {
        $content = Services\EditorService::getInstance()->renderContent($content);
        $content = Services\SiteTableService::getInstance()->replaceShortcodes($content);

        $tocResult = TableOfContents::instance()->process($content, $type, $id);
        $prepared = (string) ($tocResult['content'] ?? $content);
        $tocHtml = (string) ($tocResult['toc'] ?? '');

        if ($tocHtml !== '') {
            $prepared = $this->injectTocIntoContent(
                $prepared,
                $tocHtml,
                (string) TableOfContents::instance()->getSetting('position', 'before')
            );
        }

        return $prepared;
    }

    /**
     * Fügt ein TOC anhand der konfigurierten Position in bereits gerenderten Inhalt ein.
     */
    private function injectTocIntoContent(string $content, string $tocHtml, string $position): string
    {
        if ($tocHtml === '') {
            return $content;
        }

        return match ($position) {
            'top' => $tocHtml . $content,
            'bottom' => $content . $tocHtml,
            'after' => $this->insertAfterFirstHeading($content, $tocHtml),
            default => $this->insertBeforeFirstHeading($content, $tocHtml),
        };
    }

    private function insertBeforeFirstHeading(string $content, string $html): string
    {
        if (preg_match('/<h[1-6]\b[^>]*>/i', $content, $match, PREG_OFFSET_CAPTURE)) {
            $position = (int) ($match[0][1] ?? 0);
            return substr($content, 0, $position) . $html . substr($content, $position);
        }

        return $html . $content;
    }

    private function insertAfterFirstHeading(string $content, string $html): string
    {
        if (preg_match('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', $content, $match, PREG_OFFSET_CAPTURE)) {
            $fullMatch = (string) ($match[0][0] ?? '');
            $position = (int) ($match[0][1] ?? 0) + strlen($fullMatch);
            return substr($content, 0, $position) . $html . substr($content, $position);
        }

        return $html . $content;
    }
    
    /**
     * Redirect
     */
    public function redirect(string $url): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = SITE_URL . $url;
        }
        
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Serve XML Sitemap
     */
    public function serveSitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        
        $seoService = \CMS\Services\SEOService::getInstance();
        echo $seoService->generateSitemap();
        exit;
    }
    
    /**
     * Serve robots.txt
     */
    public function serveRobotsTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $seoService = \CMS\Services\SEOService::getInstance();
        echo $seoService->generateRobotsTxt();
        exit;
    }
    /**
     * Streamt Seiten-/Beitragsinhalt als PDF per PdfService.
     */
    private function streamContentAsPdf(string $title, string $htmlContent, ?string $author): void
    {
        $pdf = Services\PdfService::getInstance();
        if (!$pdf->isAvailable()) {
            http_response_code(503);
            echo 'PDF-Generierung ist nicht verfügbar.';
            return;
        }

        $siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
        $html = $pdf->wrapTemplate($title, $htmlContent);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', mb_substr($title, 0, 80)) . '.pdf';
        $pdf->streamFromHtml($html, $safeFilename);
    }

    /**
     * Admin-Zugriff prüfen und ggf. 403-JSON zurückgeben.
     */
    private function requireAdmin(): void
    {
        if (!Auth::instance()->isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Zugriff verweigert']);
            exit;
        }
    }

    /**
     * Grid.js JSON-API: Posts (Server-Side Pagination + Search + Sort).
     */
    private function jsonAdminPosts(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $db     = Database::instance();
        $prefix = $db->getPrefix();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset  = ($page - 1) * $limit;
        $search  = trim($_GET['search'] ?? '');
        $status  = trim($_GET['status'] ?? 'all');
        $category = max(0, (int)($_GET['category'] ?? 0));
        $sort    = in_array($_GET['sort'] ?? '', ['title', 'status', 'published_at', 'views', 'updated_at'], true)
                   ? $_GET['sort'] : 'updated_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];
        if ($status === 'all') {
            $where[] = "p.status != 'trash'";
        } elseif (in_array($status, ['published', 'draft', 'trash'], true)) {
            $where[]  = "p.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = "(p.title LIKE ? OR p.slug LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $category;
        }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->get_var(
            "SELECT COUNT(*) FROM {$prefix}posts p {$whereStr}",
            $params
        );
        $rows = $db->get_results(
            "SELECT p.id, p.title, p.slug, p.status, p.views, p.featured_image,
                    p.published_at, p.updated_at,
                    u.display_name AS author_name,
                    c.name AS category_name
             FROM {$prefix}posts p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
             {$whereStr}
             ORDER BY p.{$sort} {$order}
             LIMIT {$limit} OFFSET {$offset}",
            $params
        ) ?: [];

        echo json_encode([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    /**
     * Grid.js JSON-API: Pages (Server-Side Pagination + Search + Sort).
     */
    private function jsonAdminPages(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $db     = Database::instance();
        $prefix = $db->getPrefix();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset  = ($page - 1) * $limit;
        $search  = trim($_GET['search'] ?? '');
        $status  = trim($_GET['status'] ?? '');
        $sort    = in_array($_GET['sort'] ?? '', ['title', 'slug', 'status', 'updated_at', 'created_at'], true)
                   ? $_GET['sort'] : 'updated_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];

        if ($status !== '' && in_array($status, ['published', 'draft', 'private'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->get_var(
            "SELECT COUNT(*) FROM {$prefix}pages p {$whereStr}",
            $params
        );
        $rows = $db->get_results(
            "SELECT p.id, p.title, p.slug, p.status, p.updated_at, p.created_at,
                    u.display_name AS author_name
             FROM {$prefix}pages p
             LEFT JOIN {$prefix}users u ON u.id = p.author_id
             {$whereStr}
             ORDER BY p.{$sort} {$order}
             LIMIT {$limit} OFFSET {$offset}",
            $params
        ) ?: [];

        echo json_encode([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    /**
     * Grid.js JSON-API: Users (Server-Side Pagination + Search + Sort).
     */
    private function jsonAdminUsers(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $db     = Database::instance();
        $prefix = $db->getPrefix();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset  = ($page - 1) * $limit;
        $search  = trim($_GET['search'] ?? '');
        $role    = trim($_GET['role'] ?? 'all');
        $status  = trim($_GET['status'] ?? '');
        $sort    = in_array($_GET['sort'] ?? '', ['username', 'email', 'display_name', 'role', 'status', 'created_at'], true)
                   ? $_GET['sort'] : 'created_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where  = [];
        $params = [];
        if ($role === 'banned') {
            $where[]  = "u.status = 'banned'";
        } elseif ($role !== 'all' && $role !== '') {
            $where[]  = "u.role = ?";
            $params[] = $role;
        }
        if ($status !== '' && in_array($status, ['active', 'inactive', 'banned'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = "(u.username LIKE ? OR u.email LIKE ? OR u.display_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->get_var(
            "SELECT COUNT(*) FROM {$prefix}users u {$whereStr}",
            $params
        );
        $rows = $db->get_results(
            "SELECT u.id, u.username, u.email, u.display_name, u.role, u.status, u.created_at,
                    (SELECT COUNT(*) FROM {$prefix}user_group_members ugm WHERE ugm.user_id = u.id) AS group_count
             FROM {$prefix}users u
             {$whereStr}
             ORDER BY u.{$sort} {$order}
             LIMIT {$limit} OFFSET {$offset}",
            $params
        ) ?: [];

        echo json_encode([
            'data'  => $rows,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    /**
     * Render plugin admin page
     */
    public function renderPluginPage(string $plugin, string $page): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirect('/');
            return;
        }

        // Load admin menu partial to get access to layout functions and registered menus
        if (file_exists(ABSPATH . 'admin/partials/admin-menu.php')) {
            require_once ABSPATH . 'admin/partials/admin-menu.php';
        }
        
        // IMPORTANT: Fire the hook so plugins can register their menus!
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::doAction('cms_admin_menu');
        }

        // Get registered menus
        $menus = [];
        if (function_exists('get_registered_admin_menus')) {
            $menus = get_registered_admin_menus();
        }

        $callback = null;
        $title = 'Plugin Page';

        // Search for the callback matching the plugin/page
        foreach ($menus as $menu) {
            // Check top level
            if (isset($menu['menu_slug']) && $menu['menu_slug'] === $plugin && empty($menu['children']) && $plugin === $page) {
                  $callback = $menu['callable'] ?? null;
                  $title = $menu['page_title'] ?? $title;
                  break;
            }

            // Check children
            if (!empty($menu['children'])) {
                foreach ($menu['children'] as $child) {
                    // Check if parent matches plugin slug AND child matches page slug
                    // The URL structure is /admin/plugins/{parent_slug}/{child_slug}
                    if (($menu['menu_slug'] === $plugin) && ($child['menu_slug'] === $page)) {
                        $callback = $child['callable'] ?? null;
                        $title = $child['page_title'] ?? $title;
                        break 2;
                    }
                }
            }
        }

        if ($callback && is_callable($callback)) {
            // Render Admin Layout
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart($title, $page);
            }
            
            // Execute plugin callback
            call_user_func($callback);
            
            if (function_exists('renderAdminLayoutEnd')) {
                renderAdminLayoutEnd();
            }
        } else {
            $this->render404();
        }
    }
}


