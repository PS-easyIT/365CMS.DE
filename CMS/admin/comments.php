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

function cms_admin_comments_handle_action(CommentsModule $module, string $action, array $post): array
{
    switch ($action) {
        case 'status':
            return $module->updateStatus(
                (int) ($post['id'] ?? 0),
                (string) ($post['new_status'] ?? '')
            );

        case 'delete':
            return $module->delete((int) ($post['id'] ?? 0));

        case 'bulk':
            $bulkAction = (string) ($post['bulk_action'] ?? '');
            $ids = is_array($post['ids'] ?? null) ? $post['ids'] : [];

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
    $action = trim((string) ($_POST['action'] ?? ''));
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if (!$module->isSupportedAction($action)) {
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
