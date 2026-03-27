<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Explorer – Entry Point
 * Route: /admin/theme-explorer
 */

use CMS\Auth;

const CMS_ADMIN_THEME_EXPLORER_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEME_EXPLORER_ALERT_SESSION_KEY = 'admin_theme_explorer_alert';

function cms_admin_theme_explorer_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_THEME_EXPLORER_CAPABILITY);
}

if (!cms_admin_theme_explorer_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/themes/ThemeEditorModule.php';

function cms_admin_theme_explorer_allowed_actions(): array
{
    return ['save_file'];
}

function cms_admin_theme_explorer_normalize_action(mixed $action): string
{
    $action = strtolower(trim((string) $action));

    return in_array($action, cms_admin_theme_explorer_allowed_actions(), true) ? $action : '';
}

function cms_admin_theme_explorer_normalize_file(mixed $file): string
{
    $file = is_scalar($file) ? (string) $file : '';
    $file = preg_replace('/[\x00-\x1F\x7F]/u', '', $file) ?? '';

    return trim($file);
}

/**
 * @return array{action:string,file:string,content:string,error:string}
 */
function cms_admin_theme_explorer_normalize_payload(array $post): array
{
    $action = cms_admin_theme_explorer_normalize_action($post['action'] ?? '');
    $file = cms_admin_theme_explorer_normalize_file($post['file'] ?? '');
    $content = (string) ($post['content'] ?? '');
    $error = '';

    if ($action === '') {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
    } elseif ($file === '') {
        $error = 'Kein gültiger Dateipfad angegeben.';
    }

    return [
        'action' => $action,
        'file' => $file,
        'content' => $content,
        'error' => $error,
    ];
}

function cms_admin_theme_explorer_resolve_redirect_path(mixed $result = null): string
{
    $file = '';
    if (is_array($result)) {
        $file = cms_admin_theme_explorer_normalize_file($result['current_file'] ?? '');
    }

    if ($file === '') {
        $file = cms_admin_theme_explorer_normalize_file($_POST['file'] ?? $_GET['file'] ?? '');
    }

    if ($file === '') {
        return '/admin/theme-explorer';
    }

    return '/admin/theme-explorer?file=' . rawurlencode($file);
}

function cms_admin_theme_explorer_handle_action(ThemeEditorModule $module, array $payload): array
{
    $result = match ($payload['action']) {
        'save_file' => $module->saveFile((string) ($payload['file'] ?? ''), (string) ($payload['content'] ?? '')),
        default => ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'],
    };

    $result['current_file'] = cms_admin_theme_explorer_normalize_file($payload['file'] ?? '');

    return $result;
}

$sectionPageConfig = [
    'route_path' => '/admin/theme-explorer',
    'view_file' => __DIR__ . '/views/themes/editor.php',
    'page_title' => 'Theme Explorer',
    'active_page' => 'theme-explorer',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-theme-explorer.js'),
        ],
    ],
    'csrf_action' => 'admin_theme_explorer',
    'module_file' => __DIR__ . '/modules/themes/ThemeEditorModule.php',
    'module_factory' => static fn (): ThemeEditorModule => new ThemeEditorModule(),
    'data_loader' => static function (ThemeEditorModule $module): array {
        $currentFile = cms_admin_theme_explorer_normalize_file($_GET['file'] ?? '');

        return $module->getData($currentFile);
    },
    'request_context_resolver' => static function (ThemeEditorModule $module): array {
        $currentFile = cms_admin_theme_explorer_normalize_file($_GET['file'] ?? '');

        return [
            'data' => $module->getData($currentFile),
        ];
    },
    'post_handler' => static function (ThemeEditorModule $module, string $section, array $post): array {
        $payload = cms_admin_theme_explorer_normalize_payload($post);
        if ($payload['error'] !== '') {
            return [
                'success' => false,
                'error' => $payload['error'],
                'current_file' => $payload['file'],
            ];
        }

        return cms_admin_theme_explorer_handle_action($module, $payload);
    },
    'redirect_path_resolver' => static fn ($module, string $section, $result): string => cms_admin_theme_explorer_resolve_redirect_path($result),
    'access_checker' => static fn (): bool => cms_admin_theme_explorer_can_access(),
    'access_denied_route' => '/',
    'alert_session_key' => CMS_ADMIN_THEME_EXPLORER_ALERT_SESSION_KEY,
    'invalid_token_message' => 'Sicherheitstoken ungültig.',
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
