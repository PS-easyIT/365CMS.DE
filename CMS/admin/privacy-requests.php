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

function cms_admin_privacy_requests_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

function cms_admin_privacy_requests_target_url(): string
{
    return SITE_URL . '/admin/data-requests';
}

if (!Auth::instance()->isAdmin()) {
    cms_admin_privacy_requests_redirect(SITE_URL);
}

cms_admin_privacy_requests_redirect(cms_admin_privacy_requests_target_url());
