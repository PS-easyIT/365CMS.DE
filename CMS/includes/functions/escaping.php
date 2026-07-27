<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Escape HTML output
 */
function esc_html(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape URL – sanitiert und blockiert unsichere Schemes (javascript:, data:, vbscript:)
 */
function esc_url(string $url): string {
    $url = trim($url);
    $stripped = strtolower((string) preg_replace('/[\x00-\x1f\s]/', '', $url));
    if (preg_match('/^(javascript|data|vbscript|file):/i', $stripped)) {
        return '';
    }

    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}

/**
 * Escape attribute
 */
function esc_attr(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Echo escaped HTML
 */
function esc_html_e(string $text, string $domain = 'default'): void {
    echo htmlspecialchars(__($text, $domain), ENT_QUOTES, 'UTF-8');
}

/**
 * Echo escaped attribute
 */
function esc_attr_e(string $text, string $domain = 'default'): void {
    echo htmlspecialchars(__($text, $domain), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for textarea content
 */
function esc_textarea(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape for inline JavaScript
 */
function esc_js(string $text): string {
    return addslashes(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
}

/**
 * UTF-8-sicherer Text-Kürzer mit Fallback ohne mbstring.
 */
function cms_truncate_text(string $text, int $width, string $trimMarker = '…'): string {
    if ($width <= 0 || $text === '') {
        return '';
    }

    if (function_exists('mb_strimwidth')) {
        return (string) mb_strimwidth($text, 0, $width, $trimMarker, 'UTF-8');
    }

    $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($characters)) {
        if (count($characters) <= $width) {
            return $text;
        }

        $markerCharacters = preg_split('//u', $trimMarker, -1, PREG_SPLIT_NO_EMPTY);
        $markerLength = is_array($markerCharacters) ? count($markerCharacters) : strlen($trimMarker);

        if ($markerLength >= $width) {
            return is_array($markerCharacters)
                ? implode('', array_slice($markerCharacters, 0, $width))
                : substr($trimMarker, 0, $width);
        }

        return implode('', array_slice($characters, 0, $width - $markerLength)) . $trimMarker;
    }

    if (strlen($text) <= $width) {
        return $text;
    }

    if (strlen($trimMarker) >= $width) {
        return substr($trimMarker, 0, $width);
    }

    return substr($text, 0, $width - strlen($trimMarker)) . $trimMarker;
}

/**
 * WordPress-style date formatting with timezone support
 */
function wp_date(string $format, ?int $timestamp = null, mixed $timezone = null): string|false {
    $timestamp = $timestamp ?? time();
    return date($format, $timestamp);
}

/**
 * Format a number with locale-aware thousands separator
 */
function number_format_i18n(float $number, int $decimals = 0): string {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Generate pagination links (minimal WP-compatible implementation)
 */
function paginate_links(array $args = []): string
{
    $defaults = [
        'base'      => '%_%',
        'format'    => '?paged=%#%',
        'current'   => 1,
        'total'     => 1,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'type'      => 'plain',
    ];
    $args = array_merge($defaults, $args);

    $current = (int) $args['current'];
    $total   = (int) $args['total'];

    if ($total <= 1) {
        return '';
    }

    $output = [];

    $page_url = function (int $page) use ($args): string {
        return str_replace(
            ['%_%', '%#%'],
            [str_replace('%#%', (string) $page, $args['format']), (string) $page],
            $args['base']
        );
    };

    if ($current > 1) {
        $output[] = '<a class="prev page-numbers" href="' . esc_url($page_url($current - 1)) . '">' . $args['prev_text'] . '</a>';
    }

    for ($i = 1; $i <= $total; $i++) {
        if ($i === $current) {
            $output[] = '<span aria-current="page" class="page-numbers current">' . $i . '</span>';
        } else {
            $output[] = '<a class="page-numbers" href="' . esc_url($page_url($i)) . '">' . $i . '</a>';
        }
    }

    if ($current < $total) {
        $output[] = '<a class="next page-numbers" href="' . esc_url($page_url($current + 1)) . '">' . $args['next_text'] . '</a>';
    }

    return implode("\n", $output);
}

/**
 * Sanitize text field
 */
function sanitize_text(string $text): string {
    return strip_tags(trim($text));
}

/**
 * Sanitize text field (WP compatible)
 */
function sanitize_text_field($str): string {
    if (is_array($str)) {
        return '';
    }

    return strip_tags(trim((string) $str));
}

/**
 * Sanitize email
 */
function sanitize_email(string $email): string {
    return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
}

/**
 * Format date
 */
function format_date(string $date, string $format = 'd.m.Y'): string {
    return date($format, strtotime($date));
}

/**
 * Sanitize textarea field
 */
function sanitize_textarea_field(string $text): string {
    return strip_tags(trim($text));
}

/**
 * Sanitize title (Mock)
 */
function sanitize_title(string $title): string {
    return strtolower((string) preg_replace('/[^a-zA-Z0-9-]/', '-', $title));
}

/**
 * Remove slashes from a string or array of strings.
 */
function wp_unslash($value) {
    return stripslashes_deep($value);
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 */
function stripslashes_deep($value) {
    return map_deep($value, 'stripslashes');
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 */
function map_deep($value, $callback) {
    if (is_array($value)) {
        foreach ($value as $index => $item) {
            $value[$index] = map_deep($item, $callback);
        }
    } elseif (is_object($value)) {
        $objectVars = get_object_vars($value);
        foreach ($objectVars as $propertyName => $propertyValue) {
            $value->$propertyName = map_deep($propertyValue, $callback);
        }
    } else {
        $value = call_user_func($callback, $value);
    }

    return $value;
}

/**
 * WordPress Number / String Helpers
 */
if (!function_exists('absint')) {
    function absint($maybeint): int {
        return abs((int) $maybeint);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);
        return $key ?? '';
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url) && !preg_match('#^ftp://#i', $url)) {
            return '';
        }

        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string {
        $text = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $text) ?? $text;
        $text = strip_tags($text);
        if ($remove_breaks) {
            $text = preg_replace('/[\r\n\t ]+/', ' ', $text) ?? $text;
        }

        return trim($text);
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit(string $string): string {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit(string $string): string {
        return rtrim($string, '/\\');
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, array $defaults = []): array {
        if (is_array($args)) {
            return array_merge($defaults, $args);
        }
        if (is_object($args)) {
            return array_merge($defaults, (array) $args);
        }

        parse_str((string) $args, $parsed);
        return array_merge($defaults, $parsed);
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url(string $url, int $component = -1) {
        return parse_url($url, $component);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, int $options = 0, int $depth = 512): string|false {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('wp_uniqid')) {
    function wp_uniqid(string $prefix = '', bool $more_entropy = false): string {
        return uniqid($prefix, $more_entropy);
    }
}

if (!function_exists('wp_sprintf')) {
    function wp_sprintf(string $pattern, ...$args): string {
        return vsprintf($pattern, $args);
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($data) {
        global $wpdb;
        if (isset($wpdb) && is_object($wpdb) && method_exists($wpdb, '_real_escape')) {
            return $wpdb->_real_escape($data);
        }
        if (is_array($data)) {
            return array_map('addslashes', $data);
        }
        return addslashes((string) $data);
    }
}

if (!function_exists('maybe_serialize')) {
    function maybe_serialize($data): string {
        if (is_array($data) || is_object($data)) {
            return serialize($data);
        }
        return (string) $data;
    }
}

if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize(string $original) {
        if (is_serialized($original)) {
            return @unserialize($original, ['allowed_classes' => false]);
        }
        return $original;
    }
}

if (!function_exists('is_serialized')) {
    function is_serialized($data, bool $strict = true): bool {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ($data === 'N;') {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if ($data[1] !== ':') {
            return false;
        }
        return (bool) preg_match('/^[aOCbids]:/', $data);
    }
}
