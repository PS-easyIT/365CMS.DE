<?php
/**
 * CMS Installation Script
 * 
 * Intelligenter Installer der:
 * - Domain automatisch erkennt
 * - Alle Konfigurationswerte abfragt
 * - config.php automatisch erstellt
 * - Datenbank-Tabellen erstellt
 * - Admin-User anlegt
 * 
 * WICHTIG: Nach erfolgreicher Installation LÖSCHEN!
 * 
 * @package 365CMS
 */

declare(strict_types=1);

// Session für mehrstufiges Formular
session_start();

// Helper-Funktionen
function autoDetectUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($script);
    
    // Entferne /install.php und bereinige Pfad
    $basePath = ($dir === '/' || $dir === '\\') ? '' : $dir;
    
    return rtrim($protocol . $host . $basePath, '/');
}

function generateSecurityKey(int $length = 64): string {
    return bin2hex(random_bytes($length / 2));
}

function parseExistingConfig(): array|bool {
    $configPath = __DIR__ . '/config.php';
    
    if (!file_exists($configPath)) {
        return false;
    }
    
    $content = file_get_contents($configPath);
    $config = [];
    
    // Parse DB-Credentials
    if (preg_match("/define\('DB_HOST',\s*'([^']+)'\);/", $content, $matches)) {
        $config['db_host'] = $matches[1];
    }
    if (preg_match("/define\('DB_NAME',\s*'([^']+)'\);/", $content, $matches)) {
        $config['db_name'] = $matches[1];
    }
    if (preg_match("/define\('DB_USER',\s*'([^']+)'\);/", $content, $matches)) {
        $config['db_user'] = $matches[1];
    }
    if (preg_match("/define\('DB_PASS',\s*'([^']+)'\);/", $content, $matches)) {
        $config['db_pass'] = $matches[1];
    }
    
    // Parse Site-Info
    if (preg_match("/define\('SITE_NAME',\s*'([^']+)'\);/", $content, $matches)) {
        $config['site_name'] = $matches[1];
    }
    if (preg_match("/define\('ADMIN_EMAIL',\s*'([^']+)'\);/", $content, $matches)) {
        $config['admin_email'] = $matches[1];
    }
    
    return !empty($config['db_host']) ? $config : false;
}

function cleanDatabase(PDO $pdo, string $prefix = 'cms_'): array {
    $results = [
        'dropped' => [],
        'errors' => []
    ];
    
    try {
        // Alle Tabellen mit Präfix finden
        $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            return $results;
        }
        
        // Foreign Key Checks ausschalten
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Alle Tabellen löschen
        foreach ($tables as $table) {
            try {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                $results['dropped'][] = $table;
            } catch (PDOException $e) {
                $results['errors'][$table] = $e->getMessage();
            }
        }
        
        // Foreign Key Checks wieder einschalten
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
    } catch (PDOException $e) {
        $results['errors']['general'] = $e->getMessage();
    }
    
    return $results;
}

function testDatabaseConnection(string $host, string $name, string $user, string $pass): bool|string {
    try {
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
        new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return true;
    } catch (PDOException $e) {
        return 'Fehler: ' . $e->getMessage();
    }
}

function createConfigFile(array $data): bool|string {
    $configPath = __DIR__ . '/config.php';
    
    // Backup existierende config
    if (file_exists($configPath)) {
        $backupPath = __DIR__ . '/config.php.backup.' . date('Y-m-d_H-i-s');
        copy($configPath, $backupPath);
    }
    
    $content = <<<PHP
<?php
/**
 * CMS Configuration File
 * 
 * Automatisch erstellt am {$data['created_at']}
 * 
 * @package CMSv2
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Debug Mode (DISABLE in production!)
define('CMS_DEBUG', {$data['debug_mode']});

// Database Configuration
define('DB_HOST', '{$data['db_host']}');
define('DB_NAME', '{$data['db_name']}');
define('DB_USER', '{$data['db_user']}');
define('DB_PASS', '{$data['db_pass']}');
define('DB_CHARSET', 'utf8mb4');

// Security Keys
define('AUTH_KEY', '{$data['auth_key']}');
define('SECURE_AUTH_KEY', '{$data['secure_auth_key']}');
define('NONCE_KEY', '{$data['nonce_key']}');

// Site Configuration
define('SITE_NAME', '{$data['site_name']}');
define('SITE_URL', '{$data['site_url']}');
define('ADMIN_EMAIL', '{$data['admin_email']}');

// Paths
define('CORE_PATH', ABSPATH . 'core/');
define('THEME_PATH', ABSPATH . 'themes/');
define('PLUGIN_PATH', ABSPATH . 'plugins/');
define('UPLOAD_PATH', ABSPATH . 'uploads/');
define('ASSETS_PATH', ABSPATH . 'assets/');

// URLs
define('SITE_URL_PATH', '/');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOAD_URL', SITE_URL . '/uploads');

// System Settings
define('DEFAULT_THEME', 'default');
define('SESSIONS_LIFETIME', 3600 * 2); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 300); // 5 minutes

// Error Reporting
if (CMS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ABSPATH . 'logs/error.log');
}

// Timezone
date_default_timezone_set('Europe/Berlin');

PHP;
    
    $written = file_put_contents($configPath, $content);
    
    if ($written === false) {
        return 'Fehler beim Schreiben der config.php';
    }
    
    return true;
}

function createDatabaseTables(PDO $pdo, string $prefix = 'cms_'): array {
    $results = [];
    
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS {$prefix}users (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'user_meta' => "CREATE TABLE IF NOT EXISTS {$prefix}user_meta (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            INDEX idx_user_id (user_id),
            INDEX idx_meta_key (meta_key),
            FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'roles' => "CREATE TABLE IF NOT EXISTS {$prefix}roles (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'settings' => "CREATE TABLE IF NOT EXISTS {$prefix}settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            option_name VARCHAR(191) NOT NULL UNIQUE,
            option_value LONGTEXT,
            autoload TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_option_name (option_name),
            INDEX idx_autoload (autoload)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'sessions' => "CREATE TABLE IF NOT EXISTS {$prefix}sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT UNSIGNED,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            payload TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_last_activity (last_activity),
            INDEX idx_created_at (created_at),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'login_attempts' => "CREATE TABLE IF NOT EXISTS {$prefix}login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60),
            ip_address VARCHAR(45),
            success TINYINT(1) DEFAULT 0,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_ip (ip_address),
            INDEX idx_time (attempted_at),
            INDEX idx_success (success)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'blocked_ips' => "CREATE TABLE IF NOT EXISTS {$prefix}blocked_ips (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            reason VARCHAR(255),
            expires_at DATETIME,
            permanent TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'activity_log' => "CREATE TABLE IF NOT EXISTS {$prefix}activity_log (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'pages' => "CREATE TABLE IF NOT EXISTS {$prefix}pages (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'page_revisions' => "CREATE TABLE IF NOT EXISTS {$prefix}page_revisions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            author_id INT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page_id (page_id),
            INDEX idx_author (author_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (page_id) REFERENCES {$prefix}pages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'cache' => "CREATE TABLE IF NOT EXISTS {$prefix}cache (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cache_key VARCHAR(191) NOT NULL UNIQUE,
            cache_value LONGTEXT,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_key (cache_key),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'failed_logins' => "CREATE TABLE IF NOT EXISTS {$prefix}failed_logins (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60),
            ip_address VARCHAR(45),
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            user_agent VARCHAR(255),
            INDEX idx_username (username),
            INDEX idx_ip (ip_address),
            INDEX idx_time (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'landing_sections' => "CREATE TABLE IF NOT EXISTS {$prefix}landing_sections (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            data TEXT,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (type),
            INDEX idx_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        'media' => "CREATE TABLE IF NOT EXISTS {$prefix}media (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'plugins' => "CREATE TABLE IF NOT EXISTS {$prefix}plugins (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'plugin_meta' => "CREATE TABLE IF NOT EXISTS {$prefix}plugin_meta (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plugin_id INT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT,
            INDEX idx_plugin_id (plugin_id),
            INDEX idx_meta_key (meta_key),
            FOREIGN KEY (plugin_id) REFERENCES {$prefix}plugins(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'theme_customizations' => "CREATE TABLE IF NOT EXISTS {$prefix}theme_customizations (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'page_views' => "CREATE TABLE IF NOT EXISTS {$prefix}page_views (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        // ==================== SUBSCRIPTION SYSTEM ====================
        
        'subscription_plans' => "CREATE TABLE IF NOT EXISTS {$prefix}subscription_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            price_monthly DECIMAL(10,2) DEFAULT 0.00,
            price_yearly DECIMAL(10,2) DEFAULT 0.00,
            
            -- Feature Limits (-1 = unlimited, 0 = disabled)
            limit_experts INT DEFAULT -1,
            limit_companies INT DEFAULT -1,
            limit_events INT DEFAULT -1,
            limit_speakers INT DEFAULT -1,
            limit_storage_mb INT DEFAULT 1000,
            
            -- Plugin Access Control
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
            INDEX idx_active (is_active),
            INDEX idx_sort (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Abo-Pakete mit Limits und Features'",
        
        'user_subscriptions' => "CREATE TABLE IF NOT EXISTS {$prefix}user_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            plan_id INT NOT NULL,
            
            -- Status & Billing
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
            
            FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES {$prefix}subscription_plans(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_plan_id (plan_id),
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Benutzer-Abo-Zuweisungen'",
        
        'user_groups' => "CREATE TABLE IF NOT EXISTS {$prefix}user_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            role_id INT UNSIGNED NULL COMMENT 'Verknüpfte RBAC-Rolle',
            plan_id INT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (plan_id) REFERENCES {$prefix}subscription_plans(id) ON DELETE SET NULL,
            INDEX idx_slug (slug),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Gruppen für Abo-Verwaltung'",
        
        'user_group_members' => "CREATE TABLE IF NOT EXISTS {$prefix}user_group_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            group_id INT NOT NULL,
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES {$prefix}user_groups(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_group (user_id, group_id),
            INDEX idx_user_id (user_id),
            INDEX idx_group_id (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Gruppen-Mitgliedschaften'",
        
        'subscription_usage' => "CREATE TABLE IF NOT EXISTS {$prefix}subscription_usage (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            resource_type VARCHAR(50) NOT NULL COMMENT 'experts, companies, events, speakers, storage',
            current_count INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            FOREIGN KEY (user_id) REFERENCES {$prefix}users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_resource (user_id, resource_type),
            INDEX idx_user_id (user_id),
            INDEX idx_resource (resource_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Ressourcen-Nutzungszähler für Limits'",

        'orders' => "CREATE TABLE IF NOT EXISTS {$prefix}orders (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Bestellungen für Abos'",

        'post_categories' => "CREATE TABLE IF NOT EXISTS {$prefix}post_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT UNSIGNED DEFAULT NULL,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug),
            INDEX idx_parent (parent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Blog-Kategorien'",

        'posts' => "CREATE TABLE IF NOT EXISTS {$prefix}posts (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Blog-Beiträge'"
    ];
    
    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            $results[$name] = true;
        } catch (PDOException $e) {
            $results[$name] = 'Fehler: ' . $e->getMessage();
        }
    }
    
    return $results;
}

function createAdminUser(PDO $pdo, string $username, string $email, string $password, string $prefix = 'cms_'): bool|string {
    try {
        // Check if admin already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetchColumn() > 0) {
            return 'Admin-User existiert bereits';
        }
        
        // Create admin
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("INSERT INTO {$prefix}users (username, email, password, display_name, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
        $stmt->execute([$username, $email, $hash, 'Administrator']);
        
        return true;
    } catch (PDOException $e) {
        return 'Fehler: ' . $e->getMessage();
    }
}

function createDefaultSettings(PDO $pdo, string $siteName, string $adminEmail, string $prefix = 'cms_'): bool {
    try {
        $settings = [
            ['site_title', '365CMS'],
            ['site_tagline', 'Content Management System'],
            ['site_name', $siteName],
            ['admin_email', $adminEmail],
            ['timezone', 'Europe/Berlin'],
            ['date_format', 'd.m.Y'],
            ['time_format', 'H:i'],
            ['active_theme', 'default'],
            ['active_plugins', '[]'],
            ['landing_page_content', '']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO {$prefix}settings (option_name, option_value, autoload) VALUES (?, ?, 1)");
        
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function initializeLandingPageData(PDO $pdo, string $prefix = 'cms_'): bool {
    try {
        // Header section
        $headerData = json_encode([
            'title' => 'IT Expert Network CMS',
            'subtitle' => 'Modernes Content Management System',
            'description' => 'Ein leistungsstarkes, sicheres und erweiterbares CMS für professionelle Websites.',
            'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
            'gitlab_url' => '',
            'version' => '2.0.0',
            'logo' => '',
            'colors' => [
                'hero_gradient_start' => '#1e293b',
                'hero_gradient_end' => '#0f172a',
                'hero_border' => '#3b82f6',
                'hero_text' => '#ffffff',
                'features_bg' => '#f8fafc',
                'feature_card_bg' => '#ffffff',
                'feature_card_hover' => '#3b82f6',
                'primary_button' => '#3b82f6'
            ]
        ]);
        
        $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('header', ?, 0, NOW(), NOW())");
        $stmt->execute([$headerData]);
        
        // Feature sections (12 features for 4x3 grid)
        $features = [
            ['icon' => '🚀', 'title' => 'Blitzschnell', 'description' => 'Optimierte Performance für schnelle Ladezeiten'],
            ['icon' => '🔒', 'title' => 'Sicher', 'description' => 'Moderne Sicherheitsstandards und Verschlüsselung'],
            ['icon' => '📱', 'title' => 'Responsive', 'description' => 'Perfekte Darstellung auf allen Geräten'],
            ['icon' => '🎨', 'title' => 'Anpassbar', 'description' => 'Flexibles Theme-System für individuelle Designs'],
            ['icon' => '🔌', 'title' => 'Erweiterbar', 'description' => 'Plugin-System für unbegrenzte Möglichkeiten'],
            ['icon' => '📊', 'title' => 'Analytics', 'description' => 'Integrierte Statistiken und Monitoring'],
            ['icon' => '👥', 'title' => 'Multi-User', 'description' => 'Rollen-basierte Benutzerverwaltung'],
            ['icon' => '🌐', 'title' => 'SEO-Ready', 'description' => 'Suchmaschinenoptimiertes Framework'],
            ['icon' => '⚡', 'title' => 'REST API', 'description' => 'Moderne API für Integrationen'],
            ['icon' => '💾', 'title' => 'Backups', 'description' => 'Automatische Datensicherung'],
            ['icon' => '🔄', 'title' => 'Updates', 'description' => 'Einfache Update-Verwaltung'],
            ['icon' => '📝', 'title' => 'Editor', 'description' => 'Intuitiver Content-Editor']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())");
        
        foreach ($features as $index => $feature) {
            $featureData = json_encode($feature);
            $stmt->execute([$featureData, $index + 1]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Landing Page Initialization Error: ' . $e->getMessage());
        return false;
    }
}

// Installation Step Handler
$step = $_POST['step'] ?? $_GET['step'] ?? 1;
$errors = [];

// Prüfe auf bestehende Installation
$existingConfig = parseExistingConfig();
$isReinstall = $existingConfig !== false;

// Step 1: Willkommen & System-Check
if ($step == 1) {
    $autoUrl = autoDetectUrl();
    $phpVersion = PHP_VERSION;
    $mysqlAvailable = extension_loaded('pdo_mysql');
    $writePermission = is_writable(__DIR__);
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Installation - Schritt 1</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 2rem;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
            }
            .card {
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #1e293b;
                font-size: 2.5rem;
                margin-bottom: 0.5rem;
            }
            .subtitle {
                color: #64748b;
                margin-bottom: 2rem;
            }
            .check-item {
                display: flex;
                align-items: center;
                padding: 1rem;
                margin: 0.5rem 0;
                background: #f8fafc;
                border-radius: 8px;
            }
            .check-icon {
                font-size: 1.5rem;
                margin-right: 1rem;
            }
            .success { color: #10b981; }
            .error { color: #ef4444; }
            .info-box {
                background: #eff6ff;
                border-left: 4px solid #3b82f6;
                padding: 1rem;
                margin: 2rem 0;
                border-radius: 4px;
            }
            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                margin-top: 2rem;
                width: 100%;
            }
            .btn:hover {
                opacity: 0.9;
            }
            .btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>🚀 CMS Installation</h1>
                <p class="subtitle">Willkommen! Wir richten Ihr CMS in wenigen Schritten ein.</p>
                
                <h2 style="margin: 2rem 0 1rem; color: #475569;">System-Überprüfung</h2>
                
                <div class="check-item">
                    <span class="check-icon success">✓</span>
                    <div>
                        <strong>PHP Version</strong><br>
                        <small>Version <?php echo $phpVersion; ?> (erforderlich: 8.0+)</small>
                    </div>
                </div>
                
                <div class="check-item">
                    <span class="check-icon <?php echo $mysqlAvailable ? 'success' : 'error'; ?>">
                        <?php echo $mysqlAvailable ? '✓' : '✗'; ?>
                    </span>
                    <div>
                        <strong>MySQL/PDO Extension</strong><br>
                        <small><?php echo $mysqlAvailable ? 'Verfügbar' : 'NICHT verfügbar - Installation nicht möglich!'; ?></small>
                    </div>
                </div>
                
                <div class="check-item">
                    <span class="check-icon <?php echo $writePermission ? 'success' : 'error'; ?>">
                        <?php echo $writePermission ? '✓' : '✗'; ?>
                    </span>
                    <div>
                        <strong>Schreibrechte</strong><br>
                        <small><?php echo $writePermission ? 'Verzeichnis ist beschreibbar' : 'KEINE Schreibrechte - config.php kann nicht erstellt werden!'; ?></small>
                    </div>
                </div>
                
                <?php if ($isReinstall): ?>
                <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                    <strong>⚠️ Bestehende Installation gefunden!</strong><br>
                    Es wurde eine config.php mit folgenden Daten gefunden:<br>
                    <ul style="margin: 0.5rem 0 0.5rem 1.5rem;">
                        <li><strong>Datenbank:</strong> <?php echo htmlspecialchars($existingConfig['db_name'] ?? 'N/A'); ?> @ <?php echo htmlspecialchars($existingConfig['db_host'] ?? 'N/A'); ?></li>
                        <li><strong>Site:</strong> <?php echo htmlspecialchars($existingConfig['site_name'] ?? 'N/A'); ?></li>
                    </ul>
                    <br>
                    <strong>ACHTUNG:</strong> Die Neuinstallation wird <strong style="color: #dc2626;">ALLE TABELLEN LÖSCHEN</strong> und das CMS komplett neu aufsetzen!<br>
                    <small style="color: #92400e;">Dies kann nicht rückgängig gemacht werden. Erstellen Sie vorher ein Backup!</small>
                </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <strong>🌐 Automatisch erkannte Domain:</strong><br>
                    <code style="font-size: 1.1rem; color: #3b82f6;"><?php echo htmlspecialchars($autoUrl); ?></code><br>
                    <small style="color: #64748b;">Diese wird im nächsten Schritt verwendet.</small>
                </div>
                
                <form method="post" action="?step=2">
                    <?php if ($isReinstall): ?>
                    <input type="hidden" name="reinstall" value="1">
                    <?php endif; ?>
                    <button type="submit" class="btn" <?php echo (!$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                        <?php echo $isReinstall ? '🔄 Neuinstallation starten →' : 'Weiter zu Schritt 2 →'; ?>
                    </button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Step 2: Datenbank-Konfiguration
if ($step == 2) {
    $isReinstall = isset($_POST['reinstall']) && $_POST['reinstall'] == '1';
    
    // Bei Neuinstallation: DB-Daten aus config.php laden und direkt bereinigen
    if ($isReinstall && $existingConfig) {
        try {
            $dsn = "mysql:host={$existingConfig['db_host']};dbname={$existingConfig['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $existingConfig['db_user'], $existingConfig['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $cleanResult = cleanDatabase($pdo, 'cms_');
            
            $_SESSION['db_config'] = [
                'db_host' => $existingConfig['db_host'],
                'db_name' => $existingConfig['db_name'],
                'db_user' => $existingConfig['db_user'],
                'db_pass' => $existingConfig['db_pass']
            ];
            $_SESSION['db_cleaned'] = $cleanResult;
            $_SESSION['is_reinstall'] = true;
            
            header('Location: ?step=3');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage();
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_db'])) {
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        
        $testResult = testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);
        
        if ($testResult === true) {
            $_SESSION['db_config'] = [
                'db_host' => $dbHost,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass
            ];
            header('Location: ?step=3');
            exit;
        } else {
            $errors[] = $testResult;
        }
    }
    
    $defaultValues = $_SESSION['db_config'] ?? [
        'db_host' => 'localhost',
        'db_name' => '',
        'db_user' => '',
        'db_pass' => ''
    ];
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Installation - Schritt 2</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 2rem;
            }
            .container { max-width: 800px; margin: 0 auto; }
            .card {
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; }
            .progress {
                display: flex;
                gap: 0.5rem;
                margin: 2rem 0;
            }
            .progress-step {
                flex: 1;
                height: 4px;
                background: #e2e8f0;
                border-radius: 2px;
            }
            .progress-step.active { background: #667eea; }
            .form-group {
                margin: 1.5rem 0;
            }
            label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: #334155;
            }
            input {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 1rem;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
            }
            .error-box {
                background: #fef2f2;
                border-left: 4px solid #ef4444;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
                color: #991b1b;
            }
            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
            }
            .btn:hover { opacity: 0.9; }
            .help-text {
                font-size: 0.875rem;
                color: #64748b;
                margin-top: 0.25rem;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>📊 Datenbank-Konfiguration</h1>
                <p style="color: #64748b; margin-bottom: 1rem;">Schritt 2 von 4</p>
                
                <div class="progress">
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                    <div class="progress-step"></div>
                    <div class="progress-step"></div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <strong>⚠️ Verbindungsfehler:</strong><br>
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="test_db" value="1">
                    
                    <div class="form-group">
                        <label>Datenbank-Host</label>
                        <input type="text" name="db_host" value="<?php echo htmlspecialchars($defaultValues['db_host']); ?>" required>
                        <p class="help-text">Meist "localhost" oder eine IP-Adresse</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Name</label>
                        <input type="text" name="db_name" value="<?php echo htmlspecialchars($defaultValues['db_name']); ?>" required>
                        <p class="help-text">Die Datenbank muss bereits existieren</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Benutzer</label>
                        <input type="text" name="db_user" value="<?php echo htmlspecialchars($defaultValues['db_user']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Passwort</label>
                        <input type="password" name="db_pass" value="<?php echo htmlspecialchars($defaultValues['db_pass']); ?>">
                    </div>
                    
                    <button type="submit" class="btn">Verbindung testen & weiter →</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Step 3: Site-Konfiguration
if ($step == 3) {
    if (!isset($_SESSION['db_config'])) {
        header('Location: ?step=2');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_config'])) {
        $_SESSION['site_config'] = [
            'site_name' => trim($_POST['site_name']),
            'site_url' => rtrim(trim($_POST['site_url']), '/'),
            'admin_email' => trim($_POST['admin_email']),
            'debug_mode' => isset($_POST['debug_mode']) ? 'true' : 'false'
        ];
        header('Location: ?step=4');
        exit;
    }
    
    $autoUrl = autoDetectUrl();
    $isReinstall = isset($_SESSION['is_reinstall']) && $_SESSION['is_reinstall'];
    $defaultValues = $_SESSION['site_config'] ?? [
        'site_name' => $isReinstall && $existingConfig ? ($existingConfig['site_name'] ?? 'IT Expert Network') : 'IT Expert Network',
        'site_url' => $autoUrl,
        'admin_email' => $isReinstall && $existingConfig ? ($existingConfig['admin_email'] ?? '') : '',
        'debug_mode' => true
    ];
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Installation - Schritt 3</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 2rem;
            }
            .container { max-width: 800px; margin: 0 auto; }
            .card {
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; }
            .progress {
                display: flex;
                gap: 0.5rem;
                margin: 2rem 0;
            }
            .progress-step {
                flex: 1;
                height: 4px;
                background: #e2e8f0;
                border-radius: 2px;
            }
            .progress-step.active { background: #667eea; }
            .form-group {
                margin: 1.5rem 0;
            }
            label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: #334155;
            }
            input[type="text"], input[type="email"], input[type="url"] {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 1rem;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
            }
            .checkbox-group {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .checkbox-group input[type="checkbox"] {
                width: auto;
            }
            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
            }
            .btn:hover { opacity: 0.9; }
            .help-text {
                font-size: 0.875rem;
                color: #64748b;
                margin-top: 0.25rem;
            }
            .info-box {
                background: #eff6ff;
                border-left: 4px solid #3b82f6;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>⚙️ Site-Konfiguration</h1>
                <p style="color: #64748b; margin-bottom: 1rem;">Schritt 3 von 4</p>
                
                <div class="progress">
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                    <div class="progress-step"></div>
                </div>
                
                <?php if (isset($_SESSION['db_cleaned']) && !empty($_SESSION['db_cleaned']['dropped'])): ?>
                <div class="info-box" style="background: #d1fae5; border-left-color: #10b981;">
                    <strong>✓ Datenbank bereinigt</strong><br>
                    Folgende Tabellen wurden gelöscht:<br>
                    <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem;">
                        <?php foreach ($_SESSION['db_cleaned']['dropped'] as $table): ?>
                        <li><code><?php echo htmlspecialchars($table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <small style="color: #047857;">Das CMS wird komplett neu installiert.</small>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="site_config" value="1">
                    
                    <div class="form-group">
                        <label>Site-Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($defaultValues['site_name']); ?>" required>
                        <p class="help-text">Der Name Ihrer Website</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Site-URL</label>
                        <input type="url" name="site_url" value="<?php echo htmlspecialchars($defaultValues['site_url']); ?>" required>
                        <p class="help-text">Automatisch erkannt - NIEMALS mit Unterverzeichnis!</p>
                    </div>
                    
                    <div class="info-box">
                        <strong>💡 Hinweis:</strong> Die URL wurde automatisch erkannt. Falls sie falsch ist, korrigieren Sie sie bitte.
                    </div>
                    
                    <div class="form-group">
                        <label>Admin E-Mail</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($defaultValues['admin_email']); ?>" required>
                        <p class="help-text">Wird für System-Benachrichtigungen verwendet</p>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="debug_mode" id="debug_mode" <?php echo $defaultValues['debug_mode'] ? 'checked' : ''; ?>>
                            <label for="debug_mode" style="margin: 0;">Debug-Modus aktivieren</label>
                        </div>
                        <p class="help-text">Zeigt Fehler an (nur für Entwicklung!)</p>
                    </div>
                    
                    <button type="submit" class="btn">Weiter zu Schritt 4 →</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Step 4: Admin-User & Installation
if ($step == 4) {
    if (!isset($_SESSION['db_config']) || !isset($_SESSION['site_config'])) {
        header('Location: ?step=2');
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
        $adminUsername = trim($_POST['admin_username']);
        $adminEmail = trim($_POST['admin_email']);
        $adminPassword = $_POST['admin_password'];
        $adminPasswordConfirm = $_POST['admin_password_confirm'];
        
        // Validation
        if (empty($adminUsername) || empty($adminEmail) || empty($adminPassword)) {
            $errors[] = 'Alle Felder sind erforderlich';
        } elseif ($adminPassword !== $adminPasswordConfirm) {
            $errors[] = 'Passwörter stimmen nicht überein';
        } elseif (strlen($adminPassword) < 8) {
            $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein';
        } else {
            // Start Installation!
            $dbConfig = $_SESSION['db_config'];
            $siteConfig = $_SESSION['site_config'];
            
            // 1. Create config.php
            $configData = [
                'created_at' => date('Y-m-d H:i:s'),
                'debug_mode' => $siteConfig['debug_mode'],
                'db_host' => $dbConfig['db_host'],
                'db_name' => $dbConfig['db_name'],
                'db_user' => $dbConfig['db_user'],
                'db_pass' => $dbConfig['db_pass'],
                'auth_key' => generateSecurityKey(),
                'secure_auth_key' => generateSecurityKey(),
                'nonce_key' => generateSecurityKey(),
                'site_name' => $siteConfig['site_name'],
                'site_url' => $siteConfig['site_url'],
                'admin_email' => $siteConfig['admin_email']
            ];
            
            $configResult = createConfigFile($configData);
            
            if ($configResult !== true) {
                $errors[] = $configResult;
            } else {
                // 2. Connect to database
                try {
                    $dsn = "mysql:host={$dbConfig['db_host']};dbname={$dbConfig['db_name']};charset=utf8mb4";
                    $pdo = new PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    
                    // 3. Create tables
                    $tableResults = createDatabaseTables($pdo);
                    
                    // 4. Create admin user
                    $adminResult = createAdminUser($pdo, $adminUsername, $adminEmail, $adminPassword);
                    
                    // 5. Create default settings
                    createDefaultSettings($pdo, $siteConfig['site_name'], $siteConfig['admin_email']);
                    
                    // 6. Initialize landing page data
                    initializeLandingPageData($pdo);
                    
                    if ($adminResult === true) {
                        // SUCCESS!
                        $_SESSION['install_success'] = [
                            'username' => $adminUsername,
                            'site_url' => $siteConfig['site_url']
                        ];
                        header('Location: ?step=5');
                        exit;
                    } else {
                        $errors[] = $adminResult;
                    }
                    
                } catch (PDOException $e) {
                    $errors[] = 'Datenbankfehler: ' . $e->getMessage();
                }
            }
        }
    }
    
    $defaultEmail = $_SESSION['site_config']['admin_email'] ?? '';
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Installation - Schritt 4</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #669bea 0%, #4b67a2 100%);
                min-height: 100vh;
                padding: 2rem;
            }
            .container { max-width: 800px; margin: 0 auto; }
            .card {
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { color: #1e293b; font-size: 2rem; margin-bottom: 0.5rem; }
            .progress {
                display: flex;
                gap: 0.5rem;
                margin: 2rem 0;
            }
            .progress-step {
                flex: 1;
                height: 4px;
                background: #e2e8f0;
                border-radius: 2px;
            }
            .progress-step.active { background: #667eea; }
            .form-group {
                margin: 1.5rem 0;
            }
            label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.5rem;
                color: #334155;
            }
            input {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 1rem;
            }
            input:focus {
                outline: none;
                border-color: #667eea;
            }
            .error-box {
                background: #fef2f2;
                border-left: 4px solid #ef4444;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
                color: #991b1b;
            }
            .btn {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
            }
            .btn:hover { opacity: 0.9; }
            .help-text {
                font-size: 0.875rem;
                color: #64748b;
                margin-top: 0.25rem;
            }
            .warning-box {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>👤 Administrator-Account</h1>
                <p style="color: #64748b; margin-bottom: 1rem;">Schritt 4 von 4</p>
                
                <div class="progress">
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                    <div class="progress-step active"></div>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <strong>⚠️ Fehler:</strong><br>
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>
                
                <div class="warning-box">
                    <strong>⚠️ Wichtig:</strong> Notieren Sie sich die Login-Daten! Sie benötigen diese nach der Installation.
                </div>
                
                <form method="post">
                    <input type="hidden" name="install" value="1">
                    
                    <div class="form-group">
                        <label>Admin-Benutzername</label>
                        <input type="text" name="admin_username" value="" required autocomplete="off">
                        <p class="help-text">Min. 4 Zeichen, nur Buchstaben und Zahlen</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin E-Mail</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($defaultEmail); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin-Passwort</label>
                        <input type="password" name="admin_password" required autocomplete="new-password">
                        <p class="help-text">Mindestens 8 Zeichen</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort bestätigen</label>
                        <input type="password" name="admin_password_confirm" required autocomplete="off">
                    </div>
                    
                    <button type="submit" class="btn">🚀 Installation starten!</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Step 5: Success!
if ($step == 5) {
    if (!isset($_SESSION['install_success'])) {
        header('Location: ?step=1');
        exit;
    }
    
    $success = $_SESSION['install_success'];
    session_destroy();
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installation erfolgreich!</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                min-height: 100vh;
                padding: 2rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container { max-width: 800px; width: 100%; }
            .card {
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
            }
            .success-icon {
                font-size: 5rem;
                margin-bottom: 1rem;
                animation: bounce 0.5s ease-in-out;
            }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            h1 {
                color: #065f46;
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
            .success-message {
                color: #64748b;
                font-size: 1.1rem;
                margin-bottom: 2rem;
            }
            .info-box {
                background: #f0fdf4;
                border: 2px solid #86efac;
                padding: 1.5rem;
                border-radius: 12px;
                margin: 2rem 0;
                text-align: left;
            }
            .info-item {
                margin: 0.75rem 0;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .info-item strong {
                color: #065f46;
                min-width: 140px;
            }
            code {
                background: white;
                padding: 0.25rem 0.5rem;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                color: #059669;
            }
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 1rem 3rem;
                border: none;
                border-radius: 8px;
                font-size: 1.1rem;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                margin-top: 1rem;
            }
            .btn:hover { opacity: 0.9; }
            .warning-box {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 1rem;
                margin: 2rem 0;
                text-align: left;
                border-radius: 4px;
            }
            .checklist {
                text-align: left;
                margin: 2rem 0;
            }
            .checklist-item {
                padding: 0.5rem;
                margin: 0.25rem 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <div class="success-icon">🎉</div>
                <h1>Installation erfolgreich!</h1>
                <p class="success-message">
                    Ihr CMS wurde erfolgreich installiert und ist einsatzbereit.
                </p>
                
                <div class="info-box">
                    <div class="info-item">
                        <strong>🌐 Site-URL:</strong>
                        <code><?php echo htmlspecialchars($success['site_url']); ?></code>
                    </div>
                    <div class="info-item">
                        <strong>👤 Admin-User:</strong>
                        <code><?php echo htmlspecialchars($success['username']); ?></code>
                    </div>
                    <div class="info-item">
                        <strong>🔐 Login-URL:</strong>
                        <code><?php echo htmlspecialchars($success['site_url']); ?>/login</code>
                    </div>
                    <div class="info-item">
                        <strong>⚙️ Admin-Panel:</strong>
                        <code><?php echo htmlspecialchars($success['site_url']); ?>/admin</code>
                    </div>
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ Wichtige Sicherheitshinweise:</strong>
                    <div class="checklist">
                        <div class="checklist-item">
                            ❗ <strong>LÖSCHEN Sie install.php SOFORT!</strong><br>
                            <code style="background:#fee; color:#b91c1c;">rm install.php</code> oder manuell aus dem Verzeichnis entfernen
                        </div>
                        <div class="checklist-item">
                            ✓ Debug-Modus in config.php deaktivieren (Production)
                        </div>
                        <div class="checklist-item">
                            ✓ HTTPS aktivieren (SSL-Zertifikat)
                        </div>
                        <div class="checklist-item">
                            ✓ Backup-Strategie einrichten
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo htmlspecialchars($success['site_url']); ?>/login" class="btn">
                    Zum Login →
                </a>
                
                <p style="margin-top: 2rem; color: #94a3b8; font-size: 0.875rem;">
                    Viel Erfolg mit Ihrem neuen CMS! 🚀
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
