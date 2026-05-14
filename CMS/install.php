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
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

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
