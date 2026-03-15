<?php
declare(strict_types=1);

namespace CMS\Services\SiteTable;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteTableDisplaySettings
{
    public const OPTION_KEY = 'site_table_display_settings';

    /** @var array<string,array{label:string,description:string}> */
    public const STYLE_OPTIONS = [
        'default' => [
            'label' => 'Standard',
            'description' => 'Klassische Tabellenoptik mit neutralem Kopfbereich.',
        ],
        'stripe' => [
            'label' => 'Gestreift',
            'description' => 'Abwechselnde Zeilenhintergründe für bessere Lesbarkeit.',
        ],
        'hover' => [
            'label' => 'Hover',
            'description' => 'Hebt Zeilen beim Überfahren visuell hervor.',
        ],
        'cell-border' => [
            'label' => 'Rahmen',
            'description' => 'Zeigt zusätzliche Zellrahmen für datenlastige Tabellen.',
        ],
    ];

    /** @var array<string,mixed> */
    public const DEFAULTS = [
        'show_meta_panel' => true,
        'show_table_name' => true,
        'show_description' => true,
        'show_export_links' => true,
        'show_caption' => true,
        'responsive_default' => true,
        'default_style' => 'default',
        'enabled_styles' => ['default', 'stripe', 'hover', 'cell-border'],
    ];

    /** @return array<string,mixed> */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    /** @return array<string,array{label:string,description:string}> */
    public static function styleOptions(): array
    {
        return self::STYLE_OPTIONS;
    }

    /** @param array<string,mixed> $settings
     *  @return array<string,mixed>
     */
    public static function normalize(array $settings): array
    {
        $normalized = self::DEFAULTS;

        foreach (['show_meta_panel', 'show_table_name', 'show_description', 'show_export_links', 'show_caption', 'responsive_default'] as $key) {
            $normalized[$key] = self::toBool($settings[$key] ?? self::DEFAULTS[$key]);
        }

        $enabledStyles = $settings['enabled_styles'] ?? self::DEFAULTS['enabled_styles'];
        if (!is_array($enabledStyles)) {
            $enabledStyles = self::DEFAULTS['enabled_styles'];
        }

        $enabledStyles = array_values(array_unique(array_filter(array_map(
            static fn($value): string => is_string($value) ? trim($value) : '',
            $enabledStyles
        ), static fn(string $style): bool => isset(self::STYLE_OPTIONS[$style]))));

        if ($enabledStyles === []) {
            $enabledStyles = ['default'];
        }

        if (count($enabledStyles) > 4) {
            $enabledStyles = array_slice($enabledStyles, 0, 4);
        }

        $normalized['enabled_styles'] = $enabledStyles;

        $defaultStyle = (string) ($settings['default_style'] ?? self::DEFAULTS['default_style']);
        if (!isset(self::STYLE_OPTIONS[$defaultStyle]) || !in_array($defaultStyle, $enabledStyles, true)) {
            $defaultStyle = $enabledStyles[0];
        }
        $normalized['default_style'] = $defaultStyle;

        return $normalized;
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}