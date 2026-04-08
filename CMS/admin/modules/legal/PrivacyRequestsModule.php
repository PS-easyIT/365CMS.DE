<?php
declare(strict_types=1);

/**
 * PrivacyRequestsModule – DSGVO Art. 15 Auskunftsanfragen
 */

if (!defined('ABSPATH')) {
    exit;
}

class PrivacyRequestsModule
{
    private const REQUEST_TYPE = 'export';

    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}privacy_requests (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type          VARCHAR(20) NOT NULL DEFAULT 'export',
                user_id       INT UNSIGNED DEFAULT NULL,
                email         VARCHAR(255) NOT NULL,
                name          VARCHAR(255) DEFAULT NULL,
                status        VARCHAR(20) NOT NULL DEFAULT 'pending',
                reject_reason TEXT DEFAULT NULL,
                processed_at  DATETIME DEFAULT NULL,
                completed_at  DATETIME DEFAULT NULL,
                execute_after DATETIME DEFAULT NULL,
                created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_type   (type),
                INDEX idx_execute_after (execute_after)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        try {
            $column = $this->db->get_row(
                "SHOW COLUMNS FROM {$this->prefix}privacy_requests LIKE ?",
                ['execute_after']
            );

            if (!$column) {
                $this->db->getPdo()->exec(
                    "ALTER TABLE {$this->prefix}privacy_requests ADD COLUMN execute_after DATETIME DEFAULT NULL AFTER completed_at"
                );
                $this->db->getPdo()->exec(
                    "ALTER TABLE {$this->prefix}privacy_requests ADD INDEX idx_execute_after (execute_after)"
                );
            }
        } catch (\Throwable) {
        }
    }

    public function getData(): array
    {
        $requests = $this->db->get_results(
            "SELECT r.*, u.username FROM {$this->prefix}privacy_requests r
             LEFT JOIN {$this->prefix}users u ON r.user_id = u.id
             WHERE r.type = '" . self::REQUEST_TYPE . "'
             ORDER BY r.created_at DESC"
        ) ?: [];

        $pending   = 0;
        $processed = 0;
        $completed = 0;
        $rejected  = 0;
        foreach ($requests as $r) {
            if ($r->status === 'pending')    $pending++;
            if ($r->status === 'processing') $processed++;
            if ($r->status === 'completed')  $completed++;
            if ($r->status === 'rejected')   $rejected++;
        }

        return [
            'requests' => array_map(fn($r) => (array)$r, $requests),
            'stats'    => [
                'total'      => count($requests),
                'pending'    => $pending,
                'processing' => $processed,
                'completed'  => $completed,
                'rejected'   => $rejected,
            ],
        ];
    }

    public function processRequest(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        if ($this->getRequestById($id) === null) {
            return ['success' => false, 'error' => 'Auskunftsanfrage nicht gefunden.'];
        }

        $this->db->update('privacy_requests', [
            'status'       => 'processing',
            'processed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
        return ['success' => true, 'message' => 'Anfrage wird bearbeitet.'];
    }

    public function completeRequest(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];

        // Trigger DSGVO export hook
        $request = $this->getRequestById($id);
        if ($request === null) {
            return ['success' => false, 'error' => 'Auskunftsanfrage nicht gefunden.'];
        }

        if ($request && class_exists('\CMS\Hooks')) {
            \CMS\Hooks::doAction('dsgvo_export_data', $request->user_id, $request->email);
        }

        $this->db->update('privacy_requests', [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
        return ['success' => true, 'message' => 'Auskunft erteilt und als abgeschlossen markiert.'];
    }

    public function rejectRequest(int $id, string $reason): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        if ($this->getRequestById($id) === null) {
            return ['success' => false, 'error' => 'Auskunftsanfrage nicht gefunden.'];
        }

        $this->db->update('privacy_requests', [
            'status'        => 'rejected',
            'reject_reason' => strip_tags($reason),
            'completed_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
        return ['success' => true, 'message' => 'Anfrage abgelehnt.'];
    }

    public function deleteRequest(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        if ($this->getRequestById($id) === null) {
            return ['success' => false, 'error' => 'Auskunftsanfrage nicht gefunden.'];
        }

        $this->db->delete('privacy_requests', ['id' => $id]);
        return ['success' => true, 'message' => 'Anfrage gelöscht.'];
    }

    private function getRequestById(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $request = $this->db->get_row(
            "SELECT * FROM {$this->prefix}privacy_requests WHERE id = ? AND type = ? LIMIT 1",
            [$id, self::REQUEST_TYPE]
        );

        return is_object($request) ? $request : null;
    }
}
