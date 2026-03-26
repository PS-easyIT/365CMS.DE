<?php
declare(strict_types=1);

/**
 * Datenschutz-Auskunft (DSGVO Art. 15) – Entry Point
 * Route: /admin/privacy-requests
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

$adminRedirectAliasConfig = [
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin(),
    'target_url' => '/admin/data-requests',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
