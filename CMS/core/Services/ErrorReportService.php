<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\AuditLogger;
use CMS\Database;
use CMS\WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

final class ErrorReportService
{
    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTable();
    }

    public static function buildFailureResultFromWpError(WP_Error $error, array $context = []): array
    {
        return [
            'success' => false,
            'error' => self::buildAlertDataFromWpError($error, $context)['message'],
            'error_details' => self::buildAlertDataFromWpError($error, $context)['error_details'],
            'report_payload' => self::buildAlertDataFromWpError($error, $context)['report_payload'],
        ];
    }

    public static function buildAlertDataFromWpError(WP_Error $error, array $context = []): array
    {
        $message = trim($error->get_error_message());
        $code = trim($error->get_error_code());
        $data = $error->get_error_data();

        return [
            'type' => 'danger',
            'message' => $message !== '' ? $message : 'Ein unbekannter CMS_Error ist aufgetreten.',
            'error_details' => [
                'code' => $code,
                'data' => is_array($data) ? $data : [],
                'context' => $context,
            ],
            'report_payload' => self::buildReportPayloadFromWpError($error, $context),
        ];
    }

    public static function buildReportPayloadFromWpError(WP_Error $error, array $context = []): array
    {
        $message = trim($error->get_error_message());
        $code = trim($error->get_error_code());
        $source = trim((string)($context['source'] ?? ($_SERVER['REQUEST_URI'] ?? '')));
        $title = trim((string)($context['title'] ?? 'CMS_Error'));

        if ($code !== '') {
            $title .= ' · ' . $code;
        }

        return [
            'title' => $title,
            'message' => $message,
            'error_code' => $code,
            'error_data' => is_array($error->get_error_data()) ? $error->get_error_data() : [],
            'context' => $context,
            'source_url' => $source,
        ];
    }

    public function createReport(array $payload): array
    {
        $title = $this->limit((string)($payload['title'] ?? 'Fehlerreport'), 255);
        $message = $this->limit((string)($payload['message'] ?? ''), 2000);
        $errorCode = $this->limit((string)($payload['error_code'] ?? ''), 120);
        $sourceUrl = $this->limit((string)($payload['source_url'] ?? ''), 500);
        $errorData = is_array($payload['error_data'] ?? null) ? $payload['error_data'] : [];
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];
        $status = $this->limit((string)($payload['status'] ?? 'open'), 30);
        $createdBy = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        if ($message === '') {
            return ['success' => false, 'error' => 'Für einen Fehlerreport wird mindestens eine Fehlermeldung benötigt.'];
        }

        try {
            $this->db->insert('error_reports', [
                'title' => $title !== '' ? $title : 'Fehlerreport',
                'message' => $message,
                'error_code' => $errorCode,
                'source_url' => $sourceUrl,
                'status' => $status,
                'error_data_json' => $errorData !== [] ? json_encode($errorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'context_json' => $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'created_by' => $createdBy,
            ]);

            $reportId = (int)$this->db->lastInsertId();

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.error_report.create',
                'Fehlerreport #' . $reportId . ' wurde erstellt',
                'error_report',
                $reportId,
                [
                    'error_code' => $errorCode,
                    'source_url' => $sourceUrl,
                    'title' => $title,
                ],
                'warning'
            );

            return [
                'success' => true,
                'id' => $reportId,
                'message' => 'Fehlerreport #' . $reportId . ' wurde im Adminbereich angelegt.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehlerreport konnte nicht gespeichert werden: ' . $e->getMessage()];
        }
    }

    public function getRecentReports(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        try {
            $rows = $this->db->get_results(
                "SELECT * FROM {$this->prefix}error_reports ORDER BY created_at DESC LIMIT {$limit}"
            ) ?: [];

            return array_map(static function ($row): array {
                $errorData = [];
                $context = [];

                if (!empty($row->error_data_json)) {
                    $decoded = json_decode((string)$row->error_data_json, true);
                    $errorData = is_array($decoded) ? $decoded : [];
                }

                if (!empty($row->context_json)) {
                    $decoded = json_decode((string)$row->context_json, true);
                    $context = is_array($decoded) ? $decoded : [];
                }

                return [
                    'id' => (int)($row->id ?? 0),
                    'title' => (string)($row->title ?? ''),
                    'message' => (string)($row->message ?? ''),
                    'error_code' => (string)($row->error_code ?? ''),
                    'source_url' => (string)($row->source_url ?? ''),
                    'status' => (string)($row->status ?? 'open'),
                    'created_by' => isset($row->created_by) ? (int)$row->created_by : null,
                    'created_at' => (string)($row->created_at ?? ''),
                    'error_data' => $errorData,
                    'context' => $context,
                ];
            }, $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    private function ensureTable(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}error_reports (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                error_code VARCHAR(120) DEFAULT NULL,
                source_url VARCHAR(500) DEFAULT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'open',
                error_data_json LONGTEXT DEFAULT NULL,
                context_json LONGTEXT DEFAULT NULL,
                created_by INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function limit(string $value, int $length): string
    {
        return mb_substr(trim($value), 0, $length);
    }
}