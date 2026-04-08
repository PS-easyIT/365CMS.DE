<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_POST_CATEGORIES_WRITE_CAPABILITY = 'edit_all_posts';

if (!Auth::instance()->isAdmin() || !Auth::instance()->hasCapability(CMS_ADMIN_POST_CATEGORIES_WRITE_CAPABILITY)) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/posts/PostsModule.php';

$module = new PostsModule();
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_post_categories')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.'];
        header('Location: ' . SITE_URL . '/admin/post-categories');
        exit;
    }

    switch ($action) {
        case 'save_category':
            $result = $module->saveCategory($_POST);
            break;
        case 'delete_category':
            $result = $module->deleteCategory(
                (int) ($_POST['cat_id'] ?? 0),
                (int) ($_POST['replacement_category_id'] ?? 0)
            );
            break;
        case 'delete_categories_with_replacement':
            $result = $module->deleteCategoriesWithStoredReplacement();
            break;
        default:
            $result = ['success' => false, 'error' => 'Unbekannte Aktion.'];
            break;
    }

    $_SESSION['admin_alert'] = [
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? 'Aktion abgeschlossen.'),
    ];

    header('Location: ' . SITE_URL . '/admin/post-categories');
    exit;
}

if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_post_categories');
$data = $module->getCategoryAdminData();
$editCategoryId = max(0, (int) ($_GET['edit'] ?? 0));
$editCategory = null;

if ($editCategoryId > 0) {
    foreach (($data['categories'] ?? []) as $category) {
        if ((int) ($category['id'] ?? 0) === $editCategoryId) {
            $editCategory = $category;
            break;
        }
    }
}

$pageTitle = 'Beitrags-Kategorien';
$activePage = 'post-categories';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/sidebar.php';
require __DIR__ . '/views/posts/categories.php';
require __DIR__ . '/partials/footer.php';
