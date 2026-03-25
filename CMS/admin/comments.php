<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comments – Entry Point
 * Route: /admin/comments
 */

use CMS\Auth;
use CMS\Security;

require_once __DIR__ . '/modules/comments/CommentsModule.php';

$module = new CommentsModule();

const CMS_ADMIN_COMMENTS_ALLOWED_ACTIONS = ['status', 'delete', 'bulk'];
const CMS_ADMIN_COMMENTS_ALLOWED_STATUSES = ['pending', 'approved', 'spam', 'trash'];
const CMS_ADMIN_COMMENTS_ALLOWED_BULK_ACTIONS = ['approve', 'spam', 'trash', 'delete'];
const CMS_ADMIN_COMMENTS_MAX_BULK_IDS = 100;

function cms_admin_comments_redirect(CommentsModule $module, string $status): never
{
    header('Location: ' . $module->buildListUrl($status));
    exit;
}

function cms_admin_comments_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
    ];
}

function cms_admin_comments_flash_result(array $result): void
{
    cms_admin_comments_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
    ]);
}

function cms_admin_comments_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_comments_normalize_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_COMMENTS_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_comments_normalize_positive_id(mixed $id): int
{
    $normalizedId = (int)$id;

    return $normalizedId > 0 ? $normalizedId : 0;
}

function cms_admin_comments_normalize_status_value(mixed $status): string
{
    $normalizedStatus = strtolower(trim((string)$status));

    return in_array($normalizedStatus, CMS_ADMIN_COMMENTS_ALLOWED_STATUSES, true) ? $normalizedStatus : '';
}

function cms_admin_comments_normalize_bulk_action(mixed $action): string
{
    $normalizedAction = strtolower(trim((string)$action));

    return in_array($normalizedAction, CMS_ADMIN_COMMENTS_ALLOWED_BULK_ACTIONS, true) ? $normalizedAction : '';
}

/**
 * @return list<int>
 */
function cms_admin_comments_normalize_bulk_ids(mixed $ids): array
{
    if (!is_array($ids)) {
        return [];
    }

    $normalized = [];

    foreach ($ids as $id) {
        $value = cms_admin_comments_normalize_positive_id($id);
        if ($value <= 0) {
            continue;
        }

        $normalized[$value] = $value;
        if (count($normalized) >= CMS_ADMIN_COMMENTS_MAX_BULK_IDS) {
            break;
        }
    }

    return array_values($normalized);
}

function cms_admin_comments_handle_action(CommentsModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'status':
            return $module->updateStatus(
                cms_admin_comments_normalize_positive_id($post['id'] ?? 0),
                cms_admin_comments_normalize_status_value($post['new_status'] ?? '')
            );

        case 'delete':
            return $module->delete(cms_admin_comments_normalize_positive_id($post['id'] ?? 0));

        case 'bulk':
            $bulkAction = cms_admin_comments_normalize_bulk_action($post['bulk_action'] ?? '');
            $ids = cms_admin_comments_normalize_bulk_ids($post['ids'] ?? []);

            return $module->bulkAction($bulkAction, $ids);
    }

    return ['success' => false, 'error' => 'Aktion konnte nicht verarbeitet werden.'];
}

if (!Auth::isLoggedIn() || !$module->canView()) {
    header('Location: ' . SITE_URL);
    exit;
}

$alert  = null;
$status = $module->normalizeStatusFilter((string)($_GET['status'] ?? 'all'));

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cms_admin_comments_normalize_action($_POST['action'] ?? '');
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if ($action === '' || !$module->isSupportedAction($action)) {
        cms_admin_comments_flash(['type' => 'danger', 'message' => 'Unbekannte Kommentar-Aktion.']);
    } elseif (!Security::instance()->verifyToken($postToken, 'admin_comments')) {
        cms_admin_comments_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } else {
        $result = cms_admin_comments_handle_action($module, $action, $_POST);
        cms_admin_comments_flash_result($result);
    }

    // PRG-Redirect mit Status-Erhaltung
    cms_admin_comments_redirect($module, $status);
}

// ─── Session-Alert abholen ───────────────────────────────
$alert = cms_admin_comments_pull_alert();

$csrfToken = Security::instance()->generateToken('admin_comments');

// ─── View ────────────────────────────────────────────────
$data       = $module->getListData();
$pageTitle  = 'Kommentare';
$activePage = 'comments';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/comments/list.php';
require __DIR__ . '/partials/footer.php';
