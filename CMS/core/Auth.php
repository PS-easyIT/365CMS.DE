<?php
/**
 * Authentication Class
 * 
 * Handles user login, registration, session management
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Auth
{
    private const DEVICE_COOKIE_NAME = 'cms_device';
    private const DEVICE_COOKIE_TTL = 7200;

    private static ?self $instance = null;
    private ?object $currentUser = null;
    
    /**
     * Singleton instance
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct()
    {
        $this->checkSession();
    }
    
    /**
     * M-17: Session-Lifetime-Limits.
     * Admin: 8 Stunden, Member: 30 Tage.
     */
    private const SESSION_LIFETIME_ADMIN  = 28_800;   // 8 h
    private const SESSION_LIFETIME_MEMBER = 2_592_000; // 30 Tage

    /**
     * Check if user is logged in via session
     * M-17: Session-Lifetime nach Rolle prüfen.
     */
    private function checkSession(): void
    {
        if (!isset($_SESSION['user_id'])) {
            return;
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->forceExpireSession();
            return;
        }

        // M-17: Maximale Lebensdauer je Rolle
        $role        = (string)($_SESSION['user_role'] ?? 'member');
        $maxLifetime = ($role === 'admin')
            ? self::SESSION_LIFETIME_ADMIN
            : self::SESSION_LIFETIME_MEMBER;

        $sessionStart = (int)($_SESSION['session_start_time'] ?? 0);

        if ($sessionStart === 0 || (time() - $sessionStart) > $maxLifetime) {
            // Session abgelaufen oder kein Startzeit-Stempel → sauber beenden
            $this->forceExpireSession();
            return;
        }

        if (!$this->hasValidDeviceCookie($userId)) {
            $this->bindCurrentSessionToDeviceCookie($userId);
        }

        $this->currentUser = $this->getUserById((int)$_SESSION['user_id']);
        if ($this->currentUser === null) {
            $this->forceExpireSession();
        }
    }

    /**
     * M-17: Session ohne Redirect sauber invalidieren
     * (kein header()-Aufruf – checkSession läuft im Konstruktor).
     * Nur aufrufen wenn session_start() bereits erfolgt ist.
     */
    private function forceExpireSession(): void
    {
        $this->clearDeviceCookie();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->currentUser = null;
            return;
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        $this->currentUser = null;
    }
    
    /**
     * Login user
     */
    public function login(string $username, string $password): bool|string
    {
        $security = Security::instance();
        $db = Database::instance();
        
        // H-05: DB-basiertes Rate-Limiting (schützt auch gegen verteilte Brute-Force)
        if (!$security->checkDbRateLimit($security->getClientIp(), 'login', MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'login_blocked',
                'Brute-Force-Sperre für IP: ' . $security->getClientIp(),
                'ip',
                null,
                ['ip' => $security->getClientIp()],
                'warning'
            );
            return 'Zu viele Login-Versuche. Bitte warten Sie ' . (LOGIN_TIMEOUT / 60) . ' Minuten.';
        }
        
        $username = $security->sanitize($username);
        
        $stmt = $db->prepare("SELECT * FROM {$db->getPrefix()}users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetchObject();
        
        if (!$user) {
            $this->logLoginAttempt($username);
            // H-01: Fehlgeschlagenen Login protokollieren
            AuditLogger::instance()->loginFailed($username);
            if (function_exists('cms_log')) {
                cms_log('notice', "Login fehlgeschlagen: Benutzer '{$username}' nicht gefunden", ['category' => 'auth', 'username' => $username]);
            }
            return 'Ungültige Anmeldedaten.';
        }
        
        if (!$security->verifyPassword($password, $user->password)) {
            $this->logLoginAttempt($username);
            // H-01: Fehlgeschlagenen Login protokollieren
            AuditLogger::instance()->loginFailed($username);
            if (function_exists('cms_log')) {
                cms_log('notice', "Login fehlgeschlagen: Falsches Passwort für '{$username}'", ['category' => 'auth', 'username' => $username]);
            }
            return 'Ungültige Anmeldedaten.';
        }
        
        if ($user->status !== 'active') {
            return 'Ihr Konto ist deaktiviert.';
        }

        // H-02: MFA-Check – wenn TOTP aktiv, Pending-Session setzen statt direkt einloggen
        if ($this->isMfaEnabled((int)$user->id)) {
            $_SESSION['mfa_pending_user_id'] = (int)$user->id;
            return 'MFA_REQUIRED';
        }

        session_regenerate_id(true); // Prevent Fixation
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['session_start_time'] = time(); // M-17: Startzeitstempel für Lifetime-Prüfung
        $this->bindCurrentSessionToDeviceCookie((int)$user->id);
        
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user->id]);
        $this->currentUser = $user;

        // H-01: Erfolgreichen Login protokollieren
        AuditLogger::instance()->loginSuccess($username);
        
        return true;
    }

    /**
     * Check Capability (Advanced Support)
     */
    public function hasCapability(string $cap): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $role = $this->currentUser->role;

        // Admin has all caps
        if ($role === 'admin') {
            return true;
        }

        $db = Database::instance();

        try {
            $granted = $db->get_var(
                "SELECT granted FROM {$db->getPrefix()}role_permissions WHERE role = ? AND capability = ? LIMIT 1",
                [$role, $cap]
            );

            if ($granted !== null) {
                return (bool)$granted;
            }
        } catch (\Throwable $e) {
        }

        $caps = [
            'member' => ['read', 'edit_profile', 'view_content'],
            'editor' => ['read', 'edit_profile', 'view_content', 'edit_posts', 'publish_posts', 'manage_media'],
            'author' => ['read', 'edit_profile', 'view_content', 'edit_own_posts']
        ];

        return isset($caps[$role]) && in_array($cap, $caps[$role], true);
    }

    /**
     * Get Current User
     */
    public function currentUser(): ?object
    {
        return $this->currentUser;
    }
    
    /**
     * Register new user
     */
    public function register(array $data): bool|string
    {
        $security = Security::instance();
        $db = Database::instance();
        
        // Validate required fields
        $required = ['username', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return "Feld '{$field}' ist erforderlich.";
            }
        }
        
        // Sanitize
        $username = $security->sanitize($data['username']);
        $email = $security->sanitize($data['email'], 'email');
        
        // Validate email
        if (!$security->validateEmail($email)) {
            return 'Ungültige E-Mail-Adresse.';
        }

        // M-18: Passwort-Policy prüfen (min. 12 Zeichen, Komplexität)
        $policyResult = self::validatePasswordPolicy($data['password']);
        if ($policyResult !== true) {
            return $policyResult;
        }

        // Check if username exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->getPrefix()}users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()->count > 0) {
            return 'Benutzername bereits vergeben.';
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->getPrefix()}users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()->count > 0) {
            return 'E-Mail-Adresse bereits registriert.';
        }
        
        // Create user
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $security->hashPassword($data['password']),
            'display_name' => $data['display_name'] ?? $username,
            'role' => 'member',
            'status' => 'active'
        ]);
        
        if ($userId) {
            Hooks::doAction('user_registered', $userId);
            return true;
        }
        
        return 'Registrierung fehlgeschlagen.';
    }

    /**
     * M-18: Passwort-Policy validieren.
     *
     * Anforderungen:
     *  - Mindestlänge: 12 Zeichen
     *  - Mindestens 1 Großbuchstabe (A-Z)
     *  - Mindestens 1 Kleinbuchstabe (a-z)
     *  - Mindestens 1 Ziffer (0-9)
     *  - Mindestens 1 Sonderzeichen (!@#$%^&* etc.)
     *
     * @param  string   $password Das zu prüfende Passwort (Klartext).
     * @return true|string        true wenn gültig, Fehlermeldung sonst.
     */
    public static function validatePasswordPolicy(string $password): true|string
    {
        if (strlen($password) < 12) {
            return 'Das Passwort muss mindestens 12 Zeichen lang sein.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Das Passwort muss mindestens einen Großbuchstaben enthalten.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Das Passwort muss mindestens einen Kleinbuchstaben enthalten.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'Das Passwort muss mindestens eine Ziffer (0–9) enthalten.';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'Das Passwort muss mindestens ein Sonderzeichen enthalten (z. B. !@#$%^&*).';
        }

        return true;
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->clearDeviceCookie();

        // Clear session data
        $_SESSION = [];
        
        // Expire the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
        $this->currentUser = null;
    }

    public function bindCurrentSessionToDeviceCookie(int $userId): void
    {
        if ($userId <= 0 || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionId = session_id();
        if ($sessionId === '') {
            return;
        }

        $issuedAt = time();
        $expiresAt = $issuedAt + self::DEVICE_COOKIE_TTL;
        $payload = implode('|', [
            (string)$userId,
            (string)$issuedAt,
            (string)$expiresAt,
            hash('sha256', $sessionId),
        ]);
        $signature = hash_hmac('sha256', $payload, $this->getDeviceCookieSigningKey());
        $cookieValue = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');

        $this->setDeviceCookie($cookieValue, $expiresAt);
    }
    
    /**
     * Check if user is logged in (static wrapper)
     */
    public static function isLoggedIn(): bool
    {
        return self::instance()->currentUser !== null;
    }
    
    /**
     * Check if current user has role (static wrapper)
     */
    public static function hasRole(string $role): bool
    {
        $instance = self::instance();
        if (!$instance->currentUser) {
            return false;
        }
        
        return $instance->currentUser->role === $role;
    }
    
    /**
     * Check if current user is admin (static wrapper)
     */
    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }
    
    /**
     * Get current user (static wrapper)
     */
    public static function getCurrentUser(): ?object
    {
        return self::instance()->currentUser;
    }
    
    /**
     * Get user by ID
     */
    private function getUserById(int $id): ?object
    {
        $db = Database::instance();
        $stmt = $db->prepare("SELECT * FROM {$db->getPrefix()}users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Log login attempt (for security tracking)
     *
     * H-05: action-Feld für DB-basiertes Rate-Limiting mitschreiben
     */
    private function logLoginAttempt(string $username, string $action = 'login'): void
    {
        $db = Database::instance();
        $security = Security::instance();
        
        $db->insert('login_attempts', [
            'username'     => $username,
            'ip_address'   => $security->getClientIp(),
            'action'       => $action,
        ]);
    }

    // ── H-02: TOTP / MFA ─────────────────────────────────────────────────────

    /**
     * Prüft ob MFA für den Nutzer aktiv ist.
     * MFA-Status wird in user_meta gespeichert (Key: mfa_enabled).
     */
    public function isMfaEnabled(int $userId): bool
    {
        return $this->getUserMeta($userId, 'mfa_enabled') === '1';
    }

    /**
     * Beginnt den MFA-Einrichtungsflow: erzeugt ein neues TOTP-Secret,
     * speichert es als "pending" (noch nicht aktiv) in user_meta und
     * gibt die Setup-Daten für das Frontend zurück.
     *
     * @return array{secret: string, qr_url: string, otp_uri: string, qr_data_uri: string}
     */
    public function setupMfaSecret(int $userId): array
    {
        // Account-Label: Benutzername oder E-Mail
        $user  = $this->getUserById($userId);
        $label = $user ? $user->email : (string)$userId;

        return \CMS\Auth\MFA\TotpAdapter::instance()->startSetup($userId, $label);
    }

    /**
     * Bestätigt die MFA-Einrichtung: prüft den ersten TOTP-Code gegen das
     * Pending-Secret und aktiviert MFA, wenn der Code korrekt ist.
     */
    public function confirmMfaSetup(int $userId, string $code): bool
    {
        $pendingSecret = $this->getUserMeta($userId, 'mfa_pending_secret');
        if (!$pendingSecret) {
            return false;
        }

        if (!Totp::instance()->verifyCode($pendingSecret, $code)) {
            return false;
        }

        // MFA aktivieren: Active-Secret setzen + Pending-Secret löschen
        $this->setUserMeta($userId, 'mfa_secret', $pendingSecret);
        $this->setUserMeta($userId, 'mfa_enabled', '1');
        $this->deleteUserMeta($userId, 'mfa_pending_secret');

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'mfa_enabled',
            'MFA (TOTP) aktiviert.',
            'user',
            $userId,
            [],
            'info'
        );

        return true;
    }

    /**
     * Verifiziert einen TOTP-Code gegen das gespeicherte Secret.
     * Wird beim Login-Challenge aufgerufen.
     */
    public function verifyMfaCode(int $userId, string $code): bool
    {
        $secret = $this->getUserMeta($userId, 'mfa_secret');
        if (!$secret) {
            return false;
        }

        return Totp::instance()->verifyCode($secret, $code);
    }

    /**
     * Deaktiviert MFA für einen Nutzer und löscht alle MFA-Meta-Keys.
     */
    public function disableMfa(int $userId): void
    {
        $this->deleteUserMeta($userId, 'mfa_secret');
        $this->deleteUserMeta($userId, 'mfa_enabled');
        $this->deleteUserMeta($userId, 'mfa_pending_secret');

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'mfa_disabled',
            'MFA (TOTP) deaktiviert.',
            'user',
            $userId,
            [],
            'warning'
        );
    }

    /**
     * Liest einen einzelnen user_meta-Wert.
     */
    private function getUserMeta(int $userId, string $key): ?string
    {
        $db   = Database::instance();
        $stmt = $db->prepare(
            "SELECT meta_value FROM {$db->getPrefix()}user_meta WHERE user_id = ? AND meta_key = ? LIMIT 1"
        );
        $stmt->execute([$userId, $key]);
        $row = $stmt->fetch();
        return $row ? (string)$row->meta_value : null;
    }

    /**
     * Setzt (INSERT OR UPDATE) einen user_meta-Wert.
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

    /**
     * Löscht einen user_meta-Eintrag.
     */
    private function deleteUserMeta(int $userId, string $key): void
    {
        $db = Database::instance();
        $db->execute(
            "DELETE FROM {$db->getPrefix()}user_meta WHERE user_id = ? AND meta_key = ?",
            [$userId, $key]
        );
    }

    private function hasValidDeviceCookie(int $userId): bool
    {
        $rawCookie = trim((string)($_COOKIE[self::DEVICE_COOKIE_NAME] ?? ''));
        if ($rawCookie === '' || session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $normalizedCookie = strtr($rawCookie, '-_', '+/');
        $padding = strlen($normalizedCookie) % 4;
        if ($padding !== 0) {
            $normalizedCookie .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalizedCookie, true);
        if (!is_string($decoded) || $decoded === '') {
            return false;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 5) {
            return false;
        }

        [$cookieUserId, $issuedAt, $expiresAt, $sessionHash, $signature] = $parts;
        $payload = implode('|', [$cookieUserId, $issuedAt, $expiresAt, $sessionHash]);
        $expectedSignature = hash_hmac('sha256', $payload, $this->getDeviceCookieSigningKey());

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $now = time();
        if ((int)$cookieUserId !== $userId || (int)$expiresAt < $now || (int)$issuedAt > $now) {
            return false;
        }

        $sessionId = session_id();
        if ($sessionId === '') {
            return false;
        }

        return hash_equals($sessionHash, hash('sha256', $sessionId));
    }

    private function getDeviceCookieSigningKey(): string
    {
        $jwtSecret = defined('JWT_SECRET') ? trim((string)JWT_SECRET) : '';
        if ($jwtSecret !== '') {
            return $jwtSecret;
        }

        $secureAuthKey = defined('SECURE_AUTH_KEY') ? trim((string)SECURE_AUTH_KEY) : '';
        if ($secureAuthKey !== '') {
            return $secureAuthKey;
        }

        $authKey = defined('AUTH_KEY') ? trim((string)AUTH_KEY) : '';
        if ($authKey !== '') {
            return $authKey;
        }

        return hash('sha256', __FILE__ . PHP_VERSION);
    }

    private function setDeviceCookie(string $value, int $expiresAt): void
    {
        $params = session_get_cookie_params();
        $cookieDomain = $this->resolveCookieDomain((string)($params['domain'] ?? ''));
        setcookie(self::DEVICE_COOKIE_NAME, $value, [
            'expires' => $expiresAt,
            'path' => $params['path'] ?? '/',
            'domain' => $cookieDomain,
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => (string)($params['samesite'] ?? 'Lax'),
        ]);
        $_COOKIE[self::DEVICE_COOKIE_NAME] = $value;
    }

    private function clearDeviceCookie(): void
    {
        $params = session_get_cookie_params();
        $cookieDomain = $this->resolveCookieDomain((string)($params['domain'] ?? ''));
        setcookie(self::DEVICE_COOKIE_NAME, '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $cookieDomain,
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => true,
            'samesite' => (string)($params['samesite'] ?? 'Lax'),
        ]);
        unset($_COOKIE[self::DEVICE_COOKIE_NAME]);
    }

    private function resolveCookieDomain(string $configuredDomain = ''): string
    {
        $domain = strtolower(trim($configuredDomain));
        if ($domain !== '') {
            return ltrim($domain, '.');
        }

        $host = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
        if ($host === '') {
            return '';
        }

        if (str_contains($host, ':')) {
            $host = explode(':', $host, 2)[0];
        }

        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }

        return preg_replace('/^www\./i', '', $host) ?? '';
    }
}
