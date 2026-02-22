<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Security;
use WP_Error;

/**
 * User Service - Business Logic für Benutzerverwaltung
 * 
 * Alle Business-Logik für User-Operationen.
 * Wird von AJAX-Endpoints UND Admin-Views verwendet.
 * 
 * @package CMS\Services
 */
class UserService {
    
    private Database $db;
    private string $prefix;
    
    public function __construct() {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }
    
    /**
     * Singleton Instance
     */
    private static ?UserService $instance = null;
    
    public static function getInstance(): UserService {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Benutzer erstellen
     * 
     * @param array $data User-Daten [username, email, password, role, status]
     * @return int|WP_Error User-ID oder Fehler
     */
    public function createUser(array $data): int|WP_Error {
        // Validierung
        $validation = $this->validateUserData($data, 'create');
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Prüfe ob Username bereits existiert
        $existing = $this->db->get_row(
            "SELECT id FROM {$this->prefix}users WHERE username = ?",
            [$data['username']]
        );
        
        if ($existing) {
            return new WP_Error('username_exists', 'Benutzername bereits vergeben', ['status' => 409]);
        }
        
        // Prüfe ob E-Mail bereits existiert
        $existing_email = $this->db->get_row(
            "SELECT id FROM {$this->prefix}users WHERE email = ?",
            [$data['email']]
        );
        
        if ($existing_email) {
            return new WP_Error('email_exists', 'E-Mail-Adresse bereits vergeben', ['status' => 409]);
        }
        
        // Sanitize & Hash
        $clean_data = [
            'username' => Security::sanitize($data['username'], 'username'),
            'email' => Security::sanitize($data['email'], 'email'),
            'password' => Security::hashPassword($data['password']),
            'role' => in_array($data['role'] ?? 'member', ['admin', 'editor', 'author', 'member']) 
                      ? $data['role'] 
                      : 'member',
            'status' => in_array($data['status'] ?? 'active', ['active', 'inactive', 'banned']) 
                       ? $data['status'] 
                       : 'active'
        ];
        
        // Insert
        $result = $this->db->insert('users', $clean_data);
        
        if (!$result) {
            return new WP_Error('db_error', 'Datenbankfehler beim Erstellen', ['status' => 500]);
        }
        
        $user_id = $this->db->insert_id();
        
        // Meta-Daten speichern (Vorname, Nachname, etc.)
        if (!empty($data['first_name'])) {
            $this->updateUserMeta($user_id, 'first_name', Security::sanitize($data['first_name'], 'text'));
        }
        if (!empty($data['last_name'])) {
            $this->updateUserMeta($user_id, 'last_name', Security::sanitize($data['last_name'], 'text'));
        }
        
        // Log-Eintrag
        $this->logAction('user_created', $user_id, [
            'username' => $clean_data['username'],
            'role' => $clean_data['role']
        ]);
        
        return $user_id;
    }
    
    /**
     * Benutzer aktualisieren
     * 
     * @param int $user_id User-ID
     * @param array $data Zu aktualisierende Daten
     * @return bool|WP_Error Success oder Fehler
     */
    public function updateUser(int $user_id, array $data): bool|WP_Error {
        // Prüfe ob User existiert
        $user = $this->getUserById($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Benutzer nicht gefunden', ['status' => 404]);
        }
        
        // Validierung
        $validation = $this->validateUserData($data, 'update');
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $update_data = [];
        
        // Username (nur wenn geändert)
        if (isset($data['username']) && $data['username'] !== $user->username) {
            // Prüfe Eindeutigkeit
            $existing = $this->db->get_row(
                "SELECT id FROM {$this->prefix}users WHERE username = ? AND id != ?",
                [$data['username'], $user_id]
            );
            if ($existing) {
                return new WP_Error('username_exists', 'Benutzername bereits vergeben', ['status' => 409]);
            }
            $update_data['username'] = Security::sanitize($data['username'], 'username');
        }
        
        // E-Mail (nur wenn geändert)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $existing = $this->db->get_row(
                "SELECT id FROM {$this->prefix}users WHERE email = ? AND id != ?",
                [$data['email'], $user_id]
            );
            if ($existing) {
                return new WP_Error('email_exists', 'E-Mail bereits vergeben', ['status' => 409]);
            }
            $update_data['email'] = Security::sanitize($data['email'], 'email');
        }
        
        // Passwort (nur wenn angegeben)
        if (!empty($data['password'])) {
            $update_data['password'] = Security::hashPassword($data['password']);
        }
        
        // Rolle
        if (isset($data['role'])) {
            $allowed_roles = ['admin', 'editor', 'author', 'member'];
            if (in_array($data['role'], $allowed_roles)) {
                $update_data['role'] = $data['role'];
            }
        }
        
        // Status
        if (isset($data['status'])) {
            $allowed_statuses = ['active', 'inactive', 'banned'];
            if (in_array($data['status'], $allowed_statuses)) {
                $update_data['status'] = $data['status'];
            }
        }
        
        // Update nur wenn Daten vorhanden
        if (!empty($update_data)) {
            $result = $this->db->update('users', $update_data, ['id' => $user_id]);
            if (!$result && $this->db->last_error) {
                return new WP_Error('db_error', 'Datenbankfehler beim Aktualisieren', ['status' => 500]);
            }
        }
        
        // Meta-Daten aktualisieren
        if (isset($data['first_name'])) {
            $this->updateUserMeta($user_id, 'first_name', Security::sanitize($data['first_name'], 'text'));
        }
        if (isset($data['last_name'])) {
            $this->updateUserMeta($user_id, 'last_name', Security::sanitize($data['last_name'], 'text'));
        }
        
        // Log
        $this->logAction('user_updated', $user_id, $update_data);
        
        return true;
    }
    
    /**
     * Benutzer löschen
     * 
     * @param int $user_id User-ID
     * @param bool $hard_delete Permanent löschen oder nur deaktivieren
     * @return bool|WP_Error Success oder Fehler
     */
    public function deleteUser(int $user_id, bool $hard_delete = false): bool|WP_Error {
        $user = $this->getUserById($user_id);
        if (!$user) {
            return new WP_Error('user_not_found', 'Benutzer nicht gefunden', ['status' => 404]);
        }
        
        // Verhindere Löschen des eigenen Accounts
        if ($user_id === (int)($_SESSION['user_id'] ?? 0)) {
            return new WP_Error('cannot_delete_self', 'Sie können Ihren eigenen Account nicht löschen', ['status' => 403]);
        }
        
        if ($hard_delete) {
            // Permanent löschen
            $result = $this->db->delete('users', ['id' => $user_id]);
            if (!$result) {
                return new WP_Error('db_error', 'Fehler beim Löschen', ['status' => 500]);
            }
            
            // Meta-Daten werden durch CASCADE gelöscht
            $this->logAction('user_deleted', $user_id, ['hard_delete' => true]);
        } else {
            // Soft-Delete (auf inaktiv setzen)
            $result = $this->db->update('users', ['status' => 'inactive'], ['id' => $user_id]);
            if (!$result) {
                return new WP_Error('db_error', 'Fehler beim Deaktivieren', ['status' => 500]);
            }
            
            $this->logAction('user_deactivated', $user_id);
        }
        
        return true;
    }
    
    /**
     * Benutzer abrufen
     * 
     * @param int $user_id User-ID
     * @return object|null User-Objekt oder null
     */
    public function getUserById(int $user_id): ?object {
        $user = $this->db->get_row(
            "SELECT * FROM {$this->prefix}users WHERE id = ?",
            [$user_id]
        );
        
        if ($user) {
            // Meta-Daten hinzufügen
            $user->meta = $this->getUserMeta($user_id);
        }
        
        return $user;
    }
    
    /**
     * Benutzer-Liste abrufen mit Filtern
     * 
     * @param array $args Filter-Argumente [role, status, search, limit, offset, orderby, order]
     * @return array ['users' => array, 'total' => int]
     */
    public function getUsers(array $args = []): array {
        $defaults = [
            'role' => '',
            'status' => '',
            'search' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        
        $args = array_merge($defaults, $args);
        
        // WHERE-Bedingungen aufbauen
        $where = [];
        $params = [];
        
        if (!empty($args['role'])) {
            $where[] = "role = ?";
            $params[] = $args['role'];
        }
        
        if (!empty($args['status'])) {
            $where[] = "status = ?";
            $params[] = $args['status'];
        }
        
        if (!empty($args['search'])) {
            $where[] = "(username LIKE ? OR email LIKE ?)";
            $search_term = '%' . $args['search'] . '%';
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Total Count
        $total = $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->prefix}users {$where_sql}",
            $params
        );
        
        // Allowed orderby columns
        $allowed_orderby = ['id', 'username', 'email', 'role', 'status', 'created_at'];
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Users abrufen
        $users = $this->db->get_results(
            "SELECT * FROM {$this->prefix}users {$where_sql} ORDER BY {$orderby} {$order} LIMIT ? OFFSET ?",
            array_merge($params, [(int)$args['limit'], (int)$args['offset']])
        );
        
        // Meta-Daten für jeden User laden
        foreach ($users as $user) {
            $user->meta = $this->getUserMeta($user->id);
        }
        
        return [
            'users' => $users,
            'total' => (int)$total
        ];
    }
    
    /**
     * Bulk-Aktionen ausführen
     * 
     * @param string $action Aktion (activate, deactivate, delete, change_role)
     * @param array $user_ids User-IDs
     * @param array $data Zusätzliche Daten (z.B. neue Rolle)
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkAction(string $action, array $user_ids, array $data = []): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($user_ids as $user_id) {
            $user_id = (int)$user_id;
            
            // Verhindere Aktion auf eigenem Account
            if ($user_id === (int)($_SESSION['user_id'] ?? 0)) {
                $results['failed']++;
                $results['errors'][] = "User ID {$user_id}: Aktion auf eigenem Account nicht erlaubt";
                continue;
            }
            
            $result = match($action) {
                'activate' => $this->db->update('users', ['status' => 'active'], ['id' => $user_id]),
                'deactivate' => $this->db->update('users', ['status' => 'inactive'], ['id' => $user_id]),
                'delete' => $this->deleteUser($user_id, false),
                'hard_delete' => $this->deleteUser($user_id, true),
                'change_role' => !empty($data['role']) 
                    ? $this->db->update('users', ['role' => $data['role']], ['id' => $user_id])
                    : false,
                default => false
            };
            
            if ($result && !is_wp_error($result)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $error_msg = is_wp_error($result) ? $result->get_error_message() : 'Unbekannter Fehler';
                $results['errors'][] = "User ID {$user_id}: {$error_msg}";
            }
        }
        
        // Log
        $this->logAction('bulk_action', 0, [
            'action' => $action,
            'total' => count($user_ids),
            'success' => $results['success'],
            'failed' => $results['failed']
        ]);
        
        return $results;
    }
    
    /**
     * User-Statistiken abrufen
     * 
     * @return array Statistiken
     */
    public function getStatistics(): array {
        return [
            'total_users' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users"),
            'active_users' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'active'"),
            'inactive_users' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'inactive'"),
            'banned_users' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE status = 'banned'"),
            'admins' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'admin'"),
            'editors' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'editor'"),
            'authors' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'author'"),
            'members' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}users WHERE role = 'member'"),
            'new_last_30_days' => (int)$this->db->get_var(
                "SELECT COUNT(*) FROM {$this->prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )
        ];
    }
    
    /**
     * User-Daten validieren
     * 
     * @param array $data Zu validierende Daten
     * @param string $context 'create' oder 'update'
     * @return bool|WP_Error true bei Erfolg, WP_Error bei Fehler
     */
    private function validateUserData(array $data, string $context = 'create'): bool|WP_Error {
        if ($context === 'create') {
            // Username erforderlich
            if (empty($data['username'])) {
                return new WP_Error('missing_username', 'Benutzername ist erforderlich', ['status' => 400]);
            }
            
            if (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
                return new WP_Error('invalid_username', 'Benutzername muss 3-50 Zeichen lang sein', ['status' => 400]);
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                return new WP_Error('invalid_username', 'Benutzername darf nur Buchstaben, Zahlen und Unterstrich enthalten', ['status' => 400]);
            }
            
            // E-Mail erforderlich
            if (empty($data['email'])) {
                return new WP_Error('missing_email', 'E-Mail ist erforderlich', ['status' => 400]);
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new WP_Error('invalid_email', 'Ungültige E-Mail-Adresse', ['status' => 400]);
            }
            
            // Passwort erforderlich
            if (empty($data['password'])) {
                return new WP_Error('missing_password', 'Passwort ist erforderlich', ['status' => 400]);
            }
            
            if (strlen($data['password']) < 8) {
                return new WP_Error('weak_password', 'Passwort muss mindestens 8 Zeichen lang sein', ['status' => 400]);
            }
        }
        
        // Update-spezifische Validierung
        if ($context === 'update') {
            if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new WP_Error('invalid_email', 'Ungültige E-Mail-Adresse', ['status' => 400]);
            }
            
            if (isset($data['password']) && strlen($data['password']) < 8) {
                return new WP_Error('weak_password', 'Passwort muss mindestens 8 Zeichen lang sein', ['status' => 400]);
            }
        }
        
        return true;
    }
    
    /**
     * User-Meta abrufen
     * 
     * @param int $user_id User-ID
     * @param string|null $key Meta-Key (null = alle)
     * @return mixed
     */
    private function getUserMeta(int $user_id, ?string $key = null): mixed {
        if ($key) {
            return $this->db->get_var(
                "SELECT meta_value FROM cms_user_meta WHERE user_id = ? AND meta_key = ?",
                [$user_id, $key]
            );
        }
        
        $meta_rows = $this->db->get_results(
            "SELECT meta_key, meta_value FROM cms_user_meta WHERE user_id = ?",
            [$user_id]
        );
        
        $meta = [];
        foreach ($meta_rows as $row) {
            $meta[$row->meta_key] = $row->meta_value;
        }
        
        return $meta;
    }
    
    /**
     * User-Meta aktualisieren
     * 
     * @param int $user_id User-ID
     * @param string $key Meta-Key
     * @param mixed $value Meta-Value
     * @return bool
     */
    private function updateUserMeta(int $user_id, string $key, mixed $value): bool {
        // Prüfe ob bereits existiert
        $existing = $this->db->get_var(
            "SELECT meta_value FROM cms_user_meta WHERE user_id = ? AND meta_key = ?",
            [$user_id, $key]
        );
        
        if ($existing !== null) {
            // Update
            return $this->db->update(
                'cms_user_meta',
                ['meta_value' => $value],
                ['user_id' => $user_id, 'meta_key' => $key]
            );
        } else {
            // Insert
            return $this->db->insert('cms_user_meta', [
                'user_id' => $user_id,
                'meta_key' => $key,
                'meta_value' => $value
            ]);
        }
    }
    
    /**
     * Aktion loggen
     * 
     * @param string $action Aktionsname
     * @param int $user_id Betroffene User-ID
     * @param array $details Zusätzliche Details
     * @return bool
     */
    private function logAction(string $action, int $user_id, array $details = []): bool {
        return $this->db->insert('cms_activity_log', [
            'user_id' => $_SESSION['user_id'] ?? 0,
            'action' => $action,
            'object_type' => 'user',
            'object_id' => $user_id,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    /**
     * Alle Benutzer abrufen (ohne Paginierung)
     * 
     * @return array Array mit User-Objekten
     */
    public function getAllUsers(): array {
        $result = $this->getUsers(['limit' => 9999]);
        $users = $result['users'] ?? [];
        
        // Convert stdClass objects to arrays recursively
        return json_decode(json_encode($users), true);
    }
    
    /**
     * Verfügbare Benutzer-Rollen
     * 
     * @return array [slug => label]
     */
    public function getAvailableRoles(): array {
        return [
            'admin' => 'Administrator',
            'editor' => 'Redakteur',
            'author' => 'Autor',
            'member' => 'Mitglied'
        ];
    }
    
    /**
     * Role-Beschreibungen
     * 
     * @return array [slug => description]
     */
    public function getRoleDescriptions(): array {
        return [
            'admin' => [
                'name' => 'Administrator',
                'icon' => '👑',
                'description' => 'Voller Zugriff auf alle Funktionen und Einstellungen',
                'capabilities' => $this->getRoleCapabilities('admin')
            ],
            'editor' => [
                'name' => 'Redakteur',
                'icon' => '✏️',
                'description' => 'Kann alle Inhalte erstellen, bearbeiten und veröffentlichen',
                'capabilities' => $this->getRoleCapabilities('editor')
            ],
            'author' => [
                'name' => 'Autor',
                'icon' => '📝',
                'description' => 'Kann eigene Beiträge erstellen und veröffentlichen',
                'capabilities' => $this->getRoleCapabilities('author')
            ],
            'member' => [
                'name' => 'Mitglied',
                'icon' => '👤',
                'description' => 'Kann sich einloggen und eigenes Profil bearbeiten',
                'capabilities' => $this->getRoleCapabilities('member')
            ]
        ];
    }
    
    /**
     * Rolle-Capabilities abrufen
     * 
     * @param string $role Rolle
     * @return array Capabilities
     */
    public function getRoleCapabilities(string $role): array {
        $capabilities = [
            'admin' => [
                'manage_users',
                'manage_settings',
                'manage_pages',
                'edit_all_posts',
                'delete_all_posts',
                'manage_media',
                'view_analytics'
            ],
            'editor' => [
                'manage_pages',
                'edit_all_posts',
                'delete_all_posts',
                'manage_media'
            ],
            'author' => [
                'edit_own_posts',
                'delete_own_posts',
                'upload_files'
            ],
            'member' => [
                'read',
                'edit_profile'
            ]
        ];
        
        return $capabilities[$role] ?? [];
    }
    
    /**
     * Verfügbare User-Status
     * 
     * @return array [slug => label]
     */
    public function getAvailableStatuses(): array {
        return [
            'active' => 'Aktiv',
            'inactive' => 'Inaktiv',
            'banned' => 'Gesperrt'
        ];
    }
}
