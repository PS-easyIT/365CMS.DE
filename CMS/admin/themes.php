<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Themes – Entry Point
 * Route: /admin/themes
 */

use CMS\Auth;

const CMS_ADMIN_THEMES_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEMES_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_themes_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_THEMES_READ_CAPABILITY);
}

function cms_admin_themes_can_mutate(): bool
{
    return cms_admin_themes_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_THEMES_WRITE_CAPABILITY);
}

/** @return array<string, true> */
function cms_admin_themes_allowed_actions(): array
{
    return [
        'activate' => true,
        'delete' => true,
    ];
}

function cms_admin_themes_normalize_slug(array $post): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($post['theme'] ?? '')) ?? '';
}

function cms_admin_themes_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return isset(cms_admin_themes_allowed_actions()[$action]) ? $action : '';
}

function cms_admin_themes_handle_action(ThemesModule $module, string $action, array $post): array
{
    $slug = cms_admin_themes_normalize_slug($post);

    return match ($action) {
        'activate' => $module->activateTheme($slug),
        'delete' => $module->deleteTheme($slug),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/themes',
    'view_file' => __DIR__ . '/views/themes/list.php',
    'page_title' => 'Themes',
    'active_page' => 'themes',
    'csrf_action' => 'admin_themes',
    'module_file' => __DIR__ . '/modules/themes/ThemesModule.php',
    'module_factory' => static fn (): ThemesModule => new ThemesModule(),
    'access_checker' => static fn (): bool => cms_admin_themes_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (ThemesModule $module, string $section, array $post): array {
        if (!cms_admin_themes_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Theme-Aktionen.'];
        }

        $action = cms_admin_themes_normalize_action($post['action'] ?? null);
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if (cms_admin_themes_normalize_slug($post) === '') {
            return ['success' => false, 'error' => 'Ungültiger Theme-Slug.'];
        }

        return cms_admin_themes_handle_action($module, $action, $post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
