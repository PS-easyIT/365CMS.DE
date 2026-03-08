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

        // Vendor-Autoloader für externe Libraries (CMS/assets/)
        // Primär: produktiver Autoloader aus CMS/assets/ (wird aufs Hosting deployed)
        $vendorAutoload = ABSPATH . 'assets/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        } else {
            // Fallback: Entwicklungs-Autoloader (ASSETS/ im Repo-Root, wird NICHT deployed)
            $devAutoload = dirname(ABSPATH) . '/ASSETS/autoload.php';
            if (file_exists($devAutoload)) {
                require_once $devAutoload;
            }
        }
    }
    
    /**
     * Stellt sicher dass alle kritischen CMS-Konstanten definiert sind.
     * Dient als Fallback für Server-Umgebungen, in denen config.php
     * ältere oder unvollständige Definitionen enthält.
     */
    private function ensureConstants(): void
    {
        defined('CMS_VERSION')   || define('CMS_VERSION',   '2.5.4');
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

        // H-10: Inkrementelle DB-Migrationen ausführen (idempotent, version-basiert –
        // nur 1 DB-Query pro Request wenn bereits aktuell)
        (new MigrationManager($this->db))->run();

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

        // PurifierService – lazy Singleton (wird erst bei erstem Zugriff instanziiert)
        $this->container->singleton(Services\PurifierService::class, fn() => Services\PurifierService::getInstance());
        $this->container->singleton('purifier', fn() => Services\PurifierService::getInstance());

        // MailService – lazy Singleton
        $this->container->singleton(Services\MailService::class, fn() => Services\MailService::getInstance());
        $this->container->singleton('mail', fn() => Services\MailService::getInstance());

        // SettingsService – zentrale Settings-Abstraktion für gruppierte/verschlüsselte Werte
        $this->container->singleton(Services\SettingsService::class, fn() => Services\SettingsService::getInstance());
        $this->container->singleton('settings', fn() => Services\SettingsService::getInstance());

        // AzureMailTokenProvider – XOAUTH2 Token-Caching für Microsoft 365 SMTP
        $this->container->singleton(Services\AzureMailTokenProvider::class, fn() => Services\AzureMailTokenProvider::getInstance());
        $this->container->singleton('mail.azure', fn() => Services\AzureMailTokenProvider::getInstance());

        // MailLogService – Versandhistorie für Admin & Diagnose
        $this->container->singleton(Services\MailLogService::class, fn() => Services\MailLogService::getInstance());
        $this->container->singleton('mail.logs', fn() => Services\MailLogService::getInstance());

        // MailQueueService – asynchroner Mailversand mit Cron-Worker und Retry-Backoff
        $this->container->singleton(Services\MailQueueService::class, fn() => Services\MailQueueService::getInstance());
        $this->container->singleton('mail.queue', fn() => Services\MailQueueService::getInstance());

        // GraphApiService – Microsoft Graph via Client-Credentials
        $this->container->singleton(Services\GraphApiService::class, fn() => Services\GraphApiService::getInstance());
        $this->container->singleton('graph', fn() => Services\GraphApiService::getInstance());

        // SearchService – lazy Singleton (TNTSearch Volltextsuche)
        $this->container->singleton(Services\SearchService::class, fn() => Services\SearchService::getInstance());
        $this->container->singleton('search', fn() => Services\SearchService::getInstance());

        // ImageService – lazy Singleton (GD-basierte Bildbearbeitung)
        $this->container->singleton(Services\ImageService::class, fn() => Services\ImageService::getInstance());
        $this->container->singleton('image', fn() => Services\ImageService::getInstance());

        // FeedService – lazy Singleton (SimplePie RSS/Atom-Parsing)
        $this->container->singleton(Services\FeedService::class, fn() => Services\FeedService::getInstance());
        $this->container->singleton('feed', fn() => Services\FeedService::getInstance());

        // CookieConsentService – lazy Singleton (Frontend Consent-Banner)
        $this->container->singleton(Services\CookieConsentService::class, fn() => Services\CookieConsentService::getInstance());
        $this->container->singleton('cookieconsent', fn() => Services\CookieConsentService::getInstance());

        // TranslationService – lazy Singleton (I18n)
        $this->container->singleton(Services\TranslationService::class, fn() => Services\TranslationService::getInstance());
        $this->container->singleton('translation', fn() => Services\TranslationService::getInstance());

        // PdfService – lazy Singleton (Dompdf-basierte PDF-Erzeugung)
        $this->container->singleton(Services\PdfService::class, fn() => Services\PdfService::getInstance());
        $this->container->singleton('pdf', fn() => Services\PdfService::getInstance());

        // EditorService – lazy Singleton (SunEditor WYSIWYG)
        $this->container->singleton(Services\EditorService::class, fn() => Services\EditorService::getInstance());
        $this->container->singleton('editor', fn() => Services\EditorService::getInstance());

        // EditorJsService – lazy Singleton (Editor.js Block-Editor)
        $this->container->singleton(Services\EditorJsService::class, fn() => Services\EditorJsService::getInstance());
        $this->container->singleton('editorjs', fn() => Services\EditorJsService::getInstance());

        // EditorJsRenderer – lazy Singleton (Editor.js HTML-Rendering)
        $this->container->singleton(Services\EditorJsRenderer::class, fn() => Services\EditorJsRenderer::getInstance());
        $this->container->singleton('editorjs.renderer', fn() => Services\EditorJsRenderer::getInstance());

        // FileUploadService – lazy Singleton (FilePond-Upload)
        $this->container->singleton(Services\FileUploadService::class, fn() => Services\FileUploadService::getInstance());
        $this->container->singleton('fileupload', fn() => Services\FileUploadService::getInstance());

        // CommentService – lazy Singleton (Kommentarsystem)
        $this->container->singleton(Services\CommentService::class, fn() => Services\CommentService::getInstance());
        $this->container->singleton('comments', fn() => Services\CommentService::getInstance());

        // SEOService – lazy Singleton (Meta-Tags, Schema.org)
        $this->container->singleton(Services\SEOService::class, fn() => Services\SEOService::getInstance());
        $this->container->singleton('seo', fn() => Services\SEOService::getInstance());

        // MemberService – lazy Singleton (Mitgliederverwaltung)
        $this->container->singleton(Services\MemberService::class, fn() => Services\MemberService::getInstance());
        $this->container->singleton('member', fn() => Services\MemberService::getInstance());

        // MessageService – lazy Singleton (Internes Nachrichtensystem)
        $this->container->singleton(Services\MessageService::class, fn() => Services\MessageService::getInstance());
        $this->container->singleton('messages', fn() => Services\MessageService::getInstance());

        // UserService – lazy Singleton (Benutzerverwaltung)
        $this->container->singleton(Services\UserService::class, fn() => Services\UserService::getInstance());
        $this->container->singleton('users', fn() => Services\UserService::getInstance());

        // StatusService – lazy Singleton (Online-Status)
        $this->container->singleton(Services\StatusService::class, fn() => Services\StatusService::getInstance());
        $this->container->singleton('status', fn() => Services\StatusService::getInstance());

        // DashboardService – lazy Singleton (Admin-Dashboard-Statistiken)
        $this->container->singleton(Services\DashboardService::class, fn() => Services\DashboardService::getInstance());
        $this->container->singleton('dashboard', fn() => Services\DashboardService::getInstance());

        // LandingPageService – lazy Singleton (Landing-Page-Builder)
        $this->container->singleton(Services\LandingPageService::class, fn() => Services\LandingPageService::getInstance());
        $this->container->singleton('landingpage', fn() => Services\LandingPageService::getInstance());

        // AnalyticsService – lazy Singleton (Seitenstatistiken)
        $this->container->singleton(Services\AnalyticsService::class, fn() => Services\AnalyticsService::getInstance());
        $this->container->singleton('analytics', fn() => Services\AnalyticsService::getInstance());

        // TrackingService – lazy Singleton (Seitenaufrufe)
        $this->container->singleton(Services\TrackingService::class, fn() => Services\TrackingService::getInstance());
        $this->container->singleton('tracking', fn() => Services\TrackingService::getInstance());

        // BackupService – lazy Singleton (Datenbank-Backups)
        $this->container->singleton(Services\BackupService::class, fn() => Services\BackupService::getInstance());
        $this->container->singleton('backup', fn() => Services\BackupService::getInstance());

        // SystemService – lazy Singleton (Systeminfo & Wartung)
        $this->container->singleton(Services\SystemService::class, fn() => Services\SystemService::instance());
        $this->container->singleton('system', fn() => Services\SystemService::instance());

        // ThemeCustomizer – lazy Singleton (Theme-Anpassungen)
        $this->container->singleton(Services\ThemeCustomizer::class, fn() => Services\ThemeCustomizer::instance());
        $this->container->singleton('customizer', fn() => Services\ThemeCustomizer::instance());

        // UpdateService – lazy Singleton (Auto-Update-Prüfung)
        $this->container->singleton(Services\UpdateService::class, fn() => Services\UpdateService::getInstance());
        $this->container->singleton('update', fn() => Services\UpdateService::getInstance());

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

        // Frontend: CookieConsent im Theme-Footer ausgeben
        if ($this->mode === 'web') {
            Hooks::addAction('body_end', [Services\CookieConsentService::getInstance(), 'render'], 20);

            // PhotoSwipe V5 — Lightbox für Bilder in Content-Bereichen
            Hooks::addAction('head', function () {
                echo '<link rel="stylesheet" href="' . SITE_URL . '/assets/photoswipe/photoswipe.css">' . "\n";
            }, 30);

            // Custom Fonts (DSGVO-konform lokal gespeicherte Schriften)
            Hooks::addAction('head', function () {
                try {
                    $db = Database::instance();
                    $fonts = $db->get_results(
                        "SELECT css_path FROM {$db->getPrefix()}custom_fonts WHERE css_path IS NOT NULL AND css_path != ''"
                    ) ?: [];
                    foreach ($fonts as $font) {
                        $cssFile = ABSPATH . ltrim($font->css_path, '/');
                        if (is_file($cssFile)) {
                            echo '<link rel="stylesheet" href="' . SITE_URL . '/' . htmlspecialchars($font->css_path) . '">' . "\n";
                        }
                    }
                } catch (\Throwable $e) {
                    // Tabelle existiert noch nicht – ignorieren
                }
            }, 15);
            Hooks::addAction('body_end', function () {
                echo '<script type="module" src="' . SITE_URL . '/assets/js/photoswipe-init.js"></script>' . "\n";
            }, 10);
        }

        // Initialize hooks
        Hooks::addAction('cms_cron_mail_queue', [Services\MailQueueService::getInstance(), 'handleCronHook'], 10);
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
