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
    
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Handle API Request
     * /api/v1/{endpoint}/{id}
     */
    public function handleRequest(string $endpoint, ?string $id = null): void
    {
        header('Content-Type: application/json');
        
        try {
            switch ($endpoint) {
                case 'status':
                    $this->sendResponse(['status' => 'ok', 'version' => '2.0.0']);
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
        } catch (\Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }
    
    private function handlePages(?string $slug): void
    {
        // Public API requires auth for page listing/search
        if (!Auth::instance()->isLoggedIn()) {
            $this->sendError('Unauthorized', 401);
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
            $query = $_GET['q'] ?? '';
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
            $stmt = $db->prepare("SELECT id, username, email, role FROM {$db->prefix()}users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetchObject();
            $this->sendResponse($user);
        } else {
            $stmt = $db->query("SELECT id, username, email, role FROM {$db->prefix()}users LIMIT 50");
            $this->sendResponse($stmt->fetchAll(\PDO::FETCH_CLASS));
        }
    }

    private function sendResponse($data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode(['data' => $data]);
        exit;
    }
    
    private function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}
