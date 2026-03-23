<?php
declare(strict_types=1);

/**
 * CMS Installation Script
 * 
 * Intelligenter Installer der:
 * - Domain automatisch erkennt
 * - Alle Konfigurationswerte abfragt
 * - config/app.php automatisch erstellt (C-01/C-02)
 * - Datenbank-Tabellen erstellt
 * - Admin-User anlegt
 * 
 * WICHTIG: Nach erfolgreicher Installation LÖSCHEN!
 * 
 * @package 365CMS
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/core/Contracts/CacheInterface.php';
    require_once __DIR__ . '/core/CacheManager.php';

    \CMS\CacheManager::instance()->sendResponseHeaders('private');
}

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

function getCmsVersion(): string {
    require_once __DIR__ . '/core/Version.php';

    return \CMS\Version::CURRENT;
}

function getInstallerLockPath(): string {
    return __DIR__ . '/config/install.lock';
}

function hasInstallerLock(): bool {
    return is_file(getInstallerLockPath());
}

function writeInstallerLockFile(?array $config = null): void {
    $lockPath = getInstallerLockPath();
    $lockDir  = dirname($lockPath);

    if (!is_dir($lockDir) && !mkdir($lockDir, 0755, true) && !is_dir($lockDir)) {
        return;
    }

    $payload = [
        'locked'     => true,
        'created_at' => date(DATE_ATOM),
        'site_url'   => trim((string) ($config['site_url'] ?? '')),
    ];

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || $json === '') {
        return;
    }

    file_put_contents($lockPath, $json);
}

function canAccessInstalledInstaller(): bool {
    if (PHP_SAPI === 'cli') {
        return true;
    }

    try {
        require_once __DIR__ . '/config.php';
        require_once __DIR__ . '/core/autoload.php';

        return class_exists('CMS\\Auth') && \CMS\Auth::isAdmin();
    } catch (Throwable $e) {
        error_log('install.php admin-guard failed: ' . $e->getMessage());
        return false;
    }
}

function denyInstalledInstallerAccess(): void {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Installer deaktiviert</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .card {
                max-width: 720px;
                width: 100%;
                background: white;
                border-radius: 16px;
                padding: 3rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            }
            h1 {
                color: #991b1b;
                font-size: 2rem;
                margin-bottom: 1rem;
            }
            p {
                color: #475569;
                margin-bottom: 1rem;
                line-height: 1.6;
            }
            .hint {
                background: #f8fafc;
                border-left: 4px solid #2563eb;
                padding: 1rem;
                border-radius: 8px;
                color: #1e293b;
            }
            code {
                background: #eff6ff;
                color: #1d4ed8;
                padding: 0.1rem 0.35rem;
                border-radius: 4px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>🔒 Installer deaktiviert</h1>
            <p>Für bestehende Installationen ist <code>install.php</code> öffentlich gesperrt.</p>
            <p>Der Zugriff ist nur noch für bereits angemeldete Administratoren oder über die CLI vorgesehen.</p>
            <div class="hint">
                Wenn Wartung nötig ist, melden Sie sich zuerst im Admin-Bereich an oder entfernen Sie den Installer vollständig aus dem öffentlichen Deployment.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function parseExistingConfig(): array|bool {
    // C-02: config/app.php ist der primäre Speicherort (isoliert via .htaccess)
    $configPath = __DIR__ . '/config/app.php';
    if (!file_exists($configPath)) {
        // Fallback: Root config.php (Stub oder Legacy-Installation)
        $configPath = __DIR__ . '/config.php';
    }

    if (!file_exists($configPath)) {
        return false;
    }

    $content = file_get_contents($configPath);
    $config  = [];

    /**
     * Bereinigt bekannte Platzhalter-Werte aus dem Installer-Template.
     * Gibt einen leeren String zurück wenn der Wert noch nicht konfiguriert wurde.
     */
    $clean = static function (string $val): string {
        if (
            str_contains($val, 'YOUR_')
            || str_contains($val, 'REPLACE_VIA_INSTALLER')
            || str_contains($val, 'example.com')
        ) {
            return '';
        }
        return $val;
    };

    // Parse DB-Credentials
    if (preg_match("/define\('DB_HOST',\s*'([^']+)'\);/", $content, $m)) {
        // 'localhost' ist ein wirksamer Wert – kein Platzhalter-Check nötig
        $config['db_host'] = $m[1];
    }
    if (preg_match("/define\('DB_NAME',\s*'([^']+)'\);/", $content, $m)) {
        $config['db_name'] = $clean($m[1]);
    }
    if (preg_match("/define\('DB_USER',\s*'([^']+)'\);/", $content, $m)) {
        $config['db_user'] = $clean($m[1]);
    }
    if (preg_match("/define\('DB_PASS',\s*'([^']+)'\);/", $content, $m)) {
        // Passwort darf leer sein → kein Platzhalter-Check, nur REPLACE_-Strings entfernen
        $config['db_pass'] = str_contains($m[1], 'YOUR_') ? '' : $m[1];
    }
    if (preg_match("/define\('DB_PREFIX',\s*'([^']+)'\);/", $content, $m)) {
        $config['db_prefix'] = !empty($m[1]) ? $m[1] : 'cms_';
    }

    // Parse Site-Info
    if (preg_match("/define\('SITE_NAME',\s*'([^']+)'\);/", $content, $m)) {
        $config['site_name'] = $clean($m[1]);
    }
    if (preg_match("/define\('ADMIN_EMAIL',\s*'([^']+)'\);/", $content, $m)) {
        $config['admin_email'] = $clean($m[1]);
    }
    // SITE_URL: ersten Match nehmen (Datei könnte doppelten define haben)
    if (preg_match("/define\('SITE_URL',\s*'([^']+)'\);/", $content, $m)) {
        $config['site_url'] = $clean($m[1]);
    }
    if (preg_match("/define\('CMS_DEBUG',\s*(true|false)\);/", $content, $m)) {
        $config['debug_mode'] = $m[1];
    }

    // Security-Keys beibehalten (Update-Modus – kein Neuerstellen erforderlich)
    if (preg_match("/define\('AUTH_KEY',\s*'([^']+)'\);/", $content, $m)) {
        $config['auth_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
    }
    if (preg_match("/define\('SECURE_AUTH_KEY',\s*'([^']+)'\);/", $content, $m)) {
        $config['secure_auth_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
    }
    if (preg_match("/define\('NONCE_KEY',\s*'([^']+)'\);/", $content, $m)) {
        $config['nonce_key'] = str_contains($m[1], 'REPLACE_VIA_INSTALLER') ? '' : $m[1];
    }

    // Nur als "echte Installation" werten wenn DB-Username UND DB-Name vorhanden sind.
    // 'localhost' allein reicht nicht – das steht auch im Template.
    return (!empty($config['db_user']) && !empty($config['db_name']))
        ? $config
        : false;
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
    // C-02: Eigentliche Konfiguration wird in config/app.php geschrieben (web-isoliert)
    $configDir  = __DIR__ . '/config';
    $configPath = $configDir . '/app.php';
    $htaccessPath = $configDir . '/.htaccess';
    $stubPath   = __DIR__ . '/config.php';

    // config/-Verzeichnis anlegen wenn nötig
    if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0755, true)) {
            return 'Fehler: config/-Verzeichnis konnte nicht erstellt werden';
        }
    }

    // config/.htaccess schreiben (verhindert direkten Web-Zugriff)
    $htaccessContent = "# Auto-generated by CMS Installer (C-02)\n"
        . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Deny from all\n</IfModule>\n";
    file_put_contents($htaccessPath, $htaccessContent);

    // Backup existierende config/app.php
    if (file_exists($configPath)) {
        $backupPath = $configDir . '/app.php.backup.' . date('Y-m-d_H-i-s');
        copy($configPath, $backupPath);
    }

    // C-01: Security-Keys wurden bereits via generateSecurityKey() (random_bytes) erzeugt
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
    // __DIR__ = CMS/config → dirname(__DIR__) = CMS/
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// ─── Debug-Modus ───────────────────────────────────────────────────────────
define('CMS_DEBUG', {$data['debug_mode']});

// ─── Datenbank-Konfiguration ───────────────────────────────────────────────
define('DB_HOST',    '{$data['db_host']}');
define('DB_NAME',    '{$data['db_name']}');
define('DB_USER',    '{$data['db_user']}');
define('DB_PASS',    '{$data['db_pass']}');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  '{$data['db_prefix']}');

// ─── Security-Keys (via random_bytes – NICHT manuell setzen!) ─────────────
define('AUTH_KEY',        '{$data['auth_key']}');
define('SECURE_AUTH_KEY', '{$data['secure_auth_key']}');
define('NONCE_KEY',       '{$data['nonce_key']}');

// ─── Site-Konfiguration ────────────────────────────────────────────────────
define('SITE_NAME',   '{$data['site_name']}');
define('SITE_URL',    '{$data['site_url']}');
define('ADMIN_EMAIL', '{$data['admin_email']}');

require_once ABSPATH . 'core/Version.php';
define('CMS_VERSION', \CMS\Version::CURRENT);

// ─── Pfade ─────────────────────────────────────────────────────────────────
define('CORE_PATH',   ABSPATH . 'core/');
define('THEME_PATH',  ABSPATH . 'themes/');
define('PLUGIN_PATH', ABSPATH . 'plugins/');
define('UPLOAD_PATH', ABSPATH . 'uploads/');
define('ASSETS_PATH', ABSPATH . 'assets/');


$cmsLogDir = dirname(ABSPATH) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
if (!is_dir($cmsLogDir) && !@mkdir($cmsLogDir, 0755, true) && !is_dir($cmsLogDir)) {
    $cmsLogDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '365cms-logs' . DIRECTORY_SEPARATOR;
    if (!is_dir($cmsLogDir)) {
        @mkdir($cmsLogDir, 0755, true);
    }
}

defined('LOG_PATH') || define('LOG_PATH', $cmsLogDir);
defined('CMS_ERROR_LOG') || define('CMS_ERROR_LOG', rtrim(LOG_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'error.log');

// ─── URLs ──────────────────────────────────────────────────────────────────
define('SITE_URL_PATH', '/');
define('ASSETS_URL',    SITE_URL . '/assets');
define('UPLOAD_URL',    SITE_URL . '/uploads');

// ─── System-Einstellungen ──────────────────────────────────────────────────
define('DEFAULT_THEME',      'cms-default');
define('SESSIONS_LIFETIME',  3600 * 2);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT',      300);

// ─── Fehler-Reporting ──────────────────────────────────────────────────────
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

// ─── Zeitzone ──────────────────────────────────────────────────────────────
date_default_timezone_set('Europe/Berlin');
PHP;

    $written = file_put_contents($configPath, $content);

    if ($written === false) {
        return 'Fehler beim Schreiben von config/app.php';
    }

    // Stub config.php im CMS-Root schreiben / aktualisieren
    $stub = '<?php' . "\n"
        . '/**' . "\n"
        . ' * CMS Configuration Stub' . "\n"
        . ' *' . "\n"
        . ' * C-02: Eigentliche Konfiguration in config/app.php (via .htaccess geschützt).' . "\n"
        . ' * Automatisch generiert durch CMS Installer.' . "\n"
        . ' *' . "\n"
        . ' * @package 365CMS' . "\n"
        . ' */' . "\n\n"
        . "declare(strict_types=1);\n\n"
        . "if (!defined('ABSPATH')) {\n"
        . "    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);\n"
        . "}\n\n"
        . "require_once __DIR__ . '/config/app.php';\n";
    file_put_contents($stubPath, $stub);

    return true;
}

/**
 * Update-Modus: config/app.php neu schreiben und dabei
 * bestehende DB-Zugangsdaten sowie Security-Keys beibehalten.
 *
 * @param array $existing  Geparste Werte der bestehenden config/app.php
 * @param array $updates   Neue Site-Werte (site_name, site_url, admin_email, debug_mode)
 */
function updateConfigFile(array $existing, array $updates): bool|string {
    $data = [
        'created_at'      => date('Y-m-d H:i:s'),
        'debug_mode'      => $updates['debug_mode']      ?? $existing['debug_mode']      ?? 'false',
        'db_host'         => $existing['db_host'],
        'db_name'         => $existing['db_name'],
        'db_user'         => $existing['db_user'],
        'db_pass'         => $existing['db_pass'],
        'db_prefix'       => $existing['db_prefix']  ?? 'cms_',
        // Security-Keys aus bestehender Config übernehmen; nur neu erzeugen wenn nicht vorhanden
        'auth_key'        => $existing['auth_key']        ?? generateSecurityKey(),
        'secure_auth_key' => $existing['secure_auth_key'] ?? generateSecurityKey(),
        'nonce_key'       => $existing['nonce_key']       ?? generateSecurityKey(),
        'site_name'       => $updates['site_name']   ?? $existing['site_name']   ?? 'IT Expert Network',
        'site_url'        => $updates['site_url']    ?? $existing['site_url']    ?? '',
        'admin_email'     => $updates['admin_email'] ?? $existing['admin_email'] ?? '',
    ];
    return createConfigFile($data);
}

/**
 * Lädt die zentralen Schema-Definitionen aus dem SchemaManager.
 *
 * @return array<string,string>
 */
function getCentralSchemaQueries(string $prefix = 'cms_', string $charset = 'utf8mb4'): array {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
    }

    require_once __DIR__ . '/core/SchemaManager.php';

    return \CMS\SchemaManager::getSchemaQueries($prefix, $charset);
}

function clearSchemaManagerFlagFile(): void {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
    }

    require_once __DIR__ . '/core/SchemaManager.php';

    $flagFile = ABSPATH . 'cache/db_schema_' . \CMS\SchemaManager::SCHEMA_VERSION . '.flag';
    if (is_file($flagFile)) {
        unlink($flagFile);
    }
}

function createDatabaseTables(PDO $pdo, string $prefix = 'cms_'): array {
    $results = [];

    $tables = getCentralSchemaQueries($prefix);
    
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
        $displayName = ucfirst($username);
        $stmt = $pdo->prepare("INSERT INTO {$prefix}users (username, email, password, display_name, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')");
        $stmt->execute([$username, $email, $hash, $displayName]);
        
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
            'title' => '365CMS – modernes CMS für Inhalte, Portale und Mitgliederbereiche',
            'subtitle' => 'Landing Pages, Redaktion, Plugins, Member Area und Design Editor in einem System',
            'description' => '365CMS vereint Content-Management, Design-Anpassung, Mitgliederfunktionen, System-Mails und modulare Business-Features in einer flexiblen Plattform für professionelle Websites und Portale.',
            'github_url' => 'https://github.com/PS-easyIT/WordPress-365network',
            'gitlab_url' => '',
            'version' => getCmsVersion(),
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
            ['icon' => '🧠', 'title' => 'Themes & Hooks', 'description' => 'Customizer, Hooks und Erweiterungspunkte für individuelle 365CMS-Lösungen.']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO {$prefix}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())");
        
        foreach ($features as $index => $feature) {
            $featureData = json_encode($feature);
            $stmt->execute([$featureData, $index + 1]);
        }

        $contentData = json_encode([
            'content_type' => 'features',
            'content_text' => '',
            'posts_count' => 5,
        ]);
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

// Installation Step Handler
$step = $_POST['step'] ?? $_GET['step'] ?? 1;
$errors = [];

if (!defined('CMS_MIN_PHP_VERSION')) {
    define('CMS_MIN_PHP_VERSION', '8.4.0');
}

// Prüfe auf bestehende Installation
$existingConfig = parseExistingConfig();
$isReinstall = $existingConfig !== false;

if ($existingConfig !== false) {
    if (!hasInstallerLock()) {
        writeInstallerLockFile($existingConfig);
    }

    if (!isset($_SESSION['install_success']) && !canAccessInstalledInstaller()) {
        denyInstalledInstallerAccess();
    }
}

// Step 1: Willkommen & System-Check
if ($step == 1) {
    $autoUrl = autoDetectUrl();
    $phpVersion = PHP_VERSION;
    $requiredPhpVersion = CMS_MIN_PHP_VERSION;
    $phpCompatible = version_compare($phpVersion, $requiredPhpVersion, '>=');
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
                background: linear-gradient(135deg, #669fea 0%, #4b5fa2 100%);
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
                    <span class="check-icon <?php echo $phpCompatible ? 'success' : 'error'; ?>"><?php echo $phpCompatible ? '✓' : '✗'; ?></span>
                    <div>
                        <strong>PHP Version</strong><br>
                        <small>Version <?php echo $phpVersion; ?> (erforderlich: <?php echo htmlspecialchars($requiredPhpVersion, ENT_QUOTES, 'UTF-8'); ?>+)</small>
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
                        <small><?php echo $writePermission ? 'Verzeichnis ist beschreibbar (config/app.php kann geschrieben werden)' : 'KEINE Schreibrechte – config/app.php kann nicht erstellt werden!'; ?></small>
                    </div>
                </div>
                
                <?php if ($isReinstall): ?>
                <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                    <strong>⚠️ Bestehende Installation erkannt!</strong><br>
                    Vorhandene Konfiguration (<code>config/app.php</code>):<br>
                    <ul style="margin: 0.5rem 0 0.5rem 1.5rem;">
                        <li><strong>Datenbank:</strong> <?php echo htmlspecialchars($existingConfig['db_name'] ?? 'N/A'); ?> @ <?php echo htmlspecialchars($existingConfig['db_host'] ?? 'N/A'); ?></li>
                        <li><strong>Site:</strong> <?php echo htmlspecialchars($existingConfig['site_name'] ?? 'N/A'); ?></li>
                        <li><strong>URL:</strong> <?php echo htmlspecialchars($existingConfig['site_url'] ?? 'N/A'); ?></li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>🌐 Automatisch erkannte Domain:</strong><br>
                    <code style="font-size: 1.1rem; color: #3b82f6;"><?php echo htmlspecialchars($autoUrl); ?></code><br>
                    <small style="color: #64748b;">Diese wird im nächsten Schritt verwendet.</small>
                </div>

                <?php if ($isReinstall): ?>
                <!-- Update empfohlen -->
                <form method="post" action="?step=update" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn" style="background: linear-gradient(135deg,#10b981 0%,#059669 100%);" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                        🔄 Update / Schema-Reparatur (bestehende Daten bleiben erhalten)
                    </button>
                </form>
                <!-- Neuinstallation: eingeklappt, extra Bestätigung notwendig -->
                <details style="margin-top: 1rem; border: 2px solid #fca5a5; border-radius: 8px; overflow: hidden;">
                    <summary style="padding: .75rem 1rem; background: #fef2f2; color: #991b1b; font-weight: 600; cursor: pointer; list-style: none;">
                        ⚠️ Komplett-Neuinstallation (alle Daten löschen) – hier aufklappen
                    </summary>
                    <div style="padding: 1rem; background: #fff;">
                        <p style="color: #7f1d1d; margin-bottom: 1rem; font-size: .9rem;">
                            <strong>ACHTUNG:</strong> Löscht <strong>alle Datenbank-Tabellen</strong> unwiderruflich!
                            Bitte vorher ein Backup erstellen.
                        </p>
                        <form method="post" action="?step=2">
                            <input type="hidden" name="reinstall" value="1">
                            <button type="submit" class="btn" style="background: linear-gradient(135deg,#ef4444 0%,#b91c1c 100%);" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                                ❌ Ja, komplett neu installieren (alle Daten löschen)
                            </button>
                        </form>
                    </div>
                </details>
                <?php else: ?>
                <form method="post" action="?step=2" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn" <?php echo (!$phpCompatible || !$mysqlAvailable || !$writePermission) ? 'disabled' : ''; ?>>
                        Weiter zu Schritt 2 →
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ─── Update-Modus: fehlende Tabellen ergänzen, Config-Werte aktualisieren ────
if ($step === 'update') {
    if (!$existingConfig) {
        header('Location: ?step=1');
        exit;
    }

    $updateErrors  = [];
    $tableResults  = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_update'])) {
        $newSiteName   = trim($_POST['site_name']   ?? $existingConfig['site_name']   ?? '');
        $newSiteUrl    = rtrim(trim($_POST['site_url'] ?? $existingConfig['site_url'] ?? ''), '/');
        $newAdminEmail = trim($_POST['admin_email']  ?? $existingConfig['admin_email'] ?? '');
        $newDebugMode  = isset($_POST['debug_mode'])  ? 'true' : 'false';

        // 1. config/app.php aktualisieren – DB-Zugangsdaten + Security-Keys bleiben erhalten
        $configResult = updateConfigFile($existingConfig, [
            'site_name'   => $newSiteName,
            'site_url'    => $newSiteUrl,
            'admin_email' => $newAdminEmail,
            'debug_mode'  => $newDebugMode,
        ]);

        if ($configResult !== true) {
            $updateErrors[] = 'Config-Update fehlgeschlagen: ' . $configResult;
        } else {
            try {
                $dsn = "mysql:host={$existingConfig['db_host']};dbname={$existingConfig['db_name']};charset=utf8mb4";
                $pdo = new PDO($dsn, $existingConfig['db_user'], $existingConfig['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);

                // 2. Fehlende Tabellen anlegen (CREATE TABLE IF NOT EXISTS – Daten bleiben)
                $tableResults = createDatabaseTables($pdo, $existingConfig['db_prefix'] ?? 'cms_');

                // 3. Zentrale Settings in DB aktualisieren
                $prefix = 'cms_';
                $settingStmt = $pdo->prepare(
                    "INSERT INTO {$prefix}settings (option_name, option_value, autoload)
                     VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value)"
                );
                foreach ([
                    'site_name'   => $newSiteName,
                    'admin_email' => $newAdminEmail,
                    'site_title'  => $newSiteName,
                ] as $k => $v) {
                    try { $settingStmt->execute([$k, $v]); } catch (PDOException) {}
                }

                $newTables = array_keys(array_filter($tableResults, static fn($v) => $v === true));
                clearSchemaManagerFlagFile();
                writeInstallerLockFile($existingConfig + ['site_url' => $newSiteUrl]);
                $_SESSION['install_success'] = [
                    'username'       => '',
                    'site_url'       => $newSiteUrl,
                    'is_update'      => true,
                    'tables_created' => $newTables,
                ];
                header('Location: ?step=5');
                exit;

            } catch (PDOException $e) {
                $updateErrors[] = 'Datenbankfehler: ' . $e->getMessage();
            }
        }
    }

    // Formular-Vorbelegung aus bestehender Config
    $fSiteName   = htmlspecialchars($existingConfig['site_name']   ?? 'IT Expert Network');
    $fSiteUrl    = htmlspecialchars($existingConfig['site_url']    ?? autoDetectUrl());
    $fAdminEmail = htmlspecialchars($existingConfig['admin_email'] ?? '');
    $fDebugMode  = ($existingConfig['debug_mode'] ?? 'false') === 'true';

    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CMS Update / Schema-Reparatur</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
            .form-group { margin: 1.5rem 0; }
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
            input[readonly] {
                background: #f8fafc;
                color: #94a3b8;
                cursor: not-allowed;
            }
            input:focus { outline: none; border-color: #10b981; }
            .help-text { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
            .error-box {
                background: #fef2f2;
                border-left: 4px solid #ef4444;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
                color: #991b1b;
            }
            .info-box {
                background: #f0fdf4;
                border-left: 4px solid #10b981;
                padding: 1rem;
                margin: 1rem 0;
                border-radius: 4px;
            }
            .btn {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 1rem 2rem;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                width: 100%;
                margin-top: 1rem;
            }
            .btn:hover { opacity: 0.9; }
            .btn-back { display: inline-block; color: #64748b; text-decoration: none; font-size: .875rem; margin-bottom: 1.5rem; }
            .checkbox-group { display: flex; align-items: center; gap: .5rem; }
            .section-divider { border: none; border-top: 2px solid #e2e8f0; margin: 2rem 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <a href="?step=1" class="btn-back">← Zurück zur Übersicht</a>
                <h1>🔄 CMS Update / Schema-Reparatur</h1>
                <p style="color: #64748b; margin-bottom: 1.5rem;">
                    Fehlende Datenbank-Tabellen werden ergänzt.
                    Bestehende Daten und Einstellungen <strong>bleiben vollständig erhalten</strong>.
                </p>

                <?php if (!empty($updateErrors)): ?>
                <div class="error-box">
                    <strong>⚠️ Fehler:</strong><br>
                    <?php echo implode('<br>', array_map('htmlspecialchars', $updateErrors)); ?>
                </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>📋 Bestehende Datenbankverbindung:</strong><br>
                    Datenbank: <code><?php echo htmlspecialchars($existingConfig['db_name'] ?? ''); ?></code>
                    @ <code><?php echo htmlspecialchars($existingConfig['db_host'] ?? ''); ?></code>
                    &nbsp;·&nbsp; Benutzer: <code><?php echo htmlspecialchars($existingConfig['db_user'] ?? ''); ?></code><br>
                    <small style="color: #065f46;">DB-Zugangsdaten und Security-Keys werden unverändert übernommen.</small>
                </div>

                <form method="post">
                    <input type="hidden" name="run_update" value="1">

                    <hr class="section-divider">
                    <p style="font-weight: 700; color: #1e293b; margin-bottom: 1rem;">Site-Konfiguration prüfen / anpassen</p>

                    <div class="form-group">
                        <label>Site-Name</label>
                        <input type="text" name="site_name" value="<?php echo $fSiteName; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Site-URL</label>
                        <input type="url" name="site_url" value="<?php echo $fSiteUrl; ?>" required>
                        <p class="help-text">Ohne abschließenden Slash — wird aus bestehender Config übernommen.</p>
                    </div>
                    <div class="form-group">
                        <label>Admin E-Mail</label>
                        <input type="email" name="admin_email" value="<?php echo $fAdminEmail; ?>" required>
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="debug_mode" id="debug_mode" <?php echo $fDebugMode ? 'checked' : ''; ?>>
                            <label for="debug_mode" style="margin: 0;">Debug-Modus aktivieren</label>
                        </div>
                        <p class="help-text">Nur für Entwicklung — in Produktion deaktivieren!</p>
                    </div>

                    <hr class="section-divider">
                    <p style="font-weight: 700; color: #1e293b; margin-bottom: .5rem;">Datenbankzugänge (schreibgeschützt)</p>
                    <div class="form-group">
                        <label>DB-Host</label>
                        <input type="text" value="<?php echo htmlspecialchars($existingConfig['db_host'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>DB-Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($existingConfig['db_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>DB-Benutzer</label>
                        <input type="text" value="<?php echo htmlspecialchars($existingConfig['db_user'] ?? ''); ?>" readonly>
                    </div>

                    <button type="submit" class="btn">🚀 Update starten</button>
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
    // Reinstall-Flag aus POST (Step-1-Button) oder Session (Seiten-Reload) lesen.
    // KEIN automatischer Verbindungsversuch mit bestehenden Credentials hier –
    // der Nutzer muss die Zugangsdaten zuerst prüfen und bestätigen.
    $isReinstall = (isset($_POST['reinstall']) && $_POST['reinstall'] == '1')
                || (isset($_SESSION['is_reinstall']) && $_SESSION['is_reinstall'] === true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_db'])) {
        $dbHost = trim($_POST['db_host'] ?? '');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        // Reinstall-Flag aus dem versteckten Formularfeld
        $reinstallFlag = isset($_POST['reinstall_flag']) && $_POST['reinstall_flag'] == '1';

        $testResult = testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);

        $dbPrefix = trim($_POST['db_prefix'] ?? 'cms_');
        // Sicherheits-Validierung: nur Buchstaben, Zahlen, Unterstrich; muss mit _ enden
        if (!preg_match('/^[a-z][a-z0-9_]*_$/i', $dbPrefix)) {
            $errors[] = 'Tabellen-Präfix darf nur Buchstaben, Zahlen und _ enthalten und muss mit _ enden (z.B. cms_)';
        } elseif (strlen($dbPrefix) > 20) {
            $errors[] = 'Tabellen-Präfix darf maximal 20 Zeichen lang sein';
        } else {
            $dbPrefix = strtolower($dbPrefix);
        }

        if ($testResult === true && empty($errors)) {
            $_SESSION['db_config'] = [
                'db_host'   => $dbHost,
                'db_name'   => $dbName,
                'db_user'   => $dbUser,
                'db_pass'   => $dbPass,
                'db_prefix' => $dbPrefix,
            ];
            // Reinstall-Flag für Step 4 merken (DB-Bereinigung erfolgt dort)
            $_SESSION['is_reinstall'] = $reinstallFlag;
            header('Location: ?step=3');
            exit;
        } elseif ($testResult !== true) {
            $errors[] = $testResult;
        }
    }

    // Formular vorbelegen: zuerst Session-Werte, dann bestehende Config, dann Defaults.
    // Placeholder-Werte wie 'YOUR_DATABASE_USER' aus der Config werden angezeigt,
    // damit der Nutzer sie korrigieren kann – aber es wird NICHT versucht, damit zu verbinden.
    $defaultValues = $_SESSION['db_config'] ?? ($existingConfig ? [
        'db_host'   => $existingConfig['db_host']   ?? 'localhost',
        'db_name'   => $existingConfig['db_name']   ?? '',
        'db_user'   => $existingConfig['db_user']   ?? '',
        'db_pass'   => '',
        'db_prefix' => $existingConfig['db_prefix'] ?? 'cms_',
    ] : [
        'db_host'   => 'localhost',
        'db_name'   => '',
        'db_user'   => '',
        'db_pass'   => '',
        'db_prefix' => 'cms_',
    ]);
    
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

                <?php if ($isReinstall): ?>
                <div class="info-box" style="background:#fef3c7;border-left-color:#f59e0b;">
                    <strong>⚠️ Neuinstallation</strong> – alle Tabellen werden nach erfolgreicher Verbindungsprüfung gelöscht.<br>
                    <?php if ($existingConfig): ?>
                    Die Felder wurden aus der bestehenden <code>config/app.php</code> vorbelegt.
                    Bitte Zugangsdaten prüfen und ggf. korrigieren, bevor Sie fortfahren.
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="test_db" value="1">
                    <input type="hidden" name="reinstall_flag" value="<?php echo $isReinstall ? '1' : '0'; ?>">

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

                    <div class="form-group">
                        <label>Tabellen-Präfix</label>
                        <input type="text" name="db_prefix"
                               value="<?php echo htmlspecialchars($defaultValues['db_prefix'] ?? 'cms_'); ?>"
                               pattern="[a-zA-Z][a-zA-Z0-9_]*_"
                               maxlength="20"
                               placeholder="cms_"
                               required>
                        <p class="help-text">
                            Standard: <code>cms_</code> &mdash; nur Buchstaben, Zahlen und <code>_</code>, muss mit <code>_</code> enden.<br>
                            Bei mehreren CMS-Installationen in einer Datenbank unterschiedliche Präfixe verwenden (z.&nbsp;B. <code>shop_</code>, <code>blog_</code>).
                        </p>
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
            $dbConfig   = $_SESSION['db_config'];
            $siteConfig = $_SESSION['site_config'];
            $prefix     = $dbConfig['db_prefix'] ?? 'cms_';
            
            // 1. Create config.php
            $configData = [
                'created_at'      => date('Y-m-d H:i:s'),
                'debug_mode'      => $siteConfig['debug_mode'],
                'db_host'         => $dbConfig['db_host'],
                'db_name'         => $dbConfig['db_name'],
                'db_user'         => $dbConfig['db_user'],
                'db_pass'         => $dbConfig['db_pass'],
                'db_prefix'       => $prefix,
                'auth_key'        => generateSecurityKey(),
                'secure_auth_key' => generateSecurityKey(),
                'nonce_key'       => generateSecurityKey(),
                'site_name'       => $siteConfig['site_name'],
                'site_url'        => $siteConfig['site_url'],
                'admin_email'     => $siteConfig['admin_email'],
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
                    
                    // 3. Immer alle bestehenden Tabellen mit diesem Präfix löschen
                    //    (idempotent – cleanDatabase gibt leeres Array zurück wenn keine Tabellen existieren)
                    $cleanResult = cleanDatabase($pdo, $prefix);
                    $_SESSION['db_cleaned'] = $cleanResult;

                    // 4. Create tables (CREATE TABLE IF NOT EXISTS)
                    $tableResults = createDatabaseTables($pdo, $prefix);

                    // 5. Create admin user
                    $adminResult = createAdminUser($pdo, $adminUsername, $adminEmail, $adminPassword, $prefix);
                    
                    // 5. Create default settings
                    createDefaultSettings($pdo, $siteConfig['site_name'], $siteConfig['admin_email'], $prefix);
                    
                    // 6. Initialize landing page data
                    initializeLandingPageData($pdo, $prefix);
                    
                    if ($adminResult === true) {
                        // SchemaManager-Flag löschen → beim ersten CMS-Boot wird das aktuelle Schema neu geprüft
                        clearSchemaManagerFlagFile();
                        writeInstallerLockFile(['site_url' => $siteConfig['site_url']]);
                        // SUCCESS!
                        $_SESSION['install_success'] = [
                            'username' => $adminUsername,
                            'site_url' => $siteConfig['site_url']
                        ];
                        unset($_SESSION['is_reinstall']);
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
        <title><?php echo isset($success['is_update']) && $success['is_update'] ? 'Update erfolgreich!' : 'Installation erfolgreich!'; ?></title>
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
                <div class="success-icon"><?php echo isset($success['is_update']) && $success['is_update'] ? '🔄' : '🎉'; ?></div>
                <h1><?php echo isset($success['is_update']) && $success['is_update'] ? 'Update erfolgreich!' : 'Installation erfolgreich!'; ?></h1>
                <p class="success-message">
                    <?php if (isset($success['is_update']) && $success['is_update']): ?>
                        Das CMS wurde erfolgreich aktualisiert. Alle bestehenden Daten sind erhalten geblieben.
                        <?php if (!empty($success['tables_created'])): ?>
                        <br><small style="color:#065f46;">Neu angelegte Tabellen: <?php echo htmlspecialchars(implode(', ', $success['tables_created'])); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        Ihr CMS wurde erfolgreich installiert und ist einsatzbereit.
                    <?php endif; ?>
                </p>
                
                <div class="info-box">
                    <div class="info-item">
                        <strong>🌐 Site-URL:</strong>
                        <code><?php echo htmlspecialchars($success['site_url']); ?></code>
                    </div>
                    <?php if (!empty($success['username'])): ?>
                    <div class="info-item">
                        <strong>👤 Admin-User:</strong>
                        <code><?php echo htmlspecialchars($success['username']); ?></code>
                    </div>
                    <?php endif; ?>
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
