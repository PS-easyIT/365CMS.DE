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

$adminRedirectAliasConfig = [
    'access_checker' => static fn (array $_config = []): bool => Auth::instance()->isAdmin(),
    'target_url' => '/admin/info',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
