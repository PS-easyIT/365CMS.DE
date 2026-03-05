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
        // H-24: Konsistente Sanitierung
        $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $display_name = Security::sanitize(trim($_POST['display_name'] ?? ''), 'text');
        $role         = in_array($_POST['role'] ?? '', $allRoleNames) ? $_POST['role'] : 'member';
        $status       = in_array($_POST['status'] ?? '', ['active', 'inactive', 'banned']) ? $_POST['status'] : 'active';
        $new_password = $_POST['new_password'] ?? '';
        $groups       = array_map('intval', (array)($_POST['groups'] ?? []));

        if ($eid < 1 || empty($email)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige Eingaben.'];
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
        if ($did > 0 && $did !== $current_user_id) {
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
        if (!empty($bulk_ids)) {
            $phs = implode(',', array_fill(0, count($bulk_ids), '?'));
            match ($bulk_action) {
                'ban'         => $db->execute("UPDATE {$prefix}users SET status='banned' WHERE id IN ({$phs})", array_values($bulk_ids)),
                'activate'    => $db->execute("UPDATE {$prefix}users SET status='active' WHERE id IN ({$phs})",  array_values($bulk_ids)),
                'make_admin'  => $db->execute("UPDATE {$prefix}users SET role='admin' WHERE id IN ({$phs})",    array_values($bulk_ids)),
                'make_member' => $db->execute("UPDATE {$prefix}users SET role='member' WHERE id IN ({$phs})",   array_values($bulk_ids)),
                'delete'      => (function() use ($db, $prefix, $phs, $bulk_ids) {
                    $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id IN ({$phs})", array_values($bulk_ids));
                    $db->execute("DELETE FROM {$prefix}users WHERE id IN ({$phs})",                   array_values($bulk_ids));
                })(),
                default => null
            };
        }
        header('Location: ' . SITE_URL . '/admin/users?msg=bulk_done');
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
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages[] = ['type' => $msgMap[$_GET['msg']][0], 'text' => $msgMap[$_GET['msg']][1]];
    }
}

// ── View / Filter ─────────────────────────────────────────────────────────────
// Alle Rollen aus der Datenbank laden (inkl. benutzerdefinierter Rollen)
$allDbRoles = $db->get_results("SELECT name, display_name FROM {$prefix}roles ORDER BY sort_order, display_name");
$allRoleNames = array_map(fn($r) => $r->name, $allDbRoles);
// Fallback falls DB-Tabelle leer ist
if (empty($allRoleNames)) {
    $allRoleNames = ['admin', 'editor', 'member'];
}
// Emoji-Mapping für Rollen-Anzeige
$roleEmojis = ['admin' => '🔑', 'editor' => '✏️', 'member' => '👤', 'moderator' => '🛡️', 'contributor' => '✍️', 'viewer' => '👁️'];

$view       = $_GET['view']   ?? 'list';
$editUserId = (int)($_GET['id'] ?? 0);
$roleFilter = in_array($_GET['role'] ?? '', array_merge($allRoleNames, ['banned'])) ? $_GET['role'] : 'all';
$search     = trim($_GET['search'] ?? '');
$perPage    = 25;
$page       = max(1, (int)($_GET['p'] ?? 1));

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

<?php foreach ($messages as $m):
    $alertCls = $m['type'] === 'success' ? 'alert-success' : 'alert-danger';
?>
<div class="alert <?php echo $alertCls; ?> alert-dismissible" role="alert">
    <div class="d-flex"><div><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div></div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
</div>
<?php endforeach; ?>

<?php
/* ================================================================
   EDIT-ANSICHT
   ================================================================ */
if ($view === 'edit' && $editUserId > 0):
    $eu = $db->get_row("SELECT * FROM {$prefix}users WHERE id=?", [$editUserId]);
    if (!$eu):
        echo '<script>window.location.href="<?php echo SITE_URL; ?>/admin/users";</script>';
        exit;
    endif;

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
                    <input type="password" name="new_password" class="form-control" placeholder="min. 6 Zeichen (leer lassen für unverändert)" minlength="6" autocomplete="new-password">
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
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" <?php echo (int)$eu->id === $current_user_id ? 'disabled' : ''; ?>>
                        <option value="active"   <?php echo ($eu->status ?? 'active') === 'active'   ? 'selected' : ''; ?>>✅ Aktiv</option>
                        <option value="inactive" <?php echo ($eu->status ?? '') === 'inactive' ? 'selected' : ''; ?>>⏸️ Inaktiv</option>
                        <option value="banned"   <?php echo ($eu->status ?? '') === 'banned'   ? 'selected' : ''; ?>>🚫 Gesperrt</option>
                    </select>
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
    <input type="hidden" name="user_id" value="<?php echo (int)$eu->id; ?>">
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwField   = document.querySelector('input[name="new_password"]');
    const pwConfirm = document.getElementById('pwConfirm');
    const pwHint    = document.getElementById('pwHint');
    function checkPw() {
        if (!pwField.value) { pwHint.textContent = ''; return; }
        if (pwField.value !== pwConfirm.value) {
            pwHint.textContent = '⚠️ Passwörter stimmen nicht überein.';
            pwHint.style.color = '#b91c1c';
        } else {
            pwHint.textContent = '✅ Passwörter stimmen überein.';
            pwHint.style.color = '#15803d';
        }
    }
    if (pwField)   pwField.addEventListener('input', checkPw);
    if (pwConfirm) pwConfirm.addEventListener('input', checkPw);
});
</script>

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
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="••••••" autocomplete="new-password">
                        <small class="form-hint">Mindestens 6 Zeichen.</small>
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
    $whereParts = [];
    $params     = [];
    if ($roleFilter === 'banned') {
        $whereParts[] = "u.status = 'banned'";
    } elseif ($roleFilter !== 'all') {
        $whereParts[] = "u.role = ?";
        $params[]     = $roleFilter;
        $whereParts[] = "u.status != 'banned'";
    } else {
        $whereParts[] = "u.status != 'banned'";
    }
    if (!empty($search)) {
        $whereParts[] = "(u.username LIKE ? OR u.email LIKE ? OR u.display_name LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $where      = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';
    $total      = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users u" . $where, $params);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $users = $db->get_results(
        "SELECT u.*, (SELECT COUNT(*) FROM {$prefix}user_group_members m WHERE m.user_id=u.id) AS group_count
         FROM {$prefix}users u" . $where . " ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    );

    $buildUrl = fn(array $extra = []) =>
        SITE_URL . '/admin/users?' . http_build_query(array_merge(
            ['role' => $roleFilter],
            $search ? ['search' => $search] : [],
            $extra
        ));
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
        <a href="<?php echo SITE_URL; ?>/admin/users?role=<?php echo $r; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="nav-link <?php echo $roleFilter === $r ? 'active' : ''; ?>">
            <?php echo $lbl; ?> <span class="badge bg-secondary ms-1"><?php echo $roleCounts[$r] ?? 0; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?php echo SITE_URL; ?>/admin/users" class="row g-2 align-items-center">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>">
            <div class="col-auto" style="flex:1; max-width:400px;">
                <div class="input-group">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Suche nach Name, E-Mail…">
                    <button type="submit" class="btn btn-secondary">🔍 Suchen</button>
                </div>
            </div>
            <?php if ($search): ?>
            <div class="col-auto">
                <a href="<?php echo SITE_URL; ?>/admin/users?role=<?php echo $roleFilter; ?>" class="btn btn-secondary btn-sm">✕ Filter löschen</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users" id="bulkForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfBulk; ?>">
    <input type="hidden" name="_action" value="bulk">
    
    <div style="background:#f8fafc; padding:0.75rem; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:1rem; display:flex; align-items:center; gap:0.75rem;">
        <select name="bulk_action" class="form-select" style="width:auto; display:inline-block; padding:0.4rem;">
            <option value="">Aktion wählen…</option>
            <option value="activate">Aktivieren</option>
            <option value="ban">Sperren</option>
            <option value="make_member">Rolle → Member</option>
            <option value="make_admin">Rolle → Admin</option>
            <option value="delete">Endgültig löschen</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Anwenden</button>
        <span style="color:#64748b; font-size:0.875rem; margin-left:auto;"><?php echo $total; ?> Benutzer gesamt</span>
    </div>

    <?php if (empty($users)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem; margin:0;">👤</p>
        <p><strong>Keine Benutzer gefunden.</strong></p>
        <p class="text-muted"><?php echo $search
            ? 'Keine Treffer für <strong>' . htmlspecialchars($search, ENT_QUOTES) . '</strong>'
            : 'Erstellen Sie den ersten Benutzer.'; ?></p>
        <?php if(!$search): ?>
        <a href="<?php echo SITE_URL; ?>/admin/users?view=new" class="btn btn-primary" style="margin-top:1rem;">➕ Benutzer erstellen</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                <tr>
                    <th style="width:30px;">
                        <input type="checkbox" onchange="document.querySelectorAll('#bulkForm input[name=\'bulk_ids[]\']').forEach(c=>c.checked=this.checked)">
                    </th>
                    <th>Benutzer</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Status</th>
                    <th style="text-align:center;">Gruppen</th>
                    <th>Registriert</th>
                    <th style="text-align:right;">Aktion</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $colors      = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
                $avatarColor = $colors[abs(crc32($u->username)) % count($colors)];
                $roleLbl     = ['admin' => 'Administrator', 'editor' => 'Editor', 'member' => 'Member'][$u->role] ?? ucfirst($u->role);
                $statusLbl   = ['active' => 'Aktiv', 'inactive' => 'Inaktiv', 'banned' => 'Gesperrt'][$u->status ?? 'active'] ?? ucfirst($u->status ?? 'active');
                
                $roleBadgeClass = match($u->role) {
                    'admin' => 'bg-yellow-lt',
                    'editor' => 'bg-blue-lt', 
                    default => 'bg-azure-lt'
                };
                $statusBadgeClass = match($u->status) {
                    'active' => 'bg-success-lt',
                    'banned' => 'bg-danger-lt',
                    default => 'bg-secondary-lt'
                };
            ?>
            <tr>
                <td><input type="checkbox" name="bulk_ids[]" value="<?php echo (int)$u->id; ?>"
                           <?php echo (int)$u->id === $current_user_id ? 'disabled' : ''; ?>></td>
                <td>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div style="width:32px; height:32px; border-radius:50%; background:<?php echo $avatarColor; ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                            <?php echo strtoupper(substr($u->username, 0, 1)); ?>
                        </div>
                        <div style="display:flex; flex-direction:column;">
                            <a href="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$u->id; ?>"
                               style="font-weight:600; color:#1e293b; text-decoration:none;">
                                <?php echo htmlspecialchars($u->username, ENT_QUOTES); ?>
                                <?php if ((int)$u->id === $current_user_id): ?>
                                <span style="background:#dcfce7; color:#166534; padding:0 4px; border-radius:4px; font-size:0.65rem; margin-left:4px;">Sie</span>
                                <?php endif; ?>
                            </a>
                            <?php if (!empty($u->display_name) && $u->display_name !== $u->username): ?>
                            <small style="color:#64748b;"><?php echo htmlspecialchars($u->display_name, ENT_QUOTES); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="color:#64748b;"><?php echo htmlspecialchars($u->email, ENT_QUOTES); ?></td>
                <td><span class="badge <?php echo $roleBadgeClass; ?>"><?php echo $roleLbl; ?></span></td>
                <td><span class="badge <?php echo $statusBadgeClass; ?>"><?php echo $statusLbl; ?></span></td>
                <td style="text-align:center; color:#64748b;"><?php echo (int)($u->group_count ?? 0); ?></td>
                <td style="color:#64748b;"><?php echo date('d.m.Y', strtotime($u->created_at)); ?></td>
                <td style="text-align:right; white-space:nowrap;">
                    <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                        <a href="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$u->id; ?>"
                           class="btn btn-secondary btn-sm" title="Bearbeiten">✏️</a>
                        <?php if ((int)$u->id !== $current_user_id): ?>
                        <button type="button" class="btn btn-danger btn-sm" title="Löschen"
                                onclick="deleteUser(<?php echo (int)$u->id; ?>)">🗑️</button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination" style="display:flex; gap:0.5rem; justify-content:center; margin-top:1.5rem;">
        <?php if ($page > 1): ?><a href="<?php echo $buildUrl(['p' => $page - 1]); ?>" class="btn btn-secondary btn-sm">‹</a><?php endif; ?>
        <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <?php if ($i === $page): ?><span class="btn btn-primary btn-sm" style="pointer-events:none;"><?php echo $i; ?></span>
            <?php else: ?><a href="<?php echo $buildUrl(['p' => $i]); ?>" class="btn btn-secondary btn-sm"><?php echo $i; ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="<?php echo $buildUrl(['p' => $page + 1]); ?>" class="btn btn-secondary btn-sm">›</a><?php endif; ?>
        <span style="color:#94a3b8; font-size:0.875rem; align-self:center; margin-left:0.5rem;"><?php echo $page; ?> von <?php echo $totalPages; ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</form>

<form id="deleteUserForm" method="post" action="<?php echo SITE_URL; ?>/admin/users" style="display:none;">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfDelete; ?>">
    <input type="hidden" name="_action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId" value="">
</form>

<!-- Benutzer löschen – Bootstrap 5 Modal -->
<div class="modal modal-blur fade" id="userDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                <h3>Benutzer löschen</h3>
                <div class="text-secondary">Benutzer wirklich <strong>endgültig löschen</strong>? Diese Aktion kann nicht rückgängig gemacht werden.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn w-100" data-bs-dismiss="modal">Abbrechen</button>
                        </div>
                        <div class="col">
                            <button type="button" class="btn btn-danger w-100" id="userDeleteConfirmBtn">🗑️ Löschen</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
const _delModal = new bootstrap.Modal(document.getElementById('userDeleteModal'));
function deleteUser(id) {
    document.getElementById('userDeleteConfirmBtn').onclick = function() {
        _delModal.hide();
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserForm').submit();
    };
    _delModal.show();
}
function openUserDeleteModal() {
    document.getElementById('userDeleteConfirmBtn').onclick = function() {
        _delModal.hide();
        document.getElementById('deleteUserForm').submit();
    };
    _delModal.show();
}
</script>

<?php renderAdminLayoutEnd(); ?>
