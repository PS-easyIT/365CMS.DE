<?php
/**
 * 365CMS Vendor Autoloader
 *
 * Zentraler Autoloader für alle externen Libraries aus dem ASSETS-Verzeichnis.
 * Nur standalone-fähige Libraries werden hier registriert.
 *
 * Verwendung:
 *   require_once ABSPATH . '../ASSETS/autoload.php';
 *
 * @package 365CMS\Vendor
 */

declare(strict_types=1);

// Basisverzeichnis des ASSETS-Ordners
if (!defined('CMS_ASSETS_VENDOR_PATH')) {
    define('CMS_ASSETS_VENDOR_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

// ─── HTMLPurifier (4.19.0) ─────────────────────────────────────────────────
// Eigener Autoloader über Bootstrap-Klasse
$_htmlPurifierLib = CMS_ASSETS_VENDOR_PATH . 'htmlpurifier-4.19.0/library/HTMLPurifier.auto.php';
if (file_exists($_htmlPurifierLib)) {
    require_once $_htmlPurifierLib;
}
unset($_htmlPurifierLib);

// ─── SimplePie (1.9.0) ────────────────────────────────────────────────────
// Eigener PSR-4 Autoloader + Legacy-Autoloader
$_simplePieAutoload = CMS_ASSETS_VENDOR_PATH . 'simplepie-1.9.0/autoloader.php';
if (file_exists($_simplePieAutoload)) {
    require_once $_simplePieAutoload;
}
unset($_simplePieAutoload);

// ─── TNTSearch (5.0.2) ────────────────────────────────────────────────────
// PSR-4: TeamTNT\TNTSearch\ → src/
spl_autoload_register(function (string $class): void {
    $prefix = 'TeamTNT\\TNTSearch\\';
    $baseDir = CMS_ASSETS_VENDOR_PATH . 'tntsearch-5.0.2/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// TNTSearch Helper-Funktionen
$_tntHelpers = CMS_ASSETS_VENDOR_PATH . 'tntsearch-5.0.2/helper/helpers.php';
if (file_exists($_tntHelpers)) {
    require_once $_tntHelpers;
}
unset($_tntHelpers);

// ─── elFinder (2.1.66) ────────────────────────────────────────────────────
// Eigener Autoloader für elFinder*-Klassen
spl_autoload_register(function (string $class): void {
    // elFinder-Klassen beginnen alle mit "elFinder"
    if (strncmp($class, 'elFinder', 8) !== 0) {
        return;
    }

    $file = CMS_ASSETS_VENDOR_PATH . 'elFinder-2.1.66/php/' . $class . '.class.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Intervention Image (3.11.7) ──────────────────────────────────────────
// PSR-4: Intervention\Image\ → src/
// HINWEIS: Benötigt intervention/gif (Dependency fehlt) – nur für GD-basierte
// Operationen ohne GIF-Unterstützung nutzbar.
spl_autoload_register(function (string $class): void {
    $prefix = 'Intervention\\Image\\';
    $baseDir = CMS_ASSETS_VENDOR_PATH . 'image-3.11.7/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Schema.org (4.0.0 / Spatie) ──────────────────────────────────────────
// PSR-4: Spatie\SchemaOrg\ → src/
spl_autoload_register(function (string $class): void {
    $prefix = 'Spatie\\SchemaOrg\\';
    $baseDir = CMS_ASSETS_VENDOR_PATH . 'schema-org-4.0.0/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
