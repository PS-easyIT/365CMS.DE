<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * System-Info & Diagnose – Legacy-Entry-Point
 * Route: /admin/system-info
 */

use CMS\Auth;

const CMS_ADMIN_SYSTEM_INFO_READ_CAPABILITY = 'manage_settings';

function cms_admin_system_info_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_SYSTEM_INFO_READ_CAPABILITY);
}

$adminRedirectAliasConfig = [
    'access_checker' => static fn (array $_config = []): bool => cms_admin_system_info_can_access(),
    'target_url' => '/admin/info',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
