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

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

require_once __DIR__ . '/modules/users/UsersModule.php';
$module    = new UsersModule();
$alert     = null;

// ─── POST-Handling ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    if (!Security::instance()->verifyToken($postToken, 'admin_users')) {
        $_SESSION['admin_alert'] = ['type' => 'danger', 'message' => 'Sicherheitstoken ungültig.'];
        header('Location: ' . SITE_URL . '/admin/users');
        exit;
    }

    switch ($action) {
        case 'save':
            $result = $module->save($_POST);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            if ($result['success'] && !empty($result['id'])) {
                header('Location: ' . SITE_URL . '/admin/users?action=edit&id=' . $result['id']);
            } else {
                header('Location: ' . SITE_URL . '/admin/users');
            }
            exit;

        case 'delete':
            $id     = (int)($_POST['id'] ?? 0);
            $result = $module->deleteUser($id);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? $result['error'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/users');
            exit;

        case 'bulk':
            $bulkAction = $_POST['bulk_action'] ?? '';
            $ids        = array_filter(array_map('intval', $_POST['ids'] ?? []));
            $result     = $module->bulkAction($bulkAction, $ids);
            $_SESSION['admin_alert'] = [
                'type'    => $result['success'] ? 'success' : 'danger',
                'message' => $result['message'] ?? '',
            ];
            header('Location: ' . SITE_URL . '/admin/users');
            exit;
    }
}

// ─── Session-Alert ───────────────────────────────────────
if (!empty($_SESSION['admin_alert'])) {
    $alert = $_SESSION['admin_alert'];
    unset($_SESSION['admin_alert']);
}

$csrfToken  = Security::instance()->generateToken('admin_users');
$viewAction = $_GET['action'] ?? 'list';

if ($viewAction === 'edit') {
    $id         = isset($_GET['id']) ? (int)$_GET['id'] : null;
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
