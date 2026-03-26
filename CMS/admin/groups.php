<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gruppen – Entry Point
 * Route: /admin/groups
 */

use CMS\Auth;

require_once __DIR__ . '/modules/users/GroupsModule.php';

/** @return array<string, true> */
function cms_admin_groups_allowed_actions(): array
{
    return [
        'save' => true,
        'delete' => true,
    ];
}

function cms_admin_groups_normalize_action(mixed $value): ?string
{
    $action = strtolower(trim((string) $value));

    return isset(cms_admin_groups_allowed_actions()[$action]) ? $action : null;
}

function cms_admin_groups_normalize_id(array $post): int
{
    $id = (int) ($post['id'] ?? 0);

    return $id > 0 ? $id : 0;
}

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_groups_action_handlers(GroupsModule $module): array
{
    return [
        'save' => static fn (array $post): array => $module->save($post),
        'delete' => static fn (array $post): array => $module->delete(cms_admin_groups_normalize_id($post)),
    ];
}

function cms_admin_groups_handle_action(GroupsModule $module, string $action, array $post): array
{
    $handlers = cms_admin_groups_action_handlers($module);

    if (!isset($handlers[$action])) {
        return ['success' => false, 'error' => 'Unbekannte Aktion.'];
    }

    return $handlers[$action]($post);
}

$sectionPageConfig = [
    'route_path' => '/admin/groups',
    'view_file' => __DIR__ . '/views/users/groups.php',
    'page_title' => 'Gruppen',
    'active_page' => 'groups',
    'csrf_action' => 'admin_groups',
    'module_file' => __DIR__ . '/modules/users/GroupsModule.php',
    'module_factory' => static fn (): GroupsModule => new GroupsModule(),
    'data_loader' => static fn (GroupsModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin(),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte Aktion.',
    'post_handler' => static function (GroupsModule $module, string $section, array $post): array {
        $action = cms_admin_groups_normalize_action($post['action'] ?? null);

        if ($action === null) {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if ($action === 'delete' && cms_admin_groups_normalize_id($post) <= 0) {
            return ['success' => false, 'error' => 'Ungültige Gruppen-ID.'];
        }

        return cms_admin_groups_handle_action($module, $action, $post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
