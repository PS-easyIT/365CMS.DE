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
     * Per-Request CSP-Nonce (H-03)
     * Wird in init() einmalig generiert und in allen Templates genutzt.
     */
    private string $cspNonce = '';

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
     * CSP-Nonce für inline <script nonce="..."> und <style nonce="..."> zurückgeben (H-03)
     */
    public function getNonce(): string
    {
        return $this->cspNonce;
    }

    /**
     * Fertiges nonce-Attribut zurückgeben, z. B.: nonce="abc123"
     * Nutzung in Templates: <script <?= Security::instance()->nonceAttr() ?>>
     */
    public function nonceAttr(): string
    {
        return 'nonce="' . htmlspecialchars($this->cspNonce, ENT_QUOTES, 'UTF-8') . '"';
    }
    
    /**
     * Initialize security measures
     * Nonce wird VOR den Headers generiert, damit er in den CSP-Header eingebettet werden kann.
     */
    public function init(): void
    {
        // H-03: Nonce einmalig pro Request generieren
        $this->cspNonce = base64_encode(random_bytes(18)); // 24 Zeichen base64
        $this->setSecurityHeaders();
        $this->startSession();
    }

    private function isHttpsRequest(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if (in_array($forwardedProto, ['https', 'wss'], true)) {
            return true;
        }

        $forwardedSsl = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
        if (in_array($forwardedSsl, ['on', '1', 'true'], true)) {
            return true;
        }

        $frontEndHttps = strtolower((string)($_SERVER['HTTP_FRONT_END_HTTPS'] ?? ''));
        return in_array($frontEndHttps, ['on', '1'], true);
    }

    /**
     * @return array<int, string>
     */
    private function getBaseCspDirectives(string $nonce, bool $includeUpgradeInsecureRequests = true): array
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'nonce-{$nonce}'",
            "img-src 'self' data: https: blob:",
            "font-src 'self' data: https:",
            "connect-src 'self'",
            "media-src 'self' data: blob:",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "manifest-src 'self'",
        ];

        if ($includeUpgradeInsecureRequests && $this->isHttpsRequest()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return $directives;
    }

    private function buildCspPolicy(array $directives): string
    {
        return implode('; ', $directives);
    }

    public function getSecurityHeaderProfile(): array
    {
        $isHttps = $this->isHttpsRequest();
        $hstsEnabled = !CMS_DEBUG && $isHttps;
        $trustedTypesEnforced = !CMS_DEBUG;

        return [
            'https' => $isHttps,
            'csp_mode' => CMS_DEBUG ? 'report-only' : 'enforced',
            'csp_uses_nonce' => true,
            'trusted_types_enforced' => $trustedTypesEnforced,
            'trusted_types_report_only' => CMS_DEBUG,
            'hsts_enabled' => $hstsEnabled,
            'hsts_include_subdomains' => $hstsEnabled,
            'hsts_preload' => $hstsEnabled,
            'hsts_value' => $hstsEnabled ? 'max-age=31536000; includeSubDomains; preload' : '',
        ];
    }
    
    /**
     * Set security headers
     *
     * H-03: Nonce-basierte CSP (kein unsafe-inline mehr)
     * H-04: HSTS mit includeSubDomains
     */
    private function setSecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

            // H-04: HSTS – nur über HTTPS senden, nicht im Debug-Modus
            if (!CMS_DEBUG && $this->isHttpsRequest()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }

            $nonce = $this->cspNonce;
            $enforcedCsp = $this->buildCspPolicy(array_merge(
                $this->getBaseCspDirectives($nonce, true),
                [
                    "trusted-types cms365 default sanitize-html dompurify",
                    "require-trusted-types-for 'script'",
                ]
            ));
            $reportOnlyCsp = $this->buildCspPolicy(array_merge(
                $this->getBaseCspDirectives($nonce, false),
                [
                    "trusted-types cms365 default sanitize-html dompurify",
                    "require-trusted-types-for 'script'",
                ]
            ));

            if (CMS_DEBUG) {
                header('Content-Security-Policy-Report-Only: ' . $reportOnlyCsp);
            } else {
                header('Content-Security-Policy: ' . $enforcedCsp);
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
        
        $valid = hash_equals($stored['token'], $token);

        // Token nach erfolgreicher Prüfung invalidieren (verhindert Replay-Angriffe)
        if ($valid) {
            unset($_SESSION['csrf_tokens'][$action]);
        }

        return $valid;
    }

    /**
     * Verify CSRF token without invalidating it.
     *
     * Nützlich für UI-Komponenten mit vielen Folge-Requests innerhalb derselben
     * Session (z. B. Dateimanager/Connectoren), bei denen ein Einmal-Token für
     * jeden einzelnen Request unpraktisch wäre.
     */
    public function verifyPersistentToken(string $token, string $action = 'default'): bool
    {
        if (!isset($_SESSION['csrf_tokens'][$action])) {
            return false;
        }

        $stored = $_SESSION['csrf_tokens'][$action];

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
    public static function escape(string|int $output): string
    {
        return htmlspecialchars((string)$output, ENT_QUOTES, 'UTF-8');
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
     * Fallback für Kontexte ohne DB. Primär: checkDbRateLimit() verwenden.
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
     * H-05: Rate-Limiting auf Datenbankbasis (IP + Action)
     *
     * Zählt Login-Versuche aus der DB-Tabelle `login_attempts` – erkennt so auch
     * verteilte Brute-Force-Angriffe über mehrere Sessions/Browser/IPs-Ranges.
     *
     * @param  string $ip          Client-IP (aus getClientIp())
     * @param  string $action      Aktion-Bezeichner, z. B. 'login'
     * @param  int    $maxAttempts Maximale Versuche im Zeitfenster
     * @param  int    $timeWindow  Zeitfenster in Sekunden
     * @return bool   true = Versuch erlaubt, false = gesperrt
     */
    public static function checkDbRateLimit(
        string $ip,
        string $action,
        int $maxAttempts = 5,
        int $timeWindow = 300
    ): bool {
        try {
            $db = Database::instance();
            $prefix = $db->getPrefix();
            $since  = date('Y-m-d H:i:s', time() - $timeWindow);
            // H-05: Datenbankbasiertes Zählen – IP + Action-Feld berücksichtigen
            // (schützt auch gegen verteilte Brute-Force über mehrere Sessions)
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS attempt_count
                   FROM {$prefix}login_attempts
                  WHERE ip_address = ?
                    AND action = ?
                    AND attempted_at >= ?"
            );
            $stmt->execute([$ip, $action, $since]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);

            $count = (int) ($row->attempt_count ?? 0);

            // Gelegentlich alte Einträge bereinigen (1:20 Chance pro Request)
            if (random_int(1, 20) === 1) {
                $cutoff = date('Y-m-d H:i:s', time() - max($timeWindow * 4, 86400));
                $del = $db->prepare("DELETE FROM {$prefix}login_attempts WHERE attempted_at < ?");
                $del->execute([$cutoff]);
            }

            return $count < $maxAttempts;

        } catch (\Throwable $e) {
            error_log('Security::checkDbRateLimit() Fehler (Fallback auf Session): ' . $e->getMessage());
            // Fallback auf Session-basiertes Rate-Limiting wenn DB nicht verfügbar
            return self::checkRateLimit('db_rl_' . $action . '_' . md5($ip), $maxAttempts, $timeWindow);
        }
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
