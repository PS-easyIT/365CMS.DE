<?php
/**
 * Öffentliche i18n-Helfer für Plugin-Routen (DE/EN-Präfix, Site-Unterpfad).
 *
 * Stellt die cms_plugin_public_*-Funktionen zentral im Core bereit, damit
 * einzeln verteilte Plugins nicht auf einen gemeinsamen shared/-Ordner angewiesen sind.
 *
 * @package 365CMS
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cms_plugin_public_normalize_path')) {
    function cms_plugin_public_normalize_path(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        $normalized = (string) preg_replace('#/+#', '/', $path);
        $normalized = trim($normalized, '/');

        return strtolower($normalized);
    }
}

if (!function_exists('cms_plugin_public_site_prefix')) {
    function cms_plugin_public_site_prefix(): string
    {
        $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
        $path = (string) parse_url($siteUrl, PHP_URL_PATH);

        return cms_plugin_public_normalize_path($path);
    }
}

if (!function_exists('cms_plugin_public_request_path')) {
    function cms_plugin_public_request_path(): string
    {
        $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return cms_plugin_public_normalize_path($requestPath);
    }
}

if (!function_exists('cms_plugin_public_strip_site_prefix')) {
    function cms_plugin_public_strip_site_prefix(string $normalizedPath): string
    {
        $prefix = cms_plugin_public_site_prefix();
        if ($prefix === '' || $normalizedPath === '') {
            return $normalizedPath;
        }

        if ($normalizedPath === $prefix) {
            return '';
        }

        if (str_starts_with($normalizedPath, $prefix . '/')) {
            return substr($normalizedPath, strlen($prefix . '/')) ?: '';
        }

        return $normalizedPath;
    }
}

if (!function_exists('cms_plugin_public_language')) {
    function cms_plugin_public_language(?string $normalizedPath = null): string
    {
        $path = $normalizedPath ?? cms_plugin_public_request_path();
        $withoutSitePrefix = cms_plugin_public_strip_site_prefix($path);

        if ($withoutSitePrefix === 'en' || str_starts_with($withoutSitePrefix, 'en/')) {
            return 'en';
        }

        return 'de';
    }
}

if (!function_exists('cms_plugin_public_path_without_lang')) {
    function cms_plugin_public_path_without_lang(?string $normalizedPath = null): string
    {
        $path = $normalizedPath ?? cms_plugin_public_request_path();
        $withoutSitePrefix = cms_plugin_public_strip_site_prefix($path);

        if ($withoutSitePrefix === 'en') {
            return '';
        }

        if (str_starts_with($withoutSitePrefix, 'en/')) {
            return substr($withoutSitePrefix, 3) ?: '';
        }

        return $withoutSitePrefix;
    }
}

if (!function_exists('cms_plugin_public_localized_path')) {
    function cms_plugin_public_localized_path(string $path, string $lang = 'de'): string
    {
        $path = cms_plugin_public_normalize_path($path);
        $prefix = cms_plugin_public_site_prefix();

        $parts = [];
        if ($prefix !== '') {
            $parts[] = $prefix;
        }

        if ($lang === 'en') {
            $parts[] = 'en';
        }

        if ($path !== '') {
            $parts[] = $path;
        }

        return '/' . implode('/', $parts);
    }
}

if (!function_exists('cms_plugin_public_i18n_value')) {
    /**
     * @param array<string,string> $values
     */
    function cms_plugin_public_i18n_value(array $values, string $key, string $lang, string $fallback = ''): string
    {
        if ($lang === 'en' && isset($values[$key . '_en']) && $values[$key . '_en'] !== '') {
            return (string) $values[$key . '_en'];
        }

        if (isset($values[$key]) && $values[$key] !== '') {
            return (string) $values[$key];
        }

        return $fallback;
    }
}
