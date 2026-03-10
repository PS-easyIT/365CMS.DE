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
    private array $requestContext = [
        'uri' => '/',
        'base_uri' => '/',
        'locale' => 'de',
        'is_localized' => false,
    ];
    private bool $notFoundLogged = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->requestUri = $this->resolveRequestUri();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->registerDefaultRoutes();
    }

    private function resolveRequestUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        if (SITE_URL_PATH !== '/') {
            $uri = str_replace(SITE_URL_PATH, '', $uri);
        }

        return '/' . trim($uri, '/');
    }

    private function registerDefaultRoutes(): void
    {
        if ($this->isApiRequest($this->requestUri)) {
            $this->loadRouteModule('ApiRouter');
            return;
        }

        if ($this->isAdminRequest($this->requestUri)) {
            $this->loadRouteModule('AdminRouter');
            return;
        }

        if ($this->isMemberRequest($this->requestUri)) {
            $this->loadRouteModule('MemberRouter');
            return;
        }

        $this->loadRouteModule('PublicRouter');
        $this->loadRouteModule('ThemeRouter');
    }

    private function loadRouteModule(string $moduleName): void
    {
        $className = 'CMS\\Routing\\' . $moduleName;
        if (!class_exists($className, false)) {
            $file = CORE_PATH . 'Routing/' . $moduleName . '.php';
            if (!is_file($file)) {
                error_log('Router: Routemodul nicht gefunden: ' . $file);
                return;
            }

            require_once $file;
        }

        if (!class_exists($className)) {
            error_log('Router: Routemodul-Klasse nicht geladen: ' . $className);
            return;
        }

        $module = new $className($this);
        if (!method_exists($module, 'registerRoutes')) {
            error_log('Router: Routemodul ohne registerRoutes(): ' . $className);
            return;
        }

        $module->registerRoutes();
    }

    private function isApiRequest(string $uri): bool
    {
        return $uri === '/api' || str_starts_with($uri, '/api/');
    }

    private function isAdminRequest(string $uri): bool
    {
        return $uri === '/admin' || str_starts_with($uri, '/admin/');
    }

    private function isMemberRequest(string $uri): bool
    {
        $prefixes = ['/member', '/dashboard'];
        foreach ($prefixes as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    public function addRoute(string $method, string $path, callable $callback): void
    {
        $this->routes[$method][$path] = $callback;
    }

    public function dispatch(): void
    {
        $method = $this->requestMethod;
        $uri = $this->requestUri;
        Debug::checkpoint('router.dispatch.start', ['method' => $method, 'uri' => $uri]);
        $this->requestContext = Services\ContentLocalizationService::getInstance()->resolveRequestContext($uri);
        $routingUri = (string)($this->requestContext['base_uri'] ?? $uri);

        $this->applyRequestCacheHeaders($routingUri, $method);

        if (class_exists('\\CMS\\Services\\RedirectService')) {
            $redirect = Services\RedirectService::getInstance()->findRedirect($uri);
            if (is_array($redirect) && !empty($redirect['target_url'])) {
                http_response_code((int)($redirect['redirect_type'] ?? 301));
                header('Location: ' . (string)$redirect['target_url'], true, (int)($redirect['redirect_type'] ?? 301));
                exit;
            }
        }

        $csrfBypassPrefixes = ['/api/', '/admin/', '/member/', '/contact/'];
        $csrfBypassExact = ['/login', '/register', '/logout', '/contact', '/comments/post'];

        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)
            && !in_array($routingUri, $csrfBypassExact, true)
            && !array_reduce($csrfBypassPrefixes, fn(bool $carry, string $prefix): bool => $carry || str_starts_with($routingUri, $prefix), false)
        ) {
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!Security::instance()->verifyToken($csrfToken, 'form_guard')) {
                http_response_code(403);
                error_log('Router [C-04]: CSRF-Fehlschlag für ' . $method . ' ' . $routingUri);
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'CSRF-Sicherheitsüberprüfung fehlgeschlagen.']);
                } else {
                    echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1><p>CSRF-Validierung fehlgeschlagen.</p></body></html>';
                }
                exit;
            }
        }

        if (isset($this->routes[$method][$routingUri])) {
            Debug::checkpoint('router.route.exact_match', ['method' => $method, 'uri' => $routingUri]);
            call_user_func($this->routes[$method][$routingUri]);
            return;
        }

        foreach ($this->routes[$method] ?? [] as $pattern => $callback) {
            $params = $this->matchRoute($pattern, $routingUri);
            if ($params !== false) {
                Debug::checkpoint('router.route.pattern_match', ['method' => $method, 'pattern' => $pattern, 'uri' => $routingUri]);
                call_user_func_array($callback, $params);
                return;
            }
        }

        if (!$this->shouldAttemptDynamicPageLookup($routingUri)) {
            Debug::checkpoint('router.route.404_direct', ['uri' => $routingUri]);
            $this->render404();
            return;
        }

        Debug::checkpoint('router.route.dynamic_lookup', ['uri' => $routingUri]);
        $this->dispatchDynamicPage($routingUri, $uri);
    }

    private function shouldAttemptDynamicPageLookup(string $routingUri): bool
    {
        foreach (['/api', '/admin', '/member'] as $prefix) {
            if ($routingUri === $prefix || str_starts_with($routingUri, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    private function dispatchDynamicPage(string $routingUri, string $uri): void
    {
        try {
            $pageManager = PageManager::instance();
            $slug = trim($routingUri, '/');
            if ($slug === '') {
                $slug = 'home';
            }

            $page = $pageManager->getPageBySlug($slug);
            if ($page && $page['status'] === 'published') {
                $locale = $this->getRequestLocale();

                if (!$this->sendConditionalPublicPageHeaders('page', $page, $locale)) {
                    return;
                }

                $page = Services\ContentLocalizationService::getInstance()->localizePage($page, $locale);
                if (!empty($page['content'])) {
                    $page['content'] = $this->prepareRenderableContent((string)$page['content'], 'page', (int)($page['id'] ?? 0));
                }
                if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
                    $this->streamContentAsPdf(
                        htmlspecialchars((string)($page['title'] ?? 'Seite'), ENT_QUOTES, 'UTF-8'),
                        (string)$page['content'],
                        null
                    );
                    return;
                }

                ThemeManager::instance()->render('page', ['page' => $page, 'contentLocale' => $locale]);
                return;
            }

            $hubPage = Services\SiteTableService::getInstance()->getHubPageBySlug($slug, $this->getRequestLocale());
            if ($hubPage !== null) {
                if (!$this->sendConditionalPublicPageHeaders('hub', $hubPage, $this->getRequestLocale())) {
                    return;
                }

                ThemeManager::instance()->render('page', ['page' => $hubPage, 'contentLocale' => $this->getRequestLocale()]);
                return;
            }

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
            error_log('Router Page Check Error für "' . ($slug ?? $uri) . '": ' . $e->getMessage());
        }

        $this->render404();
    }

    private function matchRoute(string $pattern, string $uri): array|false
    {
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([a-zA-Z0-9_-]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            return $matches;
        }

        return false;
    }

    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function applyRequestCacheHeaders(string $routingUri, string $method): void
    {
        $cache = CacheManager::instance();

        if (!in_array($method, ['GET', 'HEAD'], true)) {
            $cache->sendResponseHeaders('private');
            return;
        }

        foreach (['/admin', '/member', '/api'] as $prefix) {
            if ($routingUri === $prefix || str_starts_with($routingUri, $prefix . '/')) {
                $cache->sendResponseHeaders('private');
                return;
            }
        }

        if (in_array($routingUri, ['/login', '/register', '/logout', '/mfa-challenge', '/mfa-setup', '/order'], true)) {
            $cache->sendResponseHeaders('private');
            return;
        }

        $cache->sendResponseHeaders('public', 300);
    }

    private function sendConditionalPublicPageHeaders(string $type, array $resource, string $locale): bool
    {
        if (!in_array($this->requestMethod, ['GET', 'HEAD'], true)) {
            return true;
        }

        $resourceId = (int)($resource['id'] ?? 0);
        if ($resourceId <= 0) {
            return true;
        }

        $lastModified = (string)($resource['updated_at'] ?? $resource['created_at'] ?? '');
        if ($lastModified === '') {
            return true;
        }

        return CacheManager::instance()->sendConditionalHeaders(
            $type . ':' . $resourceId . ':' . $locale,
            $lastModified
        );
    }

    public function render404(): void
    {
        Debug::checkpoint('router.render_404', ['uri' => $this->requestUri]);
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

    public function getRequestLocale(): string
    {
        return (string)($this->requestContext['locale'] ?? 'de');
    }

    public function getRequestContext(): array
    {
        return $this->requestContext;
    }

    public function prepareRenderableContent(string $content, string $type, int $id = 0): string
    {
        $content = Services\EditorService::getInstance()->renderContent($content);
        $content = Services\SiteTableService::getInstance()->replaceShortcodes($content);

        $tocResult = TableOfContents::instance()->process($content, $type, $id);
        $prepared = (string)($tocResult['content'] ?? $content);
        $tocHtml = (string)($tocResult['toc'] ?? '');

        if ($tocHtml !== '') {
            $prepared = $this->injectTocIntoContent(
                $prepared,
                $tocHtml,
                (string)TableOfContents::instance()->getSetting('position', 'before')
            );
        }

        return $prepared;
    }

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
            $position = (int)($match[0][1] ?? 0);
            return substr($content, 0, $position) . $html . substr($content, $position);
        }

        return $html . $content;
    }

    private function insertAfterFirstHeading(string $content, string $html): string
    {
        if (preg_match('/<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', $content, $match, PREG_OFFSET_CAPTURE)) {
            $fullMatch = (string)($match[0][0] ?? '');
            $position = (int)($match[0][1] ?? 0) + strlen($fullMatch);
            return substr($content, 0, $position) . $html . substr($content, $position);
        }

        return $html . $content;
    }

    public function redirect(string $url): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = SITE_URL . $url;
        }

        header('Location: ' . $url);
        exit;
    }

    public function streamContentAsPdf(string $title, string $htmlContent, ?string $author): void
    {
        $pdf = Services\PdfService::getInstance();
        if (!$pdf->isAvailable()) {
            http_response_code(503);
            echo 'PDF-Generierung ist nicht verfügbar.';
            return;
        }

        $html = $pdf->wrapTemplate($title, $htmlContent);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', mb_substr($title, 0, 80)) . '.pdf';
        $pdf->streamFromHtml($html, $safeFilename);
    }
}
