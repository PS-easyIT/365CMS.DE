<?php
declare(strict_types=1);

/**
 * Plugins – Entry Point
 * Route: /admin/plugins
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

const CMS_ADMIN_PLUGINS_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_PLUGINS_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_plugins_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_PLUGINS_READ_CAPABILITY);
}

function cms_admin_plugins_can_mutate(): bool
{
    return cms_admin_plugins_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_PLUGINS_WRITE_CAPABILITY);
}

/** @return array<string, true> */
function cms_admin_plugins_allowed_actions(): array
{
    return [
        'activate' => true,
        'deactivate' => true,
        'delete' => true,
    ];
}

function cms_admin_plugins_normalize_slug(array $post): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($post['slug'] ?? '')));
}

/**
 * @return array{action:string,slug:string}
 */
function cms_admin_plugins_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_plugins_normalize_action($post['action'] ?? null),
        'slug' => cms_admin_plugins_normalize_slug($post),
    ];
}

function cms_admin_plugins_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return isset(cms_admin_plugins_allowed_actions()[$action]) ? $action : '';
}

function cms_admin_plugins_handle_action(PluginsModule $module, array $payload): array
{
    return match ($payload['action']) {
        'activate' => $module->activatePlugin($payload['slug']),
        'deactivate' => $module->deactivatePlugin($payload['slug']),
        'delete' => $module->deletePlugin($payload['slug']),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/plugins',
    'view_file' => __DIR__ . '/views/plugins/list.php',
    'page_title' => 'Plugins',
    'active_page' => 'plugins',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-plugins.js'),
        ],
    ],
    'csrf_action' => 'admin_plugins',
    'module_file' => __DIR__ . '/modules/plugins/PluginsModule.php',
    'module_factory' => static fn (): PluginsModule => new PluginsModule(),
    'access_checker' => static fn (): bool => cms_admin_plugins_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (PluginsModule $module, string $section, array $post): array {
        if (!cms_admin_plugins_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Plugin-Aktionen.'];
        }

        $payload = cms_admin_plugins_normalize_payload($post);
        if ($payload['action'] === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if ($payload['slug'] === '') {
            return ['success' => false, 'error' => 'Ungültiger Plugin-Slug.'];
        }

        return cms_admin_plugins_handle_action($module, $payload);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
