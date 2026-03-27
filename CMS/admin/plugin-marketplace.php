<?php
declare(strict_types=1);

/**
 * Plugin Marketplace – Entry Point
 * Route: /admin/plugin-marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

const CMS_ADMIN_PLUGIN_MARKETPLACE_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_PLUGIN_MARKETPLACE_WRITE_CAPABILITY = 'manage_settings';
const CMS_ADMIN_PLUGIN_MARKETPLACE_INSTALL_ACTION = 'install';

function cms_admin_plugin_marketplace_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_PLUGIN_MARKETPLACE_READ_CAPABILITY);
}

function cms_admin_plugin_marketplace_can_install(): bool
{
    return cms_admin_plugin_marketplace_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_PLUGIN_MARKETPLACE_WRITE_CAPABILITY);
}

function cms_admin_plugin_marketplace_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['slug'] ?? '')));
}

function cms_admin_plugin_marketplace_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return $action === CMS_ADMIN_PLUGIN_MARKETPLACE_INSTALL_ACTION ? $action : '';
}

/** @return array{action:string,slug:string,error:string} */
function cms_admin_plugin_marketplace_normalize_payload(array $post): array
{
    $action = cms_admin_plugin_marketplace_normalize_action($post['action'] ?? null);
    $slug = cms_admin_plugin_marketplace_normalize_slug($post);

    $error = '';
    if ($action === '') {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
    } elseif ($action === CMS_ADMIN_PLUGIN_MARKETPLACE_INSTALL_ACTION && $slug === '') {
        $error = 'Ungültiger Plugin-Slug.';
    }

    return [
        'action' => $action,
        'slug' => $slug,
        'error' => $error,
    ];
}

function cms_admin_plugin_marketplace_view_data(PluginMarketplaceModule $module): array
{
    return $module->getData();
}

$sectionPageConfig = [
    'route_path' => '/admin/plugin-marketplace',
    'view_file' => __DIR__ . '/views/plugins/marketplace.php',
    'page_title' => 'Plugin Marketplace',
    'active_page' => 'plugin-marketplace',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-plugin-marketplace.js'),
        ],
    ],
    'csrf_action' => 'admin_plugin_mp',
    'module_file' => __DIR__ . '/modules/plugins/PluginMarketplaceModule.php',
    'module_factory' => static fn (): PluginMarketplaceModule => new PluginMarketplaceModule(),
    'access_checker' => static fn (): bool => cms_admin_plugin_marketplace_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'data_loader' => static fn (PluginMarketplaceModule $module): array => cms_admin_plugin_marketplace_view_data($module),
    'post_handler' => static function (PluginMarketplaceModule $module, string $section, array $post): array {
        if (!cms_admin_plugin_marketplace_can_install()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Marketplace-Installationen.'];
        }

        $payload = cms_admin_plugin_marketplace_normalize_payload($post);
        if ($payload['error'] !== '') {
            return ['success' => false, 'error' => $payload['error']];
        }

        return match ($payload['action']) {
            CMS_ADMIN_PLUGIN_MARKETPLACE_INSTALL_ACTION => $module->installPlugin($payload['slug']),
            default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
        };
    },
];

require __DIR__ . '/partials/section-page-shell.php';
