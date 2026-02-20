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
        
        // Rate limiting
        if (!$security->checkRateLimit('login_' . $security->getClientIp(), MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
            return 'Zu viele Login-Versuche. Bitte warten Sie ' . (LOGIN_TIMEOUT / 60) . ' Minuten.';
        }
        
        $username = $security->sanitize($username);
        
        $stmt = $db->prepare("SELECT * FROM {$db->getPrefix()}users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetchObject();
        
        if (!$user) {
            $this->logLoginAttempt($username);
            return 'Ungültige Anmeldedaten.';
        }
        
        if (!$security->verifyPassword($password, $user->password)) {
            $this->logLoginAttempt($username);
            return 'Ungültige Anmeldedaten.';
        }
        
        if ($user->status !== 'active') {
            return 'Ihr Konto ist deaktiviert.';
        }
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role;
        session_regenerate_id(true); // Prevent Fixation
        
        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user->id]);
        $this->currentUser = $user;
        
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
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->getPrefix()}users WHERE username = ?");;
        $stmt->execute([$username]);
        if ($stmt->fetch()->count > 0) {
            return 'Benutzername bereits vergeben.';
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$db->getPrefix()}users WHERE email = ?");;
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
        $stmt = $db->prepare("SELECT * FROM {$db->getPrefix()}users WHERE id = ? AND status = 'active' LIMIT 1");;
        $stmt->execute([$id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Log login attempt (for security tracking)
     */
    private function logLoginAttempt(string $username): void
    {
        $db = Database::instance();
        $security = Security::instance();
        
        $db->insert('login_attempts', [
            'username' => $username,
            'ip_address' => $security->getClientIp()
        ]);
    }
}
