<?php
declare(strict_types=1);

/**
 * SecurityAuditModule – Sicherheitsprüfungen & Audit-Log
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;

class SecurityAuditModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $checks = $this->performChecks();
        $passed  = 0;
        $warning = 0;
        $critical = 0;
        foreach ($checks as $c) {
            if ($c['status'] === 'ok')       $passed++;
            if ($c['status'] === 'warning')  $warning++;
            if ($c['status'] === 'critical') $critical++;
        }

        // Audit-Log (letzte Login-Versuche etc.)
        $auditLog = [];
        try {
            $auditLog = $this->db->get_results(
                "SELECT * FROM {$this->prefix}audit_log ORDER BY created_at DESC LIMIT 50"
            ) ?: [];
        } catch (\Exception $e) {}

        return [
            'checks'  => $checks,
            'stats'   => ['passed' => $passed, 'warning' => $warning, 'critical' => $critical, 'total' => count($checks)],
            // FIX: Audit-Log-Zeilen mit stabilen Anzeige-Keys an die View liefern.
            'audit_log' => array_map(static function ($r): array {
                $row = (array)$r;
                if (!isset($row['details'])) {
                    $row['details'] = (string)($row['description'] ?? '');
                }
                return $row;
            }, $auditLog),
        ];
    }

    public function runAudit(): array
    {
        $checks = $this->performChecks();
        $critical = 0;
        foreach ($checks as $c) {
            if ($c['status'] === 'critical') $critical++;
        }
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'security.audit.run',
            'Sicherheits-Audit manuell ausgeführt',
            'security_audit',
            null,
            ['critical' => $critical, 'total' => count($checks)],
            $critical > 0 ? 'warning' : 'info'
        );
        return ['success' => true, 'message' => "Audit abgeschlossen. {$critical} kritische Probleme gefunden."];
    }

    public function clearLog(): array
    {
        try {
            $this->db->query("DELETE FROM {$this->prefix}audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'security.audit.clear_log',
                'Alte Sicherheits-Audit-Logs bereinigt',
                'security_audit',
                null,
                [],
                'warning'
            );
            return ['success' => true, 'message' => 'Alte Audit-Einträge (> 30 Tage) gelöscht.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function performChecks(): array
    {
        $checks = [];
        $abspath = defined('ABSPATH') ? ABSPATH : '';
        $headerProfile = class_exists('\CMS\Security') ? \CMS\Security::instance()->getSecurityHeaderProfile() : [];
        $htaccessStatus = $this->inspectHtaccessHeaders($abspath);

        // 1. HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        $checks[] = [
            'name'   => 'HTTPS aktiv',
            'status' => $isHttps ? 'ok' : 'critical',
            'detail' => $isHttps ? 'Verbindung ist verschlüsselt.' : 'HTTPS ist nicht aktiv! Alle Daten werden unverschlüsselt übertragen.',
        ];

        // 2. PHP Version
        $phpVersion = PHP_VERSION;
        $phpStatus  = match (true) {
            version_compare($phpVersion, '8.2.0', '>=') => 'ok',
            version_compare($phpVersion, '8.1.0', '>=') => 'warning',
            default                                      => 'critical',
        };
        $checks[] = [
            'name'   => 'PHP-Version (' . $phpVersion . ')',
            'status' => $phpStatus,
            'detail' => match ($phpStatus) {
                'ok'       => 'PHP-Version ist aktuell.',
                'warning'  => 'PHP 8.1 – Update auf 8.2+ empfohlen.',
                'critical' => 'PHP-Version ist veraltet! Mindestens 8.1 erforderlich.',
            },
        ];

        // 3. config.php Berechtigungen
        $configFile = $abspath . 'config.php';
        if (file_exists($configFile)) {
            $perms = substr(sprintf('%o', fileperms($configFile)), -4);
            $configOk = in_array($perms, ['0644', '0640', '0600'], true);
            $checks[] = [
                'name'   => 'config.php Berechtigungen (' . $perms . ')',
                'status' => $configOk ? 'ok' : 'warning',
                'detail' => $configOk ? 'Dateiberechtigungen sind korrekt.' : 'config.php sollte nicht öffentlich beschreibbar sein (empfohlen: 644 oder 640).',
            ];
        }

        // 4. install.php vorhanden
        $installExists = file_exists($abspath . 'install.php');
        $checks[] = [
            'name'   => 'install.php entfernt',
            'status' => $installExists ? 'warning' : 'ok',
            'detail' => $installExists ? 'install.php existiert noch und sollte nach der Installation gelöscht werden.' : 'install.php wurde entfernt.',
        ];

        // 5. Debug-Modus
        $debugOn = defined('CMS_DEBUG') && CMS_DEBUG;
        $checks[] = [
            'name'   => 'Debug-Modus',
            'status' => $debugOn ? 'warning' : 'ok',
            'detail' => $debugOn ? 'Debug-Modus ist aktiv. Sollte in Produktion deaktiviert sein.' : 'Debug-Modus ist deaktiviert.',
        ];

        // 6. uploads-Verzeichnis
        $uploadsDir = $abspath . 'uploads/';
        if (is_dir($uploadsDir)) {
            $htaccess = file_exists($uploadsDir . '.htaccess');
            $checks[] = [
                'name'   => 'Uploads-Schutz (.htaccess)',
                'status' => $htaccess ? 'ok' : 'warning',
                'detail' => $htaccess ? 'Upload-Verzeichnis ist geschützt.' : 'Kein .htaccess im Upload-Verzeichnis. PHP-Ausführung sollte blockiert sein.',
            ];
        }

        // 7. Passwort-Policy
        $checks[] = [
            'name'   => 'Passwort-Policy',
            'status' => method_exists('\CMS\Auth', 'validatePasswordPolicy') ? 'ok' : 'critical',
            'detail' => method_exists('\CMS\Auth', 'validatePasswordPolicy')
                ? 'Passwort-Policy ist implementiert.' : 'Keine Passwort-Policy-Validierung gefunden.',
        ];

        // 8. CSRF-Schutz
        $checks[] = [
            'name'   => 'CSRF-Token-System',
            'status' => class_exists('\CMS\Security') ? 'ok' : 'critical',
            'detail' => class_exists('\CMS\Security') ? 'CSRF-Token-System ist aktiv.' : 'Security-Klasse nicht gefunden.',
        ];

        // 9. Content Security Policy
        $cspStatus = (($headerProfile['csp_mode'] ?? '') === 'enforced' && !empty($headerProfile['csp_uses_nonce']))
            ? 'ok'
            : 'warning';
        $checks[] = [
            'name'   => 'Content-Security-Policy (CSP)',
            'status' => $cspStatus,
            'detail' => !empty($headerProfile['csp_uses_nonce'])
                ? ('Nonce-basierte CSP aktiv; Modus: ' . (($headerProfile['csp_mode'] ?? 'report-only') === 'enforced' ? 'enforced' : 'report-only') . '.')
                : 'Keine nonce-basierte CSP erkannt.',
        ];

        // 10. Trusted Types
        $trustedTypesStatus = !empty($headerProfile['trusted_types_enforced'])
            ? 'ok'
            : (!empty($headerProfile['trusted_types_report_only']) ? 'warning' : 'critical');
        $checks[] = [
            'name'   => 'Trusted Types',
            'status' => $trustedTypesStatus,
            'detail' => !empty($headerProfile['trusted_types_enforced'])
                ? 'Trusted Types werden erzwungen.'
                : (!empty($headerProfile['trusted_types_report_only'])
                    ? 'Trusted Types laufen aktuell im Report-Only-Modus zur Kompatibilitätsprüfung.'
                    : 'Trusted Types sind nicht konfiguriert.'),
        ];

        // 11. HSTS
        $hstsStatus = !$isHttps
            ? 'warning'
            : (!empty($headerProfile['hsts_enabled']) && !empty($headerProfile['hsts_include_subdomains']) && !empty($headerProfile['hsts_preload']) ? 'ok' : 'critical');
        $checks[] = [
            'name'   => 'Strict-Transport-Security (HSTS)',
            'status' => $hstsStatus,
            'detail' => !$isHttps
                ? 'HSTS kann erst bei aktivem HTTPS vollständig bewertet werden.'
                : (!empty($headerProfile['hsts_enabled'])
                    ? 'HSTS aktiv mit includeSubDomains und preload.'
                    : 'HSTS ist für HTTPS-Anfragen nicht vollständig aktiv.'),
        ];

        // 12. Apache-Fallback-Header
        $fallbackStatus = !$htaccessStatus['exists']
            ? 'warning'
            : (($htaccessStatus['csp_setifempty'] && !$htaccessStatus['csp_has_unsafe_eval'] && $htaccessStatus['trusted_types_present'] && $htaccessStatus['hsts_include_subdomains'] && $htaccessStatus['hsts_preload']) ? 'ok' : 'warning');
        $checks[] = [
            'name'   => '.htaccess Sicherheits-Fallback',
            'status' => $fallbackStatus,
            'detail' => !$htaccessStatus['exists']
                ? 'Keine .htaccess-Datei gefunden – Apache-Fallback kann nicht geprüft werden.'
                : ($fallbackStatus === 'ok'
                    ? 'Apache-Fallback für CSP/HSTS ist konsistent gehärtet.'
                    : 'Apache-Fallback prüfen: setifempty, HSTS preload/includeSubDomains und Trusted-Types-Report-Only sollten aktiv sein.'),
        ];

        // 9. Datenbank-Backup Alter
        $backupDir = $abspath . 'backups/';
        if (is_dir($backupDir)) {
            $backups = glob($backupDir . '*.sql*');
            $lastBackup = 0;
            foreach ($backups ?: [] as $b) {
                $lastBackup = max($lastBackup, filemtime($b));
            }
            $daysSince = $lastBackup ? (int)ceil((time() - $lastBackup) / 86400) : 999;
            $checks[] = [
                'name'   => 'Letztes Backup',
                'status' => $daysSince <= 7 ? 'ok' : ($daysSince <= 30 ? 'warning' : 'critical'),
                'detail' => $lastBackup
                    ? "Letztes Backup vor {$daysSince} Tagen."
                    : 'Kein Datenbank-Backup gefunden.',
            ];
        }

        // 13. Admin-Benutzer mit schwachen Passwörtern
        try {
            $weakAdmins = $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'admin' AND LENGTH(password) < 50"
            );
            $checks[] = [
                'name'   => 'Admin-Passwort-Hashes',
                'status' => ((int)$weakAdmins === 0) ? 'ok' : 'critical',
                'detail' => ((int)$weakAdmins === 0)
                    ? 'Alle Admin-Passwörter sind korrekt gehasht.'
                    : "{$weakAdmins} Admin-Accounts haben möglicherweise schwache oder ungehashte Passwörter.",
            ];
        } catch (\Exception) {
            // users-Tabelle existiert möglicherweise noch nicht
        }

        return $checks;
    }

    private function inspectHtaccessHeaders(string $abspath): array
    {
        $path = $abspath . '.htaccess';
        $content = file_exists($path) ? (string)file_get_contents($path) : '';

        return [
            'exists' => $content !== '',
            'csp_setifempty' => preg_match('/Header\s+always\s+setifempty\s+Content-Security-Policy/i', $content) === 1,
            'csp_has_unsafe_eval' => str_contains($content, "'unsafe-eval'"),
            'trusted_types_present' => stripos($content, 'trusted-types') !== false,
            'hsts_include_subdomains' => stripos($content, 'includeSubDomains') !== false,
            'hsts_preload' => stripos($content, 'preload') !== false,
        ];
    }
}
