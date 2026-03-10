<?php
declare(strict_types=1);

namespace CMS\Services\Landing;

use CMS\Contracts\DatabaseInterface;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class LandingRepository
{
    private readonly DatabaseInterface $db;

    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?? Database::instance();
    }

    public function getSection(string $type): ?array
    {
        $row = $this->db->get_row(
            "SELECT * FROM {$this->db->getPrefix()}landing_sections WHERE type = ? LIMIT 1",
            [$type]
        );

        return $row instanceof \stdClass ? get_object_vars($row) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSectionsByType(string $type, string $orderBy = 'sort_order ASC'): array
    {
        $rows = $this->db->get_results(
            "SELECT * FROM {$this->db->getPrefix()}landing_sections WHERE type = ? ORDER BY {$orderBy}",
            [$type]
        );

        return array_map(
            static fn(object $row): array => get_object_vars($row),
            array_filter($rows, static fn(mixed $row): bool => $row instanceof \stdClass)
        );
    }

    public function upsertSection(string $type, array $payload, int $sortOrder = 0): bool
    {
        $json = json_encode($payload);
        if ($json === false) {
            return false;
        }

        $existing = $this->getSection($type);
        if ($existing !== null) {
            try {
                $this->db->execute(
                    "UPDATE {$this->db->getPrefix()}landing_sections SET data = ?, updated_at = NOW() WHERE id = ?",
                    [$json, (int)($existing['id'] ?? 0)]
                );

                return true;
            } catch (\Throwable) {
                return false;
            }
        }

        try {
            $this->db->execute(
                "INSERT INTO {$this->db->getPrefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                [$type, $json, $sortOrder]
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function countSectionsByType(string $type): int
    {
        return (int)($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->getPrefix()}landing_sections WHERE type = ?",
            [$type]
        ) ?? 0);
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
            try {
                $this->db->execute(
                    "UPDATE {$this->db->getPrefix()}landing_sections SET data = ?, sort_order = ?, updated_at = NOW() WHERE id = ?",
                    [$json, $sortOrder, $id]
                );

                return $id;
            } catch (\Throwable) {
                return 0;
            }
        }

        try {
            $this->db->execute(
                "INSERT INTO {$this->db->getPrefix()}landing_sections (type, data, sort_order, created_at, updated_at) VALUES ('feature', ?, ?, NOW(), NOW())",
                [$json, $sortOrder]
            );

            return $this->db->insert_id();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function deleteFeature(int $id): bool
    {
        try {
            $this->db->execute(
                "DELETE FROM {$this->db->getPrefix()}landing_sections WHERE id = ? AND type = 'feature'",
                [$id]
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteAllFeatures(): void
    {
        try {
            $this->db->execute("DELETE FROM {$this->db->getPrefix()}landing_sections WHERE type = 'feature'");
        } catch (\Throwable) {
        }
    }
}
