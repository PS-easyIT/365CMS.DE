<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const CMS_ADMIN_SYSTEM_MONITOR_SECTION_ACTIONS = [
    'info' => [],
    'diagnose' => ['clear_cache', 'optimize_db', 'clear_logs', 'create_tables', 'repair_tables'],
    'response-time' => [],
    'disk' => [],
    'scheduled-tasks' => [],
    'health-check' => [],
    'email-alerts' => ['save_monitoring_alerts', 'send_monitoring_test_email'],
    'cron' => [],
];

const CMS_ADMIN_SYSTEM_MONITOR_PAGE_CONFIGS = [
    'info' => [
        'route_path' => '/admin/info',
        'view_file' => __DIR__ . '/views/system/info.php',
        'page_title' => 'Info',
        'active_page' => 'info',
    ],
    'diagnose' => [
        'route_path' => '/admin/diagnose',
        'view_file' => __DIR__ . '/views/system/diagnose.php',
        'page_title' => 'Diagnose',
        'active_page' => 'diagnose',
    ],
    'response-time' => [
        'route_path' => '/admin/monitor-response-time',
        'view_file' => __DIR__ . '/views/system/response-time.php',
        'page_title' => 'Monitoring · Response Time',
        'active_page' => 'monitor-response-time',
    ],
    'disk' => [
        'route_path' => '/admin/monitor-disk-usage',
        'view_file' => __DIR__ . '/views/system/disk-usage.php',
        'page_title' => 'Monitoring · Disk Usage',
        'active_page' => 'monitor-disk-usage',
    ],
    'scheduled-tasks' => [
        'route_path' => '/admin/monitor-scheduled-tasks',
        'view_file' => __DIR__ . '/views/system/scheduled-tasks.php',
        'page_title' => 'Monitoring · Scheduled Tasks',
        'active_page' => 'monitor-scheduled-tasks',
    ],
    'health-check' => [
        'route_path' => '/admin/monitor-health-check',
        'view_file' => __DIR__ . '/views/system/health-check.php',
        'page_title' => 'Monitoring · Health Check',
        'active_page' => 'monitor-health-check',
    ],
    'email-alerts' => [
        'route_path' => '/admin/monitor-email-alerts',
        'view_file' => __DIR__ . '/views/system/email-alerts.php',
        'page_title' => 'Monitoring · E-Mail-Benachrichtigungen',
        'active_page' => 'monitor-email-alerts',
    ],
    'cron' => [
        'route_path' => '/admin/monitor-cron-status',
        'view_file' => __DIR__ . '/views/system/cron-status.php',
        'page_title' => 'Monitoring · Cron-Status',
        'active_page' => 'monitor-cron-status',
    ],
];

function cms_admin_system_monitor_can_access(): bool
{
    return \CMS\Auth::instance()->isAdmin() && \CMS\Auth::instance()->hasCapability('manage_settings');
}

function cms_admin_system_monitor_normalize_section(string $section): string
{
    $section = trim($section);

    return array_key_exists($section, CMS_ADMIN_SYSTEM_MONITOR_SECTION_ACTIONS) ? $section : 'info';
}

function cms_admin_system_monitor_normalize_action(string $section, mixed $action): string
{
    $normalizedSection = cms_admin_system_monitor_normalize_section($section);
    $normalizedAction = trim((string) $action);
    $allowedActions = CMS_ADMIN_SYSTEM_MONITOR_SECTION_ACTIONS[$normalizedSection] ?? [];

    return in_array($normalizedAction, $allowedActions, true) ? $normalizedAction : '';
}

function cms_admin_system_monitor_can_run_action(string $section, string $action): bool
{
    return $action !== ''
        && array_key_exists(cms_admin_system_monitor_normalize_section($section), CMS_ADMIN_SYSTEM_MONITOR_SECTION_ACTIONS)
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
function cms_admin_system_monitor_normalize_page_config(array $pageConfig): array
{
    $section = cms_admin_system_monitor_normalize_section((string) ($pageConfig['section'] ?? 'info'));
    $defaults = CMS_ADMIN_SYSTEM_MONITOR_PAGE_CONFIGS[$section] ?? CMS_ADMIN_SYSTEM_MONITOR_PAGE_CONFIGS['info'];

    return [
        'section' => $section,
        'route_path' => (string) ($defaults['route_path'] ?? '/admin/info'),
        'view_file' => (string) ($defaults['view_file'] ?? (__DIR__ . '/views/system/info.php')),
        'page_title' => (string) ($defaults['page_title'] ?? 'Info & Diagnose'),
        'active_page' => (string) ($defaults['active_page'] ?? 'info'),
        'page_assets' => is_array($pageConfig['page_assets'] ?? null) ? $pageConfig['page_assets'] : [],
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

$systemMonitorPageConfig['section'] = cms_admin_system_monitor_normalize_section((string) ($systemMonitorPageConfig['section'] ?? 'info'));

if (!cms_admin_system_monitor_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

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

        $normalizedSection = cms_admin_system_monitor_normalize_section($section);
        $action = cms_admin_system_monitor_normalize_action($normalizedSection, $postData['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if (!cms_admin_system_monitor_can_run_action($normalizedSection, $action)) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Aktion.'];
        }

        return $module->handleAction($normalizedSection, $action, $postData);
    },
    'data_loader' => static function ($module) use ($systemMonitorPageConfig): array {
        return $module instanceof SystemInfoModule
            ? $module->getSectionData((string) ($systemMonitorPageConfig['section'] ?? 'info'))
            : [];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
