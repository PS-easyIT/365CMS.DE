<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

function cms_admin_modules_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability('manage_settings');
}

function cms_admin_modules_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return $normalizedAction === 'save_modules' ? $normalizedAction : '';
}

require_once __DIR__ . '/modules/system/ModulesModule.php';

$sectionPageConfig = [
    'route_path' => '/admin/modules',
    'view_file' => __DIR__ . '/views/system/modules.php',
    'page_title' => 'System · Module',
    'active_page' => 'modules',
    'csrf_action' => 'admin_modules',
    'guard_constant' => 'CMS_ADMIN_SYSTEM_VIEW',
    'module_file' => __DIR__ . '/modules/system/ModulesModule.php',
    'module_factory' => static fn (): ModulesModule => new ModulesModule(),
    'access_checker' => static fn (): bool => cms_admin_modules_can_access(),
    'post_handler' => static function (ModulesModule $module, string $section, array $post): array {
        $action = cms_admin_modules_normalize_action($post['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte Modul-Aktion.'];
        }

        return $module->saveModules($post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
