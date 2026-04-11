<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_POST_TAGS_WRITE_CAPABILITY = 'edit_all_posts';

function cms_admin_post_tags_target_url(?int $editId = null): string
{
    if ($editId !== null && $editId > 0) {
        return '/admin/post-tags?edit=' . $editId;
    }

    return '/admin/post-tags';
}

function cms_admin_post_tags_redirect(?int $editId = null): never
{
    header('Location: ' . cms_admin_post_tags_target_url($editId));
    exit;
}

if (!Auth::instance()->isAdmin() || !Auth::instance()->hasCapability(CMS_ADMIN_POST_TAGS_WRITE_CAPABILITY)) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/modules/posts/PostsModule.php';

$module = new PostsModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $postToken = (string) ($_POST['csrf_token'] ?? '');
    $redirectEditId = 0;

    if (!Security::instance()->verifyToken($postToken, 'admin_post_tags')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.'];
        cms_admin_post_tags_redirect();
    }

    switch ($action) {
        case 'save_tag':
            $redirectEditId = max(0, (int) ($_POST['tag_id'] ?? 0));
            $result = $module->saveTag($_POST);
            break;
        case 'delete_tag':
            $result = $module->deleteTag(
                (int) ($_POST['tag_id'] ?? 0),
                (int) ($_POST['replacement_tag_id'] ?? 0)
            );
            break;
        default:
            $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
            break;
    }

    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Aktion abgeschlossen.'),
    ];

    cms_admin_post_tags_redirect(!empty($result['success']) ? null : ($redirectEditId > 0 ? $redirectEditId : null));
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_post_tags');
$editTagId = max(0, (int) ($_GET['edit'] ?? 0));
$data = $module->getTagAdminData($editTagId);
$editTag = $data['editTag'] ?? null;
$pageTitle = 'Beitrags-Tags';
$activePage = 'post-tags';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/posts/tags.php';
require __DIR__ . '/partials/footer.php';
