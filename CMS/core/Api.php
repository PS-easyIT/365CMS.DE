<?php
/**
 * API Controller
 * 
 * Handles REST API V1 Requests
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Api
{
    private static ?self $instance = null;

    private const GENERIC_SERVER_ERROR_MESSAGE = 'Internal server error';
    private const MAX_SEARCH_QUERY_LENGTH = 120;
    private const MAX_SLUG_LENGTH = 200;
    
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor – Singleton erzwingen.
     */
    private function __construct() {}  // Singleton: nur via instance() instanziieren

    /**
     * Handle API Request
     * /api/v1/{endpoint}/{id}
     * M-19: Rate-Limiting für alle API-Endpunkte.
     */
    public function handleRequest(string $endpoint, ?string $id = null): void
    {
        header('Content-Type: application/json');

        // M-19: API-Rate-Limiting – max. 60 Anfragen / 60 s pro IP
        $security = Security::instance();
        if (!$security->checkDbRateLimit(
            $security->getClientIp(),
            'api',
            60,   // max. Versuche
            60    // Zeitfenster in Sekunden
        )) {
            http_response_code(429);
            header('Retry-After: 60');
            echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
            exit;
        }
        Security::recordDbRateLimitAttempt($security->getClientIp(), 'api');
        
        try {
            switch ($endpoint) {
                case 'status':
                    $this->sendResponse(['status' => 'ok', 'version' => defined('CMS_VERSION') ? CMS_VERSION : Version::CURRENT]);
                    break;
                    
                case 'pages':
                    $this->handlePages($id);
                    break;
                    
                case 'users':
                    $this->handleUsers($id);
                    break;
                    
                default:
                    $this->sendError('Endpoint not found', 404);
            }
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('api')->error('API request failed.', [
                'endpoint' => $endpoint,
                'resource_id' => $id,
                'exception' => $e,
            ]);
            $this->sendError(self::GENERIC_SERVER_ERROR_MESSAGE, 500);
        }
    }
    
    private function handlePages(?string $slug): void
    {
        // Public API requires auth for page listing/search
        if (!Auth::instance()->isLoggedIn()) {
            $this->sendError('Unauthorized', 401);
        }

        $slug = $slug !== null ? $this->normalizeSlug($slug) : null;
        if ($slug === '') {
            $this->sendError('Invalid page slug', 400);
        }
        
        $pm = PageManager::instance();
        
        if ($slug) {
            $page = $pm->getPageBySlug($slug);
            if ($page) {
                $this->sendResponse($page);
            } else {
                $this->sendError('Page not found', 404);
            }
        } else {
            // List pages (search)
            $query = $this->normalizeSearchQuery($_GET['q'] ?? '');
            $pages = $pm->search($query);
            $this->sendResponse($pages);
        }
    }
    
    private function handleUsers(?string $id): void
    {
        if (!Auth::instance()->isAdmin()) {
            $this->sendError('Forbidden', 403);
        }
        
        $db = Database::instance();
        if ($id) {
            $userId = $this->normalizePositiveId($id);
            if ($userId < 1) {
                $this->sendError('Invalid user id', 400);
            }

            $stmt = $db->prepare("SELECT id, username, email, role FROM {$db->getPrefix()}users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetchObject();
            $this->sendResponse($user);
        } else {
            $stmt = $db->prepare("SELECT id, username, email, role FROM {$db->getPrefix()}users LIMIT ?");
            $stmt->bindValue(1, 50, \PDO::PARAM_INT);
            $stmt->execute();
            $this->sendResponse($stmt->fetchAll(\PDO::FETCH_OBJ));
        }
    }

    private function sendResponse($data, int $code = 200): void
    {
        http_response_code($code);
        $json = json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo is_string($json) ? $json : '{"error":"Internal server error"}';
        exit;
    }
    
    private function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        $json = json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo is_string($json) ? $json : '{"error":"Internal server error"}';
        exit;
    }

    private function normalizeSearchQuery(mixed $value): string
    {
        $query = trim((string) $value);
        $query = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $query) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($query, 0, self::MAX_SEARCH_QUERY_LENGTH, 'UTF-8')
            : substr($query, 0, self::MAX_SEARCH_QUERY_LENGTH);
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = trim(rawurldecode($slug));
        $slug = preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', $slug) ?? '';

        $length = function_exists('mb_strlen') ? mb_strlen($slug, 'UTF-8') : strlen($slug);
        if ($slug === '' || $length > self::MAX_SLUG_LENGTH) {
            return '';
        }

        return preg_match('/^[\p{L}\p{N}][\p{L}\p{N}._-]*$/u', $slug) === 1 ? $slug : '';
    }

    private function normalizePositiveId(mixed $value): int
    {
        if (is_int($value)) {
            return max(0, $value);
        }

        $value = trim((string) $value);
        if ($value === '' || preg_match('/^[1-9][0-9]{0,9}$/', $value) !== 1) {
            return 0;
        }

        return (int) $value;
    }
}
