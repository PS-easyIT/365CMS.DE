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

function cms_admin_deletion_requests_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

if (!Auth::instance()->isAdmin()) {
    cms_admin_deletion_requests_redirect(SITE_URL);
}

cms_admin_deletion_requests_redirect(SITE_URL . '/admin/data-requests');
