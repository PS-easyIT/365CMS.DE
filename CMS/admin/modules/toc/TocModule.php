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

    private const ALLOWED_TYPES = ['post', 'page'];
    private const ALLOWED_POSITIONS = ['before', 'after', 'top', 'bottom'];
    private const ALLOWED_WIDTHS = ['auto', '100%', '75%', '50%'];
    private const ALLOWED_ALIGNMENTS = ['none', 'left', 'center', 'right'];
    private const ALLOWED_THEMES = ['grey', 'light', 'dark', 'transparent', 'custom', 'light-blue', 'white', 'black'];
    private const ALLOWED_HEADINGS = ['h2', 'h3', 'h4', 'h5', 'h6'];

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
            $row = Database::fetchOne(
                "SELECT option_value FROM {$this->prefix}settings WHERE option_name = 'toc_settings'"
            );
            if ($row && !empty($row['option_value'])) {
                $saved = \CMS\Json::decodeArray($row['option_value'] ?? null, []);
                if (is_array($saved)) {
                    return $this->normalizeSettings(array_merge(self::DEFAULTS, $saved));
                }
            }
        } catch (\Throwable) {
        }
        return $this->normalizeSettings(self::DEFAULTS);
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

        $settings = $this->normalizeSettings($settings);

        // JSON speichern
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE);

        try {
            $existing = Database::fetchOne(
                "SELECT id FROM {$this->prefix}settings WHERE option_name = 'toc_settings'"
            );

            if ($existing) {
                $this->db->execute(
                    "UPDATE {$this->prefix}settings SET option_value = ? WHERE option_name = 'toc_settings'",
                    [$json]
                );
            } else {
                $this->db->execute(
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

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings): array
    {
        $settings['support_types'] = $this->normalizeStringList((array) ($settings['support_types'] ?? []), self::ALLOWED_TYPES);
        $settings['auto_insert_types'] = $this->normalizeStringList((array) ($settings['auto_insert_types'] ?? []), self::ALLOWED_TYPES);
        $settings['headings'] = $this->normalizeStringList((array) ($settings['headings'] ?? self::DEFAULTS['headings']), self::ALLOWED_HEADINGS);
        $settings['position'] = $this->normalizeEnum((string) ($settings['position'] ?? self::DEFAULTS['position']), self::ALLOWED_POSITIONS, self::DEFAULTS['position']);
        $settings['width'] = $this->normalizeEnum((string) ($settings['width'] ?? self::DEFAULTS['width']), self::ALLOWED_WIDTHS, self::DEFAULTS['width']);
        $settings['alignment'] = $this->normalizeEnum((string) ($settings['alignment'] ?? self::DEFAULTS['alignment']), self::ALLOWED_ALIGNMENTS, self::DEFAULTS['alignment']);
        $settings['theme'] = $this->normalizeEnum((string) ($settings['theme'] ?? self::DEFAULTS['theme']), self::ALLOWED_THEMES, self::DEFAULTS['theme']);
        $settings['show_limit'] = max(1, min(20, (int) ($settings['show_limit'] ?? self::DEFAULTS['show_limit'])));
        $settings['smooth_scroll_offset'] = max(0, min(200, (int) ($settings['smooth_scroll_offset'] ?? self::DEFAULTS['smooth_scroll_offset'])));
        $settings['mobile_scroll_offset'] = max(0, min(200, (int) ($settings['mobile_scroll_offset'] ?? self::DEFAULTS['mobile_scroll_offset'])));
        $settings['custom_bg_color'] = $this->normalizeHexColor((string) ($settings['custom_bg_color'] ?? self::DEFAULTS['custom_bg_color']), self::DEFAULTS['custom_bg_color']);
        $settings['custom_border_color'] = $this->normalizeHexColor((string) ($settings['custom_border_color'] ?? self::DEFAULTS['custom_border_color']), self::DEFAULTS['custom_border_color']);
        $settings['custom_title_color'] = $this->normalizeHexColor((string) ($settings['custom_title_color'] ?? self::DEFAULTS['custom_title_color']), self::DEFAULTS['custom_title_color']);
        $settings['custom_link_color'] = $this->normalizeHexColor((string) ($settings['custom_link_color'] ?? self::DEFAULTS['custom_link_color']), self::DEFAULTS['custom_link_color']);
        $settings['exclude_headings'] = implode('|', $this->splitDelimitedList((string) ($settings['exclude_headings'] ?? '')));
        $settings['limit_path'] = $this->normalizeLimitPath((string) ($settings['limit_path'] ?? ''));
        $settings['anchor_prefix'] = $this->normalizeAnchorPrefix((string) ($settings['anchor_prefix'] ?? ''));

        return $settings;
    }

    /**
     * @param list<string> $allowed
     * @return list<string>
     */
    private function normalizeStringList(array $values, array $allowed): array
    {
        $normalized = [];

        foreach ($values as $value) {
            $candidate = strtolower(trim((string) $value));
            if ($candidate === '' || !in_array($candidate, $allowed, true) || in_array($candidate, $normalized, true)) {
                continue;
            }

            $normalized[] = $candidate;
        }

        return $normalized;
    }

    /** @param list<string> $allowed */
    private function normalizeEnum(string $value, array $allowed, string $fallback): string
    {
        $value = strtolower(trim($value));

        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function normalizeHexColor(string $value, string $fallback): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
    }

    /** @return list<string> */
    private function splitDelimitedList(string $value): array
    {
        $parts = preg_split('/\s*(?:\||,|\r\n|\r|\n)\s*/u', $value) ?: [];

        return array_values(array_filter(array_map(static fn (string $part): string => trim($part), $parts), static fn (string $part): bool => $part !== ''));
    }

    private function normalizeLimitPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $path = '/' . ltrim($path, '/');

        return rtrim((string) preg_replace('#/+#', '/', $path), '/') ?: '/';
    }

    private function normalizeAnchorPrefix(string $prefix): string
    {
        $prefix = trim($prefix);
        $prefix = (string) preg_replace('/[^\p{L}\p{N}\-_]+/u', '-', $prefix);

        return trim($prefix, '-_');
    }
}
