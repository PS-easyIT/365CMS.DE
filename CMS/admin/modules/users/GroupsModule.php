<?php
declare(strict_types=1);

/**
 * Groups Module – Business-Logik für Benutzergruppen
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class GroupsModule
{
    private Database $db;
    private string $prefix;

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Alle Gruppen mit Mitgliederzahl laden
     */
    public function getData(): array
    {
        $groups = $this->db->get_results(
            "SELECT g.*, COUNT(ugm.user_id) AS member_count
             FROM {$this->prefix}user_groups g
             LEFT JOIN {$this->prefix}user_group_members ugm ON g.id = ugm.group_id
             GROUP BY g.id
             ORDER BY g.name ASC"
        ) ?: [];

        return ['groups' => $groups];
    }

    /**
     * Gruppe erstellen oder aktualisieren
     */
    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $name        = trim($post['name'] ?? '');
        $description = trim($post['description'] ?? '');

        if ($name === '') {
            return ['success' => false, 'error' => 'Gruppenname darf nicht leer sein.'];
        }

        try {
            if ($id > 0) {
                $this->db->query(
                    "UPDATE {$this->prefix}user_groups SET name = ?, description = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $description, $id]
                );
                return ['success' => true, 'message' => 'Gruppe aktualisiert.'];
            } else {
                $this->db->query(
                    "INSERT INTO {$this->prefix}user_groups (name, description, created_at) VALUES (?, ?, NOW())",
                    [$name, $description]
                );
                return ['success' => true, 'message' => 'Gruppe erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Gruppe löschen
     */
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige Gruppen-ID.'];
        }

        try {
            $this->db->query("DELETE FROM {$this->prefix}user_group_members WHERE group_id = ?", [$id]);
            $this->db->query("DELETE FROM {$this->prefix}user_groups WHERE id = ?", [$id]);
            return ['success' => true, 'message' => 'Gruppe gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }
}
