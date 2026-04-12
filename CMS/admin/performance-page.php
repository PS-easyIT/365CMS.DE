<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\CoreModuleService;

const CMS_ADMIN_PERFORMANCE_SECTION_ACTIONS = [
    'overview' => [],
    'cache' => ['clear_all_cache', 'clear_file_cache', 'clear_opcache', 'warmup_opcache', 'save_settings', 'save_cache_settings'],
    'database' => ['optimize_database', 'repair_tables', 'save_settings'],
    'media' => ['convert_media_to_webp', 'save_settings', 'save_media_settings'],
    'sessions' => ['clear_expired_sessions', 'save_settings', 'save_session_settings'],
    'settings' => ['save_settings'],
];

const CMS_ADMIN_PERFORMANCE_PAGE_CONFIGS = [
    'overview' => [
        'route_path' => '/admin/performance',
        'view_file' => __DIR__ . '/views/seo/performance.php',
        'page_title' => 'Performance',
        'active_page' => 'performance',
    ],
    'cache' => [
        'route_path' => '/admin/performance-cache',
        'view_file' => __DIR__ . '/views/performance/cache.php',
        'page_title' => 'Performance · Cache-Verwaltung',
        'active_page' => 'performance-cache',
    ],
    'database' => [
        'route_path' => '/admin/performance-database',
        'view_file' => __DIR__ . '/views/performance/database.php',
        'page_title' => 'Performance · Datenbank-Wartung',
        'active_page' => 'performance-database',
    ],
    'media' => [
        'route_path' => '/admin/performance-media',
        'view_file' => __DIR__ . '/views/performance/media.php',
        'page_title' => 'Performance · Medien-Optimierung',
        'active_page' => 'performance-media',
    ],
    'sessions' => [
        'route_path' => '/admin/performance-sessions',
        'view_file' => __DIR__ . '/views/performance/sessions.php',
        'page_title' => 'Performance · Session-Verwaltung',
        'active_page' => 'performance-sessions',
    ],
    'settings' => [
        'route_path' => '/admin/performance-settings',
        'view_file' => __DIR__ . '/views/performance/settings.php',
        'page_title' => 'Performance · Einstellungen',
        'active_page' => 'performance-settings',
    ],
];

function cms_admin_performance_can_access(): bool
{
    return \CMS\Auth::instance()->isAdmin() && \CMS\Auth::instance()->hasCapability('manage_settings');
}

function cms_admin_performance_normalize_section(string $section): string
{
    $section = trim($section);

    return array_key_exists($section, CMS_ADMIN_PERFORMANCE_SECTION_ACTIONS) ? $section : 'overview';
}

function cms_admin_performance_normalize_action(string $section, mixed $action): string
{
    $section = cms_admin_performance_normalize_section($section);
    $normalizedAction = trim((string) $action);
    $allowedActions = CMS_ADMIN_PERFORMANCE_SECTION_ACTIONS[$section] ?? [];

    return in_array($normalizedAction, $allowedActions, true) ? $normalizedAction : '';
}

function cms_admin_performance_can_run_action(string $section, string $action): bool
{
    return $action !== ''
        && array_key_exists(cms_admin_performance_normalize_section($section), CMS_ADMIN_PERFORMANCE_SECTION_ACTIONS)
        && \CMS\Auth::instance()->hasCapability('manage_settings');
}

/**
 * @param array<string, mixed> $pageConfig
 * @return array{
 *     section: string,
 *     route_path: string,
 *     view_file: string,
 *     page_title: string,
 *     active_page: string,
 *     page_assets: array<int|string, mixed>
 * }
 */
function cms_admin_performance_normalize_page_config(array $pageConfig): array
{
    $section = cms_admin_performance_normalize_section((string) ($pageConfig['section'] ?? 'overview'));
    $defaults = CMS_ADMIN_PERFORMANCE_PAGE_CONFIGS[$section] ?? CMS_ADMIN_PERFORMANCE_PAGE_CONFIGS['overview'];

    return [
        'section' => $section,
        'route_path' => (string) ($defaults['route_path'] ?? '/admin/performance'),
        'view_file' => (string) ($defaults['view_file'] ?? (__DIR__ . '/views/seo/performance.php')),
        'page_title' => (string) ($defaults['page_title'] ?? 'Performance'),
        'active_page' => (string) ($defaults['active_page'] ?? 'performance'),
        'page_assets' => is_array($pageConfig['page_assets'] ?? null) ? $pageConfig['page_assets'] : [],
    ];
}

/**
 * @param array{
 *     section: string,
 *     route_path: string,
 *     view_file: string,
 *     page_title: string,
 *     active_page: string,
 *     page_assets: array<int|string, mixed>
 * } $performancePageConfig
 * @return array<string, mixed>
 */
function cms_admin_performance_build_section_page_config(array $performancePageConfig): array
{
    return [
        'section' => $performancePageConfig['section'],
        'route_path' => $performancePageConfig['route_path'],
        'view_file' => $performancePageConfig['view_file'],
        'page_title' => $performancePageConfig['page_title'],
        'active_page' => $performancePageConfig['active_page'],
        'page_assets' => $performancePageConfig['page_assets'],
        'csrf_action' => 'admin_performance',
        'guard_constant' => 'CMS_ADMIN_PERFORMANCE_VIEW',
        'module_file' => __DIR__ . '/modules/seo/PerformanceModule.php',
        'module_factory' => static function () {
            return new PerformanceModule();
        },
        'access_checker' => static function () use ($performancePageConfig): bool {
            return cms_admin_performance_can_access()
                && (!class_exists(CoreModuleService::class) || CoreModuleService::getInstance()->isAdminPageEnabled((string) ($performancePageConfig['active_page'] ?? 'performance')));
        },
        'post_handler' => static function ($module, string $section, array $postData): array {
            if (!$module instanceof PerformanceModule) {
                return ['success' => false, 'error' => 'Performance-Modul konnte nicht initialisiert werden.'];
            }

            $normalizedSection = cms_admin_performance_normalize_section($section);
            $action = cms_admin_performance_normalize_action($normalizedSection, $postData['action'] ?? '');
            if ($action === '') {
                return ['success' => false, 'error' => 'Unbekannte Aktion.'];
            }

            if (!cms_admin_performance_can_run_action($normalizedSection, $action)) {
                return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
            }

            return $module->handleAction($normalizedSection, $action, $postData);
        },
        'data_loader' => static function ($module) use ($performancePageConfig): array {
            return $module instanceof PerformanceModule
                ? $module->getSectionData((string) ($performancePageConfig['section'] ?? 'overview'))
                : [];
        },
    ];
}

$performancePageConfig = cms_admin_performance_normalize_page_config(array_merge(
    [
        'section' => $performanceSection ?? 'overview',
        'route_path' => $performanceRoutePath ?? '/admin/performance',
        'view_file' => $performanceViewFile ?? (__DIR__ . '/views/seo/performance.php'),
        'page_title' => $pageTitle ?? 'Performance',
        'active_page' => $activePage ?? 'performance',
        'page_assets' => $pageAssets ?? [],
    ],
    is_array($performancePageConfig ?? null) ? $performancePageConfig : []
));

$performancePageConfig['section'] = cms_admin_performance_normalize_section((string) ($performancePageConfig['section'] ?? 'overview'));

$sectionPageConfig = cms_admin_performance_build_section_page_config($performancePageConfig);

require __DIR__ . '/partials/section-page-shell.php';
