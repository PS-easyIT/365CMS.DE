<?php
/**
 * LdapAuthProvider – LDAP-Authentifizierung über LdapRecord
 *
 * Verbindet sich mit einem konfigurierten LDAP/Active-Directory-Server,
 * authentifiziert Benutzer und synchronisiert sie in die lokale CMS-Datenbank.
 *
 * Konfiguration über Konstanten in config/app.php:
 *   LDAP_HOST, LDAP_PORT, LDAP_BASE_DN, LDAP_USERNAME, LDAP_PASSWORD,
 *   LDAP_USE_SSL, LDAP_USE_TLS, LDAP_FILTER, LDAP_DEFAULT_ROLE
 *
 * @package CMSv2\Core\Auth\LDAP
 */

declare(strict_types=1);

namespace CMS\Auth\LDAP;

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Security;
use LdapRecord\Connection;
use LdapRecord\Auth\BindException;

final class LdapAuthProvider
{
    private static ?self $instance = null;

    private ?Connection $connection = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    // ── Öffentliche API ──────────────────────────────────────────────────────

    /**
     * Benutzer gegen LDAP authentifizieren.
     *
     * @return array|null  LDAP-Benutzerdaten bei Erfolg, null bei Fehlschlag
     */
    public function authenticate(string $username, string $password): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $conn = $this->getConnection();

            // Service-Account binden, um den User zu suchen
            $conn->connect();
            $conn->auth()->bindAsConfiguredUser();

            $user = $this->findLdapUser($conn, $username);
            if ($user === null) {
                return null;
            }

            // DN des gefundenen Users zum Bind verwenden
            $dn = $user['dn'] ?? '';
            if ($dn === '') {
                return null;
            }

            if (!$conn->auth()->attempt($dn, $password)) {
                return null;
            }

            return $user;
        } catch (BindException) {
            error_log('[LdapAuthProvider] Bind fehlgeschlagen für: ' . $username);
            return null;
        } catch (\Throwable $e) {
            error_log('[LdapAuthProvider] Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * LDAP-Benutzer in der lokalen CMS-Datenbank anlegen oder aktualisieren.
     *
     * @param array $ldapUser  Ergebnis von authenticate() / findLdapUser()
     * @return int|null        user-ID bei Erfolg, null bei Fehler
     */
    public function syncLocalUser(array $ldapUser): ?int
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();

        $email    = $this->extractAttribute($ldapUser, 'mail');
        $name     = $this->extractAttribute($ldapUser, 'cn')
                    ?: $this->extractAttribute($ldapUser, 'displayname');
        $username = $this->extractAttribute($ldapUser, 'samaccountname')
                    ?: $this->extractAttribute($ldapUser, 'uid')
                    ?: $email;

        if ($email === '' || $username === '') {
            error_log('[LdapAuthProvider] Sync fehlgeschlagen – kein E-Mail oder Username im LDAP-Eintrag.');
            return null;
        }

        // Bestehenden Benutzer suchen (via E-Mail oder LDAP-Meta)
        $existing = $db->execute(
            "SELECT id FROM {$prefix}users WHERE email = ? LIMIT 1",
            [$email]
        )->fetch();

        if ($existing) {
            // Nur Name aktualisieren
            $db->execute(
                "UPDATE {$prefix}users SET display_name = ?, updated_at = NOW() WHERE id = ?",
                [$name, (int)$existing->id]
            );
            $this->setUserMeta((int)$existing->id, 'ldap_dn', $ldapUser['dn'] ?? '');
            return (int)$existing->id;
        }

        // Neuen Benutzer anlegen – Zufallspasswort (LDAP übernimmt Auth)
        $randomPassword = Security::instance()->hashPassword(bin2hex(random_bytes(32)));
        $defaultRole = defined('LDAP_DEFAULT_ROLE') ? LDAP_DEFAULT_ROLE : 'member';

        $db->execute(
            "INSERT INTO {$prefix}users (username, email, display_name, password, role, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())",
            [$username, $email, $name, $randomPassword, $defaultRole]
        );

        $userId = (int)$db->getPdo()->lastInsertId();
        if ($userId === 0) {
            return null;
        }

        $this->setUserMeta($userId, 'ldap_dn', $ldapUser['dn'] ?? '');
        $this->setUserMeta($userId, 'ldap_synced', '1');

        return $userId;
    }

    /**
     * Verbindung zum LDAP-Server testen.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'LDAP ist nicht konfiguriert.'];
        }

        if (!extension_loaded('ldap')) {
            return ['success' => false, 'message' => 'PHP-LDAP-Extension ist nicht installiert.'];
        }

        try {
            $conn = $this->getConnection();
            $conn->connect();
            $conn->auth()->bindAsConfiguredUser();
            return ['success' => true, 'message' => 'Verbindung erfolgreich.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Prüft ob LDAP konfiguriert ist.
     */
    public function isConfigured(): bool
    {
        return defined('LDAP_HOST')
            && LDAP_HOST !== ''
            && defined('LDAP_BASE_DN')
            && LDAP_BASE_DN !== '';
    }

    // ── LDAP-Suche ───────────────────────────────────────────────────────────

    /**
     * Benutzer im LDAP-Verzeichnis suchen.
     */
    private function findLdapUser(Connection $conn, string $username): ?array
    {
        $filter = defined('LDAP_FILTER') && LDAP_FILTER !== ''
            ? str_replace('{username}', $this->escapeLdapFilter($username), LDAP_FILTER)
            : '(|(sAMAccountName=' . $this->escapeLdapFilter($username) . ')'
              . '(uid=' . $this->escapeLdapFilter($username) . ')'
              . '(mail=' . $this->escapeLdapFilter($username) . '))';

        $results = $conn->query()
            ->rawFilter($filter)
            ->in(LDAP_BASE_DN)
            ->select(['cn', 'displayname', 'mail', 'samaccountname', 'uid', 'dn'])
            ->limit(1)
            ->get();

        if (empty($results)) {
            return null;
        }

        return $results[0] ?? null;
    }

    // ── Connection-Management ────────────────────────────────────────────────

    private function getConnection(): Connection
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $config = [
            'hosts'    => [LDAP_HOST],
            'port'     => defined('LDAP_PORT')     ? (int)LDAP_PORT     : 389,
            'base_dn'  => LDAP_BASE_DN,
            'username' => defined('LDAP_USERNAME')  ? LDAP_USERNAME      : '',
            'password' => defined('LDAP_PASSWORD')  ? LDAP_PASSWORD      : '',
            'use_ssl'  => defined('LDAP_USE_SSL')   ? (bool)LDAP_USE_SSL : false,
            'use_tls'  => defined('LDAP_USE_TLS')   ? (bool)LDAP_USE_TLS : true,
        ];

        $this->connection = new Connection($config);

        return $this->connection;
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    /**
     * Einzelnes Attribut aus einem LDAP-Eintrag extrahieren.
     * LDAP-Attribute sind oft Arrays: ['mail' => ['user@example.com', 'count' => 1]]
     */
    private function extractAttribute(array $entry, string $key): string
    {
        $key = strtolower($key);

        if (!isset($entry[$key])) {
            return '';
        }

        $value = $entry[$key];
        if (is_array($value)) {
            return (string)($value[0] ?? '');
        }

        return (string)$value;
    }

    /**
     * LDAP-Filter-Werte escapen (RFC 4515).
     */
    private function escapeLdapFilter(string $value): string
    {
        return str_replace(
            ['\\', '*', '(', ')', "\x00"],
            ['\\5c', '\\2a', '\\28', '\\29', '\\00'],
            $value
        );
    }

    /**
     * user_meta setzen (Upsert).
     */
    private function setUserMeta(int $userId, string $key, string $value): void
    {
        $db = Database::instance();
        $db->execute(
            "INSERT INTO {$db->getPrefix()}user_meta (user_id, meta_key, meta_value)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
            [$userId, $key, $value]
        );
    }
}
