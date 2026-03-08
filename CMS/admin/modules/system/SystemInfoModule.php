<?php
declare(strict_types=1);

/**
 * System-Info, Diagnose & Monitoring-Modul
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\SchemaManager;
use CMS\Services\MailQueueService;
use CMS\Services\MailService;
use CMS\Services\SystemService;
use CMS\AuditLogger;

class SystemInfoModule
{
    private const MONITOR_DEFAULTS = [
        'monitor_email_notifications_enabled' => '0',
        'monitor_alert_email' => '',
        'monitor_response_threshold_ms' => '800',
        'monitor_disk_threshold_percent' => '85',
        'monitor_health_endpoint_enabled' => '0',
        'monitor_health_endpoint_path' => '/health',
    ];

    private SystemService $service;

    public function __construct()
    {
        $this->service = SystemService::instance();
    }

    public function getData(): array
    {
        return [
            'system' => $this->getSystemInfoSafe(),
            'database' => $this->getDatabaseStatusSafe(),
            'tables' => $this->getTablesSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'directories' => $this->getDirectorySizesSafe(),
            'statistics' => $this->getStatisticsSafe(),
            'security' => $this->getSecurityStatusSafe(),
            'monitoring' => $this->getMonitoringOverview(),
            'cron' => $this->getCronData(),
            'disk' => $this->getDiskUsageData(),
            'scheduled_tasks' => $this->getScheduledTasksData(),
            'health' => $this->getHealthChecksData(),
            'email_alerts' => $this->getMonitoringSettings(),
            'mail_queue' => $this->getMailQueueData(),
        ];
    }

    public function getInfoData(): array
    {
        return $this->getData();
    }

    public function getDiagnosticsData(): array
    {
        return [
            'database' => $this->getDatabaseStatusSafe(),
            'tables' => $this->getTablesSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'monitoring' => $this->getMonitoringOverview(),
            'health' => $this->getHealthChecksData(),
        ];
    }

    public function handleAction(string $section, string $action, array $post): array
    {
        return match ($action) {
            'clear_cache' => $this->clearCache(),
            'optimize_db' => $this->optimizeDatabase(),
            'clear_logs' => $this->clearLogs(),
            'create_tables' => $this->createMissingTables(),
            'repair_tables' => $this->repairTables(),
            'save_monitoring_alerts' => $this->saveMonitoringSettings($post),
            'send_monitoring_test_email' => $this->sendMonitoringTestEmail($post),
            'run_mail_queue_now' => $this->runMailQueueNow($post),
            'release_mail_queue_stale' => $this->releaseMailQueueStale(),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };
    }

    public function clearCache(): array
    {
        try {
            $this->service->clearCache();
            $this->service->clearOldSessions();
            $this->service->clearOldFailedLogins();
            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.cache.clear',
                'System-Cache, Sessions und Login-Versuche bereinigt',
                'system',
                null,
                [],
                'warning'
            );
            return ['success' => true, 'message' => 'Cache, alte Sessions und Login-Versuche bereinigt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function optimizeDatabase(): array
    {
        try {
            $results = $this->service->optimizeTables();
            $success = 0;
            $failed = 0;
            foreach ($results as $result) {
                if (!empty($result['success'])) {
                    $success++;
                } else {
                    $failed++;
                }
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.database.optimize',
                'Datenbankoptimierung aus Diagnosebereich gestartet',
                'database',
                null,
                ['success' => $success, 'failed' => $failed],
                'warning'
            );

            return ['success' => true, 'message' => $success . ' Tabelle(n) optimiert' . ($failed > 0 ? ', ' . $failed . ' fehlgeschlagen' : '') . '.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function clearLogs(): array
    {
        try {
            $this->service->clearErrorLogs();
            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.logs.clear',
                'Fehlerlogs aus dem Diagnosebereich gelöscht',
                'log',
                null,
                [],
                'warning'
            );
            return ['success' => true, 'message' => 'Fehlerlogs gelöscht.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function createMissingTables(): array
    {
        try {
            $db = Database::instance();
            $schema = new SchemaManager($db);
            $schema->clearFlag();
            $schema->createTables();
            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.schema.create_missing',
                'Fehlende Tabellen/Migrationen aus dem Diagnosebereich erstellt',
                'database',
                null,
                [],
                'warning'
            );
            return ['success' => true, 'message' => 'Fehlende Tabellen wurden erstellt und Migrationen ausgeführt.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function repairTables(): array
    {
        try {
            $db = Database::instance();
            $pdo = $db->getPdo();
            $prefix = $db->getPrefix();
            $tables = $this->getTablesSafe();
            $repaired = 0;
            $errors = [];

            foreach ($tables as $info) {
                if (!is_array($info) || empty($info['exists'])) {
                    continue;
                }

                $fullTable = $prefix . $info['name'];
                $check = $pdo->query("CHECK TABLE `{$fullTable}`");
                $row = $check ? $check->fetch(\PDO::FETCH_ASSOC) : null;
                $status = $row['Msg_text'] ?? 'OK';

                if (stripos($status, 'ok') === false) {
                    $repair = $pdo->query("REPAIR TABLE `{$fullTable}`");
                    $repairRow = $repair ? $repair->fetch(\PDO::FETCH_ASSOC) : null;
                    $repairStatus = $repairRow['Msg_text'] ?? 'unknown';
                    if (stripos($repairStatus, 'ok') !== false) {
                        $repaired++;
                    } else {
                        $errors[] = $info['name'] . ': ' . $repairStatus;
                    }
                }
            }

            $message = $repaired > 0 ? $repaired . ' Tabelle(n) repariert.' : 'Alle Tabellen sind in Ordnung — keine Reparatur nötig.';
            if ($errors !== []) {
                $message .= ' Fehler bei: ' . implode(', ', $errors);
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.database.repair',
                'Tabellenprüfung/Reparatur aus dem Diagnosebereich ausgeführt',
                'database',
                null,
                ['repaired' => $repaired, 'errors' => $errors],
                $errors === [] ? 'warning' : 'critical'
            );

            return ['success' => true, 'message' => $message];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function getSystemInfoSafe(): array
    {
        try {
            return $this->service->getSystemInfo();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getDatabaseStatusSafe(): array
    {
        try {
            return $this->service->getDatabaseStatus();
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getTablesSafe(): array
    {
        try {
            return $this->service->checkDatabaseTables();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getPermissionsSafe(): array
    {
        try {
            return $this->service->checkFilePermissions();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getDirectorySizesSafe(): array
    {
        try {
            return $this->service->getDirectorySizes();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getStatisticsSafe(): array
    {
        try {
            return $this->service->getCMSStatistics();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getSecurityStatusSafe(): array
    {
        try {
            return $this->service->getSecurityStatus();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getMonitoringSettings(): array
    {
        $settings = self::MONITOR_DEFAULTS;
        $db = Database::instance();
        $keys = array_keys($settings);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        try {
            $rows = $db->get_results(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name IN ({$placeholders})",
                $keys
            );

            foreach ($rows as $row) {
                $name = (string)($row->option_name ?? '');
                if ($name !== '' && array_key_exists($name, $settings)) {
                    $settings[$name] = (string)($row->option_value ?? '');
                }
            }
        } catch (\Throwable) {
        }

        return $settings;
    }

    private function saveMonitoringSettings(array $post): array
    {
        $settings = $this->getMonitoringSettings();
        $settings['monitor_email_notifications_enabled'] = !empty($post['monitor_email_notifications_enabled']) ? '1' : '0';
        $settings['monitor_alert_email'] = trim((string)($post['monitor_alert_email'] ?? ''));
        $settings['monitor_response_threshold_ms'] = (string)max(100, (int)($post['monitor_response_threshold_ms'] ?? $settings['monitor_response_threshold_ms']));
        $settings['monitor_disk_threshold_percent'] = (string)min(99, max(1, (int)($post['monitor_disk_threshold_percent'] ?? $settings['monitor_disk_threshold_percent'])));
        $settings['monitor_health_endpoint_enabled'] = !empty($post['monitor_health_endpoint_enabled']) ? '1' : '0';
        $settings['monitor_health_endpoint_path'] = trim((string)($post['monitor_health_endpoint_path'] ?? $settings['monitor_health_endpoint_path'])) ?: '/health';

        try {
            $db = Database::instance();
            foreach ($settings as $key => $value) {
                $exists = (int)($db->get_var("SELECT COUNT(*) FROM {$db->getPrefix()}settings WHERE option_name = ?", [$key]) ?? 0);
                if ($exists > 0) {
                    $db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Monitoring-Einstellungen konnten nicht gespeichert werden: ' . $e->getMessage()];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.monitoring.save',
            'Monitoring- und Alert-Einstellungen gespeichert',
            'monitoring',
            null,
            $settings,
            'warning'
        );

        return ['success' => true, 'message' => 'Monitoring- und E-Mail-Einstellungen gespeichert.'];
    }

    private function sendMonitoringTestEmail(array $post): array
    {
        $settings = $this->getMonitoringSettings();
        $recipient = trim((string)($post['test_email_recipient'] ?? ''));
        if ($recipient === '') {
            $recipient = trim((string)($settings['monitor_alert_email'] ?? ''));
        }

        $queue = MailQueueService::getInstance();
        $result = $queue->shouldQueue()
            ? MailService::getInstance()->queueBackendTestEmail($recipient, 'monitor-email-alerts')
            : MailService::getInstance()->sendBackendTestEmail($recipient, 'monitor-email-alerts');

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.monitoring.mail_test',
            !empty($result['success']) ? 'Monitoring-Test-E-Mail versendet' : 'Monitoring-Test-E-Mail fehlgeschlagen',
            'monitoring',
            null,
            [
                'recipient' => $recipient,
                'result' => !empty($result['success']) ? 'success' : 'error',
                'transport' => $result['transport'] ?? null,
                'queued' => isset($result['id']),
            ],
            !empty($result['success']) ? 'info' : 'warning'
        );

        return $result;
    }

    private function runMailQueueNow(array $post): array
    {
        $limit = max(1, min(100, (int) ($post['queue_run_limit'] ?? 25)));
        $result = MailQueueService::getInstance()->processDueJobs($limit, 'monitoring', true);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.monitoring.mail_queue_run',
            !empty($result['success']) ? 'Mail-Queue aus der Diagnose manuell ausgeführt.' : 'Mail-Queue-Lauf aus der Diagnose fehlgeschlagen.',
            'monitoring',
            null,
            $result,
            !empty($result['success']) ? 'info' : 'warning'
        );

        return $result;
    }

    private function releaseMailQueueStale(): array
    {
        $queue = MailQueueService::getInstance();
        $config = $queue->getConfiguration();
        $released = $queue->releaseStaleProcessingJobs((int) ($config['lock_timeout_seconds'] ?? 900));

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.monitoring.mail_queue_release_stale',
            'Verwaiste Mail-Queue-Locks aus der Diagnose freigegeben.',
            'monitoring',
            null,
            ['released' => $released],
            'warning'
        );

        return [
            'success' => true,
            'message' => $released > 0
                ? $released . ' verwaiste Mail-Queue-Locks wurden freigegeben.'
                : 'Keine verwaisten Mail-Queue-Locks gefunden.',
        ];
    }

    private function getMonitoringOverview(): array
    {
        return [
            'response_time' => $this->measureResponseTime(SITE_URL),
            'cron_hooks' => count($this->getCronData()['hooks'] ?? []),
            'disk' => $this->getDiskUsageData(),
        ];
    }

    private function getCronData(): array
    {
        $scanRoots = [
            ABSPATH,
            dirname(ABSPATH) . '-PLUGINS',
        ];
        $hooks = [];

        foreach ($scanRoots as $root) {
            if (!is_dir($root)) {
                continue;
            }

            try {
                $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                        continue;
                    }

                    $path = $file->getPathname();
                    if (str_contains($path, DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR) || str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
                        continue;
                    }

                    $content = @file_get_contents($path);
                    if ($content === false) {
                        continue;
                    }

                    if (preg_match_all("/'(cms_cron_[a-z_]+)'/i", $content, $matches) > 0) {
                        foreach (array_unique($matches[1]) as $hook) {
                            $hooks[$hook][] = $this->normalizeDisplayedPath($path);
                        }
                    }
                }
            } catch (\Throwable) {
            }
        }

        ksort($hooks);
        $mappedHooks = [];
        foreach ($hooks as $hook => $files) {
            $mappedHooks[] = [
                'hook' => $hook,
                'files' => array_values(array_unique($files)),
                'occurrences' => count($files),
            ];
        }

        return [
            'cron_file_exists' => file_exists(ABSPATH . 'cron.php'),
            'hooks' => $mappedHooks,
            'hook_count' => count($mappedHooks),
        ];
    }

    private function getDiskUsageData(): array
    {
        $total = @disk_total_space(ABSPATH);
        $free = @disk_free_space(ABSPATH);
        $used = ($total !== false && $free !== false) ? $total - $free : 0;

        return [
            'total_bytes' => $total !== false ? (int)$total : 0,
            'free_bytes' => $free !== false ? (int)$free : 0,
            'used_bytes' => (int)$used,
            'used_percent' => ($total !== false && $total > 0) ? round(($used / $total) * 100, 1) : null,
            'directories' => $this->getDirectorySizesSafe(),
        ];
    }

    private function getScheduledTasksData(): array
    {
        $cron = $this->getCronData();
        $tasks = [];
        foreach ($cron['hooks'] as $hook) {
            $tasks[] = [
                'name' => (string)($hook['hook'] ?? ''),
                'description' => 'Registrierter Cron-/Task-Hook im Codebestand',
                'files' => (array)($hook['files'] ?? []),
                'occurrences' => (int)($hook['occurrences'] ?? 0),
            ];
        }

        return [
            'tasks' => $tasks,
            'task_count' => count($tasks),
        ];
    }

    private function getHealthChecksData(): array
    {
        $settings = $this->getMonitoringSettings();
        $response = $this->measureResponseTime(SITE_URL);
        $disk = $this->getDiskUsageData();
        $db = $this->getDatabaseStatusSafe();
        $cacheWritable = is_writable(ABSPATH . 'cache');
        $uploadsWritable = is_writable(ABSPATH . 'uploads');
        $logsWritable = is_writable(ABSPATH . 'logs');

        $checks = [
            ['label' => 'Datenbank', 'passed' => !empty($db['connected']), 'detail' => !empty($db['connected']) ? 'Verbunden' : 'Nicht erreichbar'],
            ['label' => 'Cache-Verzeichnis', 'passed' => $cacheWritable, 'detail' => $cacheWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Uploads-Verzeichnis', 'passed' => $uploadsWritable, 'detail' => $uploadsWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Logs-Verzeichnis', 'passed' => $logsWritable, 'detail' => $logsWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Response Time', 'passed' => empty($response['error']) && ((int)($response['duration_ms'] ?? 0) <= (int)$settings['monitor_response_threshold_ms']), 'detail' => empty($response['error']) ? ((int)$response['duration_ms']) . ' ms' : (string)$response['error']],
            ['label' => 'Disk-Auslastung', 'passed' => ($disk['used_percent'] ?? 0) < (float)$settings['monitor_disk_threshold_percent'], 'detail' => ($disk['used_percent'] ?? null) !== null ? ((string)$disk['used_percent']) . '%' : 'Unbekannt'],
            ['label' => 'Health-Endpunkt', 'passed' => ($settings['monitor_health_endpoint_enabled'] ?? '0') === '1', 'detail' => ($settings['monitor_health_endpoint_enabled'] ?? '0') === '1' ? (string)$settings['monitor_health_endpoint_path'] : 'Deaktiviert'],
        ];

        $passed = 0;
        foreach ($checks as $check) {
            if (!empty($check['passed'])) {
                $passed++;
            }
        }

        return [
            'checks' => $checks,
            'passed' => $passed,
            'total' => count($checks),
        ];
    }

    private function getMailQueueData(): array
    {
        return MailQueueService::getInstance()->getDiagnosticsData(100);
    }

    private function measureResponseTime(string $url): array
    {
        $start = microtime(true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => '365CMS-Monitor/1.0',
            ]);
            curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'url' => $url,
                'duration_ms' => (int)round((microtime(true) - $start) * 1000),
                'status_code' => $status,
                'error' => $error !== '' ? $error : null,
            ];
        }

        $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $content = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        $status = 0;
        if (!empty($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            $status = (int)$matches[1];
        }

        return [
            'url' => $url,
            'duration_ms' => (int)round((microtime(true) - $start) * 1000),
            'status_code' => $status,
            'error' => $content === false ? 'Anfrage fehlgeschlagen' : null,
        ];
    }

    private function normalizeDisplayedPath(string $path): string
    {
        if (str_starts_with($path, ABSPATH)) {
            return str_replace('\\', '/', str_replace(ABSPATH, '', $path));
        }

        return str_replace('\\', '/', $path);
    }

    public function runSystemCheck(): array
    {
        return [
            'system_info' => $this->service->getSystemInfo(),
            'database_status' => $this->service->getDatabaseStatus(),
            'table_status' => $this->service->checkDatabaseTables(),
            'file_permissions' => $this->service->checkFilePermissions(),
            'directory_sizes' => $this->service->getDirectorySizes(),
            'cms_statistics' => $this->service->getCMSStatistics(),
            'security_status' => $this->service->getSecurityStatus(),
            'monitoring' => $this->getMonitoringOverview(),
            'health' => $this->getHealthChecksData(),
        ];
    }
}
