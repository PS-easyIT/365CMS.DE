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
        default => 'Lege eine sichere Datei admin/customizer.php innerhalb des aktiven Theme-Verzeichnisses an oder nutze den Theme Explorer für die Vorbereitung.',
    };
}

/** @return array{themes:string, explorer:string} */
function cms_admin_theme_editor_fallback_links(): array
{
    return [
        'themes' => SITE_URL . '/admin/themes',
        'explorer' => SITE_URL . '/admin/theme-explorer',
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
            'themeEditorState' => $state,
        ],
    ];
}

$sectionPageConfig = [
    'route_path' => CMS_ADMIN_THEME_EDITOR_ROUTE_PATH,
    'view_file' => CMS_ADMIN_THEME_EDITOR_FALLBACK_VIEW,
    'page_title' => cms_admin_theme_editor_page_title(),
    'active_page' => 'theme-editor',
    'page_assets' => [],
    'csrf_action' => 'admin_theme_editor',
    'module_factory' => static fn (): ThemeManager => ThemeManager::instance(),
    'data_loader' => static fn (ThemeManager $themeManager): array => cms_admin_theme_editor_resolve_state($themeManager),
    'request_context_resolver' => static fn (ThemeManager $themeManager): array => cms_admin_theme_editor_runtime_context($themeManager),
    'access_checker' => static fn (): bool => cms_admin_theme_editor_can_access(),
    'access_denied_route' => '/',
];

require __DIR__ . '/partials/section-page-shell.php';
