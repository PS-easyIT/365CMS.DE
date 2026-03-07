<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Posts – Entry Point
 * Route: /admin/posts
 */

use CMS\Auth;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/posts/PostsModule.php';
$module    = new PostsModule();
$user      = Auth::instance()->getCurrentUser();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_posts')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.'];
        header('Location: ' . SITE_URL . '/admin/posts');
        exit;
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST, (int)($user['id'] ?? 0));
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if ($result['success']) {
                header('Location: ' . SITE_URL . '/admin/posts?action=edit&id=' . ($result['id'] ?? 0));
            } else {
                header('Location: ' . SITE_URL . '/admin/posts');
            }
            exit;

        case 'delete':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->delete($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/posts');
            exit;

        case 'bulk':
            $bulkAction = $_POST['bulk_action'] ?? '';
            $ids        = $_POST['ids'] ?? [];
            $result     = $module->bulkAction($bulkAction, $ids);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/posts');
            exit;

        case 'save_category':
            $result = $module->saveCategory($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/posts');
            exit;

        case 'delete_category':
            $catId  = (int)($_POST['cat_id'] ?? 0);
            $result = $module->deleteCategory($catId);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/posts');
            exit;
    }
}

// ─── Session-Alert abholen ───────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

// CSRF-Token erneuern nach POST-Redirect
$csrfToken = Security::instance()->generateToken('admin_posts');

// ─── View-Routing ────────────────────────────────────────
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'edit') {
    $id        = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data      = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neuer Beitrag' : 'Beitrag bearbeiten';
    $activePage = 'posts';
    $assetsUrl  = defined('ASSETS_URL') ? ASSETS_URL : (defined('SITE_URL') ? SITE_URL : '') . '/assets';
    $pageAssets = [
        'js' => [
            $assetsUrl . '/editorjs/editorjs.umd.js',
            $assetsUrl . '/editorjs/header.umd.js',
            $assetsUrl . '/editorjs/paragraph.umd.js',
            $assetsUrl . '/editorjs/editorjs-list.umd.js',
            $assetsUrl . '/editorjs/checklist.umd.js',
            $assetsUrl . '/editorjs/quote.umd.js',
            $assetsUrl . '/editorjs/warning.umd.js',
            $assetsUrl . '/editorjs/code.umd.js',
            $assetsUrl . '/editorjs/raw.umd.js',
            $assetsUrl . '/editorjs/table.umd.js',
            $assetsUrl . '/editorjs/inline-code.umd.js',
            $assetsUrl . '/editorjs/underline.umd.js',
            $assetsUrl . '/editorjs/delimiter.umd.js',
            $assetsUrl . '/editorjs/image.umd.js',
            $assetsUrl . '/editorjs/link.umd.js',
            $assetsUrl . '/editorjs/attaches.umd.js',
            $assetsUrl . '/js/editor-init.js',
        ],
    ];

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/posts/edit.php';

    // EditorJS-Initialisierung (läuft in footer.php NACH den deferred Scripts)
    $inlineJs = sprintf(
        "(function(){
            if(typeof createCmsEditor!=='function'){console.warn('EditorJS nicht verfügbar');return;}
            var inp=document.getElementById('contentInput'),data=null;
            if(inp&&inp.value){try{var p=JSON.parse(inp.value);if(p&&p.blocks&&p.blocks.length)data=p;}catch(e){}}
            var ed=createCmsEditor('editorjs',data,%%s,%%s);
            var form=document.getElementById('postForm');
            if(form){form.addEventListener('submit',function(e){
                e.preventDefault();var f=this;
                ed.save().then(function(o){inp.value=JSON.stringify(o);f.submit();}).catch(function(){f.submit();});
            });}
        })();",
        json_encode((defined('SITE_URL') ? SITE_URL : '') . '/api/media'),
        json_encode($csrfToken)
    );

    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Beiträge';
    $activePage = 'posts';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/posts/list.php';
    require __DIR__ . '/partials/footer.php';
}
