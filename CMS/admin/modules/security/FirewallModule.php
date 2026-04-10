<?php
declare(strict_types=1);

/**
 * FirewallModule – IP-Blocking, Rate-Limiting, Regelverwaltung
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;

class FirewallModule
{
    private const SETTING_KEYS = [
        'firewall_enabled',
        'firewall_rate_limit',
        'firewall_rate_window',
        'firewall_block_duration',
        'firewall_log_enabled',
    ];

    private const SUPPORTED_ACTIONS = ['save_settings', 'add_rule', 'delete_rule', 'toggle_rule'];

    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTable();
    }

    public function isSupportedAction(string $action): bool
    {
        return in_array(trim($action), self::SUPPORTED_ACTIONS, true);
    }

    private function ensureTable(): void
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
    }

    public function getData(): array
    {
        $rules = $this->db->get_results(
            "SELECT * FROM {$this->prefix}firewall_rules ORDER BY created_at DESC"
        ) ?: [];

        // Stats
        $total   = count($rules);
        $active  = 0;
        $blocked = 0;
        $allowed = 0;
        foreach ($rules as $r) {
            if ((int)$r->is_active) $active++;
            if ($r->rule_type === 'block_ip' || $r->rule_type === 'block_range') $blocked++;
            if ($r->rule_type === 'allow_ip') $allowed++;
        }

        // Firewall-Settings (Batch-Abfrage)
        $settings = $this->loadSettings();

        // Letzte blockierte Zugriffe (aus Logs, wenn vorhanden)
        $recentBlocks = [];
        try {
            $recentBlocks = $this->db->get_results(
                "SELECT * FROM {$this->prefix}security_log WHERE action = 'blocked' ORDER BY created_at DESC LIMIT 10"
            ) ?: [];
        } catch (\Exception $e) {}

        return [
            'rules'         => array_map(fn($r) => $this->normalizeRuleRow($r), $rules),
            'stats'         => ['total' => $total, 'active' => $active, 'blocked_ips' => $blocked, 'allowed_ips' => $allowed],
            'settings'      => $settings,
            'recent_blocks' => array_map(fn($r) => (array)$r, $recentBlocks),
        ];
    }

    public function saveSettings(array $post): array
    {
        $keys = [
            'firewall_enabled'        => isset($post['firewall_enabled']) ? '1' : '0',
            'firewall_rate_limit'     => (string)max(10, min(1000, (int)($post['firewall_rate_limit'] ?? 60))),
            'firewall_rate_window'    => (string)max(60, min(3600, (int)($post['firewall_rate_window'] ?? 60))),
            'firewall_block_duration' => (string)max(60, min(86400, (int)($post['firewall_block_duration'] ?? 3600))),
            'firewall_log_enabled'    => isset($post['firewall_log_enabled']) ? '1' : '0',
        ];

        try {
            foreach ($keys as $key => $value) {
                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            // ADDED: Änderungen an Security-Einstellungen zentral protokollieren.
            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'firewall.settings.save',
                'Firewall-Einstellungen gespeichert',
                'setting',
                null,
                $keys,
                'warning'
            );

            return ['success' => true, 'message' => 'Firewall-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            $this->logFailure('firewall.settings.save_failed', 'Firewall-Einstellungen konnten nicht gespeichert werden.', [
                'exception' => $e::class,
            ]);

            return ['success' => false, 'error' => 'Firewall-Einstellungen konnten nicht gespeichert werden.'];
        }
    }

    public function addRule(array $post): array
    {
        $type  = in_array($post['rule_type'] ?? '', ['block_ip', 'block_range', 'allow_ip', 'block_ua', 'block_country'], true)
            ? (string)$post['rule_type'] : 'block_ip';
        $value = $this->sanitizeRuleValue((string)($post['rule_value'] ?? ''));
        if ($value === '') {
            return ['success' => false, 'error' => 'Wert ist erforderlich.'];
        }

        if (in_array($type, ['block_ip', 'allow_ip'], true) && !filter_var($value, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => 'Ungültige IP-Adresse.'];
        }
        if ($type === 'block_range' && !$this->isValidCidrRange($value)) {
            return ['success' => false, 'error' => 'Ungültiger IP-Bereich. Erwartet wird z. B. 192.168.0.0/24.'];
        }
        if ($type === 'block_country') {
            $value = strtoupper($value);
            if (preg_match('/^[A-Z]{2}$/', $value) !== 1) {
                return ['success' => false, 'error' => 'Ungültiger Ländercode. Erwartet wird ein ISO-3166-Code wie DE oder AT.'];
            }
        }
        if ($type === 'block_ua' && (function_exists('mb_strlen') ? mb_strlen($value) : strlen($value)) < 3) {
            return ['success' => false, 'error' => 'User-Agent-Regeln müssen mindestens 3 Zeichen lang sein.'];
        }

        if ($this->ruleExists($type, $value)) {
            return ['success' => false, 'error' => 'Diese Firewall-Regel existiert bereits.'];
        }

        $reason = $this->sanitizeText((string)($post['rule_reason'] ?? ''), 255);
        $expiresAt = $this->normalizeExpiration((string)($post['expires_at'] ?? ''));
        if (($post['expires_at'] ?? '') !== '' && $expiresAt === null) {
            return ['success' => false, 'error' => 'Ungültiges Ablaufdatum.'];
        }

        try {
            $insertId = $this->db->insert('firewall_rules', [
                'rule_type'  => $type,
                'value'      => $value,
                'reason'     => $reason,
                'is_active'  => 1,
                'expires_at' => $expiresAt,
            ]);

            if ($insertId === false) {
                return ['success' => false, 'error' => 'Regel konnte nicht gespeichert werden.'];
            }

            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'firewall.rule.add',
                'Firewall-Regel hinzugefügt',
                'firewall_rule',
                (int)$insertId,
                ['type' => $type, 'value' => $value, 'reason' => $reason, 'expires_at' => $expiresAt],
                'warning'
            );

            return ['success' => true, 'message' => 'Regel hinzugefügt.'];
        } catch (\Throwable $e) {
            $this->logFailure('firewall.rule.add_failed', 'Firewall-Regel konnte nicht hinzugefügt werden.', [
                'type' => $type,
                'exception' => $e::class,
            ]);

            return ['success' => false, 'error' => 'Firewall-Regel konnte nicht hinzugefügt werden.'];
        }
    }

    public function deleteRule(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        $rule = $this->getRuleById($id);
        if ($rule === null) {
            return ['success' => false, 'error' => 'Regel nicht gefunden.'];
        }

        try {
            $deleted = $this->db->delete('firewall_rules', ['id' => $id]);
            if (!$deleted) {
                return ['success' => false, 'error' => 'Regel konnte nicht gelöscht werden.'];
            }
        } catch (\Throwable $e) {
            $this->logFailure('firewall.rule.delete_failed', 'Firewall-Regel konnte nicht gelöscht werden.', [
                'rule_id' => $id,
                'exception' => $e::class,
            ]);

            return ['success' => false, 'error' => 'Regel konnte nicht gelöscht werden.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'firewall.rule.delete',
            'Firewall-Regel gelöscht',
            'firewall_rule',
            $id,
            ['type' => (string)($rule['rule_type'] ?? ''), 'value' => (string)($rule['value'] ?? '')],
            'warning'
        );

        return ['success' => true, 'message' => 'Regel gelöscht.'];
    }

    public function toggleRule(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }

        $rule = $this->db->get_row(
            "SELECT id, rule_type, value, is_active FROM {$this->prefix}firewall_rules WHERE id = ?",
            [$id]
        );
        if (!$rule) {
            return ['success' => false, 'error' => 'Regel nicht gefunden.'];
        }

        $newStatus = (int)$rule->is_active ? 0 : 1;

        try {
            $updated = $this->db->update('firewall_rules', ['is_active' => $newStatus], ['id' => $id]);
            if (!$updated) {
                return ['success' => false, 'error' => 'Regelstatus konnte nicht geändert werden.'];
            }
        } catch (\Throwable $e) {
            $this->logFailure('firewall.rule.toggle_failed', 'Firewall-Regel konnte nicht umgeschaltet werden.', [
                'rule_id' => $id,
                'exception' => $e::class,
            ]);

            return ['success' => false, 'error' => 'Regelstatus konnte nicht geändert werden.'];
        }

        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'firewall.rule.toggle',
            $newStatus ? 'Firewall-Regel aktiviert' : 'Firewall-Regel deaktiviert',
            'firewall_rule',
            $id,
            ['is_active' => $newStatus, 'type' => (string)$rule->rule_type, 'value' => (string)$rule->value],
            'warning'
        );

        return ['success' => true, 'message' => $newStatus ? 'Regel aktiviert.' : 'Regel deaktiviert.'];
    }

    private function loadSettings(): array
    {
        $placeholders = implode(',', array_fill(0, count(self::SETTING_KEYS), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            self::SETTING_KEYS
        ) ?: [];

        $settings = array_fill_keys(self::SETTING_KEYS, '');
        foreach ($rows as $row) {
            $settings[(string)$row->option_name] = (string)$row->option_value;
        }

        return $settings;
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim(strip_tags($value))) ?? '';

        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
    }

    private function sanitizeRuleValue(string $value): string
    {
        return $this->sanitizeText($value, 255);
    }

    private function isValidCidrRange(string $value): bool
    {
        if (preg_match('/^([0-9a-f:.]+)\/(\d{1,3})$/i', $value, $matches) !== 1) {
            return false;
        }

        $ip = (string)$matches[1];
        $mask = (int)$matches[2];

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $mask >= 0 && $mask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $mask >= 0 && $mask <= 128;
        }

        return false;
    }

    private function normalizeExpiration(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false || $timestamp <= time()) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function ruleExists(string $type, string $value): bool
    {
        return (int)$this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}firewall_rules WHERE rule_type = ? AND value = ?",
            [$type, $value]
        ) > 0;
    }

    private function getRuleById(int $id): ?array
    {
        $row = $this->db->get_row(
            "SELECT id, rule_type, value FROM {$this->prefix}firewall_rules WHERE id = ? LIMIT 1",
            [$id]
        );

        return $row ? (array)$row : null;
    }

    private function normalizeRuleRow(object|array $rule): array
    {
        $row = (array)$rule;

        return [
            'id' => (int)($row['id'] ?? 0),
            'rule_type' => (string)($row['rule_type'] ?? ''),
            'value' => (string)($row['value'] ?? ''),
            'reason' => (string)($row['reason'] ?? ''),
            'is_active' => (int)($row['is_active'] ?? 0),
            'expires_at' => (string)($row['expires_at'] ?? ''),
            'created_at' => (string)($row['created_at'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $context */
    private function logFailure(string $action, string $message, array $context = []): void
    {
        Logger::instance()->withChannel('admin.security')->warning($message, $context);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            $action,
            $message,
            'firewall_rule',
            isset($context['rule_id']) ? (int)$context['rule_id'] : null,
            $context,
            'error'
        );
    }
}
