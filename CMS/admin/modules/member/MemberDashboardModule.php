<?php
declare(strict_types=1);

/**
 * Member Dashboard Module – Konfiguration des Member-Bereichs
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class MemberDashboardModule
{
    private Database $db;
    private string $prefix;

    private const SETTINGS_KEYS = [
        'member_dashboard_enabled',
        'member_registration_enabled',
        'member_email_verification',
        'member_welcome_message',
        'member_dashboard_widgets',
        'member_default_role',
        'member_profile_fields',
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Einstellungen laden
     */
    public function getData(): array
    {
        $settings = $this->getSettings();
        $stats    = $this->getMemberStats();

        return [
            'settings'  => $settings,
            'stats'     => $stats,
            'widgets'   => $this->getAvailableWidgets(),
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $post): array
    {
        try {
            $values = [
                'member_dashboard_enabled'    => !empty($post['dashboard_enabled']) ? '1' : '0',
                'member_registration_enabled' => !empty($post['registration_enabled']) ? '1' : '0',
                'member_email_verification'   => !empty($post['email_verification']) ? '1' : '0',
                'member_welcome_message'      => strip_tags($post['welcome_message'] ?? '', '<p><a><strong><em><br>'),
                'member_default_role'         => preg_replace('/[^a-zA-Z0-9_-]/', '', $post['default_role'] ?? 'member'),
                'member_dashboard_widgets'    => json_encode(array_keys(array_filter($post['widgets'] ?? []))),
                'member_profile_fields'       => json_encode(array_keys(array_filter($post['profile_fields'] ?? []))),
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

            return ['success' => true, 'message' => 'Member-Dashboard-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Einstellungen laden
     */
    private function getSettings(): array
    {
        $settings = [];
        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings WHERE option_name LIKE 'member_%'"
            ) ?: [];
            foreach ($rows as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        } catch (\Throwable $e) {
            // Defaults
        }

        return [
            'dashboard_enabled'    => ($settings['member_dashboard_enabled'] ?? '1') === '1',
            'registration_enabled' => ($settings['member_registration_enabled'] ?? '1') === '1',
            'email_verification'   => ($settings['member_email_verification'] ?? '0') === '1',
            'welcome_message'      => $settings['member_welcome_message'] ?? '',
            'default_role'         => $settings['member_default_role'] ?? 'member',
            'widgets'              => json_decode($settings['member_dashboard_widgets'] ?? '[]', true) ?: [],
            'profile_fields'       => json_decode($settings['member_profile_fields'] ?? '[]', true) ?: [],
        ];
    }

    /**
     * Member-Statistiken
     */
    private function getMemberStats(): array
    {
        try {
            $total    = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users");
            $active   = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'active'");
            $thisWeek = (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

            return [
                'total'    => $total,
                'active'   => $active,
                'thisWeek' => $thisWeek,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'active' => 0, 'thisWeek' => 0];
        }
    }

    /**
     * Verfügbare Dashboard-Widgets
     */
    private function getAvailableWidgets(): array
    {
        return [
            'profile'       => 'Profil-Übersicht',
            'activity'      => 'Letzte Aktivitäten',
            'messages'      => 'Nachrichten',
            'bookmarks'     => 'Lesezeichen',
            'notifications' => 'Benachrichtigungen',
            'quick_links'   => 'Schnellzugriffe',
            'statistics'    => 'Statistiken',
        ];
    }
}
