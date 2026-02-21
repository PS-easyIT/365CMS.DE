<?php
/**
 * Admin - Users Management (Complete CRUD Edition)
 * 
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config.php';
}

use CMS\Security;
use CMS\Database;

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/admin/login');
    exit;
}

$security = Security::instance();
$current_user_id = $_SESSION['user_id'];

// Get current user
$current_user = Database::fetchOne("SELECT * FROM " . Database::instance()->prefix() . "users WHERE id = ?", [$current_user_id]);
if (!$current_user) {
    session_destroy();
    header('Location: ' . SITE_URL . '/admin/login');
    exit;
}

$db = Database::instance();
$prefix = $db->prefix();

// === HANDLE ACTIONS ===

// Create User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'create_user')) {
        $_SESSION['error'] = 'Ungültiges Token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $display_name = trim($_POST['display_name'] ?? '');
        $role = $_POST['role'] ?? 'member';
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'Benutzername, E-Mail und Passwort sind erforderlich.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Ungültige E-Mail-Adresse.';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Passwort muss mindestens 6 Zeichen lang sein.';
        } elseif (!in_array($role, ['admin', 'member'])) {
            $_SESSION['error'] = 'Ungültige Rolle.';
        } else {
            // Check if username or email already exists
            $check = Database::fetchOne("SELECT id FROM {$prefix}users WHERE username = ? OR email = ?", [$username, $email]);
            if ($check) {
                $_SESSION['error'] = 'Benutzername oder E-Mail bereits vergeben.';
            } else {
                try {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    Database::exec(
                        "INSERT INTO {$prefix}users (username, email, password, display_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                        [$username, $email, $hashed, $display_name ?: $username, $role]
                    );
                    $_SESSION['success'] = '✅ Benutzer wurde erfolgreich erstellt.';
                } catch (Exception $e) {
                    $_SESSION['error'] = 'Fehler beim Erstellen: ' . $e->getMessage();
                }
            }
        }
    }
    header('Location: ' . SITE_URL . '/admin/users');
    exit;
}

// Delete User
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    if (!$security->verifyToken($_GET['token'] ?? '', 'delete_user_' . $_GET['delete'])) {
        $_SESSION['error'] = 'Ungültiges Sicherheits-Token.';
    } elseif ($_GET['delete'] == $current_user_id) {
        $_SESSION['error'] = 'Sie können sich nicht selbst löschen.';
    } else {
        try {
            Database::exec("DELETE FROM {$prefix}users WHERE id = ?", [$_GET['delete']]);
            $_SESSION['success'] = '✅ Benutzer wurde gelöscht.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Fehler beim Löschen: ' . $e->getMessage();
        }
    }
    header('Location: ' . SITE_URL . '/admin/users');
    exit;
}

// Change Role
if (isset($_POST['change_role']) && isset($_POST['user_id'])) {
    if (!$security->verifyToken($_POST['csrf_token'] ?? '', 'change_role')) {
        $_SESSION['error'] = 'Ungültiges Token.';
    } else {
        $target_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        if ($target_id === $current_user_id) {
            $_SESSION['error'] = 'Sie können Ihre eigene Rolle nicht ändern.';
        } elseif (!in_array($new_role, ['admin', 'member'])) {
            $_SESSION['error'] = 'Ungültige Rolle.';
        } else {
            try {
                Database::exec("UPDATE {$prefix}users SET role = ? WHERE id = ?", [$new_role, $target_id]);
                $_SESSION['success'] = '✅ Rolle wurde geändert.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Fehler: ' . $e->getMessage();
            }
        }
    }
    header('Location: ' . SITE_URL . '/admin/users');
    exit;
}

// Get all users with stats
$users = Database::fetchAll("SELECT * FROM {$prefix}users ORDER BY created_at DESC");

// Calculate stats
$total_users = count($users);
$admin_count = 0;
$member_count = 0;
$new_this_week = 0;

$week_ago = strtotime('-7 days');
foreach ($users as $u) {
    if ($u['role'] === 'admin') $admin_count++;
    if ($u['role'] === 'member') $member_count++;
    if (strtotime($u['created_at']) > $week_ago) $new_this_week++;
}

$page_title = 'Benutzer';
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Benutzer', 'users');
?>

<style>
.users-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.users-stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1.25rem;
}
.users-stat-card h3 {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
}
.users-stat-card p {
    margin: 0.25rem 0 0;
    font-size: 0.9rem;
    color: #64748b;
}
.user-row {
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.2s;
}
.user-row:hover {
    background: #f8fafc;
}
.role-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
.role-admin { background: #fef3c7; color: #92400e; }
.role-member { background: #dbeafe; color: #1e40af; }
</style>

<!-- Header Card -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; font-size: 1.75rem;">
                <i class="fa-solid fa-users"></i> Benutzerverwaltung
            </h2>
            <p style="margin: 0.5rem 0 0; color: #64748b;">Verwalten Sie alle registrierten Benutzer</p>
        </div>
        <a href="#create-user" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Neuer Benutzer
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="users-stats">
    <div class="users-stat-card">
        <h3 style="color: #3b82f6;"><?php echo $total_users; ?></h3>
        <p>Gesamt</p>
    </div>
    <div class="users-stat-card">
        <h3 style="color: #f59e0b;"><?php echo $admin_count; ?></h3>
        <p>Administratoren</p>
    </div>
    <div class="users-stat-card">
        <h3 style="color: #22c55e;"><?php echo $member_count; ?></h3>
        <p>Mitglieder</p>
    </div>
    <div class="users-stat-card">
        <h3 style="color: #8b5cf6;"><?php echo $new_this_week; ?></h3>
        <p>Neue (7 Tage)</p>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                <th style="padding: 1rem;">Benutzer</th>
                <th style="padding: 1rem;">E-Mail</th>
                <th style="padding: 1rem;">Rolle</th>
                <th style="padding: 1rem;">Registriert</th>
                <th style="padding: 1rem; text-align: right;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr class="user-row">
                <td style="padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #1e293b;">
                                <?php echo htmlspecialchars($u['username']); ?>
                                <?php if ($u['id'] == $current_user_id): ?>
                                    <span style="background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">Sie</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($u['display_name']) && $u['display_name'] !== $u['username']): ?>
                                <div style="font-size: 0.85rem; color: #64748b;"><?php echo htmlspecialchars($u['display_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="padding: 1rem; color: #64748b;">
                    <?php echo htmlspecialchars($u['email']); ?>
                </td>
                <td style="padding: 1rem;">
                    <?php if ($u['id'] == $current_user_id): ?>
                        <span class="role-badge role-<?php echo $u['role']; ?>">
                            <?php echo htmlspecialchars($u['role']); ?>
                        </span>
                    <?php else: ?>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('change_role'); ?>">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <select name="new_role" onchange="if(confirm('Rolle wirklich ändern?')) this.form.submit();" 
                                    style="padding: 3px 8px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.75rem; font-weight: 600; background: <?php echo $u['role'] === 'admin' ? '#fef3c7' : '#dbeafe'; ?>; color: <?php echo $u['role'] === 'admin' ? '#92400e' : '#1e40af'; ?>;">
                                <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                <option value="member" <?php echo $u['role'] === 'member' ? 'selected' : ''; ?>>member</option>
                            </select>
                            <input type="hidden" name="change_role" value="1">
                        </form>
                    <?php endif; ?>
                </td>
                <td style="padding: 1rem; font-size: 0.9rem; color: #64748b;">
                    <?php echo date('d.m.Y H:i', strtotime($u['created_at'])); ?>
                </td>
                <td style="padding: 1rem; text-align: right;">
                    <?php if ($u['id'] != $current_user_id): ?>
                        <a href="?delete=<?php echo $u['id']; ?>&token=<?php echo $security->generateToken('delete_user_' . $u['id']); ?>" 
                           onclick="return confirm('Benutzer \'<?php echo htmlspecialchars($u['username']); ?>\' wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!');" 
                           class="btn" style="color: #ef4444; padding: 0.5rem 1rem;">
                            <i class="fa-solid fa-trash"></i> Löschen
                        </a>
                    <?php else: ?>
                        <span style="color: #cbd5e1; font-size: 0.85rem;">Keine Aktion</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Create User Form -->
<div id="create-user" style="margin-top: 2rem;">
    <div class="card">
        <h3 style="margin-top: 0;">
            <i class="fa-solid fa-user-plus"></i> Neuen Benutzer erstellen
        </h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $security->generateToken('create_user'); ?>">
            <input type="hidden" name="create_user" value="1">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="username" class="form-label">Benutzername *</label>
                    <input type="text" id="username" name="username" required class="form-input" placeholder="z.B. max123">
                </div>
                
                <div>
                    <label for="email" class="form-label">E-Mail *</label>
                    <input type="email" id="email" name="email" required class="form-input" placeholder="user@example.com">
                </div>
                
                <div>
                    <label for="password" class="form-label">Passwort * (min. 6 Zeichen)</label>
                    <input type="password" id="password" name="password" required class="form-input" minlength="6" placeholder="••••••">
                </div>
                
                <div>
                    <label for="role" class="form-label">Rolle *</label>
                    <select id="role" name="role" required class="form-input">
                        <option value="member">Member</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <label for="display_name" class="form-label">Anzeigename (optional)</label>
                    <input type="text" id="display_name" name="display_name" class="form-input" placeholder="Max Mustermann">
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-check"></i> Benutzer erstellen
                </button>
                <a href="<?php echo SITE_URL; ?>/admin/users" class="btn" style="background: #e2e8f0; color: #1e293b;">
                    <i class="fa-solid fa-times"></i> Abbrechen
                </a>
            </div>
        </form>
    </div>
</div>

<?php renderAdminLayoutEnd(); ?>
