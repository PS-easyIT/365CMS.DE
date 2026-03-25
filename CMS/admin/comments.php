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

if (!Auth::isLoggedIn() || !$module->canView()) {
    header('Location: ' . SITE_URL);
    exit;
}

$alert  = null;
$status = $module->normalizeStatusFilter((string)($_GET['status'] ?? 'all'));

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = trim((string)($_POST['action'] ?? ''));
    $postToken = $_POST['csrf_token'] ?? '';

    if (!$module->isSupportedAction($action)) {
        cms_admin_comments_flash(['type' => 'danger', 'message' => 'Unbekannte Kommentar-Aktion.']);
    } elseif (!Security::instance()->verifyToken($postToken, 'admin_comments')) {
        cms_admin_comments_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
    } else {
        $result = ['success' => false, 'error' => 'Aktion konnte nicht verarbeitet werden.'];

        switch ($action) {
            case 'status':
                $result = $module->updateStatus(
                    (int)($_POST['id'] ?? 0),
                    (string)($_POST['new_status'] ?? '')
                );
                break;

            case 'delete':
                $result = $module->delete((int)($_POST['id'] ?? 0));
                break;

            case 'bulk':
                $bulkAction = $_POST['bulk_action'] ?? '';
                $ids        = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
                $result     = $module->bulkAction((string)$bulkAction, $ids);
                break;
        }

        cms_admin_comments_flash_result($result);
    }

    // PRG-Redirect mit Status-Erhaltung
    header('Location: ' . $module->buildListUrl($status));
    exit;
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_comments');

// ─── View ────────────────────────────────────────────────
$data       = $module->getListData();
$pageTitle  = 'Kommentare';
$activePage = 'comments';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/comments/list.php';
require __DIR__ . '/partials/footer.php';
