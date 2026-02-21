<?php
/**
 * Tracking Service
 * 
 * Tracks page views, user interactions, and events
 * 
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

class TrackingService
{
    private static ?self $instance = null;
    private Database $db;
    
    /**
     * Singleton instance
     */
    public static function getInstance(): self
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
        $this->db = Database::instance();
        $this->createTrackingTable();
    }
    
    /**
     * Create tracking table if not exists
     */
    private function createTrackingTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db->getPrefix()}page_views (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id INT UNSIGNED NULL,
            page_slug VARCHAR(200),
            page_title VARCHAR(255),
            user_id INT UNSIGNED NULL,
            session_id VARCHAR(128),
            ip_address VARCHAR(45),
            user_agent VARCHAR(500),
            referrer VARCHAR(500),
            visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page_id (page_id),
            INDEX idx_page_slug (page_slug),
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_visited_at (visited_at),
            INDEX idx_date (visited_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $this->db->query($sql);
        } catch (\Exception $e) {
            error_log('TrackingService: Could not create page_views table: ' . $e->getMessage());
        }
    }
    
    /**
     * Track page view
     */
    public function trackPageView(
        ?int $pageId,
        string $pageSlug,
        string $pageTitle,
        ?int $userId = null
    ): bool {
        try {
            return $this->db->insert('page_views', [
                'page_id' => $pageId,
                'page_slug' => $pageSlug,
                'page_title' => $pageTitle,
                'user_id' => $userId,
                'session_id' => session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            ]) !== false;
        } catch (\Exception $e) {
            error_log('TrackingService::trackPageView() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get page views for date range
     */
    public function getPageViewsByDate(int $days = 30): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT DATE(visited_at) as date, COUNT(*) as views
                FROM {$this->db->getPrefix()}page_views
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(visited_at)
                ORDER BY date ASC
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute([$days]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Fill missing dates with 0
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $data[$date] = 0;
            }
            
            foreach ($results as $row) {
                $data[$row['date']] = (int)$row['views'];
            }
            
            return $data;
        } catch (\Exception $e) {
            error_log('TrackingService::getPageViewsByDate() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get top pages by views
     */
    public function getTopPages(int $days = 30, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    page_slug,
                    page_title,
                    COUNT(*) as views,
                    COUNT(DISTINCT session_id) as unique_visitors
                FROM {$this->db->getPrefix()}page_views
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY page_slug, page_title
                ORDER BY views DESC
                LIMIT ?
            ");
            
            if (!$stmt) {
                return [];
            }
            
            $stmt->execute([$days, $limit]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('TrackingService::getTopPages() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unique visitors count
     */
    public function getUniqueVisitors(int $days = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT session_id) as count
                FROM {$this->db->getPrefix()}page_views
                WHERE visited_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            if (!$stmt) {
                return 0;
            }
            
            $stmt->execute([$days]);
            $result = $stmt->fetch(\PDO::FETCH_OBJ);
            
            return $result ? (int)$result->count : 0;
        } catch (\Exception $e) {
            error_log('TrackingService::getUniqueVisitors() Error: ' . $e->getMessage());
            return 0;
        }
    }
}
