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
            $ids        = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
            $result     = $module->bulkAction($bulkAction, $ids, $_POST);
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

    $pageAssets['css'] = $pageAssets['css'] ?? [];
    $pageAssets['js'] = $pageAssets['js'] ?? [];
    $pageAssets['js'][] = cms_asset_url('js/admin-seo-editor.js');
    $pageAssets['js'][] = cms_asset_url('js/admin-content-editor.js');

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/posts/edit.php';

    $inlineJs = '';

    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Beiträge';
    $activePage = 'posts';
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
            cmsGrid('#postsGrid', {
                url: %s,
                search: false,
                limit: 20,
                extraParams: {
                    status: %s,
                    category: %s,
                    search: %s
                },
                sortMap: {1:'title',3:'status',5:'created_at'},
                columns: [
                    {
                        id: 'id',
                        name: gridjs.html('<input class=\"form-check-input bulk-select-all\" type=\"checkbox\" aria-label=\"Alle Beiträge auswählen\">'),
                        sort: false,
                        formatter: function(cell){
                            return gridjs.html('<input class=\"form-check-input bulk-row-check\" type=\"checkbox\" value=\"' + encodeURIComponent(cell) + '\" aria-label=\"Beitrag auswählen\">');
                        }
                    },
                    {
                        id: 'title',
                        name: 'Titel',
                        data: function(row){
                            return {
                                id: row.id,
                                title: row.title,
                                title_en: row.title_en,
                                display_title: row.display_title,
                                slug: row.slug,
                                slug_en: row.slug_en,
                                display_slug: row.display_slug,
                                is_english_only: !!row.is_english_only,
                                status: row.status,
                                effective_status: row.effective_status,
                                published_at: row.published_at
                            };
                        },
                        formatter: function(cell){
                            var title = cell.display_title || cell.title || cell.title_en || 'Ohne Titel';
                            var baseSlug = cell.display_slug || cell.slug || cell.slug_en || '';
                            var slugLine = baseSlug !== '' ? '/blog/' + window.cmsEsc(baseSlug) : '—';
                            var localeBadge = cell.is_english_only
                                ? '<span class=\"badge bg-blue-lt ms-2\">EN only</span>'
                                : (cell.title_en ? '<span class=\"badge bg-secondary-lt ms-2\">EN</span>' : '');
                            var englishSlugLine = cell.slug_en && cell.slug_en !== cell.slug
                                ? '<div class=\"text-secondary small\">/en/blog/' + window.cmsEsc(cell.slug_en) + '</div>'
                                : '';
                            return gridjs.html(
                                '<div>' +
                                    '<a href=\"' + %s + '/admin/posts?action=edit&id=' + encodeURIComponent(cell.id) + '\" class=\"text-reset\">' + window.cmsEsc(title) + '</a>' + localeBadge +
                                    '<div class=\"text-secondary small\">' + slugLine + '</div>' +
                                    englishSlugLine +
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
                        formatter: function(cell, row){
                            var effective = (row && row.cells && row.cells[1] && row.cells[1].data && row.cells[1].data.effective_status) || cell;
                            var cls = 'warning';
                            var label = 'Entwurf';

                            if (effective === 'scheduled') {
                                cls = 'azure';
                                label = 'Geplant';
                            } else if (effective === 'published') {
                                cls = 'success';
                                label = 'Veröffentlicht';
                            }

                            return gridjs.html('<span class=\"badge bg-' + cls + '-lt\">' + label + '</span>');
                        }
                    },
                    { id: 'author_name', name: 'Autor' },
                    { id: 'created_at', name: 'Erstellt am' },
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
