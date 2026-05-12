<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\AuditLogger;
use CMS\Database;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class SecurityAlertService
{
    private const CONFIG_KEYS = [
        'monitor_email_notifications_enabled',
        'monitor_alert_email',
        'security_email_notifications_enabled',
        'security_alert_bruteforce_threshold',
        'security_alert_antispam_threshold',
        'security_alert_firewall_threshold',
        'security_alert_window_minutes',
        'security_alert_cooldown_minutes',
    ];

    private const SETTINGS_GROUP = 'security_alerts';
    private const META_LAST_RUN = 'last_run';
    private const META_LAST_SENT = 'last_sent';

    private const DEFAULTS = [
        'monitor_email_notifications_enabled' => '0',
        'monitor_alert_email' => '',
        'security_email_notifications_enabled' => '0',
        'security_alert_bruteforce_threshold' => '15',
        'security_alert_antispam_threshold' => '10',
        'security_alert_firewall_threshold' => '10',
        'security_alert_window_minutes' => '60',
        'security_alert_cooldown_minutes' => '180',
    ];

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
        $this->logger = Logger::instance()->withChannel('security.alerts');
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminSummary(): array
    {
        $config = $this->getConfiguration();
        $snapshot = $this->collectSnapshot((int) ($config['window_minutes'] ?? 60));

        return [
            'enabled' => !empty($config['enabled']),
            'pipeline_enabled' => !empty($config['pipeline_enabled']),
            'recipient_configured' => !empty($config['recipient']),
            'window_minutes' => (int) ($config['window_minutes'] ?? 60),
            'cooldown_minutes' => (int) ($config['cooldown_minutes'] ?? 180),
            'last_run' => $this->getLastRunMeta(),
            'last_sent' => $this->getLastSentMeta(),
            'snapshot' => $snapshot,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runScheduledScan(): array
    {
        try {
            $config = $this->getConfiguration();
            $summary = [
                'success' => true,
                'enabled' => !empty($config['enabled']),
                'pipeline_enabled' => !empty($config['pipeline_enabled']),
                'recipient_configured' => !empty($config['recipient']),
                'alerts_sent' => [],
                'alerts_suppressed' => [],
                'snapshot' => [],
                'message' => 'Security-Alerts sind deaktiviert.',
            ];

            if (empty($config['enabled'])) {
                $this->persistLastRunMeta([
                    'executed_at' => date('Y-m-d H:i:s'),
                    'enabled' => false,
                    'reason' => empty($config['pipeline_enabled']) ? 'pipeline_disabled' : 'disabled',
                ]);

                return $summary;
            }

            $snapshot = $this->collectSnapshot((int) $config['window_minutes']);
            $summary['snapshot'] = $snapshot;

            [$triggered, $suppressed] = $this->resolveTriggeredAlerts($snapshot, $config);
            $summary['alerts_suppressed'] = array_keys($suppressed);

            if ($triggered === []) {
                $summary['message'] = 'Keine Security-Schwellenwerte überschritten.';
                $this->persistLastRunMeta([
                    'executed_at' => date('Y-m-d H:i:s'),
                    'enabled' => true,
                    'triggered' => [],
                    'suppressed' => array_keys($suppressed),
                    'counts' => $this->extractCounts($snapshot),
                ]);

                return $summary;
            }

            $mailResult = $this->dispatchAlertMail($config, $snapshot, $triggered, $suppressed);
            $summary['mail_result'] = $mailResult;
            $summary['alerts_sent'] = !empty($mailResult['success']) ? array_keys($triggered) : [];
            $summary['success'] = !empty($mailResult['success']);
            $summary['message'] = !empty($mailResult['success'])
                ? 'Security-Alarm per E-Mail ausgelöst.'
                : (string) ($mailResult['error'] ?? 'Security-Alarm konnte nicht versendet werden.');

            $this->persistLastRunMeta([
                'executed_at' => date('Y-m-d H:i:s'),
                'enabled' => true,
                'triggered' => array_keys($triggered),
                'suppressed' => array_keys($suppressed),
                'counts' => $this->extractCounts($snapshot),
                'mail_success' => !empty($mailResult['success']),
            ]);

            if (!empty($mailResult['success'])) {
                $this->persistLastSentMeta($triggered, $snapshot, $mailResult);
                AuditLogger::instance()->log(
                    AuditLogger::CAT_SECURITY,
                    'security.alerts.sent',
                    'Security-Alarm per Mail ausgelöst',
                    'security',
                    null,
                    [
                        'types' => array_keys($triggered),
                        'window_minutes' => (int) $config['window_minutes'],
                        'queued' => !empty($mailResult['queued']),
                    ],
                    'warning'
                );
            } else {
                AuditLogger::instance()->log(
                    AuditLogger::CAT_SECURITY,
                    'security.alerts.send_failed',
                    'Security-Alarm konnte nicht versendet werden',
                    'security',
                    null,
                    [
                        'types' => array_keys($triggered),
                        'window_minutes' => (int) $config['window_minutes'],
                    ],
                    'error'
                );
            }

            return $summary;
        } catch (\Throwable $e) {
            $this->logger->error('Security-Alert-Scan ist fail-soft abgefangen worden.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->persistLastRunMeta([
                'executed_at' => date('Y-m-d H:i:s'),
                'enabled' => false,
                'reason' => 'exception',
            ]);

            return [
                'success' => false,
                'enabled' => false,
                'pipeline_enabled' => false,
                'recipient_configured' => false,
                'alerts_sent' => [],
                'alerts_suppressed' => [],
                'snapshot' => [],
                'message' => 'Security-Alert-Scan wurde fail-soft abgefangen.',
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfiguration(): array
    {
        $settings = $this->loadRawSettings();
        $pipelineEnabled = ($settings['monitor_email_notifications_enabled'] ?? '0') === '1';
        $securityEnabled = ($settings['security_email_notifications_enabled'] ?? '0') === '1';
        $recipient = trim((string) ($settings['monitor_alert_email'] ?? ''));

        return [
            'pipeline_enabled' => $pipelineEnabled,
            'enabled' => $pipelineEnabled && $securityEnabled && filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false,
            'recipient' => $recipient,
            'bruteforce_threshold' => max(1, min(10000, (int) ($settings['security_alert_bruteforce_threshold'] ?? 15))),
            'antispam_threshold' => max(1, min(10000, (int) ($settings['security_alert_antispam_threshold'] ?? 10))),
            'firewall_threshold' => max(1, min(10000, (int) ($settings['security_alert_firewall_threshold'] ?? 10))),
            'window_minutes' => max(5, min(1440, (int) ($settings['security_alert_window_minutes'] ?? 60))),
            'cooldown_minutes' => max(15, min(10080, (int) ($settings['security_alert_cooldown_minutes'] ?? 180))),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function loadRawSettings(): array
    {
        $settings = self::DEFAULTS;
        $placeholders = implode(',', array_fill(0, count(self::CONFIG_KEYS), '?'));

        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
                self::CONFIG_KEYS
            ) ?: [];

            foreach ($rows as $row) {
                $optionName = (string) ($row->option_name ?? '');
                if ($optionName !== '' && array_key_exists($optionName, $settings)) {
                    $settings[$optionName] = (string) ($row->option_value ?? '');
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Security-Alert-Konfiguration konnte nicht geladen werden.', [
                'exception' => $e::class,
            ]);
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectSnapshot(int $windowMinutes): array
    {
        return [
            'window_minutes' => $windowMinutes,
            'bruteforce' => $this->collectBruteforceSnapshot($windowMinutes),
            'antispam' => $this->collectAntispamSnapshot($windowMinutes),
            'firewall' => $this->collectFirewallSnapshot($windowMinutes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectBruteforceSnapshot(int $windowMinutes): array
    {
        $since = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
        $count = 0;
        $samples = [];

        try {
            $count = (int) ($this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}login_attempts WHERE action = ? AND attempted_at >= ?",
                ['login', $since]
            ) ?? 0);

            $rows = $this->db->get_results(
                "SELECT ip_address, COUNT(*) AS hits, MAX(attempted_at) AS last_attempt
                 FROM {$this->prefix}login_attempts
                 WHERE action = ? AND attempted_at >= ?
                 GROUP BY ip_address
                 ORDER BY hits DESC, last_attempt DESC
                 LIMIT 5",
                ['login', $since]
            ) ?: [];

            foreach ($rows as $row) {
                $samples[] = [
                    'ip' => $this->maskIpAddress((string) ($row->ip_address ?? '')),
                    'hits' => (int) ($row->hits ?? 0),
                    'last_event' => (string) ($row->last_attempt ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Brute-Force-Snapshot konnte nicht geladen werden.', [
                'exception' => $e::class,
            ]);
        }

        return [
            'count' => $count,
            'samples' => $samples,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectAntispamSnapshot(int $windowMinutes): array
    {
        $since = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
        $count = 0;
        $samples = [];
        $reasons = [];

        try {
            $count = (int) ($this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}security_log WHERE action = ? AND created_at >= ?",
                ['antispam_rejected', $since]
            ) ?? 0);

            $rows = $this->db->get_results(
                "SELECT ip_address, request_uri, extra, created_at
                 FROM {$this->prefix}security_log
                 WHERE action = ? AND created_at >= ?
                 ORDER BY created_at DESC
                 LIMIT 10",
                ['antispam_rejected', $since]
            ) ?: [];

            foreach ($rows as $row) {
                $extra = $this->decodeJsonObject((string) ($row->extra ?? ''));
                $reason = $this->sanitizeReason((string) ($extra['reason'] ?? 'rejected'));
                $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;

                if (count($samples) >= 5) {
                    continue;
                }

                $samples[] = [
                    'ip' => $this->maskIpAddress((string) ($row->ip_address ?? '')),
                    'reason' => $reason,
                    'source' => $this->sanitizeReason((string) ($extra['source'] ?? 'runtime')),
                    'path' => $this->sanitizePath((string) ($row->request_uri ?? '')),
                    'last_event' => (string) ($row->created_at ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('AntiSpam-Snapshot konnte nicht geladen werden.', [
                'exception' => $e::class,
            ]);
        }

        arsort($reasons);

        return [
            'count' => $count,
            'reason_counts' => $reasons,
            'samples' => $samples,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectFirewallSnapshot(int $windowMinutes): array
    {
        $since = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
        $count = 0;
        $samples = [];

        try {
            $count = (int) ($this->db->get_var(
                "SELECT COUNT(*)
                 FROM {$this->prefix}security_log
                 WHERE action IN ('blocked', 'rate_limited')
                   AND created_at >= ?",
                [$since]
            ) ?? 0);

            $rows = $this->db->get_results(
                "SELECT ip_address, action, rule_matched, request_uri, created_at
                 FROM {$this->prefix}security_log
                 WHERE action IN ('blocked', 'rate_limited')
                   AND created_at >= ?
                 ORDER BY created_at DESC
                 LIMIT 5",
                [$since]
            ) ?: [];

            foreach ($rows as $row) {
                $samples[] = [
                    'ip' => $this->maskIpAddress((string) ($row->ip_address ?? '')),
                    'action' => (string) ($row->action ?? ''),
                    'rule' => $this->sanitizeRule((string) ($row->rule_matched ?? '')),
                    'path' => $this->sanitizePath((string) ($row->request_uri ?? '')),
                    'last_event' => (string) ($row->created_at ?? ''),
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Firewall-Snapshot konnte nicht geladen werden.', [
                'exception' => $e::class,
            ]);
        }

        return [
            'count' => $count,
            'samples' => $samples,
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $config
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<string, mixed>>}
     */
    private function resolveTriggeredAlerts(array $snapshot, array $config): array
    {
        $definitions = [
            'bruteforce' => [
                'label' => 'Login-Brute-Force',
                'count' => (int) ($snapshot['bruteforce']['count'] ?? 0),
                'threshold' => (int) ($config['bruteforce_threshold'] ?? 15),
            ],
            'antispam' => [
                'label' => 'AntiSpam-Spitze',
                'count' => (int) ($snapshot['antispam']['count'] ?? 0),
                'threshold' => (int) ($config['antispam_threshold'] ?? 10),
            ],
            'firewall' => [
                'label' => 'Firewall-Blocks',
                'count' => (int) ($snapshot['firewall']['count'] ?? 0),
                'threshold' => (int) ($config['firewall_threshold'] ?? 10),
            ],
        ];

        $triggered = [];
        $suppressed = [];

        foreach ($definitions as $type => $definition) {
            if ((int) $definition['count'] < (int) $definition['threshold']) {
                continue;
            }

            if ($this->isSuppressedByCooldown($type, (int) ($config['cooldown_minutes'] ?? 180))) {
                $suppressed[$type] = $definition;
                continue;
            }

            $triggered[$type] = $definition;
        }

        return [$triggered, $suppressed];
    }

    private function isSuppressedByCooldown(string $type, int $cooldownMinutes): bool
    {
        $lastSent = $this->getLastSentMeta();
        $entry = $lastSent[$type] ?? null;
        if (!is_array($entry)) {
            return false;
        }

        $sentAt = strtotime((string) ($entry['sent_at'] ?? ''));
        if ($sentAt === false) {
            return false;
        }

        return $sentAt > (time() - ($cooldownMinutes * 60));
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $snapshot
     * @param array<string, array<string, mixed>> $triggered
     * @param array<string, array<string, mixed>> $suppressed
     * @return array<string, mixed>
     */
    private function dispatchAlertMail(array $config, array $snapshot, array $triggered, array $suppressed): array
    {
        $recipient = trim((string) ($config['recipient'] ?? ''));
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'Keine gültige Empfängeradresse für Security-Alerts konfiguriert.'];
        }

        $subject = '365CMS Sicherheitsalarm: ' . implode(', ', array_map(
            static fn (array $definition): string => (string) ($definition['label'] ?? 'Alarm'),
            array_values($triggered)
        ));
        $body = $this->buildMailBody($snapshot, $triggered, $suppressed, $config);

        $queue = MailQueueService::getInstance();
        if ($queue->shouldQueue()) {
            $result = $queue->enqueuePlain($recipient, $subject, $body, [], null, 'security-alerts');
            $result['queued'] = !empty($result['success']);
            return $result;
        }

        $result = MailService::getInstance()->sendPlainDetailed($recipient, $subject, $body, [
            'X-365CMS-Alert' => 'security',
        ]);
        $result['queued'] = false;

        return $result;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, array<string, mixed>> $triggered
     * @param array<string, array<string, mixed>> $suppressed
     * @param array<string, mixed> $config
     */
    private function buildMailBody(array $snapshot, array $triggered, array $suppressed, array $config): string
    {
        $lines = [
            '365CMS Sicherheitsalarm',
            '========================',
            '',
            'Zeitpunkt: ' . date('Y-m-d H:i:s'),
            'Website: ' . (defined('SITE_URL') ? (string) SITE_URL : ''),
            'Betrachtungsfenster: ' . (int) ($snapshot['window_minutes'] ?? 60) . ' Minuten',
            'Cooldown pro Alert-Typ: ' . (int) ($config['cooldown_minutes'] ?? 180) . ' Minuten',
            '',
            'Ausgelöste Schwellenwerte:',
        ];

        foreach ($triggered as $type => $definition) {
            $count = (int) ($definition['count'] ?? 0);
            $threshold = (int) ($definition['threshold'] ?? 0);
            $label = (string) ($definition['label'] ?? $type);
            $lines[] = '- ' . $label . ': ' . $count . ' Treffer (Schwelle ' . $threshold . ')';

            foreach ($this->buildDetailLines($type, $snapshot) as $detailLine) {
                $lines[] = '  ' . $detailLine;
            }
        }

        if ($suppressed !== []) {
            $lines[] = '';
            $lines[] = 'Der folgende Typ lag ebenfalls über seiner Schwelle, blieb aber wegen des Cooldowns ohne neue Mail:';
            foreach ($suppressed as $definition) {
                $lines[] = '- ' . (string) ($definition['label'] ?? 'Alert');
            }
        }

        $lines[] = '';
        $lines[] = 'Prüfen Sie bei Bedarf:';
        $lines[] = '- /admin/monitor-email-alerts';
        $lines[] = '- /admin/security-audit';
        $lines[] = '- /admin/firewall';
        $lines[] = '- /admin/antispam';
        $lines[] = '';
        $lines[] = 'Hinweis: Diese Mail nutzt bewusst nur datensparsame, read-only Betriebsdaten ohne Tokens oder Formulardaten.';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function buildDetailLines(string $type, array $snapshot): array
    {
        return match ($type) {
            'bruteforce' => $this->buildBruteforceDetailLines($snapshot['bruteforce'] ?? []),
            'antispam' => $this->buildAntispamDetailLines($snapshot['antispam'] ?? []),
            'firewall' => $this->buildFirewallDetailLines($snapshot['firewall'] ?? []),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function buildBruteforceDetailLines(array $snapshot): array
    {
        $lines = [];
        foreach ((array) ($snapshot['samples'] ?? []) as $sample) {
            if (!is_array($sample)) {
                continue;
            }

            $lines[] = sprintf(
                'Top-IP %s · %d Versuche · zuletzt %s',
                (string) ($sample['ip'] ?? 'unbekannt'),
                (int) ($sample['hits'] ?? 0),
                (string) ($sample['last_event'] ?? 'unbekannt')
            );
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function buildAntispamDetailLines(array $snapshot): array
    {
        $lines = [];
        $reasonCounts = (array) ($snapshot['reason_counts'] ?? []);
        if ($reasonCounts !== []) {
            $reasonPreview = [];
            foreach ($reasonCounts as $reason => $count) {
                $reasonPreview[] = $reason . ': ' . (int) $count;
                if (count($reasonPreview) >= 3) {
                    break;
                }
            }
            $lines[] = 'Gründe · ' . implode(' · ', $reasonPreview);
        }

        foreach ((array) ($snapshot['samples'] ?? []) as $sample) {
            if (!is_array($sample)) {
                continue;
            }

            $lines[] = sprintf(
                'Beispiel %s · %s · %s · %s',
                (string) ($sample['ip'] ?? 'unbekannt'),
                (string) ($sample['reason'] ?? 'rejected'),
                (string) ($sample['source'] ?? 'runtime'),
                (string) ($sample['path'] ?? '/')
            );
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function buildFirewallDetailLines(array $snapshot): array
    {
        $lines = [];
        foreach ((array) ($snapshot['samples'] ?? []) as $sample) {
            if (!is_array($sample)) {
                continue;
            }

            $lines[] = sprintf(
                'Beispiel %s · %s · %s · %s',
                (string) ($sample['ip'] ?? 'unbekannt'),
                (string) ($sample['action'] ?? 'blocked'),
                (string) ($sample['rule'] ?? 'regel'),
                (string) ($sample['path'] ?? '/')
            );
        }

        return $lines;
    }

    /**
     * @param array<string, array<string, mixed>> $triggered
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $mailResult
     */
    private function persistLastSentMeta(array $triggered, array $snapshot, array $mailResult): void
    {
        $lastSent = $this->getLastSentMeta();
        $sentAt = date('Y-m-d H:i:s');

        foreach (array_keys($triggered) as $type) {
            $count = match ($type) {
                'bruteforce' => (int) ($snapshot['bruteforce']['count'] ?? 0),
                'antispam' => (int) ($snapshot['antispam']['count'] ?? 0),
                'firewall' => (int) ($snapshot['firewall']['count'] ?? 0),
                default => 0,
            };

            $lastSent[$type] = [
                'sent_at' => $sentAt,
                'count' => $count,
                'queued' => !empty($mailResult['queued']),
            ];
        }

        $this->settings->set(self::SETTINGS_GROUP, self::META_LAST_SENT, $lastSent, false, 0);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function persistLastRunMeta(array $payload): void
    {
        $this->settings->set(self::SETTINGS_GROUP, self::META_LAST_RUN, $payload, false, 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function getLastRunMeta(): array
    {
        $value = $this->settings->get(self::SETTINGS_GROUP, self::META_LAST_RUN, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getLastSentMeta(): array
    {
        $value = $this->settings->get(self::SETTINGS_GROUP, self::META_LAST_SENT, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, int>
     */
    private function extractCounts(array $snapshot): array
    {
        return [
            'bruteforce' => (int) ($snapshot['bruteforce']['count'] ?? 0),
            'antispam' => (int) ($snapshot['antispam']['count'] ?? 0),
            'firewall' => (int) ($snapshot['firewall']['count'] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function sanitizeReason(string $value): string
    {
        $value = preg_replace('/[^a-z0-9_.:-]/i', '_', strtolower(trim($value))) ?? '';

        return $value !== '' ? substr($value, 0, 40) : 'unknown';
    }

    private function sanitizeRule(string $value): string
    {
        $value = preg_replace('/[^a-z0-9#_.:-]/i', '_', trim($value)) ?? '';

        return $value !== '' ? substr($value, 0, 80) : 'regel';
    }

    private function sanitizePath(string $value): string
    {
        $path = (string) parse_url($value, PHP_URL_PATH);
        if ($path === '') {
            $path = '/';
        }

        $path = preg_replace('/[\x00-\x1F\x7F]+/u', '', $path) ?? '/';

        return function_exists('mb_substr') ? mb_substr($path, 0, 120) : substr($path, 0, 120);
    }

    private function maskIpAddress(string $ip): string
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'unbekannt';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = 'x';
                return implode('.', $parts);
            }
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $parts = array_slice($parts, 0, 4);
            return implode(':', $parts) . '::x';
        }

        return 'unbekannt';
    }
}
