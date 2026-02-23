<?php
/**
 * AuditLogger – Zentrales Sicherheits-Audit-Log
 *
 * Protokolliert sicherheitsrelevante Aktionen (Theme-Wechsel, Plugin-Aktivierung,
 * Admin-Login, Rollenwechsel, Code-Änderungen …) in der Tabelle {prefix}audit_log.
 *
 * Roadmap H-01: Zentrales Audit-Log-System
 * Roadmap C-15: Theme-Aktionen (Aktivieren, Löschen, Code-Änderung) protokollieren
 *
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class AuditLogger
{
    private static ?self $instance = null;

    // ── Kategorien ────────────────────────────────────────────────────────────
    public const CAT_AUTH    = 'auth';       // Login, Logout, PW-Reset
    public const CAT_THEME   = 'theme';      // Aktivieren, Löschen, Code-Edit
    public const CAT_PLUGIN  = 'plugin';     // Aktivieren, Deaktivieren, Install
    public const CAT_USER    = 'user';       // Erstellen, Rollen, Löschen
    public const CAT_SETTING = 'setting';    // Admin-Einstellungen
    public const CAT_MEDIA   = 'media';      // Upload, Löschen
    public const CAT_SYSTEM  = 'system';     // Backup, Updates, Cache-Flush
    public const CAT_SECURITY = 'security';  // CSP, Firewall, Sperr-IPs

    /**
     * Singleton
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Tabelle wird durch install.php / Database::createTables() angelegt
    }

    /**
     * Sicherheitsrelevante Aktion protokollieren
     *
     * @param string      $category   Kategorie (nutze die CAT_* Konstanten)
     * @param string      $action     Kurzbezeichnung der Aktion, z. B. 'theme.switch'
     * @param string      $description Lesbarer Text für das Protokoll
     * @param string      $entityType  Betroffener Typ (theme|plugin|user …)
     * @param int|null    $entityId    Betroffene ID (optional)
     * @param array       $metadata    Zusatzdaten als assoziatives Array
     * @param string      $severity   'info'|'warning'|'critical'
     */
    public function log(
        string  $category,
        string  $action,
        string  $description,
        string  $entityType  = '',
        ?int    $entityId    = null,
        array   $metadata    = [],
        string  $severity    = 'info'
    ): void {
        try {
            $db = Database::instance();

            $userId    = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
            $ipAddress = $this->getClientIp();
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $db->execute(
                "INSERT INTO {$db->getPrefix()}audit_log
                    (user_id, category, action, entity_type, entity_id,
                     description, ip_address, user_agent, metadata, severity, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $category,
                    $action,
                    $entityType ?: null,
                    $entityId,
                    $description,
                    $ipAddress,
                    $userAgent,
                    !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
                    $severity,
                ]
            );
        } catch (\Throwable $e) {
            // Audit-Logging darf die Anwendung nie zum Absturz bringen
            error_log('AuditLogger::log() failed: ' . $e->getMessage());
        }
    }

    // ── Convenience-Methoden ─────────────────────────────────────────────────

    /** Theme wurde aktiviert */
    public function themeSwitch(string $from, string $to): void
    {
        $this->log(
            self::CAT_THEME,
            'theme.switch',
            "Theme gewechselt von \"{$from}\" zu \"{$to}\"",
            'theme',
            null,
            ['from' => $from, 'to' => $to],
            'info'
        );
    }

    /** Theme wurde gelöscht */
    public function themeDelete(string $folder): void
    {
        $this->log(
            self::CAT_THEME,
            'theme.delete',
            "Theme \"{$folder}\" wurde gelöscht",
            'theme',
            null,
            ['folder' => $folder],
            'warning'
        );
    }

    /** Theme-Datei wurde im Editor gespeichert */
    public function themeFileEdit(string $theme, string $file): void
    {
        $this->log(
            self::CAT_THEME,
            'theme.file.edit',
            "Theme-Datei bearbeitet: {$theme}/{$file}",
            'theme',
            null,
            ['theme' => $theme, 'file' => $file],
            'warning'
        );
    }

    /** Plugin aktiviert oder deaktiviert */
    public function pluginAction(string $action, string $slug): void
    {
        $severity = ($action === 'activate') ? 'info' : 'warning';
        $this->log(
            self::CAT_PLUGIN,
            "plugin.{$action}",
            "Plugin \"{$slug}\" wurde " . ($action === 'activate' ? 'aktiviert' : 'deaktiviert'),
            'plugin',
            null,
            ['slug' => $slug, 'action' => $action],
            $severity
        );
    }

    /** Admin-Login erfolgreich */
    public function loginSuccess(string $username): void
    {
        $this->log(
            self::CAT_AUTH,
            'auth.login',
            "Erfolgreicher Login: {$username}",
            'user',
            null,
            ['username' => $username],
            'info'
        );
    }

    /** Login-Fehlschlag (z. B. falsches Passwort) */
    public function loginFailed(string $username): void
    {
        $this->log(
            self::CAT_AUTH,
            'auth.login.failed',
            "Fehlgeschlagener Login-Versuch: {$username}",
            'user',
            null,
            ['username' => $username],
            'warning'
        );
    }

    /** Benutzer-Rolle geändert */
    public function userRoleChange(int $userId, string $oldRole, string $newRole): void
    {
        $this->log(
            self::CAT_USER,
            'user.role.change',
            "Rolle von User #{$userId} geändert: {$oldRole} → {$newRole}",
            'user',
            $userId,
            ['old_role' => $oldRole, 'new_role' => $newRole],
            'warning'
        );
    }

    /** Backup erstellt oder wiederhergestellt */
    public function backupAction(string $action, string $file): void
    {
        $this->log(
            self::CAT_SYSTEM,
            "backup.{$action}",
            "Backup-Aktion \"{$action}\": {$file}",
            'system',
            null,
            ['file' => $file, 'action' => $action],
            'warning'
        );
    }

    // ── Hilfsmethoden ─────────────────────────────────────────────────────────

    /**
     * Letzte Einträge abrufen (für Admin-Ansicht)
     */
    public function getRecent(int $limit = 50, string $category = ''): array
    {
        try {
            $db = Database::instance();

            if ($category !== '') {
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->getPrefix()}audit_log
                      WHERE category = ?
                      ORDER BY created_at DESC LIMIT {$limit}"
                );
                $stmt->execute([$category]);
            } else {
                $stmt = $db->prepare(
                    "SELECT * FROM {$db->getPrefix()}audit_log
                      ORDER BY created_at DESC LIMIT {$limit}"
                );
                $stmt->execute();
            }

            return $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            error_log('AuditLogger::getRecent() failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Client-IP-Adresse – delegiert an Security::getClientIp() (keine Code-Duplizierung)
     */
    private function getClientIp(): string
    {
        return Security::getClientIp();
    }
}
