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
        return SITE_URL . '/admin/pages?action=edit&id=' . $id;
    }

    return SITE_URL . '/admin/pages';
}

function cms_admin_pages_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_pages_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
    ];
}

function cms_admin_pages_flash_result(array $result): void
{
    cms_admin_pages_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
        'details' => $result['details'] ?? [],
    ]);
}

function cms_admin_pages_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
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

// ─── Auth-Check ────────────────────────────────────────────────────────────
if (!cms_admin_pages_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

// ─── Module laden ──────────────────────────────────────────────────────────
require_once __DIR__ . '/modules/pages/PagesModule.php';
$module    = new PagesModule();
$alert     = null;

// ─── POST-Verarbeitung ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = (string)($_POST['csrf_token'] ?? '');
    $postAction = cms_admin_pages_normalize_action($_POST['action'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_pages')) {
        cms_admin_pages_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig. Bitte erneut versuchen.']);
        cms_admin_pages_redirect(cms_admin_pages_target_url());
    }

    if ($postAction === '') {
        cms_admin_pages_flash(['type' => 'danger', 'message' => 'Unbekannte Seiten-Aktion.']);
        cms_admin_pages_redirect(cms_admin_pages_target_url());
    }

    switch ($postAction) {
        case 'save':
            $userId = Auth::instance()->getCurrentUser()->id ?? 0;
            $result = $module->save($_POST, (int) $userId);
            if ($result['success']) {
                cms_admin_pages_flash_result($result);
                cms_admin_pages_redirect(cms_admin_pages_target_url(cms_admin_pages_normalize_positive_id($result['id'] ?? 0)));
            }

            $alert = [
                'type' => 'danger',
                'message' => (string) ($result['error'] ?? 'Seite konnte nicht gespeichert werden.'),
                'details' => is_array($result['details'] ?? null) ? $result['details'] : [],
            ];
            break;

        case 'delete':
            $id = cms_admin_pages_normalize_positive_id($_POST['id'] ?? 0);
            if ($id < 1) {
                cms_admin_pages_flash(['type' => 'danger', 'message' => 'Ungültige Seiten-ID.']);
                cms_admin_pages_redirect(cms_admin_pages_target_url());
            }

            $result = $module->delete($id);
            cms_admin_pages_flash_result($result);
            cms_admin_pages_redirect(cms_admin_pages_target_url());

        case 'bulk':
            $bulkAction = cms_admin_pages_normalize_bulk_action($_POST['bulk_action'] ?? '');
            $bulkIds = cms_admin_pages_normalize_bulk_ids($_POST['ids'] ?? [], $_POST['bulk_ids'] ?? '');
            if ($bulkAction === '') {
                cms_admin_pages_flash(['type' => 'danger', 'message' => 'Unbekannte Bulk-Aktion für Seiten.']);
                cms_admin_pages_redirect(cms_admin_pages_target_url());
            }

            if ($bulkIds === []) {
                cms_admin_pages_flash(['type' => 'danger', 'message' => 'Bitte mindestens eine gültige Seite auswählen.']);
                cms_admin_pages_redirect(cms_admin_pages_target_url());
            }

            $result = $module->bulkAction($bulkAction, $bulkIds, $_POST);
            cms_admin_pages_flash_result($result);
            cms_admin_pages_redirect(cms_admin_pages_target_url());
    }
}

// Session-Alert abholen
if ($alert === null) {
    $alert = cms_admin_pages_pull_alert();
}

$csrfToken = Security::instance()->generateToken('admin_pages');
$editorMediaToken = Security::instance()->generateToken('editorjs_media');

// ─── View bestimmen ────────────────────────────────────────────────────────
$action = cms_admin_pages_normalize_view($_GET['action'] ?? 'list');

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
    $id       = cms_admin_pages_normalize_positive_id($_GET['id'] ?? 0);
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
