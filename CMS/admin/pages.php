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
use CMS\Services\EditorJsService;
use CMS\Services\EditorService;

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
$editorMediaToken = Security::instance()->generateToken('editorjs_media');

// ─── View bestimmen ────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'list';

$pageTitle  = 'Seiten';
$activePage = 'pages';
$pageAssets = [];
$useEditorJs = false;

if ($action === 'edit') {
    $useEditorJs = EditorService::isEditorJs();

    if ($useEditorJs) {
        $pageAssets = EditorJsService::getInstance()->getPageAssets();
    } else {
        EditorService::getInstance();
    }
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

    if ($useEditorJs) {
        // EditorJS-Initialisierung (läuft in footer.php NACH den deferred Scripts)
        $inlineJs = sprintf(
            "(function(){
                if(typeof createCmsEditor!=='function'){console.warn('EditorJS nicht verfügbar');return;}
                var inp=document.getElementById('editorContent');
                var rawContent=inp?inp.value:'';
                var ed=createCmsEditor('editorjs',rawContent,%s,%s);
                var form=document.getElementById('pageForm');
                if(form){form.addEventListener('submit',function(e){
                    e.preventDefault();var f=this;
                    ed.save().then(function(o){inp.value=JSON.stringify(o);f.submit();}).catch(function(){f.submit();});
                });}
            })();",
            json_encode((defined('SITE_URL') ? SITE_URL : '') . '/api/media'),
            json_encode($editorMediaToken)
        );
    }
} else {
    $listData = $module->getListData();
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
            cmsGrid('#pagesGrid', {
                url: %s,
                search: false,
                limit: 20,
                extraParams: {
                    status: %s,
                    search: %s
                },
                sortMap: {0:'title',1:'slug',2:'status',3:'updated_at'},
                columns: [
                    {
                        id: 'title',
                        name: 'Titel',
                        data: function(row){ return { id: row.id, title: row.title, slug: row.slug }; },
                        formatter: function(cell){
                            return gridjs.html(
                                '<div>' +
                                    '<a href=\"' + %s + '/admin/pages?action=edit&id=' + encodeURIComponent(cell.id) + '\" class=\"text-reset fw-medium\">' + window.cmsEsc(cell.title || '') + '</a>' +
                                '</div>'
                            );
                        }
                    },
                    {
                        id: 'slug',
                        name: 'Slug',
                        formatter: function(cell){ return '/' + window.cmsEsc(cell || ''); }
                    },
                    {
                        id: 'status',
                        name: 'Status',
                        formatter: function(cell){
                            var map = { published: 'bg-green', draft: 'bg-yellow', private: 'bg-purple' };
                            var labelMap = { published: 'Veröffentlicht', draft: 'Entwurf', private: 'Privat' };
                            return gridjs.html('<span class=\"badge ' + (map[cell] || 'bg-secondary') + '\">' + window.cmsEsc(labelMap[cell] || cell || '') + '</span>');
                        }
                    },
                    { id: 'author_name', name: 'Autor' },
                    { id: 'updated_at', name: 'Aktualisiert' },
                    {
                        id: 'id',
                        name: '',
                        sort: false,
                        formatter: function(cell){
                            return gridjs.html('<a href=\"' + %s + '/admin/pages?action=edit&id=' + encodeURIComponent(cell) + '\" class=\"btn btn-sm btn-outline-primary\">Bearbeiten</a>');
                        }
                    }
                ]
            });
        })();",
        json_encode(SITE_URL . '/api/v1/admin/pages'),
        json_encode((string)($listData['filter'] ?? '')),
        json_encode((string)($listData['search'] ?? '')),
        json_encode(SITE_URL),
        json_encode(SITE_URL)
    );
    require_once __DIR__ . '/views/pages/list.php';
}

require_once __DIR__ . '/partials/footer.php';
