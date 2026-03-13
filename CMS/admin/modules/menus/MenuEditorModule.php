<?php
declare(strict_types=1);

/**
 * Menu Editor Module – Menü-Verwaltung
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\ThemeManager;

class MenuEditorModule
{
    private Database $db;
    private string $prefix;

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTables();
        $this->syncThemeMenus();
    }

    /**
     * Daten laden
     */
    public function getData(int $currentMenuId = 0): array
    {
        $this->syncThemeMenus();

        $menus     = $this->getMenus();
        $locations = $this->getMenuLocations();
        $pages     = $this->getPages();

        $currentMenu  = null;
        $menuItems    = [];

        if ($currentMenuId > 0) {
            foreach ($menus as $menu) {
                if ((int)$menu->id === $currentMenuId) {
                    $currentMenu = $menu;
                    break;
                }
            }
            if ($currentMenu) {
                $menuItems = $this->getMenuItems($currentMenuId);
            }
        }

        return [
            'menus'       => $menus,
            'currentMenu' => $currentMenu,
            'menuItems'   => $menuItems,
            'locations'   => $locations,
            'pages'       => $pages,
        ];
    }

    /**
     * Menü erstellen/bearbeiten
     */
    public function saveMenu(array $post): array
    {
        $menuId   = (int)($post['menu_id'] ?? 0);
        $name     = trim(strip_tags($post['menu_name'] ?? ''));
        $location = preg_replace('/[^a-zA-Z0-9_-]/', '', $post['menu_location'] ?? '');

        if (empty($name)) {
            return ['success' => false, 'error' => 'Menü-Name darf nicht leer sein.'];
        }

        try {
            if ($menuId > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}menus SET name = ?, location = ? WHERE id = ?",
                    [$name, $location, $menuId]
                );
                return ['success' => true, 'message' => 'Menü aktualisiert.'];
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}menus (name, location) VALUES (?, ?)",
                    [$name, $location]
                );

                if ($location !== '') {
                    ThemeManager::instance()->saveMenu($location, ThemeManager::instance()->getMenu($location));
                }

                return ['success' => true, 'message' => 'Menü erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Menü löschen
     */
    public function deleteMenu(int $menuId): array
    {
        if ($menuId <= 0) {
            return ['success' => false, 'error' => 'Ungültige Menü-ID.'];
        }

        try {
            $menu = $this->db->get_row(
                "SELECT * FROM {$this->prefix}menus WHERE id = ? LIMIT 1",
                [$menuId]
            );

            if ($menu && !empty($menu->location)) {
                ThemeManager::instance()->saveMenu((string)$menu->location, []);
            }

            $this->db->query("DELETE FROM {$this->prefix}menu_items WHERE menu_id = ?", [$menuId]);
            $this->db->query("DELETE FROM {$this->prefix}menus WHERE id = ?", [$menuId]);
            return ['success' => true, 'message' => 'Menü gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Menü-Items speichern (JSON)
     */
    public function saveItems(int $menuId, string $itemsJson): array
    {
        if ($menuId <= 0) {
            return ['success' => false, 'error' => 'Ungültige Menü-ID.'];
        }

        $items = \CMS\Json::decodeArray($itemsJson, []);
        if (!is_array($items)) {
            return ['success' => false, 'error' => 'Ungültiges JSON-Format.'];
        }

        try {
            $menu = $this->db->get_row(
                "SELECT * FROM {$this->prefix}menus WHERE id = ? LIMIT 1",
                [$menuId]
            );

            $normalizedItems = $this->sanitizeEditorItems($items);

            // Alle bestehenden Items löschen
            $this->db->execute("DELETE FROM {$this->prefix}menu_items WHERE menu_id = ?", [$menuId]);

            $position = 0;
            $tree = $this->buildEditorTree($normalizedItems);
            $this->insertEditorTree($menuId, $tree, 0, $position);

            if ($menu && !empty($menu->location)) {
                ThemeManager::instance()->saveMenu((string)$menu->location, $this->normalizeThemeItems($normalizedItems));
            }

            return ['success' => true, 'message' => 'Menü-Items gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Alle Menüs laden
     */
    private function getMenus(): array
    {
        try {
            return $this->db->get_results(
                "SELECT m.*, (SELECT COUNT(*) FROM {$this->prefix}menu_items WHERE menu_id = m.id) AS item_count 
                 FROM {$this->prefix}menus m ORDER BY m.name ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Menü-Items laden
     */
    private function getMenuItems(int $menuId): array
    {
        try {
            return $this->db->get_results(
                "SELECT * FROM {$this->prefix}menu_items WHERE menu_id = ? ORDER BY position ASC",
                [$menuId]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Menü-Positionen aus Theme-JSON laden
     */
    private function getMenuLocations(): array
    {
        $locations = [];

        try {
            foreach (ThemeManager::instance()->getMenuLocations() as $location) {
                if (is_array($location) && isset($location['slug'], $location['label'])) {
                    $locations[(string)$location['slug']] = (string)$location['label'];
                    continue;
                }

                if (is_string($location)) {
                    $locations[$location] = $location;
                }
            }
        } catch (\Throwable $e) {
            // Fallback unten verwenden
        }

        if (empty($locations)) {
            $locations = ['primary' => 'Hauptnavigation', 'footer' => 'Footer-Menü'];
        }

        return $locations;
    }

    /**
     * Registrierte Theme-Menüs in die Admin-Tabellen spiegeln.
     */
    private function syncThemeMenus(): void
    {
        $locations = $this->getMenuLocations();

        if (empty($locations)) {
            return;
        }

        foreach ($locations as $slug => $label) {
            $menu = $this->db->get_row(
                "SELECT * FROM {$this->prefix}menus WHERE location = ? LIMIT 1",
                [$slug]
            );

            if (!$menu) {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}menus (name, location) VALUES (?, ?)",
                    [$label, $slug]
                );

                $menu = $this->db->get_row(
                    "SELECT * FROM {$this->prefix}menus WHERE location = ? LIMIT 1",
                    [$slug]
                );
            }

            if (!$menu) {
                continue;
            }

            $this->syncMenuItemsFromThemeSettings((int)$menu->id, $slug);
        }
    }

    /**
     * Spiegelt die eigentlichen Theme-Menüeinträge in die Admin-Tabelle.
     */
    private function syncMenuItemsFromThemeSettings(int $menuId, string $location): void
    {
        $items = ThemeManager::instance()->getMenu($location);

        $this->db->execute("DELETE FROM {$this->prefix}menu_items WHERE menu_id = ?", [$menuId]);

        $position = 0;
        $this->insertThemeItemsRecursive($menuId, $items, 0, $position);
    }

    /**
     * Normalisiert Menü-Items aus dem Admin-Editor für ThemeManager::saveMenu().
     */
    private function normalizeThemeItems(array $items): array
    {
        $normalized = [];

        foreach ($this->buildEditorTree($items) as $item) {
            $node = $this->buildThemeNodeFromEditorItem($item);
            if ($node !== null) {
                $normalized[] = $node;
            }
        }

        return $normalized;
    }

    private function sanitizeEditorItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim(strip_tags((string)($item['title'] ?? $item['label'] ?? '')));
            $url = filter_var((string)($item['url'] ?? ''), FILTER_SANITIZE_URL);
            if ($title === '' || $url === '') {
                continue;
            }

            $id = trim((string)($item['id'] ?? 'item-' . $index));
            if ($id === '') {
                $id = 'item-' . $index;
            }

            $parentId = $item['parent_id'] ?? 0;
            if (is_string($parentId)) {
                $parentId = trim($parentId);
            }

            $normalized[] = [
                'id' => (string) $id,
                'title' => $title,
                'url' => $url,
                'target' => ((string)($item['target'] ?? '_self')) === '_blank' ? '_blank' : '_self',
                'icon' => trim(strip_tags((string)($item['icon'] ?? ''))),
                'parent_id' => $parentId === '' || $parentId === 0 || $parentId === '0' ? 0 : (string) $parentId,
            ];
        }

        return $normalized;
    }

    private function buildEditorTree(array $items): array
    {
        $nodes = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $item['children'] = [];
            $nodes[(string)$item['id']] = $item;
        }

        $tree = [];
        foreach ($nodes as $id => $item) {
            $parentId = $item['parent_id'] ?? 0;
            if ($parentId !== 0 && $parentId !== '0' && $parentId !== $id && isset($nodes[(string)$parentId])) {
                $nodes[(string)$parentId]['children'][] = &$nodes[$id];
                continue;
            }

            $tree[] = &$nodes[$id];
        }

        return array_values($tree);
    }

    private function insertEditorTree(int $menuId, array $items, int $parentDbId, int &$position): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}menu_items (menu_id, parent_id, title, url, target, icon, position) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $menuId,
                    $parentDbId,
                    (string) ($item['title'] ?? ''),
                    (string) ($item['url'] ?? '#'),
                    ((string)($item['target'] ?? '_self')) === '_blank' ? '_blank' : '_self',
                    (string) ($item['icon'] ?? ''),
                    $position++,
                ]
            );

            $currentId = (int) $this->db->lastInsertId();
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            if ($children !== []) {
                $this->insertEditorTree($menuId, $children, $currentId, $position);
            }
        }
    }

    private function insertThemeItemsRecursive(int $menuId, array $items, int $parentId, int &$position): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string)($item['label'] ?? $item['title'] ?? ''));
            $url   = trim((string)($item['url'] ?? '#'));
            $target = ((string)($item['target'] ?? '_self')) === '_blank' ? '_blank' : '_self';
            $icon  = trim((string)($item['icon'] ?? ''));

            if ($title === '') {
                continue;
            }

            $this->db->execute(
                "INSERT INTO {$this->prefix}menu_items (menu_id, parent_id, title, url, target, icon, position) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$menuId, $parentId, $title, $url, $target, $icon, $position++]
            );

            $currentId = (int) $this->db->lastInsertId();
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];
            if ($children !== []) {
                $this->insertThemeItemsRecursive($menuId, $children, $currentId, $position);
            }
        }
    }

    private function buildThemeNodeFromEditorItem(array $item): ?array
    {
        $label = trim((string)($item['title'] ?? $item['label'] ?? ''));
        $url   = filter_var((string)($item['url'] ?? ''), FILTER_SANITIZE_URL);

        if ($label === '' || $url === '') {
            return null;
        }

        $node = [
            'label'  => $label,
            'url'    => $url,
            'target' => ((string)($item['target'] ?? '_self')) === '_blank' ? '_blank' : '_self',
            'icon'   => trim(strip_tags((string)($item['icon'] ?? ''))),
        ];

        $children = [];
        foreach (($item['children'] ?? []) as $child) {
            if (!is_array($child)) {
                continue;
            }

            $childNode = $this->buildThemeNodeFromEditorItem($child);
            if ($childNode !== null) {
                $children[] = $childNode;
            }
        }

        if ($children !== []) {
            $node['children'] = $children;
        }

        return $node;
    }

    /**
     * Seiten für "Link zu Seite" laden
     */
    private function getPages(): array
    {
        try {
            return $this->db->get_results(
                "SELECT id, title, slug FROM {$this->prefix}pages WHERE status = 'published' ORDER BY title ASC"
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Tabellen anlegen
     */
    private function ensureTables(): void
    {
        try {
            $this->db->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$this->prefix}menus (
                id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name     VARCHAR(255) NOT NULL,
                location VARCHAR(100) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->db->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$this->prefix}menu_items (
                id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                menu_id   INT UNSIGNED NOT NULL,
                parent_id INT UNSIGNED DEFAULT 0,
                title     VARCHAR(255) NOT NULL,
                url       VARCHAR(500) NOT NULL DEFAULT '#',
                target    VARCHAR(10) DEFAULT '_self',
                icon      VARCHAR(100) DEFAULT NULL,
                position  INT UNSIGNED DEFAULT 0,
                INDEX idx_menu (menu_id),
                INDEX idx_parent (parent_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            // Tabellen existieren bereits
        }
    }
}
