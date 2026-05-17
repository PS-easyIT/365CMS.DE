<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;
use CMS\Services\CoreModuleService;

require_once __DIR__ . '/modules/system/SystemInfoModule.php';
require_once __DIR__ . '/modules/security/SecurityAuditModule.php';

const CMS_ADMIN_LOGS_SECTION_ACTIONS = [
    'overview' => ['clear_all_cms_logs', 'export_diagnostic_report', 'run_audit', 'clear_log'],
    'operational' => ['clear_all_cms_logs'],
    'security-audit' => ['run_audit', 'clear_log'],
    'php-errors' => ['clear_logs'],
    'channels' => ['clear_cms_log'],
];

const CMS_ADMIN_LOGS_PAGE_CONFIGS = [
    'overview' => [
        'route_path' => '/admin/logs',
        'view_file' => __DIR__ . '/views/logs/overview.php',
        'page_title' => 'Logs & Audit',
        'active_page' => 'logs-overview',
    ],
    'operational' => [
        'route_path' => '/admin/logs/operational',
        'view_file' => __DIR__ . '/views/logs/operational.php',
        'page_title' => 'Logs & Audit · Operativer Log',
        'active_page' => 'logs-operational',
    ],
    'security-audit' => [
        'route_path' => '/admin/logs/security-audit',
        'view_file' => __DIR__ . '/views/logs/security-audit.php',
        'page_title' => 'Logs & Audit · Sicherheits-Audit',
        'active_page' => 'logs-security-audit',
    ],
    'php-errors' => [
        'route_path' => '/admin/logs/php-errors',
        'view_file' => __DIR__ . '/views/logs/php-errors.php',
        'page_title' => 'Logs & Audit · PHP-Fehlerlog',
        'active_page' => 'logs-php-errors',
    ],
    'channels' => [
        'route_path' => '/admin/logs/channels',
        'view_file' => __DIR__ . '/views/logs/channels.php',
        'page_title' => 'Logs & Audit · Kanal-Logs & Update-Historie',
        'active_page' => 'logs-channels',
    ],
];

function cms_admin_logs_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && Auth::instance()->hasCapability('manage_settings')
        && (!class_exists(CoreModuleService::class) || CoreModuleService::getInstance()->isAdminPageEnabled('logs'));
}

function cms_admin_logs_normalize_section(string $section): string
{
    $section = trim($section);

    return array_key_exists($section, CMS_ADMIN_LOGS_PAGE_CONFIGS) ? $section : 'overview';
}

function cms_admin_logs_normalize_action(string $section, mixed $action): string
{
    $allowedActions = CMS_ADMIN_LOGS_SECTION_ACTIONS[cms_admin_logs_normalize_section($section)] ?? [];
    $action = trim((string) $action);

    return in_array($action, $allowedActions, true) ? $action : '';
}

function cms_admin_logs_build_page_config(array $logsPageConfig): array
{
    $section = cms_admin_logs_normalize_section((string) ($logsPageConfig['section'] ?? 'overview'));
    $defaults = CMS_ADMIN_LOGS_PAGE_CONFIGS[$section];

    return [
        'section' => $section,
        'route_path' => (string) ($defaults['route_path'] ?? '/admin/logs'),
        'view_file' => (string) ($defaults['view_file'] ?? (__DIR__ . '/views/logs/overview.php')),
        'page_title' => (string) ($defaults['page_title'] ?? 'Logs & Audit'),
        'active_page' => (string) ($defaults['active_page'] ?? 'logs-overview'),
    ];
}

function cms_admin_logs_security_action(string $action, SecurityAuditModule $securityModule): array
{
    return match ($action) {
        'run_audit' => $securityModule->runAudit(),
        'clear_log' => $securityModule->clearLog(),
        default => ['success' => false, 'error' => 'Unbekannte Sicherheits-Aktion.'],
    };
}

function cms_admin_logs_build_activity_feed(array $logsData, array $securityAuditData): array
{
    $rows = [];

    foreach ((array) ($logsData['selected_entries'] ?? []) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $rows[] = [
            'timestamp' => (string) ($entry['timestamp'] ?? ''),
            'area' => 'System',
            'action' => (string) ($entry['channel'] ?? 'system.log'),
            'level' => strtolower((string) ($entry['level'] ?? 'info')),
            'message' => (string) ($entry['message'] ?? ''),
            'source' => 'system',
        ];
    }

    foreach ((array) ($logsData['operational_audit_entries'] ?? []) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $group = (string) ($entry['group'] ?? 'system');
        $area = match ($group) {
            'backup' => 'Backup',
            'cron' => 'Cron',
            'monitoring', 'performance' => 'System',
            default => 'System',
        };
        $rows[] = [
            'timestamp' => (string) ($entry['created_at'] ?? ''),
            'area' => $area,
            'action' => (string) ($entry['action'] ?? ''),
            'level' => strtolower((string) ($entry['severity'] ?? 'info')),
            'message' => (string) ($entry['details'] ?? ''),
            'source' => 'operational',
        ];
    }

    foreach ((array) ($securityAuditData['audit_log'] ?? []) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $action = (string) ($entry['action'] ?? '');
        $area = str_starts_with($action, 'firewall.') ? 'Firewall' : 'Auth';
        $rows[] = [
            'timestamp' => (string) ($entry['created_at'] ?? ''),
            'area' => $area,
            'action' => $action,
            'level' => strtolower((string) ($entry['severity'] ?? 'warning')),
            'message' => (string) ($entry['details'] ?? ''),
            'source' => 'security',
        ];
    }

    usort($rows, static fn(array $a, array $b): int => strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? '')));

    return array_slice($rows, 0, 250);
}

$logsPageConfig = cms_admin_logs_build_page_config(array_merge(
    ['section' => $logsSection ?? 'overview'],
    is_array($logsPageConfig ?? null) ? $logsPageConfig : []
));

$sectionPageConfig = [
    'section' => $logsPageConfig['section'],
    'route_path' => $logsPageConfig['route_path'],
    'view_file' => $logsPageConfig['view_file'],
    'page_title' => $logsPageConfig['page_title'],
    'active_page' => $logsPageConfig['active_page'],
    'csrf_action' => 'admin_system_info',
    'csrf_actions' => ['admin_system_info', 'admin_sec_audit'],
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'access_checker' => static function (): bool {
        return cms_admin_logs_can_access();
    },
    'module_factory' => static function (): array {
        return [
            'system' => new SystemInfoModule(),
            'security' => new SecurityAuditModule(),
        ];
    },
    'post_handler' => static function ($module, string $section, array $post): array {
        $container = is_array($module) ? $module : [];
        $systemModule = $container['system'] ?? null;
        $securityModule = $container['security'] ?? null;
        if (!$systemModule instanceof SystemInfoModule || !$securityModule instanceof SecurityAuditModule) {
            return ['success' => false, 'error' => 'Logs-Modul konnte nicht initialisiert werden.'];
        }

        $action = cms_admin_logs_normalize_action($section, $post['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if (in_array($action, ['run_audit', 'clear_log'], true)) {
            return cms_admin_logs_security_action($action, $securityModule);
        }

        return $systemModule->handleAction('logs', $action, $post);
    },
    'data_loader' => static function ($module): array {
        $container = is_array($module) ? $module : [];
        $systemModule = $container['system'] ?? null;
        $securityModule = $container['security'] ?? null;
        if (!$systemModule instanceof SystemInfoModule || !$securityModule instanceof SecurityAuditModule) {
            return [];
        }

        $logsData = $systemModule->getSectionData('logs');
        $securityAuditData = $securityModule->getData();

        return [
            'logs' => $logsData,
            'security_audit' => $securityAuditData,
            'activity_feed' => cms_admin_logs_build_activity_feed($logsData, $securityAuditData),
        ];
    },
    'request_context_resolver' => static function (): array {
        return [
            'template_vars' => [
                'securityCsrfToken' => Security::instance()->generateToken('admin_sec_audit'),
            ],
        ];
    },
];

require __DIR__ . '/partials/section-page-shell.php';
