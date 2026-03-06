<?php
declare(strict_types=1);

/**
 * Admin - Benutzerverwaltung (vollständig ausgebaut)
 *
 * @package CMSv2\Admin
 */

require_once __DIR__ . '/../config.php';
require_once CORE_PATH . 'autoload.php';

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Security;
use CMS\Database;
use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login');
    exit;
}

$security        = Security::instance();
$db              = Database::instance();
$prefix          = $db->prefix;
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

$messages = [];

// Alle Rollen früh laden, damit POST-Handler valide Rollen sauber prüfen können.
$allDbRoles = $db->get_results("SELECT name, display_name FROM {$prefix}roles ORDER BY sort_order, display_name");
if (empty($allDbRoles)) {
    $allDbRoles = [
        (object) ['name' => 'admin', 'display_name' => 'Administrator'],
        (object) ['name' => 'editor', 'display_name' => 'Editor'],
        (object) ['name' => 'member', 'display_name' => 'Member'],
    ];
}
$allRoleNames = array_values(array_unique(array_map(static fn($r) => (string) $r->name, $allDbRoles)));
$roleEmojis = ['admin' => '🔑', 'editor' => '✏️', 'member' => '👤', 'moderator' => '🛡️', 'contributor' => '✍️', 'viewer' => '👁️'];

// ── Create User ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_create')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        // H-24: Konsistente Sanitierung aller Eingabefelder
        $username     = Security::sanitize(trim($_POST['username']     ?? ''), 'username');
        $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password     = $_POST['password'] ?? '';
        $display_name = Security::sanitize(trim($_POST['display_name'] ?? ''), 'text');
        $role         = in_array($_POST['role'] ?? '', $allRoleNames) ? $_POST['role'] : 'member';

        if (empty($username) || empty($email) || empty($password)) {
            $messages[] = ['type' => 'error', 'text' => 'Benutzername, E-Mail und Passwort sind Pflichtfelder.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige E-Mail-Adresse.'];
        } else {
            $policyResult = \CMS\Auth::validatePasswordPolicy($password);
            if ($policyResult !== true) {
                $messages[] = ['type' => 'error', 'text' => $policyResult];
            } else {
                $existing = $db->get_var("SELECT id FROM {$prefix}users WHERE username=? OR email=?", [$username, $email]);
                if ($existing) {
                    $messages[] = ['type' => 'error', 'text' => 'Benutzername oder E-Mail bereits vergeben.'];
                } else {
                    $db->execute(
                        "INSERT INTO {$prefix}users (username, email, password, display_name, role, status, created_at) VALUES (?,?,?,?,?,?,NOW())",
                        [$username, $email, password_hash($password, PASSWORD_BCRYPT), $display_name ?: $username, $role, 'active']
                    );
                    header('Location: ' . SITE_URL . '/admin/users?msg=created');
                    exit;
                }
            }
        }
    }
}

// ── Edit User ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'edit_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_edit')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        $eid          = (int)($_POST['user_id'] ?? 0);
        $existingUser = $eid > 0 ? $db->get_row("SELECT id, role, status FROM {$prefix}users WHERE id=?", [$eid]) : null;
        // H-24: Konsistente Sanitierung
        $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $display_name = Security::sanitize(trim($_POST['display_name'] ?? ''), 'text');
        $role         = in_array($_POST['role'] ?? '', $allRoleNames, true)
            ? (string) $_POST['role']
            : (string) ($existingUser->role ?? 'member');
        $status       = in_array($_POST['status'] ?? '', ['active', 'inactive', 'banned'], true)
            ? (string) $_POST['status']
            : (string) ($existingUser->status ?? 'active');
        $new_password = $_POST['new_password'] ?? '';
        $groups       = array_map('intval', (array)($_POST['groups'] ?? []));

        if ($eid < 1 || !$existingUser || empty($email)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige Eingaben.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige E-Mail-Adresse.'];
        } else {
            $passwordValid = true;
            $upd = "UPDATE {$prefix}users SET email=?, display_name=?, role=?, status=?, updated_at=NOW()";
            $params = [$email, $display_name, $role, $status];
            if (!empty($new_password)) {
                $policyResult = \CMS\Auth::validatePasswordPolicy($new_password);
                if ($policyResult !== true) {
                    $messages[] = ['type' => 'error', 'text' => $policyResult];
                    $passwordValid = false;
                } else {
                    $upd .= ', password=?';
                    $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                }
            }
            if ($passwordValid) {
                $upd .= ' WHERE id=?';
                $params[] = $eid;
                $db->execute($upd, $params);

                // Gruppen aktualisieren
                $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id=?", [$eid]);
                foreach ($groups as $gid) {
                    if ($gid > 0) {
                        $db->execute(
                            "INSERT IGNORE INTO {$prefix}user_group_members (user_id, group_id, joined_at) VALUES (?,?,NOW())",
                            [$eid, $gid]
                        );
                    }
                }

                header('Location: ' . SITE_URL . '/admin/users?msg=updated');
                exit;
            }
        }
    }
}

// ── Delete User ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_delete')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        $did = (int)($_POST['user_id'] ?? 0);
        $deleteUser = $did > 0 ? $db->get_row("SELECT id, role FROM {$prefix}users WHERE id=?", [$did]) : null;
        if ($did <= 0 || !$deleteUser) {
            $messages[] = ['type' => 'error', 'text' => 'Benutzer konnte nicht gefunden werden.'];
        } elseif ($did === $current_user_id) {
            $messages[] = ['type' => 'error', 'text' => 'Der aktuell angemeldete Benutzer kann nicht gelöscht werden.'];
        } elseif (($deleteUser->role ?? '') === 'admin') {
            $messages[] = ['type' => 'error', 'text' => 'Administratoren können nicht über die Benutzerverwaltung gelöscht werden.'];
        } else {
            $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id=?", [$did]);
            $db->execute("DELETE FROM {$prefix}users WHERE id=?", [$did]);
            header('Location: ' . SITE_URL . '/admin/users?msg=deleted');
            exit;
        }
    }
}

// ── Bulk Action ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'bulk') {
    if ($security->verifyToken($_POST['_csrf'] ?? '', 'users_bulk')) {
        $bulk_ids    = array_map('intval', (array)($_POST['bulk_ids'] ?? []));
        $bulk_ids    = array_filter($bulk_ids, fn($id) => $id !== $current_user_id);
        $bulk_action = $_POST['bulk_action'] ?? '';
        $msg = 'bulk_done';
        if (empty($bulk_ids) || $bulk_action === '') {
            $msg = 'bulk_invalid';
        } else {
            $phs = implode(',', array_fill(0, count($bulk_ids), '?'));
            if ($bulk_action === 'delete') {
                $adminRows = $db->get_results(
                    "SELECT id FROM {$prefix}users WHERE role='admin' AND id IN ({$phs})",
                    array_values($bulk_ids)
                ) ?: [];
                $adminIds = array_map(static fn($row) => (int) $row->id, $adminRows);
                $bulk_ids = array_values(array_filter($bulk_ids, static fn($id) => !in_array($id, $adminIds, true)));
                if (!empty($adminIds)) {
                    $msg = !empty($bulk_ids) ? 'bulk_partial' : 'bulk_protected';
                }
                if (!empty($bulk_ids)) {
                    $phs = implode(',', array_fill(0, count($bulk_ids), '?'));
                    $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id IN ({$phs})", array_values($bulk_ids));
                    $db->execute("DELETE FROM {$prefix}users WHERE id IN ({$phs})", array_values($bulk_ids));
                }
            } else {
                match ($bulk_action) {
                'ban'         => $db->execute("UPDATE {$prefix}users SET status='banned' WHERE id IN ({$phs})", array_values($bulk_ids)),
                'activate'    => $db->execute("UPDATE {$prefix}users SET status='active' WHERE id IN ({$phs})",  array_values($bulk_ids)),
                'make_admin'  => $db->execute("UPDATE {$prefix}users SET role='admin' WHERE id IN ({$phs})",    array_values($bulk_ids)),
                'make_member' => $db->execute("UPDATE {$prefix}users SET role='member' WHERE id IN ({$phs})",   array_values($bulk_ids)),
                    default => $msg = 'bulk_invalid'
                };
            }
        }
        header('Location: ' . SITE_URL . '/admin/users?msg=' . urlencode($msg));
        exit;
    }
}

// ── URL messages ─────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgMap = [
        'created'   => ['success', '✅ Benutzer erfolgreich erstellt.'],
        'updated'   => ['success', '✅ Benutzer erfolgreich aktualisiert.'],
        'deleted'   => ['success', '🗑️ Benutzer gelöscht.'],
        'bulk_done' => ['success', '✅ Aktion ausgeführt.'],
        'bulk_partial' => ['warning', '⚠️ Aktion ausgeführt, Administratoren wurden dabei nicht gelöscht.'],
        'bulk_protected' => ['warning', '⚠️ Die ausgewählten Benutzer konnten nicht gelöscht werden, weil Administratoren geschützt sind.'],
        'bulk_invalid' => ['warning', '⚠️ Bitte zuerst Benutzer markieren und eine gültige Aktion auswählen.'],
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages[] = ['type' => $msgMap[$_GET['msg']][0], 'text' => $msgMap[$_GET['msg']][1]];
    }
}

// ── View / Filter ─────────────────────────────────────────────────────────────
$view       = $_GET['view']   ?? 'list';
$editUserId = (int)($_GET['id'] ?? 0);
$roleFilter = in_array($_GET['role'] ?? '', array_merge($allRoleNames, ['banned'])) ? $_GET['role'] : 'all';

$editUser = null;
if ($view === 'edit' && $editUserId > 0) {
    $editUser = $db->get_row("SELECT * FROM {$prefix}users WHERE id=?", [$editUserId]);
    if (!$editUser) {
        $messages[] = ['type' => 'error', 'text' => 'Der angeforderte Benutzer wurde nicht gefunden.'];
        $view = 'list';
        $editUserId = 0;
    }
}

// H-13: Batch-Query statt N+1-Einzelabfragen für Rollenzählung
$roleCountRows = $db->get_results(
    "SELECT role, COUNT(*) AS cnt FROM {$prefix}users WHERE status != 'banned' GROUP BY role"
);
$roleCounts = ['all' => 0];
foreach ($allRoleNames as $rn) { $roleCounts[$rn] = 0; }
foreach ($roleCountRows as $rc) {
    $roleCounts[$rc->role] = (int) $rc->cnt;
    $roleCounts['all'] += (int) $rc->cnt;
}
$roleCounts['banned'] = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users WHERE status='banned'");

$csrfCreate = $security->generateToken('users_create');
$csrfEdit   = $security->generateToken('users_edit');
$csrfDelete = $security->generateToken('users_delete');
$csrfBulk   = $security->generateToken('users_bulk');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Benutzer', 'users');
?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Verwaltung</div>
                <h2 class="page-title">👥 Benutzerverwaltung</h2>
            </div>
            <div class="col-auto ms-auto">
                <?php if ($view === 'edit' || $view === 'new'): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/users" class="btn btn-secondary">↩️ Zurück zur Liste</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/admin/users?view=new" class="btn btn-primary">➕ Neuer Benutzer</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="usersNoticeContainer"></div>

<?php foreach ($messages as $m): ?>
    <?php renderAdminAlert((string) ($m['type'] ?? 'info'), (string) ($m['text'] ?? '')); ?>
<?php endforeach; ?>

<?php
/* ================================================================
   EDIT-ANSICHT
   ================================================================ */
if ($view === 'edit' && $editUserId > 0):
    $eu = $editUser;

    $euGroups  = $db->get_results(
        "SELECT g.id, g.name FROM {$prefix}user_group_members m
         JOIN {$prefix}user_groups g ON g.id=m.group_id
         WHERE m.user_id=?", [(int)$eu->id]
    );
    $allGroups = $db->get_results("SELECT id, name FROM {$prefix}user_groups WHERE is_active=1 ORDER BY name");
?>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$eu->id; ?>" id="editUserForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfEdit; ?>">
    <input type="hidden" name="_action" value="edit_user">
    <input type="hidden" name="user_id" value="<?php echo (int)$eu->id; ?>">

    <div class="form-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap:1.5rem;">
        <div style="display:flex; flex-direction:column; gap:1.5rem;">
            <div class="card">
                <h3>👤 Benutzerdaten</h3>
                <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div class="col-md-6">
                        <label class="form-label">Benutzername</label>
                        <input type="text" value="<?php echo htmlspecialchars($eu->username, ENT_QUOTES); ?>" disabled class="form-control" style="background:#f1f5f9; color:#64748b;">
                        <small class="form-hint">Nicht änderbar.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-Mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($eu->email, ENT_QUOTES); ?>" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Anzeigename</label>
                        <input type="text" name="display_name" value="<?php echo htmlspecialchars($eu->display_name ?? '', ENT_QUOTES); ?>" class="form-control" placeholder="Anzeigename">
                    </div>
                </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">🔑 Passwort ändern</h3></div>
                <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Neues Passwort</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Neues Passwort (leer lassen für unverändert)" minlength="12" autocomplete="new-password">
                    <small class="form-hint">Passwortregel: mindestens 12 Zeichen inkl. Groß-/Kleinbuchstabe, Zahl und Sonderzeichen.</small>
                </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">👥 Gruppen</h3></div>
                <div class="card-body">
                <?php if (empty($allGroups)): ?>
                <p style="color:#94a3b8; font-size:0.9rem;">Noch keine Gruppen vorhanden. <a href="groups.php">Gruppen verwalten →</a></p>
                <?php else: ?>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.75rem;">
                    <?php foreach ($allGroups as $grp):
                        $isMember = array_filter($euGroups, fn($g) => (int)$g->id === (int)$grp->id);
                    ?>
                    <label class="checkbox-label" style="padding:0.5rem 1rem; border:1px solid #e2e8f0; border-radius:6px; background:<?php echo $isMember ? '#eff6ff' : '#fff'; ?>;">
                        <input type="checkbox" name="groups[]" value="<?php echo (int)$grp->id; ?>" <?php echo $isMember ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-hint">Gruppen-Mitgliedschaften werden nach dem Speichern aktualisiert.</small>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">⚙️ Status & Rolle</h3></div>
                <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Rolle</label>
                    <select name="role" class="form-select" <?php echo (int)$eu->id === $current_user_id ? 'disabled' : ''; ?>>
                        <?php foreach ($allDbRoles as $dbRole): ?>
                        <option value="<?php echo htmlspecialchars($dbRole->name, ENT_QUOTES); ?>" <?php echo ($eu->role ?? '') === $dbRole->name ? 'selected' : ''; ?>>
                            <?php echo $roleEmojis[$dbRole->name] ?? '🏷️'; ?> <?php echo htmlspecialchars($dbRole->display_name, ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ((int)$eu->id === $current_user_id): ?>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars((string) ($eu->role ?? 'member'), ENT_QUOTES); ?>">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" <?php echo (int)$eu->id === $current_user_id ? 'disabled' : ''; ?>>
                        <option value="active"   <?php echo ($eu->status ?? 'active') === 'active'   ? 'selected' : ''; ?>>✅ Aktiv</option>
                        <option value="inactive" <?php echo ($eu->status ?? '') === 'inactive' ? 'selected' : ''; ?>>⏸️ Inaktiv</option>
                        <option value="banned"   <?php echo ($eu->status ?? '') === 'banned'   ? 'selected' : ''; ?>>🚫 Gesperrt</option>
                    </select>
                    <?php if ((int)$eu->id === $current_user_id): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) ($eu->status ?? 'active'), ENT_QUOTES); ?>">
                    <?php endif; ?>
                </div>
                <div style="font-size:0.8rem; color:#94a3b8; border-top:1px solid #f1f5f9; padding-top:0.75rem; margin-top:1rem; display:flex; flex-direction:column; gap:0.25rem;">
                    <span>ID: #<?php echo (int)$eu->id; ?></span>
                    <span>Erstellt: <?php echo date('d.m.Y H:i', strtotime($eu->created_at)); ?></span>
                    <?php if ($eu->updated_at): ?>
                    <span>Aktualisiert: <?php echo date('d.m.Y H:i', strtotime($eu->updated_at)); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($eu->last_login)): ?>
                    <span>Letzter Login: <?php echo date('d.m.Y H:i', strtotime($eu->last_login)); ?></span>
                    <?php endif; ?>
                </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary w-100">💾 Änderungen speichern</button>
                    <?php if ((int)$eu->id !== $current_user_id): ?>
                    <button type="button" class="btn btn-danger btn-sm w-100"
                            onclick="openUserDeleteModal()">🗑️ Löschen</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ((int)$eu->id !== $current_user_id): ?>
<form id="deleteUserForm" method="post" action="<?php echo SITE_URL; ?>/admin/users" style="display:none;">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfDelete; ?>">
    <input type="hidden" name="_action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId" value="<?php echo (int)$eu->id; ?>">
</form>
<?php endif; ?>

<?php
/* ================================================================
   NEUER BENUTZER
   ================================================================ */
elseif ($view === 'new'):
?>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfCreate; ?>">
    <input type="hidden" name="_action" value="create_user">

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">👤 Benutzerdaten</h3></div>
                <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Benutzername <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" required placeholder="z.B. max123" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-Mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="user@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Passwort <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="12" placeholder="••••••••••••" autocomplete="new-password">
                        <small class="form-hint">Mindestens 12 Zeichen inkl. Groß-/Kleinbuchstabe, Zahl und Sonderzeichen.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Anzeigename</label>
                        <input type="text" name="display_name" class="form-control" placeholder="Max Mustermann">
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">⚙️ Rolle</h3></div>
                <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Rolle</label>
                    <select name="role" class="form-select">
                        <?php foreach ($allDbRoles as $dbRole): ?>
                        <option value="<?php echo htmlspecialchars($dbRole->name, ENT_QUOTES); ?>" <?php echo $dbRole->name === 'member' ? 'selected' : ''; ?>>
                            <?php echo $roleEmojis[$dbRole->name] ?? '🏷️'; ?> <?php echo htmlspecialchars($dbRole->display_name, ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">✅ Benutzer erstellen</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
/* ================================================================
   LISTEN-ANSICHT
   ================================================================ */
else:
    // Grid.js holt Daten per API – PHP-seitig nur Role-Tabs nötig
?>

<ul class="nav nav-tabs mb-3">
    <?php
    $roleTabs = ['all' => 'Alle'];
    foreach ($allDbRoles as $dbRole) {
        $emoji = $roleEmojis[$dbRole->name] ?? '🏷️';
        $roleTabs[$dbRole->name] = $emoji . ' ' . $dbRole->display_name;
    }
    $roleTabs['banned'] = '🚫 Gesperrt';
    ?>
    <?php foreach ($roleTabs as $r => $lbl): ?>
    <li class="nav-item">
        <a href="<?php echo SITE_URL; ?>/admin/users?role=<?php echo $r; ?>"
           class="nav-link <?php echo $roleFilter === $r ? 'active' : ''; ?>">
            <?php echo $lbl; ?> <span class="badge bg-secondary ms-1"><?php echo $roleCounts[$r] ?? 0; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Grid.js übernimmt die Suche -->

<form method="post" action="<?php echo SITE_URL; ?>/admin/users" id="bulkForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfBulk; ?>">
    <input type="hidden" name="_action" value="bulk">
    <div class="bulk-bar">
        <select name="bulk_action" class="form-select" style="width:auto; display:inline-block; padding:0.4rem;">
            <option value="">Aktion wählen…</option>
            <option value="activate">Aktivieren</option>
            <option value="ban">Sperren</option>
            <option value="make_member">Rolle → Member</option>
            <option value="make_admin">Rolle → Admin</option>
            <option value="delete">Endgültig löschen</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Anwenden</button>
    </div>
    <div id="bulkIdsContainer"></div>
</form>

<!-- Grid.js Users-Tabelle -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/gridjs/mermaid.min.css">
<div id="users-grid"></div>

<script src="<?php echo SITE_URL; ?>/assets/gridjs/gridjs.umd.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/gridjs-init.js"></script>
<script>
(function() {
    var SITE = <?php echo json_encode(SITE_URL); ?>;
    var ROLE = <?php echo json_encode($roleFilter); ?>;
    var ME   = <?php echo (int)$current_user_id; ?>;
    var _sel = new Set();
    var ROLE_MAP = <?php echo json_encode(array_reduce($allDbRoles, static function(array $carry, object $role) use ($roleEmojis): array {
        $carry[(string) $role->name] = [
            (string) $role->display_name,
            ((string) $role->name === 'admin') ? 'bg-yellow-lt' : (((string) $role->name === 'editor') ? 'bg-blue-lt' : 'bg-azure-lt')
        ];
        return $carry;
    }, []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    function esc(s) { return cmsEsc(s); }

    function showUsersNotice(type, message) {
        var container = document.getElementById('usersNoticeContainer');
        if (!container) {
            return;
        }
        var cls = type === 'error' ? 'alert-danger' : (type === 'warning' ? 'alert-warning' : 'alert-info');
        container.innerHTML = '<div class="alert ' + cls + ' alert-dismissible" role="alert">'
            + '<div class="d-flex"><div>' + esc(message) + '</div></div>'
            + '<a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>'
            + '</div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Bulk-Checkbox → hidden inputs beim Submit
    window._usersBulkIds = _sel;
    document.getElementById('bulkForm').addEventListener('submit', function(event) {
        var actionField = this.querySelector('[name="bulk_action"]');
        var action = actionField ? actionField.value : '';
        if (!action) {
            event.preventDefault();
            showUsersNotice('warning', 'Bitte zuerst eine Bulk-Aktion auswählen.');
            return;
        }
        if (_sel.size === 0) {
            event.preventDefault();
            showUsersNotice('warning', 'Bitte zuerst mindestens einen Benutzer markieren.');
            return;
        }
        if (action === 'delete') {
            event.preventDefault();
            cmsConfirm('Die markierten Benutzer wirklich endgültig löschen? Administratoren bleiben aus Sicherheitsgründen unberührt.', function() {
                submitBulkForm();
            }, 'Benutzer löschen');
            return;
        }
        submitBulkForm();
    });

    function submitBulkForm() {
        var c = document.getElementById('bulkIdsContainer');
        c.innerHTML = '';
        _sel.forEach(function(id) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'bulk_ids[]'; inp.value = id;
            c.appendChild(inp);
        });
        document.getElementById('bulkForm').submit();
    }

    var _colors = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];

    var statusMap = {
        active:   ['Aktiv',    'bg-success-lt'],
        inactive: ['Inaktiv',  'bg-secondary-lt'],
        banned:   ['Gesperrt', 'bg-danger-lt']
    };

    cmsGrid('#users-grid', {
        url: SITE + '/api/v1/admin/users',
        extraParams: { role: ROLE },
        limit: 20,
        sortMap: { 2: 'username', 3: 'email', 4: 'role', 5: 'status', 7: 'created_at' },
        columns: [
            { id: 'id', hidden: true },
            { id: 'display_name', hidden: true },
            {
                id: '_check',
                name: gridjs.html('<input type="checkbox" onchange="document.querySelectorAll(\'.gjs-user-chk\').forEach(function(c){if(!c.disabled){c.checked=this.checked;if(this.checked)window._usersBulkIds.add(+c.value);else window._usersBulkIds.delete(+c.value);}}.bind(this))">'),
                width: '36px',
                sort: false,
                formatter: function (_, row) {
                    var id = row.cells[0].data;
                    var dis = (id === ME) ? ' disabled' : '';
                    var chk = _sel.has(id) ? ' checked' : '';
                    return gridjs.html('<input type="checkbox" class="gjs-user-chk" value="' + id + '"' + chk + dis + ' onchange="this.checked?window._usersBulkIds.add(' + id + '):window._usersBulkIds.delete(' + id + ')">');
                }
            },
            {
                id: 'username',
                name: 'Benutzer',
                formatter: function (_, row) {
                    var d = row.cells;
                    var id = d[0].data, user = d[3].data, disp = d[1].data || '';
                    var col = _colors[Math.abs(hashCode(user)) % _colors.length];
                    var letter = user.charAt(0).toUpperCase();
                    var me = (id === ME) ? ' <span style="background:#dcfce7;color:#166534;padding:0 4px;border-radius:4px;font-size:.65rem;margin-left:4px;">Sie</span>' : '';
                    var sub = (disp && disp !== user) ? '<small style="color:#64748b;">' + esc(disp) + '</small>' : '';
                    return gridjs.html(
                        '<div style="display:flex;align-items:center;gap:.75rem;">' +
                        '<div style="width:32px;height:32px;border-radius:50%;background:' + col + ';color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;">' + letter + '</div>' +
                        '<div style="display:flex;flex-direction:column;">' +
                        '<a href="' + SITE + '/admin/users?view=edit&id=' + id + '" style="font-weight:600;color:#1e293b;text-decoration:none;">' + esc(user) + me + '</a>' +
                        sub + '</div></div>'
                    );
                }
            },
            {
                id: 'email',
                name: 'E-Mail',
                formatter: function (cell) {
                    return gridjs.html('<span style="color:#64748b;">' + esc(cell) + '</span>');
                }
            },
            {
                id: 'role',
                name: 'Rolle',
                width: '120px',
                formatter: function (cell) {
                    var r = ROLE_MAP[cell] || [cell, 'bg-azure-lt'];
                    return gridjs.html('<span class="badge ' + r[1] + '">' + r[0] + '</span>');
                }
            },
            {
                id: 'status',
                name: 'Status',
                width: '100px',
                formatter: function (cell) {
                    var s = statusMap[cell] || [cell, 'bg-secondary-lt'];
                    return gridjs.html('<span class="badge ' + s[1] + '">' + s[0] + '</span>');
                }
            },
            {
                id: 'group_count',
                name: 'Gruppen',
                width: '80px',
                sort: false,
                formatter: function (cell) {
                    return gridjs.html('<span style="color:#64748b;text-align:center;display:block;">' + Number(cell || 0) + '</span>');
                }
            },
            {
                id: 'created_at',
                name: 'Registriert',
                width: '110px',
                formatter: function (cell) {
                    return gridjs.html('<span style="font-size:.78rem;color:#64748b;" title="' + esc(cell) + '">' + cmsTimeAgo(cell) + '</span>');
                }
            },
            {
                id: '_actions',
                name: '',
                width: '100px',
                sort: false,
                formatter: function (_, row) {
                    var id = row.cells[0].data;
                    var del = (id !== ME)
                        ? '<button type="button" class="btn btn-danger btn-sm" onclick="deleteUser(' + id + ')">🗑️</button>'
                        : '';
                    return gridjs.html(
                        '<div style="display:flex;justify-content:flex-end;gap:.3rem;white-space:nowrap;">' +
                        '<a href="' + SITE + '/admin/users?view=edit&id=' + id + '" class="btn btn-secondary btn-sm" title="Bearbeiten">✏️</a>' +
                        del + '</div>'
                    );
                }
            },
            // Hidden data columns sind oben definiert (id, display_name)
        ]
    });

    // Simple string hash for avatar colors
    function hashCode(s) {
        for (var h = 0, i = 0; i < s.length; i++)
            h = ((h << 5) - h + s.charCodeAt(i)) | 0;
        return h;
    }
})();
</script>

<form id="deleteUserForm" method="post" action="<?php echo SITE_URL; ?>/admin/users" style="display:none;">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfDelete; ?>">
    <input type="hidden" name="_action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId" value="">
</form>

<?php endif; ?>

<?php renderAdminConfirmModal(); ?>

<script>
function deleteUser(id) {
    cmsConfirm('Benutzer wirklich endgültig löschen? Diese Aktion kann nicht rückgängig gemacht werden.', function() {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserForm').submit();
    }, 'Benutzer löschen');
}
function openUserDeleteModal() {
    cmsConfirm('Benutzer wirklich endgültig löschen? Diese Aktion kann nicht rückgängig gemacht werden.', function() {
        document.getElementById('deleteUserForm').submit();
    }, 'Benutzer löschen');
}
</script>

<?php renderAdminLayoutEnd(); ?>
