<?php
declare(strict_types=1);

/**
 * Meridian CMS Default Theme – Functions
 *
 * @package CMSDefault
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MERIDIAN_THEME_VERSION', '1.1.0');
define('MERIDIAN_THEME_DIR',     THEME_PATH . 'cms-default/');
define('MERIDIAN_THEME_URL',     \CMS\ThemeManager::instance()->getThemeUrl());

$themeIncludes = [
    MERIDIAN_THEME_DIR . 'includes/theme-class.php',
    MERIDIAN_THEME_DIR . 'includes/theme-utility-helpers.php',
    MERIDIAN_THEME_DIR . 'includes/theme-runtime-helpers.php',
    MERIDIAN_THEME_DIR . 'includes/theme-data-helpers.php',
    MERIDIAN_THEME_DIR . 'includes/theme-auth-helpers.php',
];

foreach ($themeIncludes as $themeInclude) {
    if (file_exists($themeInclude)) {
        require_once $themeInclude;
    }
}

MeridianCMSDefaultTheme::instance();
\CMS\Hooks::addFilter('local_font_slugs', [MeridianCMSDefaultTheme::instance(), 'registerRequiredLocalFonts']);
