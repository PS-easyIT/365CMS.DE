<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Bootstrap;
use CMS\Database;
use CMS\Hooks;

if (!defined('ABSPATH')) {
    exit;
}

final class CronRunnerService
{
    private static ?self $instance = null;

    /**
     * @return list<string>
     */
    public function getSupportedTasks(): array
    {
        return ['all', 'mail-queue', 'hourly', 'cms_cron_mail_queue', 'cms_cron_hourly'];
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * @param array{task?:mixed,limit?:mixed,force?:mixed,mode?:mixed,source?:mixed} $options
     * @return array{success:bool,task:string,mode:string,source:string,result:array<string,mixed>,error?:string,error_code?:string}
     */
    public function run(array $options = []): array
    {
        $task = $this->normalizeTask($options['task'] ?? 'all');
        $limit = $this->normalizeLimit($options['limit'] ?? null);
        $force = !empty($options['force']);
        $mode = trim((string) ($options['mode'] ?? (PHP_SAPI === 'cli' ? 'cli' : 'web')));
        $mode = $mode !== '' ? $mode : (PHP_SAPI === 'cli' ? 'cli' : 'web');
        $source = trim((string) ($options['source'] ?? 'cron-runner'));
        $source = $source !== '' ? $source : 'cron-runner';

        $result = [
            'mail_queue' => null,
            'hourly' => null,
            'feed_queue_recovery' => null,
            'feed_queue' => null,
            'hook' => null,
        ];

        $supportedTasks = $this->getSupportedTasks();
        $isGenericCronHook = str_starts_with($task, 'cms_cron_');
        if (!$isGenericCronHook && !in_array($task, $supportedTasks, true)) {
            return [
                'success' => false,
                'task' => $task,
                'mode' => $mode,
                'source' => $source,
                'result' => $result,
                'error_code' => 'invalid_task',
                'error' => 'Unbekannte Cron-Task. Unterstützt werden aktuell "all", "mail-queue", "hourly" und generische "cms_cron_*"-Hooks.',
            ];
        }

        $lockHandle = $this->acquireLock();
        if ($lockHandle === null) {
            return [
                'success' => false,
                'task' => $task,
                'mode' => $mode,
                'source' => $source,
                'result' => $result,
                'error_code' => 'lock_active',
                'error' => 'Cron-Lauf bereits aktiv.',
            ];
        }

        try {
            Bootstrap::instance();

            $queue = MailQueueService::getInstance();
            $settings = SettingsService::getInstance();

            if ($task === 'mail-queue' || $task === 'cms_cron_mail_queue' || $task === 'all') {
                $result['mail_queue'] = $queue->handleCronHook([
                    'limit' => $limit,
                    'force' => $force,
                ]);

                Hooks::doAction('cms_cron_mail_queue', [
                    'limit' => $limit,
                    'force' => $force,
                    'source' => $source,
                    'mode' => $mode,
                    'mail_queue_already_handled' => true,
                ]);
            }

            if ($task === 'hourly' || $task === 'cms_cron_hourly' || $task === 'all' || $task === 'mail-queue' || $task === 'cms_cron_mail_queue') {
                $result['hourly'] = $this->runHourlyHooks($settings, $force);
            }

            if (($task === 'mail-queue' || $task === 'cms_cron_mail_queue' || $task === 'all')
                && (!is_array($result['hourly']) || !empty($result['hourly']['skipped']))) {
                $result['feed_queue_recovery'] = $this->repairFeedProcessingQueue();
                $result['feed_queue'] = $this->runLegacyFeedQueueBridge();
            }

            if ($isGenericCronHook && !in_array($task, ['cms_cron_mail_queue', 'cms_cron_hourly'], true)) {
                $result['hook'] = $this->runGenericHook($task, $limit, $force, $mode, $source);
            }

            $success = true;
            if (is_array($result['mail_queue']) && array_key_exists('success', $result['mail_queue'])) {
                $success = $success && !empty($result['mail_queue']['success']);
            }
            if (is_array($result['hourly']) && array_key_exists('success', $result['hourly'])) {
                $success = $success && !empty($result['hourly']['success']);
            }
            if (is_array($result['hook']) && array_key_exists('success', $result['hook'])) {
                $success = $success && !empty($result['hook']['success']);
            }
            if (is_array($result['feed_queue']) && array_key_exists('success', $result['feed_queue'])) {
                $success = $success && !empty($result['feed_queue']['success']);
            }
            if (is_array($result['feed_queue_recovery']) && array_key_exists('success', $result['feed_queue_recovery'])) {
                $success = $success && !empty($result['feed_queue_recovery']['success']);
            }

            return [
                'success' => $success,
                'task' => $task,
                'mode' => $mode,
                'source' => $source,
                'result' => $result,
            ];
        } catch (\Throwable $e) {
            error_log(
                'CMS Cron Error [' . get_class($e) . ']: '
                . $this->truncateForLog($e->getMessage())
                . ' in '
                . $this->truncateForLog($e->getFile(), 220)
                . ':'
                . (int) $e->getLine()
            );

            return [
                'success' => false,
                'task' => $task,
                'mode' => $mode,
                'source' => $source,
                'result' => $result,
                'error_code' => 'exception',
                'error' => 'Cron-Lauf fehlgeschlagen. Details wurden intern protokolliert.',
            ];
        } finally {
            $this->releaseLock($lockHandle);
        }
    }

    private function normalizeTask(mixed $task): string
    {
        $task = strtolower(trim((string) $task));
        if ($task === '' || $task === 'default') {
            return 'all';
        }

        if (str_starts_with($task, 'hook:')) {
            $task = substr($task, 5);
        }

        $task = preg_replace('/[^a-z0-9_-]+/', '', $task) ?? '';

        return $task !== '' ? $task : 'all';
    }

    private function normalizeLimit(mixed $limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $normalized = filter_var($limit, FILTER_VALIDATE_INT);
        if ($normalized === false) {
            return null;
        }

        return min(100, max(1, (int) $normalized));
    }

    /**
     * @return resource|null
     */
    private function acquireLock()
    {
        $lockFile = rtrim((string) sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '365cms-cron-' . hash('sha256', ABSPATH) . '.lock';
        $lockHandle = @fopen($lockFile, 'c+');
        if (!is_resource($lockHandle) || !@flock($lockHandle, LOCK_EX | LOCK_NB)) {
            if (is_resource($lockHandle)) {
                @fclose($lockHandle);
            }

            return null;
        }

        return $lockHandle;
    }

    /**
     * @param resource|null $lockHandle
     */
    private function releaseLock($lockHandle): void
    {
        if (!is_resource($lockHandle)) {
            return;
        }

        @flock($lockHandle, LOCK_UN);
        @fclose($lockHandle);
    }

    private function runHourlyHooks(SettingsService $settings, bool $forceRun): array
    {
        $lastRunRaw = $settings->getString('cron', 'hourly_last_run', '');
        $lastRunTs = $lastRunRaw !== '' ? strtotime($lastRunRaw) : false;
        $isDue = $forceRun || $lastRunTs === false || (time() - $lastRunTs) >= 3600;

        if (!$isDue) {
            return [
                'success' => true,
                'executed' => false,
                'skipped' => true,
                'reason' => 'Stündlicher Hook ist noch nicht fällig.',
                'last_run' => $lastRunRaw,
                'next_due_in_seconds' => max(0, 3600 - (time() - (int) $lastRunTs)),
            ];
        }

        Hooks::doAction('cms_cron_hourly');

        $executedAt = date('Y-m-d H:i:s');
        $settings->set('cron', 'hourly_last_run', $executedAt, false, 0);

        return [
            'success' => true,
            'executed' => true,
            'skipped' => false,
            'executed_at' => $executedAt,
        ];
    }

    private function runGenericHook(string $hookName, ?int $limit, bool $forceRun, string $mode, string $source): array
    {
        Hooks::doAction($hookName, [
            'limit' => $limit,
            'force' => $forceRun,
            'source' => $source,
            'mode' => $mode,
        ]);

        return [
            'success' => true,
            'executed' => true,
            'hook' => $hookName,
        ];
    }

    private function repairFeedProcessingQueue(): array
    {
        if (!class_exists('CMS_Feed_Database')) {
            return [
                'success' => true,
                'requeued' => 0,
                'mode' => 'feed-queue-not-loaded',
            ];
        }

        try {
            $db = Database::instance();
            $prefix = $db->prefix();
            $stmt = $db->prepare(
                "UPDATE {$prefix}feed_fetch_queue
                 SET status = 'pending',
                     error = CASE
                         WHEN error IS NULL OR error = '' THEN 'Queue-Task nach Timeout aus processing zurück auf pending gesetzt.'
                         ELSE CONCAT(error, '\n[cron-recovery] Queue-Task nach Timeout aus processing zurück auf pending gesetzt.')
                     END
                 WHERE status = 'processing'
                   AND created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)
                   AND processed_at IS NULL"
            );
            $stmt->execute();

            return [
                'success' => true,
                'requeued' => (int) $stmt->rowCount(),
                'mode' => 'core-feed-queue-recovery',
            ];
        } catch (\Throwable $e) {
            error_log('CMS Cron Feed Queue Recovery Error: ' . $e->getMessage());

            return [
                'success' => false,
                'requeued' => 0,
                'mode' => 'core-feed-queue-recovery',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function runLegacyFeedQueueBridge(): ?array
    {
        if (!class_exists('CMS_Feed_Cron')) {
            return null;
        }

        $feedCron = \CMS_Feed_Cron::instance();
        if (!method_exists($feedCron, 'process_queue')) {
            return null;
        }

        if (Hooks::hasAction('cms_cron_mail_queue', [$feedCron, 'drain_pending_queue'], 20)) {
            return null;
        }

        $result = $feedCron->process_queue();

        return [
            'success' => true,
            'executed' => true,
            'mode' => 'legacy-core-bridge',
            'result' => $result,
        ];
    }

    private function truncateForLog(string $value, int $limit = 400): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, 0, $limit, 'UTF-8');
        }

        return substr($value, 0, $limit);
    }
}