<?php
/**
 * Admin-Menu-Bridge (robust)
 *
 * Übergangsdatei für die neue modulare Admin-Struktur.
 * Nutzt bevorzugt das bestehende Layout in admin/old.
 * Fällt automatisch auf ein eingebautes vollständiges Tabler-Layout zurück,
 * wenn admin/old im Deployment nicht vorhanden ist.
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$legacyCandidates = [
    dirname(__DIR__) . '/old/partials/admin-menu.php',
    dirname(__DIR__, 2) . '/admin/old/partials/admin-menu.php',
    rtrim(ABSPATH, '/\\') . '/admin/old/partials/admin-menu.php',
    rtrim(ABSPATH, '/\\') . '/CMS/admin/old/partials/admin-menu.php',
];

foreach ($legacyCandidates as $legacyFile) {
    if (is_file($legacyFile)) {
        require_once $legacyFile;
        return;
    }
}

// ── Fallback: vollständiges Tabler-Admin-Layout (ohne admin/old) ───────────

// Inline SVG Icon Helper (Subset der wichtigsten Icons)
if (!function_exists('_fallbackTablerIcon')) {
    function _fallbackTablerIcon(string $name, int $size = 20): string
    {
        static $icons = [
            'layout-dashboard' => '<path d="M4 4h6v8h-6z"/><path d="M4 16h6v4h-6z"/><path d="M14 12h6v8h-6z"/><path d="M14 4h6v4h-6z"/>',
            'home'             => '<path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/>',
            'files'            => '<path d="M15 3v4a1 1 0 0 0 1 1h4"/><path d="M18 17h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h4l5 5v7a2 2 0 0 1 -2 2z"/><path d="M16 17v2a2 2 0 0 1 -2 2h-7a2 2 0 0 1 -2 -2v-10a2 2 0 0 1 2 -2h2"/>',
            'file'             => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"/>',
            'article'          => '<path d="M3 4m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/><path d="M7 8h10"/><path d="M7 12h10"/><path d="M7 16h10"/>',
            'messages'         => '<path d="M21 14l-3 -3h-7a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1h9a1 1 0 0 1 1 1v10"/><path d="M14 15v2a1 1 0 0 1 -1 1h-7l-3 3v-10a1 1 0 0 1 1 -1h2"/>',
            'photo'            => '<path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>',
            'users'            => '<path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/>',
            'user'             => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
            'credit-card'      => '<path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l2 0"/>',
            'brush'            => '<path d="M3 21v-4a4 4 0 1 1 4 4h-4"/><path d="M21 3a16 16 0 0 0 -12.8 10.2"/><path d="M21 3a16 16 0 0 1 -10.2 12.8"/><path d="M10.6 9a9 9 0 0 1 4.4 4.4"/>',
            'chart-line'       => '<path d="M4 19l16 0"/><path d="M4 15l4 -6l4 2l4 -5l4 4"/>',
            'chart-bar'        => '<path d="M3 12m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M9 8m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v10a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M15 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 20l18 0"/>',
            'scale'            => '<path d="M7 20l10 0"/><path d="M6 6l6 -1l6 1"/><path d="M12 3l0 17"/><path d="M9 12l-3 -6l-3 6a3 3 0 0 0 6 0"/><path d="M21 12l-3 -6l-3 6a3 3 0 0 0 6 0"/>',
            'puzzle'           => '<path d="M4 7h3a1 1 0 0 0 1 -1v-1a2 2 0 0 1 4 0v1a1 1 0 0 0 1 1h3a1 1 0 0 1 1 1v3a1 1 0 0 0 1 1h1a2 2 0 0 1 0 4h-1a1 1 0 0 0 -1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-1a2 2 0 0 0 -4 0v1a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1h1a2 2 0 0 0 0 -4h-1a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1"/>',
            'settings'         => '<path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/>',
            'refresh'          => '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
            'database'         => '<path d="M12 6m-8 0a8 3 0 1 0 16 0a8 3 0 1 0 -16 0"/><path d="M4 6v6a8 3 0 0 0 16 0v-6"/><path d="M4 12v6a8 3 0 0 0 16 0v-6"/>',
            'stethoscope'      => '<path d="M6 4h-1a2 2 0 0 0 -2 2v3.5h0a5.5 5.5 0 0 0 11 0v-3.5a2 2 0 0 0 -2 -2h-1"/><path d="M8 15a6 6 0 1 0 12 0v-3"/><path d="M11 3v2"/><path d="M6 3v2"/><path d="M20 10m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/>',
            'book'             => '<path d="M3 19a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6a9 9 0 0 1 9 0a9 9 0 0 1 9 0"/><path d="M3 6l0 13"/><path d="M12 6l0 13"/><path d="M21 6l0 13"/>',
            'plug'             => '<path d="M9.785 6l8.215 8.215l-2.054 2.054a5.81 5.81 0 1 1 -8.215 -8.215l2.054 -2.054z"/><path d="M4 20l3.5 -3.5"/><path d="M15 4l-3.5 3.5"/><path d="M20 9l-3.5 3.5"/>',
            'logout'           => '<path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/>',
            'layout-grid'      => '<path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/><path d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z"/>',
            'building-store'   => '<path d="M3 21l18 0"/><path d="M3 7v1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1m0 1a3 3 0 0 0 6 0v-1h-18l2 -4h14l2 4"/><path d="M5 21l0 -10.15"/><path d="M19 21l0 -10.15"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/>',
            'shield-lock'     => '<path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/><path d="M12 11m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 12l0 2.5"/>',
            'shield-check'    => '<path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06"/><path d="M15 19l2 2l4 -4"/>',
            'flame'            => '<path d="M12 12c2 -2.96 0 -7 -1 -8c0 3.038 -1.773 4.741 -3 6c-1.226 1.26 -2 3.24 -2 5a6 6 0 1 0 12 0c0 -1.532 -1.056 -3.94 -2 -5c-1.786 3 -2.791 3 -4 2z"/>',
            'user-shield'     => '<path d="M6 21v-2a4 4 0 0 1 4 -4h2"/><path d="M22 16c0 4 -2.5 6 -3.5 6s-3.5 -2 -3.5 -6c1 0 2.5 -.5 3.5 -1.5c1 1 2.5 1.5 3.5 1.5z"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/>',
            'eraser'           => '<path d="M19 20h-10.5l-4.21 -4.3a1 1 0 0 1 0 -1.41l10 -10a1 1 0 0 1 1.41 0l5 5a1 1 0 0 1 0 1.41l-9.2 9.3"/><path d="M18 13.3l-6.3 -6.3"/>',
            'typography'       => '<path d="M4 20l3 0"/><path d="M14 20l7 0"/><path d="M6.9 15l6.9 0"/><path d="M10.2 6.3l5.8 13.7"/><path d="M5 20l6 -16l2 0l7 16"/>',
            'table'            => '<path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z"/><path d="M3 10l18 0"/><path d="M10 3l0 18"/>',
            'list'             => '<path d="M9 6l11 0"/><path d="M9 12l11 0"/><path d="M9 18l11 0"/><path d="M5 6l0 .01"/><path d="M5 12l0 .01"/><path d="M5 18l0 .01"/>',
            'palette'          => '<path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25"/><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>',
            'code'             => '<path d="M7 8l-4 4l4 4"/><path d="M17 8l4 4l-4 4"/><path d="M14 4l-4 16"/>',
            'users-group'      => '<path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1"/><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M17 10h2a2 2 0 0 1 2 2v1"/><path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M3 13v-1a2 2 0 0 1 2 -2h2"/>',
            'receipt'          => '<path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16l-3 -2l-2 2l-2 -2l-2 2l-2 -2l-3 2"/><path d="M14 8h-2.5a1.5 1.5 0 0 0 0 3h1a1.5 1.5 0 0 1 0 3h-2.5m2 0v1.5m0 -9v1.5"/>',
            'trash-x'          => '<path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>',
        ];

        $paths = $icons[$name] ?? '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" '
             . 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
             . 'stroke-linecap="round" stroke-linejoin="round" class="icon">'
             . '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>'
             . $paths
             . '</svg>';
    }
}

if (!function_exists('_adminFallbackIsActive')) {
    function _adminFallbackIsActive(string $path): bool
    {
        $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
        $currentPath = parse_url($requestUri, PHP_URL_PATH) ?? '';
        $currentQuery = parse_url($requestUri, PHP_URL_QUERY) ?? '';

        $targetPath = parse_url($path, PHP_URL_PATH) ?? $path;
        $targetQuery = parse_url($path, PHP_URL_QUERY) ?? '';

        if (rtrim($currentPath, '/') !== rtrim($targetPath, '/')) {
            return false;
        }

        if ($targetQuery === '') {
            return true;
        }

        parse_str($currentQuery, $currentParams);
        parse_str($targetQuery, $targetParams);

        foreach ($targetParams as $key => $value) {
            if (!array_key_exists($key, $currentParams) || (string)$currentParams[$key] !== (string)$value) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('_adminFallbackRenderIcon')) {
    function _adminFallbackRenderIcon(string $icon, int $size = 20): string
    {
        if ($icon === '') {
            return '';
        }

        if (preg_match('/^[a-z0-9-]+$/i', $icon) === 1) {
            return _fallbackTablerIcon($icon, $size);
        }

        if (str_contains($icon, '<')) {
            return $icon;
        }

        return '<span class="icon-text">' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (!function_exists('_adminFallbackCollectDynamicMenuGroups')) {
    function _adminFallbackCollectDynamicMenuGroups(string $currentPage = ''): array
    {
        $dynamicGroups = [];

        global $cms_admin_menu;
        if ((!isset($cms_admin_menu) || empty($cms_admin_menu)) && class_exists('CMS\\Hooks')) {
            \CMS\Hooks::doAction('cms_admin_menu');
        }

        if (function_exists('get_registered_admin_menus')) {
            foreach (get_registered_admin_menus() as $menu) {
                if (($menu['type'] ?? '') !== 'plugin_page' || !empty($menu['hidden'])) {
                    continue;
                }

                $menuSlug = (string)($menu['menu_slug'] ?? '');
                if ($menuSlug === '') {
                    continue;
                }

                $menuIcon = (string)($menu['icon_url'] ?? 'puzzle');
                if ($menuIcon === '') {
                    $menuIcon = 'puzzle';
                }

                $children = [];
                $hasActiveChild = false;

                foreach (($menu['children'] ?? []) as $child) {
                    $childSlug = (string)($child['menu_slug'] ?? '');
                    if ($childSlug === '') {
                        continue;
                    }

                    $childUrl = (string)($child['url'] ?? ('/admin/plugins/' . $menuSlug . '/' . $childSlug));
                    $childActive = _adminFallbackIsActive($childUrl) || $currentPage === $childSlug;
                    $hasActiveChild = $hasActiveChild || $childActive;

                    $children[] = [
                        'slug' => $childSlug,
                        'label' => (string)($child['menu_title'] ?? $childSlug),
                        'icon' => 'puzzle',
                        'url' => $childUrl,
                        'active' => $childActive,
                    ];
                }

                if ($children !== []) {
                    $dynamicGroups[] = [
                        'type' => 'group',
                        'label' => (string)($menu['menu_title'] ?? $menuSlug),
                        'icon' => $menuIcon,
                        'active' => $hasActiveChild,
                        'children' => $children,
                    ];
                    continue;
                }

                $menuUrl = '/admin/plugins/' . $menuSlug . '/' . $menuSlug;
                $dynamicGroups[] = [
                    'type' => 'item',
                    'slug' => $menuSlug,
                    'label' => (string)($menu['menu_title'] ?? $menuSlug),
                    'icon' => $menuIcon,
                    'url' => $menuUrl,
                    'active' => _adminFallbackIsActive($menuUrl) || $currentPage === $menuSlug,
                ];
            }
        }

        if (class_exists('CMS\\Hooks')) {
            $legacyMenuItems = \CMS\Hooks::applyFilters('admin_menu_items', []);

            foreach ($legacyMenuItems as $item) {
                if (!is_array($item) || empty($item['url']) || empty($item['label'])) {
                    continue;
                }

                $icon = (string)($item['icon'] ?? 'puzzle');
                if ($icon === '') {
                    $icon = 'puzzle';
                }

                $children = [];
                $hasActiveChild = false;

                foreach (($item['children'] ?? []) as $child) {
                    if (!is_array($child) || empty($child['url']) || empty($child['label'])) {
                        continue;
                    }

                    $childActive = !empty($child['active']) || _adminFallbackIsActive((string)$child['url']) || $currentPage === (string)($child['slug'] ?? '');
                    $hasActiveChild = $hasActiveChild || $childActive;

                    $children[] = [
                        'slug' => (string)($child['slug'] ?? ''),
                        'label' => (string)$child['label'],
                        'icon' => (string)($child['icon'] ?? 'puzzle'),
                        'url' => (string)$child['url'],
                        'active' => $childActive,
                    ];
                }

                if ($children !== []) {
                    $dynamicGroups[] = [
                        'type' => 'group',
                        'label' => (string)$item['label'],
                        'icon' => $icon,
                        'active' => !empty($item['active']) || $hasActiveChild,
                        'children' => $children,
                    ];
                    continue;
                }

                $dynamicGroups[] = [
                    'type' => 'item',
                    'slug' => (string)($item['slug'] ?? ''),
                    'label' => (string)$item['label'],
                    'icon' => $icon,
                    'url' => (string)$item['url'],
                    'active' => !empty($item['active']) || _adminFallbackIsActive((string)$item['url']) || $currentPage === (string)($item['slug'] ?? ''),
                ];
            }
        }

        return $dynamicGroups;
    }
}

if (!function_exists('renderAdminSidebarStyles')) {
    function renderAdminSidebarStyles(): void
    {
        ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/tabler/css/tabler.min.css">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=20260306">
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-tabler.css?v=20260306">
        <script src="<?php echo SITE_URL; ?>/assets/tabler/js/tabler.min.js" defer></script>
        <?php
        if (class_exists('\CMS\Services\EditorService')) {
            \CMS\Services\EditorService::getInstance()->enqueueEditorAssets();
        }
    }
}

if (!function_exists('renderAdminSidebar')) {
    function renderAdminSidebar(string $currentPage = ''): void
    {
        $menuGroups = [
            [
                'type' => 'item', 'slug' => 'dashboard', 'label' => 'Dashboard',
                'icon' => 'layout-dashboard', 'url' => '/admin',
            ],
            [
                'type' => 'group', 'label' => 'Landing Page', 'icon' => 'home',
                'children' => [
                    ['slug' => 'landing-header', 'label' => 'Header', 'url' => '/admin/landing-page?section=header'],
                    ['slug' => 'landing-content', 'label' => 'Content', 'url' => '/admin/landing-page?section=content'],
                    ['slug' => 'landing-footer', 'label' => 'Footer', 'url' => '/admin/landing-page?section=footer'],
                    ['slug' => 'landing-design', 'label' => 'Design', 'url' => '/admin/landing-page?section=design'],
                    ['slug' => 'landing-plugins', 'label' => 'Plugins', 'icon' => 'puzzle', 'url' => '/admin/landing-page?section=plugins'],
                    ['slug' => 'landing-settings', 'label' => 'Einstellungen', 'url' => '/admin/landing-page?section=settings'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Seiten & Beiträge', 'icon' => 'files',
                'children' => [
                    ['slug' => 'pages', 'label' => 'Seiten', 'icon' => 'file', 'url' => '/admin/pages'],
                    ['slug' => 'posts', 'label' => 'Beiträge', 'icon' => 'article', 'url' => '/admin/posts'],
                    ['slug' => 'comments', 'label' => 'Kommentare', 'icon' => 'messages', 'url' => '/admin/comments'],
                    ['slug' => 'table-of-contents', 'label' => 'Inhaltsverzeichnis', 'icon' => 'list', 'url' => '/admin/table-of-contents'],
                    ['slug' => 'site-tables', 'label' => 'Tabellen', 'icon' => 'table', 'url' => '/admin/site-tables'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Medienverwaltung', 'icon' => 'photo',
                'children' => [
                    ['slug' => 'media', 'label' => 'Medien', 'url' => '/admin/media'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Benutzer & Gruppen', 'icon' => 'users',
                'children' => [
                    ['slug' => 'users', 'label' => 'Benutzer', 'icon' => 'user', 'url' => '/admin/users'],
                    ['slug' => 'groups', 'label' => 'Gruppen', 'icon' => 'users-group', 'url' => '/admin/groups'],
                    ['slug' => 'rbac', 'label' => 'Rollen & Rechte', 'icon' => 'user-shield', 'url' => '/admin/rbac'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Aboverwaltung', 'icon' => 'credit-card',
                'children' => [
                    ['slug' => 'subscriptions', 'label' => 'Pakete', 'url' => '/admin/subscriptions'],
                    ['slug' => 'subscription-settings', 'label' => 'Abo-Einstellungen', 'icon' => 'receipt', 'url' => '/admin/subscription-settings'],
                    ['slug' => 'orders', 'label' => 'Bestellungen', 'icon' => 'receipt', 'url' => '/admin/orders'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Themes & Design', 'icon' => 'brush',
                'children' => [
                    ['slug' => 'themes', 'label' => 'Themes', 'url' => '/admin/themes'],
                    ['slug' => 'theme-customizer', 'label' => 'Design Editor', 'icon' => 'palette', 'url' => '/admin/theme-customizer'],
                    ['slug' => 'theme-settings', 'label' => 'Theme-Einstellungen', 'icon' => 'settings', 'url' => '/admin/theme-settings'],
                    ['slug' => 'theme', 'label' => 'Theme Editor', 'icon' => 'code', 'url' => '/admin/theme-editor'],
                    ['slug' => 'theme-marketplace', 'label' => 'Theme Marketplace', 'icon' => 'building-store', 'url' => '/admin/theme-marketplace'],
                    ['slug' => 'fonts-local', 'label' => 'Font Manager', 'icon' => 'typography', 'url' => '/admin/fonts-local'],
                    ['slug' => 'menus', 'label' => 'Menü Editor', 'url' => '/admin/menus'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Member Dashboard', 'icon' => 'layout-grid',
                'children' => [
                    ['slug' => 'member-dashboard', 'label' => 'Widgets', 'url' => '/admin/member-dashboard'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'SEO & Performance', 'icon' => 'chart-line',
                'children' => [
                    ['slug' => 'seo', 'label' => 'SEO Dashboard', 'url' => '/admin/seo'],
                    ['slug' => 'performance', 'label' => 'Performance', 'url' => '/admin/performance'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Analytics', 'icon' => 'chart-bar',
                'children' => [
                    ['slug' => 'analytics', 'label' => 'Übersicht', 'url' => '/admin/analytics'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Recht & Sicherheit', 'icon' => 'scale',
                'children' => [
                    ['slug' => 'legal-sites', 'label' => 'Legal Sites', 'url' => '/admin/legal-sites'],
                    ['slug' => 'cookies', 'label' => 'Cookie Manager', 'url' => '/admin/cookies'],
                    ['slug' => 'cms-firewall', 'label' => 'Firewall', 'icon' => 'shield-lock', 'url' => '/admin/cms-firewall'],
                    ['slug' => 'security-audit', 'label' => 'Sicherheits-Audit', 'icon' => 'shield-check', 'url' => '/admin/security-audit'],
                    ['slug' => 'antispam', 'label' => 'AntiSpam', 'icon' => 'flame', 'url' => '/admin/antispam'],
                    ['slug' => 'data-access', 'label' => 'Datenschutz-Auskunft', 'icon' => 'user', 'url' => '/admin/data-access'],
                    ['slug' => 'data-deletion', 'label' => 'Löschanträge', 'icon' => 'trash-x', 'url' => '/admin/data-deletion'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'Plugins', 'icon' => 'puzzle',
                'children' => [
                    ['slug' => 'plugins', 'label' => 'Verwalten', 'icon' => 'plug', 'url' => '/admin/plugins'],
                    ['slug' => 'plugin-marketplace', 'label' => 'Marketplace', 'icon' => 'building-store', 'url' => '/admin/plugin-marketplace'],
                ],
            ],
            [
                'type' => 'group', 'label' => 'System & Einstellungen', 'icon' => 'settings',
                'children' => [
                    ['slug' => 'settings', 'label' => 'Einstellungen', 'icon' => 'settings', 'url' => '/admin/settings'],
                    ['slug' => 'updates', 'label' => 'Updates', 'icon' => 'refresh', 'url' => '/admin/updates'],
                    ['slug' => 'backup', 'label' => 'Backup & Restore', 'icon' => 'database', 'url' => '/admin/backup'],
                    ['slug' => 'system', 'label' => 'Info & Diagnose', 'icon' => 'stethoscope', 'url' => '/admin/system'],
                    ['slug' => 'support', 'label' => 'Support & Docs', 'icon' => 'book', 'url' => '/admin/support'],
                ],
            ],
        ];

        $dynamicMenuGroups = _adminFallbackCollectDynamicMenuGroups($currentPage);
        if ($dynamicMenuGroups !== []) {
            $insertIndex = count($menuGroups) - 1;
            array_splice($menuGroups, $insertIndex, 0, $dynamicMenuGroups);
        }
        ?>
        <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                        aria-controls="sidebar-menu" aria-expanded="false" aria-label="Navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <h1 class="navbar-brand navbar-brand-autodark" style="margin:0;">
                    <a href="<?php echo SITE_URL; ?>/admin" style="text-decoration:none;">
                        <img src="<?php echo SITE_URL; ?>/assets/images/365CMS-DASHBOARD-Admin-100px.png"
                             alt="<?php echo htmlspecialchars(SITE_NAME); ?> Admin" height="64">
                    </a>
                </h1>

                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">
                        <?php foreach ($menuGroups as $item):
                            $type = $item['type'] ?? 'group';

                            if ($type === 'item'):
                                $active = _adminFallbackIsActive($item['url']) || $currentPage === $item['slug'];
                        ?>
                            <li class="nav-item">
                                <a class="nav-link<?php echo $active ? ' active' : ''; ?>"
                                   href="<?php echo htmlspecialchars($item['url']); ?>">
                                    <span class="nav-link-icon"><?php echo _adminFallbackRenderIcon((string)$item['icon'], 20); ?></span>
                                    <span class="nav-link-title"><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                            </li>

                        <?php elseif ($type === 'group'):
                            $groupOpen = !empty($item['active']);
                            foreach ($item['children'] as $child) {
                                if (_adminFallbackIsActive($child['url']) || $currentPage === $child['slug'] || !empty($child['active'])) {
                                    $groupOpen = true;
                                    break;
                                }
                            }
                            $groupId = 'navgroup-' . md5($item['label']);
                        ?>
                            <li class="nav-divider"></li>
                            <li class="nav-item dropdown<?php echo $groupOpen ? ' show' : ''; ?>">
                                <a class="nav-link dropdown-toggle<?php echo $groupOpen ? '' : ' collapsed'; ?>"
                                   href="#<?php echo $groupId; ?>"
                                   data-bs-toggle="collapse"
                                   role="button"
                                   aria-expanded="<?php echo $groupOpen ? 'true' : 'false'; ?>">
                                    <span class="nav-link-icon"><?php echo _adminFallbackRenderIcon((string)$item['icon'], 20); ?></span>
                                    <span class="nav-link-title"><?php echo htmlspecialchars($item['label']); ?></span>
                                </a>
                                <div class="dropdown-menu<?php echo $groupOpen ? ' show collapse show' : ' collapse'; ?>" id="<?php echo $groupId; ?>">
                                    <?php foreach ($item['children'] as $child):
                                        $childActive = _adminFallbackIsActive($child['url']) || $currentPage === $child['slug'] || !empty($child['active']);
                                        $childIcon = $child['icon'] ?? '';
                                    ?>
                                        <a class="dropdown-item<?php echo $childActive ? ' active' : ''; ?>"
                                           href="<?php echo htmlspecialchars($child['url']); ?>">
                                            <?php if ($childIcon): ?>
                                                <span class="nav-link-icon"><?php echo _adminFallbackRenderIcon((string)$childIcon, 16); ?></span>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($child['label']); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>

                    <div class="sidebar-footer">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                    <span class="nav-link-icon"><?php echo _fallbackTablerIcon('home', 18); ?></span>
                                    <span class="nav-link-title">Zur Website</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/logout">
                                    <span class="nav-link-icon"><?php echo _fallbackTablerIcon('logout', 18); ?></span>
                                    <span class="nav-link-title">Abmelden</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </aside>
        <?php
    }
}

if (!function_exists('renderAdminLayoutStart')) {
    function renderAdminLayoutStart(string $title, string $activeSlug = ''): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?> – <?php echo htmlspecialchars(SITE_NAME); ?></title>
            <?php
            renderAdminSidebarStyles();
            if (class_exists('CMS\\Hooks')) {
                \CMS\Hooks::doAction('head');
                \CMS\Hooks::doAction('admin_head');
            }
            ?>
        </head>
        <body class="admin-body">
            <div class="page">
                <?php renderAdminSidebar($activeSlug); ?>
                <div class="page-wrapper">
                    <div class="page-body">
                        <div class="container-xl">
        <?php
    }
}

if (!function_exists('renderAdminLayoutEnd')) {
    function renderAdminLayoutEnd(): void
    {
        renderAdminConfirmModal();
        ?>
                        </div>
                    </div>
                </div>
            </div>
            <script src="<?php echo SITE_URL; ?>/assets/js/admin.js?v=20260305"></script>
            <?php
            if (class_exists('CMS\\Hooks')) {
                \CMS\Hooks::doAction('body_end');
                \CMS\Hooks::doAction('admin_body_end');
            }
            ?>
        </body>
        </html>
        <?php
    }
}

/* ────────────────────────────────────────────────────────────
 * Standardisierte Admin-UI-Komponenten
 * ──────────────────────────────────────────────────────────── */

/**
 * Alert-Banner (Erfolg, Fehler, Info, Warnung).
 *
 * @param string $type    success|error|danger|warning|info
 * @param string $message Nachrichtentext (wird escaped)
 * @param bool   $dismiss Schließbar?
 */
if (!function_exists('renderAdminAlert')) {
    function renderAdminAlert(string $type, string $message, bool $dismiss = true): void
    {
        $map = [
            'success' => ['alert-success', '✅'],
            'error'   => ['alert-danger',  '❌'],
            'danger'  => ['alert-danger',  '❌'],
            'warning' => ['alert-warning', '⚠️'],
            'info'    => ['alert-info',    'ℹ️'],
        ];
        [$cls, $icon] = $map[$type] ?? $map['info'];
        ?>
        <div class="alert <?php echo $cls; ?><?php echo $dismiss ? ' alert-dismissible' : ''; ?>" role="alert">
            <?php echo $icon . ' ' . htmlspecialchars($message); ?>
            <?php if ($dismiss): ?>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            <?php endif; ?>
        </div>
        <?php
    }
}

/**
 * Gibt $success / $error Alerts aus (Konvention auf Admin-Seiten).
 */
if (!function_exists('renderAdminAlerts')) {
    function renderAdminAlerts(): void
    {
        global $success, $error;
        if (!empty($success)) {
            renderAdminAlert('success', $success);
        }
        if (!empty($error)) {
            renderAdminAlert('error', $error);
        }
    }
}

/**
 * Standard-Admin-Card mit optionalem Titel + Header-Actions.
 *
 * @param string        $title   Kartentitel (optional, mit Emoji)
 * @param string|null   $body    HTML-Inhalt (null → Card bleibt offen für eigenen Inhalt)
 * @param string        $actions HTML für Header-Buttons (optional)
 */
if (!function_exists('renderAdminCard')) {
    function renderAdminCard(string $title = '', ?string $body = null, string $actions = ''): void
    {
        echo '<div class="card">';
        if ($title !== '' || $actions !== '') {
            echo '<div class="card-header"><h3 class="card-title">' . $title . '</h3>';
            if ($actions !== '') {
                echo '<div class="card-actions">' . $actions . '</div>';
            }
            echo '</div>';
        }
        if ($body !== null) {
            echo '<div class="card-body">' . $body . '</div></div>';
        }
    }
}

/**
 * Standard-Bestätigungs-Modal (Löschen, Deaktivieren etc.).
 *
 * Ersetzt window.confirm() auf Admin-Seiten.
 * Öffnen: cmsConfirm('Wirklich löschen?', () => { ... })
 *
 * Muss einmal pro Seite gerendert werden (idempotent).
 */
if (!function_exists('renderAdminConfirmModal')) {
    function renderAdminConfirmModal(): void
    {
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        ?>
        <div class="modal modal-blur fade" id="cmsConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="cmsConfirmTitle">Bestätigung</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <p id="cmsConfirmMessage" class="text-secondary"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        <button type="button" class="btn btn-danger" id="cmsConfirmAction">Bestätigen</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function(){
            var _cb = null;
            var _modalEl = document.getElementById('cmsConfirmModal');
            var _bsModal = bootstrap.Modal.getOrCreateInstance(_modalEl);
            window.cmsConfirm = function(msg, callback, title) {
                document.getElementById('cmsConfirmMessage').textContent = msg;
                document.getElementById('cmsConfirmTitle').textContent = title || 'Bestätigung';
                _cb = callback;
                _bsModal.show();
            };
            window.cmsConfirmClose = function() {
                _bsModal.hide();
                _cb = null;
            };
            document.getElementById('cmsConfirmAction').addEventListener('click', function() {
                if (_cb) { _cb(); }
                cmsConfirmClose();
            });
        })();
        </script>
        <?php
    }
}
