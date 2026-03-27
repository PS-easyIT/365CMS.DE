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

function cms_admin_theme_editor_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_THEME_EDITOR_CAPABILITY);
}

function cms_admin_theme_editor_page_title(): string
{
    return 'Theme Editor';
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
 * @return array{activeThemeSlug:string,customizerPath:?string,reason:string,links:array{themes:string,explorer:string}}
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
            'reason' => 'Der aktive Theme-Pfad konnte nicht sicher aufgelöst werden.',
            'links' => $links,
        ];
    }

    $candidatePath = $realThemePath . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'customizer.php';
    if (!is_file($candidatePath)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'reason' => 'Das aktive Theme stellt keine Datei admin/customizer.php bereit.',
            'links' => $links,
        ];
    }

    $realCandidatePath = realpath($candidatePath);
    if ($realCandidatePath === false || !is_file($realCandidatePath)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'reason' => 'Die Customizer-Datei des aktiven Themes ist nicht lesbar oder konnte nicht sicher aufgelöst werden.',
            'links' => $links,
        ];
    }

    $realThemePrefix = rtrim($realThemePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($realCandidatePath, $realThemePrefix)) {
        return [
            'activeThemeSlug' => $activeThemeSlug,
            'customizerPath' => null,
            'reason' => 'Die aufgelöste Customizer-Datei liegt außerhalb des aktiven Theme-Verzeichnisses.',
            'links' => $links,
        ];
    }

    return [
        'activeThemeSlug' => $activeThemeSlug,
        'customizerPath' => $realCandidatePath,
        'reason' => '',
        'links' => $links,
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
    'route_path' => '/admin/theme-editor',
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
