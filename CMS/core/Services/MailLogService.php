<?php
/**
 * Persistente Mail-Protokollierung für Admin-Übersicht und Diagnose.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class MailLogService
{
    private static ?self $instance = null;

    private Database $db;
    private Logger $logger;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->logger = Logger::instance()->withChannel('mail.log');
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function log(
        string $recipient,
        string $subject,
        string $status,
        string $transport,
        string $provider,
        ?string $messageId = null,
        ?string $errorMessage = null,
        array $meta = [],
        string $source = 'system'
    ): void {
        try {
            $this->db->execute(
                "INSERT INTO {$this->prefix}mail_log
                    (recipient, subject, status, transport, provider, message_id, error_message, meta, source, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    trim($recipient),
                    trim($subject),
                    trim($status),
                    trim($transport),
                    trim($provider),
                    $messageId !== null ? trim($messageId) : null,
                    $errorMessage !== null ? trim($errorMessage) : null,
                    !empty($meta) ? json_encode($meta, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                    trim($source),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->warning('Mail-Log konnte nicht persistiert werden', [
                'recipient' => $recipient,
                'exception' => $e,
            ]);
        }
    }

    /**
     * @return array{rows: array<int, object>, total: int, page: int, limit: int}
     */
    public function getRecent(int $limit = 50, int $page = 1, string $search = '', string $status = ''): array
    {
        $page = max(1, $page);
        $limit = min(200, max(10, $limit));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = '(recipient LIKE ? OR subject LIKE ? OR provider LIKE ? OR source LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($status !== '' && in_array($status, ['sent', 'failed'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}mail_log {$whereSql}",
            $params
        );

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->prefix}mail_log {$whereSql} ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );

        $bindIndex = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($bindIndex, $param, \PDO::PARAM_INT);
            } elseif ($param === null) {
                $stmt->bindValue($bindIndex, null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($bindIndex, (string) $param, \PDO::PARAM_STR);
            }
            $bindIndex++;
        }

        $stmt->bindValue($bindIndex, $limit, \PDO::PARAM_INT);
        $stmt->bindValue($bindIndex + 1, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];

        return [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'queued_pending' => 0,
            'queued_failed' => 0,
        ];

        try {
            $rows = $this->db->get_results(
                "SELECT status, COUNT(*) AS cnt FROM {$this->prefix}mail_log GROUP BY status"
            ) ?: [];
            foreach ($rows as $row) {
                $status = (string) ($row->status ?? '');
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = (int) ($row->cnt ?? 0);
                }
            }
        } catch (\Throwable) {
            // Optional im frühen Setup.
        }

        try {
            $queueRows = $this->db->get_results(
                "SELECT status, COUNT(*) AS cnt FROM {$this->prefix}mail_queue GROUP BY status"
            ) ?: [];
            foreach ($queueRows as $row) {
                $status = (string) ($row->status ?? '');
                if ($status === 'pending') {
                    $stats['queued_pending'] = (int) ($row->cnt ?? 0);
                }
                if ($status === 'failed') {
                    $stats['queued_failed'] = (int) ($row->cnt ?? 0);
                }
            }
        } catch (\Throwable) {
            // Queue-Tabelle kann in Altinstallationen noch fehlen, bis Migration lief.
        }

        return $stats;
    }

    public function clear(): bool
    {
        try {
            $this->db->execute("DELETE FROM {$this->prefix}mail_log");
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('Mail-Logs konnten nicht geleert werden', [
                'exception' => $e,
            ]);
            return false;
        }
    }
}
