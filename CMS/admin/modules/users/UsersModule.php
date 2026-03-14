<?php
declare(strict_types=1);

/**
 * Users Module – Business-Logik für Benutzerverwaltung
 *
 * @package CMSv2\Admin\Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Database;
use CMS\Services\ErrorReportService;
use CMS\Services\UserService;

class UsersModule
{
    private Database $db;
    private UserService $userService;
    private string $prefix;

    public function __construct()
    {
        $this->db          = Database::instance();
        $this->prefix      = $this->db->getPrefix();
        $this->userService = UserService::getInstance();
    }

    /**
     * Daten für die Listenansicht
     */
    public function getListData(): array
    {
        $roleFilter   = $_GET['role'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $search       = trim($_GET['q'] ?? '');
        $page         = max(1, (int)($_GET['page'] ?? 1));
        $perPage      = 25;

        $result = $this->userService->getUsers([
            'role'    => $roleFilter,
            'status'  => $statusFilter,
            'search'  => $search,
            'limit'   => $perPage,
            'offset'  => ($page - 1) * $perPage,
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ]);

        $stats = $this->userService->getStatistics();

        return [
            'users'          => $result['users'],
            'total'          => $result['total'],
            'stats'          => $stats,
            'availableRoles' => $this->userService->getAvailableRoles(),
            'availableStatuses' => $this->userService->getAvailableStatuses(),
            'filter'         => ['role' => $roleFilter, 'status' => $statusFilter, 'search' => $search],
            'page'           => $page,
            'perPage'        => $perPage,
            'pages'          => (int)ceil($result['total'] / $perPage),
        ];
    }

    /**
     * Daten für die Edit-Ansicht
     */
    public function getEditData(?int $id): array
    {
        $user = null;
        if ($id !== null) {
            $user = $this->userService->getUserById($id);
        }

        return [
            'user'              => $user,
            'isNew'             => $user === null,
            'availableRoles'    => $this->userService->getAvailableRoles(),
            'availableStatuses' => $this->userService->getAvailableStatuses(),
        ];
    }

    /**
     * Benutzer speichern
     */
    public function save(array $post): array
    {
        $id = (int)($post['id'] ?? 0);

        $data = [
            'username'   => trim($post['username'] ?? ''),
            'email'      => trim($post['email'] ?? ''),
            'role'       => $post['role'] ?? 'member',
            'status'     => $post['status'] ?? 'active',
            'first_name' => trim($post['first_name'] ?? ''),
            'last_name'  => trim($post['last_name'] ?? ''),
        ];

        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }

        try {
            if ($id > 0) {
                $result = $this->userService->updateUser($id, $data);
                if ($result instanceof \CMS\WP_Error) {
                    return ErrorReportService::buildFailureResultFromWpError($result, [
                        'title' => 'Benutzer konnte nicht aktualisiert werden',
                        'source' => '/admin/users?action=edit&id=' . $id,
                        'module' => 'users',
                        'operation' => 'update',
                        'user_id' => $id,
                    ]);
                }
                return ['success' => true, 'id' => $id, 'message' => 'Benutzer aktualisiert.'];
            } else {
                if (empty($data['password'])) {
                    return ['success' => false, 'error' => 'Passwort ist Pflichtfeld bei neuen Benutzern.'];
                }
                $result = $this->userService->createUser($data);
                if ($result instanceof \CMS\WP_Error) {
                    return ErrorReportService::buildFailureResultFromWpError($result, [
                        'title' => 'Benutzer konnte nicht erstellt werden',
                        'source' => '/admin/users?action=edit',
                        'module' => 'users',
                        'operation' => 'create',
                    ]);
                }
                return ['success' => true, 'id' => $result, 'message' => 'Benutzer erstellt.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Fehler: ' . $e->getMessage()];
        }
    }

    /**
     * Benutzer löschen
     */
    public function deleteUser(int $id): array
    {
        $result = $this->userService->deleteUser($id, false);
        if ($result instanceof \CMS\WP_Error) {
            return ErrorReportService::buildFailureResultFromWpError($result, [
                'title' => 'Benutzer konnte nicht deaktiviert werden',
                'source' => '/admin/users',
                'module' => 'users',
                'operation' => 'delete',
                'user_id' => $id,
            ]);
        }
        return ['success' => true, 'message' => 'Benutzer deaktiviert.'];
    }

    /**
     * Bulk-Aktionen
     */
    public function bulkAction(string $action, array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Keine Benutzer ausgewählt.'];
        }
        $result = $this->userService->bulkAction($action, $ids);
        return [
            'success' => $result['success'] > 0,
            'message' => $result['success'] . ' Benutzer verarbeitet.' . ($result['failed'] > 0 ? ' ' . $result['failed'] . ' fehlgeschlagen.' : ''),
        ];
    }
}
