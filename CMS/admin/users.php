<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Benutzer – Entry Point
 * Route: /admin/users
 */

use CMS\Auth;
use CMS\Security;

const CMS_ADMIN_USERS_ALLOWED_ACTIONS = ['save', 'delete', 'bulk'];
const CMS_ADMIN_USERS_ALLOWED_VIEWS = ['list', 'edit'];
const CMS_ADMIN_USERS_ALLOWED_BULK_ACTIONS = ['activate', 'deactivate', 'delete', 'hard_delete'];
const CMS_ADMIN_USERS_WRITE_CAPABILITY = 'manage_users';

function cms_admin_users_can_access(): bool
{
    return Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_USERS_WRITE_CAPABILITY);
}

function cms_admin_users_target_url(?int $id = null): string
{
    if ($id !== null && $id > 0) {
        return SITE_URL . '/admin/users?action=edit&id=' . $id;
    }

    return SITE_URL . '/admin/users';
}

function cms_admin_users_redirect(string $redirectUrl): never
{
    header('Location: ' . $redirectUrl);
    exit;
}

function cms_admin_users_flash(array $payload): void
{
    $_SESSION['admin_alert'] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? '')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
    ];
}

function cms_admin_users_flash_result(array $result): void
{
    cms_admin_users_flash([
        'type' => !empty($result['success']) ? 'success' : 'danger',
        'message' => (string) ($result['message'] ?? $result['error'] ?? ''),
        'details' => $result['details'] ?? [],
    ]);
}

function cms_admin_users_pull_alert(): ?array
{
    $alert = $_SESSION['admin_alert'] ?? null;
    unset($_SESSION['admin_alert']);

    return is_array($alert) ? $alert : null;
}

function cms_admin_users_normalize_action(mixed $action): string
{
    $normalizedAction = trim((string) $action);

    return in_array($normalizedAction, CMS_ADMIN_USERS_ALLOWED_ACTIONS, true) ? $normalizedAction : '';
}

function cms_admin_users_normalize_view(mixed $view): string
{
    $normalizedView = trim((string) $view);

    return in_array($normalizedView, CMS_ADMIN_USERS_ALLOWED_VIEWS, true) ? $normalizedView : 'list';
}

function cms_admin_users_normalize_positive_id(mixed $id): int
{
    $normalizedId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $normalizedId === false ? 0 : (int) $normalizedId;
}

function cms_admin_users_normalize_bulk_action(mixed $bulkAction): string
{
    $normalizedBulkAction = trim((string) $bulkAction);

    return in_array($normalizedBulkAction, CMS_ADMIN_USERS_ALLOWED_BULK_ACTIONS, true) ? $normalizedBulkAction : '';
}

/**
 * @return array<int,int>
 */
function cms_admin_users_normalize_bulk_ids(mixed $ids): array
{
    $normalizedIds = [];

    foreach ((array) $ids as $id) {
        $normalizedId = cms_admin_users_normalize_positive_id($id);
        if ($normalizedId > 0) {
            $normalizedIds[$normalizedId] = $normalizedId;
        }

        if (count($normalizedIds) >= 200) {
            break;
        }
    }

    return array_values($normalizedIds);
}

if (!cms_admin_users_can_access()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/UsersModule.php';
$module    = new UsersModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = cms_admin_users_normalize_action($_POST['action'] ?? '');
    $postToken = (string) ($_POST['csrf_token'] ?? '');

    if (!Security::instance()->verifyToken($postToken, 'admin_users')) {
        cms_admin_users_flash(['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.']);
        cms_admin_users_redirect(cms_admin_users_target_url());
    }

    if ($action === '') {
        cms_admin_users_flash(['type' => 'danger', 'message' => 'Unbekannte Benutzer-Aktion.']);
        cms_admin_users_redirect(cms_admin_users_target_url());
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST);
            cms_admin_users_flash_result($result);
            if ($result['success'] && !empty($result['id'])) {
                cms_admin_users_redirect(cms_admin_users_target_url(cms_admin_users_normalize_positive_id($result['id'] ?? 0)));
            } else {
                cms_admin_users_redirect(cms_admin_users_target_url());
            }

        case 'delete':
            $id = cms_admin_users_normalize_positive_id($_POST['id'] ?? 0);
            if ($id < 1) {
                cms_admin_users_flash(['type' => 'danger', 'message' => 'Ungültige Benutzer-ID.']);
                cms_admin_users_redirect(cms_admin_users_target_url());
            }

            $result = $module->deleteUser($id);
            cms_admin_users_flash_result($result);
            cms_admin_users_redirect(cms_admin_users_target_url());

        case 'bulk':
            $bulkAction = cms_admin_users_normalize_bulk_action($_POST['bulk_action'] ?? '');
            $ids = cms_admin_users_normalize_bulk_ids($_POST['ids'] ?? []);

            if ($bulkAction === '') {
                cms_admin_users_flash(['type' => 'danger', 'message' => 'Unbekannte Bulk-Aktion für Benutzer.']);
                cms_admin_users_redirect(cms_admin_users_target_url());
            }

            if ($ids === []) {
                cms_admin_users_flash(['type' => 'danger', 'message' => 'Bitte mindestens einen gültigen Benutzer auswählen.']);
                cms_admin_users_redirect(cms_admin_users_target_url());
            }

            $result     = $module->bulkAction($bulkAction, $ids);
            cms_admin_users_flash_result($result);
            cms_admin_users_redirect(cms_admin_users_target_url());
    }
}

// ─── Session-Alert ───────────────────────────────────────
$alert = cms_admin_users_pull_alert();

$csrfToken  = Security::instance()->generateToken('admin_users');
$viewAction = cms_admin_users_normalize_view($_GET['action'] ?? 'list');

if ($viewAction === 'edit') {
    $id         = cms_admin_users_normalize_positive_id($_GET['id'] ?? 0);
    $data       = $module->getEditData($id);
    $pageTitle  = $data['isNew'] ? 'Neuer Benutzer' : 'Benutzer bearbeiten';
    $activePage = 'users';
    $pageAssets = [];
    $inlineJs = '';

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/users/edit.php';
    require __DIR__ . '/partials/footer.php';
} else {
    $data       = $module->getListData();
    $pageTitle  = 'Benutzer';
    $activePage = 'users';
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
            cmsGrid('#usersGrid', {
                url: %s,
                search: false,
                limit: 20,
                extraParams: {
                    role: %s,
                    status: %s,
                    search: %s
                },
                sortMap: {0:'username',1:'email',2:'role',3:'status',4:'created_at'},
                columns: [
                    {
                        id: 'username',
                        name: 'Benutzer',
                        data: function(row){ return { id: row.id, username: row.username, display_name: row.display_name, role: row.role }; },
                        formatter: function(cell){
                            var initials = (cell.username || '').substring(0, 2).toUpperCase();
                            return gridjs.html(
                                '<div class=\"d-flex align-items-center\">' +
                                    '<span class=\"avatar avatar-sm me-2 bg-azure\">' + window.cmsEsc(initials) + '</span>' +
                                    '<div>' +
                                        '<a href=\"' + %s + '/admin/users?action=edit&id=' + encodeURIComponent(cell.id) + '\" class=\"text-reset\">' + window.cmsEsc(cell.username || '') + '</a>' +
                                        (cell.display_name ? '<div class=\"text-secondary small\">' + window.cmsEsc(cell.display_name) + '</div>' : '') +
                                    '</div>' +
                                '</div>'
                            );
                        }
                    },
                    { id: 'email', name: 'E-Mail' },
                    {
                        id: 'role',
                        name: 'Rolle',
                        formatter: function(cell){
                            return gridjs.html('<span class=\"badge bg-azure-lt\">' + window.cmsEsc(cell || '') + '</span>');
                        }
                    },
                    {
                        id: 'status',
                        name: 'Status',
                        formatter: function(cell){
                            var map = { active: 'green', inactive: 'yellow', banned: 'red' };
                            var labelMap = { active: 'Aktiv', inactive: 'Inaktiv', banned: 'Gesperrt' };
                            var cls = map[cell] || 'secondary';
                            var label = labelMap[cell] || cell || '';
                            return gridjs.html('<span class=\"badge bg-' + cls + '-lt\">' + window.cmsEsc(label) + '</span>');
                        }
                    },
                    {
                        id: 'created_at',
                        name: 'Registriert',
                        formatter: function(cell){
                            return cell ? window.cmsEsc(String(cell).substring(0, 10).split('-').reverse().join('.')) : '–';
                        }
                    },
                    {
                        id: 'id',
                        name: '',
                        sort: false,
                        formatter: function(cell){
                            return gridjs.html('<a href=\"' + %s + '/admin/users?action=edit&id=' + encodeURIComponent(cell) + '\" class=\"btn btn-ghost-primary btn-icon btn-sm\" title=\"Bearbeiten\">✎</a>');
                        }
                    }
                ]
            });
        })();",
        json_encode(SITE_URL . '/api/v1/admin/users'),
        json_encode((string)($data['filter']['role'] ?? '')),
        json_encode((string)($data['filter']['status'] ?? '')),
        json_encode((string)($data['filter']['search'] ?? '')),
        json_encode(SITE_URL),
        json_encode(SITE_URL)
    );

    require __DIR__ . '/partials/header.php';
    require __DIR__ . '/partials/sidebar.php';
    require __DIR__ . '/views/users/list.php';
    require __DIR__ . '/partials/footer.php';
}
