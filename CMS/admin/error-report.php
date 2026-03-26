<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Services\ErrorReportService;

function cms_admin_error_report_default_url(): string
{
    return SITE_URL . '/admin/diagnose';
}

function cms_admin_error_report_limit_string(mixed $value, int $length): string
{
    return mb_substr(trim((string) $value), 0, $length);
}

function cms_normalize_admin_report_redirect(string $target): string
{
    $fallback = cms_admin_error_report_default_url();
    $target = trim($target);
    if ($target === '') {
        return $fallback;
    }

    $siteUrl = (string)SITE_URL;
    if (str_starts_with($target, $siteUrl)) {
        $target = (string)substr($target, strlen($siteUrl));
    }

    $parts = parse_url($target);
    if ($parts === false) {
        return $fallback;
    }

    $path = (string)($parts['path'] ?? '');
    if ($path === '' || !str_starts_with($path, '/admin')) {
        return $fallback;
    }

    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

    return SITE_URL . $path . $query;
}

function cms_admin_error_report_resolve_redirect_url(array $post, array $server): string
{
    return cms_normalize_admin_report_redirect((string) ($post['back_to'] ?? ($server['HTTP_REFERER'] ?? '')));
}

function cms_admin_error_report_decode_json_payload(string $payload): array
{
    $payload = trim($payload);
    if ($payload === '' || strlen($payload) > 20000) {
        return [];
    }

    $decoded = json_decode($payload, true);

    return is_array($decoded) ? cms_admin_error_report_normalize_json_payload($decoded) : [];
}

function cms_admin_error_report_normalize_json_key(mixed $value): string
{
    $key = preg_replace('/[^a-zA-Z0-9_:\-.]/', '_', trim((string) $value));
    $key = $key !== null ? $key : '';

    return mb_substr($key, 0, 80);
}

function cms_admin_error_report_normalize_json_scalar(mixed $value): mixed
{
    if (is_string($value)) {
        return cms_admin_error_report_limit_string($value, 500);
    }

    if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
        return $value;
    }

    return cms_admin_error_report_limit_string($value, 500);
}

function cms_admin_error_report_normalize_json_payload(array $payload, int $depth = 0): array
{
    if ($depth >= 4) {
        return [];
    }

    $normalized = [];
    $items = array_slice($payload, 0, 50, true);

    foreach ($items as $key => $value) {
        $normalizedKey = is_int($key) ? $key : cms_admin_error_report_normalize_json_key($key);

        if (is_array($value)) {
            $normalized[$normalizedKey] = cms_admin_error_report_normalize_json_payload($value, $depth + 1);
            continue;
        }

        $normalized[$normalizedKey] = cms_admin_error_report_normalize_json_scalar($value);
    }

    return $normalized;
}

function cms_admin_error_report_normalize_source_url(mixed $value, string $fallback): string
{
    $source = cms_admin_error_report_limit_string($value, 500);
    if ($source === '') {
        return $fallback;
    }

    if (strpbrk($source, "\r\n\0") !== false) {
        return $fallback;
    }

    if (str_starts_with($source, '/')) {
        return SITE_URL . $source;
    }

    $siteUrl = (string) SITE_URL;
    if (str_starts_with($source, $siteUrl)) {
        return $source;
    }

    return $fallback;
}

function cms_admin_error_report_build_payload(array $post, string $redirectUrl): array
{
    return [
        'title' => cms_admin_error_report_limit_string($post['title'] ?? 'Fehlerreport', 255),
        'message' => cms_admin_error_report_limit_string($post['message'] ?? '', 2000),
        'error_code' => cms_admin_error_report_limit_string($post['error_code'] ?? '', 120),
        'source_url' => cms_admin_error_report_normalize_source_url($post['source_url'] ?? $redirectUrl, $redirectUrl),
        'error_data' => cms_admin_error_report_decode_json_payload((string) ($post['error_data_json'] ?? '[]')),
        'context' => cms_admin_error_report_decode_json_payload((string) ($post['context_json'] ?? '[]')),
    ];
}

function cms_admin_error_report_handle_request(array $post, string $redirectUrl): array
{
    return ErrorReportService::getInstance()->createReport(
        cms_admin_error_report_build_payload($post, $redirectUrl)
    );
}

$postActionShellConfig = [
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin(),
    'access_denied_url' => SITE_URL,
    'csrf_action' => 'admin_error_report',
    'invalid_token_message' => 'Sicherheitstoken für den Fehlerreport ist ungültig.',
    'unknown_action_message' => 'Fehlerreport konnte nicht verarbeitet werden.',
    'redirect_resolver' => static fn (array $post, array $server): string => cms_admin_error_report_resolve_redirect_url($post, $server),
    'handler' => static fn (array $post, array $server, string $redirectUrl): array => cms_admin_error_report_handle_request($post, $redirectUrl),
];

require __DIR__ . '/partials/post-action-shell.php';