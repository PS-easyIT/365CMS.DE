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

function cms_admin_design_settings_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability('manage_settings');
}

function cms_admin_design_settings_fallback_url(): string
{
    return SITE_URL;
}

function cms_admin_design_settings_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

function cms_admin_design_settings_target_url(): string
{
    return SITE_URL . '/admin/theme-editor';
}

if (!cms_admin_design_settings_can_access()) {
    cms_admin_design_settings_redirect(cms_admin_design_settings_fallback_url());
}

cms_admin_design_settings_redirect(cms_admin_design_settings_target_url());
