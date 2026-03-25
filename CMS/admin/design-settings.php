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

function cms_admin_design_settings_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

function cms_admin_design_settings_target_url(): string
{
    return SITE_URL . '/admin/theme-editor';
}

if (!Auth::instance()->isAdmin()) {
    cms_admin_design_settings_redirect(SITE_URL);
}

cms_admin_design_settings_redirect(cms_admin_design_settings_target_url());
