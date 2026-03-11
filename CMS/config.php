<?php
/**
 * CMS Configuration Stub
 *
 * C-02: Die eigentliche Konfiguration liegt in config/app.php,
 * das durch config/.htaccess vor direktem Web-Zugriff geschützt ist.
 * Diese Stub-Datei gewährleistet Abwärtskompatibilität (z. B. Installer).
 *
 * HINWEIS: Bei Neuinstallation schreibt install.php in config/app.php.
 *
 * @package 365CMS
 */

declare(strict_types=1);

// ABSPATH muss hier gesetzt sein, bevor app.php ihn mit dirname(__DIR__) setzt,
// weil __DIR__ in app.php auf config/ zeigt (nicht auf CMS/).
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/core/Contracts/CacheInterface.php';
    require_once __DIR__ . '/core/CacheManager.php';

    \CMS\CacheManager::instance()->sendResponseHeaders('private');
}

if (!defined('CMS_MIN_PHP_VERSION')) {
    define('CMS_MIN_PHP_VERSION', '8.4.0');
}

if (!defined('CMS_INSTALLER_RUNNING') && version_compare(PHP_VERSION, CMS_MIN_PHP_VERSION, '<')) {
    $requiredPhpVersion = CMS_MIN_PHP_VERSION;
    $currentPhpVersion = PHP_VERSION;

    if (PHP_SAPI === 'cli') {
        fwrite(
            STDERR,
            '365CMS benötigt mindestens PHP ' . $requiredPhpVersion . '. Aktuell aktiv: ' . $currentPhpVersion . '.' . PHP_EOL
        );
        exit(1);
    }

    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>365CMS – PHP-Version nicht unterstützt</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 2rem; }
            .box { background: #fff; border-radius: 16px; padding: 2.5rem; max-width: 560px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18); }
            h1 { color: #b91c1c; font-size: 1.5rem; margin: 0 0 1rem; }
            p { color: #475569; line-height: 1.6; margin: 0 0 1rem; }
            code { background: #f8fafc; padding: 0.15rem 0.4rem; border-radius: 6px; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>⚠️ PHP-Version nicht unterstützt</h1>
            <p>365CMS benötigt aufgrund der produktiv eingebundenen Runtime-Bibliotheken mindestens <code>PHP <?php echo htmlspecialchars($requiredPhpVersion, ENT_QUOTES, 'UTF-8'); ?></code>.</p>
            <p>Aktuell aktiv ist <code>PHP <?php echo htmlspecialchars($currentPhpVersion, ENT_QUOTES, 'UTF-8'); ?></code>. Bitte die Hosting-Plattform anheben, bevor 365CMS gestartet wird.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$_cmsAppConfig = __DIR__ . '/config/app.php';

if (!file_exists($_cmsAppConfig)) {
    // Noch keine Installation – direkt zum Installer
    if (!defined('CMS_INSTALLER_RUNNING')) {
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') . '/install.php');
        exit;
    }
    return;
}

require_once $_cmsAppConfig;
unset($_cmsAppConfig);

// Schutzcheck: Wenn DB-Zugangsdaten noch Platzhalter-Werte enthalten,
// wurde config/app.php nach einem Git-Deployment nicht neu generiert.
// → Installer aufrufen statt kryptischen DB-Fehler zu zeigen.
if (!defined('CMS_INSTALLER_RUNNING')
    && defined('DB_USER')
    && (
        str_contains(DB_USER, 'YOUR_')
        || str_contains(DB_NAME, 'YOUR_')
    )
) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>CMS – Installation erforderlich</title>
        <style>
            body { font-family: -apple-system, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .box { background: #fff; border-radius: 12px; padding: 2.5rem; max-width: 480px; box-shadow: 0 4px 24px rgba(0,0,0,.1); text-align: center; }
            h1 { color: #dc2626; font-size: 1.4rem; margin-bottom: 1rem; }
            p { color: #475569; line-height: 1.6; }
            a { display: inline-block; margin-top: 1.5rem; padding: .75rem 1.75rem; background: #3b82f6; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
            a:hover { background: #2563eb; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>⚠️ Konfiguration nicht abgeschlossen</h1>
            <p>Die Datenbank-Konfiguration enthält noch Platzhalter-Werte.<br>
               Bitte den CMS-Installer ausführen um die Installation abzuschließen.</p>
            <a href="install.php">Zum Installer →</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
