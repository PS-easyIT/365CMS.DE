<?php
declare(strict_types=1);

/**
 * Seiten – Entry Point
 *
 * Route: /admin/pages
 * Actions: list (default), edit, save, delete, bulk
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Auth;
use CMS\Security;

// ─── Auth-Check ────────────────────────────────────────────────────────────
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

// ─── Module laden ──────────────────────────────────────────────────────────
require_once __DIR__ . '/modules/pages/PagesModule.php';
$module    = new PagesModule();
$alert     = null;

// ─── POST-Verarbeitung ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'admin_pages')) {
        $alert = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.'];
    } else {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'save':
                $userId = Auth::instance()->getCurrentUser()->id ?? 0;
                $result = $module->save($_POST, (int)$userId);
                if ($result['success']) {
                    // Redirect nach Save mit Erfolgsmeldung
                    $_SESSION['admin_alert'] = ['type' => 'success', 'message' => $result['message']];
                    header('Location: ' . SITE_URL . '/admin/pages?action=edit&id=' . $result['id']);
                    exit;
                } else {
                    $alert = ['type' => 'danger', 'message' => $result['error']];
                }
                break;

            case 'delete':
                $id     = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                $_SESSION['admin_alert'] = [
                    'type'    => $result['success'] ? 'success' : 'danger',
                    'message' => $result['success'] ? $result['message'] : $result['error'],
                ];
                header('Location: ' . SITE_URL . '/admin/pages');
                exit;

            case 'bulk':
                $bulkAction = $_POST['bulk_action'] ?? '';
                $bulkIds    = array_filter(explode(',', $_POST['bulk_ids'] ?? ''));
                $result     = $module->bulkAction($bulkAction, $bulkIds);
                $_SESSION['admin_alert'] = [
                    'type'    => $result['success'] ? 'success' : 'danger',
                    'message' => $result['success'] ? $result['message'] : $result['error'],
                ];
                header('Location: ' . SITE_URL . '/admin/pages');
                exit;
        }
    }
    // Neues CSRF-Token nach verbrauchtem
    $csrfToken = Security::instance()->generateToken('admin_pages');
}

// Session-Alert abholen
if (isset($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken = Security::instance()->generateToken('admin_pages');

// ─── View bestimmen ────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'list';

$pageTitle  = 'Seiten';
$activePage = 'pages';
$pageAssets = [];

if ($action === 'edit') {
    $assetsUrl  = defined('ASSETS_URL') ? ASSETS_URL : (defined('SITE_URL') ? SITE_URL : '') . '/assets';
    $pageAssets['js'] = [
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
    ];
}

// ─── Layout rendern ────────────────────────────────────────────────────────
$inlineJs = '';

require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/sidebar.php';

if ($action === 'edit') {
    $id       = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $editData = $module->getEditData($id);
    $pageTitle = $editData['isNew'] ? 'Neue Seite' : 'Seite bearbeiten';
    require_once __DIR__ . '/views/pages/edit.php';

    // EditorJS-Initialisierung (läuft in footer.php NACH den deferred Scripts)
    $inlineJs = sprintf(
        "(function(){
            if(typeof createCmsEditor!=='function'){console.warn('EditorJS nicht verfügbar');return;}
            var inp=document.getElementById('editorContent'),data=null;
            if(inp&&inp.value){try{var p=JSON.parse(inp.value);if(p&&p.blocks&&p.blocks.length)data=p;}catch(e){}}
            var ed=createCmsEditor('editorjs',data,%%s,%%s);
            var form=document.getElementById('pageForm');
            if(form){form.addEventListener('submit',function(e){
                e.preventDefault();var f=this;
                ed.save().then(function(o){inp.value=JSON.stringify(o);f.submit();}).catch(function(){f.submit();});
            });}
        })();",
        json_encode((defined('SITE_URL') ? SITE_URL : '') . '/api/media'),
        json_encode($csrfToken)
    );
} else {
    $listData = $module->getListData();
    require_once __DIR__ . '/views/pages/list.php';
}

require_once __DIR__ . '/partials/footer.php';
