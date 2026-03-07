<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Themes & Design Einstellungen – Entry Point
 * Route: /admin/design-settings
 */

use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

header('Location: ' . SITE_URL . '/admin/theme-editor');
exit;
