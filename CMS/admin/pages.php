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
use CMS\Services\EditorJsService;
use CMS\Services\EditorService;

const CMS_ADMIN_PAGES_ALLOWED_ACTIONS = ['save', 'delete', 'bulk'];
const CMS_ADMIN_PAGES_ALLOWED_VIEWS = ['list', 'edit'];
const CMS_ADMIN_PAGES_ALLOWED_BULK_ACTIONS = ['delete', 'publish', 'draft', 'set_category', 'clear_category'];
const CMS_ADMIN_PAGES_WRITE_CAPABILITY = 'manage_pages';

function cms_admin_pages_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_PAGES_WRITE_CAPABILITY);
}

function cms_admin_pages_target_url(?int $id = null): string
{
    if ($id !== null && $id > 0) {
        return '/admin/pages?action=edit&id=' . $id;
    }

    return '/admin/pages';
}

function cms_admin_pages_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_PAGES_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_pages_normalize_view(mixed $view): string
{
    $normalizedView = trim((string) $view);

    return in_array($normalizedView, CMS_ADMIN_PAGES_ALLOWED_VIEWS, true) ? $normalizedView : 'list';
}

function cms_admin_pages_normalize_positive_id(mixed $id): int
{
    $normalizedId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_pages_normalize_bulk_action(mixed $bulkAction): string
{
    $normalizedBulkAction = trim((string) $bulkAction);

    return in_array($normalizedBulkAction, CMS_ADMIN_PAGES_ALLOWED_BULK_ACTIONS, true) ? $normalizedBulkAction : '';
}

/**
 * @return array<int,int>
 */
function cms_admin_pages_normalize_bulk_ids(mixed $ids, mixed $csvIds = ''): array
{
    $candidates = (array) $ids;
    if ($candidates === [] && trim((string) $csvIds) !== '') {
        $candidates = explode(',', (string) $csvIds);
    }

    $normalizedIds = [];
    foreach ($candidates as $id) {
        $normalizedId = cms_admin_pages_normalize_positive_id($id);
        if ($normalizedId > 0) {
            $normalizedIds[$normalizedId] = $normalizedId;
        }

        if (count($normalizedIds) >= 200) {
            break;
        }
    }

    return array_values($normalizedIds);
}

function cms_admin_pages_view_config(PagesModule $module, string $view): array
{
    $normalizedView = cms_admin_pages_normalize_view($view);
    $baseTemplateVars = [
        'editorMediaToken' => Security::instance()->generateToken('editorjs_media'),
        'useEditorJs' => false,
    ];

    if ($normalizedView === 'edit') {
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

        $id = cms_admin_pages_normalize_positive_id($_GET['id'] ?? 0);
        $editData = $module->getEditData($id);

        return [
            'section' => 'edit',
            'view_file' => __DIR__ . '/views/pages/edit.php',
            'page_title' => $editData['isNew'] ? 'Neue Seite' : 'Seite bearbeiten',
            'active_page' => 'pages',
            'page_assets' => $pageAssets,
            'template_vars' => $baseTemplateVars + [
                'useEditorJs' => $useEditorJs,
                'editData' => $editData,
            ],
            'data' => $editData,
        ];
    }

    $listData = $module->getListData();
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

    return [
        'section' => 'list',
        'view_file' => __DIR__ . '/views/pages/list.php',
        'page_title' => 'Seiten',
        'active_page' => 'pages',
        'page_assets' => [
            'css' => [
                cms_asset_url('gridjs/mermaid.min.css'),
            ],
            'js' => [
                cms_asset_url('gridjs/gridjs.umd.js'),
                cms_asset_url('js/gridjs-init.js'),
            ],
        ],
        'template_vars' => $baseTemplateVars + [
            'listData' => $listData,
            'inlineJs' => $inlineJs,
        ],
        'data' => $listData,
    ];
}

// ─── Auth-Check ────────────────────────────────────────────────────────────
if (!cms_admin_pages_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/pages/PagesModule.php';

$sectionPageConfig = [
    'route_path' => '/admin/pages',
    'view_file' => __DIR__ . '/views/pages/list.php',
    'page_title' => 'Seiten',
    'active_page' => 'pages',
    'csrf_action' => 'admin_pages',
    'module_file' => __DIR__ . '/modules/pages/PagesModule.php',
    'module_factory' => static fn (): PagesModule => new PagesModule(),
    'access_checker' => static fn (): bool => cms_admin_pages_can_access(),
    'request_context_resolver' => static function (PagesModule $module): array {
        $view = cms_admin_pages_normalize_view($_GET['action'] ?? 'list');

        return cms_admin_pages_view_config($module, $view);
    },
    'redirect_path_resolver' => static function (PagesModule $module, string $section, mixed $result): string {
        if (is_array($result) && isset($result['redirect_path']) && is_string($result['redirect_path'])) {
            return $result['redirect_path'];
        }

        if ($section === 'edit') {
            return cms_admin_pages_target_url(cms_admin_pages_normalize_positive_id($_GET['id'] ?? 0));
        }

        return cms_admin_pages_target_url();
    },
    'post_handler' => static function (PagesModule $module, string $section, array $post): array {
        $postAction = cms_admin_pages_normalize_action($post['action'] ?? '');

        if ($postAction === '') {
            return ['success' => false, 'error' => 'Unbekannte Seiten-Aktion.'];
        }

        switch ($postAction) {
            case 'save':
                $userId = Auth::instance()->getCurrentUser()->id ?? 0;
                $result = $module->save($post, (int) $userId);
                if (!empty($result['success'])) {
                    $result['redirect_path'] = cms_admin_pages_target_url(cms_admin_pages_normalize_positive_id($result['id'] ?? 0));
                    return $result;
                }

                return [
                    'success' => false,
                    'error' => (string) ($result['error'] ?? 'Seite konnte nicht gespeichert werden.'),
                    'details' => is_array($result['details'] ?? null) ? $result['details'] : [],
                    'render_inline' => true,
                    'runtime_context' => cms_admin_pages_view_config($module, 'edit'),
                ];

            case 'delete':
                $id = cms_admin_pages_normalize_positive_id($post['id'] ?? 0);
                if ($id < 1) {
                    return ['success' => false, 'error' => 'Ungültige Seiten-ID.'];
                }

                return $module->delete($id);

            case 'bulk':
                $bulkAction = cms_admin_pages_normalize_bulk_action($post['bulk_action'] ?? '');
                $bulkIds = cms_admin_pages_normalize_bulk_ids($post['ids'] ?? [], $post['bulk_ids'] ?? '');
                if ($bulkAction === '') {
                    return ['success' => false, 'error' => 'Unbekannte Bulk-Aktion für Seiten.'];
                }

                if ($bulkIds === []) {
                    return ['success' => false, 'error' => 'Bitte mindestens eine gültige Seite auswählen.'];
                }

                return $module->bulkAction($bulkAction, $bulkIds, $post);

            default:
                return ['success' => false, 'error' => 'Unbekannte Seiten-Aktion.'];
        }
    },
];

require __DIR__ . '/partials/section-page-shell.php';
