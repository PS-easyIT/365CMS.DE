<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Backup & Restore – Entry Point
 * Route: /admin/backups
 */

use CMS\Auth;

const CMS_ADMIN_BACKUPS_READ_CAPABILITIES = ['manage_settings', 'manage_system'];
const CMS_ADMIN_BACKUPS_WRITE_CAPABILITY = 'manage_settings';

function cms_admin_backups_has_any_capability(array $capabilities): bool
{
    foreach ($capabilities as $capability) {
        if (is_string($capability) && $capability !== '' && Auth::instance()->hasCapability($capability)) {
            return true;
        }
    }

    return false;
}

function cms_admin_backups_can_access(): bool
{
    return Auth::instance()->isAdmin()
        && cms_admin_backups_has_any_capability(CMS_ADMIN_BACKUPS_READ_CAPABILITIES);
}

function cms_admin_backups_can_mutate(): bool
{
    return cms_admin_backups_can_access()
        && Auth::instance()->hasCapability(CMS_ADMIN_BACKUPS_WRITE_CAPABILITY);
}

require_once __DIR__ . '/modules/system/BackupsModule.php';

/** @return array<string, true> */
function cms_admin_backups_allowed_actions(): array
{
    return [
        'create_full' => true,
        'create_db' => true,
        'delete' => true,
    ];
}

function cms_admin_backups_normalize_action(mixed $value): ?string
{
    $action = strtolower(trim((string) $value));

    return isset(cms_admin_backups_allowed_actions()[$action]) ? $action : null;
}

function cms_admin_backups_normalize_backup_name(array $post): string
{
    $name = trim(basename((string) ($post['backup_name'] ?? '')));

    return preg_match('/^[a-z0-9][a-z0-9._-]{2,120}$/i', $name) === 1 ? $name : '';
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_backups_action_handlers(BackupsModule $module): array
{
    return [
        'create_full' => static fn (array $post): array => $module->createFullBackup(),
        'create_db' => static fn (array $post): array => $module->createDatabaseBackup(),
        'delete' => static fn (array $post): array => $module->deleteBackup(cms_admin_backups_normalize_backup_name($post)),
    ];
}

$sectionPageConfig = [
    'section' => 'overview',
    'route_path' => '/admin/backups',
    'view_file' => __DIR__ . '/views/system/backups.php',
    'page_title' => 'Backup & Restore',
    'active_page' => 'backups',
    'page_assets' => [
        'css' => [],
        'js' => [],
    ],
    'csrf_action' => 'admin_backups',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_factory' => static function (): BackupsModule {
        return new BackupsModule();
    },
    'data_loader' => static function ($module): array {
        return $module instanceof BackupsModule ? $module->getData() : [];
    },
    'access_checker' => static function (): bool {
        return cms_admin_backups_can_access();
    },
    'access_denied_route' => '/',
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof BackupsModule) {
            return ['success' => false, 'error' => 'Backup-Modul konnte nicht initialisiert werden.'];
        }

        if (!cms_admin_backups_can_mutate()) {
            return ['success' => false, 'error' => 'Keine Berechtigung für Backup-Mutationen.'];
        }

        $action = cms_admin_backups_normalize_action($postData['action'] ?? null);
        if ($action === null) {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if ($action === 'delete' && cms_admin_backups_normalize_backup_name($postData) === '') {
            return ['success' => false, 'error' => 'Ungültiger Backup-Name.'];
        }

        $handler = cms_admin_backups_action_handlers($module)[$action] ?? null;

        return is_callable($handler)
            ? $handler($postData)
            : ['success' => false, 'error' => 'Unbekannte Aktion.'];
    },
    'unknown_action_message' => 'Unbekannte Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
