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
use CMS\Logger;
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
            'username'   => $this->normalizeScalarText($post['username'] ?? '', 50),
            'email'      => $this->normalizeScalarText($post['email'] ?? '', 190),
            'role'       => $this->normalizeScalarText($post['role'] ?? 'member', 50),
            'status'     => $this->normalizeScalarText($post['status'] ?? 'active', 20),
            'first_name' => $this->normalizeScalarText($post['first_name'] ?? '', 120),
            'last_name'  => $this->normalizeScalarText($post['last_name'] ?? '', 120),
        ];

        $password = (string) ($post['password'] ?? '');
        if ($password !== '') {
            $data['password'] = $password;
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
                if ($result !== true) {
                    return [
                        'success' => false,
                        'error' => 'Benutzer konnte nicht aktualisiert werden.',
                        'error_details' => [
                            'Die Benutzerverwaltung hat keine erfolgreiche Aktualisierung bestätigt.',
                            'Bitte Eingaben und Datenbank-Logs prüfen.',
                        ],
                        'report_payload' => [
                            'title' => 'Benutzer-Update ohne Erfolgsmeldung',
                            'message' => 'Die Aktualisierung lieferte kein `true`-Ergebnis zurück.',
                            'error_code' => 'users_update_unconfirmed',
                            'source_url' => $this->buildUserEditSourceUrl($id),
                            'context' => [
                                'module' => 'users',
                                'operation' => 'update',
                                'user_id' => $id,
                            ],
                        ],
                    ];
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
                if (!is_int($result) || $result <= 0) {
                    return [
                        'success' => false,
                        'error' => 'Benutzer konnte nicht erstellt werden.',
                        'error_details' => [
                            'Die Benutzerverwaltung hat keine gültige Benutzer-ID zurückgegeben.',
                            'Bitte Eingaben und Datenbank-Logs prüfen.',
                        ],
                        'report_payload' => [
                            'title' => 'Benutzer-Erstellung ohne ID',
                            'message' => 'Die Benutzer-Erstellung lieferte keine gültige Benutzer-ID zurück.',
                            'error_code' => 'users_create_missing_id',
                            'source_url' => $this->buildUserEditSourceUrl(),
                            'context' => [
                                'module' => 'users',
                                'operation' => 'create',
                                'username' => $data['username'],
                                'email' => $data['email'],
                            ],
                        ],
                    ];
                }
                return ['success' => true, 'id' => $result, 'message' => 'Benutzer erstellt.'];
            }
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.users')->error('Benutzer konnte nicht gespeichert werden.', [
                'exception' => $e->getMessage(),
                'user_id' => $id,
                'payload_keys' => array_keys($post),
            ]);

            return [
                'success' => false,
                'error' => 'Benutzer konnte nicht gespeichert werden.',
                'error_details' => [
                    'Die Benutzerverwaltung hat den Speichervorgang wegen eines internen Fehlers abgebrochen.',
                    'Technischer Hinweis: ' . $this->normalizeScalarText($e->getMessage(), 250),
                ],
                'report_payload' => [
                    'title' => 'Benutzer konnte nicht gespeichert werden',
                    'message' => $this->normalizeScalarText($e->getMessage(), 500),
                    'error_code' => 'users_save_exception',
                    'source_url' => $this->buildUserEditSourceUrl($id),
                    'context' => [
                        'module' => 'users',
                        'operation' => $id > 0 ? 'update' : 'create',
                        'user_id' => $id,
                        'payload_keys' => array_keys($post),
                    ],
                ],
            ];
        }
    }

    private function normalizeScalarText(mixed $value, int $maxLength): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', '', $normalized) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($normalized, 0, $maxLength)
            : substr($normalized, 0, $maxLength);
    }

    /**
     * Benutzer löschen
     */
    public function deleteUser(int $id): array
    {
        try {
            $result = $this->userService->deleteUser($id, true);
            if ($result instanceof \CMS\WP_Error) {
                return ErrorReportService::buildFailureResultFromWpError($result, [
                    'title' => 'Benutzer konnte nicht gelöscht werden',
                    'source' => '/admin/users',
                    'module' => 'users',
                    'operation' => 'delete',
                    'user_id' => $id,
                ]);
            }

            return ['success' => true, 'message' => 'Benutzer dauerhaft gelöscht.'];
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.users')->error('Benutzer konnte nicht gelöscht werden.', [
                'exception' => $e->getMessage(),
                'user_id' => $id,
            ]);

            return [
                'success' => false,
                'error' => 'Benutzer konnte nicht gelöscht werden.',
                'error_details' => [
                    'Die Benutzerverwaltung hat den Löschvorgang wegen eines internen Fehlers abgebrochen.',
                    'Technischer Hinweis: ' . $this->normalizeScalarText($e->getMessage(), 250),
                ],
                'report_payload' => [
                    'title' => 'Benutzer konnte nicht gelöscht werden',
                    'message' => $this->normalizeScalarText($e->getMessage(), 500),
                    'error_code' => 'users_delete_exception',
                    'source_url' => '/admin/users',
                    'context' => [
                        'module' => 'users',
                        'operation' => 'delete',
                        'user_id' => $id,
                    ],
                ],
            ];
        }
    }

    /**
        private function buildUserEditSourceUrl(int $id = 0): string
        {
            return '/admin/users?action=' . ($id > 0 ? 'edit&id=' . $id : 'edit');
        }
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
