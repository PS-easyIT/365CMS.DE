<?php
declare(strict_types=1);

/**
 * Design Settings Module – Globale Design-Einstellungen
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Services\ThemeCustomizer;

class DesignSettingsModule
{
    private Database $db;
    private string $prefix;

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Alle Design-Einstellungen laden
     */
    public function getData(): array
    {
        $settings = $this->getAllDesignSettings();

        return [
            'colors' => [
                'primary'    => $settings['color_primary'] ?? '#2563eb',
                'secondary'  => $settings['color_secondary'] ?? '#64748b',
                'accent'     => $settings['color_accent'] ?? '#e8a838',
                'text'       => $settings['color_text'] ?? '#1e293b',
                'bg'         => $settings['color_bg'] ?? '#ffffff',
                'bg_dark'    => $settings['color_bg_dark'] ?? '#1e293b',
            ],
            'layout' => [
                'container_width' => $settings['layout_container_width'] ?? '1280',
                'sidebar_position' => $settings['layout_sidebar_position'] ?? 'right',
                'border_radius'    => $settings['layout_border_radius'] ?? '8',
            ],
            'header' => [
                'sticky'     => ($settings['header_sticky'] ?? '1') === '1',
                'transparent' => ($settings['header_transparent'] ?? '0') === '1',
                'search'     => ($settings['header_search'] ?? '1') === '1',
            ],
            'footer' => [
                'columns'  => $settings['footer_columns'] ?? '4',
                'dark'     => ($settings['footer_dark'] ?? '1') === '1',
            ],
            'performance' => [
                'lazy_loading'  => ($settings['perf_lazy_loading'] ?? '1') === '1',
                'minify_css'    => ($settings['perf_minify_css'] ?? '0') === '1',
                'minify_js'     => ($settings['perf_minify_js'] ?? '0') === '1',
            ],
            'custom_css' => $settings['custom_css'] ?? '',
        ];
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $post): array
    {
        try {
            $values = [
                'color_primary'          => $this->sanitizeColor($post['color_primary'] ?? '#2563eb'),
                'color_secondary'        => $this->sanitizeColor($post['color_secondary'] ?? '#64748b'),
                'color_accent'           => $this->sanitizeColor($post['color_accent'] ?? '#e8a838'),
                'color_text'             => $this->sanitizeColor($post['color_text'] ?? '#1e293b'),
                'color_bg'               => $this->sanitizeColor($post['color_bg'] ?? '#ffffff'),
                'color_bg_dark'          => $this->sanitizeColor($post['color_bg_dark'] ?? '#1e293b'),
                'layout_container_width' => (string)max(960, min(1920, (int)($post['container_width'] ?? 1280))),
                'layout_sidebar_position' => in_array($post['sidebar_position'] ?? 'right', ['left', 'right', 'none'], true) ? $post['sidebar_position'] : 'right',
                'layout_border_radius'   => (string)max(0, min(32, (int)($post['border_radius'] ?? 8))),
                'header_sticky'          => !empty($post['header_sticky']) ? '1' : '0',
                'header_transparent'     => !empty($post['header_transparent']) ? '1' : '0',
                'header_search'          => !empty($post['header_search']) ? '1' : '0',
                'footer_columns'         => (string)max(1, min(6, (int)($post['footer_columns'] ?? 4))),
                'footer_dark'            => !empty($post['footer_dark']) ? '1' : '0',
                'perf_lazy_loading'      => !empty($post['lazy_loading']) ? '1' : '0',
                'perf_minify_css'        => !empty($post['minify_css']) ? '1' : '0',
                'perf_minify_js'         => !empty($post['minify_js']) ? '1' : '0',
                'custom_css'             => strip_tags($post['custom_css'] ?? ''),
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

            return ['success' => true, 'message' => 'Design-Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Alle Design-bezogenen Settings aus der DB
     */
    private function getAllDesignSettings(): array
    {
        $settings = [];
        try {
            $rows = $this->db->get_results(
                "SELECT option_name, option_value FROM {$this->prefix}settings 
                 WHERE option_name LIKE 'color_%' 
                    OR option_name LIKE 'layout_%' 
                    OR option_name LIKE 'header_%' 
                    OR option_name LIKE 'footer_%' 
                    OR option_name LIKE 'perf_%' 
                    OR option_name = 'custom_css'"
            ) ?: [];
            foreach ($rows as $row) {
                $settings[$row->option_name] = $row->option_value;
            }
        } catch (\Throwable $e) {
            // Defaults
        }
        return $settings;
    }

    /**
     * Farbwert sanitisieren
     */
    private function sanitizeColor(string $color): string
    {
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '#000000';
    }
}
