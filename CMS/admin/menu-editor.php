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

function cms_admin_menu_editor_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_MENU_EDITOR_WRITE_CAPABILITY);
}

function cms_admin_menu_editor_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_MENU_EDITOR_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_menu_editor_normalize_menu_id(mixed $menuId): int
{
    $normalizedMenuId = filter_var($menuId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedMenuId === false ? 0 : (int) $normalizedMenuId;
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

/**
 * @return array<string, callable(array): array>
 */
function cms_admin_menu_editor_action_handlers(MenuEditorModule $module): array
{
    return [
        'save_menu' => static fn (array $post): array => $module->saveMenu($post),
        'delete_menu' => static fn (array $post): array => $module->deleteMenu(cms_admin_menu_editor_normalize_menu_id($post['menu_id'] ?? 0)),
        'save_items' => static fn (array $post): array => $module->saveItems(
            cms_admin_menu_editor_normalize_menu_id($post['menu_id'] ?? 0),
            (string) ($post['items'] ?? '[]')
        ),
    ];
}

$sectionPageConfig = [
    'route_path' => '/admin/menu-editor',
    'view_file' => __DIR__ . '/views/menus/editor.php',
    'page_title' => 'Menü Editor',
    'active_page' => 'menu-editor',
    'csrf_action' => 'admin_menu_editor',
    'module_file' => __DIR__ . '/modules/menus/MenuEditorModule.php',
    'module_factory' => static fn (): MenuEditorModule => new MenuEditorModule(),
    'access_checker' => static fn (): bool => cms_admin_menu_editor_can_access(),
    'request_context_resolver' => static function (MenuEditorModule $module): array {
        $currentMenuId = cms_admin_menu_editor_normalize_menu_id($_GET['menu'] ?? 0);

        return [
            'section' => (string) $currentMenuId,
            'data' => $module->getData($currentMenuId),
        ];
    },
    'redirect_path_resolver' => static function (MenuEditorModule $module, string $section, mixed $result): string {
        $redirectMenuId = 0;

        if (is_array($result) && array_key_exists('redirect_menu_id', $result)) {
            $redirectMenuId = cms_admin_menu_editor_normalize_menu_id($result['redirect_menu_id']);
        } elseif ($section !== '') {
            $redirectMenuId = cms_admin_menu_editor_normalize_menu_id($section);
        }

        return cms_admin_menu_editor_redirect_path($redirectMenuId);
    },
    'post_handler' => static function (MenuEditorModule $module, string $section, array $post): array {
        $menuId = cms_admin_menu_editor_normalize_menu_id($post['menu_id'] ?? 0);
        $action = cms_admin_menu_editor_normalize_action($post['action'] ?? '');
        $handlers = cms_admin_menu_editor_action_handlers($module);

        if ($action === '' || !isset($handlers[$action])) {
            return [
                'success' => false,
                'error' => 'Unbekannte Aktion.',
                'redirect_menu_id' => $menuId,
            ];
        }

        if (!Auth::instance()->hasCapability(CMS_ADMIN_MENU_EDITOR_WRITE_CAPABILITY)) {
            return [
                'success' => false,
                'error' => 'Keine Berechtigung für diese Aktion.',
                'redirect_menu_id' => $menuId,
            ];
        }

        $result = $handlers[$action]($post);
        $result['redirect_menu_id'] = $action === 'delete_menu' ? 0 : $menuId;

        return $result;
    },
];

require __DIR__ . '/partials/section-page-shell.php';
