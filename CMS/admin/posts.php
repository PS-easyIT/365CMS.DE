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

const CMS_ADMIN_POSTS_ALLOWED_ACTIONS = ['save', 'delete', 'bulk', 'save_category', 'delete_category'];
const CMS_ADMIN_POSTS_ALLOWED_VIEWS = ['list', 'edit'];
const CMS_ADMIN_POSTS_ALLOWED_BULK_ACTIONS = [
    'delete',
    'publish',
    'draft',
    'set_category',
    'clear_category',
    'set_author_display_name',
    'clear_author_display_name',
];
const CMS_ADMIN_POSTS_WRITE_CAPABILITY = 'edit_all_posts';

function cms_admin_posts_can_access(): bool
{
    return Auth::instance()->isAdmin();
}

function cms_admin_posts_target_url(?int $id = null): string
{
    if ($id !== null && $id > 0) {
        return '/admin/posts?action=edit&id=' . $id;
    }

    return '/admin/posts';
}

function cms_admin_posts_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_POSTS_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_posts_normalize_view(mixed $view): string
{
    $normalizedView = trim((string) $view);

    return in_array($normalizedView, CMS_ADMIN_POSTS_ALLOWED_VIEWS, true) ? $normalizedView : 'list';
}

function cms_admin_posts_normalize_positive_id(mixed $id): int
{
    $normalizedId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_posts_normalize_bulk_action(mixed $bulkAction): string
{
    $normalizedBulkAction = trim((string) $bulkAction);

    return in_array($normalizedBulkAction, CMS_ADMIN_POSTS_ALLOWED_BULK_ACTIONS, true) ? $normalizedBulkAction : '';
}

/**
 * @return array<int,int>
 */
function cms_admin_posts_normalize_bulk_ids(mixed $ids): array
{
    $normalizedIds = [];

    foreach ((array) $ids as $id) {
        $normalizedId = cms_admin_posts_normalize_positive_id($id);
        if ($normalizedId > 0) {
            $normalizedIds[$normalizedId] = $normalizedId;
        }

        if (count($normalizedIds) >= 200) {
            break;
        }
    }

    return array_values($normalizedIds);
}

function cms_admin_posts_can_run_action(string $action): bool
{
    return $action !== '' && Auth::instance()->hasCapability(CMS_ADMIN_POSTS_WRITE_CAPABILITY);
}

function cms_admin_posts_view_config(PostsModule $module, string $view): array
{
    $normalizedView = cms_admin_posts_normalize_view($view);
    $baseTemplateVars = [
        'editorMediaToken' => Security::instance()->generateToken('editorjs_media'),
        'useEditorJs' => false,
    ];

    if ($normalizedView === 'edit') {
        $id = cms_admin_posts_normalize_positive_id($_GET['id'] ?? 0);
        $editData = $module->getEditData($id);
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

        return [
            'section' => 'edit',
            'view_file' => __DIR__ . '/views/posts/edit.php',
            'page_title' => !empty($editData['isNew']) ? 'Neuer Beitrag' : 'Beitrag bearbeiten',
            'active_page' => 'posts',
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
        json_encode((string) ($listData['filter'] ?? '')),
        json_encode((int) ($listData['catFilter'] ?? 0)),
        json_encode((string) ($listData['search'] ?? '')),
        json_encode(SITE_URL),
        json_encode(SITE_URL)
    );

    return [
        'section' => 'list',
        'view_file' => __DIR__ . '/views/posts/list.php',
        'page_title' => 'Beiträge',
        'active_page' => 'posts',
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

if (!cms_admin_posts_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/posts/PostsModule.php';

$sectionPageConfig = [
    'route_path' => '/admin/posts',
    'view_file' => __DIR__ . '/views/posts/list.php',
    'page_title' => 'Beiträge',
    'active_page' => 'posts',
    'csrf_action' => 'admin_posts',
    'module_file' => __DIR__ . '/modules/posts/PostsModule.php',
    'module_factory' => static fn (): PostsModule => new PostsModule(),
    'access_checker' => static fn (): bool => cms_admin_posts_can_access(),
    'request_context_resolver' => static function (PostsModule $module): array {
        $view = cms_admin_posts_normalize_view($_GET['action'] ?? 'list');

        return cms_admin_posts_view_config($module, $view);
    },
    'redirect_path_resolver' => static function (PostsModule $module, string $section, mixed $result): string {
        if (is_array($result) && isset($result['redirect_path']) && is_string($result['redirect_path'])) {
            return $result['redirect_path'];
        }

        if ($section === 'edit') {
            return cms_admin_posts_target_url(cms_admin_posts_normalize_positive_id($_GET['id'] ?? 0));
        }

        return cms_admin_posts_target_url();
    },
    'post_handler' => static function (PostsModule $module, string $section, array $post): array {
        $postAction = cms_admin_posts_normalize_action($post['action'] ?? '');

        if ($postAction === '') {
            return ['success' => false, 'error' => 'Unbekannte Beitrags-Aktion.'];
        }

        if (!cms_admin_posts_can_run_action($postAction)) {
            return ['success' => false, 'error' => 'Keine Berechtigung für diese Beitrags-Aktion.'];
        }

        switch ($postAction) {
            case 'save':
                $userId = Auth::instance()->getCurrentUser()->id ?? 0;
                $result = $module->save($post, (int) $userId);
                if (!empty($result['success'])) {
                    $result['redirect_path'] = cms_admin_posts_target_url(cms_admin_posts_normalize_positive_id($result['id'] ?? 0));
                    return $result;
                }

                return [
                    'success' => false,
                    'error' => (string) ($result['error'] ?? 'Beitrag konnte nicht gespeichert werden.'),
                    'details' => is_array($result['details'] ?? null) ? $result['details'] : [],
                    'render_inline' => true,
                    'runtime_context' => cms_admin_posts_view_config($module, 'edit'),
                ];

            case 'delete':
                $id = cms_admin_posts_normalize_positive_id($post['id'] ?? 0);
                if ($id < 1) {
                    return ['success' => false, 'error' => 'Ungültige Beitrags-ID.'];
                }

                return $module->delete($id);

            case 'bulk':
                $bulkAction = cms_admin_posts_normalize_bulk_action($post['bulk_action'] ?? '');
                $ids = cms_admin_posts_normalize_bulk_ids($post['ids'] ?? []);
                if ($bulkAction === '') {
                    return ['success' => false, 'error' => 'Unbekannte Bulk-Aktion für Beiträge.'];
                }

                if ($ids === []) {
                    return ['success' => false, 'error' => 'Bitte mindestens einen gültigen Beitrag auswählen.'];
                }

                return $module->bulkAction($bulkAction, $ids, $post);

            case 'save_category':
                return $module->saveCategory($post);

            case 'delete_category':
                $catId = cms_admin_posts_normalize_positive_id($post['cat_id'] ?? 0);
                $replacementCategoryId = cms_admin_posts_normalize_positive_id($post['replacement_category_id'] ?? 0);
                if ($catId < 1) {
                    return ['success' => false, 'error' => 'Ungültige Kategorie-ID.'];
                }

                return $module->deleteCategory($catId, $replacementCategoryId);

            default:
                return ['success' => false, 'error' => 'Unbekannte Beitrags-Aktion.'];
        }
    },
];

require __DIR__ . '/partials/section-page-shell.php';
