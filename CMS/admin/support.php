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

$adminRedirectAliasConfig = [
    'access_checker' => static fn (array $_config = []): bool => Auth::instance()->isAdmin(),
    'target_url' => '/admin/documentation',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
