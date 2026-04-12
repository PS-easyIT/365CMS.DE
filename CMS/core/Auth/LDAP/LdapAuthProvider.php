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
            \CMS\Logger::instance()->withChannel('ldap')->warning('LDAP bind failed for user.', [
                'username' => $username,
            ]);
            return null;
        } catch (\Throwable $e) {
            \CMS\Logger::instance()->withChannel('ldap')->warning('LDAP authentication failed unexpectedly.', [
                'username' => $username,
                'exception' => $e,
            ]);
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
        $result = $this->syncLocalUserDetailed($ldapUser);

        return $result['success'] ? $result['user_id'] : null;
    }

    /**
     * LDAP-Benutzer in der lokalen CMS-Datenbank anlegen oder aktualisieren.
     * Liefert zusätzliche Statusinformationen für Admin-Sync-Läufe.
     *
     * @param array $ldapUser
     * @return array{success: bool, action: string, user_id: int|null, message: string}
     */
    public function syncLocalUserDetailed(array $ldapUser): array
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
            \CMS\Logger::instance()->withChannel('ldap')->warning('LDAP user sync skipped because required identity fields are missing.', [
                'has_email' => $email !== '',
                'has_username' => $username !== '',
                'ldap_dn' => (string) ($ldapUser['dn'] ?? ''),
            ]);
            return [
                'success' => false,
                'action' => 'skipped',
                'user_id' => null,
                'message' => 'LDAP-Eintrag ohne E-Mail oder Benutzername übersprungen.',
            ];
        }

        // Bestehenden Benutzer suchen (via E-Mail oder LDAP-Meta)
        $existingUserId = $this->findExistingUserId($email, (string)($ldapUser['dn'] ?? ''));

        if ($existingUserId !== null) {
            // Nur Name aktualisieren
            $db->execute(
                "UPDATE {$prefix}users SET display_name = ?, updated_at = NOW() WHERE id = ?",
                [$name, $existingUserId]
            );
            $this->setUserMeta($existingUserId, 'ldap_dn', (string)($ldapUser['dn'] ?? ''));
            $this->setUserMeta($existingUserId, 'ldap_synced', '1');

            return [
                'success' => true,
                'action' => 'updated',
                'user_id' => $existingUserId,
                'message' => 'Bestehender LDAP-Benutzer aktualisiert.',
            ];
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
            return [
                'success' => false,
                'action' => 'error',
                'user_id' => null,
                'message' => 'Lokaler Benutzer konnte nicht angelegt werden.',
            ];
        }

        $this->setUserMeta($userId, 'ldap_dn', $ldapUser['dn'] ?? '');
        $this->setUserMeta($userId, 'ldap_synced', '1');

        return [
            'success' => true,
            'action' => 'created',
            'user_id' => $userId,
            'message' => 'LDAP-Benutzer lokal angelegt.',
        ];
    }

    /**
     * Initiale LDAP-Synchronisierung aus dem Admin.
     *
     * @return array{success: bool, message: string, created: int, updated: int, skipped: int, errors: int, processed: int}
     */
    public function syncDirectoryUsers(int $limit = 250): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'LDAP ist nicht konfiguriert.',
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'processed' => 0,
            ];
        }

        if (!extension_loaded('ldap')) {
            return [
                'success' => false,
                'message' => 'PHP-LDAP-Extension ist nicht installiert.',
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
                'processed' => 0,
            ];
        }

        try {
            $conn = $this->getConnection();
            $conn->connect();
            $conn->auth()->bindAsConfiguredUser();

            $entries = $this->findDirectoryUsers($conn, max(1, min(1000, $limit)));

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = 0;

            foreach ($entries as $entry) {
                $result = $this->syncLocalUserDetailed($entry);
                switch ($result['action']) {
                    case 'created':
                        $created++;
                        break;
                    case 'updated':
                        $updated++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                    default:
                        if (!$result['success']) {
                            $errors++;
                        }
                        break;
                }
            }

            $processed = count($entries);
            $message = sprintf(
                'LDAP-Sync abgeschlossen: %d verarbeitet, %d neu, %d aktualisiert, %d übersprungen, %d Fehler.',
                $processed,
                $created,
                $updated,
                $skipped,
                $errors
            );

            return [
                'success' => true,
                'message' => $message,
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'processed' => $processed,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'LDAP-Sync fehlgeschlagen: ' . $e->getMessage(),
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 1,
                'processed' => 0,
            ];
        }
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

    /**
     * Mehrere Benutzer für einen Erstsync laden.
     *
     * @return array<int, array>
     */
    private function findDirectoryUsers(Connection $conn, int $limit): array
    {
        $filter = $this->buildDirectorySyncFilter();

        $results = $conn->query()
            ->rawFilter($filter)
            ->in(LDAP_BASE_DN)
            ->select(['cn', 'displayname', 'mail', 'samaccountname', 'uid', 'dn'])
            ->limit($limit)
            ->get();

        return is_array($results) ? $results : [];
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

    private function findExistingUserId(string $email, string $dn): ?int
    {
        $db = Database::instance();
        $prefix = $db->getPrefix();

        if ($dn !== '') {
            $stmt = $db->prepare(
                "SELECT user_id FROM {$prefix}user_meta WHERE meta_key = 'ldap_dn' AND meta_value = ? LIMIT 1"
            );
            $stmt->execute([$dn]);
            $metaRow = $stmt->fetch();
            if ($metaRow && isset($metaRow->user_id)) {
                return (int)$metaRow->user_id;
            }
        }

        $stmt = $db->prepare(
            "SELECT id FROM {$prefix}users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $userRow = $stmt->fetch();

        return $userRow && isset($userRow->id) ? (int)$userRow->id : null;
    }

    private function buildDirectorySyncFilter(): string
    {
        if (defined('LDAP_FILTER') && LDAP_FILTER !== '') {
            if (str_contains(LDAP_FILTER, '{username}')) {
                return str_replace('{username}', '*', LDAP_FILTER);
            }

            return LDAP_FILTER;
        }

        return '(&(objectClass=person)(|(mail=*)(uid=*)(sAMAccountName=*)))';
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
