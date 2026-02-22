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
     * Check if user is logged in via session
     */
    private function checkSession(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->getUserById($_SESSION['user_id']);
        }
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
            return 'Ungültige Anmeldedaten.';
        }
        
        if (!$security->verifyPassword($password, $user->password)) {
            $this->logLoginAttempt($username);
            // H-01: Fehlgeschlagenen Login protokollieren
            AuditLogger::instance()->loginFailed($username);
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

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        session_regenerate_id(true); // Prevent Fixation
        
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

        // Capabilities Map
        $caps = [
            'member' => ['read', 'edit_profile', 'view_content'],
            'editor' => ['read', 'edit_profile', 'view_content', 'edit_posts', 'publish_posts', 'manage_media'],
            'author' => ['read', 'edit_profile', 'view_content', 'edit_own_posts']
        ];

        return isset($caps[$role]) && in_array($cap, $caps[$role]);
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
     * Logout user
     */
    public function logout(): void
    {
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
     * @return array{secret: string, qr_url: string, otp_uri: string}
     */
    public function setupMfaSecret(int $userId): array
    {
        $db     = Database::instance();
        $totp   = Totp::instance();
        $secret = $totp->generateSecret();

        // Pending-Secret speichern (wird erst nach Code-Bestätigung aktiviert)
        $this->setUserMeta($userId, 'mfa_pending_secret', $secret);

        // Account-Label: Benutzername oder E-Mail
        $user  = $this->getUserById($userId);
        $label = $user ? $user->email : (string)$userId;

        return [
            'secret'  => $secret,
            'qr_url'  => $totp->getQrCodeUrl($secret, $label, SITE_NAME),
            'otp_uri' => $totp->getOtpAuthUri($secret, $label, SITE_NAME),
        ];
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
}
