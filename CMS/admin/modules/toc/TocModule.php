<?php
declare(strict_types=1);

/**
 * TOC Module – Table of Contents Einstellungen
 *
 * Liest/schreibt toc_settings in der options-Tabelle.
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;

class TocModule
{
    private Database $db;
    private string $prefix;

    private const DEFAULTS = [
        'support_types'        => ['post', 'page'],
        'auto_insert_types'    => ['post'],
        'position'             => 'before',
        'show_limit'           => 4,
        'show_header_label'    => true,
        'header_label'         => 'Inhaltsverzeichnis',
        'allow_toggle'         => true,
        'show_hierarchy'       => true,
        'show_counter'         => true,
        'smooth_scroll'        => true,
        'smooth_scroll_offset' => 30,
        'mobile_scroll_offset' => 0,
        'width'                => 'auto',
        'alignment'            => 'none',
        'theme'                => 'grey',
        'custom_bg_color'      => '#f9f9f9',
        'custom_border_color'  => '#aaaaaa',
        'custom_title_color'   => '#333333',
        'custom_link_color'    => '#0073aa',
        'headings'             => ['h2', 'h3', 'h4'],
        'exclude_headings'     => '',
        'limit_path'           => '',
        'lowercase'            => true,
        'hyphenate'            => true,
        'homepage_toc'         => false,
        'exclude_css'          => false,
        'anchor_prefix'        => '',
        'remove_toc_links'     => false,
        'sticky_toggle'        => false,
    ];

    public function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * Alle aktuellen Einstellungen laden
     */
    public function getSettings(): array
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'toc_settings'"
            );
            if ($row && !empty($row['option_value'])) {
                $saved = json_decode($row['option_value'], true);
                if (is_array($saved)) {
                    return array_merge(self::DEFAULTS, $saved);
                }
            }
        } catch (\Throwable) {
        }
        return self::DEFAULTS;
    }

    /**
     * Einstellungen speichern
     */
    public function saveSettings(array $input): array
    {
        $settings = self::DEFAULTS;

        // Booleans
        foreach (['show_header_label', 'allow_toggle', 'show_hierarchy', 'show_counter', 'smooth_scroll', 'lowercase', 'hyphenate', 'homepage_toc', 'exclude_css', 'remove_toc_links', 'sticky_toggle'] as $key) {
            $settings[$key] = isset($input[$key]);
        }

        // Strings
        foreach (['position', 'header_label', 'width', 'alignment', 'theme', 'custom_bg_color', 'custom_border_color', 'custom_title_color', 'custom_link_color', 'exclude_headings', 'limit_path', 'anchor_prefix'] as $key) {
            if (isset($input[$key])) {
                $settings[$key] = trim((string)$input[$key]);
            }
        }

        // Integers
        foreach (['show_limit', 'smooth_scroll_offset', 'mobile_scroll_offset'] as $key) {
            if (isset($input[$key])) {
                $settings[$key] = (int)$input[$key];
            }
        }

        // Arrays (Checkboxen)
        $settings['support_types']     = $input['support_types'] ?? [];
        $settings['auto_insert_types'] = $input['auto_insert_types'] ?? [];
        $settings['headings']          = $input['headings'] ?? ['h2', 'h3'];

        // JSON speichern
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE);

        try {
            $existing = $this->db->fetchOne(
                "SELECT id FROM {$this->prefix}settings WHERE option_name = 'toc_settings'"
            );

            if ($existing) {
                $this->db->query(
                    "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = 'toc_settings'",
                    [$json]
                );
            } else {
                $this->db->query(
                    "INSERT INTO {$this->prefix}settings (option_name, option_value) VALUES ('toc_settings', ?)",
                    [$json]
                );
            }
            return ['success' => true, 'message' => 'Einstellungen gespeichert.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler beim Speichern: ' . $e->getMessage()];
        }
    }

    /**
     * Defaults für Referenz
     */
    public function getDefaults(): array
    {
        return self::DEFAULTS;
    }
}
