<?php
/**
 * PSR-4 Autoloader für 365CMS
 * 
 * Lädt automatisch Klassen aus den Namespaces:
 * - CMS\* -> /core/
 * - CMS\Services\* -> /core/services/
 * 
 * @package CMSv2
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    // Namespace-Prefix
    $prefix = 'CMS\\';
    
    // Base directory
    $base_dir = __DIR__ . '/';
    
    // Prüfe ob Klasse den Prefix verwendet
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Nicht unsere Klasse
    }
    
    // Relativer Klassenname
    $relative_class = substr($class, $len);
    
    // Ersetze Namespace-Separator mit Directory-Separator
    // und füge .php hinzu
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // Wenn Datei existiert, lade sie
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load WP_Error compatibility class
require_once __DIR__ . '/WP_Error.php';