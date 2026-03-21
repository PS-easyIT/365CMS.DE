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
        $routeMethod = $method === 'HEAD' ? 'GET' : $method;
        $uri = $this->requestUri;
        Debug::checkpoint('router.dispatch.start', ['method' => $method, 'uri' => $uri]);
        $this->requestContext = Services\ContentLocalizationService::getInstance()->resolveRequestContext($uri);
        $routingUri = (string)($this->requestContext['base_uri'] ?? $uri);

        $this->applyRequestCacheHeaders($routingUri, $method);

        if ($this->maybeRedirectHubAliasDomain($routingUri)) {
            return;
        }

        if ($this->maybeRedirectCategoryAliasDomain($routingUri)) {
            return;
        }

        if (class_exists('\\CMS\\Services\\RedirectService')) {
            $redirect = Services\RedirectService::getInstance()->findRedirect(
                $uri,
                (string)($_SERVER['HTTP_HOST'] ?? '')
            );
            if (is_array($redirect) && !empty($redirect['target_url'])) {
                $this->redirect((string)$redirect['target_url'], (int)($redirect['redirect_type'] ?? 301));
            }
        }

        $csrfBypassPrefixes = ['/api/', '/admin/', '/member/', '/contact/'];
        $csrfBypassExact = ['/login', '/register', '/logout', '/contact', '/comments/post', '/mfa-challenge', '/mfa-setup', '/mfa-disable'];
            $isThemeFavoriteToggle = $method === 'POST'
                && (string) ($_POST['phinit_toggle_favorite'] ?? '') === '1'
                && in_array((string) ($_POST['favorite_content_type'] ?? ''), ['post', 'page'], true)
                && (int) ($_POST['favorite_content_id'] ?? 0) > 0;
        $isProtectedMethod = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true);

        if ($isProtectedMethod
            && !$this->isM365LicPublicEvaluationRequest($routingUri, $method)
            && !in_array($routingUri, $csrfBypassExact, true)
            && !array_reduce($csrfBypassPrefixes, fn(bool $carry, string $prefix): bool => $carry || str_starts_with($routingUri, $prefix), false)
        ) {
            $csrfToken = $_POST['csrf_token'] ?? '';
            $hasValidThemeFavoriteToken = false;

            if ($isThemeFavoriteToggle) {
                $favoriteType = (string) ($_POST['favorite_content_type'] ?? '');
                $favoriteId = (int) ($_POST['favorite_content_id'] ?? 0);
                $favoriteAction = 'phinit_favorite_' . $favoriteType . '_' . $favoriteId;
                $hasValidThemeFavoriteToken = Security::instance()->verifyPersistentToken(
                    (string) ($_POST['favorite_csrf_token'] ?? ''),
                    $favoriteAction
                );
            }

            if (!$hasValidThemeFavoriteToken && !Security::instance()->verifyToken($csrfToken, 'form_guard')) {
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

        if (isset($this->routes[$routeMethod][$routingUri])) {
            Debug::checkpoint('router.route.exact_match', ['method' => $routeMethod, 'uri' => $routingUri]);
            call_user_func($this->routes[$routeMethod][$routingUri]);
            return;
        }

        foreach ($this->routes[$routeMethod] ?? [] as $pattern => $callback) {
            $params = $this->matchRoute($pattern, $routingUri);
            if ($params !== false) {
                Debug::checkpoint('router.route.pattern_match', ['method' => $routeMethod, 'pattern' => $pattern, 'uri' => $routingUri]);
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
            $slug = trim($routingUri, '/');
            if ($slug === '') {
                $slug = 'home';
            }

            $hubPage = Services\SiteTableService::getInstance()->getHubPageBySlug($slug, $this->getRequestLocale());
            if ($hubPage !== null) {
                if (!$this->sendConditionalPublicPageHeaders('hub', $hubPage, $this->getRequestLocale())) {
                    return;
                }

                ThemeManager::instance()->render('page', ['page' => $hubPage, 'contentLocale' => $this->getRequestLocale()]);
                return;
            }

            $pageManager = PageManager::instance();

            $page = $pageManager->getPageBySlug($slug, $this->getRequestLocale());
            if ($page && $page['status'] === 'published') {
                $locale = $this->getRequestLocale();

                if (!empty($page['content'])) {
                    $page['updated_at'] = Services\SiteTableService::getInstance()->resolveContentLastModified(
                        (string) $page['content'],
                        (string) ($page['updated_at'] ?? $page['created_at'] ?? '')
                    );
                }

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

    private function maybeRedirectHubAliasDomain(string $routingUri): bool
    {
        if (!in_array($this->requestMethod, ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($routingUri !== '/') {
            return false;
        }

        $requestHost = $this->normalizeHost((string)($_SERVER['HTTP_HOST'] ?? ''));
        $mainHost = $this->normalizeHost((string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?? ''));
        if ($requestHost === '' || $mainHost === '' || $requestHost === $mainHost) {
            return false;
        }

        $locale = $this->getRequestLocale();
        $hubPage = Services\SiteTableService::getInstance()->getHubPageByDomain($requestHost, $locale);
        if ($hubPage === null) {
            return false;
        }

        $slug = trim((string)($hubPage['slug'] ?? ''), '/');
        if ($slug === '') {
            return false;
        }

        $targetPath = '/' . $slug;
        if ($locale !== '' && $locale !== 'de') {
            $targetPath = rtrim($targetPath, '/') . '/' . rawurlencode($locale);
        }

        $query = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_QUERY) ?? '');
        if ($query !== '') {
            $targetPath .= '?' . $query;
        }

        $this->redirect($targetPath, 302);
        return true;
    }

    private function maybeRedirectCategoryAliasDomain(string $routingUri): bool
    {
        if (!in_array($this->requestMethod, ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($routingUri !== '/') {
            return false;
        }

        $requestHost = $this->normalizeHost((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $mainHost = $this->normalizeHost((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?? ''));
        if ($requestHost === '' || $mainHost === '' || $requestHost === $mainHost) {
            return false;
        }

        $category = $this->findCategoryByAliasDomain($requestHost);
        if ($category === null) {
            return false;
        }

        $slug = trim((string) ($category['slug'] ?? ''), '/');
        if ($slug === '') {
            return false;
        }

        $targetPath = '/kategorie/' . rawurlencode($slug);
        $query = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_QUERY) ?? '');
        if ($query !== '') {
            $targetPath .= '?' . $query;
        }

        $this->redirect($targetPath, 302);
        return true;
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

    private function isM365LicPublicEvaluationRequest(string $routingUri, string $method): bool
    {
        if ($method !== 'POST') {
            return false;
        }

        if (!isset($_POST['evaluation_csrf_token']) || !array_key_exists('requirements_payload', $_POST)) {
            return false;
        }

        $routeSlug = $this->resolveM365LicRouteSlug();
        if ($routeSlug === '') {
            return false;
        }

        return in_array($routingUri, [
            '/' . $routeSlug,
            '/' . $routeSlug . '/eu-vergleich',
        ], true);
    }

    private function resolveM365LicRouteSlug(): string
    {
        static $routeSlug = null;

        if ($routeSlug !== null) {
            return $routeSlug;
        }

        $routeSlug = '';

        if (!class_exists('\\CMS_M365LIC_Repository')) {
            return $routeSlug;
        }

        try {
            $settings = \CMS_M365LIC_Repository::instance()->get_settings();
            $routeSlug = trim((string) ($settings['route_slug'] ?? 'm365-lizenzberater'), '/');
        } catch (\Throwable $e) {
            $routeSlug = '';
        }

        return $routeSlug;
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

        if (in_array($routingUri, ['/login', '/register', '/forgot-password', '/logout', '/mfa-challenge', '/mfa-setup', '/order'], true)) {
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
                'request_host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
                'request_method' => $this->requestMethod,
                'referrer_url' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
                'ip_address' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ]);
            $this->notFoundLogged = true;
        }

        http_response_code(404);

        $rendered = '';

        try {
            ob_start();
            ThemeManager::instance()->render('404');
            $rendered = (string) ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            error_log('Router 404 Render Error: ' . $e->getMessage());
            $rendered = '';
        }

        if (trim($rendered) !== '') {
            echo $rendered;
            return;
        }

        echo $this->buildFallbackErrorPage(
            404,
            'Seite nicht gefunden',
            'Die angeforderte Seite konnte nicht gefunden werden.',
            $this->requestUri
        );
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

        $prepared = (string) Hooks::applyFilters('content_render', $prepared, $type, $id);

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

    public function redirect(string $url, int $status = 302): void
    {
        $targetUrl = $this->resolveRedirectUrl($url);

        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $targetUrl, true, $status);
            exit;
        }

        http_response_code($status);
        echo $this->buildRedirectFallbackPage($targetUrl, $status);
        exit;
    }

    private function resolveRedirectUrl(string $url): string
    {
        if (preg_match('#^https?://#i', $url) === 1) {
            return $url;
        }

        return SITE_URL . (str_starts_with($url, '/') ? $url : '/' . ltrim($url, '/'));
    }

    private function normalizeHost(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        if (str_contains($value, ':')) {
            $value = explode(':', $value, 2)[0];
        }

        return trim($value, '.');
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findCategoryByAliasDomain(string $domain): ?array
    {
        try {
            $db = Database::instance();
            $prefix = $db->getPrefix();
            $column = $db->get_var("SHOW COLUMNS FROM {$prefix}post_categories LIKE 'alias_domains_json'");
            if ($column === null) {
                return null;
            }

            $rows = $db->get_results(
                "SELECT id, slug, parent_id, alias_domains_json FROM {$prefix}post_categories",
                []
            ) ?: [];

            foreach ($rows as $row) {
                if ((int) ($row->parent_id ?? 0) > 0) {
                    continue;
                }

                $domains = \CMS\Json::decodeArray((string) ($row->alias_domains_json ?? '[]'), []);
                if (!is_array($domains)) {
                    continue;
                }

                foreach ($domains as $candidate) {
                    if ($this->normalizeHost((string) $candidate) === $domain) {
                        return (array) $row;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('Router category alias lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    private function buildRedirectFallbackPage(string $targetUrl, int $status): string
    {
        $safeUrl = htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');
        $safeStatus = max(300, min(399, $status));

        return '<!DOCTYPE html>'
            . '<html lang="de"><head><meta charset="utf-8">'
            . '<meta http-equiv="refresh" content="0;url=' . $safeUrl . '">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>Weiterleitung</title></head><body>'
            . '<h1>Weiterleitung</h1>'
            . '<p>Die Seite leitet weiter (' . $safeStatus . '). Falls nichts passiert, nutze bitte diesen Link:</p>'
            . '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
            . '</body></html>';
    }

    private function buildFallbackErrorPage(int $status, string $title, string $message, string $path = ''): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
        $details = $safePath !== '' ? '<p><small>Angefragter Pfad: ' . $safePath . '</small></p>' : '';

        return '<!DOCTYPE html>'
            . '<html lang="de"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>' . (int) $status . ' – ' . $safeTitle . '</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:0;background:#f8fafc;color:#0f172a}main{max-width:720px;margin:8vh auto;padding:2rem}a{color:#2563eb}h1{margin-bottom:.75rem}p{line-height:1.6}</style>'
            . '</head><body><main>'
            . '<p>' . (int) $status . '</p>'
            . '<h1>' . $safeTitle . '</h1>'
            . '<p>' . $safeMessage . '</p>'
            . $details
            . '<p><a href="' . htmlspecialchars(SITE_URL . '/', ENT_QUOTES, 'UTF-8') . '">Zur Startseite</a></p>'
            . '</main></body></html>';
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
