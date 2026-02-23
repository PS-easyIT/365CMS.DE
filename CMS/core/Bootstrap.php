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
    
    /** H-12: Erkannter Betriebsmodus (web | admin | api | cli) */
    private string $mode = 'web';

    /** H-06: Service-Container */
    private Container $container;

    private Database $db;
    private Security $security;
    private Auth $auth;
    private Router $router;
    private PluginManager $pluginManager;
    /** @var ThemeManager|null Im API/CLI-Modus nicht geladen (H-12) */
    private ?ThemeManager $themeManager = null;

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
     * H-12: Betriebsmodus ermitteln und als Konstante definieren.
     *
     * Modus       | Trigger
     * ------------|----------------------------------------------
     * cli         | PHP_SAPI === 'cli'
     * api         | Request-URI beginnt mit /api/
     * admin       | Request-URI beginnt mit /admin/
     * web         | alle anderen Anfragen (Standard)
     */
    private static function detectMode(): string
    {
        if (PHP_SAPI === 'cli') {
            return 'cli';
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Strip query string for comparison; strtok kann false zurückgeben → Fallback '/'
        $path = (string)(strtok($uri, '?') ?: '/');

        if (str_starts_with($path, '/api/')) {
            return 'api';
        }

        if (str_starts_with($path, '/admin/') || $path === '/admin') {
            return 'admin';
        }

        return 'web';
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
        require_once CORE_PATH . 'Container.php';
        require_once CORE_PATH . 'Database.php';
        require_once CORE_PATH . 'Security.php';
        require_once CORE_PATH . 'AuditLogger.php';
        require_once CORE_PATH . 'Logger.php';
        require_once CORE_PATH . 'Totp.php';
        require_once CORE_PATH . 'Auth.php';
        require_once CORE_PATH . 'Router.php';
        require_once CORE_PATH . 'PluginManager.php';
        require_once CORE_PATH . 'ThemeManager.php';
        require_once CORE_PATH . 'Hooks.php';
        require_once CORE_PATH . 'CacheManager.php';
        require_once CORE_PATH . 'PageManager.php';
        require_once CORE_PATH . 'Api.php';
        require_once CORE_PATH . 'SubscriptionManager.php';
        // H-10: Schema- und Migrations-Manager
        require_once CORE_PATH . 'SchemaManager.php';
        require_once CORE_PATH . 'MigrationManager.php';
        
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
     * H-12: Differenzierter Bootstrap-Pfad je Betriebsmodus
     * H-06: DI-Container mit Core-Services befüllen
     */
    private function initializeCore(): void
    {
        $this->ensureConstants();

        // H-06: Container initialisieren
        $this->container = Container::instance();

        // H-12: Modus erkennen und als Konstante setzen
        $this->mode = self::detectMode();
        defined('CMS_MODE') || define('CMS_MODE', $this->mode);

        // Database – immer erforderlich
        $this->db = Database::instance();
        $this->container->bindInstance(Database::class, $this->db);
        $this->container->bindInstance('db', $this->db);

        // Security init (setzt HTTP-Security-Header) – nicht im CLI-Modus
        $this->security = Security::instance();
        $this->container->bindInstance(Security::class, $this->security);
        if ($this->mode !== 'cli') {
            $this->security->init();
        }

        // Authentication – immer erforderlich
        $this->auth = Auth::instance();
        $this->container->bindInstance(Auth::class, $this->auth);

        // Logger – immer verfügbar
        $logger = Logger::instance();
        $this->container->bindInstance(Logger::class, $logger);
        $this->container->bindInstance('logger', $logger);

        // CacheManager – immer verfügbar
        $cache = CacheManager::instance();
        $this->container->bindInstance(CacheManager::class, $cache);
        $this->container->bindInstance('cache', $cache);

        // Router – nur für Web/Admin (CLI routed anders)
        $this->router = Router::instance();
        $this->container->bindInstance(Router::class, $this->router);

        // Plugin Manager – immer laden (Plugins können CLI-Hooks registrieren)
        $this->pluginManager = PluginManager::instance();
        $this->container->bindInstance(PluginManager::class, $this->pluginManager);
        $this->pluginManager->loadPlugins();

        // Theme Manager – nicht im API- oder CLI-Modus benötigt
        if (!in_array($this->mode, ['api', 'cli'], true)) {
            $this->themeManager = ThemeManager::instance();
            $this->container->bindInstance(ThemeManager::class, $this->themeManager);
            $this->themeManager->loadTheme();
        }

        // Subscription Manager – nicht im CLI-Modus
        if ($this->mode !== 'cli') {
            $sm = SubscriptionManager::instance();
            $this->container->bindInstance(SubscriptionManager::class, $sm);
        }

        // Initialize hooks
        Hooks::doAction('cms_init');
        Hooks::doAction('cms_init_' . $this->mode); // H-12: Modus-spezifischer Hook
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

    /**
     * H-06: Zugriff auf den Service-Container
     */
    public function container(): Container
    {
        return $this->container;
    }
}
