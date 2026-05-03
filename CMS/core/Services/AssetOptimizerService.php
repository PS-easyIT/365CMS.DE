<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class AssetOptimizerService
{
    private static ?self $instance = null;
    private array $settingsCache = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public function getAssetUrl(string $absolutePath, string $publicUrl, string $type, string $fallbackVersion = ''): string
    {
        $type = strtolower($type);
        if (!in_array($type, ['css', 'js'], true) || !is_file($absolutePath) || !$this->isEnabled($type)) {
            return $this->appendVersion($publicUrl, $this->resolveVersion($absolutePath, $fallbackVersion));
        }

        $optimizedPath = $this->buildOptimizedPath($absolutePath, $type);
        $optimizedUrl = $this->buildOptimizedUrl($optimizedPath);
        if ($optimizedPath === '' || $optimizedUrl === '') {
            return $this->appendVersion($publicUrl, $this->resolveVersion($absolutePath, $fallbackVersion));
        }

        if (!is_file($optimizedPath) || (int)filemtime($optimizedPath) < (int)filemtime($absolutePath)) {
            $source = (string)file_get_contents($absolutePath);
            $minified = $type === 'css' ? $this->minifyCss($source) : $this->minifyJs($source);
            if (trim($minified) === '') {
                return $this->appendVersion($publicUrl, $this->resolveVersion($absolutePath, $fallbackVersion));
            }

            if (!is_dir(dirname($optimizedPath))) {
                mkdir(dirname($optimizedPath), 0775, true);
            }

            file_put_contents($optimizedPath, $minified);
        }

        return $this->appendVersion($optimizedUrl, $this->resolveVersion($optimizedPath, $fallbackVersion));
    }

    private function isEnabled(string $type): bool
    {
        $key = $type === 'css' ? 'perf_minify_css' : 'perf_minify_js';
        if (array_key_exists($key, $this->settingsCache)) {
            return $this->settingsCache[$key] === '1';
        }

        try {
            $db = Database::instance();
            $value = (string)($db->get_var(
                "SELECT option_value FROM {$db->getPrefix()}settings WHERE option_name = ? LIMIT 1",
                [$key]
            ) ?? '0');
        } catch (\Throwable) {
            $value = '0';
        }

        $this->settingsCache[$key] = $value;
        return $value === '1';
    }

    private function buildOptimizedPath(string $absolutePath, string $type): string
    {
        $mtime = (string)((int)filemtime($absolutePath));
        $hash = substr(hash('sha256', $absolutePath . '|' . $mtime), 0, 24);

        return rtrim((string)ABSPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'optimized-assets' . DIRECTORY_SEPARATOR . $hash . '.min.' . $type;
    }

    private function buildOptimizedUrl(string $optimizedPath): string
    {
        $base = rtrim(str_replace('\\', '/', (string)ABSPATH), '/') . '/';
        $path = str_replace('\\', '/', $optimizedPath);
        if (!str_starts_with($path, $base)) {
            return '';
        }

        return rtrim((string)SITE_URL, '/') . '/' . ltrim(substr($path, strlen($base)), '/');
    }

    private function resolveVersion(string $path, string $fallback): string
    {
        $mtime = is_file($path) ? filemtime($path) : false;
        return $mtime !== false ? (string)$mtime : $fallback;
    }

    private function appendVersion(string $url, string $version): string
    {
        if ($version === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . rawurlencode($version);
    }

    private function minifyCss(string $css): string
    {
        $css = preg_replace('!/\*[^*]*\*+(?:[^/*][^*]*\*+)*/!', '', $css) ?? $css;
        $css = preg_replace('/\s+/', ' ', $css) ?? $css;
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css) ?? $css;
        $css = str_replace([';}'], ['}'], $css);

        return trim($css);
    }

    private function minifyJs(string $js): string
    {
        $withoutComments = $this->stripJsComments($js);
        $withoutComments = preg_replace('/\s+/', ' ', $withoutComments) ?? $withoutComments;

        return trim($withoutComments);
    }

    private function stripJsComments(string $js): string
    {
        $out = '';
        $length = strlen($js);
        $quote = null;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $js[$i];
            $next = $i + 1 < $length ? $js[$i + 1] : '';

            if ($quote !== null) {
                $out .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'" || $char === '`') {
                $quote = $char;
                $out .= $char;
                continue;
            }

            if ($char === '/' && $next === '/') {
                while ($i < $length && !in_array($js[$i], ["\n", "\r"], true)) {
                    $i++;
                }
                $out .= "\n";
                continue;
            }

            if ($char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && !($js[$i] === '*' && $js[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            $out .= $char;
        }

        return $out;
    }
}
