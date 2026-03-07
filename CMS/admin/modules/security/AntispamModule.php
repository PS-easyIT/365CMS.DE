<?php
declare(strict_types=1);

/**
 * AntispamModule – Spam-Filter, Honeypot, Blacklists
 */

if (!defined('ABSPATH')) {
    exit;
}

class AntispamModule
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
            "CREATE TABLE IF NOT EXISTS {$this->prefix}spam_blacklist (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type        VARCHAR(20) NOT NULL DEFAULT 'word',
                value       VARCHAR(255) NOT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function getData(): array
    {
        $blacklist = $this->db->get_results(
            "SELECT * FROM {$this->prefix}spam_blacklist ORDER BY type, value"
        ) ?: [];

        // Settings (Batch-Abfrage)
        $settingKeys = ['antispam_enabled', 'antispam_honeypot', 'antispam_min_time', 'antispam_max_links', 'antispam_block_empty_ua', 'antispam_recaptcha_key', 'antispam_recaptcha_secret'];
        $placeholders = implode(',', array_fill(0, count($settingKeys), '?'));
        $rows = $this->db->get_results(
            "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ({$placeholders})",
            $settingKeys
        ) ?: [];
        $settings = array_fill_keys($settingKeys, '');
        foreach ($rows as $row) {
            $settings[$row->option_name] = $row->option_value;
        }

        // Spam-Statistiken
        $spamCount = 0;
        try {
            $spamCount = (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}comments WHERE status = 'spam'"
            );
        } catch (\Exception $e) {}

        $wordCount = 0;
        $emailCount = 0;
        $ipCount = 0;
        foreach ($blacklist as $b) {
            if ($b->type === 'word')  $wordCount++;
            if ($b->type === 'email') $emailCount++;
            if ($b->type === 'ip')    $ipCount++;
        }

        return [
            'blacklist' => array_map(fn($b) => (array)$b, $blacklist),
            'settings'  => $settings,
            'stats'     => [
                'spam_comments' => $spamCount,
                'words'         => $wordCount,
                'emails'        => $emailCount,
                'ips'           => $ipCount,
            ],
        ];
    }

    public function saveSettings(array $post): array
    {
        $keys = [
            'antispam_enabled'         => isset($post['antispam_enabled']) ? '1' : '0',
            'antispam_honeypot'        => isset($post['antispam_honeypot']) ? '1' : '0',
            'antispam_min_time'        => (string)max(0, min(60, (int)($post['antispam_min_time'] ?? 3))),
            'antispam_max_links'       => (string)max(0, min(50, (int)($post['antispam_max_links'] ?? 3))),
            'antispam_block_empty_ua'  => isset($post['antispam_block_empty_ua']) ? '1' : '0',
            'antispam_recaptcha_key'   => trim($post['antispam_recaptcha_key'] ?? ''),
            'antispam_recaptcha_secret' => trim($post['antispam_recaptcha_secret'] ?? ''),
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
            return ['success' => true, 'message' => 'AntiSpam-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function addBlacklist(array $post): array
    {
        $type  = in_array($post['bl_type'] ?? '', ['word', 'email', 'ip', 'domain'], true)
            ? $post['bl_type'] : 'word';
        $value = trim($post['bl_value'] ?? '');
        if ($value === '') {
            return ['success' => false, 'error' => 'Wert ist erforderlich.'];
        }
        if ($type === 'ip' && !filter_var($value, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'error' => 'Ungültige IP-Adresse.'];
        }
        if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Ungültige E-Mail-Adresse.'];
        }

        try {
            $this->db->insert('spam_blacklist', [
                'type'  => $type,
                'value' => $value,
            ]);
            return ['success' => true, 'message' => 'Blacklist-Eintrag hinzugefügt.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function deleteBlacklist(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'error' => 'Ungültige ID.'];
        }
        $this->db->delete('spam_blacklist', ['id' => $id]);
        return ['success' => true, 'message' => 'Blacklist-Eintrag gelöscht.'];
    }
}
