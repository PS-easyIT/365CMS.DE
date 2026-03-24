<?php
declare(strict_types=1);

/**
 * SecurityAuditModule – Sicherheitsprüfungen & Audit-Log
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;

class SecurityAuditModule
{
    private const MAX_AUDIT_LOG_ROWS = 50;
    private const MAX_CHECK_NAME_LENGTH = 120;
    private const MAX_CHECK_DETAIL_LENGTH = 320;
    private const MAX_AUDIT_DETAIL_LENGTH = 240;
    private const MAX_HTACCESS_BYTES = 131072;

    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        if (!$this->canAccess()) {
            return [
                'checks' => [],
                'stats' => ['passed' => 0, 'warning' => 0, 'critical' => 0, 'total' => 0],
                'audit_log' => [],
                'error' => 'Zugriff verweigert.',
            ];
        }

        $checks = $this->performChecks();

        return [
            'checks'  => $checks,
            'stats'   => $this->summarizeChecks($checks),
            'audit_log' => $this->fetchRecentAuditLog(),
        ];
    }

    public function runAudit(): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Sie dürfen dieses Audit nicht ausführen.'];
        }

        $checks = $this->performChecks();
        $stats = $this->summarizeChecks($checks);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'security.audit.run',
            'Sicherheits-Audit manuell ausgeführt',
            'security_audit',
            null,
            [
                'critical' => $stats['critical'],
                'warning' => $stats['warning'],
                'total' => $stats['total'],
            ],
            $stats['critical'] > 0 ? 'warning' : 'info'
        );

        return ['success' => true, 'message' => "Audit abgeschlossen. {$stats['critical']} kritische Probleme gefunden."];
    }

    public function clearLog(): array
    {
        if (!$this->canAccess()) {
            return ['success' => false, 'error' => 'Sie dürfen Audit-Logs nicht bereinigen.'];
        }

        try {
            $olderEntries = (int) ($this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ) ?? 0);

            $this->db->query("DELETE FROM {$this->prefix}audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'security.audit.clear_log',
                'Alte Sicherheits-Audit-Logs bereinigt',
                'security_audit',
                null,
                ['deleted_entries' => $olderEntries],
                'warning'
            );

            return ['success' => true, 'message' => 'Alte Audit-Einträge (> 30 Tage) gelöscht.'];
        } catch (\Throwable $e) {
            $this->logFailure('security.audit.clear_log_failed', 'Audit-Log-Bereinigung fehlgeschlagen.', [
                'exception' => $e::class,
            ]);

            return ['success' => false, 'error' => 'Audit-Log-Bereinigung fehlgeschlagen.'];
        }
    }

    private function performChecks(): array
    {
        $checks = [];
        $abspath = defined('ABSPATH') ? ABSPATH : '';
        $headerProfile = class_exists('\CMS\Security') ? \CMS\Security::instance()->getSecurityHeaderProfile() : [];
        $htaccessStatus = $this->inspectHtaccessHeaders($abspath);
        $isHttps = (bool) ($headerProfile['https'] ?? false);
        $debugOn = defined('CMS_DEBUG') && CMS_DEBUG;

        $checks[] = $this->buildCheck(
            'HTTPS aktiv',
            $isHttps ? 'ok' : 'critical',
            $isHttps ? 'Verbindung ist verschlüsselt.' : 'HTTPS ist nicht aktiv! Alle Daten werden unverschlüsselt übertragen.'
        );

        $phpVersion = PHP_VERSION;
        $phpStatus  = match (true) {
            version_compare($phpVersion, '8.2.0', '>=') => 'ok',
            version_compare($phpVersion, '8.1.0', '>=') => 'warning',
            default                                      => 'critical',
        };
        $checks[] = $this->buildCheck(
            'PHP-Version (' . $phpVersion . ')',
            $phpStatus,
            match ($phpStatus) {
                'ok'       => 'PHP-Version ist aktuell.',
                'warning'  => 'PHP 8.1 – Update auf 8.2+ empfohlen.',
                'critical' => 'PHP-Version ist veraltet! Mindestens 8.1 erforderlich.',
            }
        );

        $configFile = $abspath . 'config.php';
        if (is_file($configFile)) {
            $perms = substr(sprintf('%o', fileperms($configFile)), -4);
            $configOk = in_array($perms, ['0644', '0640', '0600'], true);
            $checks[] = $this->buildCheck(
                'config.php Berechtigungen (' . $perms . ')',
                $configOk ? 'ok' : 'warning',
                $configOk ? 'Dateiberechtigungen sind korrekt.' : 'config.php sollte nicht öffentlich beschreibbar sein (empfohlen: 644 oder 640).'
            );
        }

        $installExists = is_file($abspath . 'install.php');
        $checks[] = $this->buildCheck(
            'install.php entfernt',
            $installExists ? 'warning' : 'ok',
            $installExists ? 'install.php existiert noch und sollte nach der Installation gelöscht werden.' : 'install.php wurde entfernt.'
        );

        $checks[] = $this->buildCheck(
            'Debug-Modus',
            $debugOn ? 'warning' : 'ok',
            $debugOn ? 'Debug-Modus ist aktiv. Sollte in Produktion deaktiviert sein.' : 'Debug-Modus ist deaktiviert.'
        );

        $uploadsDir = $abspath . 'uploads/';
        if (is_dir($uploadsDir)) {
            $htaccess = is_file($uploadsDir . '.htaccess');
            $checks[] = $this->buildCheck(
                'Uploads-Schutz (.htaccess)',
                $htaccess ? 'ok' : 'warning',
                $htaccess ? 'Upload-Verzeichnis ist geschützt.' : 'Kein .htaccess im Upload-Verzeichnis. PHP-Ausführung sollte blockiert sein.'
            );
        }

        $checks[] = $this->buildCheck(
            'Passwort-Policy',
            method_exists('\CMS\Auth', 'validatePasswordPolicy') ? 'ok' : 'critical',
            method_exists('\CMS\Auth', 'validatePasswordPolicy')
                ? 'Passwort-Policy ist implementiert.'
                : 'Keine Passwort-Policy-Validierung gefunden.'
        );

        $checks[] = $this->buildCheck(
            'CSRF-Token-System',
            class_exists('\CMS\Security') ? 'ok' : 'critical',
            class_exists('\CMS\Security') ? 'CSRF-Token-System ist aktiv.' : 'Security-Klasse nicht gefunden.'
        );

        $cspStatus = (($headerProfile['csp_mode'] ?? '') === 'enforced' && !empty($headerProfile['csp_uses_nonce']))
            ? 'ok'
            : 'warning';
        $checks[] = $this->buildCheck(
            'Content-Security-Policy (CSP)',
            $cspStatus,
            !empty($headerProfile['csp_uses_nonce'])
                ? ('Nonce-basierte CSP aktiv; Modus: ' . (($headerProfile['csp_mode'] ?? 'report-only') === 'enforced' ? 'enforced' : 'report-only') . (($debugOn && ($headerProfile['csp_mode'] ?? '') !== 'enforced') ? ' (Debug-Modus)' : '') . '.')
                : 'Keine nonce-basierte CSP erkannt.'
        );

        $trustedTypesStatus = !empty($headerProfile['trusted_types_enforced'])
            ? 'ok'
            : (!empty($headerProfile['trusted_types_report_only']) ? 'warning' : 'critical');
        $checks[] = $this->buildCheck(
            'Trusted Types',
            $trustedTypesStatus,
            !empty($headerProfile['trusted_types_enforced'])
                ? 'Trusted Types werden erzwungen.'
                : (!empty($headerProfile['trusted_types_report_only'])
                    ? 'Trusted Types laufen aktuell im Report-Only-Modus zur Kompatibilitätsprüfung.'
                    : 'Trusted Types sind nicht konfiguriert.')
        );

        $hstsStatus = !$isHttps
            ? 'warning'
            : (!empty($headerProfile['hsts_enabled']) && !empty($headerProfile['hsts_include_subdomains']) && !empty($headerProfile['hsts_preload']) ? 'ok' : 'critical');
        $checks[] = $this->buildCheck(
            'Strict-Transport-Security (HSTS)',
            $hstsStatus,
            !$isHttps
                ? 'HSTS kann erst bei aktivem HTTPS vollständig bewertet werden.'
                : (!empty($headerProfile['hsts_enabled'])
                    ? 'HSTS aktiv mit includeSubDomains und preload.'
                    : ($debugOn
                        ? 'HSTS ist im Debug-Modus absichtlich nicht erzwungen.'
                        : 'HSTS ist für HTTPS-Anfragen nicht vollständig aktiv.'))
        );

        $fallbackStatus = !$htaccessStatus['exists']
            ? 'warning'
            : (($htaccessStatus['csp_setifempty']
                && !$htaccessStatus['csp_has_unsafe_inline']
                && !$htaccessStatus['csp_has_unsafe_eval']
                && !$htaccessStatus['report_only_present']
                && $htaccessStatus['trusted_types_present']
                && $htaccessStatus['hsts_include_subdomains']
                && $htaccessStatus['hsts_preload']
                && $htaccessStatus['proxy_https_detection']) ? 'ok' : 'warning');
        $checks[] = $this->buildCheck(
            '.htaccess Sicherheits-Fallback',
            $fallbackStatus,
            !$htaccessStatus['exists']
                ? 'Keine .htaccess-Datei gefunden – Apache-Fallback kann nicht geprüft werden.'
                : ($fallbackStatus === 'ok'
                    ? 'Apache-Fallback für CSP/HSTS ist konsistent gehärtet.'
                    : 'Apache-Fallback prüfen: kein Report-Only-Header im Produktivpfad, CSP ohne unsafe-inline/unsafe-eval, Trusted Types sowie HSTS inklusive Proxy-HTTPS-Erkennung sollten aktiv sein.')
        );

        $backupDir = $abspath . 'backups/';
        if (is_dir($backupDir)) {
            $backups = glob($backupDir . '*.sql*');
            $lastBackup = 0;
            foreach ($backups ?: [] as $backupFile) {
                $timestamp = @filemtime($backupFile);
                if (is_int($timestamp) && $timestamp > 0) {
                    $lastBackup = max($lastBackup, $timestamp);
                }
            }
            $daysSince = $lastBackup ? (int) ceil((time() - $lastBackup) / 86400) : 999;
            $checks[] = $this->buildCheck(
                'Letztes Backup',
                $daysSince <= 7 ? 'ok' : ($daysSince <= 30 ? 'warning' : 'critical'),
                $lastBackup ? "Letztes Backup vor {$daysSince} Tagen." : 'Kein Datenbank-Backup gefunden.'
            );
        }

        try {
            $weakAdmins = $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'admin' AND LENGTH(password) < 50"
            );
            $checks[] = $this->buildCheck(
                'Admin-Passwort-Hashes',
                ((int) $weakAdmins === 0) ? 'ok' : 'critical',
                ((int) $weakAdmins === 0)
                    ? 'Alle Admin-Passwörter sind korrekt gehasht.'
                    : "{$weakAdmins} Admin-Accounts haben möglicherweise schwache oder ungehashte Passwörter."
            );
        } catch (\Throwable $e) {
            $this->logFailure('security.audit.weak_admins_failed', 'Admin-Passwort-Hash-Prüfung konnte nicht ausgeführt werden.', [
                'exception' => $e::class,
            ]);
        }

        return $checks;
    }

    private function inspectHtaccessHeaders(string $abspath): array
    {
        $path = $abspath . '.htaccess';
        $content = '';

        if (is_file($path) && is_readable($path)) {
            $warning = null;
            set_error_handler(static function (int $severity, string $message) use (&$warning): bool {
                $warning = $message;
                return true;
            });

            try {
                $rawContent = file_get_contents($path, false, null, 0, self::MAX_HTACCESS_BYTES + 1);
            } finally {
                restore_error_handler();
            }

            if (is_string($rawContent)) {
                if (strlen($rawContent) > self::MAX_HTACCESS_BYTES) {
                    $this->logFailure('security.audit.htaccess_limit', '.htaccess-Prüfung auf maximale Dateigröße begrenzt.', [
                        'path' => $path,
                        'max_bytes' => self::MAX_HTACCESS_BYTES,
                    ]);
                    $rawContent = substr($rawContent, 0, self::MAX_HTACCESS_BYTES);
                }

                $content = $rawContent;
            } elseif ($warning !== null) {
                $this->logFailure('security.audit.htaccess_read_failed', '.htaccess konnte für das Sicherheits-Audit nicht gelesen werden.', [
                    'path' => $path,
                ]);
            }
        }

        $normalizedContent = preg_replace('/^\s*#.*$/m', '', $content) ?: $content;

        return [
            'exists' => $content !== '',
            'csp_setifempty' => preg_match('/Header\s+always\s+setifempty\s+Content-Security-Policy\b/i', $normalizedContent) === 1,
            'csp_has_unsafe_inline' => str_contains($normalizedContent, "'unsafe-inline'"),
            'csp_has_unsafe_eval' => str_contains($normalizedContent, "'unsafe-eval'"),
            'report_only_present' => preg_match('/Header\s+always\s+set(?:ifempty)?\s+Content-Security-Policy-Report-Only\b/i', $normalizedContent) === 1,
            'trusted_types_present' => stripos($normalizedContent, 'trusted-types') !== false,
            'hsts_include_subdomains' => stripos($normalizedContent, 'includeSubDomains') !== false,
            'hsts_preload' => stripos($normalizedContent, 'preload') !== false,
            'proxy_https_detection' => stripos($normalizedContent, 'X-Forwarded-Proto') !== false,
        ];
    }

    private function canAccess(): bool
    {
        return class_exists('\CMS\Auth') && \CMS\Auth::instance()->isAdmin();
    }

    /**
     * @return array{passed:int, warning:int, critical:int, total:int}
     */
    private function summarizeChecks(array $checks): array
    {
        $summary = ['passed' => 0, 'warning' => 0, 'critical' => 0, 'total' => count($checks)];

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'warning');
            if ($status === 'ok') {
                $summary['passed']++;
            } elseif ($status === 'critical') {
                $summary['critical']++;
            } else {
                $summary['warning']++;
            }
        }

        return $summary;
    }

    /**
     * @return array<int, array<string, scalar|null>>
     */
    private function fetchRecentAuditLog(): array
    {
        try {
            $rows = $this->db->get_results(
                "SELECT created_at, action, user_id, description, category, severity, ip_address, metadata
                 FROM {$this->prefix}audit_log
                 WHERE category IN (?, ?)
                 ORDER BY created_at DESC
                 LIMIT " . self::MAX_AUDIT_LOG_ROWS,
                [AuditLogger::CAT_SECURITY, AuditLogger::CAT_AUTH]
            ) ?: [];

            return array_map(fn (mixed $row): array => $this->normalizeAuditLogRow($row), $rows);
        } catch (\Throwable $e) {
            $this->logFailure('security.audit.log_fetch_failed', 'Audit-Log konnte für das Sicherheits-Audit nicht geladen werden.', [
                'exception' => $e::class,
            ]);

            return [];
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeAuditLogRow(mixed $row): array
    {
        $data = is_object($row) ? get_object_vars($row) : (is_array($row) ? $row : []);
        $metadataPreview = '';

        if (!empty($data['metadata']) && is_string($data['metadata'])) {
            $metadataPreview = $this->truncateText(trim($data['metadata']), self::MAX_AUDIT_DETAIL_LENGTH);
        }

        $details = trim((string) ($data['description'] ?? ''));
        if ($details === '' && $metadataPreview !== '') {
            $details = $metadataPreview;
        }

        return [
            'created_at' => (string) ($data['created_at'] ?? ''),
            'action' => $this->truncateText((string) ($data['action'] ?? ''), 120),
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'details' => $this->truncateText($details, self::MAX_AUDIT_DETAIL_LENGTH),
            'category' => $this->truncateText((string) ($data['category'] ?? ''), 40),
            'severity' => $this->truncateText((string) ($data['severity'] ?? ''), 20),
            'ip_address' => $this->truncateText((string) ($data['ip_address'] ?? ''), 45),
        ];
    }

    /**
     * @return array{name:string, status:string, detail:string}
     */
    private function buildCheck(string $name, string $status, string $detail): array
    {
        if (!in_array($status, ['ok', 'warning', 'critical'], true)) {
            $status = 'warning';
        }

        return [
            'name' => $this->truncateText($name, self::MAX_CHECK_NAME_LENGTH),
            'status' => $status,
            'detail' => $this->truncateText($detail, self::MAX_CHECK_DETAIL_LENGTH),
        ];
    }

    private function truncateText(string $text, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '' || mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $maxLength - 1))) . '…';
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFailure(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('admin.security-audit')->warning($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            $action,
            $message,
            'security_audit',
            null,
            $context,
            'warning'
        );
    }
}
