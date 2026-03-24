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
$allowedActions = ['save', 'delete', 'bulk'];
$allowedViews = ['list', 'edit'];

// ─── POST-Verarbeitung ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    $postAction = (string)($_POST['action'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_pages')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.'];
        header('Location: ' . SITE_URL . '/admin/pages');
        exit;
    }

    if (!in_array($postAction, $allowedActions, true)) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Unbekannte Seiten-Aktion.'];
        header('Location: ' . SITE_URL . '/admin/pages');
        exit;
    }

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
                    $alert = ['type' => 'danger', 'message' => $result['error'] ?? 'Seite konnte nicht gespeichert werden.'];
                }
                break;

            case 'delete':
                $id     = (int)($_POST['id'] ?? 0);
                $result = $module->delete($id);
                $_SESSION['admin_alert'] = [
                    'type'    => $result['success'] ? 'success' : 'danger',
                    'message' => $result['message'] ?? $result['error'] ?? 'Seite konnte nicht gelöscht werden.',
                ];
                header('Location: ' . SITE_URL . '/admin/pages');
                exit;

            case 'bulk':
                $bulkAction = (string)($_POST['bulk_action'] ?? '');
                $bulkIds    = isset($_POST['ids'])
                    ? array_values(array_filter(array_map('intval', (array)$_POST['ids'])))
                    : array_values(array_filter(array_map('intval', explode(',', (string)($_POST['bulk_ids'] ?? '')))));
                $result     = $module->bulkAction($bulkAction, $bulkIds, $_POST);
                $_SESSION['admin_alert'] = [
                    'type'    => $result['success'] ? 'success' : 'danger',
                    'message' => $result['message'] ?? $result['error'] ?? 'Bulk-Aktion für Seiten fehlgeschlagen.',
                ];
                header('Location: ' . SITE_URL . '/admin/pages');
                exit;
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
$action = (string)($_GET['action'] ?? 'list');
if (!in_array($action, $allowedViews, true)) {
    $action = 'list';
}

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

    $pageAssets['css'] = $pageAssets['css'] ?? [];
    $pageAssets['js'] = $pageAssets['js'] ?? [];
    $pageAssets['js'][] = cms_asset_url('js/admin-seo-editor.js');
    $pageAssets['js'][] = cms_asset_url('js/admin-content-editor.js');
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
} else {
    $listData = $module->getListData();
    $pageAssets = [
        'css' => [
            cms_asset_url('gridjs/mermaid.min.css'),
        ],
        'js' => [
            cms_asset_url('gridjs/gridjs.umd.js'),
            cms_asset_url('js/gridjs-init.js'),
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
                    category: %s,
                    search: %s
                },
                sortMap: {1:'title',2:'slug',4:'status',6:'created_at'},
                columns: [
                    {
                        id: 'id',
                        name: gridjs.html('<input class=\"form-check-input bulk-select-all\" type=\"checkbox\" aria-label=\"Alle Seiten auswählen\">'),
                        sort: false,
                        formatter: function(cell){
                            return gridjs.html('<input class=\"form-check-input bulk-row-check\" type=\"checkbox\" value=\"' + encodeURIComponent(cell) + '\" aria-label=\"Seite auswählen\">');
                        }
                    },
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
                            var map = { published: 'bg-green', draft: 'bg-yellow', private: 'bg-purple' };
                            var labelMap = { published: 'Veröffentlicht', draft: 'Entwurf', private: 'Privat' };
                            return gridjs.html('<span class=\"badge ' + (map[cell] || 'bg-secondary') + '\">' + window.cmsEsc(labelMap[cell] || cell || '') + '</span>');
                        }
                    },
                    { id: 'author_name', name: 'Autor' },
                    { id: 'created_at', name: 'Erstellt am' },
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
        json_encode((int)($listData['catFilter'] ?? 0)),
        json_encode((string)($listData['search'] ?? '')),
        json_encode(SITE_URL),
        json_encode(SITE_URL)
    );
    require_once __DIR__ . '/views/pages/list.php';
}

require_once __DIR__ . '/partials/footer.php';
