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

        // H-02: MFA-Routen
        $this->addRoute('GET',  '/mfa-challenge', [$this, 'renderMfaChallenge']);
        $this->addRoute('POST', '/mfa-challenge', [$this, 'handleMfaChallenge']);
        $this->addRoute('GET',  '/mfa-setup',     [$this, 'renderMfaSetup']);
        $this->addRoute('POST', '/mfa-setup',     [$this, 'handleMfaSetup']);
        $this->addRoute('POST', '/mfa-disable',   [$this, 'handleMfaDisable']);
        
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
            Api::instance()->handleRequest('pages');
        });
        
        $this->addRoute('GET', '/api/v1/pages/:slug', function($slug) {
            Api::instance()->handleRequest('pages', $slug);
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

        // ── CSRF-Middleware (C-04) ──────────────────────────────────────────────
        // Deckt alle öffentlichen state-ändernden Routen ab.
        // Admin / Member / API / Login / Register steuern CSRF eigenständig.
        $csrfBypassPrefixes = ['/api/', '/admin/', '/member/'];
        $csrfBypassExact    = ['/login', '/register', '/logout'];

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


