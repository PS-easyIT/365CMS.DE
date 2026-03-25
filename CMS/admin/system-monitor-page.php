<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
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
function cms_admin_system_monitor_normalize_page_config(array $pageConfig): array
{
    $defaults = [
        'section' => 'info',
        'route_path' => '/admin/info',
        'view_file' => __DIR__ . '/views/system/info.php',
        'page_title' => 'Info & Diagnose',
        'active_page' => 'info',
        'page_assets' => [],
    ];

    $normalized = array_merge($defaults, $pageConfig);

    return [
        'section' => (string) ($normalized['section'] ?? $defaults['section']),
        'route_path' => (string) ($normalized['route_path'] ?? $defaults['route_path']),
        'view_file' => (string) ($normalized['view_file'] ?? $defaults['view_file']),
        'page_title' => (string) ($normalized['page_title'] ?? $defaults['page_title']),
        'active_page' => (string) ($normalized['active_page'] ?? $defaults['active_page']),
        'page_assets' => is_array($normalized['page_assets'] ?? null) ? $normalized['page_assets'] : [],
    ];
}

$systemMonitorPageConfig = cms_admin_system_monitor_normalize_page_config(array_merge(
    [
        'section' => $systemSection ?? 'info',
        'route_path' => $systemRoutePath ?? '/admin/info',
        'view_file' => $systemViewFile ?? (__DIR__ . '/views/system/info.php'),
        'page_title' => $pageTitle ?? 'Info & Diagnose',
        'active_page' => $activePage ?? 'info',
        'page_assets' => $pageAssets ?? [],
    ],
    is_array($systemMonitorPageConfig ?? null) ? $systemMonitorPageConfig : []
));

$sectionPageConfig = [
    'section' => $systemMonitorPageConfig['section'],
    'route_path' => $systemMonitorPageConfig['route_path'],
    'view_file' => $systemMonitorPageConfig['view_file'],
    'page_title' => $systemMonitorPageConfig['page_title'],
    'active_page' => $systemMonitorPageConfig['active_page'],
    'page_assets' => $systemMonitorPageConfig['page_assets'],
    'csrf_action' => 'admin_system_info',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_file' => __DIR__ . '/modules/system/SystemInfoModule.php',
    'module_factory' => static function () {
        return new SystemInfoModule();
    },
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof SystemInfoModule) {
            return ['success' => false, 'error' => 'System-Modul konnte nicht initialisiert werden.'];
        }

        return $module->handleAction($section, (string)($postData['action'] ?? ''), $postData);
    },
    'data_loader' => static function ($module): array {
        return $module instanceof SystemInfoModule ? $module->getData() : [];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
