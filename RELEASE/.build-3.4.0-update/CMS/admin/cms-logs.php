<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$adminRedirectAliasConfig = [
    'access_checker' => static fn (): bool => \CMS\Auth::instance()->isAdmin() && \CMS\Auth::instance()->hasCapability('manage_settings'),
    'target_url' => '/admin/logs',
    'fallback_url' => '/',
];

require __DIR__ . '/partials/redirect-alias-shell.php';
