<?php
declare(strict_types=1);

/**
 * SubscriptionSettingsModule – Abo-System Konfiguration
 */

if (!defined('ABSPATH')) {
    exit;
}

class SubscriptionSettingsModule
{
    private readonly \CMS\Database $db;
    private readonly string $prefix;

    /** @var array<string, string> */
    private const array PACKAGE_SETTINGS_KEYS = [
        'subscription_enabled'       => '1',
        'trial_enabled'              => '0',
        'trial_days'                 => '14',
        'auto_renewal'               => '1',
        'grace_period_days'          => '3',
        'cancellation_period_days'   => '0',
        'payment_methods'            => 'invoice',
        'invoice_prefix'             => 'INV-',
        'invoice_next_number'        => '1001',
        'tax_rate'                   => '19',
        'tax_included'               => '1',
        'notification_before_expiry' => '7',
        'notification_email'         => '',
        'terms_page_id'              => '0',
        'cancellation_page_id'       => '0',
    ];

    /** @var array<string, string> */
    private const array GENERAL_SETTINGS_KEYS = [
        'subscription_limits_enabled'       => '1',
        'subscription_default_plan_id'      => '0',
        'subscription_member_area_enabled'  => '1',
        'subscription_ordering_enabled'     => '1',
        'subscription_public_pricing_enabled' => '1',
        'subscription_disabled_notice'      => 'Die Aboverwaltung ist derzeit deaktiviert. Es gelten aktuell keine Limits.',
    ];

    public function __construct()
    {
        $this->db     = \CMS\Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function getData(): array
    {
        $settings = [];
        foreach (self::GENERAL_SETTINGS_KEYS as $key => $default) {
            $row = $this->db->get_row("SELECT option_value FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
            $settings[$key] = $row ? $row->option_value : $default;
        }

        $plans = $this->db->get_results(
            "SELECT id, name, slug, price_monthly, is_active FROM {$this->prefix}subscription_plans ORDER BY sort_order ASC, price_monthly ASC"
        ) ?: [];

        return ['settings' => $settings, 'plans' => array_map(fn($p) => (array)$p, $plans)];
    }

    public function getPackageData(): array
    {
        $settings = [];
        foreach (self::PACKAGE_SETTINGS_KEYS as $key => $default) {
            $row = $this->db->get_row("SELECT option_value FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
            $settings[$key] = $row ? $row->option_value : $default;
        }

        // Seiten für Dropdowns laden
        $pages = $this->db->get_results("SELECT id, title FROM {$this->prefix}pages WHERE status = 'published' ORDER BY title ASC") ?: [];

        return ['settings' => $settings, 'pages' => array_map(fn($p) => (array)$p, $pages)];
    }

    public function saveSettings(array $post): array
    {
        try {
            foreach (self::GENERAL_SETTINGS_KEYS as $key => $default) {
                $value = $post[$key] ?? $default;

                if (in_array($key, ['subscription_limits_enabled', 'subscription_member_area_enabled', 'subscription_ordering_enabled', 'subscription_public_pricing_enabled'], true)) {
                    $value = isset($post[$key]) ? '1' : '0';
                }

                if ($key === 'subscription_default_plan_id') {
                    $value = (string)max(0, (int)$value);
                }

                if (is_string($value)) {
                    $value = trim($value);
                }

                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            return ['success' => true, 'message' => 'Aboverwaltung-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    public function savePackageSettings(array $post): array
    {
        try {
            foreach (self::PACKAGE_SETTINGS_KEYS as $key => $default) {
                $value = $post[$key] ?? $default;

                // Checkboxen
                if (in_array($key, ['subscription_enabled', 'trial_enabled', 'auto_renewal', 'tax_included'], true)) {
                    $value = isset($post[$key]) ? '1' : '0';
                }
                // Numerische Felder
                if (in_array($key, ['trial_days', 'grace_period_days', 'cancellation_period_days', 'tax_rate', 'notification_before_expiry', 'invoice_next_number', 'terms_page_id', 'cancellation_page_id'], true)) {
                    $value = (string)max(0, (int)$value);
                }
                // Text-Felder sanitieren
                if (is_string($value)) {
                    $value = trim($value);
                }

                $exists = $this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}settings WHERE option_name = ?", [$key]);
                if ($exists) {
                    $this->db->update('settings', ['option_value' => $value], ['option_name' => $key]);
                } else {
                    $this->db->insert('settings', ['option_name' => $key, 'option_value' => $value]);
                }
            }
            return ['success' => true, 'message' => 'Paket- und Abo-Einstellungen gespeichert.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }
}
