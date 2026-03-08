<?php
/**
 * SchemaManager – DB-Schema-Erstell-Klasse (H-10)
 *
 * Verantwortlich für:
 * - Erstellung aller CMS-Tabellen (CREATE TABLE IF NOT EXISTS)
 * - Anlegen des Standard-Admin-Accounts beim Erst-Setup
 *
 * Ausgelagert aus Database.php, um die God-Klasse aufzuteilen.
 * Wird von Database::__construct() und Database::repairTables() aufgerufen.
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

class SchemaManager
{
    /** Flag-Datei-Version – erhöhen wenn Schema geändert wird */
    public const SCHEMA_VERSION = 'v11';

    private Database $db;
    private string $prefix;
    private string $charset;

    public function __construct(Database $db)
    {
        $this->db     = $db;
        $this->prefix = $db->getPrefix();
        $this->charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
    }

    /**
     * Gibt den Pfad zur Flag-Datei zurück.
     */
    public function getFlagFile(): string
    {
        return ABSPATH . 'cache/db_schema_' . self::SCHEMA_VERSION . '.flag';
    }

    /**
     * Erzwingt eine komplette Neu-Prüfung (Flag löschen).
     */
    public function clearFlag(): void
    {
        $flagFile = $this->getFlagFile();
        if (is_file($flagFile)) {
            unlink($flagFile); // M-03: kein @, is_file geprüft
        }
    }

    /**
     * Erstellt alle CMS-Tabellen (idempotent via CREATE TABLE IF NOT EXISTS).
     * Wird beim ersten Request und nach repairTables() ausgeführt.
     */
    public function createTables(): void
    {
        $flagFile = $this->getFlagFile();
        if (file_exists($flagFile)) {
            return;
        }

        $p = $this->prefix;
        $c = $this->charset;

        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS {$p}users (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // User meta table
            "CREATE TABLE IF NOT EXISTS {$p}user_meta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                INDEX idx_user_id (user_id),
                INDEX idx_meta_key (meta_key),
                FOREIGN KEY (user_id) REFERENCES {$p}users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Roles table
            "CREATE TABLE IF NOT EXISTS {$p}roles (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT,
                capabilities TEXT COMMENT 'JSON-Array mit Berechtigungen',
                member_dashboard_access TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Zugriff auf Member-Dashboard',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_name (name)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Settings table
            "CREATE TABLE IF NOT EXISTS {$p}settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                option_name VARCHAR(255) NOT NULL UNIQUE,
                option_value LONGTEXT,
                autoload TINYINT(1) DEFAULT 1,
                INDEX idx_key (option_name)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Sessions table
            "CREATE TABLE IF NOT EXISTS {$p}sessions (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Pages table
            "CREATE TABLE IF NOT EXISTS {$p}pages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(200) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT,
                excerpt TEXT,
                status VARCHAR(20) DEFAULT 'draft',
                hide_title TINYINT(1) NOT NULL DEFAULT 0,
                featured_image VARCHAR(500) DEFAULT NULL,
                meta_title VARCHAR(255) DEFAULT NULL,
                meta_description TEXT DEFAULT NULL,
                author_id INT UNSIGNED,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at TIMESTAMP NULL,
                INDEX idx_slug (slug),
                INDEX idx_status (status),
                INDEX idx_author (author_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Page revisions table
            "CREATE TABLE IF NOT EXISTS {$p}page_revisions (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Landing sections table
            "CREATE TABLE IF NOT EXISTS {$p}landing_sections (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                data TEXT,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_type (type),
                INDEX idx_order (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Activity log table
            "CREATE TABLE IF NOT EXISTS {$p}activity_log (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Cache table
            "CREATE TABLE IF NOT EXISTS {$p}cache (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                cache_key VARCHAR(191) NOT NULL UNIQUE,
                cache_value LONGTEXT,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_key (cache_key),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Login attempts (security)
            // H-05: action-Spalte für DB-basiertes Rate-Limiting per Action + IP
            "CREATE TABLE IF NOT EXISTS {$p}login_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(60),
                ip_address VARCHAR(45),
                action VARCHAR(30) NOT NULL DEFAULT 'login',
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_ip (ip_address),
                INDEX idx_action (action),
                INDEX idx_ip_action (ip_address, action),
                INDEX idx_time (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Plugins table
            "CREATE TABLE IF NOT EXISTS {$p}plugins (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Plugin meta table
            "CREATE TABLE IF NOT EXISTS {$p}plugin_meta (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plugin_id INT UNSIGNED NOT NULL,
                meta_key VARCHAR(255) NOT NULL,
                meta_value LONGTEXT,
                INDEX idx_plugin_id (plugin_id),
                INDEX idx_meta_key (meta_key),
                FOREIGN KEY (plugin_id) REFERENCES {$p}plugins(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Theme customizations table
            "CREATE TABLE IF NOT EXISTS {$p}theme_customizations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                theme_slug VARCHAR(100) NOT NULL,
                setting_category VARCHAR(100) NOT NULL COMMENT 'Kategorie aus theme.json',
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Subscription plans table
            "CREATE TABLE IF NOT EXISTS {$p}subscription_plans (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // User subscriptions table
            "CREATE TABLE IF NOT EXISTS {$p}user_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                plan_id INT NOT NULL,
                status ENUM('active','cancelled','expired','trial','suspended') DEFAULT 'active',
                billing_cycle ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // User groups table
            "CREATE TABLE IF NOT EXISTS {$p}user_groups (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                role_id INT UNSIGNED NULL COMMENT 'Verknüpfte RBAC-Rolle',
                plan_id INT,
                is_active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // User group members table
            "CREATE TABLE IF NOT EXISTS {$p}user_group_members (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                group_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_group (user_id, group_id),
                INDEX idx_user_id (user_id),
                INDEX idx_group_id (group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Subscription usage table
            "CREATE TABLE IF NOT EXISTS {$p}subscription_usage (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                resource_type VARCHAR(50) NOT NULL,
                current_count INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_resource (user_id, resource_type),
                INDEX idx_user_id (user_id),
                INDEX idx_resource (resource_type)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Blog post categories table
            "CREATE TABLE IF NOT EXISTS {$p}post_categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT,
                parent_id INT UNSIGNED DEFAULT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_slug (slug),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Blog posts table
            "CREATE TABLE IF NOT EXISTS {$p}posts (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Orders table
            "CREATE TABLE IF NOT EXISTS {$p}orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(64) NOT NULL UNIQUE,
                user_id INT UNSIGNED NULL,
                plan_id INT NOT NULL,
                status ENUM('pending','confirmed','cancelled','refunded') DEFAULT 'pending',
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                currency VARCHAR(3) DEFAULT 'EUR',
                payment_method VARCHAR(50) DEFAULT NULL,
                billing_cycle ENUM('monthly','yearly','lifetime') DEFAULT 'monthly',
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Blocked IPs table
            "CREATE TABLE IF NOT EXISTS {$p}blocked_ips (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL UNIQUE,
                reason VARCHAR(255),
                expires_at DATETIME,
                permanent TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Failed Logins table
            "CREATE TABLE IF NOT EXISTS {$p}failed_logins (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(60),
                ip_address VARCHAR(45),
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                user_agent VARCHAR(255),
                INDEX idx_username (username),
                INDEX idx_ip (ip_address),
                INDEX idx_time (attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Media table
            "CREATE TABLE IF NOT EXISTS {$p}media (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Page Views table (Analytics)
            "CREATE TABLE IF NOT EXISTS {$p}page_views (
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
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Kommentare (wird von antispam.php und ggf. Plugins verwendet)
            "CREATE TABLE IF NOT EXISTS {$p}comments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
                user_id INT UNSIGNED NULL,
                author VARCHAR(100) NOT NULL DEFAULT '',
                author_email VARCHAR(150) NOT NULL DEFAULT '',
                author_ip VARCHAR(45) DEFAULT '',
                content TEXT NOT NULL,
                status ENUM('pending','approved','spam','trash') NOT NULL DEFAULT 'pending',
                post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_post_id (post_id),
                INDEX idx_status (status),
                INDEX idx_post_date (post_date),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c} COMMENT='Kommentare'",

            // Messages table (Member-Dashboard Nachrichten)
            "CREATE TABLE IF NOT EXISTS {$p}messages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_id INT UNSIGNED NOT NULL,
                recipient_id INT UNSIGNED NOT NULL,
                subject VARCHAR(255) NOT NULL DEFAULT '',
                body TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                read_at TIMESTAMP NULL,
                parent_id BIGINT UNSIGNED NULL COMMENT 'Antwort auf andere Nachricht (Thread)',
                deleted_by_sender TINYINT(1) NOT NULL DEFAULT 0,
                deleted_by_recipient TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sender (sender_id),
                INDEX idx_recipient (recipient_id),
                INDEX idx_parent (parent_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (sender_id) REFERENCES {$p}users(id) ON DELETE CASCADE,
                FOREIGN KEY (recipient_id) REFERENCES {$p}users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET={$c} COMMENT='Benutzer-Nachrichten (Member-Dashboard)'",

            // H-01: Sicherheits-Audit-Log (AuditLogger-Klasse)
            "CREATE TABLE IF NOT EXISTS {$p}audit_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                category VARCHAR(50) NOT NULL COMMENT 'auth|theme|plugin|user|setting|media|system|security',
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(100),
                entity_id BIGINT UNSIGNED NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(500),
                metadata LONGTEXT COMMENT 'JSON-Daten',
                severity ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_category (category),
                INDEX idx_action (action),
                INDEX idx_severity (severity),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Mail-Protokoll
            "CREATE TABLE IF NOT EXISTS {$p}mail_log (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
                transport VARCHAR(50) NOT NULL DEFAULT 'smtp',
                provider VARCHAR(50) NOT NULL DEFAULT 'default',
                message_id VARCHAR(255) DEFAULT NULL,
                error_message TEXT DEFAULT NULL,
                meta LONGTEXT COMMENT 'JSON-Daten',
                source VARCHAR(100) NOT NULL DEFAULT 'system',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_recipient (recipient),
                INDEX idx_created_at (created_at),
                INDEX idx_source (source)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            // Mail-Queue (Vorbereitung für asynchronen Versand)
            "CREATE TABLE IF NOT EXISTS {$p}mail_queue (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                recipient VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                body LONGTEXT,
                headers LONGTEXT COMMENT 'JSON-Daten',
                status ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                available_at DATETIME DEFAULT NULL,
                sent_at DATETIME DEFAULT NULL,
                last_error TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_available_at (available_at),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",

            "CREATE TABLE IF NOT EXISTS {$p}custom_fonts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                format VARCHAR(20) NOT NULL DEFAULT 'woff2',
                file_path VARCHAR(500) NOT NULL,
                css_path VARCHAR(500) DEFAULT NULL,
                source VARCHAR(50) NOT NULL DEFAULT 'upload' COMMENT 'upload|google-fonts-local',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY idx_slug (slug),
                INDEX idx_source (source)
            ) ENGINE=InnoDB DEFAULT CHARSET={$c}",
        ];

        $pdo = $this->db->getPdo();
        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
            } catch (PDOException $e) {
                error_log('SchemaManager::createTables() failed: ' . $e->getMessage());
            }
        }

        // Migrations ausführen (fehlende Spalten ergänzen)
        (new MigrationManager($this->db))->run();
        $this->ensureContentColumns();

        // Standard-Admin anlegen falls noch kein Admin existiert
        $this->createDefaultAdmin();

        // Flag-Datei schreiben
        $cacheDir = ABSPATH . 'cache/';
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                error_log('SchemaManager: Cache-Verzeichnis konnte nicht erstellt werden: ' . $cacheDir);
            }
        }
        if (file_put_contents($flagFile, date('Y-m-d H:i:s')) === false) {
            error_log('SchemaManager: Flag-Datei konnte nicht geschrieben werden: ' . $flagFile);
        }
    }

    /**
     * Legt den Standard-Admin-Account an, wenn kein Admin-User vorhanden ist.
     */
    private function createDefaultAdmin(): void
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) AS cnt FROM {$this->prefix}users WHERE role = 'admin'"
            );
            if (!$stmt) {
                return;
            }
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && (int) $result->cnt === 0) {
                // Generate a cryptographically secure random password
                $randomPassword = bin2hex(random_bytes(16)); // 32 chars hex

                $this->db->insert('users', [
                    'username'     => 'admin',
                    'email'        => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@localhost',
                    'password'     => password_hash($randomPassword, PASSWORD_BCRYPT),
                    'display_name' => 'Administrator',
                    'role'         => 'admin',
                    'status'       => 'active',
                ]);

                // Log the generated password so the admin can retrieve it
                error_log('[365CMS] Default admin created. Temp password: ' . $randomPassword);
                // Write to a one-time file for first login
                $credFile = dirname(__DIR__) . '/logs/admin-credentials.txt';
                @file_put_contents($credFile, "Default Admin Password: {$randomPassword}\nGenerated: " . date('Y-m-d H:i:s') . "\nDELETE THIS FILE AFTER FIRST LOGIN!\n");
                @chmod($credFile, 0600);
            }
        } catch (\Exception $e) {
            error_log('SchemaManager::createDefaultAdmin() Error: ' . $e->getMessage());
        }
    }

    /**
     * Ergänzt fehlende Content-/SEO-Spalten in Altinstallationen.
     */
    private function ensureContentColumns(): void
    {
        $this->ensureColumnExists(
            $this->prefix . 'pages',
            'featured_image',
            "ALTER TABLE {$this->prefix}pages ADD COLUMN featured_image VARCHAR(500) DEFAULT NULL AFTER hide_title"
        );
        $this->ensureColumnExists(
            $this->prefix . 'pages',
            'meta_title',
            "ALTER TABLE {$this->prefix}pages ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER featured_image"
        );
        $this->ensureColumnExists(
            $this->prefix . 'pages',
            'meta_description',
            "ALTER TABLE {$this->prefix}pages ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title"
        );
        $this->ensureColumnExists(
            $this->prefix . 'posts',
            'featured_image',
            "ALTER TABLE {$this->prefix}posts ADD COLUMN featured_image VARCHAR(500) DEFAULT NULL AFTER excerpt"
        );
        $this->ensureColumnExists(
            $this->prefix . 'posts',
            'meta_title',
            "ALTER TABLE {$this->prefix}posts ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER allow_comments"
        );
        $this->ensureColumnExists(
            $this->prefix . 'posts',
            'meta_description',
            "ALTER TABLE {$this->prefix}posts ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title"
        );
    }

    /**
     * Führt ein ALTER TABLE nur aus, wenn die Spalte fehlt.
     */
    private function ensureColumnExists(string $table, string $column, string $alterSql): void
    {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            if ($stmt instanceof \PDOStatement && !$stmt->fetch()) {
                $this->db->query($alterSql);
            }
        } catch (\Throwable $e) {
            error_log(sprintf('SchemaManager::ensureColumnExists(%s.%s) failed: %s', $table, $column, $e->getMessage()));
        }
    }
}
