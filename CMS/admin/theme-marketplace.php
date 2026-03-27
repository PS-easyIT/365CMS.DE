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

/** @return array<string, true> */
function cms_admin_theme_marketplace_allowed_actions(): array
{
    return [
        'install' => true,
    ];
}

function cms_admin_theme_marketplace_normalize_slug(array $post): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($post['theme'] ?? '')) ?? '';
}

function cms_admin_theme_marketplace_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return isset(cms_admin_theme_marketplace_allowed_actions()[$action]) ? $action : '';
}

function cms_admin_theme_marketplace_handle_action(ThemeMarketplaceModule $module, string $action, array $post): array
{
    return match ($action) {
        'install' => $module->installTheme(cms_admin_theme_marketplace_normalize_slug($post)),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/theme-marketplace',
    'view_file' => __DIR__ . '/views/themes/marketplace.php',
    'page_title' => 'Theme Marketplace',
    'active_page' => 'theme-marketplace',
    'csrf_action' => 'admin_theme_marketplace',
    'module_file' => __DIR__ . '/modules/themes/ThemeMarketplaceModule.php',
    'module_factory' => static fn (): ThemeMarketplaceModule => new ThemeMarketplaceModule(),
    'access_checker' => static fn (): bool => cms_admin_theme_marketplace_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (ThemeMarketplaceModule $module, string $section, array $post): array {
        if (!cms_admin_theme_marketplace_can_install()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Marketplace-Installationen.'];
        }

        $action = cms_admin_theme_marketplace_normalize_action($post['action'] ?? null);
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if ($action === 'install' && cms_admin_theme_marketplace_normalize_slug($post) === '') {
            return ['success' => false, 'error' => 'Ungültiger Theme-Slug.'];
        }

        return cms_admin_theme_marketplace_handle_action($module, $action, $post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
