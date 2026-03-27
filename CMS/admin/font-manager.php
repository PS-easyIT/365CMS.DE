<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Font Manager – Entry Point
 * Route: /admin/font-manager
 */

use CMS\Auth;

const CMS_ADMIN_FONT_MANAGER_READ_CAPABILITY = 'manage_settings';
const CMS_ADMIN_FONT_MANAGER_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_font_manager_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability(CMS_ADMIN_FONT_MANAGER_READ_CAPABILITY);
}

function cms_admin_font_manager_can_mutate(): bool
{
    return cms_admin_font_manager_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_FONT_MANAGER_WRITE_CAPABILITY);
}

/**
 * @return list<string>
 */
function cms_admin_font_manager_allowed_actions(): array
{
    return [
        'save',
        'scan_theme_fonts',
        'delete_font',
        'download_google_font',
        'download_detected_fonts',
    ];
}

function cms_admin_font_manager_normalize_action(string $action): ?string
{
    $action = trim($action);

    return in_array($action, cms_admin_font_manager_allowed_actions(), true) ? $action : null;
}

function cms_admin_font_manager_normalize_font_id(array $post): int
{
    return max(0, (int) ($post['font_id'] ?? 0));
}

function cms_admin_font_manager_normalize_google_font_family(array $post): string
{
    $fontFamily = trim((string) ($post['google_font_family'] ?? ''));
    $fontFamily = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $fontFamily) ?? '';
    $fontFamily = preg_replace('/\s+/u', ' ', $fontFamily) ?? '';

    if (function_exists('mb_substr')) {
        return trim(mb_substr($fontFamily, 0, 120));
    }

    return trim(substr($fontFamily, 0, 120));
}

/**
 * @return array{action:?string,font_id:int,google_font_family:string}
 */
function cms_admin_font_manager_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_font_manager_normalize_action(trim((string) ($post['action'] ?? ''))),
        'font_id' => cms_admin_font_manager_normalize_font_id($post),
        'google_font_family' => cms_admin_font_manager_normalize_google_font_family($post),
    ];
}

function cms_admin_font_manager_handle_action(
    FontManagerModule $module,
    array $payload,
    array $post
): array
{
    return match ($payload['action'] ?? null) {
        'save' => $module->saveSettings($post),
        'scan_theme_fonts' => $module->scanThemeFonts(),
        'delete_font' => $module->deleteCustomFont((int) ($payload['font_id'] ?? 0)),
        'download_google_font' => $module->downloadGoogleFont((string) ($payload['google_font_family'] ?? '')),
        'download_detected_fonts' => $module->downloadDetectedFonts(),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/font-manager',
    'view_file' => __DIR__ . '/views/themes/fonts.php',
    'page_title' => 'Font Manager',
    'active_page' => 'font-manager',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-font-manager.js'),
        ],
    ],
    'csrf_action' => 'admin_font_manager',
    'module_file' => __DIR__ . '/modules/themes/FontManagerModule.php',
    'module_factory' => static fn (): FontManagerModule => new FontManagerModule(),
    'data_loader' => static fn (FontManagerModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => cms_admin_font_manager_can_access(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
    'post_handler' => static function (FontManagerModule $module, string $section, array $post): array {
        if (!cms_admin_font_manager_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Font-Änderungen.'];
        }

        $normalizedPost = cms_admin_font_manager_normalize_payload($post);
        if ($normalizedPost['action'] === null) {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        return cms_admin_font_manager_handle_action($module, $normalizedPost, $post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
