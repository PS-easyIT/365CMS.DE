<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme Editor – Entry Point
 * Route: /admin/theme-editor
 */

use CMS\Auth;
use CMS\ThemeManager;

const CMS_ADMIN_THEME_EDITOR_CAPABILITY = 'manage_settings';
const CMS_ADMIN_THEME_EDITOR_FALLBACK_VIEW = __DIR__ . '/views/themes/customizer-missing.php';
const CMS_ADMIN_THEME_EDITOR_ROUTE_PATH = '/admin/theme-editor';
const CMS_ADMIN_THEME_EDITOR_BLOCKED_FUNCTIONS = [
    'eval',
    'exec',
    'system',
    'shell_exec',
    'passthru',
    'proc_open',
    'popen',
    'base64_decode',
];

function cms_admin_theme_editor_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_THEME_EDITOR_CAPABILITY);
}

function cms_admin_theme_editor_page_title(): string
{
    return 'Theme Editor';
}

function cms_admin_theme_editor_reason_hint(string $reasonCode): string
{
    return match ($reasonCode) {
        'theme_path_unresolved' => 'Prüfe zuerst, ob das aktive Theme-Verzeichnis korrekt vorhanden und für PHP auflösbar ist.',
        'customizer_unreadable' => 'Die erwartete Customizer-Datei existiert möglicherweise, ist aber nicht lesbar oder liegt nicht sauber im Theme-Pfad.',
        'customizer_outside_theme' => 'Der gefundene Customizer-Pfad wurde aus Sicherheitsgründen verworfen, weil er außerhalb des aktiven Theme-Verzeichnisses liegt.',
        'customizer_syntax_invalid' => 'Behebe zuerst den PHP-Syntaxfehler in admin/customizer.php oder nutze den Theme Explorer für eine sichere Vorprüfung.',
        'customizer_unsafe_code' => 'Entferne riskante PHP-Funktionsaufrufe aus admin/customizer.php, bevor der Theme Editor den Customizer wieder direkt einbindet.',
        default => 'Lege eine sichere Datei admin/customizer.php innerhalb des aktiven Theme-Verzeichnisses an oder nutze den Theme Explorer für die Vorbereitung.',
    };
}

/** @return array{ok:bool,reason:string,reasonCode:string} */
function cms_admin_theme_editor_validate_customizer_file(string $customizerPath): array
{
    if (!is_file($customizerPath) || !is_readable($customizerPath)) {
        return [
            'ok' => false,
            'reason' => 'Die Customizer-Datei des aktiven Themes ist nicht lesbar oder fehlt.',
            'reasonCode' => 'customizer_unreadable',
        ];
    }

    $content = file_get_contents($customizerPath);
    if (!is_string($content) || $content === '') {
        return [
            'ok' => false,
            'reason' => 'Die Customizer-Datei des aktiven Themes konnte nicht sicher gelesen werden.',
            'reasonCode' => 'customizer_unreadable',
        ];
    }

    try {
        $tokens = token_get_all($content, TOKEN_PARSE);
    } catch (\ParseError) {
        return [
            'ok' => false,
            'reason' => 'Die Customizer-Datei des aktiven Themes enthält einen PHP-Syntaxfehler und wurde aus Sicherheitsgründen nicht eingebunden.',
            'reasonCode' => 'customizer_syntax_invalid',
        ];
    }

    foreach ($tokens as $token) {
        if (!is_array($token) || ($token[0] ?? null) !== T_STRING) {
            continue;
        }

        $functionName = strtolower((string) ($token[1] ?? ''));
        if (in_array($functionName, CMS_ADMIN_THEME_EDITOR_BLOCKED_FUNCTIONS, true)) {
            return [
                'ok' => false,
                'reason' => 'Die Customizer-Datei des aktiven Themes enthält unsichere PHP-Funktionsaufrufe und wurde aus Sicherheitsgründen nicht eingebunden.',
                'reasonCode' => 'customizer_unsafe_code',
            ];
        }
    }

    return [
        'ok' => true,
        'reason' => '',
        'reasonCode' => 'ok',
    ];
}

/** @return array{themes:string, explorer:string} */
function cms_admin_theme_editor_fallback_links(): array
{
    return [
        'themes' => '/admin/themes',
        'explorer' => '/admin/theme-explorer',
    ];
}

/**
 * @return array{activeThemeSlug:string,customizerPath:?string,expectedCustomizerPath:string,reason:string,reasonCode:string,reasonHint:string,links:array{themes:string,explorer:string},constraints:array<string,int|string>}
 */
function cms_admin_theme_editor_resolve_state(ThemeManager $themeManager): array
{
    $activeThemeSlug = (string) $themeManager->getActiveThemeSlug();
    $links = cms_admin_theme_editor_fallback_links();
    $themePath = $themeManager->getThemePath();
    $realThemePath = realpath($themePath);
    if ($realThemePath === false) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'expectedCustomizerPath' => 'admin/customizer.php',
            'reason' => 'Der aktive Theme-Pfad konnte nicht sicher aufgelöst werden.',
            'reasonCode' => 'theme_path_unresolved',
            'reasonHint' => cms_admin_theme_editor_reason_hint('theme_path_unresolved'),
            'links' => $links,
            'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
        ];
    }

    $candidatePath = $realThemePath . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'customizer.php';
    if (!is_file($candidatePath)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'expectedCustomizerPath' => str_replace('\\', '/', substr($candidatePath, strlen(rtrim($realThemePath, DIRECTORY_SEPARATOR)) + 1)),
            'reason' => 'Das aktive Theme stellt keine Datei admin/customizer.php bereit.',
            'reasonCode' => 'customizer_missing',
            'reasonHint' => cms_admin_theme_editor_reason_hint('customizer_missing'),
            'links' => $links,
            'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
        ];
    }

    $realCandidatePath = realpath($candidatePath);
    if ($realCandidatePath === false || !is_file($realCandidatePath)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'expectedCustomizerPath' => 'admin/customizer.php',
            'reason' => 'Die Customizer-Datei des aktiven Themes ist nicht lesbar oder konnte nicht sicher aufgelöst werden.',
            'reasonCode' => 'customizer_unreadable',
            'reasonHint' => cms_admin_theme_editor_reason_hint('customizer_unreadable'),
            'links' => $links,
            'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
        ];
    }

    $realThemePrefix = rtrim($realThemePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($realCandidatePath, $realThemePrefix)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'expectedCustomizerPath' => 'admin/customizer.php',
            'reason' => 'Die aufgelöste Customizer-Datei liegt außerhalb des aktiven Theme-Verzeichnisses.',
            'reasonCode' => 'customizer_outside_theme',
            'reasonHint' => cms_admin_theme_editor_reason_hint('customizer_outside_theme'),
            'links' => $links,
            'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
        ];
    }

    $validation = cms_admin_theme_editor_validate_customizer_file($realCandidatePath);
    if (!($validation['ok'] ?? false)) {
        $reasonCode = (string) ($validation['reasonCode'] ?? 'customizer_unreadable');

        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'expectedCustomizerPath' => 'admin/customizer.php',
            'reason' => (string) ($validation['reason'] ?? 'Die Customizer-Datei des aktiven Themes konnte nicht sicher geprüft werden.'),
            'reasonCode' => $reasonCode,
            'reasonHint' => cms_admin_theme_editor_reason_hint($reasonCode),
            'links' => $links,
            'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
        ];
    }

    return [
        'activeThemeSlug' => $activeThemeSlug,
        'customizerPath' => $realCandidatePath,
        'expectedCustomizerPath' => 'admin/customizer.php',
        'reason' => '',
        'reasonCode' => 'ok',
        'reasonHint' => '',
        'links' => $links,
        'constraints' => ['expected_relative_path' => 'admin/customizer.php', 'fallback_view' => 'views/themes/customizer-missing.php'],
    ];
}

function cms_admin_theme_editor_runtime_context(ThemeManager $themeManager): array
{
    $state = cms_admin_theme_editor_resolve_state($themeManager);
    $customizerPath = (string) ($state['customizerPath'] ?? '');

    return [
        'view_file' => $customizerPath !== '' ? $customizerPath : CMS_ADMIN_THEME_EDITOR_FALLBACK_VIEW,
        'data' => $state,
        'template_vars' => [
            'embedInAdminLayout' => true,
            'themeEditorState' => $state,
        ],
    ];
}

/**
 * Der Theme-Customizer verarbeitet POST-Requests im eingebundenen Theme-Fragment selbst.
 * Die Section-Shell darf daher nicht vorab redirecten oder eine Unknown-Action erzwingen.
 *
 * @return array{success: bool, render_inline: bool, message: string}
 */
function cms_admin_theme_editor_inline_post_handler(): array
{
    return [
        'success' => true,
        'render_inline' => true,
        'message' => '',
    ];
}

$sectionPageConfig = [
    'route_path' => CMS_ADMIN_THEME_EDITOR_ROUTE_PATH,
    'view_file' => CMS_ADMIN_THEME_EDITOR_FALLBACK_VIEW,
    'page_title' => cms_admin_theme_editor_page_title(),
    'active_page' => 'theme-editor',
    'page_assets' => [],
    'csrf_action' => 'theme_customizer',
    'module_factory' => static fn (): ThemeManager => ThemeManager::instance(),
    'data_loader' => static fn (ThemeManager $themeManager): array => cms_admin_theme_editor_resolve_state($themeManager),
    'request_context_resolver' => static fn (ThemeManager $themeManager): array => cms_admin_theme_editor_runtime_context($themeManager),
    'post_handler' => static fn (): array => cms_admin_theme_editor_inline_post_handler(),
    'access_checker' => static fn (): bool => cms_admin_theme_editor_can_access(),
    'access_denied_route' => '/',
];

require __DIR__ . '/partials/section-page-shell.php';
