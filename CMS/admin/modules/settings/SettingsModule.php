<?php
declare(strict_types=1);

/**
 * Allgemeine Einstellungen-Modul
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class SettingsModule
{
    private Database $db;
    private string $prefix;

    private const SETTINGS_KEYS = [
        'site_name', 'site_description', 'site_url', 'admin_email',
        'language', 'timezone', 'date_format', 'time_format',
        'posts_per_page', 'registration_enabled', 'comments_enabled',
        'maintenance_mode', 'maintenance_message',
        'google_analytics', 'robots_txt', 'marketplace_enabled',
    ];

    private const TIMEZONES = [
        'Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich',
        'Europe/London', 'Europe/Paris', 'Europe/Rome',
        'Europe/Madrid', 'Europe/Amsterdam', 'Europe/Brussels',
        'UTC', 'America/New_York', 'America/Chicago',
        'America/Los_Angeles', 'Asia/Tokyo', 'Asia/Shanghai',
    ];

    private const LANGUAGES = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'it' => 'Italiano',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'pt' => 'Português',
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Alle Einstellungen laden
     */
    public function getData(): array
    {
        $settings = $this->loadSettings();

        return [
            'settings'  => [
                'site_name'            => $settings['site_name'] ?? (defined('SITE_NAME') ? SITE_NAME : ''),
                'site_description'     => $settings['site_description'] ?? '',
                'site_url'             => $settings['site_url'] ?? (defined('SITE_URL') ? SITE_URL : ''),
                'admin_email'          => $settings['admin_email'] ?? '',
                'language'             => $settings['language'] ?? 'de',
                'timezone'             => $settings['timezone'] ?? 'Europe/Berlin',
                'date_format'          => $settings['date_format'] ?? 'd.m.Y',
                'time_format'          => $settings['time_format'] ?? 'H:i',
                'posts_per_page'       => $settings['posts_per_page'] ?? '10',
                'registration_enabled' => ($settings['registration_enabled'] ?? '0') === '1',
                'comments_enabled'     => ($settings['comments_enabled'] ?? '1') === '1',
                'maintenance_mode'     => ($settings['maintenance_mode'] ?? '0') === '1',
                'maintenance_message'  => $settings['maintenance_message'] ?? 'Die Website wird gerade gewartet.',
                'google_analytics'     => $settings['google_analytics'] ?? '',
                'robots_txt'           => $settings['robots_txt'] ?? '',
                'marketplace_enabled'  => ($settings['marketplace_enabled'] ?? '1') === '1',
            ],
            'timezones' => self::TIMEZONES,
            'languages' => self::LANGUAGES,
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $post): array
    {
        try {
            $values = [
                'site_name'            => trim(strip_tags($post['site_name'] ?? '')),
                'site_description'     => trim(strip_tags($post['site_description'] ?? '')),
                'site_url'             => rtrim(filter_var($post['site_url'] ?? '', FILTER_SANITIZE_URL), '/'),
                'admin_email'          => filter_var($post['admin_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                'language'             => array_key_exists($post['language'] ?? 'de', self::LANGUAGES) ? $post['language'] : 'de',
                'timezone'             => in_array($post['timezone'] ?? '', self::TIMEZONES, true) ? $post['timezone'] : 'Europe/Berlin',
                'date_format'          => in_array($post['date_format'] ?? 'd.m.Y', ['d.m.Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'], true) ? $post['date_format'] : 'd.m.Y',
                'time_format'          => in_array($post['time_format'] ?? 'H:i', ['H:i', 'H:i:s', 'g:i A'], true) ? $post['time_format'] : 'H:i',
                'posts_per_page'       => (string)max(1, min(100, (int)($post['posts_per_page'] ?? 10))),
                'registration_enabled' => !empty($post['registration_enabled']) ? '1' : '0',
                'comments_enabled'     => !empty($post['comments_enabled']) ? '1' : '0',
                'maintenance_mode'     => !empty($post['maintenance_mode']) ? '1' : '0',
                'maintenance_message'  => trim(strip_tags($post['maintenance_message'] ?? '', '<p><strong><em><br>')),
                'google_analytics'     => preg_match('/^(G-|UA-)[A-Za-z0-9-]+$/', $post['google_analytics'] ?? '') ? $post['google_analytics'] : '',
                'robots_txt'           => strip_tags($post['robots_txt'] ?? ''),
                'marketplace_enabled'  => !empty($post['marketplace_enabled']) ? '1' : '0',
            ];

            foreach ($values as $key => $value) {
                $existing = $this->db->get_var(
                    "SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?",
                    [$key]
                );

                if ((int)$existing > 0) {
                    $this->db->query(
                        "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = ?",
                        [$value, $key]
                    );
                } else {
                    $this->db->query(
                        "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES (?, ?)",
                        [$key, $value]
                    );
                }
            }

            return ['success' => true, 'message' => 'Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    private function loadSettings(): array
    {
        $settings = [];
        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name IN ('" . implode("','", self::SETTINGS_KEYS) . "')"
            ) ?: [];
            foreach ($rows as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        } catch (\Throwable $e) {
            // Defaults werden in getData() gesetzt
        }
        return $settings;
    }
}
