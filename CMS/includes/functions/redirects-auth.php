<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirect helper
 */
function cms_get_site_origin(): string {
    $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    if ($siteUrl === '') {
        return '';
    }

    $parts = parse_url($siteUrl);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

    return strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host']) . $port;
}

/**
 * Normalisiert Redirect-Ziele auf interne URLs.
 */
function cms_normalize_redirect_target(string $url, bool $allowExternal = false): ?string {
    $candidate = trim($url);
    if ($candidate === '') {
        return null;
    }

    $stripped = strtolower((string) preg_replace('/[\x00-\x1f\s]/', '', $candidate));
    if (preg_match('/^(javascript|data|vbscript|file):/i', $stripped)) {
        return null;
    }

    if (filter_var($candidate, FILTER_VALIDATE_URL)) {
        $parts = parse_url($candidate);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $targetOrigin = $scheme . '://' . strtolower((string) $parts['host']) . (isset($parts['port']) ? ':' . (int) $parts['port'] : '');
        $siteOrigin = cms_get_site_origin();

        if (!$allowExternal && $siteOrigin !== '' && $targetOrigin !== $siteOrigin) {
            return null;
        }

        return $candidate;
    }

    $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
    if ($siteUrl === '') {
        return str_starts_with($candidate, '/') ? $candidate : '/' . ltrim($candidate, '/');
    }

    if (str_starts_with($candidate, '//')) {
        return null;
    }

    if (str_starts_with($candidate, '/')) {
        return $siteUrl . $candidate;
    }

    if (str_starts_with($candidate, '?') || str_starts_with($candidate, '#')) {
        return $siteUrl . '/' . $candidate;
    }

    return $siteUrl . '/' . ltrim($candidate, '/');
}

/**
 * Führt einen sicheren Redirect aus.
 */
function safe_redirect(string $url, int $status = 302, bool $allowExternal = false): void {
    $target = cms_normalize_redirect_target($url, $allowExternal);
    if ($target === null) {
        $target = cms_normalize_redirect_target('/', true) ?? '/';
        error_log('CMS: Blocked redirect target "' . $url . '"');
    }

    header('Location: ' . $target, true, $status);
    exit;
}

/**
 * Redirect helper
 */
function redirect(string $url, int $status = 302): void {
    safe_redirect($url, $status);
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return CMS\Auth::instance()->isLoggedIn();
}

/**
 * Output checkbox attribute
 */
function checked($checked, $current = true, bool $echo = true): string {
    return __checked_selected_helper($checked, $current, $echo, 'checked');
}

/**
 * Output selected attribute
 */
function selected($selected, $current = true, bool $echo = true): string {
    return __checked_selected_helper($selected, $current, $echo, 'selected');
}

/**
 * Helper for checked/selected
 */
function __checked_selected_helper($helper, $current, bool $echo, string $type): string {
    if ((string) $helper === (string) $current) {
        $result = " $type='$type'";
        if ($echo) {
            echo $result;
        }
        return $result;
    }
    return '';
}

/**
 * Output disabled attribute
 */
function disabled($disabled, $current = true, bool $echo = true): string {
    return __checked_selected_helper($disabled, $current, $echo, 'disabled');
}

/**
 * Output a hidden nonce input field (WP-compatible)
 */
function wp_nonce_field(string|int $action = -1, string $name = '_wpnonce', bool $referer = true, bool $echo = true): string
{
    $nonce = wp_create_nonce($action);
    $field = '<input type="hidden" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($nonce) . '" />';
    if ($referer) {
        $field .= '<input type="hidden" name="_wp_http_referer" value="' . esc_attr($_SERVER['REQUEST_URI'] ?? '') . '" />';
    }
    if ($echo) {
        echo $field;
    }
    return $field;
}

/**
 * Check if current user is admin
 */
function is_admin(): bool {
    return CMS\Auth::instance()->isAdmin();
}

/**
 * Get current user
 */
function current_user(): ?object {
    return CMS\Auth::instance()->currentUser();
}

/**
 * Get current user ID
 */
function get_current_user_id(): int {
    $user = \CMS\Auth::instance()->currentUser();
    return $user ? (int) $user->id : 0;
}

/**
 * Time ago helper — human-readable relative timestamps
 */
function time_ago(string|int $datetime): string {
    if (is_string($datetime)) {
        $timestamp = strtotime($datetime);
    } else {
        $timestamp = $datetime;
    }

    if ($timestamp === false || $timestamp <= 0) {
        return '-';
    }

    if (class_exists('Carbon\\Carbon')) {
        try {
            return \Carbon\Carbon::createFromTimestamp($timestamp)->locale('de')->diffForHumans();
        } catch (\Throwable) {
        }
    }

    $diff = time() - $timestamp;

    if ($diff < 0) {
        return 'In der Zukunft';
    }
    if ($diff < 60) {
        return 'Gerade eben';
    }
    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return 'vor ' . $mins . ' Minute' . ($mins > 1 ? 'n' : '');
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return 'vor ' . $hours . ' Stunde' . ($hours > 1 ? 'n' : '');
    }
    if ($diff < 604800) {
        $days = (int) floor($diff / 86400);
        if ($days === 1) {
            return 'Gestern';
        }
        return 'vor ' . $days . ' Tagen';
    }
    if ($diff < 2592000) {
        $weeks = (int) floor($diff / 604800);
        return 'vor ' . $weeks . ' Woche' . ($weeks > 1 ? 'n' : '');
    }
    if ($diff < 31536000) {
        $months = (int) floor($diff / 2592000);
        return 'vor ' . $months . ' Monat' . ($months > 1 ? 'en' : '');
    }

    $years = (int) floor($diff / 31536000);
    return 'vor ' . $years . ' Jahr' . ($years > 1 ? 'en' : '');
}

/**
 * Debug helper
 */
function dd(...$vars): void {
    if (!CMS_DEBUG) {
        return;
    }

    echo '<pre style="background: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; margin: 1rem;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Global logging helper — delegates to CMS\Logger
 */
function cms_log(string $level, string $message, array $context = []): void
{
    \CMS\Logger::instance()->log($level, $message, $context);
}

/**
 * Generate random string
 */
function generate_random_string(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if current user has capability
 */
function current_user_can(string $capability): bool {
    if (!class_exists('CMS\\Auth')) {
        return false;
    }

    if (!CMS\Auth::isLoggedIn()) {
        return false;
    }

    if (CMS\Auth::isAdmin()) {
        return true;
    }

    if (method_exists(CMS\Auth::instance(), 'hasCapability')) {
        return CMS\Auth::instance()->hasCapability($capability);
    }

    return false;
}
