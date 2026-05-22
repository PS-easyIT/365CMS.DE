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
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || in_array(strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')), ['https', 'wss'], true)
        || in_array(strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')), ['on', '1', 'true'], true)
        || in_array(strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')), ['on', '1'], true);

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/install/InstallerService.php';
require_once __DIR__ . '/install/InstallerController.php';

$service = new \CMS\Install\InstallerService(__DIR__);
$controller = new \CMS\Install\InstallerController($service);
$controller->handle();
