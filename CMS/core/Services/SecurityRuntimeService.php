<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\AuditLogger;
use CMS\Database;
use CMS\Logger;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

final class SecurityRuntimeService
{
    private const MAX_RULES = 500;
    private const DEFAULT_RATE_LIMIT = 60;
    private const DEFAULT_RATE_WINDOW = 60;
    private const DEFAULT_BLOCK_DURATION = 3600;

    private static ?self $instance = null;

    private Database $db;
    private string $prefix;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        try {
            $this->ensureTables();
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('security.runtime')->warning('Firewall-Runtime-Tabellen konnten nicht vorbereitet werden.', [
                'exception' => $e::class,
            ]);
        }
    }

    private function ensureTables(): void
    {
        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}firewall_rules (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                rule_type   VARCHAR(20) NOT NULL DEFAULT 'block_ip',
                value       VARCHAR(255) NOT NULL,
                reason      VARCHAR(255) DEFAULT NULL,
                is_active   TINYINT(1) NOT NULL DEFAULT 1,
                expires_at  DATETIME DEFAULT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type  (rule_type),
                INDEX idx_value (value)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->db->getPdo()->exec(
            "CREATE TABLE IF NOT EXISTS {$this->prefix}security_log (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                request_uri TEXT,
                user_agent TEXT,
                rule_matched VARCHAR(255) DEFAULT NULL,
                user_id INT UNSIGNED DEFAULT NULL,
                extra JSON DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_action (action),
                INDEX idx_ip (ip_address),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function handleRequest(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $settings = $this->loadFirewallSettings();
        if (($settings['firewall_enabled'] ?? '0') !== '1') {
            return;
        }

        $ip = Security::getClientIp();
        $uri = $this->sanitizeLogText((string)($_SERVER['REQUEST_URI'] ?? '/'), 1000);
        $userAgent = $this->sanitizeLogText((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 500);

        try {
            $this->cleanupExpiredRuntimeBlocks();
            $decision = $this->evaluateRules($ip, $userAgent);

            if ($decision['decision'] === 'block') {
                $this->denyRequest($ip, $uri, $userAgent, $decision['rule'], 'Firewall-Regel blockiert den Request.');
            }

            $this->enforceRateLimit($settings, $ip, $uri, $userAgent);

            if ($decision['decision'] === 'allow' && ($settings['firewall_log_enabled'] ?? '0') === '1') {
                $this->logSecurityEvent('allowed', $ip, $uri, $userAgent, $decision['rule'], ['source' => 'allow_rule']);
            }
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('security.runtime')->warning('Firewall-Runtime-Prüfung fehlgeschlagen.', [
                'exception' => $e::class,
            ]);
        }
    }

    /** @return array<string,string> */
    private function loadFirewallSettings(): array
    {
        $keys = [
            'firewall_enabled',
            'firewall_rate_limit',
            'firewall_rate_window',
            'firewall_block_duration',
            'firewall_log_enabled',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $keys
        ) ?: [];

        $settings = [
            'firewall_enabled' => '0',
            'firewall_rate_limit' => (string)self::DEFAULT_RATE_LIMIT,
            'firewall_rate_window' => (string)self::DEFAULT_RATE_WINDOW,
            'firewall_block_duration' => (string)self::DEFAULT_BLOCK_DURATION,
            'firewall_log_enabled' => '0',
        ];

        foreach ($rows as $row) {
            $name = (string)($row->option_name ?? '');
            if (array_key_exists($name, $settings)) {
                $settings[$name] = (string)($row->option_value ?? '');
            }
        }

        return $settings;
    }

    /** @return array{decision:string,rule:string} */
    private function evaluateRules(string $ip, string $userAgent): array
    {
        $rows = $this->db->get_results(
            "SELECT rule_type, value
             FROM {$this->prefix}firewall_rules
             WHERE is_active = 1
               AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY CASE WHEN rule_type = 'allow_ip' THEN 0 ELSE 1 END, id ASC
             LIMIT " . self::MAX_RULES
        ) ?: [];

        $country = strtoupper($this->sanitizeLogText((string)($_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['HTTP_X_APPENGINE_COUNTRY'] ?? ''), 2));

        foreach ($rows as $row) {
            $type = (string)($row->rule_type ?? '');
            $value = trim((string)($row->value ?? ''));
            if ($value === '') {
                continue;
            }

            if ($type === 'allow_ip' && hash_equals($value, $ip)) {
                return ['decision' => 'allow', 'rule' => 'allow_ip:' . $value];
            }

            if ($type === 'block_ip' && hash_equals($value, $ip)) {
                return ['decision' => 'block', 'rule' => 'block_ip:' . $value];
            }

            if ($type === 'block_range' && $this->ipMatchesCidr($ip, $value)) {
                return ['decision' => 'block', 'rule' => 'block_range:' . $value];
            }

            if ($type === 'block_ua' && $userAgent !== '' && stripos($userAgent, $value) !== false) {
                return ['decision' => 'block', 'rule' => 'block_ua:' . $this->sanitizeLogText($value, 80)];
            }

            if ($type === 'block_country' && $country !== '' && hash_equals(strtoupper($value), $country)) {
                return ['decision' => 'block', 'rule' => 'block_country:' . $country];
            }
        }

        return ['decision' => 'none', 'rule' => ''];
    }

    private function enforceRateLimit(array $settings, string $ip, string $uri, string $userAgent): void
    {
        $limit = max(10, min(1000, (int)($settings['firewall_rate_limit'] ?? self::DEFAULT_RATE_LIMIT)));
        $window = max(60, min(3600, (int)($settings['firewall_rate_window'] ?? self::DEFAULT_RATE_WINDOW)));
        $duration = max(60, min(86400, (int)($settings['firewall_block_duration'] ?? self::DEFAULT_BLOCK_DURATION)));
        $since = date('Y-m-d H:i:s', time() - $window);

        $count = (int)($this->db->get_var(
            "SELECT COUNT(*)
             FROM {$this->prefix}security_log
             WHERE ip_address = ?
               AND created_at >= ?
               AND action IN ('allowed', 'blocked', 'rate_limited', 'challenge')",
            [$ip, $since]
        ) ?? 0);

        if ($count >= $limit) {
            $expiresAt = date('Y-m-d H:i:s', time() + $duration);
            $this->createTemporaryBlock($ip, $expiresAt);
            $this->denyRequest($ip, $uri, $userAgent, 'rate_limit:' . $limit . '/' . $window, 'Firewall-Rate-Limit überschritten.', 429, $duration);
        }

        $this->logSecurityEvent('allowed', $ip, $uri, $userAgent, '', ['rate_count' => $count + 1, 'rate_limit' => $limit, 'window' => $window]);
    }

    private function createTemporaryBlock(string $ip, string $expiresAt): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }

        $exists = (int)($this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}firewall_rules WHERE rule_type = 'block_ip' AND value = ? AND is_active = 1",
            [$ip]
        ) ?? 0);
        if ($exists > 0) {
            return;
        }

        $this->db->insert('firewall_rules', [
            'rule_type' => 'block_ip',
            'value' => $ip,
            'reason' => 'Automatisches Rate-Limit',
            'is_active' => 1,
            'expires_at' => $expiresAt,
        ]);
    }

    private function cleanupExpiredRuntimeBlocks(): void
    {
        if (random_int(1, 50) !== 1) {
            return;
        }

        $this->db->execute(
            "DELETE FROM {$this->prefix}firewall_rules WHERE rule_type = 'block_ip' AND reason = ? AND expires_at IS NOT NULL AND expires_at <= NOW()",
            ['Automatisches Rate-Limit']
        );
        $this->db->execute(
            "DELETE FROM {$this->prefix}security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
    }

    private function denyRequest(string $ip, string $uri, string $userAgent, string $rule, string $message, int $status = 403, int $retryAfter = 0): never
    {
        $action = $status === 429 ? 'rate_limited' : 'blocked';
        $this->logSecurityEvent($action, $ip, $uri, $userAgent, $rule, ['status' => $status]);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'firewall.request.' . $action,
            $message,
            'ip',
            null,
            ['ip' => $ip, 'rule' => $rule, 'uri' => $this->sanitizeLogText($uri, 180)],
            'warning'
        );

        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Robots-Tag: noindex, nofollow');
            if ($retryAfter > 0) {
                header('Retry-After: ' . $retryAfter);
            }
        }

        echo $status === 429 ? 'Too many requests.' : 'Request blocked.';
        exit;
    }

    /** @param array<string,mixed> $extra */
    private function logSecurityEvent(string $action, string $ip, string $uri, string $userAgent, string $rule, array $extra = []): void
    {
        $this->db->insert('security_log', [
            'action' => $action,
            'ip_address' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
            'request_uri' => $this->sanitizeLogText($uri, 1000),
            'user_agent' => $this->sanitizeLogText($userAgent, 500),
            'rule_matched' => $this->sanitizeLogText($rule, 255),
            'user_id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
            'extra' => $extra !== [] ? json_encode($this->sanitizeLogContext($extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);
    }

    /** @param array<string,mixed> $context @return array<string,mixed> */
    private function sanitizeLogContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $context[$key] = $this->sanitizeLogText($value, 240);
            } elseif (is_array($value)) {
                $context[$key] = $this->sanitizeLogContext($value);
            }
        }

        return $context;
    }

    private function sanitizeLogText(string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        [$range, $bits] = explode('/', $cidr, 2);
        $bits = (int)$bits;
        if (!filter_var($range, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $rangeBin = inet_pton($range);
        if ($ipBin === false || $rangeBin === false || strlen($ipBin) !== strlen($rangeBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($rangeBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (~((1 << (8 - $remainingBits)) - 1)) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($rangeBin[$fullBytes]) & $mask);
    }
}