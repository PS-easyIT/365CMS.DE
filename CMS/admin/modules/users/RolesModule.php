<?php
declare(strict_types=1);

/**
 * Roles Module – Rollen- und Rechteverwaltung
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class RolesModule
{
    private Database $db;
    private string $prefix;

    private const ROLES = ['admin', 'editor', 'author', 'member'];

    private const CAPABILITIES = [
        'pages'    => ['pages.view', 'pages.create', 'pages.edit', 'pages.delete', 'pages.publish'],
        'posts'    => ['posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.publish'],
        'media'    => ['media.view', 'media.upload', 'media.delete', 'media.settings'],
        'users'    => ['users.view', 'users.create', 'users.edit', 'users.delete', 'users.roles'],
        'themes'   => ['themes.view', 'themes.activate', 'themes.customize', 'themes.install'],
        'plugins'  => ['plugins.view', 'plugins.activate', 'plugins.install', 'plugins.settings'],
        'settings' => ['settings.view', 'settings.edit', 'settings.system'],
        'comments' => ['comments.view', 'comments.moderate', 'comments.delete'],
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Rollen-Daten mit Berechtigungen laden
     */
    public function getData(): array
    {
        // Versuche gespeicherte Berechtigungen zu laden
        $permissions = [];
        try {
            $rows = $this->db->get_results(
                "SELECT * FROM {$this->prefix}role_permissions"
            ) ?: [];
            foreach ($rows as $row) {
                $permissions[$row->role][$row->capability] = (bool)$row->granted;
            }
        } catch (\Throwable $e) {
            // Tabelle existiert evtl. noch nicht – Default-Berechtigungen verwenden
        }

        // Defaults falls keine DB-Einträge
        if (empty($permissions)) {
            $permissions = $this->getDefaultPermissions();
        }

        // Benutzer-Counts pro Rolle
        $roleCounts = [];
        foreach (self::ROLES as $role) {
            $roleCounts[$role] = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}users WHERE role = ?",
                [$role]
            );
        }

        return [
            'roles'        => self::ROLES,
            'capabilities' => self::CAPABILITIES,
            'permissions'  => $permissions,
            'roleCounts'   => $roleCounts,
        ];
    }

    /**
     * Berechtigungen speichern
     */
    public function savePermissions(array $post): array
    {
        try {
            // Tabelle sicherstellen
            $this->ensureTable();

            // Alle löschen und neu einfügen
            $this->db->query("DELETE FROM {$this->prefix}role_permissions");

            $perms = $post['permissions'] ?? [];
            foreach (self::ROLES as $role) {
                foreach (self::CAPABILITIES as $group => $caps) {
                    foreach ($caps as $cap) {
                        $granted = !empty($perms[$role][$cap]) ? 1 : 0;
                        $this->db->query(
                            "INSERT INTO {$this->prefix}role_permissions (role, capability, granted) VALUES (?, ?, ?)",
                            [$role, $cap, $granted]
                        );
                    }
                }
            }

            return ['success' => true, 'message' => 'Berechtigungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Tabelle anlegen falls nötig
     */
    private function ensureTable(): void
    {
        $this->db->getPdo()->exec("CREATE TABLE IF NOT EXISTS {$this->prefix}role_permissions (
            id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role       VARCHAR(50) NOT NULL,
            capability VARCHAR(100) NOT NULL,
            granted    TINYINT(1) NOT NULL DEFAULT 0,
            UNIQUE KEY uk_role_cap (role, capability)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * Standard-Berechtigungen
     */
    private function getDefaultPermissions(): array
    {
        $allCaps = [];
        foreach (self::CAPABILITIES as $caps) {
            $allCaps = array_merge($allCaps, $caps);
        }

        $perms = [];
        foreach ($allCaps as $cap) {
            $perms['admin'][$cap] = true;
        }

        // Editor: alles außer Users und Settings
        foreach (['pages', 'posts', 'media', 'comments'] as $group) {
            foreach (self::CAPABILITIES[$group] as $cap) {
                $perms['editor'][$cap] = true;
            }
        }

        // Author: eigene Inhalte
        foreach (['pages.view', 'pages.create', 'pages.edit', 'posts.view', 'posts.create', 'posts.edit', 'media.view', 'media.upload', 'comments.view'] as $cap) {
            $perms['author'][$cap] = true;
        }

        // Member: nur Lesen
        foreach (['pages.view', 'posts.view', 'media.view', 'comments.view'] as $cap) {
            $perms['member'][$cap] = true;
        }

        return $perms;
    }
}
