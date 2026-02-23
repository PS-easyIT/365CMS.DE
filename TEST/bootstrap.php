<?php
/**
 * PHPUnit Bootstrap – 365CMS
 *
 * Definiert alle CMS-Konstanten in Test-Variante, lädt den Autoloader
 * und richtet eine isolierte Test-Umgebung ein.
 *
 * @package CMS\Tests
 */

declare(strict_types=1);

// ── Basis-Pfade ───────────────────────────────────────────────────────────────
define('ABSPATH',   dirname(__DIR__) . '/CMS/');
define('CORE_PATH', ABSPATH . 'core/');

// ── Datenbank-Konfiguration (Test-Dummys, für Unit-Tests ohne DB) ─────────────
define('DB_HOST',    '127.0.0.1');
define('DB_NAME',    'cms_test');
define('DB_USER',    'cms_test');
define('DB_PASS',    'cms_test');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX',  'test_');

// ── Site-Konfiguration ────────────────────────────────────────────────────────
define('SITE_URL',        'http://localhost');
define('SITE_NAME',       '365CMS Test');
define('DEFAULT_THEME',   'cms-default');
define('UPLOAD_PATH',     ABSPATH . 'uploads/');
define('UPLOAD_URL',      SITE_URL . '/uploads');
define('THEMES_PATH',     ABSPATH . 'themes/');
define('THEMES_URL',      SITE_URL . '/themes');
define('PLUGINS_PATH',    dirname(ABSPATH) . '/PLUGINS/');
define('PLUGINS_URL',     SITE_URL . '/plugins');

// ── Security-Keys (Test-Dummys) ────────────────────────────────────────────────
define('NONCE_KEY',    str_repeat('a', 64));
define('CSRF_KEY',     str_repeat('b', 64));
define('AUTH_KEY',     str_repeat('c', 64));

// ── Laufzeit-Konfiguration ────────────────────────────────────────────────────
define('CMS_DEBUG',   true);
define('CMS_VERSION', '2.0.0-test');
define('CMS_MODE',    'test');

// ── Autoloader laden ──────────────────────────────────────────────────────────
require_once CORE_PATH . 'autoload.php';

// ── Temp-Cache-Verzeichnis für Tests ──────────────────────────────────────────
// Wird nach jedem Test-Run geleert (via tearDown in CacheTests)
$testCacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cms_test_cache_' . getmypid() . DIRECTORY_SEPARATOR;
if (!is_dir($testCacheDir)) {
    mkdir($testCacheDir, 0755, true);
}
define('TEST_CACHE_DIR', $testCacheDir);

// Cleanup bei Script-Ende
register_shutdown_function(static function () use ($testCacheDir): void {
    if (is_dir($testCacheDir)) {
        foreach (glob($testCacheDir . '*') ?: [] as $f) {
            is_file($f) && unlink($f);
        }
        @rmdir($testCacheDir);
    }
});
