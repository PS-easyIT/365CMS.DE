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

    public function renderPluginPage(string $plugin, string $page): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->redirectUnauthorized();
            return;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (file_exists(ABSPATH . 'admin/partials/admin-menu.php')) {
            require_once ABSPATH . 'admin/partials/admin-menu.php';
        }

        Hooks::doAction('cms_admin_menu');

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
            $this->router->render404();
            return;
        }

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

        if ($isAjax) {
            call_user_func($callback);
            return;
        }

        if (function_exists('renderAdminLayoutStart') && function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutStart($title, $page);
            call_user_func($callback);
            renderAdminLayoutEnd();
            return;
        }

        $pageTitle = $title;
        $activePage = $page;

        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
        call_user_func($callback);
        require_once ABSPATH . 'admin/partials/footer.php';
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
            $this->router->redirect('/cms-login?redirect=' . urlencode($requestUri));
            return;
        }

        $this->router->redirect('/member');
    }
}
