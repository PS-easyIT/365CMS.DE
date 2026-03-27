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

/** @return array<string, true> */
function cms_admin_plugin_marketplace_allowed_actions(): array
{
    return [
        'install' => true,
    ];
}

function cms_admin_plugin_marketplace_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['slug'] ?? '')));
}

function cms_admin_plugin_marketplace_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return isset(cms_admin_plugin_marketplace_allowed_actions()[$action]) ? $action : '';
}

/** @return array{action:string,slug:string} */
function cms_admin_plugin_marketplace_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_plugin_marketplace_normalize_action($post['action'] ?? null),
        'slug' => cms_admin_plugin_marketplace_normalize_slug($post),
    ];
}

function cms_admin_plugin_marketplace_handle_action(PluginMarketplaceModule $module, array $payload): array
{
    return match ($payload['action']) {
        'install' => $module->installPlugin($payload['slug']),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
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
    'post_handler' => static function (PluginMarketplaceModule $module, string $section, array $post): array {
        if (!cms_admin_plugin_marketplace_can_install()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Marketplace-Installationen.'];
        }

        $payload = cms_admin_plugin_marketplace_normalize_payload($post);
        if ($payload['action'] === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if ($payload['action'] === 'install' && $payload['slug'] === '') {
            return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        }

        return cms_admin_plugin_marketplace_handle_action($module, $payload);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
