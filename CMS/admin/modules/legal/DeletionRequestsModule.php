<?php
declare(strict_types=1);

/**
 * DeletionRequestsModule – DSGVO Art. 17 Löschanträge
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;

class DeletionRequestsModule
{
    private const REQUEST_TYPE = 'deletion';
    private const MAX_AUDIT_STRING_LENGTH = 240;

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
        // Nutzt die gleiche Tabelle wie PrivacyRequests (type = 'deletion')
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
            $column = $this->db->get_row("SHOW COLUMNS FROM {$this->prefix}privacy_requests LIKE 'execute_after'");

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

        $pending = 0; $processing = 0; $completed = 0; $rejected = 0;
        foreach ($requests as $r) {
            if ($r->status === 'pending')    $pending++;
            if ($r->status === 'processing') $processing++;
            if ($r->status === 'completed')  $completed++;
            if ($r->status === 'rejected')   $rejected++;
        }

        return [
            'requests' => array_map(fn($r) => (array)$r, $requests),
            'stats'    => [
                'total'      => count($requests),
                'pending'    => $pending,
                'processing' => $processing,
                'completed'  => $completed,
                'rejected'   => $rejected,
            ],
        ];
    }

    public function processRequest(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        $request = $this->getRequestById($id);
        if ($request === null) {
            return ['success' => false, 'error' => 'Löschantrag nicht gefunden.'];
        }

        $this->db->update('privacy_requests', [
            'status'       => 'processing',
            'processed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'legal.data_request.deletion.process',
            'Löschantrag in Bearbeitung gesetzt',
            'privacy_request',
            $id,
            $this->buildAuditContext($request, ['status' => 'processing']),
            'info'
        );

        return ['success' => true, 'message' => 'Löschantrag wird geprüft.'];
    }

    public function executeDeletion(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];

        $request = $this->getRequestById($id);
        if (!$request) {
            return ['success' => false, 'error' => 'Löschantrag nicht gefunden.'];
        }

        $executeAfter = trim((string)($request->execute_after ?? ''));
        if ($executeAfter !== '') {
            $executeTimestamp = strtotime($executeAfter);
            if ($executeTimestamp !== false && $executeTimestamp > time()) {
                return [
                    'success' => false,
                    'error' => 'Die Löschfrist ist noch nicht abgelaufen. Früheste Ausführung ab ' . date('d.m.Y H:i', $executeTimestamp) . '.',
                ];
            }
        }

        // Trigger DSGVO delete hook
        if (class_exists('\CMS\Hooks')) {
            \CMS\Hooks::doAction('dsgvo_delete_data', $request->user_id, $request->email);
        }

        // Benutzerkonto vollständig löschen wenn vorhanden
        if ($request->user_id) {
            try {
                $deleteResult = \CMS\Services\UserService::getInstance()->deleteUser((int)$request->user_id, true);
                if ($deleteResult instanceof \CMS\WP_Error) {
                    return ['success' => false, 'error' => $deleteResult->get_error_message()];
                }
            } catch (\Throwable $e) {
                if (class_exists('\CMS\Logger')) {
                    \CMS\Logger::instance()->log('error', 'DSGVO-Löschung fehlgeschlagen für User ' . $request->user_id . ': ' . $e->getMessage());
                }
                return ['success' => false, 'error' => 'Löschung fehlgeschlagen: ' . $e->getMessage()];
            }
        }

        $this->db->update('privacy_requests', [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_USER,
            'legal.data_request.deletion.execute',
            'Löschantrag ausgeführt',
            'privacy_request',
            $id,
            $this->buildAuditContext($request, ['status' => 'completed']),
            'warning'
        );

        return ['success' => true, 'message' => 'Löschung durchgeführt. Das Benutzerkonto wurde entfernt.'];
    }

    public function rejectRequest(int $id, string $reason): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        $request = $this->getRequestById($id);
        if ($request === null) {
            return ['success' => false, 'error' => 'Löschantrag nicht gefunden.'];
        }

        $reason = trim(strip_tags($reason));
        if ($reason === '') {
            return ['success' => false, 'error' => 'Eine Begründung für die Ablehnung ist erforderlich.'];
        }

        $this->db->update('privacy_requests', [
            'status'        => 'rejected',
            'reject_reason' => $reason,
            'completed_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'legal.data_request.deletion.reject',
            'Löschantrag abgelehnt',
            'privacy_request',
            $id,
            $this->buildAuditContext($request, ['status' => 'rejected', 'reason' => $this->sanitizeAuditString($reason, 160)]),
            'warning'
        );

        return ['success' => true, 'message' => 'Löschantrag abgelehnt.'];
    }

    public function deleteRequest(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'error' => 'Ungültige ID.'];
        $request = $this->getRequestById($id);
        if ($request === null) {
            return ['success' => false, 'error' => 'Löschantrag nicht gefunden.'];
        }

        $this->db->delete('privacy_requests', ['id' => $id]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'legal.data_request.deletion.delete',
            'Löschantrag gelöscht',
            'privacy_request',
            $id,
            $this->buildAuditContext($request, ['status' => 'deleted']),
            'warning'
        );

        return ['success' => true, 'message' => 'Antrag gelöscht.'];
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

    /** @param array<string, scalar|null> $extra */
    private function buildAuditContext(object $request, array $extra = []): array
    {
        return array_merge([
            'type' => self::REQUEST_TYPE,
            'user_id' => (int) ($request->user_id ?? 0),
            'email' => $this->sanitizeAuditString((string) ($request->email ?? ''), 160),
            'status' => $this->sanitizeAuditString((string) ($request->status ?? ''), 40),
        ], $extra);
    }

    private function sanitizeAuditString(string $value, int $maxLength = self::MAX_AUDIT_STRING_LENGTH): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }
}
