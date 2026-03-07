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
    }

    /**
     * Daten laden
     */
    public function getData(int $currentMenuId = 0): array
    {
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
                $this->db->query(
                    "UPDATE {$this->prefix}menus SET name = ?, location = ? WHERE id = ?",
                    [$name, $location, $menuId]
                );
                return ['success' => true, 'message' => 'Menü aktualisiert.'];
            } else {
                $this->db->query(
                    "INSERT INTO {$this->prefix}menus (name, location) VALUES (?, ?)",
                    [$name, $location]
                );
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

        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            return ['success' => false, 'error' => 'Ungültiges JSON-Format.'];
        }

        try {
            // Alle bestehenden Items löschen
            $this->db->query("DELETE FROM {$this->prefix}menu_items WHERE menu_id = ?", [$menuId]);

            // Neue Items einfügen
            foreach ($items as $index => $item) {
                $this->db->query(
                    "INSERT INTO {$this->prefix}menu_items (menu_id, parent_id, title, url, target, icon, position) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $menuId,
                        (int)($item['parent_id'] ?? 0),
                        trim(strip_tags($item['title'] ?? '')),
                        filter_var($item['url'] ?? '', FILTER_SANITIZE_URL),
                        ($item['target'] ?? '') === '_blank' ? '_blank' : '_self',
                        trim(strip_tags($item['icon'] ?? '')),
                        $index,
                    ]
                );
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
        $locations = ['primary' => 'Hauptnavigation', 'footer' => 'Footer-Menü'];

        try {
            $themeSlug = ThemeManager::instance()->getActiveThemeSlug();
            $jsonPath  = THEME_PATH . $themeSlug . '/theme.json';
            if (file_exists($jsonPath)) {
                $json = json_decode(file_get_contents($jsonPath), true);
                if (isset($json['menus']) && is_array($json['menus'])) {
                    $locations = array_merge($locations, $json['menus']);
                }
            }
        } catch (\Throwable $e) {
            // Defaults verwenden
        }

        return $locations;
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
