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

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/comments/CommentsModule.php';
$module    = new CommentsModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_comments')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/comments');
        exit;
    }

    switch ($action) {
        case 'status':
            $id        = (int)($_POST['id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            $result    = $module->updateStatus($id, $newStatus);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            break;

        case 'delete':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->delete($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            break;

        case 'bulk':
            $bulkAction = $_POST['bulk_action'] ?? '';
            $ids        = $_POST['ids'] ?? [];
            $result     = $module->bulkAction($bulkAction, $ids);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            break;
    }

    // PRG-Redirect mit Status-Erhaltung
    $redirectStatus = $_GET['status'] ?? '';
    $redirectUrl    = SITE_URL . '/admin/comments';
    if ($redirectStatus && in_array($redirectStatus, ['pending', 'approved', 'spam', 'trash'], true)) {
        $redirectUrl .= '?status=' . $redirectStatus;
    }
    header('Location: ' . $redirectUrl);
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
