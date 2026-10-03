<?php
declare(strict_types=1);

/**
 * Zentrale Marketplace-Endpunkte für 365CMS
 *
 * Bündelt alle offiziellen Marketplace-Feeds (Plugin-/Theme-Index, Basis-URLs,
 * öffentliche Übersicht, Einreichung und Core-Update-Feed) an einer Stelle.
 * Werte können über die Settings-Tabelle überschrieben werden; leere oder
 * ungültige Werte fallen auf die offiziellen 365CMS-Defaults zurück.
 *
 * @package CMS\Services
 */

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class MarketplaceEndpoints
{
    public const OVERVIEW_URL = 'https://365cms.de/marketplace-public';
    public const PLUGIN_INDEX_URL = 'https://365cms.de/marketplace/plugins/index.json';
    public const THEME_INDEX_URL = 'https://365cms.de/marketplace/themes/index.json';
    public const PLUGIN_BASE_URL = 'https://365cms.de/marketplace/plugins';
    public const THEME_BASE_URL = 'https://365cms.de/marketplace/themes';
    public const SUBMIT_URL = 'https://365cms.de/marketplace-submit';
    public const CORE_UPDATE_URL = 'https://365cms.de/marketplace/core/365cms/update.json';

    /**
     * Settings-Key => offizieller Default.
     */
    public const DEFAULTS = [
        'marketplace_public_url' => self::OVERVIEW_URL,
        'plugin_registry_url' => self::PLUGIN_INDEX_URL,
        'theme_registry_url' => self::THEME_INDEX_URL,
        'plugin_marketplace_base_url' => self::PLUGIN_BASE_URL,
        'theme_marketplace_url' => self::THEME_BASE_URL,
        'marketplace_submit_url' => self::SUBMIT_URL,
        'core_update_url' => self::CORE_UPDATE_URL,
    ];

    /**
     * Anzeige-Labels für Admin-Oberflächen.
     */
    public const LABELS = [
        'marketplace_public_url' => 'Marketplace-Übersicht',
        'plugin_registry_url' => 'Plugins-Index',
        'theme_registry_url' => 'Themes-Index',
        'plugin_marketplace_base_url' => 'Plugins-Basis',
        'theme_marketplace_url' => 'Themes-Basis',
        'marketplace_submit_url' => 'Public Einreichung',
        'core_update_url' => 'CMS-Update-Feed',
    ];

    /** @var array<string, string>|null */
    private static ?array $resolved = null;

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Alle effektiv aktiven Endpunkte (Settings mit Fallback auf Defaults).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        return self::$resolved = self::resolve(self::loadStoredValues());
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? (self::DEFAULTS[$key] ?? '');
    }

    public static function overviewUrl(): string
    {
        return self::get('marketplace_public_url');
    }

    public static function pluginIndexUrl(): string
    {
        return self::get('plugin_registry_url');
    }

    public static function themeIndexUrl(): string
    {
        return self::get('theme_registry_url');
    }

    public static function pluginBaseUrl(): string
    {
        return self::get('plugin_marketplace_base_url');
    }

    public static function themeBaseUrl(): string
    {
        return self::get('theme_marketplace_url');
    }

    public static function submitUrl(): string
    {
        return self::get('marketplace_submit_url');
    }

    public static function coreUpdateUrl(): string
    {
        return self::get('core_update_url');
    }

    /**
     * Normalisiert rohe Eingaben (z. B. aus Settings oder POST) zu einem
     * konsistenten Endpunkt-Set. Index- und Basis-URLs werden voneinander
     * abgeleitet, wenn nur eine der beiden individuell gesetzt wurde.
     *
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    public static function resolve(array $values): array
    {
        $resolved = [];
        foreach (self::DEFAULTS as $key => $default) {
            $resolved[$key] = self::normalizeUrl((string) ($values[$key] ?? ''), $default);
        }

        // Ältere Installationen speichern teils eine Index-URL im Basis-Feld.
        foreach (['theme_marketplace_url', 'plugin_marketplace_base_url'] as $baseKey) {
            $basePath = strtolower((string) parse_url($resolved[$baseKey], PHP_URL_PATH));
            if (str_ends_with($basePath, '.json')) {
                $indexKey = $baseKey === 'theme_marketplace_url' ? 'theme_registry_url' : 'plugin_registry_url';
                if ($resolved[$indexKey] === self::DEFAULTS[$indexKey]) {
                    $resolved[$indexKey] = $resolved[$baseKey];
                }
                $resolved[$baseKey] = self::baseFromIndex($resolved[$baseKey]);
            }
        }

        // Themes: Index aus individueller Basis ableiten (und umgekehrt).
        $themeBaseCustom = $resolved['theme_marketplace_url'] !== self::THEME_BASE_URL;
        $themeIndexCustom = $resolved['theme_registry_url'] !== self::THEME_INDEX_URL;
        if ($themeBaseCustom && !$themeIndexCustom) {
            $resolved['theme_registry_url'] = self::indexFromBase($resolved['theme_marketplace_url']);
        } elseif ($themeIndexCustom && !$themeBaseCustom) {
            $resolved['theme_marketplace_url'] = self::baseFromIndex($resolved['theme_registry_url']);
        }

        // Plugins: Basis aus individuellem Index ableiten (und umgekehrt).
        $pluginBaseCustom = $resolved['plugin_marketplace_base_url'] !== self::PLUGIN_BASE_URL;
        $pluginIndexCustom = $resolved['plugin_registry_url'] !== self::PLUGIN_INDEX_URL;
        if ($pluginIndexCustom && !$pluginBaseCustom) {
            $resolved['plugin_marketplace_base_url'] = self::baseFromIndex($resolved['plugin_registry_url']);
        } elseif ($pluginBaseCustom && !$pluginIndexCustom) {
            $resolved['plugin_registry_url'] = self::indexFromBase($resolved['plugin_marketplace_base_url']);
        }

        return $resolved;
    }

    /**
     * Setzt den In-Memory-Cache zurück (z. B. nach dem Speichern der Settings).
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    public static function normalizeUrl(string $value, string $fallback): string
    {
        $value = trim($value);
        $fallback = rtrim($fallback, '/');
        if ($value === '') {
            return $fallback;
        }

        $sanitized = rtrim((string) filter_var($value, FILTER_SANITIZE_URL), '/');
        if ($sanitized === ''
            || filter_var($sanitized, FILTER_VALIDATE_URL) === false
            || !str_starts_with(strtolower($sanitized), 'https://')
        ) {
            return $fallback;
        }

        return $sanitized;
    }

    public static function indexFromBase(string $baseUrl): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $path = strtolower((string) parse_url($baseUrl, PHP_URL_PATH));

        return str_ends_with($path, '.json') ? $baseUrl : $baseUrl . '/index.json';
    }

    public static function baseFromIndex(string $indexUrl): string
    {
        $indexUrl = rtrim($indexUrl, '/');
        $path = strtolower((string) parse_url($indexUrl, PHP_URL_PATH));
        if (!str_ends_with($path, '.json')) {
            return $indexUrl;
        }

        return (string) preg_replace('~/[^/]+$~', '', $indexUrl);
    }

    /**
     * @return array<string, string>
     */
    private static function loadStoredValues(): array
    {
        $values = [];

        try {
            $db = Database::instance();
            $keys = array_keys(self::DEFAULTS);
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));
            $rows = $db->get_results(
                "SELECT option_name, option_value FROM {$db->getPrefix()}settings WHERE option_name IN ({$placeholders})",
                $keys
            );

            foreach ($rows as $row) {
                $row = (array) $row;
                $name = (string) ($row['option_name'] ?? '');
                if ($name !== '') {
                    $values[$name] = (string) ($row['option_value'] ?? '');
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $values;
    }
}
