<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;
use CMS\Services\ErrorReportService;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

function cms_admin_error_report_default_url(): string
{
    return SITE_URL . '/admin/diagnose';
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

function cms_admin_error_report_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_error_report_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? 'Fehlerreport konnte nicht verarbeitet werden.')),
    ];
}

function cms_admin_error_report_flash_result(array $result): void
{
    cms_admin_error_report_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Fehlerreport konnte nicht verarbeitet werden.'),
    ]);
}

function cms_admin_error_report_decode_json_payload(string $payload): array
{
    $decoded = json_decode($payload, true);

    return is_array($decoded) ? $decoded : [];
}

function cms_admin_error_report_build_payload(array $post, string $redirectUrl): array
{
    return [
        'title' => (string) ($post['title'] ?? 'Fehlerreport'),
        'message' => (string) ($post['message'] ?? ''),
        'error_code' => (string) ($post['error_code'] ?? ''),
        'source_url' => (string) ($post['source_url'] ?? $redirectUrl),
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

$redirectUrl = cms_admin_error_report_resolve_redirect_url($_POST, $_SERVER);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cms_admin_error_report_redirect($redirectUrl);
}

if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_error_report')) {
    cms_admin_error_report_flash(['type' => 'danger', 'message' => 'Sicherheitstoken für den Fehlerreport ist ungültig.']);
    cms_admin_error_report_redirect($redirectUrl);
}

$result = cms_admin_error_report_handle_request($_POST, $redirectUrl);

cms_admin_error_report_flash_result($result);

cms_admin_error_report_redirect($redirectUrl);