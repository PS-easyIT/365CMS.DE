<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Database;
use PDO;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingRepository
{
    private readonly Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    public function getSection(string $type): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->execute([$type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSectionsByType(string $type, string $orderBy = 'sort_order ASC'): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->db->prefix()}landing_sections WHERE type = ? ORDER BY {$orderBy}");
        if (!$stmt) {
            return [];
        }

        $stmt->execute([$type]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }

    public function upsertSection(string $type, array $payload, int $sortOrder = 0): bool
    {
        $json = json_encode($payload);
        if ($json === false) {
            return false;
        }

        $existing = $this->getSection($type);
        if ($existing !== null) {
            $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?");
            return $stmt ? $stmt->execute([$json, (int)($existing['id'] ?? 0)]) : false;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())"
        );

        return $stmt ? $stmt->execute([$type, $json, $sortOrder]) : false;
    }

    public function countSectionsByType(string $type): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->db->prefix()}landing_sections WHERE type = ?");
        if (!$stmt) {
            return 0;
        }

        $stmt->execute([$type]);

        return (int)$stmt->fetchColumn();
    }

    public function hasSectionRecord(string $type): bool
    {
        return $this->countSectionsByType($type) > 0;
    }

    public function ensureSingleSectionRecord(string $type, array $payload, int $sortOrder): void
    {
        if ($this->hasSectionRecord($type)) {
            return;
        }

        $data = $payload;
        unset($data['id']);
        $this->upsertSection($type, $data, $sortOrder);
    }

    public function saveFeature(?int $id, array $payload, int $sortOrder): int
    {
        $json = json_encode($payload);
        if ($json === false) {
            return 0;
        }

        if ($id !== null && $id > 0) {
            $stmt = $this->db->prepare("UPDATE {$this->db->prefix()}landing_sections SET data = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt) {
                return 0;
            }

            $stmt->execute([$json, $sortOrder, $id]);
            return $id;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->db->prefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())"
        );
        if (!$stmt) {
            return 0;
        }

        $stmt->execute([$json, $sortOrder]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteFeature(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->db->prefix()}landing_sections WHERE id = ? AND type = 'feature'");
        return $stmt ? $stmt->execute([$id]) : false;
    }

    public function deleteAllFeatures(): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->db->prefix()}landing_sections WHERE type = 'feature'");
        if ($stmt) {
            $stmt->execute();
        }
    }
}
