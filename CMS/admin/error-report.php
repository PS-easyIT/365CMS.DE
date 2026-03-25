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

function cms_normalize_admin_report_redirect(string $target): string
{
    $fallback = SITE_URL . '/admin/diagnose';
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

$redirectUrl = cms_normalize_admin_report_redirect((string)($_POST['back_to'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));

function cms_admin_error_report_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_error_report_flash(array $result): void
{
    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Fehlerreport konnte nicht verarbeitet werden.'),
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cms_admin_error_report_redirect($redirectUrl);
}

if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_error_report')) {
    $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken für den Fehlerreport ist ungültig.'];
    cms_admin_error_report_redirect($redirectUrl);
}

$errorData = json_decode((string)($_POST['error_data_json'] ?? '[]'), true);
$context = json_decode((string)($_POST['context_json'] ?? '[]'), true);

$result = ErrorReportService::getInstance()->createReport([
    'title' => (string)($_POST['title'] ?? 'Fehlerreport'),
    'message' => (string)($_POST['message'] ?? ''),
    'error_code' => (string)($_POST['error_code'] ?? ''),
    'source_url' => (string)($_POST['source_url'] ?? $redirectUrl),
    'error_data' => is_array($errorData) ? $errorData : [],
    'context' => is_array($context) ? $context : [],
]);

cms_admin_error_report_flash($result);

cms_admin_error_report_redirect($redirectUrl);