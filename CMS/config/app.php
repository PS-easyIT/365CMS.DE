<?php
/**
 * CMS Application Configuration – TEMPLATE
 *
 * WICHTIG: Diese Datei ist ein Template mit Platzhalter-Werten!
 * Für die echte Konfiguration install.php ausführen – der Installer
 * generiert einzigartige Security-Keys und überschreibt diese Datei.
 *
 * NIEMALS echte Credentials oder Security-Keys in VCS einchecken!
 *
 * C-01: Security-Keys via random_bytes() generiert (durch install.php)
 * C-02: Konfiguration in config/ isoliert (via .htaccess geschützt)
 *
 * @package 365CMS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    // __DIR__ = CMS/config → dirname(__DIR__) = CMS/
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

// ─── Debug-Modus (in Produktion auf false setzen!) ─────────────────────────
// Wird durch install.php gesetzt. Vor Produktiv-Betrieb auf false ändern!
define('CMS_DEBUG', true);

// ─── Datenbank-Konfiguration ───────────────────────────────────────────────
// Diese Platzhalter werden durch install.php mit den korrekten Werten ersetzt.
define('DB_HOST',    'localhost');
define('DB_NAME',    'u185238248_CMS365v1');
define('DB_USER',    'u185238248_CMS365v1');
define('DB_PASS',    '%F9NQu#guJ@4*6');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  'cms_');

// ─── Security-Keys (via random_bytes – NICHT manuell setzen!) ─────────────
// WICHTIG: Diese Datei enthält Platzhalter-Keys!
// Vor dem ersten Betrieb install.php ausführen – der Installer generiert
// kryptografisch sichere, einzigartige Keys via random_bytes(32).
// NIEMALS diese Platzhalter in Produktion verwenden!
define('AUTH_KEY',        'REPLACE_VIA_INSTALLER_run_install_php_to_generate_unique_key_auth');
define('SECURE_AUTH_KEY', 'REPLACE_VIA_INSTALLER_run_install_php_to_generate_unique_key_secure');
define('NONCE_KEY',       'REPLACE_VIA_INSTALLER_run_install_php_to_generate_unique_key_nonce');

// ─── Site-Konfiguration ────────────────────────────────────────────────────
// Platzhalter – werden durch install.php gesetzt.
define('SITE_NAME',    '365CMS.DE');
define('SITE_URL',     'https://beta.365cms.de'); // NIEMALS mit Unterverzeichnis!
define('ADMIN_EMAIL',  'admin@example.com');
define('CMS_VERSION',  '1.6.14');

// ─── Pfade ─────────────────────────────────────────────────────────────────
define('CORE_PATH',   ABSPATH . 'core/');
define('THEME_PATH',  ABSPATH . 'themes/');
define('PLUGIN_PATH', ABSPATH . 'plugins/');
define('UPLOAD_PATH', ABSPATH . 'uploads/');
define('ASSETS_PATH', ABSPATH . 'assets/');

// ─── URLs ──────────────────────────────────────────────────────────────────
define('SITE_URL_PATH', '/');
define('ASSETS_URL',    SITE_URL . '/assets');
define('UPLOAD_URL',    SITE_URL . '/uploads');

// ─── System-Einstellungen ──────────────────────────────────────────────────
define('DEFAULT_THEME',      'cms-default');
define('SESSIONS_LIFETIME',  3600 * 2); // 2 Stunden
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT',      300); // 5 Minuten

// ─── Fehler-Reporting ──────────────────────────────────────────────────────
if (CMS_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', ABSPATH . 'logs/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '0');
}

// ─── Zeitzone ──────────────────────────────────────────────────────────────
date_default_timezone_set('Europe/Berlin');
