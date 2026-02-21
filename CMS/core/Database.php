<?php
/**
 * Database Class - PDO Wrapper with Security
 * 
 * Secure database abstraction layer with prepared statements
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

use PDO;
use PDOException;

if (!defined('ABSPATH')) {
    exit;
}

class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    public string $prefix = 'cms_';
    public string $last_error = '';
    private ?\PDOStatement $lastStatement = null;
    
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
     * Alias for instance() – WordPress-style naming used by plugins
     */
    public static function get_instance(): self
    {
        return self::instance();
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->connect();
        
        // Only create tables if they don't exist - don't fail if there's an error
        try {
            $this->createTables();
        } catch (\Exception $e) {
            error_log('Database::createTables() warning: ' . $e->getMessage());
            // Don't throw - allow the app to continue
        }
    }
    
    /**
     * Connect to database
     */
    private function connect(): void
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                // Emulated prepares required for LIMIT/OFFSET bound parameters
                // on MariaDB and older MySQL versions.
                PDO::ATTR_EMULATE_PREPARES   => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (CMS_DEBUG) {
                error_log('Database connected successfully');
            }
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            $this->pdo = null;
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create required tables (Public wrapper for repair)
     */
    public function repairTables(): void
    {
        // Force-recreate tables (admin repair tool)
        $flagFile = ABSPATH . 'cache/db_schema_v3.flag';
        @unlink($flagFile);
        $this->createTables();
    }
    
    /**
     * Create required tables
     * Uses a flag-file to avoid running 15+ SQL queries on every page load.
     */
    private function createTables(): void
    {
        // Skip if tables were already verified this session
        $flagFile = ABSPATH . 'cache/db_schema_v3.flag';
        if (file_exists($flagFile)) {
            return;
        }
        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(60) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'member',
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                INDEX idx_username (username),
                INDEX idx_email (email),
                INDEX idx_role (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // User meta table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}user_meta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                INDEX idx_user_id (user_id),
                INDEX idx_meta_key (meta_key),
                FOREIGN KEY (user_id) REFERENCES {$this->prefix}users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Roles table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT,
                capabilities TEXT COMMENT 'JSON-Array mit Berechtigungen',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Settings table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                option_name VARCHAR(255) NOT NULL UNIQUE,
                option_value LONGTEXT,
                autoload TINYINT(1) DEFAULT 1,
                INDEX idx_key (option_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Sessions table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}sessions (
                id VARCHAR(128) PRIMARY KEY,
                user_id INT UNSIGNED,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                payload TEXT,
                last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_last_activity (last_activity),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Pages table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}pages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(200) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT,
                excerpt TEXT,
                status VARCHAR(20) DEFAULT 'draft',
                hide_title TINYINT(1) NOT NULL DEFAULT 0,
                author_id INT UNSIGNED,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at TIMESTAMP NULL,
                INDEX idx_slug (slug),
                INDEX idx_status (status),
                INDEX idx_author (author_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Page revisions table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}page_revisions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                page_id INT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT,
                excerpt TEXT,
                author_id INT UNSIGNED,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_page_id (page_id),
                INDEX idx_author (author_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Landing sections table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}landing_sections (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                data TEXT,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Activity log table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}activity_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(100),
                entity_id BIGINT UNSIGNED,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                metadata LONGTEXT COMMENT 'JSON-Daten',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_action (action),
                INDEX idx_entity_type (entity_type),
                INDEX idx_entity_id (entity_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Cache table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}cache (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                cache_key VARCHAR(191) NOT NULL UNIQUE,
                cache_value LONGTEXT,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_key (cache_key),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Login attempts (security)
            "CREATE TABLE IF NOT EXISTS {$this->prefix}login_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(60),
                ip_address VARCHAR(45),
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_ip (ip_address),
                INDEX idx_time (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Plugins table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}plugins (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                version VARCHAR(20) NOT NULL,
                author VARCHAR(100),
                description TEXT,
                plugin_path VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 0,
                auto_update TINYINT(1) DEFAULT 0,
                settings LONGTEXT COMMENT 'JSON-Daten für Plugin-Einstellungen',
                installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                activated_at TIMESTAMP NULL,
                INDEX idx_slug (slug),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Plugin meta table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}plugin_meta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                INDEX idx_plugin_id (plugin_id),
                INDEX idx_meta_key (meta_key),
                FOREIGN KEY (plugin_id) REFERENCES {$this->prefix}plugins(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Theme customizations table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}theme_customizations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                theme_slug VARCHAR(100) NOT NULL,
                setting_category VARCHAR(100) NOT NULL COMMENT 'Kategorie aus theme.json (colors, typography, etc.)',
                setting_key VARCHAR(255) NOT NULL COMMENT 'Einstellungs-Key aus theme.json',
                setting_value LONGTEXT COMMENT 'Wert der Einstellung',
                user_id INT UNSIGNED NULL COMMENT 'Optional: User-spezifische Anpassungen',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_theme_slug (theme_slug),
                INDEX idx_category (setting_category),
                INDEX idx_key (setting_key),
                INDEX idx_user_id (user_id),
                UNIQUE KEY unique_theme_setting (theme_slug, setting_category, setting_key, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Subscription plans table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}subscription_plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                price_monthly DECIMAL(10,2) DEFAULT 0.00,
                price_yearly DECIMAL(10,2) DEFAULT 0.00,
                limit_experts INT DEFAULT -1,
                limit_companies INT DEFAULT -1,
                limit_events INT DEFAULT -1,
                limit_speakers INT DEFAULT -1,
                limit_storage_mb INT DEFAULT 1000,
                plugin_experts BOOLEAN DEFAULT 1,
                plugin_companies BOOLEAN DEFAULT 1,
                plugin_events BOOLEAN DEFAULT 1,
                plugin_speakers BOOLEAN DEFAULT 1,
                feature_analytics BOOLEAN DEFAULT 0,
                feature_advanced_search BOOLEAN DEFAULT 0,
                feature_api_access BOOLEAN DEFAULT 0,
                feature_custom_branding BOOLEAN DEFAULT 0,
                feature_priority_support BOOLEAN DEFAULT 0,
                feature_export_data BOOLEAN DEFAULT 0,
                feature_integrations BOOLEAN DEFAULT 0,
                feature_custom_domains BOOLEAN DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_active (is_active),
                INDEX idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // User subscriptions table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}user_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                plan_id INT NOT NULL,
                status ENUM('active', 'cancelled', 'expired', 'trial', 'suspended') DEFAULT 'active',
                billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
                start_date DATETIME NOT NULL,
                end_date DATETIME,
                next_billing_date DATETIME,
                cancelled_at DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_plan_id (plan_id),
                INDEX idx_status (status),
                INDEX idx_dates (start_date, end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // User groups table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}user_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                plan_id INT,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // User group members table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}user_group_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                group_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_group (user_id, group_id),
                INDEX idx_user_id (user_id),
                INDEX idx_group_id (group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Subscription usage table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}subscription_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                resource_type VARCHAR(50) NOT NULL,
                current_count INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_resource (user_id, resource_type),
                INDEX idx_user_id (user_id),
                INDEX idx_resource (resource_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Blog post categories table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}post_categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                parent_id INT UNSIGNED DEFAULT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Blog posts table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}posts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                content LONGTEXT,
                excerpt TEXT,
                featured_image VARCHAR(500),
                status ENUM('draft','published','trash') NOT NULL DEFAULT 'draft',
                author_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED DEFAULT NULL,
                tags VARCHAR(500) COMMENT 'Kommagetrennte Tags',
                views INT UNSIGNED DEFAULT 0,
                allow_comments TINYINT(1) NOT NULL DEFAULT 1,
                meta_title VARCHAR(255),
                meta_description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at TIMESTAMP NULL,
                INDEX idx_slug (slug),
                INDEX idx_status (status),
                INDEX idx_author (author_id),
                INDEX idx_category (category_id),
                INDEX idx_published (published_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Orders table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(64) NOT NULL UNIQUE,
                user_id INT UNSIGNED NULL,
                plan_id INT NOT NULL,
                status ENUM('pending', 'confirmed', 'cancelled', 'refunded') DEFAULT 'pending',
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(3) DEFAULT 'EUR',
                payment_method VARCHAR(50) DEFAULT NULL,
                billing_cycle ENUM('monthly', 'yearly', 'lifetime') DEFAULT 'monthly',
                
                -- Contact & Billing Data
                forename VARCHAR(100),
                lastname VARCHAR(100),
                company VARCHAR(100),
                email VARCHAR(150),
                phone VARCHAR(50),
                street VARCHAR(255),
                zip VARCHAR(20),
                city VARCHAR(100),
                country VARCHAR(100),
                
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_order_number (order_number),
                INDEX idx_user_id (user_id),
                INDEX idx_plan_id (plan_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Blocked IPs table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}blocked_ips (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                reason VARCHAR(255),
                expires_at DATETIME,
                permanent TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Failed Logins table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}failed_logins (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(60),
                ip_address VARCHAR(45),
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_agent VARCHAR(255),
                INDEX idx_username (username),
                INDEX idx_ip (ip_address),
                INDEX idx_time (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,
            
            // Media table
            "CREATE TABLE IF NOT EXISTS {$this->prefix}media (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                filepath VARCHAR(500) NOT NULL,
                filetype VARCHAR(50),
                filesize INT UNSIGNED,
                title VARCHAR(255),
                alt_text VARCHAR(255),
                caption TEXT,
                uploaded_by INT UNSIGNED,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (filetype),
                INDEX idx_uploader (uploaded_by)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET,

            // Page Views table (Analytics)
            "CREATE TABLE IF NOT EXISTS {$this->prefix}page_views (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET
        ];
        
        foreach ($queries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                error_log('Table creation failed: ' . $e->getMessage());
            }
        }
        
        // Create default admin user if not exists
        $this->createDefaultAdmin();
        
        // Write flag file so subsequent requests skip this method
        $cacheDir = ABSPATH . 'cache/';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($flagFile, date('Y-m-d H:i:s'));
    }
    
    /**
     * Create default admin user
     */
    private function createDefaultAdmin(): void
    {
        try {
            $stmt = $this->prepare("SELECT COUNT(*) as count FROM {$this->prefix}users WHERE role = 'admin'");
            
            if (!$stmt) {
                error_log('Database::createDefaultAdmin() - prepare() failed');
                return;
            }
            
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result->count == 0) {
                $this->insert('users', [
                    'username' => 'admin',
                    'email' => ADMIN_EMAIL,
                    'password' => password_hash('admin123', PASSWORD_BCRYPT),
                    'display_name' => 'Administrator',
                    'role' => 'admin',
                    'status' => 'active'
                ]);
            }
        } catch (\Exception $e) {
            error_log('Database::createDefaultAdmin() Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare statement
     */
    public function prepare(string $sql): \PDOStatement|false
    {
        if ($this->pdo === null) {
            error_log('Database::prepare() - CRITICAL: PDO connection is null!');
            error_log('SQL attempted: ' . $sql);
            error_log('DB Config - Host: ' . DB_HOST . ', Name: ' . DB_NAME . ', User: ' . DB_USER);
            throw new \RuntimeException('Database connection is not available. PDO is null.');
        }
        
        try {
            $stmt = $this->pdo->prepare($sql);
            if ($stmt === false) {
                error_log('Database::prepare() - prepare() returned false: ' . $sql);
                $errorInfo = $this->pdo->errorInfo();
                error_log('PDO Error: ' . json_encode($errorInfo));
                throw new \RuntimeException('PDO prepare failed: ' . ($errorInfo[2] ?? 'Unknown error'));
            }
            return $stmt;
        } catch (\PDOException $e) {
            error_log('Database::prepare() Exception: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new \RuntimeException('Database prepare error: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute query
     */
    public function query(string $sql): \PDOStatement
    {
        return $this->pdo->query($sql);
    }

    /**
     * Bind parameters to a prepared statement with correct PDO types.
     * With ATTR_EMULATE_PREPARES => true PDO would otherwise quote every value
     * as a string, making  LIMIT '20' OFFSET '0'  which MariaDB rejects.
     */
    private function bindParams(\PDOStatement $stmt, array $params): void
    {
        foreach (array_values($params) as $i => $value) {
            if (is_int($value)) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_INT);
            } elseif (is_bool($value)) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_BOOL);
            } elseif ($value === null) {
                $stmt->bindValue($i + 1, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($i + 1, (string) $value, PDO::PARAM_STR);
            }
        }
    }

    /**
     * Execute query with parameters (prepared statement)
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Insert data (mit Error-Tracking)
     */
    public function insert(string $table, array $data): int|bool
    {
        try {
            $table = $this->prefix . $table;
            $keys = array_keys($data);
            $fields = implode(', ', $keys);
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));

            $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
            $this->lastStatement = $this->prepare($sql);
            $this->bindParams($this->lastStatement, array_values($data));
            $result = $this->lastStatement->execute();

            if (!$result) {
                $error = $this->lastStatement->errorInfo();
                $this->last_error = $error[2] ?? 'Unknown error';
                return false;
            }

            return (int) $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Update data (mit Error-Tracking)
     */
    public function update(string $table, array $data, array $where): bool
    {
        try {
            $table = $this->prefix . $table;
            $set = [];
            $values = [];

            foreach ($data as $key => $value) {
                $set[] = "{$key} = ?";
                $values[] = $value;
            }

            $whereClauses = [];
            foreach ($where as $key => $value) {
                $whereClauses[] = "{$key} = ?";
                $values[] = $value;
            }

            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClauses);
            $this->lastStatement = $this->prepare($sql);
            $this->bindParams($this->lastStatement, $values);
            $result = $this->lastStatement->execute();

            if (!$result) {
                $error = $this->lastStatement->errorInfo();
                $this->last_error = $error[2] ?? 'Unknown error';
                return false;
            }

            return true;
        } catch (\PDOException $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    /**
     * Delete data
     */
    public function delete(string $table, array $where): bool
    {
        $table = $this->prefix . $table;
        $whereClauses = [];
        $values = [];

        foreach ($where as $key => $value) {
            $whereClauses[] = "{$key} = ?";
            $values[] = $value;
        }

        $sql  = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClauses);
        $stmt = $this->prepare($sql);
        $this->bindParams($stmt, $values);
        return $stmt->execute();
    }

    /**
     * WordPress-compatible: Get single row as object
     */
    public function get_row(string $query, array $params = []): ?object
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result ?: null;
    }

    /**
     * WordPress-compatible: Get single variable (column value)
     */
    public function get_var(string $query, array $params = []): mixed
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * WordPress-compatible: Get multiple rows as objects
     */
    public function get_results(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * WordPress-compatible: Get column values as array
     */
    public function get_col(string $query, array $params = []): array
    {
        $stmt = $this->prepare($query);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get affected rows from last operation
     */
    public function affected_rows(): int
    {
        if ($this->lastStatement) {
            return $this->lastStatement->rowCount();
        }
        return 0;
    }
    
    /**
     * Get last insert ID
     */
    public function insert_id(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
    
    /**
     * Get table prefix
     *
     * @deprecated Use getPrefix() instead
     * Accepts an optional table name: prefix('users') returns 'cms_users'.
     */
    public function prefix(string $table = ''): string
    {
        return $table !== '' ? $this->prefix . $table : $this->prefix;
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Get connection (alias for getPdo for compatibility)
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Get table prefix
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Fetch all rows as an associative array
     */
    public static function fetchAll(string $query, array $params = []): array
    {
        $db = self::instance();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch single row as an associative array
     */
    public static function fetchOne(string $query, array $params = []): ?array
    {
        $db = self::instance();
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Execute a query with parameters (Static wrapper)
     */
    public static function exec(string $query, array $params = []): bool
    {
        $db = self::instance();
        if (empty($params)) {
            return (bool) $db->query($query);
        }
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    }
}
