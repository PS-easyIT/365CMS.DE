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
use CMS\Logger;

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
            "SELECT g.*, sp.name AS plan_name,
                    (
                        SELECT COUNT(*)
                        FROM {$this->prefix}user_group_members ugm
                        WHERE ugm.group_id = g.id
                    ) AS member_count
             FROM {$this->prefix}user_groups g
             LEFT JOIN {$this->prefix}subscription_plans sp ON sp.id = g.plan_id
             ORDER BY g.is_active DESC, g.name ASC"
        ) ?: [];

        $memberRows = $this->db->get_results(
            "SELECT ugm.group_id, u.id, u.username, u.display_name, u.email, u.status
             FROM {$this->prefix}user_group_members ugm
             INNER JOIN {$this->prefix}users u ON u.id = ugm.user_id
             ORDER BY u.username ASC"
        ) ?: [];

        $membersByGroup = [];
        foreach ($memberRows as $row) {
            $groupId = (int)($row->group_id ?? 0);
            if ($groupId <= 0) {
                continue;
            }

            $membersByGroup[$groupId][] = [
                'id' => (int)($row->id ?? 0),
                'username' => (string)($row->username ?? ''),
                'display_name' => (string)($row->display_name ?? ''),
                'email' => (string)($row->email ?? ''),
                'status' => (string)($row->status ?? ''),
            ];
        }

        $normalizedGroups = [];
        foreach ($groups as $group) {
            $groupId = (int)($group->id ?? 0);
            $members = $membersByGroup[$groupId] ?? [];
            $group->slug = (string)($group->slug ?? '');
            $group->is_active = (int)($group->is_active ?? 1);
            $group->members = $members;
            $group->member_ids = array_values(array_map(static fn (array $member): int => (int)$member['id'], $members));
            $normalizedGroups[] = $group;
        }

        return [
            'groups' => $normalizedGroups,
            'userOptions' => $this->getUserOptions(),
            'planOptions' => $this->getPlanOptions(),
        ];
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

    private function getUserOptions(): array
    {
        $rows = $this->db->get_results(
            "SELECT id, username, display_name, email, status
             FROM {$this->prefix}users
             ORDER BY CASE WHEN status = 'active' THEN 0 ELSE 1 END, username ASC"
        ) ?: [];

        return array_map(static function (object $row): array {
            return [
                'id' => (int)($row->id ?? 0),
                'username' => (string)($row->username ?? ''),
                'display_name' => (string)($row->display_name ?? ''),
                'email' => (string)($row->email ?? ''),
                'status' => (string)($row->status ?? 'inactive'),
            ];
        }, $rows);
    }

    private function getPlanOptions(): array
    {
        try {
            $rows = $this->db->get_results(
                "SELECT id, name, slug, is_active
                 FROM {$this->prefix}subscription_plans
                 ORDER BY is_active DESC, sort_order ASC, name ASC"
            ) ?: [];
        } catch (\Throwable) {
            return [];
        }

        return array_map(static function (object $row): array {
            return [
                'id' => (int)($row->id ?? 0),
                'name' => (string)($row->name ?? ''),
                'slug' => (string)($row->slug ?? ''),
                'is_active' => (int)($row->is_active ?? 0),
            ];
        }, $rows);
    }

    private function sanitizeExistingPlanId(mixed $value): ?int
    {
        $planId = (int)$value;
        if ($planId <= 0) {
            return null;
        }

        try {
            $exists = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}subscription_plans WHERE id = ?",
                [$planId]
            );
        } catch (\Throwable) {
            return null;
        }

        return $exists > 0 ? $planId : null;
    }

    private function normalizeSlug(mixed $value, string $fallback): string
    {
        $slug = $this->normalizeScalarText($value, 100);
        if ($slug === '') {
            $slug = $fallback;
        }

        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'gruppe';
    }

    private function ensureUniqueSlug(string $slug, int $groupId = 0): string
    {
        $baseSlug = $slug;
        $suffix = 2;

        while ($this->slugExists($slug, $groupId)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, int $groupId = 0): bool
    {
        if ($groupId > 0) {
            $count = $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}user_groups WHERE slug = ? AND id != ?",
                [$slug, $groupId]
            );
        } else {
            $count = $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}user_groups WHERE slug = ?",
                [$slug]
            );
        }

        return (int)$count > 0;
    }

    private function normalizeMemberIds(mixed $value): array
    {
        $ids = is_array($value) ? $value : [];
        $normalizedIds = [];

        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $normalizedIds[$id] = $id;
            }
        }

        if ($normalizedIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
        $rows = $this->db->get_results(
            "SELECT id FROM {$this->prefix}users WHERE id IN ({$placeholders})",
            array_values($normalizedIds)
        ) ?: [];

        return array_values(array_map(static fn (object $row): int => (int)$row->id, $rows));
    }

    private function syncGroupMembers(int $groupId, array $memberIds): void
    {
        $this->db->execute("DELETE FROM {$this->prefix}user_group_members WHERE group_id = ?", [$groupId]);

        foreach ($memberIds as $memberId) {
            $this->db->execute(
                "INSERT INTO {$this->prefix}user_group_members (user_id, group_id, joined_at) VALUES (?, ?, NOW())",
                [$memberId, $groupId]
            );
        }
    }

    /**
     * Gruppe erstellen oder aktualisieren
     */
    public function save(array $post): array
    {
        $id          = (int)($post['id'] ?? 0);
        $name        = $this->normalizeScalarText($post['name'] ?? '', 120);
        $slug        = $this->normalizeSlug($post['slug'] ?? '', $name);
        $description = $this->normalizeScalarText($post['description'] ?? '', 500);
        $memberIds   = $this->normalizeMemberIds($post['member_ids'] ?? []);
        $isActive    = !empty($post['is_active']) ? 1 : 0;
        $planId      = $this->sanitizeExistingPlanId($post['plan_id'] ?? 0);

        if ($name === '') {
            return ['success' => false, 'error' => 'Gruppenname darf nicht leer sein.'];
        }

        $slug = $this->ensureUniqueSlug($slug, $id);

        if ($id > 0 && !$this->groupExists($id)) {
            return ['success' => false, 'error' => 'Die angeforderte Gruppe existiert nicht mehr.'];
        }

        try {
            $pdo = $this->db->getPdo();
            $startedTransaction = !$pdo->inTransaction();
            if ($startedTransaction) {
                $pdo->beginTransaction();
            }

            if ($id > 0) {
                $this->db->execute(
                    "UPDATE {$this->prefix}user_groups SET name = ?, slug = ?, description = ?, plan_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                    [$name, $slug, $description, $planId, $isActive, $id]
                );
            } else {
                $this->db->execute(
                    "INSERT INTO {$this->prefix}user_groups (name, slug, description, plan_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                    [$name, $slug, $description, $planId, $isActive]
                );
                $id = (int)$pdo->lastInsertId();
            }

            $this->syncGroupMembers($id, $memberIds);

            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }

                $memberLabel = count($memberIds) === 1 ? '1 Mitglied' : count($memberIds) . ' Mitglieder';
                $planLabel = $planId !== null ? ' Paket verknüpft.' : ' Kein Paket verknüpft.';

            return [
                'success' => true,
                'message' => $post['id'] ?? false
                    ? 'Gruppe aktualisiert · ' . $memberLabel . ' zugeordnet.' . $planLabel
                    : 'Gruppe erstellt · ' . $memberLabel . ' zugeordnet.' . $planLabel,
            ];
        } catch (\Throwable $e) {
            $pdo = $this->db->getPdo();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Logger::instance()->withChannel('admin.users.groups')->error('Benutzergruppe konnte nicht gespeichert werden.', [
                'group_id' => $id,
                'exception' => $e,
            ]);

            return ['success' => false, 'error' => 'Die Gruppe konnte nicht gespeichert werden.'];
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

            Logger::instance()->withChannel('admin.users.groups')->error('Benutzergruppe konnte nicht gelöscht werden.', [
                'group_id' => $id,
                'exception' => $e,
            ]);

            return ['success' => false, 'error' => 'Die Gruppe konnte nicht gelöscht werden.'];
        }
    }
}
