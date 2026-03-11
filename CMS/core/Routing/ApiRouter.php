<?php
/**
 * API Router Module
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS\Routing;

use CMS\Api;
use CMS\Auth;
use CMS\Database;
use CMS\Router;
use CMS\Security;
use CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class ApiRouter
{
    public function __construct(private readonly Router $router)
    {
    }

    public function registerRoutes(): void
    {
        $this->router->addRoute('GET', '/api/v1/status', [$this, 'status']);
        $this->router->addRoute('GET', '/api/v1/pages', [$this, 'pages']);
        $this->router->addRoute('GET', '/api/v1/pages/:slug', [$this, 'pageBySlug']);
        $this->router->addRoute('POST', '/api/v1/analytics/web-vitals', [$this, 'captureWebVitals']);

        $this->router->addRoute('GET', '/api/v1/admin/posts', [$this, 'jsonAdminPosts']);
        $this->router->addRoute('GET', '/api/v1/admin/pages', [$this, 'jsonAdminPages']);
        $this->router->addRoute('GET', '/api/v1/admin/users', [$this, 'jsonAdminUsers']);
        $this->router->addRoute('GET', '/api/v1/admin/mail/logs', [$this, 'jsonAdminMailLogs']);
        $this->router->addRoute('POST', '/api/v1/admin/mail/test', [$this, 'testAdminMail']);
        $this->router->addRoute('POST', '/api/v1/admin/graph/test', [$this, 'testAdminGraph']);
        $this->router->addRoute('GET', '/api/v1/admin/media/elfinder', [$this, 'handleElfinder']);
        $this->router->addRoute('POST', '/api/v1/admin/media/elfinder', [$this, 'handleElfinder']);

        $this->router->addRoute('POST', '/api/upload', [$this, 'upload']);
        $this->router->addRoute('GET', '/api/media', [$this, 'media']);
        $this->router->addRoute('POST', '/api/media', [$this, 'media']);
    }

    public function status(): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'version' => defined('CMS_VERSION') ? CMS_VERSION : '2.5.30',
        ]);
        exit;
    }

    public function pages(): void
    {
        Api::instance()->handleRequest('pages');
    }

    public function pageBySlug(string $slug): void
    {
        Api::instance()->handleRequest('pages', $slug);
    }

    public function captureWebVitals(): void
    {
        if (!$this->isSameOriginRequest()) {
            http_response_code(204);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = [];

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if ($payload === []) {
            $payload = $_POST;
        }

        Services\CoreWebVitalsService::getInstance()->storeReport(is_array($payload) ? $payload : []);
        http_response_code(204);
        exit;
    }

    public function jsonAdminMailLogs(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(200, max(10, (int)($_GET['limit'] ?? 50)));
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));

        $result = Services\MailLogService::getInstance()->getRecent($limit, $page, $search, $status);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function testAdminMail(): void
    {
        $this->requireAdmin();

        $token = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::instance()->verifyToken($token, 'admin_mail_api')) {
            $this->denyJson('Sicherheitsüberprüfung fehlgeschlagen.');
        }

        $recipient = trim((string)($_POST['recipient'] ?? ''));
        $result = Services\MailService::getInstance()->sendBackendTestEmail($recipient, 'admin-mail-api');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function testAdminGraph(): void
    {
        $this->requireAdmin();

        $token = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if (!Security::instance()->verifyToken($token, 'admin_mail_api')) {
            $this->denyJson('Sicherheitsüberprüfung fehlgeschlagen.');
        }

        $result = Services\GraphApiService::getInstance()->testConnection(true);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function handleElfinder(): void
    {
        $this->requireAdmin();
        $token = (string)($_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
        if (!Security::instance()->verifyPersistentToken($token, 'media_connector')) {
            $this->denyJson('Sicherheitsüberprüfung fehlgeschlagen.');
        }

        Services\ElfinderService::getInstance()->handleConnectorRequest();
    }

    public function upload(): void
    {
        $result = Services\FileUploadService::getInstance()->handleUploadRequest();
        http_response_code((int)($result['status'] ?? 200));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result['data'] ?? ['error' => 'Unbekannter Fehler']);
        exit;
    }

    public function media(): void
    {
        Services\EditorJsService::getInstance()->handleMediaApiRequest();
    }

    public function jsonAdminPosts(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $db = Database::instance();
        $prefix = $db->getPrefix();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? 'all'));
        $category = max(0, (int)($_GET['category'] ?? 0));
        $sort = in_array($_GET['sort'] ?? '', ['title', 'status', 'published_at', 'views', 'updated_at'], true)
            ? (string)$_GET['sort']
            : 'updated_at';
        $order = strtoupper((string)($_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $where = [];
        $params = [];
        if ($status === 'all') {
            $where[] = "p.status != 'trash'";
        } elseif (in_array($status, ['published', 'draft', 'trash'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[] = '(p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $category;
        }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts p {$whereStr}", $params);
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
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    public function jsonAdminPages(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $db = Database::instance();
        $prefix = $db->getPrefix();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = trim((string)($_GET['search'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $sort = in_array($_GET['sort'] ?? '', ['title', 'slug', 'status', 'updated_at', 'created_at'], true)
            ? (string)$_GET['sort']
            : 'updated_at';
        $order = strtoupper((string)($_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $where = [];
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

        $total = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}pages p {$whereStr}", $params);
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
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    public function jsonAdminUsers(): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $db = Database::instance();
        $prefix = $db->getPrefix();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(5, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $search = trim((string)($_GET['search'] ?? ''));
        $role = trim((string)($_GET['role'] ?? 'all'));
        $status = trim((string)($_GET['status'] ?? ''));
        $sort = in_array($_GET['sort'] ?? '', ['username', 'email', 'display_name', 'role', 'status', 'created_at'], true)
            ? (string)$_GET['sort']
            : 'created_at';
        $order = strtoupper((string)($_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $where = [];
        $params = [];
        if ($role === 'banned') {
            $where[] = "u.status = 'banned'";
        } elseif ($role !== 'all' && $role !== '') {
            $where[] = 'u.role = ?';
            $params[] = $role;
        }
        if ($status !== '' && in_array($status, ['active', 'inactive', 'banned'], true)) {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.display_name LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users u {$whereStr}", $params);
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
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
        exit;
    }

    private function requireAdmin(): void
    {
        if (!Auth::instance()->isAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Zugriff verweigert']);
            exit;
        }
    }

    private function denyJson(string $message): never
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }

    private function isSameOriginRequest(): bool
    {
        $siteHost = (string)parse_url((string)SITE_URL, PHP_URL_HOST);
        if ($siteHost === '') {
            return false;
        }

        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
            $value = trim((string)($_SERVER[$header] ?? ''));
            if ($value === '') {
                continue;
            }

            $host = (string)parse_url($value, PHP_URL_HOST);
            if ($host === '' || strcasecmp($host, $siteHost) !== 0) {
                return false;
            }

            return true;
        }

        return true;
    }
}
