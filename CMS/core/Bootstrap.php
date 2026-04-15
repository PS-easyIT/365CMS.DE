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

use CMS\Services\SiteTable\SiteTableHubRenderer;

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists(__NAMESPACE__ . '\\Bootstrap', false)) {
    return;
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
    private ?Router $router = null;
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
        require_once CORE_PATH . 'Debug.php';

        $this->mode = self::detectMode();
        defined('CMS_MODE') || define('CMS_MODE', $this->mode);

        Debug::enable(defined('CMS_DEBUG') && CMS_DEBUG);
        Debug::resetRuntimeProfile([
            'mode' => $this->mode,
            'request_uri' => (string)($_SERVER['REQUEST_URI'] ?? '/'),
            'request_method' => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? (PHP_SAPI === 'cli' ? 'CLI' : 'GET'))),
            'sapi' => PHP_SAPI,
        ]);
        Debug::checkpoint('bootstrap.start', ['mode' => $this->mode]);

        $this->loadDependencies();
        Debug::checkpoint('bootstrap.dependencies_loaded', ['mode' => $this->mode]);
        $this->validateBundledPhpPlatform();
        Debug::checkpoint('bootstrap.platform_validated', ['mode' => $this->mode]);
        $this->initializeCore();
        Debug::checkpoint('bootstrap.ready', ['mode' => $this->mode]);
    }

    /**
     * H-08: Prüft, ob gebündelte Runtime-Libraries zur offiziellen PHP-Zielplattform passen.
     */
    private function validateBundledPhpPlatform(): void
    {
        $requiredPhpVersion = defined('CMS_MIN_PHP_VERSION') ? CMS_MIN_PHP_VERSION : '8.4.0';
        $manifests = [
            'symfony/mailer' => ABSPATH . 'assets/mailer/composer.json',
            'symfony/mime' => ABSPATH . 'assets/mime/composer.json',
            'symfony/translation' => ABSPATH . 'assets/translation/composer.json',
        ];

        $violations = [];

        foreach ($manifests as $packageName => $manifestPath) {
            $bundlePhpVersion = $this->extractMinimumPhpVersion($manifestPath);
            if ($bundlePhpVersion === null) {
                continue;
            }

            if (version_compare($requiredPhpVersion, $bundlePhpVersion, '<')) {
                $violations[] = sprintf(
                    '%s verlangt mindestens PHP %s, die offizielle CMS-Plattform ist aber nur auf %s gesetzt.',
                    $packageName,
                    $bundlePhpVersion,
                    $requiredPhpVersion
                );
            }

            if (version_compare(PHP_VERSION, $bundlePhpVersion, '<')) {
                $violations[] = sprintf(
                    '%s verlangt mindestens PHP %s, aktiv ist jedoch PHP %s.',
                    $packageName,
                    $bundlePhpVersion,
                    PHP_VERSION
                );
            }
        }

        if ($violations === []) {
            return;
        }

        $message = '365CMS konnte nicht starten, weil gebündelte Runtime-Abhängigkeiten eine höhere PHP-Version verlangen: ' . implode(' ', $violations);
        error_log($message);

        self::abortForPlatformMismatch($message);
    }

    private function extractMinimumPhpVersion(string $manifestPath): ?string
    {
        if (!is_file($manifestPath) || !is_readable($manifestPath)) {
            return null;
        }

        $manifest = Json::decodeArray(file_get_contents($manifestPath), []);
        $phpConstraint = is_array($manifest) ? ($manifest['require']['php'] ?? null) : null;

        if (!is_string($phpConstraint) || trim($phpConstraint) === '') {
            return null;
        }

        if (preg_match('/>=\s*([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        if (preg_match('/\^\s*([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+){0,2})/', $phpConstraint, $matches) === 1) {
            return $this->normalizeVersion($matches[1]);
        }

        return null;
    }

    private function normalizeVersion(string $version): string
    {
        $parts = explode('.', $version);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        return implode('.', array_slice($parts, 0, 3));
    }

    private static function abortForPlatformMismatch(string $message): never
    {
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, $message . PHP_EOL);
            exit(1);
        }

        http_response_code(503);

        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $expectsJson = defined('CMS_AJAX_REQUEST')
            || str_starts_with($requestUri, '/api/')
            || $requestMethod === 'POST';

        if ($expectsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => $message,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>365CMS – Plattformanforderung nicht erfüllt</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #0f172a; margin: 0; padding: 2rem; }
                .box { max-width: 760px; margin: 10vh auto; background: #fff; border-radius: 16px; padding: 2rem 2.25rem; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16); }
                h1 { margin-top: 0; color: #b91c1c; }
                p { line-height: 1.65; color: #475569; }
            </style>
        </head>
        <body>
            <div class="box">
                <h1>⚠️ Plattformanforderung nicht erfüllt</h1>
                <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Bitte die Hosting-Plattform und die offiziell deklarierte CMS-Mindestversion synchron halten, bevor 365CMS produktiv gebootet wird.</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    /**
     * Load core dependencies
     */
    private function loadDependencies(): void
    {
        require_once CORE_PATH . 'Debug.php';
        require_once CORE_PATH . 'Version.php';
        require_once CORE_PATH . 'Container.php';
        require_once CORE_PATH . 'Database.php';
        require_once CORE_PATH . 'Security.php';
        require_once CORE_PATH . 'AuditLogger.php';
        require_once CORE_PATH . 'Logger.php';
        require_once CORE_PATH . 'Totp.php';
        require_once CORE_PATH . 'Auth.php';
        require_once CORE_PATH . 'PluginManager.php';
        require_once CORE_PATH . 'Hooks.php';
        require_once CORE_PATH . 'CacheManager.php';
        // H-10: Schema- und Migrations-Manager
        if (!class_exists(__NAMESPACE__ . '\SchemaManager', false)) {
            require_once CORE_PATH . 'SchemaManager.php';
        }
        require_once CORE_PATH . 'MigrationManager.php';

        if ($this->mode !== 'cli') {
            require_once CORE_PATH . 'Router.php';
            require_once CORE_PATH . 'PageManager.php';
            require_once CORE_PATH . 'Api.php';
            require_once CORE_PATH . 'SubscriptionManager.php';
        }

        if (in_array($this->mode, ['web', 'admin'], true) && !class_exists(__NAMESPACE__ . '\ThemeManager', false)) {
            require_once CORE_PATH . 'ThemeManager.php';
        }
        
        if (file_exists(ABSPATH . 'includes/functions.php') && !defined('CMS_GLOBAL_FUNCTIONS_LOADED') && !function_exists('esc_html')) {
            require_once ABSPATH . 'includes/functions.php';
        }
        
        if ($this->mode !== 'cli' && file_exists(ABSPATH . 'includes/subscription-helpers.php')) {
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
        defined('CMS_VERSION')   || define('CMS_VERSION',   Version::CURRENT);
        defined('CMS_MIN_PHP_VERSION') || define('CMS_MIN_PHP_VERSION', '8.4.0');
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
        Debug::checkpoint('bootstrap.constants_ready', ['mode' => $this->mode]);

        // H-06: Container initialisieren
        $this->container = Container::instance();

        // Database – immer erforderlich
        $this->db = Database::instance();
        $this->container->bindInstance(Database::class, $this->db);
        $this->container->bindInstance('db', $this->db);
        Debug::checkpoint('bootstrap.database_ready');

        // H-10: Inkrementelle DB-Migrationen ausführen (idempotent, version-basiert –
        // nur 1 DB-Query pro Request wenn bereits aktuell)
        (new MigrationManager($this->db))->run();
        Debug::checkpoint('bootstrap.migrations_checked');

        // Security init (setzt HTTP-Security-Header) – nicht im CLI-Modus
        $this->security = Security::instance();
        $this->container->bindInstance(Security::class, $this->security);
        if ($this->mode !== 'cli') {
            $this->security->init();
        }
        Debug::checkpoint('bootstrap.security_ready');

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

        // FeedService – lazy Singleton (nativer RSS/Atom-Parser)
        $this->container->singleton(Services\FeedService::class, fn() => Services\FeedService::getInstance());
        $this->container->singleton('feed', fn() => Services\FeedService::getInstance());

        // TranslationService – lazy Singleton (I18n)
        $this->container->singleton(Services\TranslationService::class, fn() => Services\TranslationService::getInstance());
        $this->container->singleton('translation', fn() => Services\TranslationService::getInstance());

        // EditorJsService – lazy Singleton (Editor.js Block-Editor)
        if ($this->mode !== 'cli') {
            $this->container->singleton(Services\EditorJsService::class, fn() => Services\EditorJsService::getInstance());
            $this->container->singleton('editorjs', fn() => Services\EditorJsService::getInstance());
        }

        // EditorJsRenderer – lazy Singleton (Editor.js HTML-Rendering)
        if ($this->mode !== 'cli') {
            $this->container->singleton(Services\EditorJsRenderer::class, fn() => Services\EditorJsRenderer::getInstance());
            $this->container->singleton('editorjs.renderer', fn() => Services\EditorJsRenderer::getInstance());
        }

        // FileUploadService – lazy Singleton (interner Upload-Endpunkt)
        if ($this->mode !== 'cli') {
            $this->container->singleton(Services\FileUploadService::class, fn() => Services\FileUploadService::getInstance());
            $this->container->singleton('fileupload', fn() => Services\FileUploadService::getInstance());
        }

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

        // TrackingService – lazy Singleton (Seitenaufrufe)
        if ($this->mode !== 'cli') {
            $this->container->singleton(Services\TrackingService::class, fn() => Services\TrackingService::getInstance());
            $this->container->singleton('tracking', fn() => Services\TrackingService::getInstance());

            // FeatureUsageService – datensparsame Admin-/Member-Funktionsmetriken
            $this->container->singleton(Services\FeatureUsageService::class, fn() => Services\FeatureUsageService::getInstance());
            $this->container->singleton('featureusage', fn() => Services\FeatureUsageService::getInstance());
        }

        // BackupService – lazy Singleton (Datenbank-Backups)
        $this->container->singleton(Services\BackupService::class, fn() => Services\BackupService::getInstance());
        $this->container->singleton('backup', fn() => Services\BackupService::getInstance());

        // SystemService – lazy Singleton (Systeminfo & Wartung)
        $this->container->singleton(Services\SystemService::class, fn() => Services\SystemService::instance());
        $this->container->singleton('system', fn() => Services\SystemService::instance());

        // UpdateService – lazy Singleton (Auto-Update-Prüfung)
        $this->container->singleton(Services\UpdateService::class, fn() => Services\UpdateService::getInstance());
        $this->container->singleton('update', fn() => Services\UpdateService::getInstance());

        if ($this->mode !== 'cli') {
            // Router – nur für Web/Admin/API (CLI routed anders)
            $this->router = Router::instance();
            $this->container->bindInstance(Router::class, $this->router);
        }

        // Plugin Manager – immer laden (Plugins können CLI-Hooks registrieren)
        $this->pluginManager = PluginManager::instance();
        $this->container->bindInstance(PluginManager::class, $this->pluginManager);
        $this->pluginManager->loadPlugins();
        Debug::checkpoint('bootstrap.plugins_loaded');

        // Theme Manager – im Admin verfügbar, Theme-Runtime aber nur im Web-Pfad booten
        if (in_array($this->mode, ['web', 'admin'], true)) {
            $this->themeManager = ThemeManager::instance();
            $this->container->bindInstance(ThemeManager::class, $this->themeManager);
            if ($this->mode === 'web') {
                $this->themeManager->loadTheme();
            }
        }
        Debug::checkpoint('bootstrap.theme_state_ready', ['theme_loaded' => $this->mode === 'web']);

        // Subscription Manager – in Web/Admin verfügbar, API/CLI laden bei Bedarf direkt
        if (in_array($this->mode, ['web', 'admin'], true)) {
            $sm = SubscriptionManager::instance();
            $this->container->bindInstance(SubscriptionManager::class, $sm);
        }

        if ($this->mode !== 'cli') {
            // PdfService – typischer Web-/Admin-Renderpfad
            $this->container->singleton(Services\PdfService::class, fn() => Services\PdfService::getInstance());
            $this->container->singleton('pdf', fn() => Services\PdfService::getInstance());
        }

        if (in_array($this->mode, ['web', 'admin'], true)) {
            // EditorService – SunEditor / Content-Rendering in Web/Admin
            $this->container->singleton(Services\EditorService::class, fn() => Services\EditorService::getInstance());
            $this->container->singleton('editor', fn() => Services\EditorService::getInstance());

            // ThemeCustomizer – Theme-bezogene Laufzeit-/Admin-Konfiguration
            $this->container->singleton(Services\ThemeCustomizer::class, fn() => Services\ThemeCustomizer::instance());
            $this->container->singleton('customizer', fn() => Services\ThemeCustomizer::instance());
        }

        if ($this->mode === 'admin') {
            // AnalyticsService – nur im Admin-Diagnose-/SEO-Pfad
            $this->container->singleton(Services\AnalyticsService::class, fn() => Services\AnalyticsService::getInstance());
            $this->container->singleton('analytics', fn() => Services\AnalyticsService::getInstance());
        }

        if ($this->mode === 'web') {
            Hooks::addAction('body_end', static function (): void {
                Services\CoreWebVitalsService::getInstance()->renderTrackingScript();
            }, 15);

            // CookieConsentService – reiner Frontend-Consent-Banner
            $this->container->singleton(Services\CookieConsentService::class, fn() => Services\CookieConsentService::getInstance());
            $this->container->singleton('cookieconsent', fn() => Services\CookieConsentService::getInstance());
        }

        // Frontend: CookieConsent im Theme-Footer ausgeben
        if ($this->mode === 'web') {
            $shouldLoadPhotoSwipe = static function (): bool {
                try {
                    $enabled = Services\ThemeCustomizer::instance()->get('performance', 'enable_photoswipe', true);
                    if (!filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                        return false;
                    }
                } catch (\Throwable) {
                }

                $requestUri = (string) (strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
                $path = $requestUri;

                try {
                    $context = Services\ContentLocalizationService::getInstance()->resolveRequestContext($requestUri);
                    $baseUri = (string) ($context['base_uri'] ?? $requestUri);
                    if ($baseUri !== '') {
                        $path = $baseUri;
                    }
                } catch (\Throwable) {
                }

                if (SiteTableHubRenderer::isHubRequestUri($requestUri)) {
                    return true;
                }

                if (
                    in_array($path, ['/', '/blog', '/search', '/404', '/error', '/login', '/register', '/cms-login', '/cms-register', '/cms-password-forgot'], true)
                    || str_starts_with($path, '/member')
                    || str_starts_with($path, '/dashboard')
                    || \cms_is_archive_request_path($path, 'category')
                    || \cms_is_archive_request_path($path, 'tag')
                    || str_starts_with($path, '/author/')
                    || $path === '/authors'
                    || $path === '/autoren'
                ) {
                    if ($path !== '/') {
                        return false;
                    }
                }

                if ($path === '/') {
                    try {
                        $host = strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? ''), '.'));
                        if ($host !== '') {
                            $siteTableService = Services\SiteTableService::getInstance();
                            return $siteTableService->getHubPageByDomain($host, 'de') !== null
                                || $siteTableService->getHubPageByDomain($host, 'en') !== null;
                        }
                    } catch (\Throwable) {
                    }

                    return false;
                }

                try {
                    $postSlug = Services\PermalinkService::getInstance()->extractPostSlugFromPath($path);
                    if (is_string($postSlug) && trim($postSlug) !== '') {
                        return true;
                    }
                } catch (\Throwable) {
                }

                if (preg_match('#^/blog/[^/]+$#', $path) === 1) {
                    return true;
                }

                $slug = trim($path, '/');
                if ($slug !== '' && !str_contains($slug, '/')) {
                    try {
                        if (Services\SiteTableService::getInstance()->hubExistsBySlug($slug)) {
                            return true;
                        }
                    } catch (\Throwable) {
                    }

                    return true;
                }

                return false;
            };

            Hooks::addAction('body_end', static function (): void {
                Services\CookieConsentService::getInstance()->render();
            }, 20);

            // PhotoSwipe V5 — Lightbox für Bilder in Content-Bereichen
            Hooks::addAction('head', static function () use ($shouldLoadPhotoSwipe): void {
                if (!$shouldLoadPhotoSwipe()) {
                    return;
                }

                $href = htmlspecialchars(SITE_URL . '/assets/photoswipe/photoswipe.css', ENT_QUOTES, 'UTF-8');
                echo '<link rel="preload" as="style" href="' . $href . '" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
                echo '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>' . "\n";
            }, 30);

            Hooks::addAction('head', static function (): void {
                static $hubStylesRendered = false;

                if ($hubStylesRendered) {
                    return;
                }

                $hubStylesRendered = true;
                $href = htmlspecialchars(cms_asset_url('css/hub-sites.css'), ENT_QUOTES, 'UTF-8');
                echo '<link rel="stylesheet" href="' . $href . '">' . "\n";
            }, 12);

            // Custom Fonts (DSGVO-konform lokal gespeicherte Schriften)
            Hooks::addAction('head', function () {
                try {
                    $db = Database::instance();
                    $localFontsRow = $db->get_row(
                        "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = 'privacy_use_local_fonts' LIMIT 1"
                    );

                    if (!$localFontsRow || (string)($localFontsRow->option_value ?? '0') !== '1') {
                        return;
                    }

                    $requestedFontSlugs = Hooks::applyFilters('local_font_slugs', []);
                    $requestedFontSlugs = array_values(array_unique(array_filter(array_map(
                        static fn($slug): string => preg_replace('/[^a-z0-9_-]/i', '', (string)$slug) ?? '',
                        is_array($requestedFontSlugs) ? $requestedFontSlugs : []
                    ))));

                    if ($requestedFontSlugs === []) {
                        $fontSettingRows = $db->get_results(
                            "SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name IN ('font_heading', 'font_body')"
                        ) ?: [];

                        foreach ($fontSettingRows as $fontSettingRow) {
                            $slug = preg_replace('/[^a-z0-9_-]/i', '', (string) ($fontSettingRow->option_value ?? ''));
                            if ($slug !== '') {
                                $requestedFontSlugs[] = $slug;
                            }
                        }

                        $requestedFontSlugs = array_values(array_unique($requestedFontSlugs));
                    }

                    if ($requestedFontSlugs === []) {
                        return;
                    }

                    $fonts = $db->get_results(
                        "SELECT slug, css_path FROM {$db->getPrefix()}custom_fonts WHERE css_path IS NOT NULL AND css_path != ''"
                    ) ?: [];

                    $fontMap = [];
                    foreach ($fonts as $font) {
                        $slug = preg_replace('/[^a-z0-9_-]/i', '', (string)($font->slug ?? ''));
                        if ($slug === '') {
                            continue;
                        }
                        $fontMap[$slug] = (string)($font->css_path ?? '');
                    }

                    foreach ($requestedFontSlugs as $slug) {
                        if (!isset($fontMap[$slug])) {
                            continue;
                        }

                        $cssFile = ABSPATH . ltrim($fontMap[$slug], '/');
                        if (is_file($cssFile)) {
                            $cssHref = function_exists('cms_runtime_base_url')
                                ? cms_runtime_base_url(ltrim((string) $fontMap[$slug], '/'))
                                : rtrim((string) SITE_URL, '/') . '/' . ltrim((string) $fontMap[$slug], '/');

                            $safeCssHref = htmlspecialchars($cssHref, ENT_QUOTES, 'UTF-8');
                            echo '<link rel="preload" as="style" href="' . $safeCssHref . '" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
                            echo '<noscript><link rel="stylesheet" href="' . $safeCssHref . '"></noscript>' . "\n";
                        }
                    }
                } catch (\Throwable $e) {
                    // Tabelle existiert noch nicht – ignorieren
                }
            }, 15);
            Hooks::addAction('body_end', static function () use ($shouldLoadPhotoSwipe): void {
                if (!$shouldLoadPhotoSwipe()) {
                    return;
                }

                echo '<script type="module" src="' . SITE_URL . '/assets/js/photoswipe-init.js"></script>' . "\n";
            }, 10);
        }

        // Initialize hooks
        Hooks::addAction('cms_cron_mail_queue', static function (...$args): void {
            $context = $args[0] ?? null;
            if (is_array($context) && !empty($context['mail_queue_already_handled'])) {
                return;
            }

            Services\MailQueueService::getInstance()->handleCronHook(...$args);
        }, 10);

        Services\OpcacheWarmupService::getInstance()->maybeWarmAfterDeploy(30);
        Debug::checkpoint('bootstrap.opcache_checked');

        Hooks::doAction('cms_init');
        Hooks::doAction('cms_init_' . $this->mode); // H-12: Modus-spezifischer Hook
        Debug::checkpoint('bootstrap.hooks_initialized', ['mode' => $this->mode]);
    }
    
    /**
     * Run the CMS
     */
    public function run(): void
    {
        if ($this->router === null) {
            throw new \RuntimeException('Bootstrap::run() ist im Modus "' . $this->mode . '" ohne Router nicht verfügbar.');
        }

        Debug::checkpoint('bootstrap.run.start', ['mode' => $this->mode]);

        // Pre-routing hook
        Hooks::doAction('cms_before_route');
        
        // Allow plugins to register routes (MUST be before dispatch)
        Hooks::doAction('register_routes', $this->router);
        
        // Handle routing
        $this->router->dispatch();
        Debug::checkpoint('bootstrap.run.after_dispatch', ['mode' => $this->mode]);
        
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
