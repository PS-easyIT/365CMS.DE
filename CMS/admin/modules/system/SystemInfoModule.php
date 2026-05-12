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
use CMS\Http\Client as HttpClient;
use CMS\SchemaManager;
use CMS\Services\CronRunnerService;
use CMS\Services\MailQueueService;
use CMS\Services\MailService;
use CMS\Services\SecurityAlertService;
use CMS\Services\ErrorReportService;
use CMS\Services\SystemService;
use CMS\Services\UpdateService;
use CMS\AuditLogger;
use CMS\VendorRegistry;

class SystemInfoModule
{
    private const MAX_AUDIT_STRING_LENGTH = 240;
    private const OPERATIONAL_AUDIT_LIMIT = 40;
    private const UPDATE_HISTORY_LIMIT = 12;
    private const DIAGNOSTIC_REPORT_CMS_LOG_LIMIT = 5;
    private const DIAGNOSTIC_REPORT_LOG_ENTRY_LIMIT = 40;

    private const MONITOR_DEFAULTS = [
        'monitor_email_notifications_enabled' => '0',
        'monitor_alert_email' => '',
        'monitor_response_threshold_ms' => '800',
        'monitor_disk_threshold_percent' => '85',
        'monitor_health_endpoint_enabled' => '0',
        'monitor_health_endpoint_path' => '/health',
        'security_email_notifications_enabled' => '0',
        'security_alert_bruteforce_threshold' => '15',
        'security_alert_antispam_threshold' => '10',
        'security_alert_firewall_threshold' => '10',
        'security_alert_window_minutes' => '60',
        'security_alert_cooldown_minutes' => '180',
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
            'runtime' => $this->getRuntimeTelemetrySafe(),
            'error_reports' => ErrorReportService::getInstance()->getRecentReports(15),
            'monitoring' => $this->getMonitoringOverview(),
            'cron' => $this->getCronData(),
            'disk' => $this->getDiskUsageData(),
            'scheduled_tasks' => $this->getScheduledTasksData(),
            'health' => $this->getHealthChecksData(),
            'email_alerts' => $this->getMonitoringSettings(),
            'security_alerts' => SecurityAlertService::getInstance()->getAdminSummary(),
        ];
    }

    public function getInfoData(): array
    {
        return [
            'system' => $this->getSystemInfoSafe(),
            'database' => $this->getDatabaseStatusSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'directories' => $this->getDirectorySizesSafe(),
            'statistics' => $this->getStatisticsSafe(),
            'security' => $this->getSecurityStatusSafe(),
        ];
    }

    public function getSectionData(string $section): array
    {
        return match ($section) {
            'info' => $this->getInfoData(),
            'diagnose' => $this->getDiagnosticsData(),
            'assets' => $this->getAssetsData(),
            'logs' => $this->getLogsData($_GET['log_file'] ?? null),
            'response-time' => [
                'monitoring' => [
                    'response_time' => $this->measureResponseTime(SITE_URL),
                ],
                'email_alerts' => $this->getMonitoringSettings(),
            ],
            'disk' => [
                'disk' => $this->getDiskUsageData(),
            ],
            'scheduled-tasks' => [
                'scheduled_tasks' => $this->getScheduledTasksData(),
            ],
            'health-check' => [
                'health' => $this->getHealthChecksData(),
            ],
            'email-alerts' => [
                'email_alerts' => $this->getMonitoringSettings(),
                'security_alerts' => SecurityAlertService::getInstance()->getAdminSummary(),
            ],
            'cron' => [
                'cron' => $this->getCronData(),
            ],
            default => $this->getInfoData(),
        };
    }

    public function getDiagnosticsData(): array
    {
        return [
            'database' => $this->getDatabaseStatusSafe(),
            'tables' => $this->getTablesSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'runtime' => $this->getRuntimeTelemetrySafe(),
            'error_reports' => ErrorReportService::getInstance()->getRecentReports(15),
        ];
    }

    public function getAssetsData(): array
    {
        return [
            'directories' => $this->getDirectorySizesSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'vendor_registry' => $this->getVendorRegistryDiagnosticsSafe(),
        ];
    }

    public function getLogsData(mixed $selectedFile = null): array
    {
        $logDirectory = $this->service->getConfiguredLogDirectory();
        $logFiles = $this->service->getCmsLogFiles();
        $selectedFilename = $this->normalizeLogFilename($selectedFile);
        $selectedFileInfo = null;
        $operationalAuditEntries = $this->getOperationalAuditEntries();

        if ($selectedFilename === '' && $logFiles !== []) {
            $selectedFilename = (string) ($logFiles[0]['filename'] ?? '');
        }

        foreach ($logFiles as $fileInfo) {
            if ((string) ($fileInfo['filename'] ?? '') === $selectedFilename) {
                $selectedFileInfo = $fileInfo;
                break;
            }
        }

        return [
            'log_directory' => $logDirectory,
            'log_directory_exists' => is_dir($logDirectory),
            'log_directory_writable' => is_dir($logDirectory) && is_writable($logDirectory),
            'error_log_file' => $this->service->getConfiguredErrorLogFile(),
            'error_log_exists' => file_exists($this->service->getConfiguredErrorLogFile()),
            'error_log_entries' => $this->service->getErrorLogs(120),
            'files' => $logFiles,
            'selected_file' => $selectedFilename,
            'selected_file_info' => $selectedFileInfo,
            'selected_entries' => $selectedFilename !== '' ? $this->service->getCmsLogEntries($selectedFilename, 300) : [],
            'documentation_entries' => $this->service->getRecentLogEntriesByChannel('admin.documentation', 40),
            'operational_audit_entries' => $operationalAuditEntries,
            'operational_audit_summary' => $this->summarizeOperationalAuditEntries($operationalAuditEntries),
            'update_history_entries' => $this->getUpdateHistoryEntries(),
        ];
    }

    public function handleAction(string $section, string $action, array $post): array
    {
        return match ($action) {
            'clear_cache' => $this->clearCache(),
            'optimize_db' => $this->optimizeDatabase(),
            'export_diagnostic_report' => $this->exportDiagnosticReport(),
            'clear_logs' => $this->clearLogs(),
            'clear_cms_log' => $this->clearCmsLog($post),
            'clear_all_cms_logs' => $this->clearAllCmsLogs(),
            'create_tables' => $this->createMissingTables(),
            'repair_tables' => $this->repairTables(),
            'save_monitoring_alerts' => $this->saveMonitoringSettings($post),
            'send_monitoring_test_email' => $this->sendMonitoringTestEmail($post),
            'run_cron_direct' => $this->runCronDirect($post),
            'run_cron_loopback' => $this->runCronLoopback($post),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };
    }

    public function handleCronRunnerRequest(array $post): array
    {
        $action = trim((string) ($post['action'] ?? 'run_cron_direct'));

        return match ($action) {
            'run_cron_direct' => $this->runCronDirect($post, true),
            'run_cron_loopback' => $this->runCronLoopback($post, true),
            default => ['success' => false, 'http_status' => 400, 'error' => 'Unbekannte Cron-Runner-Aktion.'],
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
            return $this->buildActionFailureResponse(
                'system.cache.clear_failed',
                'Cache, Sessions und Login-Versuche konnten nicht bereinigt werden.',
                $e,
                'system'
            );
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
            return $this->buildActionFailureResponse(
                'system.database.optimize_failed',
                'Die Datenbankoptimierung konnte nicht abgeschlossen werden.',
                $e,
                'database'
            );
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
            return $this->buildActionFailureResponse(
                'system.logs.clear_failed',
                'Die Fehlerlogs konnten nicht gelöscht werden.',
                $e,
                'log'
            );
        }
    }

    private function clearCmsLog(array $post): array
    {
        $filename = $this->normalizeLogFilename($post['log_file'] ?? null);
        if ($filename === '') {
            return ['success' => false, 'error' => 'Keine gültige Logdatei ausgewählt.'];
        }

        try {
            if (!$this->service->clearCmsLogFile($filename)) {
                return ['success' => false, 'error' => 'Die Logdatei konnte nicht gelöscht werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.logs.clear_file',
                'Einzelne CMS-Logdatei gelöscht',
                'log',
                null,
                ['file' => $filename],
                'warning'
            );

            return ['success' => true, 'message' => 'Logdatei gelöscht: ' . $filename];
        } catch (\Throwable $e) {
            return $this->buildActionFailureResponse(
                'system.logs.clear_file_failed',
                'Die Logdatei konnte nicht gelöscht werden.',
                $e,
                'log'
            );
        }
    }

    private function clearAllCmsLogs(): array
    {
        try {
            $deleted = $this->service->clearAllCmsLogFiles();
            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.logs.clear_all',
                'Alle CMS-Logdateien gelöscht',
                'log',
                null,
                ['deleted' => $deleted],
                'warning'
            );

            return ['success' => true, 'message' => $deleted . ' Logdatei(en) gelöscht.'];
        } catch (\Throwable $e) {
            return $this->buildActionFailureResponse(
                'system.logs.clear_all_failed',
                'Die CMS-Logdateien konnten nicht vollständig gelöscht werden.',
                $e,
                'log'
            );
        }
    }

    private function exportDiagnosticReport(): array
    {
        if (!extension_loaded('zip')) {
            return ['success' => false, 'error' => 'Die ZIP-Erweiterung ist auf diesem System nicht verfügbar.'];
        }

        $tempFile = '';

        try {
            $tempFile = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
                . '365cms-diagnostic-report-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
            $report = $this->buildDiagnosticReportPayload();
            $zip = new \ZipArchive();
            $openResult = $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new \RuntimeException('Diagnose-Archiv konnte nicht erstellt werden.');
            }

            $this->addDiagnosticReportTextFile($zip, 'README.txt', $this->buildDiagnosticReportReadme($report));
            $this->addDiagnosticReportJsonFile($zip, 'manifest.json', $report['manifest'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'system-info.json', $report['system_info'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'health-check.json', $report['health_check'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'asset-status.json', $report['asset_status'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'cron-status.json', $report['cron_status'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'scheduled-tasks.json', $report['scheduled_tasks'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'logs/error-log.json', $report['logs']['error_log'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'logs/cms-log-summary.json', $report['logs']['cms_log_summary'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'logs/recent-cms-logs.json', $report['logs']['recent_cms_logs'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'logs/operational-audit.json', $report['logs']['operational_audit'] ?? []);
            $this->addDiagnosticReportJsonFile($zip, 'logs/update-history.json', $report['logs']['update_history'] ?? []);
            if (!$zip->close()) {
                throw new \RuntimeException('Diagnose-Archiv konnte nicht finalisiert werden.');
            }

            $fileSize = @filesize($tempFile);
            if (!is_int($fileSize) || $fileSize <= 0) {
                throw new \RuntimeException('Diagnose-Archiv ist leer oder nicht lesbar.');
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.diagnostic_report.exported',
                'Diagnosebericht als ZIP exportiert.',
                'diagnose',
                null,
                [
                    'format' => 'zip',
                    'size_bytes' => $fileSize,
                    'cms_log_files' => (int) ($report['manifest']['cms_log_file_count'] ?? 0),
                ],
                'warning'
            );

            $this->sendDiagnosticReportDownload($tempFile, $this->buildDiagnosticReportFilename());
        } catch (\Throwable $e) {
            if (is_file($tempFile)) {
                @unlink($tempFile);
            }

            return $this->buildActionFailureResponse(
                'system.diagnostic_report.export_failed',
                'Der Diagnosebericht konnte nicht erstellt werden.',
                $e,
                'diagnose'
            );
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
            return $this->buildActionFailureResponse(
                'system.schema.create_missing_failed',
                'Fehlende Tabellen oder Migrationen konnten nicht erstellt werden.',
                $e,
                'database'
            );
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
            return $this->buildActionFailureResponse(
                'system.database.repair_failed',
                'Die Tabellenprüfung oder Reparatur konnte nicht abgeschlossen werden.',
                $e,
                'database'
            );
        }
    }

    private function getSystemInfoSafe(): array
    {
        try {
            return $this->service->getSystemInfo();
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.info.load_failed', $e, 'system');

            return ['error' => 'Systeminformationen konnten derzeit nicht geladen werden.'];
        }
    }

    private function getDatabaseStatusSafe(): array
    {
        try {
            return $this->service->getDatabaseStatus();
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.database.status_failed', $e, 'database');

            return ['error' => 'Der Datenbankstatus konnte derzeit nicht geladen werden.'];
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

    private function getRuntimeTelemetrySafe(): array
    {
        try {
            return $this->service->getRuntimeTelemetry();
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.runtime.telemetry_failed', $e, 'system');

            return [
                'enabled' => false,
                'message' => 'Laufzeit-Telemetrie konnte derzeit nicht geladen werden.',
            ];
        }
    }

    private function getVendorRegistryDiagnosticsSafe(): array
    {
        try {
            return VendorRegistry::instance()->getDiagnostics();
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.vendor_registry.diagnostics_failed', $e, 'system');

            return [
                'error' => 'Vendor-Diagnosedaten konnten derzeit nicht geladen werden.',
                'autoload' => ['loaded' => false, 'active_path' => null, 'candidates' => []],
                'packages' => [],
                'bundles' => [],
                'platform' => [],
                'summary' => [
                    'managed_total' => 0,
                    'managed_available' => 0,
                    'managed_loaded' => 0,
                    'bundle_total' => 0,
                    'bundle_available' => 0,
                    'bundle_ready' => 0,
                    'platform_warning_count' => 0,
                    'autoload_candidate_count' => 0,
                ],
            ];
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
        $settings['monitor_health_endpoint_path'] = $this->normalizeHealthEndpointPath($post['monitor_health_endpoint_path'] ?? $settings['monitor_health_endpoint_path']);
        $settings['security_email_notifications_enabled'] = !empty($post['security_email_notifications_enabled']) ? '1' : '0';
        $settings['security_alert_bruteforce_threshold'] = (string)max(1, min(10000, (int)($post['security_alert_bruteforce_threshold'] ?? $settings['security_alert_bruteforce_threshold'])));
        $settings['security_alert_antispam_threshold'] = (string)max(1, min(10000, (int)($post['security_alert_antispam_threshold'] ?? $settings['security_alert_antispam_threshold'])));
        $settings['security_alert_firewall_threshold'] = (string)max(1, min(10000, (int)($post['security_alert_firewall_threshold'] ?? $settings['security_alert_firewall_threshold'])));
        $settings['security_alert_window_minutes'] = (string)max(5, min(1440, (int)($post['security_alert_window_minutes'] ?? $settings['security_alert_window_minutes'])));
        $settings['security_alert_cooldown_minutes'] = (string)max(15, min(10080, (int)($post['security_alert_cooldown_minutes'] ?? $settings['security_alert_cooldown_minutes'])));

        if ($settings['monitor_alert_email'] !== '' && filter_var($settings['monitor_alert_email'], FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'Bitte eine gültige Empfänger-E-Mail-Adresse für Alerts hinterlegen.'];
        }

        if (($settings['monitor_email_notifications_enabled'] === '1' || $settings['security_email_notifications_enabled'] === '1')
            && $settings['monitor_alert_email'] === '') {
            return ['success' => false, 'error' => 'Für aktivierte Alert-E-Mails wird eine Empfängeradresse benötigt.'];
        }

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
            return $this->buildActionFailureResponse(
                'system.monitoring.save_failed',
                'Monitoring-Einstellungen konnten nicht gespeichert werden. Bitte Logs prüfen.',
                $e,
                'monitoring'
            );
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

    private function getMonitoringOverview(): array
    {
        return [
            'response_time' => $this->measureResponseTime(SITE_URL),
            'cron_hooks' => count($this->getCronData()['hooks'] ?? []),
            'disk' => $this->getDiskUsageData(),
        ];
    }

    /** @return array<string, mixed> */
    private function buildDiagnosticReportPayload(): array
    {
        $systemInfo = [
            'system' => $this->getSystemInfoSafe(),
            'database' => $this->getDatabaseStatusSafe(),
            'permissions' => $this->getPermissionsSafe(),
            'directories' => $this->getDirectorySizesSafe(),
            'statistics' => $this->getStatisticsSafe(),
            'security' => $this->getSecurityStatusSafe(),
            'runtime' => $this->getRuntimeTelemetrySafe(),
        ];
        $healthCheck = $this->getHealthChecksData();
        $assetStatus = $this->getAssetsData();
        $cronStatus = $this->getCronData();
        $scheduledTasks = $this->getScheduledTasksData();
        $logs = $this->buildDiagnosticReportLogPayload();

        $report = [
            'manifest' => [
                'generated_at' => date('c'),
                'cms_version' => defined('CMS_VERSION') ? (string) CMS_VERSION : (defined('CMS\\Version::CURRENT') ? (string) \CMS\Version::CURRENT : 'unknown'),
                'report_type' => 'diagnostic-export',
                'redaction_notice' => 'Sensible Werte wie Token, Passwörter, Secrets, Autorisierungsdaten und Credentials wurden serverseitig redigiert.',
                'cms_log_file_count' => count((array) ($logs['cms_log_summary']['files'] ?? [])),
            ],
            'system_info' => $systemInfo,
            'health_check' => $healthCheck,
            'asset_status' => $assetStatus,
            'cron_status' => $cronStatus,
            'scheduled_tasks' => $scheduledTasks,
            'logs' => $logs,
        ];

        return $this->redactDiagnosticExportData($report);
    }

    /** @return array<string, mixed> */
    private function buildDiagnosticReportLogPayload(): array
    {
        $logsData = $this->getLogsData(null);
        $files = array_slice((array) ($logsData['files'] ?? []), 0, self::DIAGNOSTIC_REPORT_CMS_LOG_LIMIT);
        $recentCmsLogs = [];

        foreach ($files as $fileInfo) {
            if (!is_array($fileInfo)) {
                continue;
            }

            $filename = (string) ($fileInfo['filename'] ?? '');
            if ($filename === '') {
                continue;
            }

            $recentCmsLogs[] = [
                'file' => $filename,
                'channel' => (string) ($fileInfo['channel'] ?? ''),
                'modified_at' => (string) ($fileInfo['modified_at'] ?? ''),
                'entries' => $this->service->getCmsLogEntries($filename, self::DIAGNOSTIC_REPORT_LOG_ENTRY_LIMIT),
            ];
        }

        return [
            'error_log' => [
                'file_exists' => !empty($logsData['error_log_exists']),
                'entries' => array_slice((array) ($logsData['error_log_entries'] ?? []), 0, self::DIAGNOSTIC_REPORT_LOG_ENTRY_LIMIT),
            ],
            'cms_log_summary' => [
                'directory_exists' => !empty($logsData['log_directory_exists']),
                'directory_writable' => !empty($logsData['log_directory_writable']),
                'files' => $files,
            ],
            'recent_cms_logs' => $recentCmsLogs,
            'operational_audit' => array_slice((array) ($logsData['operational_audit_entries'] ?? []), 0, self::OPERATIONAL_AUDIT_LIMIT),
            'update_history' => array_slice((array) ($logsData['update_history_entries'] ?? []), 0, self::UPDATE_HISTORY_LIMIT),
        ];
    }

    private function getCronData(): array
    {
        $scanRoots = [
            ABSPATH,
            dirname(ABSPATH) . '-PLUGINS',
        ];
        $cronFilePath = ABSPATH . 'cron.php';
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

        $queueConfig = MailQueueService::getInstance()->getConfiguration();
        $cronToken = (string) ($queueConfig['cron_token'] ?? '');
        $cronWebPath = '/cron.php';
        $cronBaseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') . $cronWebPath : '';
        $cronUrl = defined('SITE_URL')
            ? rtrim((string) SITE_URL, '/') . $cronWebPath . '?task=all&quiet=1&token=' . rawurlencode($cronToken)
            : '';
        $mailQueueUrl = defined('SITE_URL')
            ? rtrim((string) SITE_URL, '/') . $cronWebPath . '?task=mail-queue&quiet=1&token=' . rawurlencode($cronToken)
            : '';
        $defaultCliCommand = 'php ' . escapeshellarg($cronFilePath) . ' --task=all --quiet';
        $mailQueueCliCommand = 'php ' . escapeshellarg($cronFilePath) . ' --task=mail-queue --limit=' . (int) ($queueConfig['batch_size'] ?? 10) . ' --quiet';
        $curlCommand = $cronBaseUrl !== ''
            ? 'curl -fsS ' . escapeshellarg($cronBaseUrl . '?task=all&format=json&token=' . rawurlencode($cronToken))
            : '';
        $powershellCommand = $cronBaseUrl !== ''
            ? 'Invoke-WebRequest -Uri ' . escapeshellarg($cronBaseUrl . '?task=all&format=json&token=' . rawurlencode($cronToken)) . ' -UseBasicParsing'
            : '';

        return [
            'cron_file_exists' => file_exists($cronFilePath),
            'cron_file_path' => $this->normalizeDisplayedPath($cronFilePath),
            'hooks' => $mappedHooks,
            'hook_count' => count($mappedHooks),
            'commands' => [
                'cli_all' => $defaultCliCommand,
                'cli_mail_queue' => $mailQueueCliCommand,
                'web_all' => $cronUrl,
                'web_mail_queue' => $mailQueueUrl,
                'curl_all' => $curlCommand,
                'powershell_all' => $powershellCommand,
            ],
            'mail_queue' => [
                'batch_size' => (int) ($queueConfig['batch_size'] ?? 10),
                'enabled' => !empty($queueConfig['enabled']),
            ],
            'runner' => [
                'tasks' => ['all', 'mail-queue', 'hourly'],
                'default_task' => 'all',
                'default_limit' => (int) ($queueConfig['batch_size'] ?? 10),
                'loopback_url' => $cronBaseUrl,
            ],
        ];
    }

    private function runCronDirect(array $post, bool $forJson = false): array
    {
        $request = $this->normalizeCronRequest($post);
        $result = CronRunnerService::getInstance()->run([
            'task' => $request['task'],
            'limit' => $request['limit'],
            'force' => $request['force'],
            'mode' => $forJson ? 'admin-ajax' : 'admin',
            'source' => $forJson ? 'admin-ajax-direct' : 'admin-system-direct',
        ]);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.cron.run_direct',
            !empty($result['success']) ? 'Cron direkt im CMS ausgeführt' : 'Direkter CMS-Cron-Lauf fehlgeschlagen',
            'system',
            null,
            [
                'task' => $request['task'],
                'limit' => $request['limit'],
                'force' => $request['force'],
                'mode' => $result['mode'] ?? 'admin',
                'success' => !empty($result['success']),
            ],
            !empty($result['success']) ? 'warning' : 'error'
        );

        return $this->buildCronActionResponse(
            $result,
            'Cron wurde direkt im Core ausgeführt.',
            'Direkter Cron-Lauf im Core fehlgeschlagen.',
            $forJson,
            'direct'
        );
    }

    private function runCronLoopback(array $post, bool $forJson = false): array
    {
        $request = $this->normalizeCronRequest($post);
        $queueConfig = MailQueueService::getInstance()->getConfiguration();
        $cronToken = trim((string) ($queueConfig['cron_token'] ?? ''));
        if ($cronToken === '') {
            return $this->buildCronActionResponse(
                ['success' => false, 'error' => 'Cron-Token fehlt in der Queue-Konfiguration.'],
                'Loopback-Cron erfolgreich ausgeführt.',
                'Loopback-Cron konnte nicht gestartet werden.',
                $forJson,
                'loopback',
                500
            );
        }

        $query = [
            'task' => $request['task'],
            'format' => 'json',
            'token' => $cronToken,
        ];
        if ($request['limit'] !== null) {
            $query['limit'] = $request['limit'];
        }
        if ($request['force']) {
            $query['force'] = 1;
        }

        $loopbackUrl = rtrim((string) SITE_URL, '/') . '/cron.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $response = HttpClient::getInstance()->get($loopbackUrl, [
            'userAgent' => '365CMS-System-CronLoopback/1.0',
            'timeout' => 30,
            'connectTimeout' => 5,
            'allowPrivateHosts' => true,
            'allowUnresolvedHosts' => true,
            'allowedContentTypes' => ['application/json'],
        ]);

        if (!$response['success']) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SYSTEM,
                'system.cron.run_loopback_failed',
                'Cron-Loopback im Systembereich fehlgeschlagen',
                'system',
                null,
                [
                    'task' => $request['task'],
                    'url' => $this->sanitizeAuditString($loopbackUrl),
                    'error' => $this->sanitizeAuditString((string) ($response['error'] ?? 'HTTP-Fehler')),
                ],
                'error'
            );

            return $this->buildCronActionResponse(
                [
                    'success' => false,
                    'error' => (string) ($response['error'] ?? 'Loopback-Anfrage fehlgeschlagen.'),
                    'http_status' => (int) ($response['status'] ?? 500),
                    'response' => $response,
                ],
                'Loopback-Cron erfolgreich ausgeführt.',
                'Loopback-Cron konnte nicht gestartet werden.',
                $forJson,
                'loopback',
                (int) ($response['status'] ?? 500)
            );
        }

        $payload = json_decode((string) ($response['body'] ?? ''), true);
        if (!is_array($payload)) {
            return $this->buildCronActionResponse(
                [
                    'success' => false,
                    'error' => 'Loopback-Cron lieferte keine gültige JSON-Antwort.',
                    'response' => $response,
                ],
                'Loopback-Cron erfolgreich ausgeführt.',
                'Loopback-Cron lieferte keine gültige Antwort.',
                $forJson,
                'loopback',
                502
            );
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            'system.cron.run_loopback',
            !empty($payload['success']) ? 'Cron per HTTP-Loopback ausgeführt' : 'HTTP-Loopback-Cron fehlgeschlagen',
            'system',
            null,
            [
                'task' => $request['task'],
                'url' => $this->sanitizeAuditString($loopbackUrl),
                'success' => !empty($payload['success']),
            ],
            !empty($payload['success']) ? 'warning' : 'error'
        );

        return $this->buildCronActionResponse(
            $payload,
            'Cron wurde per HTTP-Loopback gegen /cron.php ausgeführt.',
            'HTTP-Loopback gegen /cron.php ist fehlgeschlagen.',
            $forJson,
            'loopback',
            !empty($payload['success']) ? 200 : 500
        );
    }

    /**
     * @param array<string,mixed> $post
     * @return array{task:string,limit:?int,force:bool}
     */
    private function normalizeCronRequest(array $post): array
    {
        $task = trim((string) ($post['cron_task'] ?? 'all'));
        $availableTasks = ['all', 'mail-queue', 'hourly'];
        if (!in_array($task, $availableTasks, true)) {
            $task = 'all';
        }

        $limit = filter_var($post['cron_limit'] ?? null, FILTER_VALIDATE_INT);
        $limit = $limit === false ? null : max(1, min(100, (int) $limit));

        return [
            'task' => $task,
            'limit' => $limit,
            'force' => !empty($post['cron_force']),
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function buildCronActionResponse(array $payload, string $successMessage, string $errorMessage, bool $forJson, string $mechanism, int $httpStatus = 200): array
    {
        $success = !empty($payload['success']);
        $details = [
            'Mechanismus' => $mechanism,
            'Task' => (string) ($payload['task'] ?? 'all'),
            'Modus' => (string) ($payload['mode'] ?? 'admin'),
            'Quelle' => (string) ($payload['source'] ?? 'system'),
        ];

        if (is_array($payload['result'] ?? null)) {
            $details['Ergebnis'] = json_encode($payload['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
        }
        if (!empty($payload['error'])) {
            $details['Fehler'] = (string) $payload['error'];
        }

        $response = [
            'success' => $success,
            'type' => $success ? 'success' : 'danger',
            'message' => $success ? $successMessage : ($payload['error'] ?? $errorMessage),
            'details' => $details,
            'cron_run' => $payload,
        ];

        if ($forJson) {
            $response['http_status'] = $success ? 200 : $httpStatus;
        }

        return $response;
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
        $healthEndpoint = $this->probeHealthEndpoint($settings);
        $cacheWritable = is_writable(ABSPATH . 'cache');
        $uploadsWritable = is_writable(ABSPATH . 'uploads');
        $logsWritable = is_dir($this->service->getConfiguredLogDirectory()) && is_writable($this->service->getConfiguredLogDirectory());

        $checks = [
            ['label' => 'Datenbank', 'passed' => !empty($db['connected']), 'detail' => !empty($db['connected']) ? 'Verbunden' : 'Nicht erreichbar'],
            ['label' => 'Cache-Verzeichnis', 'passed' => $cacheWritable, 'detail' => $cacheWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Uploads-Verzeichnis', 'passed' => $uploadsWritable, 'detail' => $uploadsWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Logs-Verzeichnis', 'passed' => $logsWritable, 'detail' => $logsWritable ? 'Beschreibbar' : 'Nicht beschreibbar'],
            ['label' => 'Response Time', 'passed' => empty($response['error']) && ((int)($response['duration_ms'] ?? 0) <= (int)$settings['monitor_response_threshold_ms']), 'detail' => empty($response['error']) ? ((int)$response['duration_ms']) . ' ms' : (string)$response['error']],
            ['label' => 'Disk-Auslastung', 'passed' => ($disk['used_percent'] ?? 0) < (float)$settings['monitor_disk_threshold_percent'], 'detail' => ($disk['used_percent'] ?? null) !== null ? ((string)$disk['used_percent']) . '%' : 'Unbekannt'],
            ['label' => 'Health-Endpunkt', 'passed' => !empty($healthEndpoint['passed']), 'detail' => (string)($healthEndpoint['detail'] ?? 'Nicht geprüft')],
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
            'endpoint' => $healthEndpoint,
        ];
    }

    /**
     * @param array<string,string> $settings
     * @return array{enabled:bool,passed:bool,path:string,url:string,status:int,duration_ms:int,error:?string,detail:string}
     */
    private function probeHealthEndpoint(array $settings): array
    {
        $enabled = ($settings['monitor_health_endpoint_enabled'] ?? '0') === '1';
        $path = $this->normalizeHealthEndpointPath($settings['monitor_health_endpoint_path'] ?? '/health');
        $url = $this->buildSiteRelativeUrl($path);

        if (!$enabled) {
            return [
                'enabled' => false,
                'passed' => false,
                'path' => $path,
                'url' => $url,
                'status' => 0,
                'duration_ms' => 0,
                'error' => null,
                'detail' => 'Deaktiviert',
            ];
        }

        if ($url === '') {
            return [
                'enabled' => true,
                'passed' => false,
                'path' => $path,
                'url' => '',
                'status' => 0,
                'duration_ms' => 0,
                'error' => 'Ungültiger lokaler Health-Pfad.',
                'detail' => $path . ' · Ungültiger lokaler Health-Pfad',
            ];
        }

        $start = microtime(true);
        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS-HealthMonitor/1.0',
            'timeout' => 5,
            'connectTimeout' => 3,
            'maxBytes' => 128 * 1024,
            'allowPrivateHosts' => true,
            'allowUnresolvedHosts' => true,
            'allowedContentTypes' => ['application/json', 'text/plain', 'text/html'],
        ]);
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $success = !empty($response['success']);
        $status = (int) ($response['status'] ?? 0);
        $error = $success ? null : (string) ($response['error'] ?? 'Health-Endpunkt nicht erreichbar.');
        $detail = $success
            ? $path . ' · HTTP ' . $status . ' · ' . $durationMs . ' ms'
            : $path . ' · ' . ($error !== '' ? $error : 'Health-Endpunkt nicht erreichbar.');

        return [
            'enabled' => true,
            'passed' => $success,
            'path' => $path,
            'url' => $url,
            'status' => $status,
            'duration_ms' => $durationMs,
            'error' => $error,
            'detail' => $detail,
        ];
    }

    private function measureResponseTime(string $url): array
    {
        $start = microtime(true);

        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS-Monitor/1.0',
            'timeout' => 5,
            'connectTimeout' => 3,
            'maxBytes' => 256 * 1024,
            'allowPrivateHosts' => true,
        ]);

        return [
            'url' => $url,
            'duration_ms' => (int)round((microtime(true) - $start) * 1000),
            'status_code' => (int) ($response['status'] ?? 0),
            'error' => ($response['success'] ?? false) === true ? null : (string) ($response['error'] ?? 'Anfrage fehlgeschlagen'),
        ];
    }

    private function normalizeHealthEndpointPath(mixed $value): string
    {
        $path = preg_replace('/[\x00-\x1F\x7F\s]+/u', '', trim((string) $value)) ?? '';
        if ($path === '') {
            return '/health';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $siteHost = strtolower((string) parse_url((string) SITE_URL, PHP_URL_HOST));
            $pathHost = strtolower((string) parse_url($path, PHP_URL_HOST));
            if ($siteHost === '' || $pathHost === '' || $siteHost !== $pathHost) {
                return '/health';
            }

            $parsedPath = (string) parse_url($path, PHP_URL_PATH);
            $parsedQuery = (string) parse_url($path, PHP_URL_QUERY);
            $path = $parsedPath !== '' ? $parsedPath : '/health';
            if ($parsedQuery !== '') {
                $path .= '?' . $parsedQuery;
            }
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        return $path !== '' ? $path : '/health';
    }

    private function buildSiteRelativeUrl(string $path): string
    {
        $baseUrl = rtrim((string) SITE_URL, '/');
        if ($baseUrl === '') {
            return '';
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    private function getOperationalAuditEntries(): array
    {
        try {
            $db = Database::instance();
            $rows = $db->get_results(
                "SELECT created_at, action, description, category, severity, user_id, metadata
                 FROM {$db->getPrefix()}audit_log
                 WHERE category = ? OR action LIKE ?
                 ORDER BY created_at DESC
                 LIMIT " . self::OPERATIONAL_AUDIT_LIMIT,
                [AuditLogger::CAT_SYSTEM, 'performance.%']
            ) ?: [];

            return array_map(fn (mixed $row): array => $this->normalizeOperationalAuditRow($row), $rows);
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.logs.operational_audit_failed', $e, 'log');

            return [];
        }
    }

    /**
     * @param array<int, array<string, scalar|null>> $entries
     * @return array<int, array<string, scalar|null>>
     */
    private function summarizeOperationalAuditEntries(array $entries): array
    {
        $summary = [];

        foreach ($entries as $entry) {
            $group = (string) ($entry['group'] ?? 'system');
            if (!isset($summary[$group])) {
                $summary[$group] = [
                    'group' => $group,
                    'label' => $this->getOperationalAuditGroupLabel($group),
                    'count' => 0,
                    'last_created_at' => '',
                ];
            }

            $summary[$group]['count']++;
            $createdAt = (string) ($entry['created_at'] ?? '');
            if ($createdAt !== '' && strcmp($createdAt, (string) $summary[$group]['last_created_at']) > 0) {
                $summary[$group]['last_created_at'] = $createdAt;
            }
        }

        usort($summary, static function (array $left, array $right): int {
            return strcmp((string) ($right['last_created_at'] ?? ''), (string) ($left['last_created_at'] ?? ''));
        });

        return $summary;
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    private function getUpdateHistoryEntries(): array
    {
        try {
            $history = UpdateService::getInstance()->getUpdateHistory(self::UPDATE_HISTORY_LIMIT);
        } catch (\Throwable $e) {
            $this->logModuleFailure('system.logs.update_history_failed', $e, 'log');

            return [];
        }

        $entries = [];
        foreach ($history as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $userLabel = (string) ($entry['user_label'] ?? $entry['user'] ?? 'System');

            $entries[] = [
                'timestamp' => $this->sanitizeAuditString((string) ($entry['timestamp'] ?? ''), 40),
                'type' => $this->sanitizeAuditString((string) ($entry['type'] ?? 'update'), 40),
                'name' => $this->sanitizeAuditString((string) ($entry['name'] ?? ''), 120),
                'version' => $this->sanitizeAuditString((string) ($entry['version'] ?? ''), 60),
                'user' => $this->sanitizeAuditString($userLabel, 120),
            ];
        }

        return $entries;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeOperationalAuditRow(mixed $row): array
    {
        $data = is_object($row) ? get_object_vars($row) : (is_array($row) ? $row : []);
        $action = $this->sanitizeAuditString((string) ($data['action'] ?? ''), 120);
        $metadataPreview = '';

        if (!empty($data['metadata']) && is_string($data['metadata'])) {
            $decodedMetadata = json_decode($data['metadata'], true);
            if (is_array($decodedMetadata)) {
                $metadataPreview = $this->buildOperationalMetadataPreview($decodedMetadata);
            }

            if ($metadataPreview === '') {
                $metadataPreview = $this->sanitizeAuditString((string) $data['metadata'], 160);
            }
        }

        $details = $this->sanitizeAuditString((string) ($data['description'] ?? ''), 180);
        if ($details === '' && $metadataPreview !== '') {
            $details = $metadataPreview;
        }

        $group = $this->classifyOperationalAuditGroup($action);

        return [
            'created_at' => (string) ($data['created_at'] ?? ''),
            'action' => $action,
            'details' => $details,
            'category' => $this->sanitizeAuditString((string) ($data['category'] ?? ''), 40),
            'severity' => $this->sanitizeAuditString((string) ($data['severity'] ?? 'info'), 20),
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'group' => $group,
            'group_label' => $this->getOperationalAuditGroupLabel($group),
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function buildOperationalMetadataPreview(array $metadata): string
    {
        $parts = [];

        foreach ($metadata as $key => $value) {
            if (count($parts) >= 3) {
                break;
            }

            $normalizedKey = $this->sanitizeAuditString((string) $key, 40);
            if ($normalizedKey === '') {
                continue;
            }

            if (is_bool($value)) {
                $normalizedValue = $value ? 'ja' : 'nein';
            } elseif (is_scalar($value) || $value === null) {
                $normalizedValue = (string) $value;
            } elseif (is_array($value)) {
                $normalizedValue = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            } else {
                continue;
            }

            $normalizedValue = $this->sanitizeAuditString($normalizedValue, 60);
            if ($normalizedValue === '') {
                continue;
            }

            $parts[] = $normalizedKey . ': ' . $normalizedValue;
        }

        return $this->sanitizeAuditString(implode(' · ', $parts), 180);
    }

    private function classifyOperationalAuditGroup(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'backup.') => 'backup',
            str_starts_with($action, 'updates.'), str_starts_with($action, 'update.') => 'update',
            str_starts_with($action, 'performance.') => 'performance',
            str_starts_with($action, 'system.monitoring.') => 'monitoring',
            str_starts_with($action, 'system.cron.') => 'cron',
            str_starts_with($action, 'system.logs.') => 'logs',
            default => 'system',
        };
    }

    private function getOperationalAuditGroupLabel(string $group): string
    {
        return match ($group) {
            'backup' => 'Backups',
            'update' => 'Updates',
            'performance' => 'Performance',
            'monitoring' => 'Monitoring',
            'cron' => 'Cron & Queue',
            'logs' => 'Logs',
            default => 'System',
        };
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

    private function buildActionFailureResponse(string $event, string $message, \Throwable $exception, string $scope): array
    {
        $this->logModuleFailure($event, $exception, $scope);

        return ['success' => false, 'error' => $message];
    }

    private function logModuleFailure(string $event, \Throwable $exception, string $scope): void
    {
        AuditLogger::instance()->log(
            AuditLogger::CAT_SYSTEM,
            $event,
            'SystemInfoModule-Fehler',
            $scope,
            null,
            ['exception' => $this->sanitizeAuditString($exception->getMessage())],
            'error'
        );
    }

    private function sanitizeAuditString(string $value, int $maxLength = self::MAX_AUDIT_STRING_LENGTH): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function normalizeLogFilename(mixed $value): string
    {
        $filename = trim((string) $value);

        return preg_match('/^[A-Za-z0-9._-]+\.log$/', $filename) === 1 ? $filename : '';
    }

    /** @param array<string, mixed> $payload */
    private function addDiagnosticReportJsonFile(\ZipArchive $zip, string $path, array $payload): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new \RuntimeException('Diagnosebericht konnte nicht serialisiert werden.');
        }

        if (!$zip->addFromString($path, $json)) {
            throw new \RuntimeException('Diagnosebericht-Datei konnte nicht dem ZIP hinzugefügt werden: ' . $path);
        }
    }

    private function addDiagnosticReportTextFile(\ZipArchive $zip, string $path, string $content): void
    {
        if (!$zip->addFromString($path, $content)) {
            throw new \RuntimeException('Diagnosebericht-Textdatei konnte nicht dem ZIP hinzugefügt werden: ' . $path);
        }
    }

    /** @param array<string, mixed> $report */
    private function buildDiagnosticReportReadme(array $report): string
    {
        return implode("\n", [
            '365CMS Diagnosebericht',
            '====================',
            '',
            'Erstellt: ' . (string) ($report['manifest']['generated_at'] ?? date('c')),
            'CMS-Version: ' . (string) ($report['manifest']['cms_version'] ?? 'unknown'),
            '',
            'Enthaltene Bereiche:',
            '- Systeminformationen',
            '- Health-Check',
            '- Asset-Status',
            '- Cron-Status',
            '- Geplante Tasks',
            '- Letzte Logs inkl. Error-Log, CMS-Log-Summary, Betriebs-Audit und Update-Historie',
            '',
            'Redaction-Hinweis:',
            (string) ($report['manifest']['redaction_notice'] ?? 'Sensible Werte wurden serverseitig redigiert.'),
            '',
            'Sicherheitsvertrag:',
            '- Export wird ausschließlich per POST/CSRF erzeugt.',
            '- Keine Tokens in Download-URLs.',
            '- Fehlende Datenquellen fallen fail-soft auf leere Abschnitte zurück.',
        ]) . "\n";
    }

    private function buildDiagnosticReportFilename(): string
    {
        return '365cms-diagnosebericht-' . date('Y-m-d_H-i-s') . '.zip';
    }

    private function sendDiagnosticReportDownload(string $path, string $filename): never
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Diagnosebericht konnte nicht zum Download geöffnet werden.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        while (!feof($handle)) {
            echo fread($handle, 8192);
        }
        fclose($handle);

        @unlink($path);
        exit;
    }

    private function redactDiagnosticExportData(mixed $value, string $key = ''): mixed
    {
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $childKey => $childValue) {
                $normalizedKey = is_string($childKey) ? $childKey : (string) $childKey;
                if ($this->isSensitiveDiagnosticExportKey($normalizedKey)) {
                    $redacted[$childKey] = '[redacted]';
                    continue;
                }

                $redacted[$childKey] = $this->redactDiagnosticExportData($childValue, $normalizedKey);
            }

            return $redacted;
        }

        if (is_string($value)) {
            return $this->redactDiagnosticExportString($value, $key);
        }

        return $value;
    }

    private function isSensitiveDiagnosticExportKey(string $key): bool
    {
        return preg_match('/(^|_|-)(token|password|pass|secret|api_key|private_key|authorization|credential|smtp_pass|mail_password|db_pass)($|_|-)/i', $key) === 1;
    }

    private function redactDiagnosticExportString(string $value, string $key = ''): string
    {
        if ($this->isSensitiveDiagnosticExportKey($key)) {
            return '[redacted]';
        }

        $value = preg_replace('/([?&](?:token|password|pass|secret|api_key|signature|sig|authorization)=)[^&\s]+/iu', '$1[redacted]', $value) ?? $value;
        $value = preg_replace('/((?:token|password|pass|secret|api_key|authorization|smtp_pass|mail_password|db_pass)"?\s*[:=]\s*")([^"]*)(")/iu', '$1[redacted]$3', $value) ?? $value;
        $value = preg_replace('/((?:token|password|pass|secret|api_key|authorization|smtp_pass|mail_password|db_pass)\s*[:=]\s*)([^\s,;]+)/iu', '$1[redacted]', $value) ?? $value;
        $value = preg_replace('/(Authorization:\s*)(.+)$/imu', '$1[redacted]', $value) ?? $value;
        $value = preg_replace('/(Bearer\s+)[A-Za-z0-9._\-~=+\/]+/u', '$1[redacted]', $value) ?? $value;

        return $value;
    }
}
