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
        
        // Public Order Page
        $this->addRoute('GET', '/order', [$this, 'renderOrder']);
        $this->addRoute('POST', '/order', [$this, 'handleOrder']);
        
        // Member area
        $this->addRoute('GET', '/member', [$this, 'renderMember']);
        $this->addRoute('GET', '/member/plugin/:slug', [$this, 'renderMemberPluginSection']);
        $this->addRoute('POST', '/member/plugin/:slug', [$this, 'renderMemberPluginSection']);
        $this->addRoute('GET', '/member/:page', [$this, 'renderMemberPage']);
        
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
            echo json_encode(['status' => 'ok', 'version' => '2.0.0']);
            exit;
        });
        
        $this->addRoute('GET', '/api/v1/pages', function() {
            $api = new Api();
            $api->handleRequest('pages');
        });
        
        $this->addRoute('GET', '/api/v1/pages/:slug', function($slug) {
            $api = new Api();
            $api->handleRequest('pages', $slug);
        });
        
        // Search Route
        $this->addRoute('GET', '/search', function() {
            $query = $_GET['q'] ?? '';
            $pageManager = PageManager::instance();
            $results = $pageManager->search($query);
            ThemeManager::instance()->render('search', ['results' => $results, 'query' => $query]);
        });
        
        // Blog Routes
        $this->addRoute('GET', '/blog', function() {
            $db     = Database::instance();
            $prefix = $db->prefix;
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
            $prefix = $db->prefix;
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
        $uri = $this->requestUri;
        
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
                // Render page template
                $themeManager = ThemeManager::instance();
                $themeManager->render('page', ['page' => $page]);
                return;
            }
        } catch (\Throwable $e) {
            // Log error silently, proceed to 404
            error_log('Router Page Check Error: ' . $e->getMessage());
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
        
        $result = Auth::instance()->login($_POST['username'] ?? '', $_POST['password'] ?? '');
        
        if ($result === true) {
            $this->redirect('/member');
        } else {
            $_SESSION['error'] = $result;
            $this->redirect('/login');
        }
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
     * Render order page
     */
    public function renderOrder(): void
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
     * Render member area
     */
    public function renderMember(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        require_once ABSPATH . 'member/index.php';
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
        
        $file = ABSPATH . 'member/' . $page . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->render404();
        }
    }

    /**
     * Render member plugin section (/member/plugin/:slug)
     *
     * Delegiert an PluginDashboardRegistry::handleRoute().
     */
    public function renderMemberPluginSection(string $slug): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
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
        
        require_once ABSPATH . 'admin/index.php';
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
        
        $file = ABSPATH . 'admin/' . $page . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            $this->render404();
        }
    }
    
    /**
     * Render 404 page
     */
    private function render404(): void
    {
        http_response_code(404);
        ThemeManager::instance()->render('404');
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


