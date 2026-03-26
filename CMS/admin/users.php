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
        return '/admin/users?action=edit&id=' . $id;
    }

    return '/admin/users';
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

/**
 * @param array<string,mixed>|null $formData
 * @return array<string,mixed>
 */
function cms_admin_users_view_config(UsersModule $module, string $view, ?int $editId = null, ?array $formData = null): array
{
    if ($view === 'edit') {
        $resolvedId = $editId !== null && $editId > 0 ? $editId : null;
        $data = $module->getEditData($resolvedId);

        if (is_array($formData) && $formData !== []) {
            $user = $data['user'] ?? null;
            if (!is_object($user)) {
                $user = new stdClass();
            }

            $user->id = $resolvedId ?? 0;
            $user->username = trim((string) ($formData['username'] ?? ($user->username ?? '')));
            $user->email = trim((string) ($formData['email'] ?? ($user->email ?? '')));
            $user->role = (string) ($formData['role'] ?? ($user->role ?? 'member'));
            $user->status = (string) ($formData['status'] ?? ($user->status ?? 'active'));
            $user->meta = array_merge(
                is_array($user->meta ?? null) ? $user->meta : [],
                [
                    'first_name' => trim((string) ($formData['first_name'] ?? '')),
                    'last_name' => trim((string) ($formData['last_name'] ?? '')),
                ]
            );

            $data['user'] = $user;
            $data['isNew'] = $resolvedId === null;
        }

        return [
            'view_file' => __DIR__ . '/views/users/edit.php',
            'page_title' => !empty($data['isNew']) ? 'Neuer Benutzer' : 'Benutzer bearbeiten',
            'active_page' => 'users',
            'page_assets' => [],
            'data' => $data,
        ];
    }

    $data = $module->getListData();
    $siteUrlJson = json_encode(SITE_URL);
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
        json_encode((string) ($data['filter']['role'] ?? '')),
        json_encode((string) ($data['filter']['status'] ?? '')),
        json_encode((string) ($data['filter']['search'] ?? '')),
        $siteUrlJson,
        $siteUrlJson
    );

    return [
        'view_file' => __DIR__ . '/views/users/list.php',
        'page_title' => 'Benutzer',
        'active_page' => 'users',
        'page_assets' => [
            'css' => [
                cms_asset_url('gridjs/mermaid.min.css'),
            ],
            'js' => [
                cms_asset_url('gridjs/gridjs.umd.js'),
                cms_asset_url('js/gridjs-init.js'),
            ],
        ],
        'template_vars' => [
            'inlineJs' => $inlineJs,
        ],
        'data' => $data,
    ];
}

$sectionPageConfig = [
    'route_path' => '/admin/users',
    'view_file' => __DIR__ . '/views/users/list.php',
    'page_title' => 'Benutzer',
    'active_page' => 'users',
    'csrf_action' => 'admin_users',
    'module_file' => __DIR__ . '/modules/users/UsersModule.php',
    'module_factory' => static fn (): UsersModule => new UsersModule(),
    'access_checker' => static fn (): bool => cms_admin_users_can_access(),
    'request_context_resolver' => static function (UsersModule $module): array {
        $view = cms_admin_users_normalize_view($_GET['action'] ?? 'list');
        $id = cms_admin_users_normalize_positive_id($_GET['id'] ?? 0);

        return cms_admin_users_view_config($module, $view, $id > 0 ? $id : null);
    },
    'redirect_path_resolver' => static function (UsersModule $module, string $section, ?array $result = null): string {
        $redirectPath = trim((string) ($result['redirect_path'] ?? ''));
        if ($redirectPath !== '') {
            return $redirectPath;
        }

        $view = cms_admin_users_normalize_view($_GET['action'] ?? 'list');
        $id = cms_admin_users_normalize_positive_id($_GET['id'] ?? 0);
        if ($view === 'edit' && $id > 0) {
            return cms_admin_users_target_url($id);
        }

        return cms_admin_users_target_url();
    },
    'post_handler' => static function (UsersModule $module, string $section, array $post): array {
        $action = cms_admin_users_normalize_action($post['action'] ?? '');
        if ($action === '') {
            return ['success' => false, 'error' => 'Unbekannte Benutzer-Aktion.'];
        }

        if ($action === 'save') {
            $result = $module->save($post);
            $savedId = cms_admin_users_normalize_positive_id($result['id'] ?? ($post['id'] ?? 0));

            if (!empty($result['success']) && $savedId > 0) {
                $result['redirect_path'] = cms_admin_users_target_url($savedId);

                return $result;
            }

            $result['render_inline'] = true;
            $result['runtime_context'] = cms_admin_users_view_config(
                $module,
                'edit',
                $savedId > 0 ? $savedId : null,
                $post
            );

            return $result;
        }

        if ($action === 'delete') {
            $id = cms_admin_users_normalize_positive_id($post['id'] ?? 0);
            if ($id < 1) {
                return ['success' => false, 'error' => 'Ungültige Benutzer-ID.'];
            }

            return $module->deleteUser($id);
        }

        $bulkAction = cms_admin_users_normalize_bulk_action($post['bulk_action'] ?? '');
        $ids = cms_admin_users_normalize_bulk_ids($post['ids'] ?? []);

        if ($bulkAction === '') {
            return ['success' => false, 'error' => 'Unbekannte Bulk-Aktion für Benutzer.'];
        }

        if ($ids === []) {
            return ['success' => false, 'error' => 'Bitte mindestens einen gültigen Benutzer auswählen.'];
        }

        return $module->bulkAction($bulkAction, $ids);
    },
];

require __DIR__ . '/partials/section-page-shell.php';
