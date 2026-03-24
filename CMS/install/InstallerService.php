<?php
declare(strict_types=1);

namespace CMS\Install;

use PDO;
use PDOException;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

final class InstallerService
{
    public function __construct(private readonly string $rootDir)
    {
    }

    public function autoDetectUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($script);
        $basePath = ($dir === '/' || $dir === '\\') ? '' : $dir;

        return rtrim($protocol . $host . $basePath, '/');
    }

    public function generateSecurityKey(int $length = 64): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public function getCmsVersion(): string
    {
        require_once $this->rootDir . '/core/Version.php';

        return \CMS\Version::CURRENT;
    }

    public function getInstallerLockPath(): string
    {
        return $this->rootDir . '/config/install.lock';
    }

    public function hasInstallerLock(): bool
    {
        return is_file($this->getInstallerLockPath());
    }

    public function writeInstallerLockFile(?array $config = null): void
    {
        $lockPath = $this->getInstallerLockPath();
        $lockDir = dirname($lockPath);

        if (!is_dir($lockDir) && !mkdir($lockDir, 0755, true) && !is_dir($lockDir)) {
            return;
        }

        $payload = [
            'locked' => true,
            'created_at' => date(DATE_ATOM),
            'site_url' => trim((string) ($config['site_url'] ?? '')),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return;
        }

        file_put_contents($lockPath, $json);
    }

    public function canAccessInstalledInstaller(): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }

        try {
            require_once $this->rootDir . '/config.php';
            require_once $this->rootDir . '/core/autoload.php';

            return class_exists('CMS\\Auth') && \CMS\Auth::isAdmin();
        } catch (Throwable $e) {
            error_log('install.php admin-guard failed: ' . $e->getMessage());
            return false;
        }
    }

    public function parseExistingConfig(): array|bool
    {
        $configPath = $this->rootDir . '/config/app.php';
        if (!file_exists($configPath)) {
            $configPath = $this->rootDir . '/config.php';
        }

        if (!file_exists($configPath)) {
            return false;
        }

        $content = file_get_contents($configPath);
        if (!is_string($content) || $content === '') {
            return false;
        }

        $config = [];
        $clean = static function (string $val): string {
            if (str_contains($val, 'YOUR_') || str_contains($val, 'REPLACE_VIA_INSTALLER') || str_contains($val, 'example.com')) {
                return '';
            }

            return $val;
        };

        if (preg_match("/define\('DB_HOST',\s*'([^']+)'\);/", $content, $m)) {
            $config['db_host'] = $m[1];
        }
        if (preg_match("/define\('DB_NAME',\s*'([^']+)'\);/", $content, $m)) {
            $config['db_name'] = $clean($m[1]);
        }
        if (preg_match("/define\('DB_USER',\s*'([^']+)'\);/", $content, $m)) {
            $config['db_user'] = $clean($m[1]);
        }
        if (preg_match("/define\('DB_PASS',\s*'([^']+)'\);/", $content, $m)) {
            $config['db_pass'] = str_contains($m[1], 'YOUR_') ? '' : $m[1];
        }
        if (preg_match("/define\('DB_PREFIX',\s*'([^']+)'\);/", $content, $m)) {
            $config['db_prefix'] = $m[1] !== '' ? $m[1] : 'cms_';
        }
        if (preg_match("/define\('SITE_NAME',\s*'([^']+)'\);/", $content, $m)) {
            $config['site_name'] = $clean($m[1]);
        }
        if (preg_match("/define\('ADMIN_EMAIL',\s*'([^']+)'\);/", $content, $m)) {
            $config['admin_email'] = $clean($m[1]);
        }
        if (preg_match("/define\('SITE_URL',\s*'([^']+)'\);/", $content, $m)) {
            $config['site_url'] = $clean($m[1]);
        }
        if (preg_match("/define\('CMS_DEBUG',\s*(true|false)\);/", $content, $m)) {
            $config['debug_mode'] = $m[1];
        }
        if (preg_match("/define\('AUTH_KEY',\s*'([^']+)'\);/", $content, $m)) {
            $config['auth_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
        }
        if (preg_match("/define\('SECURE_AUTH_KEY',\s*'([^']+)'\);/", $content, $m)) {
            $config['secure_auth_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
        }
        if (preg_match("/define\('NONCE_KEY',\s*'([^']+)'\);/", $content, $m)) {
            $config['nonce_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
        }

        return (!empty($config['db_user']) && !empty($config['db_name'])) ? $config : false;
    }

    public function cleanDatabase(PDO $pdo, string $prefix = 'cms_'): array
    {
        $results = ['dropped' => [], 'errors' => []];

        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$prefix}%'");
            $tables = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
            if ($tables === []) {
                return $results;
            }

            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($tables as $table) {
                try {
                    $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
                    $results['dropped'][] = $table;
                } catch (PDOException $e) {
                    $results['errors'][$table] = $e->getMessage();
                }
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (PDOException $e) {
            $results['errors']['general'] = $e->getMessage();
        }

        return $results;
    }

    public function testDatabaseConnection(string $host, string $name, string $user, string $pass): bool|string
    {
        try {
            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return true;
        } catch (PDOException $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    }

    public function createConfigFile(array $data): bool|string
    {
        $configDir = $this->rootDir . '/config';
        $configPath = $configDir . '/app.php';
        $htaccessPath = $configDir . '/.htaccess';
        $stubPath = $this->rootDir . '/config.php';

        if (!is_dir($configDir) && !mkdir($configDir, 0755, true)) {
            return 'Fehler: config/-Verzeichnis konnte nicht erstellt werden';
        }

        $htaccessContent = "# Auto-generated by CMS Installer (C-02)\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n</IfModule>\n";
        file_put_contents($htaccessPath, $htaccessContent);

        if (file_exists($configPath)) {
            $backupPath = $configDir . '/app.php.backup.' . date('Y-m-d_H-i-s');
            copy($configPath, $backupPath);
        }

        $content = <<<PHP
<?php
/**
 * CMS Application Configuration
 *
 * Automatisch erstellt am {$data['created_at']}
 * C-01: Security-Keys via random_bytes() generiert – NICHT in VCS einchecken!
 * C-02: Konfiguration in config/ isoliert (via .htaccess geschützt)
 *
 * @package 365CMS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

define('CMS_DEBUG', {$data['debug_mode']});
define('DB_HOST',    '{$data['db_host']}');
define('DB_NAME',    '{$data['db_name']}');
define('DB_USER',    '{$data['db_user']}');
define('DB_PASS',    '{$data['db_pass']}');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  '{$data['db_prefix']}');
define('AUTH_KEY',        '{$data['auth_key']}');
define('SECURE_AUTH_KEY', '{$data['secure_auth_key']}');
define('NONCE_KEY',       '{$data['nonce_key']}');
define('SITE_NAME',   '{$data['site_name']}');
define('SITE_URL',    '{$data['site_url']}');
define('ADMIN_EMAIL', '{$data['admin_email']}');

require_once ABSPATH . 'core/Version.php';
define('CMS_VERSION', \CMS\Version::CURRENT);
define('CORE_PATH',   ABSPATH . 'core/');
define('THEME_PATH',  ABSPATH . 'themes/');
define('PLUGIN_PATH', ABSPATH . 'plugins/');
define('UPLOAD_PATH', ABSPATH . 'uploads/');
define('ASSETS_PATH', ABSPATH . 'assets/');

\$cmsLogDir = dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
if (!is_dir(\$cmsLogDir) && !@mkdir(\$cmsLogDir, 0755, true) && !is_dir(\$cmsLogDir)) {
    \$cmsLogDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '365cms-logs' . DIRECTORY_SEPARATOR;
    if (!is_dir(\$cmsLogDir)) {
        @mkdir(\$cmsLogDir, 0755, true);
    }
}

defined('LOG_PATH') || define('LOG_PATH', \$cmsLogDir);
defined('CMS_ERROR_LOG') || define('CMS_ERROR_LOG', rtrim(LOG_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'error.log');
define('SITE_URL_PATH', '/');
define('ASSETS_URL',    SITE_URL . '/assets');
define('UPLOAD_URL',    SITE_URL . '/uploads');
define('DEFAULT_THEME',      'cms-default');
define('SESSIONS_LIFETIME',  3600 * 2);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT',      300);
defined('CMS_HTTPS_REDIRECT_STRATEGY') || define('CMS_HTTPS_REDIRECT_STRATEGY', 'upstream');
defined('CMS_HSTS_MODE') || define('CMS_HSTS_MODE', 'https-only');
defined('CMS_HSTS_MAX_AGE') || define('CMS_HSTS_MAX_AGE', 31536000);

if (CMS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', CMS_ERROR_LOG);
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '0');
}

date_default_timezone_set('Europe/Berlin');
PHP;

        if (file_put_contents($configPath, $content) === false) {
            return 'Fehler beim Schreiben von config/app.php';
        }

        $stub = "<?php\n/**\n * CMS Configuration Stub\n *\n * C-02: Eigentliche Konfiguration in config/app.php (via .htaccess geschützt).\n * Automatisch generiert durch CMS Installer.\n *\n * @package 365CMS\n */\n\ndeclare(strict_types=1);\n\nif (!defined('ABSPATH')) {\n    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);\n}\n\nrequire_once __DIR__ . '/config/app.php';\n";
        file_put_contents($stubPath, $stub);

        return true;
    }

    public function updateConfigFile(array $existing, array $updates): bool|string
    {
        $data = [
            'created_at' => date('Y-m-d H:i:s'),
            'debug_mode' => $updates['debug_mode'] ?? $existing['debug_mode'] ?? 'false',
            'db_host' => $existing['db_host'],
            'db_name' => $existing['db_name'],
            'db_user' => $existing['db_user'],
            'db_pass' => $existing['db_pass'],
            'db_prefix' => $existing['db_prefix'] ?? 'cms_',
            'auth_key' => $existing['auth_key'] ?? $this->generateSecurityKey(),
            'secure_auth_key' => $existing['secure_auth_key'] ?? $this->generateSecurityKey(),
            'nonce_key' => $existing['nonce_key'] ?? $this->generateSecurityKey(),
            'site_name' => $updates['site_name'] ?? $existing['site_name'] ?? 'IT Expert Network',
            'site_url' => $updates['site_url'] ?? $existing['site_url'] ?? '',
            'admin_email' => $updates['admin_email'] ?? $existing['admin_email'] ?? '',
        ];

        return $this->createConfigFile($data);
    }

    public function clearSchemaManagerFlagFile(): void
    {
        require_once $this->rootDir . '/core/SchemaManager.php';

        $flagFile = ABSPATH . 'cache/db_schema_' . \CMS\SchemaManager::SCHEMA_VERSION . '.flag';
        if (is_file($flagFile)) {
            unlink($flagFile);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getCentralSchemaQueries(string $prefix = 'cms_', string $charset = 'utf8mb4'): array
    {
        require_once $this->rootDir . '/core/SchemaManager.php';

        return \CMS\SchemaManager::getSchemaQueries($prefix, $charset);
    }

    public function createDatabaseTables(PDO $pdo, string $prefix = 'cms_'): array
    {
        $results = [];
        $tables = $this->getCentralSchemaQueries($prefix);

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

    public function createAdminUser(PDO $pdo, string $username, string $email, string $password, string $prefix = 'cms_'): bool|string
    {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE username = ?");
            $stmt->execute([$username]);
            if ((int) $stmt->fetchColumn() > 0) {
                return 'Admin-User existiert bereits';
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $displayName = ucfirst($username);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}users (username, email, password, display_name, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
            $stmt->execute([$username, $email, $hash, $displayName]);

            return true;
        } catch (PDOException $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    }

    public function createDefaultSettings(PDO $pdo, string $siteName, string $adminEmail, string $prefix = 'cms_'): bool
    {
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
                ['landing_page_content', ''],
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO {$prefix}settings (option_name, option_value, autoload) VALUES (?, ?, 1)");
            foreach ($settings as $setting) {
                $stmt->execute($setting);
            }

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function initializeLandingPageData(PDO $pdo, string $prefix = 'cms_'): bool
    {
        try {
            $headerData = json_encode([
                'title' => '365CMS – modernes CMS für Inhalte, Portale und Mitgliederbereiche',
                'subtitle' => 'Landing Pages, Redaktion, Plugins, Member Area und Design Editor in einem System',
                'description' => '365CMS vereint Content-Management, Design-Anpassung, Mitgliederfunktionen, System-Mails und modulare Business-Features in einer flexiblen Plattform für professionelle Websites und Portale.',
                'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
                'gitlab_url' => '',
                'version' => $this->getCmsVersion(),
                'logo' => '',
                'colors' => [
                    'hero_gradient_start' => '#1e293b',
                    'hero_gradient_end' => '#0f172a',
                    'hero_border' => '#3b82f6',
                    'hero_text' => '#ffffff',
                    'features_bg' => '#f8fafc',
                    'feature_card_bg' => '#ffffff',
                    'feature_card_hover' => '#3b82f6',
                    'primary_button' => '#3b82f6',
                ],
            ]);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('header', ?, 0, NOW(), NOW())");
            $stmt->execute([$headerData]);

            $features = [
                ['icon' => '🧩', 'title' => 'Seiten & Content', 'description' => 'Erstelle Seiten, Beiträge, Landing Pages und strukturierte Inhalte zentral im CMS.'],
                ['icon' => '🎨', 'title' => 'Design Editor', 'description' => 'Farben, Layouts, Header, Footer und Theme-Bereiche ohne Code anpassen.'],
                ['icon' => '🔌', 'title' => 'Plugin-Ökosystem', 'description' => 'Unternehmen, Events, Experten, Jobs, Feeds und weitere Module flexibel ergänzen.'],
                ['icon' => '👤', 'title' => 'Mitgliederbereich', 'description' => 'Dashboard, Profil, Sicherheit, Benachrichtigungen und persönliche Bereiche integriert.'],
                ['icon' => '🛡️', 'title' => 'Rollen & Sicherheit', 'description' => 'Granulare Rechte, CSRF-Schutz, sichere Authentifizierung und moderne Security-Bausteine.'],
                ['icon' => '🖼️', 'title' => 'Medienverwaltung', 'description' => 'Bilder, Dokumente, Uploads und Assets komfortabel organisieren und bereitstellen.'],
                ['icon' => '✉️', 'title' => 'Mail & Zustellung', 'description' => 'SMTP, MIME, OAuth/XOAuth2 und Systemmails für zuverlässige Kommunikation.'],
                ['icon' => '🌐', 'title' => 'SEO & Sichtbarkeit', 'description' => 'Meta-Daten, Redirects, saubere URLs und Suchmaschinenfreundlichkeit ab Werk.'],
                ['icon' => '📣', 'title' => 'Kontakt & Leads', 'description' => 'Formulare, Newsletter, Anfragen und automatisierte Benachrichtigungen bündeln.'],
                ['icon' => '⚙️', 'title' => 'Cron & Automationen', 'description' => 'Hintergrundjobs, Worker und geplante Aufgaben für wiederkehrende Prozesse.'],
                ['icon' => '🚀', 'title' => 'Performance', 'description' => 'Saubere Assets, optimierte Auslieferung und schnelle Oberflächen für den Alltag.'],
                ['icon' => '🧠', 'title' => 'Themes & Hooks', 'description' => 'Customizer, Hooks und Erweiterungspunkte für individuelle 365CMS-Lösungen.'],
            ];
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())");
            foreach ($features as $index => $feature) {
                $stmt->execute([json_encode($feature), $index + 1]);
            }

            $contentData = json_encode(['content_type' => 'features', 'content_text' => '', 'posts_count' => 5]);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('content', ?, 50, NOW(), NOW())");
            $stmt->execute([$contentData]);

            $footerData = json_encode([
                'content' => '<p><strong>365CMS</strong> verbindet Content-Management, Design Editor, Mitgliederbereich und modulare Business-Features in einer modernen Plattform.</p><p>Ideal für Unternehmensseiten, Portale, Netzwerke, Events und redaktionelle Websites mit Wachstumspotenzial.</p>',
                'button_text' => 'Zum Login',
                'button_url' => '/login',
                'copyright' => '&copy; ' . date('Y') . ' 365CMS',
                'show_footer' => true,
            ]);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('footer', ?, 99, NOW(), NOW())");
            $stmt->execute([$footerData]);

            $designData = json_encode([
                'card_border_radius' => 18,
                'button_border_radius' => 12,
                'card_icon_layout' => 'top',
                'card_border_color' => '#e2e8f0',
                'card_border_width' => '1px',
                'card_shadow' => 'md',
                'feature_columns' => 'auto',
                'hero_padding' => 'md',
                'feature_padding' => 'md',
                'footer_bg' => '#0f172a',
                'footer_text_color' => '#cbd5e1',
                'content_section_bg' => '#ffffff',
            ]);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('design', ?, 90, NOW(), NOW())");
            $stmt->execute([$designData]);

            $settingsData = json_encode([
                'show_header' => true,
                'show_content' => true,
                'show_footer_section' => true,
                'landing_slug' => '',
                'maintenance_mode' => false,
            ]);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('settings', ?, 100, NOW(), NOW())");
            $stmt->execute([$settingsData]);

            return true;
        } catch (PDOException $e) {
            error_log('Landing Page Initialization Error: ' . $e->getMessage());
            return false;
        }
    }
}
