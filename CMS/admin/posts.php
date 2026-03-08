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
use CMS\Services\EditorJsService;
use CMS\Services\EditorService;

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
            $result = $module->save($_POST, (int)($user->id ?? 0));
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
$editorMediaToken = Security::instance()->generateToken('editorjs_media');

// ─── View-Routing ────────────────────────────────────────
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'edit') {
    $id        = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $data      = $module->getEditData($id);
    $pageTitle = $data['isNew'] ? 'Neuer Beitrag' : 'Beitrag bearbeiten';
    $activePage = 'posts';
    $useEditorJs = EditorService::isEditorJs();
    $pageAssets = [];

    if ($useEditorJs) {
        $pageAssets = EditorJsService::getInstance()->getPageAssets();
    } else {
        EditorService::getInstance();
    }

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/posts/edit.php';

    $inlineJs = '';
    if ($useEditorJs) {
        // EditorJS-Initialisierung (läuft in footer.php NACH den deferred Scripts)
        $inlineJs = sprintf(
            "(function(){
                if(typeof createCmsEditor!=='function'){console.warn('EditorJS nicht verfügbar');return;}
                var inp=document.getElementById('contentInput');
                var rawContent=inp?inp.value:'';
                var ed=createCmsEditor('editorjs',rawContent,%s,%s);
                var form=document.getElementById('postForm');
                if(form){form.addEventListener('submit',function(e){
                    e.preventDefault();var f=this;
                    ed.save().then(function(o){inp.value=JSON.stringify(o);f.submit();}).catch(function(){f.submit();});
                });}
            })();",
            json_encode((defined('SITE_URL') ? SITE_URL : '') . '/api/media'),
            json_encode($editorMediaToken)
        );
    }

    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Beiträge';
    $activePage = 'posts';
    $assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : SITE_URL . '/assets';
    $pageAssets = [
        'css' => [
            $assetsUrl . '/gridjs/mermaid.min.css',
        ],
        'js' => [
            $assetsUrl . '/gridjs/gridjs.umd.js',
            $assetsUrl . '/js/gridjs-init.js?v=' . (@filemtime(ASSETS_PATH . 'js/gridjs-init.js') ?: time()),
        ],
    ];
    $inlineJs = sprintf(
        "(function(){
            if(typeof cmsGrid!=='function'){return;}
            cmsGrid('#postsGrid', {
                url: %s,
                search: false,
                limit: 20,
                extraParams: {
                    status: %s,
                    category: %s,
                    search: %s
                },
                sortMap: {0:'title',2:'status',4:'updated_at'},
                columns: [
                    {
                        id: 'title',
                        name: 'Titel',
                        data: function(row){ return { id: row.id, title: row.title, slug: row.slug }; },
                        formatter: function(cell){
                            return gridjs.html(
                                '<div>' +
                                    '<a href=\"' + %s + '/admin/posts?action=edit&id=' + encodeURIComponent(cell.id) + '\" class=\"text-reset\">' + window.cmsEsc(cell.title || '') + '</a>' +
                                    '<div class=\"text-secondary small\">/blog/' + window.cmsEsc(cell.slug || '') + '</div>' +
                                '</div>'
                            );
                        }
                    },
                    {
                        id: 'category_name',
                        name: 'Kategorie',
                        formatter: function(cell){
                            return cell ? gridjs.html('<span class=\"badge bg-azure-lt\">' + window.cmsEsc(cell) + '</span>') : '–';
                        }
                    },
                    {
                        id: 'status',
                        name: 'Status',
                        formatter: function(cell){
                            var cls = cell === 'published' ? 'success' : 'warning';
                            var label = cell === 'published' ? 'Veröffentlicht' : 'Entwurf';
                            return gridjs.html('<span class=\"badge bg-' + cls + '-lt\">' + label + '</span>');
                        }
                    },
                    { id: 'author_name', name: 'Autor' },
                    { id: 'updated_at', name: 'Datum' },
                    {
                        id: 'id',
                        name: '',
                        sort: false,
                        formatter: function(cell){
                            return gridjs.html('<a href=\"' + %s + '/admin/posts?action=edit&id=' + encodeURIComponent(cell) + '\" class=\"btn btn-ghost-primary btn-icon btn-sm\" title=\"Bearbeiten\">✎</a>');
                        }
                    }
                ]
            });
        })();",
        json_encode(SITE_URL . '/api/v1/admin/posts'),
        json_encode((string)($data['filter'] ?? '')),
        json_encode((int)($data['catFilter'] ?? 0)),
        json_encode((string)($data['search'] ?? '')),
        json_encode(SITE_URL),
        json_encode(SITE_URL)
    );

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/posts/list.php';
    require __DIR__ . '/partials/footer.php';
}
