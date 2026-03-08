<?php
/**
 * AuthManager – Zentraler Authentifizierungs-Dispatcher
 *
 * Koordiniert die verschiedenen Auth-Provider:
 *  - Session-basiert (Standard via CMS\Auth)
 *  - Passkey / WebAuthn (via WebAuthnAdapter)
 *  - LDAP (via LdapAuthProvider)
 *  - MFA: TOTP + Backup-Codes
 *  - JWT für API-Authentifizierung (via JwtService)
 *
 * @package CMSv2\Core\Auth
 */

declare(strict_types=1);

namespace CMS\Auth;

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Auth\MFA\TotpAdapter;
use CMS\Auth\MFA\BackupCodesManager;
use CMS\Auth\Passkey\WebAuthnAdapter;
use CMS\Auth\LDAP\LdapAuthProvider;
use CMS\AuditLogger;
use CMS\Database;
use CMS\Hooks;

final class AuthManager
{
    private static ?self $instance = null;

    private Auth $sessionAuth;
    private ?WebAuthnAdapter $passkeyAdapter = null;
    private ?LdapAuthProvider $ldapProvider = null;
    private ?TotpAdapter $totpAdapter = null;
    private ?BackupCodesManager $backupCodes = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->sessionAuth = Auth::instance();
    }

    // ── Provider-Zugriff (Lazy Loading) ──────────────────────────────────────

    public function passkey(): WebAuthnAdapter
    {
        return $this->passkeyAdapter ??= WebAuthnAdapter::instance();
    }

    public function ldap(): LdapAuthProvider
    {
        return $this->ldapProvider ??= LdapAuthProvider::instance();
    }

    public function totp(): TotpAdapter
    {
        return $this->totpAdapter ??= TotpAdapter::instance();
    }

    public function backupCodes(): BackupCodesManager
    {
        return $this->backupCodes ??= BackupCodesManager::instance();
    }

    // ── Standard-Login ───────────────────────────────────────────────────────

    /**
     * Standard-Login über Benutzername/Passwort.
     * Delegiert an CMS\Auth::login() – gibt true, 'MFA_REQUIRED' oder Fehlertext zurück.
     */
    public function login(string $username, string $password): bool|string
    {
        return $this->sessionAuth->login($username, $password);
    }

    // ── Passkey-Login ────────────────────────────────────────────────────────

    /**
     * Passkey-Challenge-Optionen generieren (für navigator.credentials.get).
     *
     * @param int|null $userId  Wenn bekannt, nur Credentials dieses Users
     * @return array{options: \stdClass, challenge: string}
     */
    public function getPasskeyLoginOptions(?int $userId = null): array
    {
        $adapter = $this->passkey();
        $credentialIds = $userId !== null
            ? $adapter->getCredentialIdsForUser($userId)
            : [];

        return $adapter->getAuthenticationOptions($credentialIds);
    }

    /**
     * Passkey-Antwort validieren und Session starten.
     *
     * @param string $clientDataJSON      Base64-dekodiert vom Client
     * @param string $authenticatorData   Base64-dekodiert vom Client
     * @param string $signature           Base64-dekodiert vom Client
     * @param string $credentialId        Credential-ID (Base64url)
     * @param string $challenge           Gespeicherte Challenge (hex)
     * @return bool|string true bei Erfolg, Fehlertext sonst
     */
    public function authenticateViaPasskey(
        string $clientDataJSON,
        string $authenticatorData,
        string $signature,
        string $credentialId,
        string $challenge
    ): bool|string {
        $adapter = $this->passkey();

        $credential = $adapter->findCredentialById($credentialId);
        if ($credential === null) {
            return 'Passkey nicht gefunden.';
        }

        $result = $adapter->processAuthentication(
            $clientDataJSON,
            $authenticatorData,
            $signature,
            $credential['public_key'],
            $challenge,
            (int)$credential['sign_count']
        );

        if ($result !== true) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'passkey_login_failed',
                'Passkey-Login fehlgeschlagen: ' . $result,
                'user',
                (int)$credential['user_id']
            );
            return $result;
        }

        // Sign-Counter aktualisieren
        $newCounter = $adapter->getLastSignatureCounter();
        if ($newCounter !== null) {
            $adapter->updateSignCount((int)$credential['id'], $newCounter);
        }

        // Session starten (ohne Passwort-Check, da Passkey verifiziert)
        $this->startSessionForUser((int)$credential['user_id']);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'passkey_login_success',
            'Passkey-Login erfolgreich.',
            'user',
            (int)$credential['user_id']
        );

        return true;
    }

    // ── LDAP-Login ───────────────────────────────────────────────────────────

    /**
     * Authentifizierung über LDAP-Server.
     * Bei Erfolg wird der Nutzer in der lokalen DB angelegt/synchronisiert
     * und eine Session gestartet.
     *
     * @return bool|string true bei Erfolg, Fehlertext sonst
     */
    public function authenticateViaLdap(string $username, string $password): bool|string
    {
        if (!$this->isLdapEnabled()) {
            return 'LDAP ist nicht konfiguriert.';
        }

        $ldap = $this->ldap();

        $ldapUser = $ldap->authenticate($username, $password);
        if ($ldapUser === null) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'ldap_login_failed',
                'LDAP-Login fehlgeschlagen für: ' . $username,
                'ip'
            );
            return 'LDAP-Anmeldung fehlgeschlagen.';
        }

        // Lokalen Benutzer anlegen oder synchronisieren
        $userId = $ldap->syncLocalUser($ldapUser);
        if ($userId === null) {
            return 'Lokale Benutzersynchronisation fehlgeschlagen.';
        }

        // MFA-Check
        if ($this->sessionAuth->isMfaEnabled($userId)) {
            $_SESSION['mfa_pending_user_id'] = $userId;
            return 'MFA_REQUIRED';
        }

        $this->startSessionForUser($userId);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'ldap_login_success',
            'LDAP-Login erfolgreich für: ' . $username,
            'user',
            $userId
        );

        return true;
    }

    // ── MFA-Verifizierung ────────────────────────────────────────────────────

    /**
     * MFA-Code verifizieren (TOTP oder Backup-Code).
     * Muss nach einem Login mit 'MFA_REQUIRED'-Antwort aufgerufen werden.
     *
     * @return bool|string true bei Erfolg, Fehlertext sonst
     */
    public function verifyMfa(string $code): bool|string
    {
        $userId = (int)($_SESSION['mfa_pending_user_id'] ?? 0);
        if ($userId === 0) {
            return 'Keine MFA-Verifizierung ausstehend.';
        }

        // Zuerst TOTP prüfen
        if ($this->totp()->verifyCode($userId, $code)) {
            unset($_SESSION['mfa_pending_user_id']);
            $this->startSessionForUser($userId);
            return true;
        }

        // Dann Backup-Code prüfen
        if ($this->backupCodes()->verify($userId, $code)) {
            unset($_SESSION['mfa_pending_user_id']);
            $this->startSessionForUser($userId);

            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'mfa_backup_code_used',
                'Backup-Code verwendet. Verbleibend: ' . $this->backupCodes()->getRemainingCount($userId),
                'user',
                $userId,
                [],
                'warning'
            );

            return true;
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'mfa_verification_failed',
            'MFA-Verifizierung fehlgeschlagen.',
            'user',
            $userId,
            [],
            'warning'
        );

        return 'Ungültiger Code.';
    }

    // ── Provider-Status ──────────────────────────────────────────────────────

    /**
     * Verfügbare Auth-Provider abfragen.
     *
     * @return array<string, bool>
     */
    public function getAvailableProviders(): array
    {
        return [
            'session'  => true,
            'passkey'  => $this->isPasskeyAvailable(),
            'ldap'     => $this->isLdapEnabled(),
            'totp'     => true,
            'backup'   => true,
        ];
    }

    public function isPasskeyAvailable(): bool
    {
        return function_exists('openssl_open')
            && defined('SITE_URL')
            && !empty(SITE_URL)
            && $this->passkey()->isAvailable();
    }

    public function isLdapEnabled(): bool
    {
        return defined('LDAP_HOST')
            && LDAP_HOST !== ''
            && extension_loaded('ldap');
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    /**
     * Session für einen User starten (nach erfolgreicher Auth-Prüfung).
     */
    private function startSessionForUser(int $userId): void
    {
        $db = Database::instance();
        $stmt = $db->prepare(
            "SELECT id, role, status FROM {$db->getPrefix()}users WHERE id = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return;
        }

        $_SESSION['user_id']            = $user->id;
        $_SESSION['user_role']          = $user->role;
        $_SESSION['session_start_time'] = time();
        session_regenerate_id(true);

        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $userId]);

        Hooks::doAction('user_logged_in', $userId);
    }
}
