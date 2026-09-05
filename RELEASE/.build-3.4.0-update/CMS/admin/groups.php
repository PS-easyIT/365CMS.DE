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

const CMS_ADMIN_GROUPS_WRITE_CAPABILITY = 'manage_users';
const CMS_ADMIN_GROUPS_ALLOWED_ACTIONS = ['save', 'delete', 'bulk'];
const CMS_ADMIN_GROUPS_ALLOWED_BULK_ACTIONS = ['activate', 'deactivate', 'delete', 'set_plan', 'clear_plan'];

function cms_admin_groups_normalize_action(mixed $value): string
{
    $action = strtolower(trim((string) $value));

    return in_array($action, CMS_ADMIN_GROUPS_ALLOWED_ACTIONS, true) ? $action : '';
}

function cms_admin_groups_normalize_bulk_action(mixed $value): string
{
    $action = strtolower(trim((string) $value));

    return in_array($action, CMS_ADMIN_GROUPS_ALLOWED_BULK_ACTIONS, true) ? $action : '';
}

function cms_admin_groups_normalize_id(array $post): int
{
    $id = (int) ($post['id'] ?? 0);

    return $id > 0 ? $id : 0;
}

/**
 * @return array<int,int>
 */
function cms_admin_groups_normalize_bulk_ids(mixed $ids): array
{
    $normalizedIds = [];

    foreach ((array) $ids as $id) {
        $normalizedId = (int) $id;
        if ($normalizedId > 0) {
            $normalizedIds[$normalizedId] = $normalizedId;
        }

        if (count($normalizedIds) >= 200) {
            break;
        }
    }

    return array_values($normalizedIds);
}

/**
 * @return array{action:string,id:int,bulk_action:string,ids:array<int,int>,post:array<string,mixed>}
 */
function cms_admin_groups_normalize_payload(array $post): array
{
    return [
        'action' => cms_admin_groups_normalize_action($post['action'] ?? null),
        'id' => cms_admin_groups_normalize_id($post),
        'bulk_action' => cms_admin_groups_normalize_bulk_action($post['bulk_action'] ?? null),
        'ids' => cms_admin_groups_normalize_bulk_ids($post['ids'] ?? []),
        'post' => $post,
    ];
}

function cms_admin_groups_handle_action(GroupsModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save' => $module->save($payload['post']),
        'delete' => $module->delete($payload['id']),
        'bulk' => $module->bulkAction($payload['bulk_action'], $payload['ids'], $payload['post']),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };
}

$sectionPageConfig = [
    'route_path' => '/admin/groups',
    'view_file' => __DIR__ . '/views/users/groups.php',
    'page_title' => 'Gruppen',
    'active_page' => 'groups',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-user-groups.js'),
        ],
    ],
    'csrf_action' => 'admin_groups',
    'module_file' => __DIR__ . '/modules/users/GroupsModule.php',
    'module_factory' => static fn (): GroupsModule => new GroupsModule(),
    'data_loader' => static fn (GroupsModule $module): array => $module->getData(),
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_GROUPS_WRITE_CAPABILITY),
    'access_denied_route' => '/',
    'unknown_action_message' => 'Unbekannte Aktion.',
    'post_handler' => static function (GroupsModule $module, string $section, array $post): array {
        $payload = cms_admin_groups_normalize_payload($post);

        if ($payload['action'] === '') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        if ($payload['action'] === 'delete' && $payload['id'] <= 0) {
            return ['success' => false, 'error' => 'Ungültige Gruppen-ID.'];
        }

        if ($payload['action'] === 'bulk') {
            if ($payload['bulk_action'] === '') {
                return ['success' => false, 'error' => 'Unbekannte Bulk-Aktion für Gruppen.'];
            }

            if ($payload['ids'] === []) {
                return ['success' => false, 'error' => 'Bitte mindestens eine gültige Gruppe auswählen.'];
            }
        }

        return cms_admin_groups_handle_action($module, $payload);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
