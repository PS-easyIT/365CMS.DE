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
const CMS_ADMIN_THEME_EXPLORER_WRITE_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEME_EXPLORER_ALERT_SESSION_KEY = 'admin_theme_explorer_alert';
const CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH = '/admin/theme-explorer';
const CMS_ADMIN_THEME_EXPLORER_MAX_FILE_LENGTH = 260;
const CMS_ADMIN_THEME_EXPLORER_MAX_CONTENT_BYTES = 1048576;

function cms_admin_theme_explorer_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_THEME_EXPLORER_CAPABILITY);
}

function cms_admin_theme_explorer_can_mutate(): bool
{
    return cms_admin_theme_explorer_can_access() && Auth::instance()->hasCapability(CMS_ADMIN_THEME_EXPLORER_WRITE_CAPABILITY);
}

if (!cms_admin_theme_explorer_can_access()) {
    header('Location: /');
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

function cms_admin_theme_explorer_substring(string $value, int $start, ?int $length = null): string
{
    if (function_exists('mb_substr')) {
        return $length === null
            ? (string) mb_substr($value, $start, null, 'UTF-8')
            : (string) mb_substr($value, $start, $length, 'UTF-8');
    }

    return $length === null ? substr($value, $start) : substr($value, $start, $length);
}

function cms_admin_theme_explorer_string_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return (int) mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

/** @return array{file:string,truncated:bool,raw_length:int} */
function cms_admin_theme_explorer_prepare_file(mixed $file): array
{
    $file = is_scalar($file) ? (string) $file : '';
    $file = preg_replace('/[\x00-\x1F\x7F]/u', '', $file) ?? '';
    $file = str_replace('\\', '/', $file);
    $file = preg_replace('#/+#', '/', $file) ?? '';
    $file = ltrim(trim($file), '/');
    $rawLength = cms_admin_theme_explorer_string_length($file);
    $normalizedFile = trim(cms_admin_theme_explorer_substring($file, 0, CMS_ADMIN_THEME_EXPLORER_MAX_FILE_LENGTH));

    return [
        'file' => $normalizedFile,
        'truncated' => $rawLength > CMS_ADMIN_THEME_EXPLORER_MAX_FILE_LENGTH,
        'raw_length' => $rawLength,
    ];
}

function cms_admin_theme_explorer_normalize_file(mixed $file): string
{
    return cms_admin_theme_explorer_prepare_file($file)['file'];
}

function cms_admin_theme_explorer_normalize_content(mixed $content): string
{
    $content = (string) $content;

    return str_replace("\0", '', $content);
}

function cms_admin_theme_explorer_current_file(): string
{
    return cms_admin_theme_explorer_normalize_file($_GET['file'] ?? '');
}

/** @return array{file:string,warning:string} */
function cms_admin_theme_explorer_current_file_context(): array
{
    $preparedFile = cms_admin_theme_explorer_prepare_file($_GET['file'] ?? '');
    if (!empty($preparedFile['truncated'])) {
        return [
            'file' => '',
            'warning' => 'Der angeforderte Dateipfad überschreitet das sichere Limit von ' . CMS_ADMIN_THEME_EXPLORER_MAX_FILE_LENGTH . ' Zeichen und wurde nicht geladen.',
        ];
    }

    return [
        'file' => (string) ($preparedFile['file'] ?? ''),
        'warning' => '',
    ];
}

function cms_admin_theme_explorer_apply_current_file_context(array $data, array $context): array
{
    $warning = trim((string) ($context['warning'] ?? ''));
    if ($warning !== '' && trim((string) ($data['fileWarning'] ?? '')) === '') {
        $data['fileWarning'] = $warning;
    }

    return $data;
}

/**
 * @return array{action:string,file:string,content:string,error:string,errorCode:string,details:list<string>,context:array<string,mixed>}
 */
function cms_admin_theme_explorer_normalize_payload(array $post): array
{
    $action = cms_admin_theme_explorer_normalize_action($post['action'] ?? '');
    $preparedFile = cms_admin_theme_explorer_prepare_file($post['file'] ?? '');
    $file = (string) ($preparedFile['file'] ?? '');
    $content = cms_admin_theme_explorer_normalize_content($post['content'] ?? '');
    $error = '';
    $errorCode = '';
    $details = [];
    $context = [
        'action' => $action,
        'file' => $file,
    ];

    if ($action === '') {
        $error = 'Unbekannte oder nicht erlaubte Aktion.';
        $errorCode = 'theme_explorer_invalid_action';
        $details[] = 'Aktion: (leer/ungültig)';
    } elseif (!empty($preparedFile['truncated'])) {
        $error = 'Der Dateipfad überschreitet das sichere Limit und wurde nicht verarbeitet.';
        $errorCode = 'theme_explorer_file_too_long';
        $details[] = 'Browser-Limit: ' . CMS_ADMIN_THEME_EXPLORER_MAX_FILE_LENGTH . ' Zeichen';
        $context['raw_length'] = (int) ($preparedFile['raw_length'] ?? 0);
    } elseif ($file === '') {
        $error = 'Kein gültiger Dateipfad angegeben.';
        $errorCode = 'theme_explorer_missing_file';
        $details[] = 'Datei: (leer)';
    } elseif (strlen($content) > CMS_ADMIN_THEME_EXPLORER_MAX_CONTENT_BYTES) {
        $error = 'Der Editor-Inhalt überschreitet das sichere Browser-Limit.';
        $errorCode = 'theme_explorer_content_too_large';
        $details[] = 'Datei: ' . $file;
        $details[] = 'Browser-Limit: ' . CMS_ADMIN_THEME_EXPLORER_MAX_CONTENT_BYTES . ' Bytes';
        $context['content_bytes'] = strlen($content);
    } elseif (str_contains((string) ($post['content'] ?? ''), "\0")) {
        $error = 'Der Editor-Inhalt enthält ungültige Binärdaten.';
        $errorCode = 'theme_explorer_binary_content';
        $details[] = 'Datei: ' . $file;
    }

    return [
        'action' => $action,
        'file' => $file,
        'content' => $content,
        'error' => $error,
        'errorCode' => $errorCode,
        'details' => $details,
        'context' => $context,
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

function cms_admin_theme_explorer_build_failure_result(string $message, string $errorCode, array $details = [], array $context = []): array
{
    $normalizedContext = array_merge([
        'source' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH,
        'route' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH,
    ], $context);

    return [
        'success' => false,
        'error' => $message,
        'details' => array_values(array_filter(array_map(static fn (mixed $detail): string => trim((string) $detail), $details), static fn (string $detail): bool => $detail !== '')),
        'error_details' => [
            'code' => $errorCode,
            'data' => ['route' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH],
            'context' => $normalizedContext,
        ],
        'report_payload' => [
            'title' => 'Theme Explorer · ' . $errorCode,
            'message' => $message,
            'error_code' => $errorCode,
            'error_data' => ['route' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH],
            'context' => $normalizedContext,
            'source_url' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH,
        ],
    ];
}

$sectionPageConfig = [
    'route_path' => CMS_ADMIN_THEME_EXPLORER_ROUTE_PATH,
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
        $currentFileContext = cms_admin_theme_explorer_current_file_context();
        $data = $module->getData((string) ($currentFileContext['file'] ?? ''));

        return cms_admin_theme_explorer_apply_current_file_context($data, $currentFileContext);
    },
    'request_context_resolver' => static function (ThemeEditorModule $module): array {
        $currentFileContext = cms_admin_theme_explorer_current_file_context();

        return [
            'data' => cms_admin_theme_explorer_apply_current_file_context($module->getData((string) ($currentFileContext['file'] ?? '')), $currentFileContext),
        ];
    },
    'post_handler' => static function (ThemeEditorModule $module, string $section, array $post): array {
        if (!cms_admin_theme_explorer_can_mutate()) {
            return cms_admin_theme_explorer_build_failure_result(
                'Keine Berechtigung für Theme-Explorer-Änderungen.',
                'theme_explorer_permission_denied',
                ['Capability: ' . CMS_ADMIN_THEME_EXPLORER_WRITE_CAPABILITY]
            );
        }

        $payload = cms_admin_theme_explorer_normalize_payload($post);
        if ($payload['error'] !== '') {
            $result = cms_admin_theme_explorer_build_failure_result(
                $payload['error'],
                (string) ($payload['errorCode'] !== '' ? $payload['errorCode'] : 'theme_explorer_invalid_payload'),
                $payload['details'] !== [] ? (array) $payload['details'] : ['Aktion: ' . (string) ($payload['action'] ?? '(leer)'), 'Datei: ' . ((string) ($payload['file'] ?? '') !== '' ? (string) ($payload['file'] ?? '') : '(leer)')],
                is_array($payload['context'] ?? null) ? (array) $payload['context'] : ['action' => (string) ($payload['action'] ?? ''), 'file' => (string) ($payload['file'] ?? '')]
            );
            $result['current_file'] = $payload['file'];

            return $result;
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
