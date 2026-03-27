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
const CMS_ADMIN_FONT_MANAGER_MAX_FAMILY_LENGTH = 120;
const CMS_ADMIN_FONT_MANAGER_FONT_SIZE_MIN = 12;
const CMS_ADMIN_FONT_MANAGER_FONT_SIZE_MAX = 24;
const CMS_ADMIN_FONT_MANAGER_LINE_HEIGHT_MIN = 1.0;
const CMS_ADMIN_FONT_MANAGER_LINE_HEIGHT_MAX = 2.5;

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
    $action = strtolower(trim($action));

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
        return trim(mb_substr($fontFamily, 0, CMS_ADMIN_FONT_MANAGER_MAX_FAMILY_LENGTH));
    }

    return trim(substr($fontFamily, 0, CMS_ADMIN_FONT_MANAGER_MAX_FAMILY_LENGTH));
}

function cms_admin_font_manager_normalize_font_key(mixed $value): string
{
    return (string) preg_replace('/[^a-z0-9_-]/', '', strtolower(trim((string) $value)));
}

function cms_admin_font_manager_normalize_font_size(mixed $value): int
{
    return max(CMS_ADMIN_FONT_MANAGER_FONT_SIZE_MIN, min(CMS_ADMIN_FONT_MANAGER_FONT_SIZE_MAX, (int) $value));
}

function cms_admin_font_manager_normalize_line_height(mixed $value): string
{
    $normalized = max(CMS_ADMIN_FONT_MANAGER_LINE_HEIGHT_MIN, min(CMS_ADMIN_FONT_MANAGER_LINE_HEIGHT_MAX, (float) $value));

    return number_format($normalized, 1, '.', '');
}

/** @return array<string, string> */
function cms_admin_font_manager_normalize_save_payload(array $post): array
{
    return [
        'heading_font' => cms_admin_font_manager_normalize_font_key($post['heading_font'] ?? 'system-ui'),
        'body_font' => cms_admin_font_manager_normalize_font_key($post['body_font'] ?? 'system-ui'),
        'font_size' => (string) cms_admin_font_manager_normalize_font_size($post['font_size'] ?? 16),
        'line_height' => cms_admin_font_manager_normalize_line_height($post['line_height'] ?? 1.6),
        'use_local_fonts' => array_key_exists('use_local_fonts', $post) ? '1' : '0',
    ];
}

/**
 * @return array{action:?string,font_id:int,google_font_family:string,settings:array<string,string>,error:string}
 */
function cms_admin_font_manager_normalize_payload(array $post): array
{
    $action = cms_admin_font_manager_normalize_action(trim((string) ($post['action'] ?? '')));
    $fontId = cms_admin_font_manager_normalize_font_id($post);
    $fontFamily = cms_admin_font_manager_normalize_google_font_family($post);
    $settings = cms_admin_font_manager_normalize_save_payload($post);
    $error = '';

    if ($action === null) {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
    } elseif ($action === 'delete_font' && $fontId <= 0) {
        $error = 'Ungültige Font-ID.';
    } elseif ($action === 'download_google_font' && $fontFamily === '') {
        $error = 'Bitte einen gültigen Google-Font-Namen angeben.';
    }

    return [
        'action' => $action,
        'font_id' => $fontId,
        'google_font_family' => $fontFamily,
        'settings' => $settings,
        'error' => $error,
    ];
}

function cms_admin_font_manager_handle_action(
    FontManagerModule $module,
    array $payload,
): array
{
    return match ($payload['action'] ?? null) {
        'save' => $module->saveSettings((array) ($payload['settings'] ?? [])),
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
        if ($normalizedPost['error'] !== '') {
            return ['success' => false, 'error' => $normalizedPost['error']];
        }

        return cms_admin_font_manager_handle_action($module, $normalizedPost);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
