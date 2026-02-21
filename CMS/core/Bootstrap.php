<?php
/**
 * CMS Bootstrap Class
 * 
 * Initializes the CMS system, loads plugins/themes, handles routing
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Bootstrap
{
    private static ?self $instance = null;
    
    private Database $db;
    private Security $security;
    private Auth $auth;
    private Router $router;
    private PluginManager $pluginManager;
    private ThemeManager $themeManager;
    
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
        $this->loadDependencies();
        $this->initializeCore();
    }
    
    /**
     * Load core dependencies
     */
    private function loadDependencies(): void
    {
        require_once CORE_PATH . 'Database.php';
        require_once CORE_PATH . 'Security.php';
        require_once CORE_PATH . 'Auth.php';
        require_once CORE_PATH . 'Router.php';
        require_once CORE_PATH . 'PluginManager.php';
        require_once CORE_PATH . 'ThemeManager.php';
        require_once CORE_PATH . 'Hooks.php';
        require_once CORE_PATH . 'CacheManager.php';
        require_once CORE_PATH . 'PageManager.php';
        require_once CORE_PATH . 'Api.php';
        require_once CORE_PATH . 'SubscriptionManager.php';
        
        if (file_exists(ABSPATH . 'includes/functions.php')) {
            require_once ABSPATH . 'includes/functions.php';
        }
        
        if (file_exists(ABSPATH . 'includes/subscription-helpers.php')) {
            require_once ABSPATH . 'includes/subscription-helpers.php';
        }
    }
    
    /**
     * Stellt sicher dass alle kritischen CMS-Konstanten definiert sind.
     * Dient als Fallback für Server-Umgebungen, in denen config.php
     * ältere oder unvollständige Definitionen enthält.
     */
    private function ensureConstants(): void
    {
        defined('CMS_VERSION')   || define('CMS_VERSION',   '2.0.0');
        defined('SITE_NAME')     || define('SITE_NAME',     'CMS');
        defined('SITE_URL')      || define('SITE_URL',      '');
        defined('ADMIN_EMAIL')   || define('ADMIN_EMAIL',   '');
        defined('CORE_PATH')     || define('CORE_PATH',     ABSPATH . 'core/');
        defined('THEME_PATH')    || define('THEME_PATH',    ABSPATH . 'themes/');
        defined('PLUGIN_PATH')   || define('PLUGIN_PATH',   ABSPATH . 'plugins/');
        defined('UPLOAD_PATH')   || define('UPLOAD_PATH',   ABSPATH . 'uploads/');
        defined('ASSETS_PATH')   || define('ASSETS_PATH',   ABSPATH . 'assets/');
    }

    /**
     * Initialize core components
     */
    private function initializeCore(): void
    {
        $this->ensureConstants();

        // Database
        $this->db = Database::instance();
        
        // Security
        $this->security = Security::instance();
        $this->security->init();
        
        // Authentication
        $this->auth = Auth::instance();
        
        // Router
        $this->router = Router::instance();
        
        // Plugin Manager
        $this->pluginManager = PluginManager::instance();
        $this->pluginManager->loadPlugins();
        
        // Theme Manager
        $this->themeManager = ThemeManager::instance();
        $this->themeManager->loadTheme();
        
        // Subscription Manager
        SubscriptionManager::instance();
        
        // Initialize hooks
        Hooks::doAction('cms_init');
    }
    
    /**
     * Run the CMS
     */
    public function run(): void
    {
        // Pre-routing hook
        Hooks::doAction('cms_before_route');
        
        // Allow plugins to register routes (MUST be before dispatch)
        Hooks::doAction('register_routes', $this->router);
        
        // Handle routing
        $this->router->dispatch();
        
        // Post-routing hook
        Hooks::doAction('cms_after_route');
    }
    
    /**
     * Get database instance
     */
    public function db(): Database
    {
        return $this->db;
    }
    
    /**
     * Get auth instance
     */
    public function auth(): Auth
    {
        return $this->auth;
    }
    
    /**
     * Get security instance
     */
    public function security(): Security
    {
        return $this->security;
    }
}
