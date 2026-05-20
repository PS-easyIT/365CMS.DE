<?php
/**
 * Admin Router Module
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS\Routing;

use CMS\Auth;
use CMS\Hooks;
use CMS\Router;
use CMS\Services\CmsAuthPageService;
use CMS\Services\FeatureUsageService;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminRouter
{
    private FeatureUsageService $featureUsage;

    public function __construct(private readonly Router $router)
    {
        $this->featureUsage = FeatureUsageService::getInstance();
    }

    public function registerRoutes(): void
    {
        $this->router->addRoute('GET', '/admin', [$this, 'renderAdmin']);
        $this->router->addRoute('POST', '/admin', [$this, 'renderAdmin']);
        $this->router->addRoute('GET', '/admin/:page', [$this, 'renderAdminPage']);
        $this->router->addRoute('POST', '/admin/:page', [$this, 'renderAdminPage']);
        $this->router->addRoute('GET', '/admin/logs/:section', [$this, 'renderAdminLogsSection']);
        $this->router->addRoute('POST', '/admin/logs/:section', [$this, 'renderAdminLogsSection']);
        $this->router->addRoute('GET', '/admin/plugins/:plugin/:page', [$this, 'renderPluginPage']);
        $this->router->addRoute('POST', '/admin/plugins/:plugin/:page', [$this, 'renderPluginPage']);
    }

    public function renderAdmin(): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirectUnauthorized();
            return;
        }

        $candidates = [
            ABSPATH . 'admin/index.php',
            ABSPATH . 'admin/modules/dashboard/page.php',
            ABSPATH . 'admin/old/index.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                $this->trackAdminFeature('dashboard', '/admin', 'Dashboard');
                require_once $file;
                return;
            }
        }

        $this->router->render404();
    }

    public function renderAdminPage(string $page): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirectUnauthorized();
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            $this->router->render404();
            return;
        }

        $candidates = [
            ABSPATH . 'admin/' . $page . '.php',
            ABSPATH . 'admin/modules/' . $page . '/page.php',
            ABSPATH . 'admin/old/' . $page . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                $this->trackAdminFeature($page, '/admin/' . $page, $this->humanizeSlug($page));
                require_once $file;
                return;
            }
        }

        $this->router->render404();
    }

    public function renderAdminLogsSection(string $section): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirectUnauthorized();
            return;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $section)) {
            $this->router->render404();
            return;
        }

        $file = ABSPATH . 'admin/logs/' . $section . '.php';
        if (is_file($file)) {
            $this->trackAdminFeature('logs-' . $section, '/admin/logs/' . $section, 'Logs ' . $this->humanizeSlug($section));
            require_once $file;
            return;
        }

        $this->router->render404();
    }

    public function renderPluginPage(string $plugin, string $page): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirectUnauthorized();
            return;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $this->ensureAdminMenuCompatibility();

        try {
            Hooks::doAction('cms_admin_menu');
        } catch (\Throwable $e) {
            $this->logPluginAdminError($plugin, $page, $e, 'menu-registration');
        }

        $menus = function_exists('get_registered_admin_menus') ? get_registered_admin_menus() : [];
        $callback = null;
        $title = 'Plugin Page';

        foreach ($menus as $menu) {
            if (isset($menu['menu_slug']) && $menu['menu_slug'] === $plugin && empty($menu['children']) && $plugin === $page) {
                $callback = $menu['callable'] ?? null;
                $title = $menu['page_title'] ?? $title;
                break;
            }

            if (empty($menu['children'])) {
                continue;
            }

            foreach ($menu['children'] as $child) {
                if (($menu['menu_slug'] === $plugin) && ($child['menu_slug'] === $page)) {
                    $callback = $child['callable'] ?? null;
                    $title = $child['page_title'] ?? $title;
                    break 2;
                }
            }
        }

        if (!$callback || !is_callable($callback)) {
            if ($isAjax) {
                http_response_code(404);
                echo 'Plugin-Adminseite nicht gefunden.';
                return;
            }

            $this->renderPluginAdminShell(
                'Plugin-Adminseite nicht gefunden',
                $page,
                $this->renderPluginUnavailableCard($plugin, $page)
            );
            return;
        }

        try {
            $this->featureUsage->trackFeatureUsage(
                'admin.plugin.' . $plugin . '.' . $page,
                'admin-plugin',
                '/admin/plugins/' . $plugin . '/' . $page,
                isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
                [
                    'label' => $title,
                    'route_group' => $plugin,
                    'plugin_slug' => $plugin,
                ]
            );
        } catch (\Throwable $e) {
            $this->logPluginAdminError($plugin, $page, $e, 'feature-tracking');
        }

        if ($isAjax) {
            try {
                call_user_func($callback);
            } catch (\Throwable $e) {
                $this->logPluginAdminError($plugin, $page, $e, 'ajax-callback');
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Plugin-Adminaktion konnte nicht ausgeführt werden.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            return;
        }

        try {
            $content = $this->capturePluginCallback($callback);
        } catch (\Throwable $e) {
            $this->logPluginAdminError($plugin, $page, $e, 'callback');
            $this->renderPluginAdminShell($title, $page, $this->renderPluginExceptionCard($plugin, $page, $e));
            return;
        }

        if ($this->containsCompleteAdminLayout($content)) {
            echo $this->relocateStylesheetsToHead($content);
            return;
        }

        $stylesheetHrefs = [];
        $content = $this->extractStylesheetHrefs($content, $stylesheetHrefs);
        $this->renderPluginAdminShell($title, $page, $content, $stylesheetHrefs);
    }

    private function ensureAdminMenuCompatibility(): void
    {
        $menuFunctions = ABSPATH . 'includes/functions/admin-menu.php';
        if (is_file($menuFunctions)) {
            require_once $menuFunctions;
        }

        $legacyMenuPartial = ABSPATH . 'admin/partials/admin-menu.php';
        if (is_file($legacyMenuPartial)) {
            require_once $legacyMenuPartial;
        }
    }

    private function capturePluginCallback(callable $callback): string
    {
        $bufferLevel = ob_get_level();
        $layoutDepth = (int) ($GLOBALS['cms_admin_layout_depth'] ?? 0);

        ob_start();
        try {
            call_user_func($callback);

            $safety = 8;
            while (function_exists('cms_admin_layout_is_active') && cms_admin_layout_is_active() && $safety > 0) {
                renderAdminLayoutEnd();
                $safety--;
            }

            $content = ob_get_clean();
            return is_string($content) ? $content : '';
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            if (function_exists('cms_admin_layout_reset')) {
                cms_admin_layout_reset($layoutDepth);
            } else {
                $GLOBALS['cms_admin_layout_depth'] = $layoutDepth;
            }

            throw $e;
        }
    }

    /**
     * Rendert Inhalte in der aktuellen Admin-Shell, wenn ein Plugin selbst kein
     * vollständiges Layout ausgegeben hat.
     *
     * @param array<int, string> $stylesheetHrefs
     */
    private function renderPluginAdminShell(string $title, string $activePage, string $content, array $stylesheetHrefs = []): void
    {
        $pageTitle = $title !== '' ? $title : 'Plugin Page';
        $activePage = $activePage !== '' ? $activePage : 'plugins';
        $pageAssets = [
            'css' => array_values(array_unique(array_filter($stylesheetHrefs, static fn ($href): bool => is_string($href) && trim($href) !== ''))),
        ];

        require ABSPATH . 'admin/partials/header.php';
        require ABSPATH . 'admin/partials/sidebar.php';
        echo $content;
        require ABSPATH . 'admin/partials/footer.php';
    }

    private function containsCompleteAdminLayout(string $content): bool
    {
        $normalized = strtolower($content);

        return str_contains($normalized, '<html')
            && str_contains($normalized, '<body')
            && str_contains($normalized, 'class="page"')
            && str_contains($normalized, '</html>');
    }

    /**
     * @param array<int, string> $stylesheetHrefs
     */
    private function extractStylesheetHrefs(string $content, array &$stylesheetHrefs): string
    {
        $stylesheetHrefs = [];
        $result = preg_replace_callback(
            '~<link\b(?=[^>]*\brel\s*=\s*["\'][^"\']*stylesheet[^"\']*["\'])[^>]*>\s*~i',
            static function (array $matches) use (&$stylesheetHrefs): string {
                if (preg_match('~\bhref\s*=\s*(["\'])(.*?)\1~i', (string) $matches[0], $hrefMatch) === 1) {
                    $href = html_entity_decode((string) $hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (trim($href) !== '') {
                        $stylesheetHrefs[] = $href;
                    }
                }

                return '';
            },
            $content
        );

        $stylesheetHrefs = array_values(array_unique($stylesheetHrefs));

        return is_string($result) ? $result : $content;
    }

    private function relocateStylesheetsToHead(string $content): string
    {
        if (stripos($content, '</head>') === false) {
            return $content;
        }

        $stylesheetLinks = [];
        $withoutLinks = preg_replace_callback(
            '~<link\b(?=[^>]*\brel\s*=\s*["\'][^"\']*stylesheet[^"\']*["\'])[^>]*>\s*~i',
            static function (array $matches) use (&$stylesheetLinks): string {
                $link = trim((string) $matches[0]);
                $normalized = preg_replace('/\s+/', ' ', $link) ?? $link;
                if (!isset($stylesheetLinks[$normalized])) {
                    $stylesheetLinks[$normalized] = $link;
                }

                return '';
            },
            $content
        );

        if (!is_string($withoutLinks) || $stylesheetLinks === []) {
            return $content;
        }

        $linkHtml = "    " . implode("\n    ", array_values($stylesheetLinks)) . "\n";
        $result = preg_replace('~</head>~i', $linkHtml . '</head>', $withoutLinks, 1);

        return is_string($result) ? $result : $content;
    }

    private function renderPluginUnavailableCard(string $plugin, string $page): string
    {
        return '<main class="page-body"><div class="container-xl">'
            . '<div class="alert alert-warning" role="alert">'
            . '<h2 class="alert-title">Plugin-Adminseite nicht gefunden</h2>'
            . '<div>Für <strong>' . htmlspecialchars($plugin, ENT_QUOTES, 'UTF-8') . '</strong> wurde keine passende Admin-Callback-Seite für <strong>' . htmlspecialchars($page, ENT_QUOTES, 'UTF-8') . '</strong> registriert.</div>'
            . '</div></div></main>';
    }

    private function renderPluginExceptionCard(string $plugin, string $page, \Throwable $e): string
    {
        $debugEnabled = defined('CMS_DEBUG') && CMS_DEBUG;
        $details = $debugEnabled
            ? '<details class="mt-3"><summary>Technische Details</summary><pre class="mt-2 mb-0">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre></details>'
            : '';

        return '<main class="page-body"><div class="container-xl">'
            . '<div class="alert alert-danger" role="alert">'
            . '<h2 class="alert-title">Plugin-Adminseite konnte nicht geladen werden</h2>'
            . '<div>Die Seite <strong>' . htmlspecialchars($plugin . ' / ' . $page, ENT_QUOTES, 'UTF-8') . '</strong> hat einen Fehler ausgelöst. Der Fehler wurde protokolliert; die Admin-Shell bleibt weiterhin nutzbar.</div>'
            . $details
            . '</div></div></main>';
    }

    private function logPluginAdminError(string $plugin, string $page, \Throwable $e, string $context): void
    {
        error_log(sprintf(
            '365CMS plugin admin error [%s:%s/%s]: %s in %s:%d',
            $context,
            $plugin,
            $page,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));
    }

    private function trackAdminFeature(string $page, string $routePath, string $label): void
    {
        $this->featureUsage->trackFeatureUsage(
            'admin.' . $page,
            'admin',
            $routePath,
            isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            [
                'label' => $label,
                'route_group' => $page,
            ]
        );
    }

    private function humanizeSlug(string $slug): string
    {
        $label = str_replace(['-', '_'], ' ', $slug);
        $label = preg_replace('/\s+/', ' ', $label) ?? $slug;

        return ucwords(trim($label));
    }

    private function redirectUnauthorized(): void
    {
        if (!Auth::instance()->isLoggedIn()) {
            $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/admin');
            $requestUri = $requestUri !== '' ? $requestUri : '/admin';
            $loginPath = CmsAuthPageService::getInstance()->getPublicPath('login', $this->router->getRequestLocale());
            $this->router->redirect($loginPath . '?redirect=' . urlencode($requestUri) . '&login_error=session_required');
            return;
        }

        $this->router->redirect('/member');
    }
}
