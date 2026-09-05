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

$adminRedirectAliasConfig = [
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin() && Auth::instance()->hasCapability('manage_settings'),
    'target_url' => '/admin/theme-editor',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
