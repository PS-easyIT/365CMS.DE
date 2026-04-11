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

    private function normalizeScalarText(mixed $value, int $maxLength): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', '', $normalized) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($normalized, 0, $maxLength)
            : substr($normalized, 0, $maxLength);
    }

    private function groupExists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $count = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}user_groups WHERE id = ?",
            [$id]
        );

        return $count !== null && (int) $count > 0;
    }

    /**
     * Gruppe erstellen oder aktualisieren
     */
    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $name        = $this->normalizeScalarText($post['name'] ?? '', 120);
        $description = $this->normalizeScalarText($post['description'] ?? '', 500);

        if ($name === '') {
            return ['success' => false, 'error' => 'Gruppenname darf nicht leer sein.'];
        }

        if ($id > 0 && !$this->groupExists($id)) {
            return ['success' => false, 'error' => 'Die angeforderte Gruppe existiert nicht mehr.'];
        }

        try {
            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}user_groups SET name = ?, description = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $description, $id]
                );
                return ['success' => true, 'message' => 'Gruppe aktualisiert.'];
            } else {
                $this->db->execute(
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

        if (!$this->groupExists($id)) {
            return ['success' => false, 'error' => 'Die angeforderte Gruppe existiert nicht mehr.'];
        }

        try {
            $pdo = $this->db->getPdo();
            $startedTransaction = !$pdo->inTransaction();
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            $this->db->execute("DELETE FROM {$this->prefix}user_group_members WHERE group_id = ?", [$id]);
            $deleteGroupStatement = $this->db->execute("DELETE FROM {$this->prefix}user_groups WHERE id = ? LIMIT 1", [$id]);

            if ($deleteGroupStatement->rowCount() < 1) {
                throw new \RuntimeException('Die Gruppen-Löschung konnte nicht bestätigt werden.');
            }

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

            return ['success' => true, 'message' => 'Gruppe gelöscht.'];
        } catch (\Throwable $e) {
            $pdo = $this->db->getPdo();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }
}
