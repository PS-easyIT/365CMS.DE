<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu Wrappers
 */
global $cms_admin_menu;
if (!isset($cms_admin_menu)) {
    $cms_admin_menu = [];
}

/**
 * Findet den Index eines registrierten Top-Level-Plugin-Menüs über seinen Slug.
 */
function cms_find_admin_menu_index(string $menuSlug): int|string|null {
    global $cms_admin_menu;

    foreach ($cms_admin_menu as $index => $item) {
        if (is_array($item) && (string) ($item['menu_slug'] ?? '') === $menuSlug) {
            return $index;
        }
    }

    return null;
}

/**
 * Add a top-level menu page.
 */
function add_menu_page(string $page_title, string $menu_title, string $capability, string $menu_slug, $function = '', string $icon_url = '', ?int $position = null, bool $hidden = false): void {
    global $cms_admin_menu;

    if (function_exists('current_user_can') && !current_user_can($capability)) {
        return;
    }

    $menuItem = [
        'type'       => 'plugin_page',
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'capability' => $capability,
        'menu_slug'  => $menu_slug,
        'callable'   => $function,
        'icon_url'   => $icon_url,
        'position'   => $position,
        'hidden'     => $hidden,
        'children'   => [],
    ];

    $existingIndex = cms_find_admin_menu_index($menu_slug);
    if ($existingIndex !== null) {
        $existingChildren = is_array($cms_admin_menu[$existingIndex]['children'] ?? null)
            ? $cms_admin_menu[$existingIndex]['children']
            : [];
        $cms_admin_menu[$existingIndex] = array_merge($cms_admin_menu[$existingIndex], $menuItem, [
            'children' => $existingChildren,
        ]);
        return;
    }

    if ($position !== null) {
        $cms_admin_menu[$position] = $menuItem;
    } else {
        $cms_admin_menu[] = $menuItem;
    }
}

/**
 * Add a submenu page.
 */
function add_submenu_page(string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $function = ''): void {
    global $cms_admin_menu;

    if (function_exists('current_user_can') && !current_user_can($capability)) {
        return;
    }

    $submenuItem = [
        'type'       => 'submenu_page',
        'page_title' => $page_title,
        'menu_title' => $menu_title,
        'capability' => $capability,
        'menu_slug'  => $menu_slug,
        'callable'   => $function,
        'url'        => '/admin/plugins/' . $parent_slug . '/' . $menu_slug,
    ];

    foreach ($cms_admin_menu as &$item) {
        if (isset($item['menu_slug']) && $item['menu_slug'] === $parent_slug) {
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            $replaced = false;

            foreach ($children as $childIndex => $existingChild) {
                if (is_array($existingChild) && (string) ($existingChild['menu_slug'] ?? '') === $menu_slug) {
                    $children[$childIndex] = array_merge($existingChild, $submenuItem);
                    $replaced = true;
                    break;
                }
            }

            if (!$replaced) {
                $children[] = $submenuItem;
            }

            $item['children'] = $children;
            break;
        }
    }
    unset($item);
}

/**
 * Get all registered admin menus
 */
function get_registered_admin_menus(): array {
    global $cms_admin_menu;

    ksort($cms_admin_menu);
    return $cms_admin_menu;
}
