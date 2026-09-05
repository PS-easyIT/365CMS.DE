<?php
declare(strict_types=1);

/**
 * Löschanträge (DSGVO Art. 17) – Entry Point
 * Route: /admin/deletion-requests
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

const CMS_ADMIN_DELETION_REQUESTS_READ_CAPABILITY = 'manage_settings';

function cms_admin_deletion_requests_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_DELETION_REQUESTS_READ_CAPABILITY);
}

$adminRedirectAliasConfig = [
    'access_checker' => static fn (): bool => cms_admin_deletion_requests_can_access(),
    'target_url' => '/admin/data-requests',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
