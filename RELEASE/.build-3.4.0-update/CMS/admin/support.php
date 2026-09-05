<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Legacy-Weiterleitung: frühere Support-&-Dokumentation-Seite
 * Route: /admin/support
 */

use CMS\Auth;

const CMS_ADMIN_SUPPORT_READ_CAPABILITIES = ['manage_settings', 'manage_system'];

function cms_admin_support_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_support_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_support_has_any_capability(CMS_ADMIN_SUPPORT_READ_CAPABILITIES);
}

$adminRedirectAliasConfig = [
    'access_checker' => static fn (array $_config = []): bool => cms_admin_support_can_access(),
    'target_url' => '/admin/documentation',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
