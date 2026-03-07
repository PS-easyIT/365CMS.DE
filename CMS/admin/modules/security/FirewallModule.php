<?php
declare(strict_types=1);

/**
 * FirewallModule – IP-Blocking, Rate-Limiting, Regelverwaltung
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;

class FirewallModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->ensureTable();
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
        $settingKeys = ['firewall_enabled', 'firewall_rate_limit', 'firewall_rate_window', 'firewall_block_duration', 'firewall_log_enabled'];
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingKeys
        ) ?: [];
        $settings = array_fill_keys($settingKeys, '');
        foreach ($rows as $row) {
            $settings[$row->option_name] = $row->option_value;
        }

        // Letzte blockierte Zugriffe (aus Logs, wenn vorhanden)
        $recentBlocks = [];
        try {
            $recentBlocks = $this->db->get_results(
                "SELECT * FROM {$this->prefix}security_log WHERE action = 'blocked' ORDER BY created_at DESC LIMIT 10"
            ) ?: [];
        } catch (\Exception $e) {}

        return [
            'rules'         => array_map(fn($r) => (array)$r, $rules),
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
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function addRule(array $post): array
    {
        $type  = in_array($post['rule_type'] ?? '', ['block_ip', 'block_range', 'allow_ip', 'block_ua', 'block_country'], true)
            ? $post['rule_type'] : 'block_ip';
        $value = trim($post['rule_value'] ?? '');
        if ($value === '') {
            return ['success' => false, 'error' => 'Wert ist erforderlich.'];
        }
        // FIX: Regelwerte abhängig vom Typ strikt validieren.
        if (in_array($type, ['block_ip', 'allow_ip'], true) && !filter_var($value, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => 'Ungültige IP-Adresse.'];
        }
        if ($type === 'block_range' && preg_match('/^([0-9a-f:.]+)\/(\d{1,3})$/i', $value, $matches) !== 1) {
            return ['success' => false, 'error' => 'Ungültiger IP-Bereich. Erwartet wird z. B. 192.168.0.0/24.'];
        }
        if ($type === 'block_country') {
            $value = strtoupper($value);
            if (preg_match('/^[A-Z]{2}$/', $value) !== 1) {
                return ['success' => false, 'error' => 'Ungültiger Ländercode. Erwartet wird ein ISO-3166-Code wie DE oder AT.'];
            }
        }

        $reason    = strip_tags($post['rule_reason'] ?? '');
        $expiresAt = !empty($post['expires_at']) ? $post['expires_at'] : null;

        try {
            $this->db->insert('firewall_rules', [
                'rule_type'  => $type,
                'value'      => $value,
                'reason'     => $reason,
                'is_active'  => 1,
                'expires_at' => $expiresAt,
            ]);

            AuditLogger::instance()->log(
                AuditLogger::CAT_SECURITY,
                'firewall.rule.add',
                'Firewall-Regel hinzugefügt',
                'firewall_rule',
                null,
                ['type' => $type, 'value' => $value, 'reason' => $reason, 'expires_at' => $expiresAt],
                'warning'
            );

            return ['success' => true, 'message' => 'Regel hinzugefügt.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function deleteRule(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $this->db->delete('firewall_rules', ['id' => $id]);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'firewall.rule.delete',
            'Firewall-Regel gelöscht',
            'firewall_rule',
            $id,
            [],
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
            "SELECT is_active FROM {$this->prefix}firewall_rules WHERE id = ?",
            [$id]
        );
        if (!$rule) {
            return ['success' => false, 'error' => 'Regel nicht gefunden.'];
        }
        $newStatus = (int)$rule->is_active ? 0 : 1;
        $this->db->update('firewall_rules', ['is_active' => $newStatus], ['id' => $id]);
        AuditLogger::instance()->log(
            AuditLogger::CAT_SECURITY,
            'firewall.rule.toggle',
            $newStatus ? 'Firewall-Regel aktiviert' : 'Firewall-Regel deaktiviert',
            'firewall_rule',
            $id,
            ['is_active' => $newStatus],
            'warning'
        );
        return ['success' => true, 'message' => $newStatus ? 'Regel aktiviert.' : 'Regel deaktiviert.'];
    }
}
