<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media – Entry Point
 * Route: /admin/media
 *
 * Tabs: library (Standard), categories, settings
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/media/MediaModule.php';
$module    = new MediaModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_media')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/media');
        exit;
    }

    $path = $_POST['parent_path'] ?? $_POST['target_path'] ?? '';
    $redir = SITE_URL . '/admin/media' . ($path !== '' ? '?path=' . urlencode($path) : '');

    switch ($action) {
        case 'upload':
            $uploaded = 0;
            $errors   = [];
            if (!empty($_FILES['files']['name'][0])) {
                foreach ($_FILES['files']['name'] as $i => $name) {
                    $file = [
                        'name'     => $_FILES['files']['name'][$i],
                        'type'     => $_FILES['files']['type'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'error'    => $_FILES['files']['error'][$i],
                        'size'     => $_FILES['files']['size'][$i],
                    ];
                    $result = $module->uploadFile($file, $path);
                    if (!empty($result['success'])) {
                        $uploaded++;
                    } else {
                        $errors[] = htmlspecialchars($name) . ': ' . ($result['error'] ?? 'Fehler');
                    }
                }
            }
            if ($uploaded > 0) {
                $_SESSION['admin_alert'] = ['type' => 'success', 'message' => $uploaded . ' Datei(en) hochgeladen.' . (!empty($errors) ? ' Fehler: ' . implode(', ', $errors) : '')];
            } else {
                $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Upload fehlgeschlagen.' . (!empty($errors) ? ' ' . implode(', ', $errors) : '')];
            }
            header('Location: ' . $redir);
            exit;

        case 'create_folder':
            $folderName = trim($_POST['folder_name'] ?? '');
            $parentPath = $_POST['parent_path'] ?? '';
            $result = $module->createFolder($folderName, $parentPath);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . $redir);
            exit;

        case 'delete_item':
            $itemPath = $_POST['item_path'] ?? '';
            $result   = $module->deleteItem($itemPath);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . $redir);
            exit;

        case 'rename_item':
            $oldPath = $_POST['old_path'] ?? '';
            $newName = trim($_POST['new_name'] ?? '');
            $result  = $module->renameItem($oldPath, $newName);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . $redir);
            exit;

        case 'assign_category':
            $filePath = $_POST['file_path'] ?? '';
            $catSlug  = $_POST['category_slug'] ?? '';
            $result   = $module->assignCategory($filePath, $catSlug);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . $redir);
            exit;

        case 'add_category':
            $name   = trim($_POST['name'] ?? '');
            $slug   = trim($_POST['slug'] ?? '');
            $result = $module->addCategory($name, $slug);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/media?tab=categories');
            exit;

        case 'delete_category':
            $slug   = $_POST['slug'] ?? '';
            $result = $module->deleteCategory($slug);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/media?tab=categories');
            exit;

        case 'save_settings':
            $result = $module->saveSettings($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/media?tab=settings');
            exit;
    }
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_media');
$activePage = 'media';

// ─── Tab-Routing ─────────────────────────────────────────
$tab = $_GET['tab'] ?? 'library';

switch ($tab) {
    case 'categories':
        $data      = $module->getCategoriesData();
        $pageTitle = 'Medien – Kategorien';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/categories.php';
        require __DIR__ . '/partials/footer.php';
        break;

    case 'settings':
        $data      = $module->getSettingsData();
        $pageTitle = 'Medien – Einstellungen';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/settings.php';
        require __DIR__ . '/partials/footer.php';
        break;

    default:
        $data      = $module->getLibraryData();
        $pageTitle = 'Medien';
        require __DIR__ . '/partials/header.php';
        require __DIR__ . '/partials/sidebar.php';
        require __DIR__ . '/views/media/library.php';
        require __DIR__ . '/partials/footer.php';
        break;
}
