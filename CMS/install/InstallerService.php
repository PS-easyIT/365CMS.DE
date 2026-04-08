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
    /**
     * @var array<string, string>
     */
    private const CONFIG_STRING_KEYS = [
        'created_at' => 'created_at',
        'db_host' => 'db_host',
        'db_name' => 'db_name',
        'db_user' => 'db_user',
        'db_pass' => 'db_pass',
        'db_charset' => 'db_charset',
        'db_prefix' => 'db_prefix',
        'auth_key' => 'auth_key',
        'secure_auth_key' => 'secure_auth_key',
        'nonce_key' => 'nonce_key',
        'site_name' => 'site_name',
        'site_url' => 'site_url',
        'admin_email' => 'admin_email',
        'default_theme' => 'default_theme',
        'cms_https_redirect_strategy' => 'cms_https_redirect_strategy',
        'cms_hsts_mode' => 'cms_hsts_mode',
        'ldap_host' => 'ldap_host',
        'ldap_base_dn' => 'ldap_base_dn',
        'ldap_username' => 'ldap_username',
        'ldap_password' => 'ldap_password',
        'ldap_filter' => 'ldap_filter',
        'ldap_default_role' => 'ldap_default_role',
        'jwt_secret' => 'jwt_secret',
        'jwt_issuer' => 'jwt_issuer',
        'smtp_host' => 'smtp_host',
        'smtp_user' => 'smtp_user',
        'smtp_pass' => 'smtp_pass',
        'smtp_encryption' => 'smtp_encryption',
        'smtp_from_email' => 'smtp_from_email',
        'smtp_from_name' => 'smtp_from_name',
    ];

    /**
     * @var array<string, string>
     */
    private const CONFIG_INT_KEYS = [
        'ldap_port' => 'ldap_port',
        'jwt_ttl' => 'jwt_ttl',
        'smtp_port' => 'smtp_port',
        'sessions_lifetime' => 'sessions_lifetime',
        'max_login_attempts' => 'max_login_attempts',
        'login_timeout' => 'login_timeout',
        'cms_hsts_max_age' => 'cms_hsts_max_age',
    ];

    /**
     * @var array<string, string>
     */
    private const CONFIG_BOOL_KEYS = [
        'debug_mode' => 'debug_mode',
        'ldap_use_ssl' => 'ldap_use_ssl',
        'ldap_use_tls' => 'ldap_use_tls',
    ];

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

        $stringConstants = [
            'DB_HOST' => 'db_host',
            'DB_NAME' => 'db_name',
            'DB_USER' => 'db_user',
            'DB_PASS' => 'db_pass',
            'DB_CHARSET' => 'db_charset',
            'DB_PREFIX' => 'db_prefix',
            'SITE_NAME' => 'site_name',
            'ADMIN_EMAIL' => 'admin_email',
            'SITE_URL' => 'site_url',
            'AUTH_KEY' => 'auth_key',
            'SECURE_AUTH_KEY' => 'secure_auth_key',
            'NONCE_KEY' => 'nonce_key',
            'DEFAULT_THEME' => 'default_theme',
            'CMS_HTTPS_REDIRECT_STRATEGY' => 'cms_https_redirect_strategy',
            'CMS_HSTS_MODE' => 'cms_hsts_mode',
            'LDAP_HOST' => 'ldap_host',
            'LDAP_BASE_DN' => 'ldap_base_dn',
            'LDAP_USERNAME' => 'ldap_username',
            'LDAP_PASSWORD' => 'ldap_password',
            'LDAP_FILTER' => 'ldap_filter',
            'LDAP_DEFAULT_ROLE' => 'ldap_default_role',
            'JWT_SECRET' => 'jwt_secret',
            'JWT_ISSUER' => 'jwt_issuer',
            'SMTP_HOST' => 'smtp_host',
            'SMTP_USER' => 'smtp_user',
            'SMTP_PASS' => 'smtp_pass',
            'SMTP_ENCRYPTION' => 'smtp_encryption',
            'SMTP_FROM_EMAIL' => 'smtp_from_email',
            'SMTP_FROM_NAME' => 'smtp_from_name',
        ];

        foreach ($stringConstants as $constant => $key) {
            $value = $this->extractDefinedValue($content, $constant);
            if (!is_string($value)) {
                continue;
            }

            if ($key === 'db_host') {
                $config[$key] = $value;
                continue;
            }

            if ($key === 'db_pass') {
                $config[$key] = str_contains($value, 'YOUR_') ? '' : $value;
                continue;
            }

            if (in_array($key, ['auth_key', 'secure_auth_key', 'nonce_key'], true)) {
                $config[$key] = str_contains($value, 'REPLACE_VIA_INSTALLER') ? '' : $value;
                continue;
            }

            $cleanedValue = $clean($value);
            if ($cleanedValue !== '') {
                $config[$key] = $cleanedValue;
            }
        }

        foreach (['CMS_DEBUG' => 'debug_mode', 'LDAP_USE_SSL' => 'ldap_use_ssl', 'LDAP_USE_TLS' => 'ldap_use_tls'] as $constant => $key) {
            $value = $this->extractDefinedValue($content, $constant);
            if (is_bool($value)) {
                $config[$key] = $value;
            }
        }

        foreach ([
            'LDAP_PORT' => 'ldap_port',
            'JWT_TTL' => 'jwt_ttl',
            'SMTP_PORT' => 'smtp_port',
            'SESSIONS_LIFETIME' => 'sessions_lifetime',
            'MAX_LOGIN_ATTEMPTS' => 'max_login_attempts',
            'LOGIN_TIMEOUT' => 'login_timeout',
            'CMS_HSTS_MAX_AGE' => 'cms_hsts_max_age',
        ] as $constant => $key) {
            $value = $this->extractDefinedValue($content, $constant);
            if (is_int($value)) {
                $config[$key] = $value;
            }
        }

        if (!isset($config['db_prefix']) || $config['db_prefix'] === '') {
            $config['db_prefix'] = 'cms_';
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

        $content = $this->buildConfigContent($data);

        if (file_put_contents($configPath, $content) === false) {
            return 'Fehler beim Schreiben von config/app.php';
        }

        $stub = "<?php\n/**\n * CMS Configuration Stub\n *\n * C-02: Eigentliche Konfiguration in config/app.php (via .htaccess geschützt).\n * Automatisch generiert durch CMS Installer.\n *\n * @package 365CMS\n */\n\ndeclare(strict_types=1);\n\nif (!defined('ABSPATH')) {\n    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);\n}\n\nrequire_once __DIR__ . '/config/app.php';\n";
        file_put_contents($stubPath, $stub);

        return true;
    }

    public function updateConfigFile(array $existing, array $updates): bool|string
    {
        $data = array_merge($existing, $updates, [
            'created_at' => date('Y-m-d H:i:s'),
            'db_host' => $existing['db_host'] ?? '',
            'db_name' => $existing['db_name'] ?? '',
            'db_user' => $existing['db_user'] ?? '',
            'db_pass' => $existing['db_pass'] ?? '',
            'db_prefix' => $existing['db_prefix'] ?? 'cms_',
            'auth_key' => $existing['auth_key'] ?? '',
            'secure_auth_key' => $existing['secure_auth_key'] ?? '',
            'nonce_key' => $existing['nonce_key'] ?? '',
            'site_name' => $updates['site_name'] ?? $existing['site_name'] ?? 'IT Expert Network',
            'site_url' => $updates['site_url'] ?? $existing['site_url'] ?? '',
            'admin_email' => $updates['admin_email'] ?? $existing['admin_email'] ?? '',
        ]);

        return $this->createConfigFile($data);
    }

    /**
     * @param mixed $value
     */
    private function normalizeBoolean(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }

            if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return $default;
    }

    /**
     * @param mixed $value
     */
    private function normalizeInteger(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) trim($value);
        }

        return $default;
    }

    /**
     * @param mixed $value
     */
    private function normalizeString(mixed $value, string $default = ''): string
    {
        if (!is_scalar($value)) {
            return $default;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : $default;
    }

    /**
     * @param mixed $value
     */
    private function exportPhpValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value) . "'";
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfigDefaults(): array
    {
        return [
            'created_at' => date('Y-m-d H:i:s'),
            'debug_mode' => false,
            'db_host' => 'localhost',
            'db_name' => '',
            'db_user' => '',
            'db_pass' => '',
            'db_charset' => 'utf8mb4',
            'db_prefix' => 'cms_',
            'auth_key' => '',
            'secure_auth_key' => '',
            'nonce_key' => '',
            'site_name' => 'IT Expert Network',
            'site_url' => '',
            'admin_email' => '',
            'default_theme' => 'cms-default',
            'sessions_lifetime' => 3600 * 2,
            'max_login_attempts' => 5,
            'login_timeout' => 300,
            'cms_https_redirect_strategy' => 'upstream',
            'cms_hsts_mode' => 'https-only',
            'cms_hsts_max_age' => 31536000,
            'ldap_host' => '',
            'ldap_port' => 389,
            'ldap_base_dn' => '',
            'ldap_username' => '',
            'ldap_password' => '',
            'ldap_use_ssl' => false,
            'ldap_use_tls' => true,
            'ldap_filter' => '',
            'ldap_default_role' => 'member',
            'jwt_secret' => '',
            'jwt_ttl' => 3600,
            'jwt_issuer' => '',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_encryption' => 'tls',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeConfigData(array $data): array
    {
        $normalized = $this->getConfigDefaults();

        foreach (self::CONFIG_STRING_KEYS as $key => $defaultKey) {
            $normalized[$key] = $this->normalizeString($data[$key] ?? null, (string) $normalized[$defaultKey]);
        }

        foreach (self::CONFIG_INT_KEYS as $key => $defaultKey) {
            $normalized[$key] = $this->normalizeInteger($data[$key] ?? null, (int) $normalized[$defaultKey]);
        }

        foreach (self::CONFIG_BOOL_KEYS as $key => $defaultKey) {
            $normalized[$key] = $this->normalizeBoolean($data[$key] ?? null, (bool) $normalized[$defaultKey]);
        }

        $normalized['auth_key'] = $normalized['auth_key'] !== '' ? $normalized['auth_key'] : $this->generateSecurityKey();
        $normalized['secure_auth_key'] = $normalized['secure_auth_key'] !== '' ? $normalized['secure_auth_key'] : $this->generateSecurityKey();
        $normalized['nonce_key'] = $normalized['nonce_key'] !== '' ? $normalized['nonce_key'] : $this->generateSecurityKey();
        $normalized['jwt_issuer'] = $this->normalizeString($data['jwt_issuer'] ?? null, $normalized['site_url']);
        $normalized['smtp_from_email'] = $this->normalizeString($data['smtp_from_email'] ?? null, $normalized['admin_email']);
        $normalized['smtp_from_name'] = $this->normalizeString($data['smtp_from_name'] ?? null, $normalized['site_name']);

        return $normalized;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildConfigContent(array $data): string
    {
        $data = $this->normalizeConfigData($data);
        $jwtIssuerValue = $data['jwt_issuer'] === $data['site_url'] ? 'SITE_URL' : $this->exportPhpValue($data['jwt_issuer']);
        $smtpFromEmailValue = $data['smtp_from_email'] === $data['admin_email'] ? 'ADMIN_EMAIL' : $this->exportPhpValue($data['smtp_from_email']);
        $smtpFromNameValue = $data['smtp_from_name'] === $data['site_name'] ? 'SITE_NAME' : $this->exportPhpValue($data['smtp_from_name']);

        $lines = [
            '<?php',
            '/**',
            ' * CMS Application Configuration',
            ' *',
            ' * Automatisch erstellt am ' . $data['created_at'],
            ' * C-01: Security-Keys via random_bytes() generiert – NICHT in VCS einchecken!',
            ' * C-02: Konfiguration in config/ isoliert (via .htaccess geschützt)',
            ' *',
            ' * @package 365CMS',
            ' */',
            '',
            'declare(strict_types=1);',
            '',
            'if (!defined(\'ABSPATH\')) {',
            '    define(\'ABSPATH\', dirname(__DIR__) . DIRECTORY_SEPARATOR);',
            '}',
            '',
            '// ─── Debug-Modus (in Produktion auf false setzen!) ─────────────────────────',
            'define(\'CMS_DEBUG\', ' . $this->exportPhpValue($data['debug_mode']) . ');',
            '',
            '// ─── Datenbank-Konfiguration ───────────────────────────────────────────────',
            'define(\'DB_HOST\',    ' . $this->exportPhpValue($data['db_host']) . ');',
            'define(\'DB_NAME\',    ' . $this->exportPhpValue($data['db_name']) . ');',
            'define(\'DB_USER\',    ' . $this->exportPhpValue($data['db_user']) . ');',
            'define(\'DB_PASS\',    ' . $this->exportPhpValue($data['db_pass']) . ');',
            'define(\'DB_CHARSET\', ' . $this->exportPhpValue($data['db_charset']) . ');',
            'define(\'DB_PREFIX\',  ' . $this->exportPhpValue($data['db_prefix']) . ');',
            '',
            '// ─── Security-Keys (via random_bytes – NICHT manuell setzen!) ─────────────',
            'define(\'AUTH_KEY\',        ' . $this->exportPhpValue($data['auth_key']) . ');',
            'define(\'SECURE_AUTH_KEY\', ' . $this->exportPhpValue($data['secure_auth_key']) . ');',
            'define(\'NONCE_KEY\',       ' . $this->exportPhpValue($data['nonce_key']) . ');',
            '',
            '// ─── Site-Konfiguration ────────────────────────────────────────────────────',
            'define(\'SITE_NAME\',    ' . $this->exportPhpValue($data['site_name']) . ');',
            'define(\'SITE_URL\',     ' . $this->exportPhpValue($data['site_url']) . ');',
            'define(\'ADMIN_EMAIL\',  ' . $this->exportPhpValue($data['admin_email']) . ');',
            '',
            'require_once ABSPATH . \'core/Version.php\';',
            'define(\'CMS_VERSION\',  \\CMS\\Version::CURRENT);',
            '',
            '// ─── Pfade ─────────────────────────────────────────────────────────────────',
            'define(\'CORE_PATH\',   ABSPATH . \'core/\');',
            'define(\'THEME_PATH\',  ABSPATH . \'themes/\');',
            'define(\'PLUGIN_PATH\', ABSPATH . \'plugins/\');',
            'define(\'UPLOAD_PATH\', ABSPATH . \'uploads/\');',
            'define(\'ASSETS_PATH\', ABSPATH . \'assets/\');',
            '',
            '$cmsLogDir = ABSPATH . \'logs\' . DIRECTORY_SEPARATOR;',
            'if (!is_dir($cmsLogDir)) {',
            '    @mkdir($cmsLogDir, 0755, true);',
            '}',
            '',
            'defined(\'LOG_PATH\') || define(\'LOG_PATH\', $cmsLogDir);',
            'defined(\'CMS_ERROR_LOG\') || define(\'CMS_ERROR_LOG\', rtrim(LOG_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . \'error.log\');',
            '',
            '// ─── URLs ──────────────────────────────────────────────────────────────────',
            'define(\'SITE_URL_PATH\', \'/\');',
            'define(\'ASSETS_URL\',    SITE_URL . \'/assets\');',
            'define(\'UPLOAD_URL\',    SITE_URL . \'/uploads\');',
            '',
            '// ─── System-Einstellungen ──────────────────────────────────────────────────',
            'define(\'DEFAULT_THEME\',      ' . $this->exportPhpValue($data['default_theme']) . ');',
            'define(\'SESSIONS_LIFETIME\',  ' . $this->exportPhpValue($data['sessions_lifetime']) . ');',
            'define(\'MAX_LOGIN_ATTEMPTS\', ' . $this->exportPhpValue($data['max_login_attempts']) . ');',
            'define(\'LOGIN_TIMEOUT\',      ' . $this->exportPhpValue($data['login_timeout']) . ');',
            'defined(\'CMS_HTTPS_REDIRECT_STRATEGY\') || define(\'CMS_HTTPS_REDIRECT_STRATEGY\', ' . $this->exportPhpValue($data['cms_https_redirect_strategy']) . ');',
            'defined(\'CMS_HSTS_MODE\') || define(\'CMS_HSTS_MODE\', ' . $this->exportPhpValue($data['cms_hsts_mode']) . ');',
            'defined(\'CMS_HSTS_MAX_AGE\') || define(\'CMS_HSTS_MAX_AGE\', ' . $this->exportPhpValue($data['cms_hsts_max_age']) . ');',
            '',
            '// ─── Fehler-Reporting ──────────────────────────────────────────────────────',
            'if (CMS_DEBUG) {',
            '    error_reporting(E_ALL);',
            '    ini_set(\'display_errors\', \'1\');',
            '    ini_set(\'log_errors\', \'1\');',
            '    ini_set(\'error_log\', CMS_ERROR_LOG);',
            '} else {',
            '    error_reporting(0);',
            '    ini_set(\'display_errors\', \'0\');',
            '    ini_set(\'log_errors\', \'0\');',
            '}',
            '',
            '// ─── Zeitzone ──────────────────────────────────────────────────────────────',
            'date_default_timezone_set(\'Europe/Berlin\');',
            '',
            '// ─── LDAP-Konfiguration (optional) ─────────────────────────────────────────',
            'defined(\'LDAP_HOST\')         || define(\'LDAP_HOST\',         ' . $this->exportPhpValue($data['ldap_host']) . ');',
            'defined(\'LDAP_PORT\')         || define(\'LDAP_PORT\',         ' . $this->exportPhpValue($data['ldap_port']) . ');',
            'defined(\'LDAP_BASE_DN\')      || define(\'LDAP_BASE_DN\',      ' . $this->exportPhpValue($data['ldap_base_dn']) . ');',
            'defined(\'LDAP_USERNAME\')     || define(\'LDAP_USERNAME\',     ' . $this->exportPhpValue($data['ldap_username']) . ');',
            'defined(\'LDAP_PASSWORD\')     || define(\'LDAP_PASSWORD\',     ' . $this->exportPhpValue($data['ldap_password']) . ');',
            'defined(\'LDAP_USE_SSL\')      || define(\'LDAP_USE_SSL\',      ' . $this->exportPhpValue($data['ldap_use_ssl']) . ');',
            'defined(\'LDAP_USE_TLS\')      || define(\'LDAP_USE_TLS\',      ' . $this->exportPhpValue($data['ldap_use_tls']) . ');',
            'defined(\'LDAP_FILTER\')       || define(\'LDAP_FILTER\',       ' . $this->exportPhpValue($data['ldap_filter']) . ');',
            'defined(\'LDAP_DEFAULT_ROLE\') || define(\'LDAP_DEFAULT_ROLE\', ' . $this->exportPhpValue($data['ldap_default_role']) . ');',
            '',
            '// ─── JWT-Konfiguration (API-Authentifizierung) ────────────────────────────',
            'defined(\'JWT_SECRET\')        || define(\'JWT_SECRET\',  ' . $this->exportPhpValue($data['jwt_secret']) . ');',
            'defined(\'JWT_TTL\')           || define(\'JWT_TTL\',     ' . $this->exportPhpValue($data['jwt_ttl']) . ');',
            'defined(\'JWT_ISSUER\')        || define(\'JWT_ISSUER\',  ' . $jwtIssuerValue . ');',
            '',
            '// ─── E-Mail / SMTP-Konfiguration ──────────────────────────────────────────',
            'defined(\'SMTP_HOST\')       || define(\'SMTP_HOST\',       ' . $this->exportPhpValue($data['smtp_host']) . ');',
            'defined(\'SMTP_PORT\')       || define(\'SMTP_PORT\',       ' . $this->exportPhpValue($data['smtp_port']) . ');',
            'defined(\'SMTP_USER\')       || define(\'SMTP_USER\',       ' . $this->exportPhpValue($data['smtp_user']) . ');',
            'defined(\'SMTP_PASS\')       || define(\'SMTP_PASS\',       ' . $this->exportPhpValue($data['smtp_pass']) . ');',
            'defined(\'SMTP_ENCRYPTION\') || define(\'SMTP_ENCRYPTION\', ' . $this->exportPhpValue($data['smtp_encryption']) . ');',
            'defined(\'SMTP_FROM_EMAIL\') || define(\'SMTP_FROM_EMAIL\', ' . $smtpFromEmailValue . ');',
            'defined(\'SMTP_FROM_NAME\')  || define(\'SMTP_FROM_NAME\',  ' . $smtpFromNameValue . ');',
        ];

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return mixed
     */
    private function extractDefinedValue(string $content, string $constant): mixed
    {
        $pattern = '~(?:defined\(\s*[\'\"]' . preg_quote($constant, '~') . '[\'\"]\s*\)\s*\|\|\s*)?define\(\s*[\'\"]' . preg_quote($constant, '~') . '[\'\"]\s*,\s*(.+?)\s*\);\s*(?://.*)?$~m';

        if (preg_match($pattern, $content, $matches) !== 1) {
            return null;
        }

        return $this->parsePhpLiteral(trim($matches[1]));
    }

    /**
     * @return mixed
     */
    private function parsePhpLiteral(string $expression): mixed
    {
        if ($expression === '') {
            return null;
        }

        $firstChar = $expression[0];
        $lastChar = $expression[strlen($expression) - 1];

        if ($firstChar === '\'' && $lastChar === '\'') {
            $inner = substr($expression, 1, -1);

            return str_replace(['\\\\', '\\' . '\''], ['\\', '\''], $inner);
        }

        if ($firstChar === '"' && $lastChar === '"') {
            return stripcslashes(substr($expression, 1, -1));
        }

        if ($expression === 'true') {
            return true;
        }

        if ($expression === 'false') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $expression) === 1) {
            return (int) $expression;
        }

        return null;
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
                'badge_text' => $this->getCmsVersion(),
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
                ['icon' => '🧩', 'title' => 'Seiten, Beiträge & Landing Pages', 'description' => 'Verwalte klassische Seiten, Blogbeiträge, Hero-Bereiche und eigenständige Landing Pages in einem durchgängigen Workflow.'],
                ['icon' => '🧱', 'title' => 'Editor.js & Content-Blöcke', 'description' => 'Nutze strukturierte Inhalte mit modernen Blöcken wie Medien+Text, Galerien, Tabellen, Accordions und weiteren Editor.js-Tools.'],
                ['icon' => '🎨', 'title' => 'Theme-Customizer & Design', 'description' => 'Passe Farben, Layouts, Header, Footer, Kartenstile und Theme-Bereiche ohne Code direkt im Admin an.'],
                ['icon' => '🖼️', 'title' => 'Medienbibliothek & Uploads', 'description' => 'Organisiere Bilder, Dateien, WebP-Assets und Uploads zentral mit komfortabler Bibliothek und Picker-Workflows.'],
                ['icon' => '🔌', 'title' => 'Plugin-Ökosystem', 'description' => 'Erweitere 365CMS flexibel um Unternehmen, Events, Experten, Jobs, Feeds, Formulare und weitere Business-Module.'],
                ['icon' => '👤', 'title' => 'Mitgliederbereich', 'description' => 'Biete Dashboard, Profile, Favoriten, Benachrichtigungen und persönliche Bereiche für registrierte Nutzer direkt im System an.'],
                ['icon' => '🔐', 'title' => 'Rollen, Passkeys & 2FA', 'description' => 'Arbeite mit granularen Rechten, sicherer Authentifizierung, Passkeys, TOTP und zusätzlicher Zugriffshärtung.'],
                ['icon' => '🌐', 'title' => 'SEO, Sitemap & IndexNow', 'description' => 'Steuere Meta-Daten, Redirects, Sitemaps, technische SEO-Prüfungen und IndexNow direkt im Core.'],
                ['icon' => '🔎', 'title' => 'Suche & Indizierung', 'description' => 'Nutze Volltextsuche, TNTSearch-Indizes und aktualisierte Suchdaten für Seiten, Beiträge und mehrsprachige Inhalte.'],
                ['icon' => '✉️', 'title' => 'Mail Queue & Zustellung', 'description' => 'Versende System- und Projektmails zuverlässig über Queue, SMTP, MIME sowie moderne OAuth- und Retry-Pfade.'],
                ['icon' => '📣', 'title' => 'Formulare, Leads & Kontakt', 'description' => 'Bündele Kontaktanfragen, Newsletter-Workflows, Lead-Erfassung und automatische Benachrichtigungen an einer Stelle.'],
                ['icon' => '⚙️', 'title' => 'Cron Runner & Automationen', 'description' => 'Starte Cron-Aufgaben, Worker und geplante Prozesse direkt aus dem Admin oder automatisiert im Hintergrund.'],
                ['icon' => '🚀', 'title' => 'Performance & Cache', 'description' => 'Verbessere Auslieferung, Assets, Medien, Cache-Verhalten und Reaktionszeiten für schnelle Frontends.'],
                ['icon' => '📊', 'title' => 'Monitoring & Health Checks', 'description' => 'Überwache Cron, Antwortzeiten, Speicher, Disk-Usage, Health-Checks und Systemzustände direkt im Dashboard.'],
                ['icon' => '♻️', 'title' => 'Updates & Backups', 'description' => 'Halte Core, Themes und Plugins aktuell und kombiniere das mit Backup- und Wiederherstellungsprozessen.'],
                ['icon' => '🧾', 'title' => 'DSGVO & Legal Sites', 'description' => 'Pflege Datenschutz- und Rechtsseiten, Consent, Datenexporte sowie Löschprozesse systemweit nachvollziehbar.'],
                ['icon' => '🧭', 'title' => 'Menüs, Redirects & Navigation', 'description' => 'Verwalte Menüpositionen, slugbasierte Links, Weiterleitungen und Navigationsstrukturen zentral im Admin.'],
                ['icon' => '🧠', 'title' => 'Themes, Hooks & APIs', 'description' => 'Setze auf Customizer, Hooks, Services und dokumentierte Erweiterungspunkte für individuelle 365CMS-Lösungen.'],
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
                'button_url' => '/cms-login',
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
