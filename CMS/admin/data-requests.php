<?php
declare(strict_types=1);

/**
 * Auskunft & Löschen – Entry Point
 * Route: /admin/data-requests
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/legal/PrivacyRequestsModule.php';
require_once __DIR__ . '/modules/legal/DeletionRequestsModule.php';

$privacyModule  = new PrivacyRequestsModule();
$deletionModule = new DeletionRequestsModule();
$alert          = null;

function cms_admin_data_requests_redirect(): never
{
    header('Location: ' . SITE_URL . '/admin/data-requests');
    exit;
}

function cms_admin_data_requests_normalize_alert(array $payload): array
{
    return [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_data_requests_flash(array $payload): void
{
    $_SESSION['admin_alert'] = cms_admin_data_requests_normalize_alert($payload);
}

function cms_admin_data_requests_flash_result(array $result): void
{
    cms_admin_data_requests_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_data_requests_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

/** @return array<string, array<string, true>> */
function cms_admin_data_requests_allowed_actions_by_scope(): array
{
    return [
        'privacy' => [
            'process' => true,
            'complete' => true,
            'reject' => true,
            'delete' => true,
        ],
        'deletion' => [
            'process' => true,
            'execute' => true,
            'reject' => true,
            'delete' => true,
        ],
    ];
}

function cms_admin_data_requests_normalize_scope(mixed $value): ?string
{
    $scope = strtolower(trim((string) $value));
    $allowedScopes = cms_admin_data_requests_allowed_actions_by_scope();

    return isset($allowedScopes[$scope]) ? $scope : null;
}

function cms_admin_data_requests_normalize_action(?string $scope, mixed $value): ?string
{
    if ($scope === null) {
        return null;
    }

    $action = strtolower(trim((string) $value));
    $allowedActions = cms_admin_data_requests_allowed_actions_by_scope()[$scope] ?? [];

    return isset($allowedActions[$action]) ? $action : null;
}

function cms_admin_data_requests_normalize_id(array $post): int
{
    $id = (int) ($post['id'] ?? 0);

    return $id > 0 ? $id : 0;
}

function cms_admin_data_requests_normalize_reason(array $post): string
{
    $reason = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) ($post['reject_reason'] ?? ''));
    $reason = preg_replace('/\s+/u', ' ', (string) $reason);

    return trim((string) $reason);
}

function cms_admin_data_requests_handle_privacy_action(PrivacyRequestsModule $privacyModule, string $action, array $post): array
{
    $id = cms_admin_data_requests_normalize_id($post);
    $reason = cms_admin_data_requests_normalize_reason($post);

    return match ($action) {
        'process'  => $privacyModule->processRequest($id),
        'complete' => $privacyModule->completeRequest($id),
        'reject'   => $privacyModule->rejectRequest($id, $reason),
        'delete'   => $privacyModule->deleteRequest($id),
        default    => ['success' => false, 'error' => 'Unbekannte Auskunfts-Aktion.'],
    };
}

function cms_admin_data_requests_handle_deletion_action(DeletionRequestsModule $deletionModule, string $action, array $post): array
{
    $id = cms_admin_data_requests_normalize_id($post);
    $reason = cms_admin_data_requests_normalize_reason($post);

    return match ($action) {
        'process' => $deletionModule->processRequest($id),
        'execute' => $deletionModule->executeDeletion($id),
        'reject'  => $deletionModule->rejectRequest($id, $reason),
        'delete'  => $deletionModule->deleteRequest($id),
        default   => ['success' => false, 'error' => 'Unbekannte Lösch-Aktion.'],
    };
}

function cms_admin_data_requests_handle_scope_action(
    PrivacyRequestsModule $privacyModule,
    DeletionRequestsModule $deletionModule,
    string $scope,
    string $action,
    array $post
): array {
    return match ($scope) {
        'privacy' => cms_admin_data_requests_handle_privacy_action($privacyModule, $action, $post),
        'deletion' => cms_admin_data_requests_handle_deletion_action($deletionModule, $action, $post),
        default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'admin_data_requests')) {
        cms_admin_data_requests_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_data_requests_redirect();
    }

    $scope = cms_admin_data_requests_normalize_scope($_POST['scope'] ?? null);
    $action = cms_admin_data_requests_normalize_action($scope, $_POST['action'] ?? null);

    if ($scope === null || $action === null) {
        cms_admin_data_requests_flash(['type' => 'danger', 'message' => 'Unbekannte oder nicht erlaubte Aktion.']);
        cms_admin_data_requests_redirect();
    }

    $result = cms_admin_data_requests_handle_scope_action($privacyModule, $deletionModule, $scope, $action, $_POST);

    cms_admin_data_requests_flash_result($result);
    cms_admin_data_requests_redirect();
}

$alert = cms_admin_data_requests_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_data_requests');
$pageTitle  = 'Auskunft & Löschen';
$activePage = 'data-requests';
$data       = [
    'privacy' => $privacyModule->getData(),
    'deletion' => $deletionModule->getData(),
];

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';
require_once __DIR__ . '/views/legal/data-requests.php';
require_once __DIR__ . '/partials/footer.php';
