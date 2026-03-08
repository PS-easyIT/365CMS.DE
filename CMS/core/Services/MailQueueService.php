<?php
/**
 * Mail-Queue-Service für asynchronen Versand, Cron-Verarbeitung und Retry-Backoff.
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

class MailQueueService
{
    public const CRON_HOOK = 'cms_cron_mail_queue';

    private const GROUP = 'mail';
    private const SETTINGS_LAST_RUN = 'queue_last_run';

    private static ?self $instance = null;

    private Database $db;
    private SettingsService $settings;
    private Logger $logger;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->settings = SettingsService::getInstance();
        $this->logger = Logger::instance()->withChannel('mail.queue');
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * @return array<string, bool|int|string|array<string, mixed>|null>
     */
    public function getConfiguration(): array
    {
        $token = $this->settings->getString(self::GROUP, 'queue_cron_token');
        if ($token === '') {
            $token = bin2hex(random_bytes(24));
            $this->settings->set(self::GROUP, 'queue_cron_token', $token, true, 0);
        }

        $batchSize = min(100, max(1, $this->settings->getInt(self::GROUP, 'queue_batch_size', 10)));
        $maxAttempts = min(20, max(1, $this->settings->getInt(self::GROUP, 'queue_max_attempts', 5)));
        $retryDelay = min(86400, max(60, $this->settings->getInt(self::GROUP, 'queue_retry_delay_seconds', 300)));
        $throttleDelay = min(86400, max(60, $this->settings->getInt(self::GROUP, 'queue_throttle_delay_seconds', 900)));
        $lockTimeout = min(86400, max(60, $this->settings->getInt(self::GROUP, 'queue_lock_timeout_seconds', 900)));
        $lastRun = $this->settings->get(self::GROUP, self::SETTINGS_LAST_RUN, []);
        $cronUrl = (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '') . '/cron.php?task=mail-queue&token=' . rawurlencode($token);

        return [
            'enabled' => $this->settings->getBool(self::GROUP, 'queue_enabled', true),
            'batch_size' => $batchSize,
            'max_attempts' => $maxAttempts,
            'retry_delay_seconds' => $retryDelay,
            'throttle_delay_seconds' => $throttleDelay,
            'lock_timeout_seconds' => $lockTimeout,
            'cron_token' => $token,
            'cron_url' => $cronUrl,
            'cli_command' => 'php ' . escapeshellarg(ABSPATH . 'cron.php') . ' --task=mail-queue --limit=' . $batchSize,
            'last_run' => is_array($lastRun) ? $lastRun : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(int $limit = 25): array
    {
        $config = $this->getConfiguration();

        return [
            'config' => $config,
            'stats' => $this->getStats(),
            'recent_jobs' => $this->getRecent($limit),
            'last_run' => $config['last_run'],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array{success:bool,message?:string,error?:string,id?:int}
     */
    public function enqueue(
        string $recipient,
        string $subject,
        string $htmlBody,
        array $headers = [],
        ?\DateTimeInterface $availableAt = null,
        string $source = 'system',
        ?int $maxAttempts = null
    ): array {
        $recipient = trim($recipient);
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'Ungültige Empfänger-E-Mail-Adresse für die Queue.'];
        }

        $subject = trim($subject);
        if ($subject === '') {
            return ['success' => false, 'error' => 'Für die Queue ist ein Betreff erforderlich.'];
        }

        $config = $this->getConfiguration();
        $insertId = $this->db->insert('mail_queue', [
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $htmlBody,
            'headers' => $this->encodeHeaders($headers),
            'content_type' => 'html',
            'source' => trim($source) !== '' ? trim($source) : 'system',
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts !== null ? min(20, max(1, $maxAttempts)) : (int) $config['max_attempts'],
            'available_at' => ($availableAt ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'sent_at' => null,
            'locked_at' => null,
            'last_attempt_at' => null,
            'last_error' => null,
        ]);

        if ($insertId === false) {
            $this->logger->error('Mail-Queue-Eintrag konnte nicht gespeichert werden', [
                'recipient' => $recipient,
                'subject' => $subject,
                'db_error' => $this->db->last_error,
            ]);

            return ['success' => false, 'error' => 'Mail-Queue-Eintrag konnte nicht gespeichert werden.'];
        }

        return [
            'success' => true,
            'message' => 'E-Mail wurde in die Queue aufgenommen.',
            'id' => (int) $insertId,
        ];
    }

    /**
     * @return array{success:bool,message?:string,error?:string,worker:string,claimed:int,sent:int,retried:int,failed_final:int,released_stale:int,processed:int}
     */
    public function processDueJobs(?int $limit = null, string $worker = 'cron', bool $force = false): array
    {
        $config = $this->getConfiguration();
        if (empty($config['enabled']) && !$force) {
            return [
                'success' => false,
                'error' => 'Mail-Queue ist deaktiviert.',
                'worker' => $worker,
                'claimed' => 0,
                'sent' => 0,
                'retried' => 0,
                'failed_final' => 0,
                'released_stale' => 0,
                'processed' => 0,
            ];
        }

        $limit = $limit !== null ? min(100, max(1, $limit)) : (int) $config['batch_size'];
        $releasedStale = $this->releaseStaleProcessingJobs((int) $config['lock_timeout_seconds']);
        $jobs = $this->getDueJobs($limit);

        $summary = [
            'success' => true,
            'message' => 'Keine fälligen Queue-Jobs gefunden.',
            'worker' => $worker,
            'claimed' => 0,
            'sent' => 0,
            'retried' => 0,
            'failed_final' => 0,
            'released_stale' => $releasedStale,
            'processed' => 0,
        ];

        foreach ($jobs as $job) {
            $jobId = (int) ($job->id ?? 0);
            if ($jobId <= 0 || !$this->claimJob($jobId)) {
                continue;
            }

            $summary['claimed']++;
            $attemptNumber = (int) ($job->attempts ?? 0) + 1;
            $maxAttempts = (int) ($job->max_attempts ?? $config['max_attempts']);
            $headers = $this->decodeHeaders($job->headers ?? null);
            $headers['X-365CMS-Queue-Id'] = (string) $jobId;
            $headers['X-365CMS-Queue-Attempt'] = (string) $attemptNumber;
            $headers['X-365CMS-Test-Source'] = (string) ($job->source ?? 'queue');

            $result = MailService::getInstance()->sendDetailed(
                (string) ($job->recipient ?? ''),
                (string) ($job->subject ?? ''),
                (string) ($job->body ?? ''),
                $headers
            );

            if (!empty($result['success'])) {
                $this->markSent($jobId);
                $summary['sent']++;
                continue;
            }

            $error = trim((string) ($result['error'] ?? 'Unbekannter Queue-Fehler'));
            $retryable = !empty($result['retryable']);
            $delay = $this->resolveRetryDelay($result, $config);

            if ($retryable && $attemptNumber < $maxAttempts) {
                $this->rescheduleJob($jobId, $error, $delay);
                $summary['retried']++;
                continue;
            }

            $this->markFailed($jobId, $error);
            $summary['failed_final']++;
        }

        $summary['processed'] = $summary['sent'] + $summary['retried'] + $summary['failed_final'];
        $summary['message'] = $summary['processed'] > 0
            ? 'Mail-Queue verarbeitet: ' . $summary['sent'] . ' versendet, ' . $summary['retried'] . ' erneut geplant, ' . $summary['failed_final'] . ' final fehlgeschlagen.'
            : 'Keine fälligen Queue-Jobs gefunden.';

        $this->settings->set(self::GROUP, self::SETTINGS_LAST_RUN, [
            'executed_at' => date('Y-m-d H:i:s'),
            'worker' => $worker,
            'claimed' => $summary['claimed'],
            'sent' => $summary['sent'],
            'retried' => $summary['retried'],
            'failed_final' => $summary['failed_final'],
            'released_stale' => $summary['released_stale'],
            'processed' => $summary['processed'],
        ], false, 0);

        return $summary;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,message?:string,error?:string,worker:string,claimed:int,sent:int,retried:int,failed_final:int,released_stale:int,processed:int}
     */
    public function handleCronHook(array $payload = []): array
    {
        $limit = isset($payload['limit']) ? (int) $payload['limit'] : null;
        $force = !empty($payload['force']);

        return $this->processDueJobs($limit, 'cron', $force);
    }

    public function saveConfiguration(array $config): bool
    {
        return $this->settings->setMany(self::GROUP, [
            'queue_enabled' => !empty($config['enabled']),
            'queue_batch_size' => min(100, max(1, (int) ($config['batch_size'] ?? 10))),
            'queue_max_attempts' => min(20, max(1, (int) ($config['max_attempts'] ?? 5))),
            'queue_retry_delay_seconds' => min(86400, max(60, (int) ($config['retry_delay_seconds'] ?? 300))),
            'queue_throttle_delay_seconds' => min(86400, max(60, (int) ($config['throttle_delay_seconds'] ?? 900))),
            'queue_lock_timeout_seconds' => min(86400, max(60, (int) ($config['lock_timeout_seconds'] ?? 900))),
        ], [], 0);
    }

    public function rotateCronToken(): string
    {
        $token = bin2hex(random_bytes(24));
        $this->settings->set(self::GROUP, 'queue_cron_token', $token, true, 0);

        return $token;
    }

    public function releaseStaleProcessingJobs(int $lockTimeoutSeconds = 900): int
    {
        $lockTimeoutSeconds = min(86400, max(60, $lockTimeoutSeconds));
        $cutoff = date('Y-m-d H:i:s', time() - $lockTimeoutSeconds);

        try {
            $stmt = $this->db->execute(
                "UPDATE {$this->prefix}mail_queue
                 SET status = 'pending', locked_at = NULL, available_at = NOW(), last_error = COALESCE(last_error, 'Stale Job automatisch freigegeben.')
                 WHERE status = 'processing' AND locked_at IS NOT NULL AND locked_at < ?",
                [$cutoff]
            );

            return $stmt->rowCount();
        } catch (\Throwable $e) {
            $this->logger->warning('Stale Mail-Queue-Jobs konnten nicht freigegeben werden', [
                'exception' => $e,
            ]);

            return 0;
        }
    }

    /**
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        try {
            $rows = $this->db->get_results(
                "SELECT status, COUNT(*) AS cnt FROM {$this->prefix}mail_queue GROUP BY status"
            ) ?: [];

            foreach ($rows as $row) {
                $status = (string) ($row->status ?? '');
                if (array_key_exists($status, $stats)) {
                    $stats[$status] = (int) ($row->cnt ?? 0);
                }
            }
        } catch (\Throwable) {
            // Tabelle evtl. vor erster Migration noch nicht vorhanden.
        }

        return $stats;
    }

    /**
     * @return list<object>
     */
    public function getRecent(int $limit = 25): array
    {
        $limit = min(100, max(1, $limit));

        try {
            return $this->db->get_results(
                "SELECT id, recipient, subject, status, attempts, max_attempts, source, available_at, sent_at, locked_at, last_attempt_at, last_error, created_at, updated_at
                 FROM {$this->prefix}mail_queue
                 ORDER BY created_at DESC
                 LIMIT ?",
                [$limit]
            ) ?: [];
        } catch (\Throwable $e) {
            $this->logger->warning('Mail-Queue-Übersicht konnte nicht geladen werden', [
                'exception' => $e,
            ]);

            return [];
        }
    }

    /**
     * @return list<object>
     */
    private function getDueJobs(int $limit): array
    {
        return $this->db->get_results(
            "SELECT *
             FROM {$this->prefix}mail_queue
             WHERE status = 'pending'
               AND (available_at IS NULL OR available_at <= ?)
             ORDER BY COALESCE(available_at, created_at) ASC, id ASC
             LIMIT ?",
            [date('Y-m-d H:i:s'), $limit]
        ) ?: [];
    }

    private function claimJob(int $jobId): bool
    {
        try {
            $stmt = $this->db->execute(
                "UPDATE {$this->prefix}mail_queue
                 SET status = 'processing', attempts = attempts + 1, locked_at = NOW(), last_attempt_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [$jobId]
            );

            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            $this->logger->warning('Mail-Queue-Job konnte nicht übernommen werden', [
                'job_id' => $jobId,
                'exception' => $e,
            ]);

            return false;
        }
    }

    private function markSent(int $jobId): void
    {
        $this->db->execute(
            "UPDATE {$this->prefix}mail_queue
             SET status = 'sent', sent_at = NOW(), locked_at = NULL, available_at = NULL, last_error = NULL
             WHERE id = ?",
            [$jobId]
        );
    }

    private function rescheduleJob(int $jobId, string $error, int $delaySeconds): void
    {
        $nextRun = date('Y-m-d H:i:s', time() + $delaySeconds);

        $this->db->execute(
            "UPDATE {$this->prefix}mail_queue
             SET status = 'pending', locked_at = NULL, available_at = ?, last_error = ?
             WHERE id = ?",
            [$nextRun, $this->truncateError($error), $jobId]
        );
    }

    private function markFailed(int $jobId, string $error): void
    {
        $this->db->execute(
            "UPDATE {$this->prefix}mail_queue
             SET status = 'failed', locked_at = NULL, available_at = NULL, last_error = ?
             WHERE id = ?",
            [$this->truncateError($error), $jobId]
        );
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $config
     */
    private function resolveRetryDelay(array $result, array $config): int
    {
        $category = (string) ($result['error_category'] ?? 'temporary');
        $recommended = (int) ($result['recommended_delay'] ?? 0);

        $baseDelay = $category === 'throttle'
            ? (int) ($config['throttle_delay_seconds'] ?? 900)
            : (int) ($config['retry_delay_seconds'] ?? 300);

        return max(60, $recommended > 0 ? $recommended : $baseDelay);
    }

    /**
     * @param array<string, string> $headers
     */
    private function encodeHeaders(array $headers): ?string
    {
        if ($headers === []) {
            return null;
        }

        try {
            return json_encode($headers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function decodeHeaders(mixed $headers): array
    {
        if (!is_string($headers) || trim($headers) === '') {
            return [];
        }

        try {
            $decoded = json_decode($headers, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                return [];
            }

            $normalized = [];
            foreach ($decoded as $key => $value) {
                if (!is_scalar($value)) {
                    continue;
                }

                $normalized[(string) $key] = trim((string) $value);
            }

            return $normalized;
        } catch (\Throwable) {
            return [];
        }
    }

    private function truncateError(string $error): string
    {
        $error = trim($error);
        if ($error === '') {
            return 'Unbekannter Mail-Queue-Fehler';
        }

        return mb_substr($error, 0, 4000);
    }
}
