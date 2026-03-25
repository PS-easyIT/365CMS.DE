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
        return SITE_URL . '/admin/posts?action=edit&id=' . $id;
    }

    return SITE_URL . '/admin/posts';
}

function cms_admin_posts_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_posts_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
    ];
}

function cms_admin_posts_flash_result(array $result): void
{
    cms_admin_posts_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
        'details' => $result['details'] ?? [],
    ]);
}

function cms_admin_posts_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
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

if (!cms_admin_posts_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/posts/PostsModule.php';
$module    = new PostsModule();
$user      = Auth::instance()->getCurrentUser();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = cms_admin_posts_normalize_action($_POST['action'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_posts')) {
        cms_admin_posts_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.']);
        cms_admin_posts_redirect(cms_admin_posts_target_url());
    }

    if ($action === '') {
        cms_admin_posts_flash(['type' => 'danger', 'message' => 'Unbekannte Beitrags-Aktion.']);
        cms_admin_posts_redirect(cms_admin_posts_target_url());
    }

    if (!cms_admin_posts_can_run_action($action)) {
        cms_admin_posts_flash(['type' => 'danger', 'message' => 'Keine Berechtigung für diese Beitrags-Aktion.']);
        cms_admin_posts_redirect(cms_admin_posts_target_url());
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST, (int)($user->id ?? 0));
            cms_admin_posts_flash_result($result);
            if ($result['success']) {
                cms_admin_posts_redirect(cms_admin_posts_target_url(cms_admin_posts_normalize_positive_id($result['id'] ?? 0)));
            } else {
                cms_admin_posts_redirect(cms_admin_posts_target_url());
            }

        case 'delete':
            $id     = cms_admin_posts_normalize_positive_id($_POST['id'] ?? 0);
            if ($id < 1) {
                cms_admin_posts_flash(['type' => 'danger', 'message' => 'Ungültige Beitrags-ID.']);
                cms_admin_posts_redirect(cms_admin_posts_target_url());
            }

            $result = $module->delete($id);
            cms_admin_posts_flash_result($result);
            cms_admin_posts_redirect(cms_admin_posts_target_url());

        case 'bulk':
            $bulkAction = cms_admin_posts_normalize_bulk_action($_POST['bulk_action'] ?? '');
            $ids        = cms_admin_posts_normalize_bulk_ids($_POST['ids'] ?? []);

            if ($bulkAction === '') {
                cms_admin_posts_flash(['type' => 'danger', 'message' => 'Unbekannte Bulk-Aktion für Beiträge.']);
                cms_admin_posts_redirect(cms_admin_posts_target_url());
            }

            if ($ids === []) {
                cms_admin_posts_flash(['type' => 'danger', 'message' => 'Bitte mindestens einen gültigen Beitrag auswählen.']);
                cms_admin_posts_redirect(cms_admin_posts_target_url());
            }

            $result     = $module->bulkAction($bulkAction, $ids, $_POST);
            cms_admin_posts_flash_result($result);
            cms_admin_posts_redirect(cms_admin_posts_target_url());

        case 'save_category':
            $result = $module->saveCategory($_POST);
            cms_admin_posts_flash_result($result);
            cms_admin_posts_redirect(cms_admin_posts_target_url());

        case 'delete_category':
            $catId  = cms_admin_posts_normalize_positive_id($_POST['cat_id'] ?? 0);
            $replacementCategoryId = cms_admin_posts_normalize_positive_id($_POST['replacement_category_id'] ?? 0);

            if ($catId < 1) {
                cms_admin_posts_flash(['type' => 'danger', 'message' => 'Ungültige Kategorie-ID.']);
                cms_admin_posts_redirect(cms_admin_posts_target_url());
            }

            $result = $module->deleteCategory($catId, $replacementCategoryId);
            cms_admin_posts_flash_result($result);
            cms_admin_posts_redirect(cms_admin_posts_target_url());
    }
}

// ─── Session-Alert abholen ───────────────────────────────
$alert = cms_admin_posts_pull_alert();

// CSRF-Token erneuern nach POST-Redirect
$csrfToken = Security::instance()->generateToken('admin_posts');
$editorMediaToken = Security::instance()->generateToken('editorjs_media');

// ─── View-Routing ────────────────────────────────────────
$viewAction = cms_admin_posts_normalize_view($_GET['action'] ?? 'list');

if ($viewAction === 'edit') {
    $id        = cms_admin_posts_normalize_positive_id($_GET['id'] ?? 0);
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
