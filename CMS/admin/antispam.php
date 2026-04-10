<?php
declare(strict_types=1);

/**
 * AntiSpam – Entry Point
 * Route: /admin/antispam
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;

require_once __DIR__ . '/modules/security/AntispamModule.php';

const CMS_ADMIN_ANTISPAM_ALLOWED_ACTIONS = [
    'save_settings',
    'add_blacklist',
    'delete_blacklist',
];

const CMS_ADMIN_ANTISPAM_ACTION_CAPABILITIES = [
    'save_settings' => 'manage_settings',
    'add_blacklist' => 'manage_settings',
    'delete_blacklist' => 'manage_settings',
];

function cms_admin_antispam_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_ANTISPAM_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_antispam_normalize_positive_id(mixed $id): int
{
    $normalizedId = (int)$id;

    return $normalizedId > 0 ? $normalizedId : 0;
}

function cms_admin_antispam_can_run_action(string $action): bool
{
    $requiredCapability = CMS_ADMIN_ANTISPAM_ACTION_CAPABILITIES[$action] ?? '';
    if ($requiredCapability === '') {
        return false;
    }

    return Auth::instance()->hasCapability($requiredCapability);
}

function cms_admin_antispam_handle_action(AntispamModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'save_settings':
            return $module->saveSettings($post);

        case 'add_blacklist':
            return $module->addBlacklist($post);

        case 'delete_blacklist':
            return $module->deleteBlacklist(cms_admin_antispam_normalize_positive_id($post['id'] ?? 0));
    }

    return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
}

$sectionPageConfig = [
    'section' => 'overview',
    'route_path' => '/admin/antispam',
    'view_file' => __DIR__ . '/views/security/antispam.php',
    'page_title' => 'AntiSpam',
    'active_page' => 'antispam',
    'page_assets' => [
        'css' => [],
        'js' => [],
    ],
    'csrf_action' => 'admin_antispam',
    'module_factory' => static function (): AntispamModule {
        return new AntispamModule();
    },
    'data_loader' => static function ($module): array {
        return $module instanceof AntispamModule ? $module->getData() : [];
    },
    'access_checker' => static function (): bool {
        return Auth::instance()->isAdmin() && Auth::instance()->hasCapability('manage_settings');
    },
    'access_denied_route' => '/',
    'post_handler' => static function ($module, string $section, array $postData): array {
        if (!$module instanceof AntispamModule) {
            return ['success' => false, 'error' => 'AntiSpam-Modul konnte nicht initialisiert werden.'];
        }

        $action = cms_admin_antispam_normalize_action($postData['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte oder nicht erlaubte Aktion.'];
        }

        if (!cms_admin_antispam_can_run_action($action)) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese AntiSpam-Aktion.'];
        }

        return cms_admin_antispam_handle_action($module, $action, $postData);
    },
    'unknown_action_message' => 'Unbekannte oder nicht erlaubte Aktion.',
];

require __DIR__ . '/partials/section-page-shell.php';
