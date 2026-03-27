<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Marketplace – Entry Point
 * Route: /admin/theme-marketplace
 */

use CMS\Auth;

const CMS_ADMIN_THEME_MARKETPLACE_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEME_MARKETPLACE_WRITE_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEME_MARKETPLACE_INSTALL_ACTION = 'install';

function cms_admin_theme_marketplace_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_THEME_MARKETPLACE_READ_CAPABILITY);
}

function cms_admin_theme_marketplace_can_install(): bool
{
    return cms_admin_theme_marketplace_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_THEME_MARKETPLACE_WRITE_CAPABILITY);
}

function cms_admin_theme_marketplace_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['theme'] ?? '')));
}

function cms_admin_theme_marketplace_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return $action === CMS_ADMIN_THEME_MARKETPLACE_INSTALL_ACTION ? $action : '';
}

/** @return array{action:string,theme:string,error:string} */
function cms_admin_theme_marketplace_normalize_payload(array $post): array
{
    $action = cms_admin_theme_marketplace_normalize_action($post['action'] ?? null);
    $theme = cms_admin_theme_marketplace_normalize_slug($post);

    $error = '';
    if ($action === '') {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
    } elseif ($action === CMS_ADMIN_THEME_MARKETPLACE_INSTALL_ACTION && $theme === '') {
        $error = 'Ungültiger Theme-Slug.';
    }

    return [
        'action' => $action,
        'theme' => $theme,
        'error' => $error,
    ];
}

function cms_admin_theme_marketplace_view_data(ThemeMarketplaceModule $module): array
{
    return $module->getData();
}

$sectionPageConfig = [
    'route_path' => '/admin/theme-marketplace',
    'view_file' => __DIR__ . '/views/themes/marketplace.php',
    'page_title' => 'Theme Marketplace',
    'active_page' => 'theme-marketplace',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-theme-marketplace.js'),
        ],
    ],
    'csrf_action' => 'admin_theme_marketplace',
    'module_file' => __DIR__ . '/modules/themes/ThemeMarketplaceModule.php',
    'module_factory' => static fn (): ThemeMarketplaceModule => new ThemeMarketplaceModule(),
    'access_checker' => static fn (): bool => cms_admin_theme_marketplace_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'data_loader' => static fn (ThemeMarketplaceModule $module): array => cms_admin_theme_marketplace_view_data($module),
    'post_handler' => static function (ThemeMarketplaceModule $module, string $section, array $post): array {
        if (!cms_admin_theme_marketplace_can_install()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Marketplace-Installationen.'];
        }

        $payload = cms_admin_theme_marketplace_normalize_payload($post);
        if ($payload['error'] !== '') {
            return ['success' => false, 'error' => $payload['error']];
        }

        return match ($payload['action']) {
            CMS_ADMIN_THEME_MARKETPLACE_INSTALL_ACTION => $module->installTheme($payload['theme']),
            default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
        };
    },
];

require __DIR__ . '/partials/section-page-shell.php';
