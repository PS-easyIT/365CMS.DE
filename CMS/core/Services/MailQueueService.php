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
        $cronFilePath = ABSPATH . 'cron.php';
        $cronWebPath = '/cron.php';
        $cronUrl = (defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '') . $cronWebPath . '?task=mail-queue&quiet=1&token=' . rawurlencode($token);

        return [
            'enabled' => $this->settings->getBool(self::GROUP, 'queue_enabled', true),
            'batch_size' => $batchSize,
            'max_attempts' => $maxAttempts,
            'retry_delay_seconds' => $retryDelay,
            'throttle_delay_seconds' => $throttleDelay,
            'lock_timeout_seconds' => $lockTimeout,
            'cron_token' => $token,
            'cron_url' => $cronUrl,
            'cli_command' => 'php ' . escapeshellarg($cronFilePath) . ' --task=mail-queue --limit=' . $batchSize . ' --quiet',
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
            'failure_categories' => $this->getFailureCategories(),
            'stale_jobs' => $this->getStaleJobs(),
        ];
    }

    public function isEnabled(): bool
    {
        return !empty($this->getConfiguration()['enabled']);
    }

    public function shouldQueue(array $headers = []): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $normalized = $this->decodeHeaders($this->encodeHeaders($headers));
        $forceDirect = strtolower(trim((string) ($normalized['X-365CMS-Force-Direct'] ?? '0')));

        return !in_array($forceDirect, ['1', 'true', 'yes', 'on'], true);
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
        return $this->enqueueMessage($recipient, $subject, $htmlBody, $headers, $availableAt, $source, $maxAttempts, 'html');
    }

    /**
     * @param array<string, string> $headers
     * @return array{success:bool,message?:string,error?:string,id?:int}
     */
    public function enqueuePlain(
        string $recipient,
        string $subject,
        string $plainBody,
        array $headers = [],
        ?\DateTimeInterface $availableAt = null,
        string $source = 'system',
        ?int $maxAttempts = null
    ): array {
        return $this->enqueueMessage($recipient, $subject, $plainBody, $headers, $availableAt, $source, $maxAttempts, 'plain');
    }

    /**
     * @param array<string, string> $headers
     * @return array{success:bool,message?:string,error?:string,id?:int}
     */
    public function enqueueWithAttachment(
        string $recipient,
        string $subject,
        string $body,
        string $attachmentPath,
        string $attachmentName = '',
        bool $isHtml = true,
        array $headers = [],
        ?\DateTimeInterface $availableAt = null,
        string $source = 'system',
        ?int $maxAttempts = null
    ): array {
        $attachmentPath = trim($attachmentPath);
        if ($attachmentPath === '' || !is_file($attachmentPath) || !is_readable($attachmentPath)) {
            return ['success' => false, 'error' => 'Anhang für die Queue ist nicht lesbar.'];
        }

        $attachmentName = trim($attachmentName) !== '' ? trim($attachmentName) : basename($attachmentPath);
        $attachmentMime = mime_content_type($attachmentPath) ?: 'application/octet-stream';

        return $this->enqueueMessage(
            $recipient,
            $subject,
            $body,
            $headers,
            $availableAt,
            $source,
            $maxAttempts,
            $isHtml ? 'html' : 'plain',
            $attachmentPath,
            $attachmentName,
            $attachmentMime
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDiagnosticsData(int $limit = 100): array
    {
        $config = $this->getConfiguration();

        return [
            'config' => $config,
            'stats' => $this->getStats(),
            'last_run' => $config['last_run'],
            'recent_jobs' => $this->getRecent($limit),
            'failure_categories' => $this->getFailureCategories(),
            'stale_jobs' => $this->getStaleJobs(),
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array{success:bool,message?:string,error?:string,id?:int}
     */
    private function enqueueMessage(
        string $recipient,
        string $subject,
        string $body,
        array $headers = [],
        ?\DateTimeInterface $availableAt = null,
        string $source = 'system',
        ?int $maxAttempts = null,
        string $contentType = 'html',
        ?string $attachmentPath = null,
        ?string $attachmentName = null,
        ?string $attachmentMime = null
    ): array {
        $recipient = trim($recipient);
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'Ungültige Empfänger-E-Mail-Adresse für die Queue.'];
        }

        $subject = trim($subject);
        if ($subject === '') {
            return ['success' => false, 'error' => 'Für die Queue ist ein Betreff erforderlich.'];
        }

        $contentType = $contentType === 'plain' ? 'plain' : 'html';

        $config = $this->getConfiguration();
        $insertId = $this->db->insert('mail_queue', [
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'headers' => $this->encodeHeaders($headers),
            'content_type' => $contentType,
            'source' => trim($source) !== '' ? trim($source) : 'system',
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $maxAttempts !== null ? min(20, max(1, $maxAttempts)) : (int) $config['max_attempts'],
            'available_at' => ($availableAt ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'sent_at' => null,
            'locked_at' => null,
            'last_attempt_at' => null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime' => $attachmentMime,
            'error_category' => null,
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
            $contentType = (string) ($job->content_type ?? 'html');
            $attachmentPath = trim((string) ($job->attachment_path ?? ''));
            $attachmentName = (string) ($job->attachment_name ?? '');
            $attachmentMime = (string) ($job->attachment_mime ?? '');

            if ($attachmentPath !== '') {
                $result = MailService::getInstance()->sendWithAttachmentDetailed(
                    (string) ($job->recipient ?? ''),
                    (string) ($job->subject ?? ''),
                    (string) ($job->body ?? ''),
                    $attachmentPath,
                    $attachmentName,
                    $contentType !== 'plain',
                    $headers,
                    $attachmentMime
                );
            } elseif ($contentType === 'plain') {
                $result = MailService::getInstance()->sendPlainDetailed(
                    (string) ($job->recipient ?? ''),
                    (string) ($job->subject ?? ''),
                    (string) ($job->body ?? ''),
                    $headers
                );
            } else {
                $result = MailService::getInstance()->sendDetailed(
                    (string) ($job->recipient ?? ''),
                    (string) ($job->subject ?? ''),
                    (string) ($job->body ?? ''),
                    $headers
                );
            }

            if (!empty($result['success'])) {
                $this->markSent($jobId);
                $summary['sent']++;
                continue;
            }

            $error = trim((string) ($result['error'] ?? 'Unbekannter Queue-Fehler'));
            $retryable = !empty($result['retryable']);
            $delay = $this->resolveRetryDelay($result, $config);
            $errorCategory = (string) ($result['error_category'] ?? 'temporary');

            if ($retryable && $attemptNumber < $maxAttempts) {
                $this->rescheduleJob($jobId, $error, $delay, $errorCategory);
                $summary['retried']++;
                continue;
            }

            $this->markFailed($jobId, $error, $errorCategory);
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
                 SET status = 'pending', locked_at = NULL, available_at = NOW(), error_category = COALESCE(error_category, 'stale_lock'), last_error = COALESCE(last_error, 'Stale Job automatisch freigegeben.')
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
                "SELECT id, recipient, subject, status, attempts, max_attempts, source, content_type, available_at, sent_at, locked_at, last_attempt_at, error_category, last_error, created_at, updated_at
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
             SET status = 'sent', sent_at = NOW(), locked_at = NULL, available_at = NULL, error_category = NULL, last_error = NULL
             WHERE id = ?",
            [$jobId]
        );
    }

    private function rescheduleJob(int $jobId, string $error, int $delaySeconds, string $errorCategory): void
    {
        $nextRun = date('Y-m-d H:i:s', time() + $delaySeconds);

        $this->db->execute(
            "UPDATE {$this->prefix}mail_queue
             SET status = 'pending', locked_at = NULL, available_at = ?, error_category = ?, last_error = ?
             WHERE id = ?",
            [$nextRun, $errorCategory, $this->truncateError($error), $jobId]
        );
    }

    private function markFailed(int $jobId, string $error, string $errorCategory): void
    {
        $this->db->execute(
            "UPDATE {$this->prefix}mail_queue
             SET status = 'failed', locked_at = NULL, available_at = NULL, error_category = ?, last_error = ?
             WHERE id = ?",
            [$errorCategory, $this->truncateError($error), $jobId]
        );
    }

    /**
     * @return list<array{category:string,count:int}>
     */
    private function getFailureCategories(): array
    {
        try {
            $rows = $this->db->get_results(
                "SELECT COALESCE(error_category, 'unknown') AS category, COUNT(*) AS cnt
                 FROM {$this->prefix}mail_queue
                 WHERE status IN ('pending', 'failed') AND last_error IS NOT NULL
                 GROUP BY COALESCE(error_category, 'unknown')
                 ORDER BY cnt DESC, category ASC"
            ) ?: [];

            return array_map(static function (object $row): array {
                return [
                    'category' => (string) ($row->category ?? 'unknown'),
                    'count' => (int) ($row->cnt ?? 0),
                ];
            }, $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<object>
     */
    private function getStaleJobs(): array
    {
        $config = $this->getConfiguration();
        $cutoff = date('Y-m-d H:i:s', time() - (int) ($config['lock_timeout_seconds'] ?? 900));

        try {
            return $this->db->get_results(
                "SELECT id, recipient, subject, attempts, max_attempts, locked_at, last_attempt_at, error_category, last_error, source, updated_at
                 FROM {$this->prefix}mail_queue
                 WHERE status = 'processing' AND locked_at IS NOT NULL AND locked_at < ?
                 ORDER BY locked_at ASC",
                [$cutoff]
            ) ?: [];
        } catch (\Throwable) {
            return [];
        }
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
