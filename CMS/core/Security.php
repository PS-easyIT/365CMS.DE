<?php
/**
 * Security Class
 * 
 * Handles CSRF protection, XSS prevention, input sanitization
 * 
 * @package CMSv2\Core
 */

declare(strict_types=1);

namespace CMS;

if (!defined('ABSPATH')) {
    exit;
}

class Security
{
    private static ?self $instance = null;
    
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
     * Initialize security measures
     */
    public function init(): void
    {
        $this->setSecurityHeaders();
        $this->startSession();
    }
    
    /**
     * Set security headers
     */
    private function setSecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
            
            if (!CMS_DEBUG) {
                header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'');
            }
        }
    }
    
    /**
     * Start secure session
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Start session with secure settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_strict_mode', '1');
            session_start();
            
            // Regenerate session ID for security (prevent session fixation)
            if (!isset($_SESSION['initialized'])) {
                session_regenerate_id(true);
                $_SESSION['initialized'] = true;
            }
        }
    }
    
    /**
     * Generate CSRF token field (WP style compatibility)
     */
    public function generateNonceField(string $action = 'default'): void
    {
        $token = $this->generateToken($action);
        echo '<input type="hidden" name="_wpnonce" value="' . $this->escape($token) . '">';
    }

    /**
     * Verify CSRF token (WP style compatibility)
     */
    public function verifyNonce(string $token, string $action = 'default'): bool
    {
        return $this->verifyToken($token, $action);
    }

    /**
     * Generate CSRF token (Alias for generateToken)
     */
    public function createNonce(string $action = 'default'): string
    {
        return $this->generateToken($action);
    }

    /**
     * Generate CSRF token
     */
    public function generateToken(string $action = 'default'): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$action] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyToken(string $token, string $action = 'default'): bool
    {
        if (!isset($_SESSION['csrf_tokens'][$action])) {
            return false;
        }
        
        $stored = $_SESSION['csrf_tokens'][$action];
        
        // Check expiration (1 hour)
        if (time() - $stored['time'] > 3600) {
            unset($_SESSION['csrf_tokens'][$action]);
            return false;
        }
        
        return hash_equals($stored['token'], $token);
    }
    
    /**
     * Sanitize input string (static wrapper)
     */
    public static function sanitize(string $input, string $type = 'text'): string
    {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            
            case 'int':
                return (string) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            case 'username':
                // Username: alphanumeric + underscore only
                return preg_replace('/[^a-zA-Z0-9_]/', '', $input);
            
            case 'text':
            default:
                return strip_tags(trim($input));
        }
    }
    
    /**
     * Escape output for HTML (static wrapper)
     */
    public static function escape(string $output): string
    {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email (static wrapper)
     */
    public static function validateEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate URL (static wrapper)
     */
    public static function validateUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Hash password (static wrapper)
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password (static wrapper)
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Rate limiting check (static wrapper with session)
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset if time window passed
        if (time() - $data['first_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'first_attempt' => time()
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        return true;
    }
    
    /**
     * Get client IP address (static wrapper)
     */
    public static function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '0.0.0.0';
    }
}
