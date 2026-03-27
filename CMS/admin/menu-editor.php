<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menü Editor – Entry Point
 * Route: /admin/menu-editor
 */

use CMS\Auth;

const CMS_ADMIN_MENU_EDITOR_ALLOWED_ACTIONS = [
    'save_menu',
    'delete_menu',
    'save_items',
];

const CMS_ADMIN_MENU_EDITOR_WRITE_CAPABILITY = 'manage_settings';
const CMS_ADMIN_MENU_EDITOR_MAX_ITEMS_JSON_LENGTH = 250000;

function cms_admin_menu_editor_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_MENU_EDITOR_WRITE_CAPABILITY);
}

function cms_admin_menu_editor_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string) $action));

    return in_array($normalizedAction, CMS_ADMIN_MENU_EDITOR_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_menu_editor_normalize_menu_id(mixed $menuId): int
{
    $normalizedMenuId = filter_var($menuId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedMenuId === false ? 0 : (int) $normalizedMenuId;
}

/**
 * @return array{action:string,menu_id:int,items_json:string,error:string,post:array<string,mixed>}
 */
function cms_admin_menu_editor_normalize_payload(array $post): array
{
    $action = cms_admin_menu_editor_normalize_action($post['action'] ?? '');
    $menuId = cms_admin_menu_editor_normalize_menu_id($post['menu_id'] ?? 0);
    $itemsJson = (string) ($post['items'] ?? '[]');
    $error = '';

    if ($action === '') {
        $error = 'Unbekannte Aktion.';
    } elseif (in_array($action, ['delete_menu', 'save_items'], true) && $menuId <= 0) {
        $error = 'Ungültige Menü-ID.';
    } elseif ($action === 'save_items' && strlen($itemsJson) > CMS_ADMIN_MENU_EDITOR_MAX_ITEMS_JSON_LENGTH) {
        $error = 'Die Menü-Konfiguration ist zu groß.';
    }

    return [
        'action' => $action,
        'menu_id' => $menuId,
        'items_json' => $itemsJson,
        'error' => $error,
        'post' => $post,
    ];
}

if (!cms_admin_menu_editor_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/menus/MenuEditorModule.php';

function cms_admin_menu_editor_redirect_path(int $menuId = 0): string
{
    return $menuId > 0
        ? '/admin/menu-editor?menu=' . $menuId
        : '/admin/menu-editor';
}

function cms_admin_menu_editor_handle_action(MenuEditorModule $module, array $payload): array
{
    return match ($payload['action']) {
        'save_menu' => $module->saveMenu($payload['post']),
        'delete_menu' => $module->deleteMenu($payload['menu_id']),
        'save_items' => $module->saveItems($payload['menu_id'], $payload['items_json']),
        default => [
            'success' => false,
            'error' => 'Unbekannte Aktion.',
        ],
    };
}

function cms_admin_menu_editor_load_data(MenuEditorModule $module, int $menuId): array
{
    return $module->getData($menuId);
}

$sectionPageConfig = [
    'route_path' => '/admin/menu-editor',
    'view_file' => __DIR__ . '/views/menus/editor.php',
    'page_title' => 'Menü Editor',
    'active_page' => 'menu-editor',
    'page_assets' => [
        'js' => [
            cms_asset_url('js/admin-menu-editor.js'),
        ],
    ],
    'csrf_action' => 'admin_menu_editor',
    'module_file' => __DIR__ . '/modules/menus/MenuEditorModule.php',
    'module_factory' => static fn (): MenuEditorModule => new MenuEditorModule(),
    'access_checker' => static fn (): bool => cms_admin_menu_editor_can_access(),
    'request_context_resolver' => static function (MenuEditorModule $module): array {
        $currentMenuId = cms_admin_menu_editor_normalize_menu_id($_GET['menu'] ?? 0);

        return [
            'section' => (string) $currentMenuId,
            'data' => cms_admin_menu_editor_load_data($module, $currentMenuId),
        ];
    },
    'redirect_path_resolver' => static function (MenuEditorModule $module, string $section, mixed $result): string {
        $redirectMenuId = 0;

        if (is_array($result) && array_key_exists('redirect_menu_id', $result)) {
            $redirectMenuId = cms_admin_menu_editor_normalize_menu_id($result['redirect_menu_id']);
        } elseif (is_array($result) && !empty($result['success']) && array_key_exists('id', $result)) {
            $redirectMenuId = cms_admin_menu_editor_normalize_menu_id($result['id']);
        } elseif ($section !== '') {
            $redirectMenuId = cms_admin_menu_editor_normalize_menu_id($section);
        }

        return cms_admin_menu_editor_redirect_path($redirectMenuId);
    },
    'post_handler' => static function (MenuEditorModule $module, string $section, array $post): array {
        $payload = cms_admin_menu_editor_normalize_payload($post);

        if ($payload['error'] !== '') {
            return [
                'success' => false,
                'error' => $payload['error'],
                'redirect_menu_id' => $payload['menu_id'],
            ];
        }

        if (!Auth::instance()->hasCapability(CMS_ADMIN_MENU_EDITOR_WRITE_CAPABILITY)) {
            return [
                'success' => false,
                'error' => 'Keine Berechtigung für diese Aktion.',
                'redirect_menu_id' => $payload['menu_id'],
            ];
        }

        $result = cms_admin_menu_editor_handle_action($module, $payload);
        if ($payload['action'] === 'delete_menu') {
            $result['redirect_menu_id'] = 0;
        } elseif ($payload['action'] === 'save_menu' && !empty($result['success'])) {
            $result['redirect_menu_id'] = cms_admin_menu_editor_normalize_menu_id($result['id'] ?? $payload['menu_id']);
        } else {
            $result['redirect_menu_id'] = $payload['menu_id'];
        }

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
