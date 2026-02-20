<?php
/**
 * Subscription Manager
 * 
 * Verwaltet Abo-Pakete, Benutzer-Zuweisungen und Limits
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class SubscriptionManager
{
    private static ?self $instance = null;
    private Database $db;
    private array $cache = [];
    
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
        $this->db = Database::instance();
        // Only create tables when not already verified
        $flagFile = ABSPATH . 'cache/db_schema_subscriptions.flag';
        if (!file_exists($flagFile)) {
            $this->createTables();
            $cacheDir = ABSPATH . 'cache/';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            @file_put_contents($flagFile, date('Y-m-d H:i:s'));
        }
    }
    
    /**
     * Erstellt Datenbank-Tabellen
     */
    public function createTables(): void
    {
        $prefix = $this->db->getPrefix();
        
        // Subscription Plans
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$prefix}subscription_plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                price_monthly DECIMAL(10,2) DEFAULT 0.00,
                price_yearly DECIMAL(10,2) DEFAULT 0.00,
                
                -- Feature Limits
                limit_experts INT DEFAULT -1 COMMENT '-1 = unlimited, 0 = disabled',
                limit_companies INT DEFAULT -1,
                limit_events INT DEFAULT -1,
                limit_speakers INT DEFAULT -1,
                limit_storage_mb INT DEFAULT 1000,
                
                -- Plugin Access
                plugin_experts BOOLEAN DEFAULT 1,
                plugin_companies BOOLEAN DEFAULT 1,
                plugin_events BOOLEAN DEFAULT 1,
                plugin_speakers BOOLEAN DEFAULT 1,
                
                -- Premium Features
                feature_analytics BOOLEAN DEFAULT 0,
                feature_advanced_search BOOLEAN DEFAULT 0,
                feature_api_access BOOLEAN DEFAULT 0,
                feature_custom_branding BOOLEAN DEFAULT 0,
                feature_priority_support BOOLEAN DEFAULT 0,
                feature_export_data BOOLEAN DEFAULT 0,
                feature_integrations BOOLEAN DEFAULT 0,
                feature_custom_domains BOOLEAN DEFAULT 0,
                
                -- Meta
                is_active BOOLEAN DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_slug (slug),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // User Subscriptions
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$prefix}user_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                plan_id INT NOT NULL,
                
                -- Billing
                status ENUM('active', 'cancelled', 'expired', 'trial', 'suspended') DEFAULT 'active',
                billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
                
                -- Dates
                start_date DATETIME NOT NULL,
                end_date DATETIME,
                next_billing_date DATETIME,
                cancelled_at DATETIME,
                
                -- Tracking
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                FOREIGN KEY (plan_id) REFERENCES {$prefix}subscription_plans(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // User Groups
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$prefix}user_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                plan_id INT,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                FOREIGN KEY (plan_id) REFERENCES {$prefix}subscription_plans(id) ON DELETE SET NULL,
                INDEX idx_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // User-Group Assignments
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$prefix}user_group_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                group_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                FOREIGN KEY (group_id) REFERENCES {$prefix}user_groups(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_group (user_id, group_id),
                INDEX idx_user_id (user_id),
                INDEX idx_group_id (group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Usage Tracking
        $this->db->query("
            CREATE TABLE IF NOT EXISTS {$prefix}subscription_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                resource_type VARCHAR(50) NOT NULL COMMENT 'experts, companies, events, speakers, storage',
                current_count INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                UNIQUE KEY unique_user_resource (user_id, resource_type),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }
    
    /**
     * Holt aktives Abo eines Benutzers
     */
    public function getUserSubscription(int $userId): ?object
    {
        $cacheKey = 'user_subscription_' . $userId;
        
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $stmt = $this->db->prepare("
            SELECT us.*, sp.*,
                   us.id as subscription_id,
                   sp.id as plan_id
            FROM {$this->db->prefix()}user_subscriptions us
            JOIN {$this->db->prefix()}subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ?
              AND us.status = 'active'
              AND (us.end_date IS NULL OR us.end_date > NOW())
            ORDER BY us.created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
        
        // Fallback: Check Group Subscription
        if (!$subscription) {
            $subscription = $this->getUserGroupSubscription($userId);
        }
        
        // Fallback: Free Plan
        if (!$subscription) {
            $subscription = $this->getFreePlan();
        }
        
        $this->cache[$cacheKey] = $subscription ?: null;
        return $this->cache[$cacheKey];
    }
    
    /**
     * Holt Abo durch Gruppen-Mitgliedschaft
     */
    private function getUserGroupSubscription(int $userId): ?object
    {
        $stmt = $this->db->prepare("
            SELECT ug.*, sp.*,
                   ug.id as group_id,
                   sp.id as plan_id
            FROM {$this->db->getPrefix()}user_group_members ugm
            JOIN {$this->db->getPrefix()}user_groups ug ON ugm.group_id = ug.id
            JOIN {$this->db->getPrefix()}subscription_plans sp ON ug.plan_id = sp.id
            WHERE ugm.user_id = ?
              AND ug.is_active = 1
              AND sp.is_active = 1
            ORDER BY sp.price_monthly DESC
            LIMIT 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Holt Free Plan
     */
    private function getFreePlan(): ?object
    {
        $stmt = $this->db->query("
            SELECT * FROM {$this->db->getPrefix()}subscription_plans
            LIMIT 1
        ");
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Prüft ob Benutzer Zugriff auf Plugin hat
     */
    public function canAccessPlugin(int $userId, string $pluginSlug): bool
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return false;
        }
        
        $fieldName = 'plugin_' . str_replace('-', '_', $pluginSlug);
        
        return !empty($subscription->{$fieldName});
    }
    
    /**
     * Prüft ob Limit erreicht ist
     */
    public function checkLimit(int $userId, string $resourceType): bool
    {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return false;
        }
        
        $limitField = 'limit_' . str_replace('-', '_', $resourceType);
        $limit = $subscription->{$limitField} ?? 0;
        
        // -1 = unlimited
        if ($limit === -1) {
            return true;
        }
        
        // 0 = disabled
        if ($limit === 0) {
            return false;
        }
        
        // Check current usage
        $currentCount = $this->getCurrentUsage($userId, $resourceType);
        
        return $currentCount < $limit;
    }
    
    /**
     * Holt aktuelle Nutzung
     */
    public function getCurrentUsage(int $userId, string $resourceType): int
    {
        $stmt = $this->db->prepare("
            SELECT current_count
            FROM {$this->db->getPrefix()}subscription_usage
            WHERE user_id = ? AND resource_type = ?
        ");
        
        $stmt->execute([$userId, $resourceType]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result->current_count : 0;
    }
    
    /**
     * Aktualisiert Nutzungszähler
     */
    public function updateUsage(int $userId, string $resourceType, int $count): bool
    {
        $existing = $this->db->prepare("
            SELECT id FROM {$this->db->getPrefix()}subscription_usage
            WHERE user_id = ? AND resource_type = ?
        ");
        $existing->execute([$userId, $resourceType]);
        
        if ($existing->fetch()) {
            return $this->db->update('subscription_usage', 
                ['current_count' => $count],
                ['user_id' => $userId, 'resource_type' => $resourceType]
            );
        } else {
            return $this->db->insert('subscription_usage', [
                'user_id' => $userId,
                'resource_type' => $resourceType,
                'current_count' => $count
            ]) > 0;
        }
    }
    
    /**
     * Weist Benutzer ein Abo zu
     */
    public function assignSubscription(int $userId, int $planId, string $billingCycle = 'monthly'): bool
    {
        // Deaktiviere alte Abos
        $this->db->update('user_subscriptions',
            ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')],
            ['user_id' => $userId, 'status' => 'active']
        );
        
        // Erstelle neues Abo
        $startDate = date('Y-m-d H:i:s');
        $endDate = null;
        $nextBilling = null;
        
        if ($billingCycle === 'monthly') {
            $nextBilling = date('Y-m-d H:i:s', strtotime('+1 month'));
            $endDate = $nextBilling;
        } elseif ($billingCycle === 'yearly') {
            $nextBilling = date('Y-m-d H:i:s', strtotime('+1 year'));
            $endDate = $nextBilling;
        }
        
        $result = $this->db->insert('user_subscriptions', [
            'user_id' => $userId,
            'plan_id' => $planId,
            'status' => 'active',
            'billing_cycle' => $billingCycle,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_billing_date' => $nextBilling
        ]);
        
        // Clear cache
        unset($this->cache['user_subscription_' . $userId]);
        
        Hooks::doAction('subscription_assigned', $userId, $planId);
        
        return $result > 0;
    }
    
    /**
     * Holt alle Pläne
     */
    public function getAllPlans(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM {$this->db->getPrefix()}subscription_plans
            WHERE is_active = 1
            ORDER BY sort_order ASC, price_monthly ASC
        ");
        
        return $stmt->fetchAll() ?: [];
    }
    
    /**
     * Holt Plan nach ID
     */
    public function getPlan(int $planId): ?object
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->db->getPrefix()}subscription_plans
            WHERE id = ?
        ");
        
        $stmt->execute([$planId]);
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Erstellt Standard-Pakete
     */
    public function seedDefaultPlans(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Kostenloser Basis-Zugang mit eingeschränkten Features',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'limit_experts' => 1,
                'limit_companies' => 1,
                'limit_events' => 5,
                'limit_speakers' => 1,
                'limit_storage_mb' => 100,
                'plugin_experts' => 1,
                'plugin_companies' => 0,
                'plugin_events' => 1,
                'plugin_speakers' => 0,
                'feature_analytics' => 0,
                'feature_advanced_search' => 0,
                'feature_api_access' => 0,
                'feature_custom_branding' => 0,
                'feature_priority_support' => 0,
                'feature_export_data' => 0,
                'feature_integrations' => 0,
                'feature_custom_domains' => 0,
                'sort_order' => 1
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Ideal für Einzelpersonen und kleine Projekte',
                'price_monthly' => 9.99,
                'price_yearly' => 99.00,
                'limit_experts' => 5,
                'limit_companies' => 3,
                'limit_events' => 20,
                'limit_speakers' => 5,
                'limit_storage_mb' => 500,
                'plugin_experts' => 1,
                'plugin_companies' => 1,
                'plugin_events' => 1,
                'plugin_speakers' => 1,
                'feature_analytics' => 0,
                'feature_advanced_search' => 1,
                'feature_api_access' => 0,
                'feature_custom_branding' => 0,
                'feature_priority_support' => 0,
                'feature_export_data' => 1,
                'feature_integrations' => 0,
                'feature_custom_domains' => 0,
                'sort_order' => 2
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Für professionelle Anwender und wachsende Teams',
                'price_monthly' => 29.99,
                'price_yearly' => 299.00,
                'limit_experts' => 20,
                'limit_companies' => 10,
                'limit_events' => 100,
                'limit_speakers' => 20,
                'limit_storage_mb' => 2000,
                'plugin_experts' => 1,
                'plugin_companies' => 1,
                'plugin_events' => 1,
                'plugin_speakers' => 1,
                'feature_analytics' => 1,
                'feature_advanced_search' => 1,
                'feature_api_access' => 1,
                'feature_custom_branding' => 1,
                'feature_priority_support' => 0,
                'feature_export_data' => 1,
                'feature_integrations' => 1,
                'feature_custom_domains' => 0,
                'sort_order' => 3
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Für Unternehmen mit erweiterten Anforderungen',
                'price_monthly' => 79.99,
                'price_yearly' => 799.00,
                'limit_experts' => 100,
                'limit_companies' => 50,
                'limit_events' => 500,
                'limit_speakers' => 100,
                'limit_storage_mb' => 10000,
                'plugin_experts' => 1,
                'plugin_companies' => 1,
                'plugin_events' => 1,
                'plugin_speakers' => 1,
                'feature_analytics' => 1,
                'feature_advanced_search' => 1,
                'feature_api_access' => 1,
                'feature_custom_branding' => 1,
                'feature_priority_support' => 1,
                'feature_export_data' => 1,
                'feature_integrations' => 1,
                'feature_custom_domains' => 1,
                'sort_order' => 4
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Premium-Paket mit allen Features und hohen Limits',
                'price_monthly' => 149.99,
                'price_yearly' => 1499.00,
                'limit_experts' => 500,
                'limit_companies' => 200,
                'limit_events' => 2000,
                'limit_speakers' => 500,
                'limit_storage_mb' => 50000,
                'plugin_experts' => 1,
                'plugin_companies' => 1,
                'plugin_events' => 1,
                'plugin_speakers' => 1,
                'feature_analytics' => 1,
                'feature_advanced_search' => 1,
                'feature_api_access' => 1,
                'feature_custom_branding' => 1,
                'feature_priority_support' => 1,
                'feature_export_data' => 1,
                'feature_integrations' => 1,
                'feature_custom_domains' => 1,
                'sort_order' => 5
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unbegrenzte Möglichkeiten für große Organisationen',
                'price_monthly' => 499.99,
                'price_yearly' => 4999.00,
                'limit_experts' => -1,
                'limit_companies' => -1,
                'limit_events' => -1,
                'limit_speakers' => -1,
                'limit_storage_mb' => 200000,
                'plugin_experts' => 1,
                'plugin_companies' => 1,
                'plugin_events' => 1,
                'plugin_speakers' => 1,
                'feature_analytics' => 1,
                'feature_advanced_search' => 1,
                'feature_api_access' => 1,
                'feature_custom_branding' => 1,
                'feature_priority_support' => 1,
                'feature_export_data' => 1,
                'feature_integrations' => 1,
                'feature_custom_domains' => 1,
                'sort_order' => 6
            ]
        ];
        
        foreach ($plans as $plan) {
            // Check if exists
            $stmt = $this->db->prepare("SELECT id FROM {$this->db->getPrefix()}subscription_plans WHERE slug = ?");
            $stmt->execute([$plan['slug']]);
            
            if (!$stmt->fetch()) {
                $this->db->insert('subscription_plans', $plan);
            }
        }
    }
}
