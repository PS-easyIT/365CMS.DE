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
        $roles        = $this->getKnownRoles();
        $capabilities = $this->getKnownCapabilities();
        $permissions  = $this->getPermissionsMatrix($roles, $capabilities);
        $roleCounts   = [];

        foreach ($roles as $role) {
            $roleCounts[$role] = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}users WHERE role = ?",
                [$role]
            );
        }

        return [
            'roles'        => $roles,
            'roleLabels'   => $this->getRoleLabels($roles),
            'capabilities' => $capabilities,
            'permissions'  => $permissions,
            'roleCounts'   => $roleCounts,
            'customRoles'  => $this->getCustomRoles($roles),
            'customCapabilities' => $this->getCustomCapabilities($capabilities),
        ];
    }

    /**
     * Berechtigungen speichern
     */
    public function savePermissions(array $post): array
    {
        try {
            $this->ensureTable();

            $roles        = $this->getKnownRoles();
            $capabilities = $this->getKnownCapabilities();
            $this->db->query("DELETE FROM {$this->prefix}role_permissions");

            $perms = $post['permissions'] ?? [];
            foreach ($roles as $role) {
                foreach ($capabilities as $caps) {
                    foreach ($caps as $cap) {
                        $granted = ($role === 'admin' || !empty($perms[$role][$cap])) ? 1 : 0;
                        $this->db->execute(
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
     * Neue Rolle anlegen
     */
    public function addRole(array $post): array
    {
        $slug = $this->sanitizeRoleSlug((string)($post['role_slug'] ?? ''));
        if ($slug === '') {
            return ['success' => false, 'error' => 'Bitte einen gültigen Rollen-Slug angeben.'];
        }

        $roles = $this->getKnownRoles();
        if (in_array($slug, $roles, true)) {
            return ['success' => false, 'error' => 'Diese Rolle existiert bereits.'];
        }

        try {
            $this->ensureTable();

            $capabilities = $this->getKnownCapabilities();
            $templateRole = $this->sanitizeRoleSlug((string)($post['copy_role'] ?? 'member'));
            $defaults     = $this->getPermissionsMatrix(array_merge($roles, [$slug]), $capabilities);

            foreach ($capabilities as $caps) {
                foreach ($caps as $cap) {
                    $granted = !empty($defaults[$templateRole][$cap]) ? 1 : 0;
                    $this->db->execute(
                        "INSERT INTO {$this->prefix}role_permissions (role, capability, granted) VALUES (?, ?, ?)",
                        [$slug, $cap, $granted]
                    );
                }
            }

            return ['success' => true, 'message' => 'Neue Rolle wurde angelegt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Anlegen der Rolle: ' . $e->getMessage()];
        }
    }

    public function updateRole(array $post): array
    {
        $currentRole = $this->sanitizeRoleSlug((string)($post['current_role'] ?? ''));
        $newRole     = $this->sanitizeRoleSlug((string)($post['role_slug'] ?? ''));

        if ($currentRole === '' || $newRole === '') {
            return ['success' => false, 'error' => 'Bitte gültige Rollen-Slugs angeben.'];
        }

        if (in_array($currentRole, self::ROLES, true)) {
            return ['success' => false, 'error' => 'Systemrollen können nicht umbenannt werden.'];
        }

        if ($currentRole !== $newRole && in_array($newRole, $this->getKnownRoles(), true)) {
            return ['success' => false, 'error' => 'Die Zielrolle existiert bereits.'];
        }

        try {
            $this->ensureTable();

            $this->db->execute(
                "UPDATE {$this->prefix}role_permissions SET role = ? WHERE role = ?",
                [$newRole, $currentRole]
            );

            $this->db->execute(
                "UPDATE {$this->prefix}users SET role = ? WHERE role = ?",
                [$newRole, $currentRole]
            );

            return ['success' => true, 'message' => 'Rolle wurde aktualisiert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Bearbeiten der Rolle: ' . $e->getMessage()];
        }
    }

    public function deleteRole(array $post): array
    {
        $role         = $this->sanitizeRoleSlug((string)($post['role_slug'] ?? ''));
        $fallbackRole = $this->sanitizeRoleSlug((string)($post['fallback_role'] ?? 'member'));

        if ($role === '') {
            return ['success' => false, 'error' => 'Ungültige Rolle.'];
        }

        if (in_array($role, self::ROLES, true)) {
            return ['success' => false, 'error' => 'Systemrollen können nicht gelöscht werden.'];
        }

        if ($fallbackRole === '' || $fallbackRole === $role) {
            $fallbackRole = 'member';
        }

        try {
            $this->ensureTable();

            $this->db->execute(
                "UPDATE {$this->prefix}users SET role = ? WHERE role = ?",
                [$fallbackRole, $role]
            );

            $this->db->execute(
                "DELETE FROM {$this->prefix}role_permissions WHERE role = ?",
                [$role]
            );

            return ['success' => true, 'message' => 'Rolle wurde gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen der Rolle: ' . $e->getMessage()];
        }
    }

    /**
     * Neue Berechtigung anlegen
     */
    public function addCapability(array $post): array
    {
        $rawCapability = (string)($post['capability_slug'] ?? '');
        $capability    = $this->sanitizeCapability($rawCapability);
        if ($capability === '') {
            return [
                'success' => false,
                'error'   => 'Bitte eine gültige Berechtigung im Format modul.aktion angeben, z. B. shop.orders.view.',
            ];
        }

        $capabilities = $this->getKnownCapabilities();
        foreach ($capabilities as $caps) {
            if (in_array($capability, $caps, true)) {
                return ['success' => false, 'error' => 'Diese Berechtigung existiert bereits.'];
            }
        }

        try {
            $this->ensureTable();

            foreach ($this->getKnownRoles() as $role) {
                $granted = $role === 'admin' ? 1 : 0;
                $this->db->execute(
                    "INSERT INTO {$this->prefix}role_permissions (role, capability, granted) VALUES (?, ?, ?)",
                    [$role, $capability, $granted]
                );
            }

            return ['success' => true, 'message' => 'Neue Berechtigung wurde angelegt: ' . $capability];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Anlegen der Berechtigung: ' . $e->getMessage()];
        }
    }

    public function updateCapability(array $post): array
    {
        $currentCapability = $this->sanitizeCapability((string)($post['current_capability'] ?? ''));
        $newCapability     = $this->sanitizeCapability((string)($post['capability_slug'] ?? ''));

        if ($currentCapability === '' || $newCapability === '') {
            return ['success' => false, 'error' => 'Bitte gültige Berechtigungen angeben.'];
        }

        if ($this->isCoreCapability($currentCapability)) {
            return ['success' => false, 'error' => 'Systemrechte können nicht umbenannt werden.'];
        }

        if ($currentCapability !== $newCapability && $this->capabilityExists($newCapability)) {
            return ['success' => false, 'error' => 'Diese Ziel-Berechtigung existiert bereits.'];
        }

        try {
            $this->ensureTable();
            $this->db->execute(
                "UPDATE {$this->prefix}role_permissions SET capability = ? WHERE capability = ?",
                [$newCapability, $currentCapability]
            );

            return ['success' => true, 'message' => 'Berechtigung wurde aktualisiert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Bearbeiten der Berechtigung: ' . $e->getMessage()];
        }
    }

    public function deleteCapability(array $post): array
    {
        $capability = $this->sanitizeCapability((string)($post['capability_slug'] ?? ''));

        if ($capability === '') {
            return ['success' => false, 'error' => 'Ungültige Berechtigung.'];
        }

        if ($this->isCoreCapability($capability)) {
            return ['success' => false, 'error' => 'Systemrechte können nicht gelöscht werden.'];
        }

        try {
            $this->ensureTable();
            $this->db->execute(
                "DELETE FROM {$this->prefix}role_permissions WHERE capability = ?",
                [$capability]
            );

            return ['success' => true, 'message' => 'Berechtigung wurde gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Löschen der Berechtigung: ' . $e->getMessage()];
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
        $perms = [];
        foreach ($this->flattenCapabilities(self::CAPABILITIES) as $cap) {
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

    private function getKnownRoles(): array
    {
        $roles = self::ROLES;

        try {
            $dbRoles = $this->db->get_results("SELECT DISTINCT role FROM {$this->prefix}role_permissions ORDER BY role ASC") ?: [];
            foreach ($dbRoles as $row) {
                if (!empty($row->role)) {
                    $roles[] = (string)$row->role;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $userRoles = $this->db->get_results("SELECT DISTINCT role FROM {$this->prefix}users ORDER BY role ASC") ?: [];
            foreach ($userRoles as $row) {
                if (!empty($row->role)) {
                    $roles[] = (string)$row->role;
                }
            }
        } catch (\Throwable $e) {
        }

        $roles = array_values(array_unique(array_filter($roles, static fn ($role): bool => is_string($role) && $role !== '')));

        usort($roles, static function (string $a, string $b): int {
            $priority = array_flip(self::ROLES);
            $aIndex   = $priority[$a] ?? 999;
            $bIndex   = $priority[$b] ?? 999;

            return $aIndex === $bIndex ? strcasecmp($a, $b) : ($aIndex <=> $bIndex);
        });

        return $roles;
    }

    private function getKnownCapabilities(): array
    {
        $capabilities = self::CAPABILITIES;

        try {
            $rows = $this->db->get_results("SELECT DISTINCT capability FROM {$this->prefix}role_permissions ORDER BY capability ASC") ?: [];
            foreach ($rows as $row) {
                $capability = (string)($row->capability ?? '');
                if ($capability === '') {
                    continue;
                }

                [$group] = array_pad(explode('.', $capability, 2), 2, 'general');
                $group = $group !== '' ? $group : 'general';

                if (!isset($capabilities[$group])) {
                    $capabilities[$group] = [];
                }

                if (!in_array($capability, $capabilities[$group], true)) {
                    $capabilities[$group][] = $capability;
                }
            }
        } catch (\Throwable $e) {
        }

        foreach ($capabilities as &$caps) {
            sort($caps, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($caps);

        ksort($capabilities, SORT_NATURAL | SORT_FLAG_CASE);

        return $capabilities;
    }

    private function getPermissionsMatrix(array $roles, array $capabilities): array
    {
        $permissions = [];
        $defaults    = $this->getDefaultPermissions();

        foreach ($roles as $role) {
            foreach ($capabilities as $caps) {
                foreach ($caps as $cap) {
                    $permissions[$role][$cap] = !empty($defaults[$role][$cap]);
                }
            }
        }

        try {
            $rows = $this->db->get_results("SELECT role, capability, granted FROM {$this->prefix}role_permissions") ?: [];
            foreach ($rows as $row) {
                $role = (string)($row->role ?? '');
                $cap  = (string)($row->capability ?? '');
                if ($role === '' || $cap === '') {
                    continue;
                }

                $permissions[$role][$cap] = (bool)$row->granted;
            }
        } catch (\Throwable $e) {
        }

        foreach ($capabilities as $caps) {
            foreach ($caps as $cap) {
                $permissions['admin'][$cap] = true;
            }
        }

        return $permissions;
    }

    private function getRoleLabels(array $roles): array
    {
        $labels = [
            'admin'  => 'Administrator',
            'editor' => 'Editor',
            'author' => 'Autor',
            'member' => 'Mitglied',
        ];

        foreach ($roles as $role) {
            if (!isset($labels[$role])) {
                $labels[$role] = $this->humanizeSlug($role);
            }
        }

        return $labels;
    }

    private function getCustomRoles(array $roles): array
    {
        return array_values(array_filter($roles, fn (string $role): bool => !in_array($role, self::ROLES, true)));
    }

    private function getCustomCapabilities(array $capabilities): array
    {
        $custom = [];

        foreach ($capabilities as $group => $caps) {
            foreach ($caps as $capability) {
                if ($this->isCoreCapability($capability)) {
                    continue;
                }

                $custom[] = [
                    'group' => $group,
                    'capability' => $capability,
                ];
            }
        }

        return $custom;
    }

    private function capabilityExists(string $capability): bool
    {
        foreach ($this->getKnownCapabilities() as $caps) {
            if (in_array($capability, $caps, true)) {
                return true;
            }
        }

        return false;
    }

    private function isCoreCapability(string $capability): bool
    {
        foreach (self::CAPABILITIES as $caps) {
            if (in_array($capability, $caps, true)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeRoleSlug(string $role): string
    {
        $role = strtolower(trim($role));
        $role = preg_replace('/[^a-z0-9_-]+/', '-', $role) ?? '';
        $role = trim($role, '-_');

        return $role;
    }

    private function sanitizeCapability(string $capability): string
    {
        $capability = strtolower(trim($capability));

        if ($capability === '') {
            return '';
        }

        $capability = str_replace(['\\', '/', ':'], '.', $capability);
        $capability = preg_replace('/\s+/', '.', $capability) ?? '';
        $capability = preg_replace('/[^a-z0-9._-]+/', '-', $capability) ?? '';
        $capability = preg_replace('/\.{2,}/', '.', $capability) ?? '';
        $capability = preg_replace('/-{2,}/', '-', $capability) ?? '';
        $capability = trim($capability, '.-_');

        if ($capability === '' || !preg_match('/^[a-z0-9_-]+(?:\.[a-z0-9_-]+)+$/', $capability)) {
            return '';
        }

        return $capability;
    }

    private function humanizeSlug(string $value): string
    {
        $value = str_replace(['_', '-', '.'], ' ', strtolower($value));
        return ucwords(trim($value));
    }

    private function flattenCapabilities(array $capabilities): array
    {
        $flat = [];
        foreach ($capabilities as $caps) {
            $flat = array_merge($flat, $caps);
        }

        return array_values(array_unique($flat));
    }
}
